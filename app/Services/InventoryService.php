<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ReceivingBatch;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\Spoilage;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Process receiving of a purchase and update weighted average cost & batches
     */
    public function receiveGoods(
        int $purchaseId,
        ?int $supplierId,
        int $warehouseId,
        string $invoiceDrNumber,
        string $receiptDate,
        array $items,
        ?int $userId = null
    ): void {
        DB::transaction(function () use ($purchaseId, $supplierId, $warehouseId, $invoiceDrNumber, $receiptDate, $items, $userId) {
            $totalPurchaseAmount = 0;
            $supplierTotals = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $itemSupplierId = $item['supplier_id'] ?? $supplierId;
                $conversionFactor = (float) ($item['conversion_factor'] ?? 1);
                $receivedQty = (float) $item['quantity_received']; // in purchase unit
                $baseQty = $receivedQty * $conversionFactor;
                $unitCost = (float) $item['unit_cost']; // per purchase unit
                $baseCost = $conversionFactor > 0 ? ($unitCost / $conversionFactor) : $unitCost;
                $lineTotal = $receivedQty * $unitCost;
                $totalPurchaseAmount += $lineTotal;

                if ($itemSupplierId) {
                    $supplierTotals[$itemSupplierId] = ($supplierTotals[$itemSupplierId] ?? 0) + $lineTotal;
                }

                $currentStock = $product->available_stock;
                $oldAvgCost = (float) $product->average_cost;

                // Weighted Average Formula:
                // New Average Cost = ((Current Total Stock * Old Average Cost) + (New Received Base Qty * New Base Cost)) / (Current Total Stock + New Received Base Qty)
                $newTotalStock = $currentStock + $baseQty;
                if ($newTotalStock > 0) {
                    $newAvgCost = (($currentStock * $oldAvgCost) + ($baseQty * $baseCost)) / $newTotalStock;
                } else {
                    $newAvgCost = $baseCost;
                }

                $product->average_cost = $newAvgCost;
                $product->save();

                // Generate Unique Batch Code
                $batchCode = 'BATCH-' . date('Ymd', strtotime($receiptDate)) . '-' . strtoupper(substr(uniqid(), -5));

                // Create Receiving Batch (Preserves exact supplier and buying cost history)
                $batch = ReceivingBatch::create([
                    'batch_code' => $batchCode,
                    'purchase_id' => $purchaseId,
                    'purchase_line_id' => $item['purchase_line_id'] ?? null,
                    'product_id' => $product->id,
                    'supplier_id' => $itemSupplierId,
                    'warehouse_id' => $warehouseId,
                    'receipt_date' => $receiptDate,
                    'invoice_dr_number' => $invoiceDrNumber,
                    'initial_quantity' => $baseQty,
                    'current_quantity' => $baseQty,
                    'unit_cost' => $baseCost,
                    'status' => 'active',
                ]);

                // Record Inventory Movement Traceability
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => 'purchase_receipt',
                    'reference_type' => 'purchase',
                    'reference_id' => $purchaseId,
                    'quantity_change' => $baseQty,
                    'unit_cost' => $baseCost,
                    'unit_price' => null,
                    'stock_before' => $currentStock,
                    'stock_after' => $newTotalStock,
                    'notes' => "Received {$receivedQty} {$item['unit_name']} (DR/Inv: {$invoiceDrNumber})",
                    'created_by' => $userId ?? auth()->id(),
                ]);

                // Update product_supplier pivot
                if ($itemSupplierId) {
                    $product->suppliers()->syncWithoutDetaching([
                        $itemSupplierId => [
                            'last_purchase_cost' => $unitCost,
                        ]
                    ]);
                }
            }

            // Update Supplier Balances per supplier
            foreach ($supplierTotals as $supId => $amount) {
                $supplier = Supplier::find($supId);
                if ($supplier && $amount > 0) {
                    $supplier->increment('outstanding_balance', $amount);
                }
            }
        });
    }

    /**
     * Process checkout and stock deduction (supports FIFO/batch allocation, unit conversions, and anti-overselling)
     */
    public function processSale(Sale $sale, array $cartItems, ?int $userId = null): void
    {
        DB::transaction(function () use ($sale, $cartItems, $userId) {
            $totalCogs = 0;

            foreach ($cartItems as $item) {
                $product = Product::with('activeBatches')->lockForUpdate()->findOrFail($item['product_id']);
                $conversionFactor = (float) ($item['conversion_factor'] ?? 1);
                $soldQty = (float) $item['quantity']; // quantity in chosen selling unit
                $baseQtyNeeded = $soldQty * $conversionFactor;
                $unitPrice = (float) $item['unit_price'];
                $lineDiscount = (float) ($item['line_discount'] ?? 0);
                $subtotal = ($soldQty * $unitPrice) - $lineDiscount;

                // Validate available stock
                $currentStock = $product->available_stock;
                if ($currentStock < $baseQtyNeeded) {
                    throw new \Exception("Insufficient stock for {$product->name}. Requested: {$soldQty} {$item['unit_name']} ({$baseQtyNeeded} {$product->base_unit}), Available: {$currentStock} {$product->base_unit}.");
                }

                // Batch Allocation (FIFO - oldest active batch first)
                $activeBatches = $product->activeBatches()->orderBy('receipt_date', 'asc')->orderBy('id', 'asc')->get();
                $remainingBaseQtyToDeduct = $baseQtyNeeded;
                $itemTotalCogs = 0;
                $firstBatchId = null;

                foreach ($activeBatches as $batch) {
                    if ($remainingBaseQtyToDeduct <= 0) break;

                    $firstBatchId = $firstBatchId ?? $batch->id;
                    $availableInBatch = (float) $batch->current_quantity;
                    $deductFromThisBatch = min($availableInBatch, $remainingBaseQtyToDeduct);

                    $batch->current_quantity -= $deductFromThisBatch;
                    if ($batch->current_quantity <= 0) {
                        $batch->current_quantity = 0;
                        $batch->status = 'depleted';
                    }
                    $batch->save();

                    $costForPortion = $deductFromThisBatch * (float) $batch->unit_cost;
                    $itemTotalCogs += $costForPortion;
                    $remainingBaseQtyToDeduct -= $deductFromThisBatch;
                }

                // Fallback for COGS if no batch or remainder
                if ($remainingBaseQtyToDeduct > 0) {
                    $itemTotalCogs += $remainingBaseQtyToDeduct * (float) $product->average_cost;
                }

                $totalCogs += $itemTotalCogs;
                $lineUnitCost = $baseQtyNeeded > 0 ? ($itemTotalCogs / $baseQtyNeeded) : (float) $product->average_cost;

                // Create Sale Line
                SaleLine::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'batch_id' => $firstBatchId,
                    'unit_name' => $item['unit_name'],
                    'conversion_factor' => $conversionFactor,
                    'quantity' => $soldQty,
                    'base_quantity' => $baseQtyNeeded,
                    'unit_price' => $unitPrice,
                    'cost_price' => $lineUnitCost,
                    'line_cogs' => $itemTotalCogs,
                    'line_discount' => $lineDiscount,
                    'subtotal' => $subtotal,
                ]);

                // Record Inventory Movement
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $firstBatchId,
                    'warehouse_id' => $sale->warehouse_id,
                    'movement_type' => 'pos_sale',
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'quantity_change' => -$baseQtyNeeded,
                    'unit_cost' => $lineUnitCost,
                    'unit_price' => $unitPrice,
                    'stock_before' => $currentStock,
                    'stock_after' => $currentStock - $baseQtyNeeded,
                    'notes' => "Sold {$soldQty} {$item['unit_name']} via Sale #{$sale->sale_number}",
                    'created_by' => $userId ?? auth()->id(),
                ]);
            }

            // Update Sale with calculated COGS
            $sale->total_cogs = $totalCogs;
            $sale->save();

            // If Credit Sale, update Customer AR balance
            if ($sale->payment_method === 'credit' || $sale->payment_status === 'unpaid' || $sale->payment_status === 'partial') {
                if ($sale->customer_id) {
                    $unpaidAmount = $sale->total_amount - $sale->amount_paid;
                    $customer = Customer::find($sale->customer_id);
                    if ($customer) {
                        $customer->increment('current_balance', $unpaidAmount);
                    }
                }
            }

            // Audit log
            AuditLog::log(
                action: 'pos_sale_completed',
                modelType: Sale::class,
                modelId: $sale->id,
                before: null,
                after: [
                    'sale_number' => $sale->sale_number,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'customer_id' => $sale->customer_id,
                ],
                reason: 'POS Sale Checkout'
            );
        });
    }

    /**
     * Approve Spoilage and record stock loss
     */
    public function approveSpoilage(Spoilage $spoilage, int $approverId): void
    {
        DB::transaction(function () use ($spoilage, $approverId) {
            $product = Product::with('activeBatches')->lockForUpdate()->findOrFail($spoilage->product_id);
            $qtyNeeded = (float) $spoilage->quantity;
            $currentStock = $product->available_stock;

            if ($currentStock < $qtyNeeded) {
                throw new \Exception("Cannot approve spoilage: requested {$qtyNeeded} {$product->base_unit} exceeds available stock ({$currentStock} {$product->base_unit}).");
            }

            // Deduct from specified batch or FIFO
            $deductedCost = 0;
            if ($spoilage->batch_id && $batch = ReceivingBatch::find($spoilage->batch_id)) {
                $deduct = min((float)$batch->current_quantity, $qtyNeeded);
                $batch->current_quantity -= $deduct;
                if ($batch->current_quantity <= 0) {
                    $batch->status = 'depleted';
                }
                $batch->save();
                $deductedCost = $deduct * (float)$batch->unit_cost;
            } else {
                $batches = $product->activeBatches()->orderBy('receipt_date', 'asc')->get();
                $rem = $qtyNeeded;
                foreach ($batches as $b) {
                    if ($rem <= 0) break;
                    $d = min((float)$b->current_quantity, $rem);
                    $b->current_quantity -= $d;
                    if ($b->current_quantity <= 0) $b->status = 'depleted';
                    $b->save();
                    $deductedCost += $d * (float)$b->unit_cost;
                    $rem -= $d;
                }
            }

            if ($deductedCost <= 0) {
                $deductedCost = $qtyNeeded * (float)$product->average_cost;
            }

            $spoilage->unit_cost = $qtyNeeded > 0 ? ($deductedCost / $qtyNeeded) : (float)$product->average_cost;
            $spoilage->total_cost = $deductedCost;
            $spoilage->status = 'approved';
            $spoilage->approved_by = $approverId;
            $spoilage->approved_at = now();
            $spoilage->save();

            // Record Movement
            InventoryMovement::create([
                'product_id' => $product->id,
                'batch_id' => $spoilage->batch_id,
                'warehouse_id' => $spoilage->warehouse_id,
                'movement_type' => 'spoilage',
                'reference_type' => 'spoilage',
                'reference_id' => $spoilage->id,
                'quantity_change' => -$qtyNeeded,
                'unit_cost' => $spoilage->unit_cost,
                'unit_price' => null,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock - $qtyNeeded,
                'notes' => "Approved Spoilage: {$spoilage->reason} (Ref: {$spoilage->spoilage_number})",
                'created_by' => $approverId,
            ]);

            // Audit
            AuditLog::log(
                action: 'spoilage_approved',
                modelType: Spoilage::class,
                modelId: $spoilage->id,
                before: ['status' => 'pending'],
                after: ['status' => 'approved', 'loss_cost' => $deductedCost],
                reason: "Spoilage approved: {$spoilage->reason}"
            );
        });
    }

    /**
     * Void a sale transaction and restore inventory
     */
    public function voidSale(Sale $sale, string $reason, int $voidedBy): void
    {
        DB::transaction(function () use ($sale, $reason, $voidedBy) {
            if ($sale->status === 'voided') {
                throw new \Exception("Sale #{$sale->sale_number} is already voided.");
            }

            foreach ($sale->lines as $line) {
                $product = Product::findOrFail($line->product_id);
                $currentStock = $product->available_stock;

                // Return stock to batch or restore
                if ($line->batch_id && $batch = ReceivingBatch::find($line->batch_id)) {
                    $batch->current_quantity += $line->base_quantity;
                    $batch->status = 'active';
                    $batch->save();
                } else {
                    // Create adjustment or reactivate latest batch
                    $latestBatch = $product->batches()->latest()->first();
                    if ($latestBatch) {
                        $latestBatch->current_quantity += $line->base_quantity;
                        $latestBatch->status = 'active';
                        $latestBatch->save();
                    }
                }

                // Record Inventory Movement
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $line->batch_id,
                    'warehouse_id' => $sale->warehouse_id,
                    'movement_type' => 'sale_void',
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'quantity_change' => $line->base_quantity,
                    'unit_cost' => $line->cost_price,
                    'unit_price' => $line->unit_price,
                    'stock_before' => $currentStock,
                    'stock_after' => $currentStock + $line->base_quantity,
                    'notes' => "Voided Sale #{$sale->sale_number} - Restored {$line->quantity} {$line->unit_name}",
                    'created_by' => $voidedBy,
                ]);
            }

            // Reverse AR customer balance if needed
            if ($sale->customer_id && ($sale->payment_method === 'credit' || $sale->payment_status !== 'paid')) {
                $unpaid = $sale->total_amount - $sale->amount_paid;
                if ($unpaid > 0) {
                    $customer = Customer::find($sale->customer_id);
                    if ($customer) {
                        $customer->decrement('current_balance', min((float)$customer->current_balance, $unpaid));
                    }
                }
            }

            $sale->status = 'voided';
            $sale->void_reason = $reason;
            $sale->voided_by = $voidedBy;
            $sale->voided_at = now();
            $sale->save();

            AuditLog::log(
                action: 'sale_voided',
                modelType: Sale::class,
                modelId: $sale->id,
                before: ['status' => 'completed'],
                after: ['status' => 'voided', 'reason' => $reason],
                reason: "Sale #{$sale->sale_number} voided: {$reason}"
            );
        });
    }
}
