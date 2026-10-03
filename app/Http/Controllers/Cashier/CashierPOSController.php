<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CashierPOSController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $warehouse = Warehouse::where('is_active', true)->first();
        $customers = Customer::where('is_active', true)->get();
        $categories = \App\Models\Category::withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }])->get();
        $totalAllProducts = Product::where('is_active', true)->count();
        $today = Carbon::today();
        $myShiftSales = Sale::where('cashier_id', auth()->id())
            ->whereDate('sale_date', $today)
            ->where('status', 'completed')
            ->sum('total_amount');
        $myShiftCount = Sale::where('cashier_id', auth()->id())
            ->whereDate('sale_date', $today)
            ->where('status', 'completed')
            ->count();

        return view('cashier.pos.index', compact('warehouse', 'customers', 'categories', 'totalAllProducts', 'myShiftSales', 'myShiftCount'));
    }

    /**
     * Product search / scan endpoint for POS
     * STRICT COST CONFIDENTIALITY ENFORCED:
     * Excludes unit_cost, average_cost, supplier details, or profit margins.
     */
    public function searchProducts(Request $request)
    {
        $search = $request->input('q');
        $categoryId = $request->input('category_id');

        $query = Product::with(['category', 'units' => function ($q) {
            $q->select('id', 'product_id', 'unit_name', 'conversion_factor', 'srp', 'is_default_selling');
        }])
            ->where('is_active', true)
            ->select('id', 'category_id', 'sku', 'barcode', 'name', 'base_unit', 'default_srp', 'image_path', 'low_stock_threshold');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 8);
        $paginator = $query->paginate($perPage);

        // Calculate global active counts per category so pill badges stay accurate
        $categoryCounts = Product::where('is_active', true)
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');
        $totalAll = Product::where('is_active', true)->count();

        // Map safely to ensure no backend cost leakage
        $sanitized = collect($paginator->items())->map(function ($p) {
            return [
                'id' => $p->id,
                'category_id' => $p->category_id,
                'category_name' => $p->category->name ?? 'General',
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'base_unit' => $p->base_unit,
                'default_srp' => (float) $p->default_srp,
                'available_stock' => (float) $p->available_stock,
                'image_url' => $p->image_url,
                'units' => $p->units->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'unit_name' => $u->unit_name,
                        'conversion_factor' => (float) $u->conversion_factor,
                        'srp' => (float) $u->srp,
                        'is_default_selling' => (bool) $u->is_default_selling,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $sanitized,
            'current_page' => $paginator->currentPage(),
            'has_more' => $paginator->hasMorePages(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'total_all' => $totalAll,
            'category_counts' => $categoryCounts,
        ]);
    }

    /**
     * Process checkout
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,credit,mixed',
            'amount_paid' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'supervisor_password' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_discount' => 'nullable|numeric|min:0',
        ]);

        // If discount applied, require supervisor or admin auth if configured
        if (!empty($validated['discount_value']) && $validated['discount_value'] > 0) {
            if (!empty($validated['supervisor_password'])) {
                $supervisor = User::whereIn('role', ['admin'])->first();
                if (!$supervisor || !Hash::check($validated['supervisor_password'], $supervisor->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Supervisor PIN/Password for discount authorization.',
                    ], 422);
                }
            }
        }

        // Calculate Totals
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['line_discount'] ?? 0);
            $subtotal += $lineTotal;
        }

        $discountAmount = 0;
        if (!empty($validated['discount_type']) && $validated['discount_type'] !== 'none') {
            if ($validated['discount_type'] === 'fixed') {
                $discountAmount = (float) ($validated['discount_value'] ?? 0);
            } elseif ($validated['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * ((float) ($validated['discount_value'] ?? 0) / 100));
            }
        }

        $totalAmount = max(0, $subtotal - $discountAmount);
        $amountPaid = (float) $validated['amount_paid'];

        // If credit sale, validate customer is selected
        if ($validated['payment_method'] === 'credit') {
            if (empty($validated['customer_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer must be selected for Credit / On-Account sales.',
                ], 422);
            }

            $customer = Customer::findOrFail($validated['customer_id']);
            $newBalance = $customer->current_balance + $totalAmount;
            if ($customer->credit_limit > 0 && $newBalance > $customer->credit_limit) {
                return response()->json([
                    'success' => false,
                    'message' => "Sale exceeds customer credit limit (Limit: ₱" . number_format($customer->credit_limit, 2) . ", Current Balance: ₱" . number_format($customer->current_balance, 2) . ").",
                ], 422);
            }
        }

        // Payment status & change
        $changeAmount = 0;
        $paymentStatus = 'paid';

        if ($validated['payment_method'] === 'credit') {
            if ($amountPaid >= $totalAmount) {
                $paymentStatus = 'paid';
                $changeAmount = $amountPaid - $totalAmount;
            } elseif ($amountPaid > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'unpaid';
            }
        } else {
            if ($amountPaid < $totalAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount paid (₱" . number_format($amountPaid, 2) . ") is less than the total bill (₱" . number_format($totalAmount, 2) . ").",
                ], 422);
            }
            $changeAmount = $amountPaid - $totalAmount;
        }

        try {
            $saleNumber = 'POS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'cashier_id' => auth()->id(),
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? 'none',
                'discount_value' => $validated['discount_value'] ?? 0,
                'discount_amount' => $discountAmount,
                'discount_reason' => $validated['discount_reason'] ?? null,
                'total_amount' => $totalAmount,
                'total_cogs' => 0, // Calculated inside inventoryService
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'payment_status' => $paymentStatus,
                'due_date' => $validated['due_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'status' => 'completed',
            ]);

            // Deduct inventory atomically with locks
            $this->inventoryService->processSale($sale, $validated['items'], auth()->id());

            $sale->load(['customer', 'lines.product']);

            return response()->json([
                'success' => true,
                'message' => "Transaction complete! Change: ₱" . number_format($changeAmount, 2),
                'sale' => [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'sale_date' => $sale->sale_date->format('Y-m-d h:i A'),
                    'cashier_name' => auth()->user()->name,
                    'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                    'payment_method' => strtoupper($sale->payment_method),
                    'subtotal' => (float) $sale->subtotal,
                    'discount_amount' => (float) $sale->discount_amount,
                    'total_amount' => (float) $sale->total_amount,
                    'amount_paid' => (float) $sale->amount_paid,
                    'change_amount' => (float) $sale->change_amount,
                    'lines' => $sale->lines->map(function ($l) {
                        return [
                            'name' => $l->product->name,
                            'unit' => $l->unit_name,
                            'quantity' => (float) $l->quantity,
                            'unit_price' => (float) $l->unit_price,
                            'subtotal' => (float) $l->subtotal,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
