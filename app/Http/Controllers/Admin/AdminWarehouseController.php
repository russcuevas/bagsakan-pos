<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ReceivingBatch;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::withCount(['receivingBatches as active_batches_count' => function ($q) {
            $q->where('status', 'active')->where('current_quantity', '>', 0);
        }])->get();

        $transfers = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.product'])
            ->latest('transfer_date')
            ->latest('id')
            ->paginate(10);

        $products = Product::with(['units', 'activeBatches'])->where('is_active', true)->get();

        return view('admin.warehouses.index', compact('warehouses', 'transfers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'location' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $warehouse = Warehouse::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        AuditLog::log('warehouse_created', Warehouse::class, $warehouse->id, null, $warehouse->toArray(), "Created Warehouse {$warehouse->name} ({$warehouse->code})");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Warehouse {$warehouse->name} created successfully!",
                'warehouse' => $warehouse,
            ]);
        }

        return back()->with('success', "Warehouse {$warehouse->name} created successfully!");
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'location' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $old = $warehouse->toArray();

        $warehouse->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : false,
        ]);

        AuditLog::log('warehouse_updated', Warehouse::class, $warehouse->id, $old, $warehouse->toArray(), "Updated Warehouse {$warehouse->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Warehouse {$warehouse->name} updated successfully!",
                'warehouse' => $warehouse,
            ]);
        }

        return back()->with('success', "Warehouse {$warehouse->name} updated successfully!");
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $transfer = DB::transaction(function () use ($validated) {
                $fromWh = Warehouse::findOrFail($validated['from_warehouse_id']);
                $toWh = Warehouse::findOrFail($validated['to_warehouse_id']);
                $transferNumber = 'TRF-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

                $transfer = WarehouseTransfer::create([
                    'transfer_number' => $transferNumber,
                    'from_warehouse_id' => $fromWh->id,
                    'to_warehouse_id' => $toWh->id,
                    'transfer_date' => $validated['transfer_date'],
                    'status' => 'completed',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($validated['items'] as $itemData) {
                    $product = Product::with('activeBatches')->lockForUpdate()->findOrFail($itemData['product_id']);
                    $convFactor = (float) $itemData['conversion_factor'];
                    $transferQty = (float) $itemData['quantity'];
                    $baseQtyToMove = $transferQty * $convFactor;

                    // Verify stock in source warehouse
                    $batchesInFromWh = ReceivingBatch::where('product_id', $product->id)
                        ->where('warehouse_id', $fromWh->id)
                        ->where('status', 'active')
                        ->where('current_quantity', '>', 0)
                        ->orderBy('receipt_date', 'asc')
                        ->get();

                    $availableInFromWh = $batchesInFromWh->sum('current_quantity');
                    if ($availableInFromWh < $baseQtyToMove) {
                        throw new \Exception("Insufficient stock in {$fromWh->name} for {$product->name}. Requested: {$transferQty} {$itemData['unit_name']} ({$baseQtyToMove} {$product->base_unit}), Available: {$availableInFromWh} {$product->base_unit}.");
                    }

                    $remainingBase = $baseQtyToMove;
                    $movedCostTotal = 0;
                    $firstBatch = null;

                    foreach ($batchesInFromWh as $batch) {
                        if ($remainingBase <= 0) break;
                        $firstBatch = $firstBatch ?? $batch;

                        $deduct = min((float) $batch->current_quantity, $remainingBase);
                        $batch->current_quantity -= $deduct;
                        if ($batch->current_quantity <= 0) {
                            $batch->current_quantity = 0;
                            $batch->status = 'depleted';
                        }
                        $batch->save();

                        $movedCostTotal += ($deduct * (float) $batch->unit_cost);
                        $remainingBase -= $deduct;

                        // Create batch in destination warehouse
                        $destBatchCode = 'BATCH-TRF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
                        ReceivingBatch::create([
                            'batch_code' => $destBatchCode,
                            'purchase_id' => $batch->purchase_id,
                            'purchase_line_id' => $batch->purchase_line_id,
                            'product_id' => $product->id,
                            'supplier_id' => $batch->supplier_id,
                            'warehouse_id' => $toWh->id,
                            'receipt_date' => $validated['transfer_date'],
                            'invoice_dr_number' => "TRF-FROM-{$fromWh->code}",
                            'initial_quantity' => $deduct,
                            'current_quantity' => $deduct,
                            'unit_cost' => $batch->unit_cost,
                            'status' => 'active',
                        ]);
                    }

                    $unitCostMoved = $baseQtyToMove > 0 ? ($movedCostTotal / $baseQtyToMove) : (float) $product->average_cost;

                    WarehouseTransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $product->id,
                        'batch_id' => $firstBatch?->id,
                        'unit_name' => $itemData['unit_name'],
                        'conversion_factor' => $convFactor,
                        'quantity' => $transferQty,
                        'base_quantity' => $baseQtyToMove,
                        'unit_cost' => $unitCostMoved,
                    ]);

                    // Movement OUT from source warehouse
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => $firstBatch?->id,
                        'warehouse_id' => $fromWh->id,
                        'movement_type' => 'transfer_out',
                        'reference_type' => 'warehouse_transfer',
                        'reference_id' => $transfer->id,
                        'quantity_change' => -$baseQtyToMove,
                        'unit_cost' => $unitCostMoved,
                        'unit_price' => null,
                        'stock_before' => $availableInFromWh,
                        'stock_after' => $availableInFromWh - $baseQtyToMove,
                        'notes' => "Transferred to {$toWh->name} (Ref: {$transferNumber})",
                        'created_by' => auth()->id(),
                    ]);

                    // Movement IN to destination warehouse
                    $stockBeforeDest = ReceivingBatch::where('product_id', $product->id)
                        ->where('warehouse_id', $toWh->id)
                        ->where('status', 'active')
                        ->sum('current_quantity');

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => null,
                        'warehouse_id' => $toWh->id,
                        'movement_type' => 'transfer_in',
                        'reference_type' => 'warehouse_transfer',
                        'reference_id' => $transfer->id,
                        'quantity_change' => $baseQtyToMove,
                        'unit_cost' => $unitCostMoved,
                        'unit_price' => null,
                        'stock_before' => $stockBeforeDest,
                        'stock_after' => $stockBeforeDest + $baseQtyToMove,
                        'notes' => "Received transfer from {$fromWh->name} (Ref: {$transferNumber})",
                        'created_by' => auth()->id(),
                    ]);
                }

                AuditLog::log('warehouse_transfer_completed', WarehouseTransfer::class, $transfer->id, null, [
                    'transfer_number' => $transferNumber,
                    'from' => $fromWh->name,
                    'to' => $toWh->name,
                ], "Completed stock transfer #{$transferNumber} from {$fromWh->name} to {$toWh->name}");

                return $transfer;
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Warehouse stock transfer #{$transfer->transfer_number} processed successfully!",
                    'transfer' => $transfer,
                ]);
            }

            return back()->with('success', "Warehouse stock transfer #{$transfer->transfer_number} processed successfully!");
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
