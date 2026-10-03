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
        $purchases = Purchase::with(['supplier', 'warehouse', 'lines.product'])->latest('purchase_date')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::with('units')->where('is_active', true)->get();

        return view('staff.purchases.index', compact('purchases', 'suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'invoice_dr_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
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

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
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

                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
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
        $purchase->load(['supplier', 'warehouse', 'lines.product', 'batches']);
        return response()->json([
            'success' => true,
            'data' => $purchase,
        ]);
    }
}
