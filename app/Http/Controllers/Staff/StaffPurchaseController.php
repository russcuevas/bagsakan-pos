<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffPurchaseController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $relations = ['supplier', 'warehouse', 'lines.product', 'batches'];
        if (method_exists(\App\Models\PurchaseLine::class, 'supplier')) {
            $relations[] = 'lines.supplier';
        }
        $purchases = Purchase::with($relations)->latest('purchase_date')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::with(['units', 'suppliers'])->where('is_active', true)->get();

        return view('staff.purchases.index', compact('purchases', 'suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'invoice_dr_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.supplier_id' => 'nullable|exists:suppliers,id',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $purchaseNumber = 'PO-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_cost']);
            }

            $lineSuppliers = collect($validated['items'])->pluck('supplier_id')->filter()->unique();
            $headerSupplierId = $validated['supplier_id'] ?? ($lineSuppliers->count() === 1 ? $lineSuppliers->first() : null);

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $headerSupplierId,
                'warehouse_id' => $validated['warehouse_id'],
                'invoice_dr_number' => $validated['invoice_dr_number'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'payment_terms' => $validated['payment_terms'] ?? 'Cash',
                'payment_status' => 'unpaid',
                'status' => 'ordered',
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $conv = (float) $item['conversion_factor'];
                $unitCost = (float) $item['unit_cost'];
                $baseCost = $conv > 0 ? ($unitCost / $conv) : $unitCost;
                $lineSupplierId = $item['supplier_id'] ?? $headerSupplierId ?? null;

                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'supplier_id' => $lineSupplierId,
                    'unit_name' => $item['unit_name'],
                    'conversion_factor' => $conv,
                    'quantity_ordered' => $item['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $unitCost,
                    'base_cost' => $baseCost,
                    'subtotal' => $item['quantity'] * $unitCost,
                ]);
            }

            AuditLog::log('staff_purchase_order_created', Purchase::class, $purchase->id, null, [
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $purchase->supplier_id,
                'is_multi_supplier' => $lineSuppliers->count() > 1,
            ], 'Purchase order placed by staff');
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order submitted successfully!',
            ]);
        }

        return redirect()->route('staff.purchases.index')->with('success', 'Purchase order created successfully!');
    }

    public function show(Purchase $purchase)
    {
        $relations = ['supplier', 'warehouse', 'lines.product', 'batches.supplier'];
        if (method_exists(\App\Models\PurchaseLine::class, 'supplier')) {
            $relations[] = 'lines.supplier';
        }
        $purchase->load($relations);
        return response()->json([
            'success' => true,
            'data' => $purchase,
        ]);
    }

    public function receive(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'invoice_dr_number' => 'required|string|max:100',
            'receipt_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.line_id' => 'required|exists:purchase_lines,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($purchase, $validated) {
            $receivingItems = [];

            foreach ($validated['items'] as $itemData) {
                $qty = (float) $itemData['quantity_received'];
                if ($qty <= 0) continue;

                $line = PurchaseLine::where('purchase_id', $purchase->id)->findOrFail($itemData['line_id']);
                $line->quantity_received += $qty;
                $line->save();

                $receivingItems[] = [
                    'purchase_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'supplier_id' => $line->supplier_id ?? $purchase->supplier_id,
                    'unit_name' => $line->unit_name,
                    'conversion_factor' => $line->conversion_factor,
                    'quantity_received' => $qty,
                    'unit_cost' => $line->unit_cost,
                ];
            }

            if (!empty($receivingItems)) {
                $this->inventoryService->receiveGoods(
                    purchaseId: $purchase->id,
                    supplierId: $purchase->supplier_id,
                    warehouseId: $purchase->warehouse_id,
                    invoiceDrNumber: $validated['invoice_dr_number'],
                    receiptDate: $validated['receipt_date'],
                    items: $receivingItems,
                    userId: auth()->id()
                );
            }

            if ($purchase->invoice_dr_number) {
                if (!str_contains($purchase->invoice_dr_number, $validated['invoice_dr_number'])) {
                    $purchase->invoice_dr_number .= ', ' . $validated['invoice_dr_number'];
                }
            } else {
                $purchase->invoice_dr_number = $validated['invoice_dr_number'];
            }

            $purchase->load('lines');
            $allCompleted = true;
            foreach ($purchase->lines as $l) {
                if ((float) $l->quantity_received < (float) $l->quantity_ordered) {
                    $allCompleted = false;
                    break;
                }
            }

            $newStatus = $allCompleted ? 'received' : 'partial';
            try {
                $purchase->status = $newStatus;
                $purchase->save();
            } catch (\Throwable $e) {
                $purchase->status = $allCompleted ? 'received' : 'ordered';
                $purchase->save();
            }

            AuditLog::log('staff_goods_received', Purchase::class, $purchase->id, null, [
                'dr_number' => $validated['invoice_dr_number'],
                'receipt_date' => $validated['receipt_date'],
                'status' => $newStatus,
                'is_partial' => !$allCompleted,
            ], $allCompleted ? 'Inbound goods fully received by purchaser' : 'Inbound goods partially received (Received Partial)');
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Goods received and inventory updated successfully!',
            ]);
        }

        return redirect()->route('staff.purchases.index')->with('success', 'Goods received and inventory updated!');
    }

    public function receivingHistory(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'warehouse',
            'lines.product',
            'lines.supplier',
            'batches.product',
            'batches.supplier',
            'batches.warehouse',
            'batches.movements'
        ]);

        $batches = $purchase->batches->map(function ($b) {
            return [
                'id' => $b->id,
                'batch_code' => $b->batch_code,
                'purchase_line_id' => $b->purchase_line_id,
                'product_name' => $b->product?->name ?? 'N/A',
                'product_sku' => $b->product?->sku ?? 'N/A',
                'supplier_name' => $b->supplier?->name ?? 'N/A',
                'warehouse_name' => $b->warehouse?->name ?? 'N/A',
                'receipt_date' => $b->receipt_date ? $b->receipt_date->format('M d, Y') : 'N/A',
                'invoice_dr_number' => $b->invoice_dr_number,
                'quantity_received' => (float) $b->initial_quantity,
                'unit_cost' => (float) $b->unit_cost,
                'received_by' => $b->movements->first()?->created_by ? (\App\Models\User::find($b->movements->first()->created_by)?->name ?? 'Purchaser') : 'Purchaser',
                'created_at' => $b->created_at ? $b->created_at->format('M d, Y h:i A') : 'N/A',
            ];
        });

        $linesSummary = $purchase->lines->map(function ($l) {
            $ordered = (float) $l->quantity_ordered;
            $received = (float) $l->quantity_received;
            $deficit = max(0, $ordered - $received);

            return [
                'id' => $l->id,
                'product_name' => $l->product?->name ?? 'N/A',
                'product_sku' => $l->product?->sku ?? 'N/A',
                'supplier_name' => $l->supplier?->name ?? 'N/A',
                'unit_name' => $l->unit_name,
                'quantity_ordered' => $ordered,
                'quantity_received' => $received,
                'deficit' => $deficit,
                'status' => $deficit <= 0 ? 'Full' : ($received > 0 ? 'Partial' : 'Pending'),
            ];
        });

        return response()->json([
            'success' => true,
            'purchase' => [
                'id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'status' => $purchase->status,
                'status_label' => $purchase->status_label,
                'total_ordered' => $purchase->total_ordered_quantity,
                'total_received' => $purchase->total_received_quantity,
                'total_deficit' => $purchase->deficit_quantity,
                'invoice_dr_number' => $purchase->invoice_dr_number,
            ],
            'lines' => $linesSummary,
            'batches' => $batches,
        ]);
    }
}
