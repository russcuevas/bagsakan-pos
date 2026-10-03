<?php

namespace App\Http\Controllers\Admin;

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

class AdminPurchaseController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'warehouse', 'creator', 'lines.product']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest('purchase_date')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::with('units')->where('is_active', true)->get();

        return view('admin.purchases.index', compact('purchases', 'suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'invoice_dr_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'auto_receive' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $purchaseNumber = 'PO-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_cost']);
            }

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'invoice_dr_number' => $validated['invoice_dr_number'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'payment_terms' => $validated['payment_terms'] ?? 'Cash',
                'payment_status' => 'unpaid',
                'status' => !empty($validated['auto_receive']) ? 'received' : 'ordered',
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $receivingItems = [];

            foreach ($validated['items'] as $item) {
                $conversionFactor = (float) $item['conversion_factor'];
                $unitCost = (float) $item['unit_cost'];
                $baseCost = $conversionFactor > 0 ? ($unitCost / $conversionFactor) : $unitCost;
                $lineSubtotal = $item['quantity'] * $unitCost;

                $line = PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'unit_name' => $item['unit_name'],
                    'conversion_factor' => $conversionFactor,
                    'quantity_ordered' => $item['quantity'],
                    'quantity_received' => !empty($validated['auto_receive']) ? $item['quantity'] : 0,
                    'unit_cost' => $unitCost,
                    'base_cost' => $baseCost,
                    'subtotal' => $lineSubtotal,
                ]);

                if (!empty($validated['auto_receive'])) {
                    $receivingItems[] = [
                        'purchase_line_id' => $line->id,
                        'product_id' => $item['product_id'],
                        'unit_name' => $item['unit_name'],
                        'conversion_factor' => $conversionFactor,
                        'quantity_received' => $item['quantity'],
                        'unit_cost' => $unitCost,
                    ];
                }
            }

            // Auto-receive goods if flagged
            if (!empty($validated['auto_receive']) && !empty($receivingItems)) {
                $this->inventoryService->receiveGoods(
                    purchaseId: $purchase->id,
                    supplierId: $purchase->supplier_id,
                    warehouseId: $purchase->warehouse_id,
                    invoiceDrNumber: $purchase->invoice_dr_number ?? 'DR-DIRECT',
                    receiptDate: $purchase->purchase_date->format('Y-m-d'),
                    items: $receivingItems,
                    userId: auth()->id()
                );
            }

            AuditLog::log('purchase_created', Purchase::class, $purchase->id, null, [
                'purchase_number' => $purchaseNumber,
                'total_amount' => $totalAmount,
                'supplier_id' => $purchase->supplier_id,
                'auto_receive' => !empty($validated['auto_receive']),
            ], 'Created Purchase Order / Inbound Purchase');
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order created and posted successfully!',
            ]);
        }

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase recorded successfully!');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'warehouse', 'creator', 'lines.product', 'batches']);
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
                $line = PurchaseLine::where('purchase_id', $purchase->id)->findOrFail($itemData['line_id']);
                $line->quantity_received += $itemData['quantity_received'];
                $line->save();

                $receivingItems[] = [
                    'purchase_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'unit_name' => $line->unit_name,
                    'conversion_factor' => $line->conversion_factor,
                    'quantity_received' => $itemData['quantity_received'],
                    'unit_cost' => $line->unit_cost,
                ];
            }

            $purchase->invoice_dr_number = $validated['invoice_dr_number'];
            $purchase->status = 'received';
            $purchase->save();

            $this->inventoryService->receiveGoods(
                purchaseId: $purchase->id,
                supplierId: $purchase->supplier_id,
                warehouseId: $purchase->warehouse_id,
                invoiceDrNumber: $validated['invoice_dr_number'],
                receiptDate: $validated['receipt_date'],
                items: $receivingItems,
                userId: auth()->id()
            );

            AuditLog::log('goods_received', Purchase::class, $purchase->id, null, [
                'dr_number' => $validated['invoice_dr_number'],
                'receipt_date' => $validated['receipt_date'],
            ], 'Goods receipt confirmed and stock posted');
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Goods received and inventory updated successfully!',
            ]);
        }

        return redirect()->route('admin.purchases.index')->with('success', 'Goods received and inventory updated!');
    }
}
