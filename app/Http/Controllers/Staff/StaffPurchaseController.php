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
        $relations = ['supplier', 'warehouse', 'lines.product'];
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
}
