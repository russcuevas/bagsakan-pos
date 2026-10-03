<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CashierSalesHistoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'lines.product'])
            ->where('cashier_id', auth()->id())
            ->latest('sale_date');

        if ($request->filled('date')) {
            $query->whereDate('sale_date', $request->date);
        } else {
            $query->whereDate('sale_date', today());
        }

        $sales = $query->get();

        return view('cashier.sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        // Enforce privacy: Hide COGS from cashier view
        $sale->load(['customer', 'cashier', 'lines.product']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'sale_date' => $sale->sale_date->format('Y-m-d h:i A'),
                'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                'payment_method' => strtoupper($sale->payment_method),
                'status' => $sale->status,
                'subtotal' => (float) $sale->subtotal,
                'discount_amount' => (float) $sale->discount_amount,
                'total_amount' => (float) $sale->total_amount,
                'amount_paid' => (float) $sale->amount_paid,
                'change_amount' => (float) $sale->change_amount,
                'lines' => $sale->lines->map(function ($line) {
                    return [
                        'product_name' => $line->product->name,
                        'unit_name' => $line->unit_name,
                        'quantity' => (float) $line->quantity,
                        'unit_price' => (float) $line->unit_price,
                        'subtotal' => (float) $line->subtotal,
                    ];
                }),
            ]
        ]);
    }

    public function voidSale(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'admin_password' => 'required|string',
            'reason' => 'required|string|max:255',
        ]);

        $admin = User::where('role', 'admin')->first();
        if (!$admin || !Hash::check($validated['admin_password'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Admin authorization password for transaction voiding.',
            ], 422);
        }

        try {
            $this->inventoryService->voidSale($sale, $validated['reason'], auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Sale #{$sale->sale_number} has been voided and inventory restored successfully!",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
