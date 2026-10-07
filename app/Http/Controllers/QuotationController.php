<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display list of quotations
     */
    public function index(Request $request)
    {
        $query = Quotation::with(['customer', 'preparer', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('quotation_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'like', "%{$s}%")->orWhere('business_name', 'like', "%{$s}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('quotation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('quotation_date', '<=', $request->date_to);
        }

        $quotations = $query->latest('quotation_date')->latest('id')->paginate(15)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products = Product::with(['units', 'category'])->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('quotations.index', compact('quotations', 'customers', 'products', 'warehouses'));
    }

    /**
     * Store new quotation
     * Allows creation regardless of available stock
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:100',
            'customer_address' => 'nullable|string',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:quotation_date',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sku' => 'required|string',
            'items.*.description' => 'required|string',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        $quotation = DB::transaction(function () use ($validated) {
            $quotationNumber = 'QTN-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $discount = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $lineTotal = ($qty * $unitPrice) - $discount + $tax;
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'description' => $item['description'],
                    'unit_name' => $item['unit_name'],
                    'conversion_factor' => (float) $item['conversion_factor'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $discountAmount = (float) ($validated['discount_amount'] ?? 0);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);
            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount);

            // Customer details auto-populate if customer selected
            $custName = $validated['customer_name'] ?? null;
            $custContact = $validated['customer_contact'] ?? null;
            $custAddress = $validated['customer_address'] ?? null;

            if (!empty($validated['customer_id'])) {
                $c = Customer::find($validated['customer_id']);
                if ($c) {
                    $custName = $c->name . ($c->business_name ? " ({$c->business_name})" : '');
                    $custContact = $c->contact_number;
                    $custAddress = $c->address;
                }
            }

            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $custName,
                'customer_contact' => $custContact,
                'customer_address' => $custAddress,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'remarks' => $validated['remarks'] ?? null,
                'prepared_by' => auth()->id(),
            ]);

            foreach ($itemsData as $row) {
                $quotation->items()->create($row);
            }

            AuditLog::log('quotation_created', Quotation::class, $quotation->id, null, [
                'quotation_number' => $quotationNumber,
                'total_amount' => $totalAmount,
                'customer' => $custName,
            ], "Prepared Quotation #{$quotationNumber}");

            return $quotation;
        });

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Quotation generated successfully!',
                'quotation' => $quotation->load(['items', 'customer']),
            ]);
        }

        return back()->with('success', 'Quotation ' . $quotation->quotation_number . ' generated successfully!');
    }

    /**
     * Show quotation details (JSON for modal / viewing)
     */
    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'preparer', 'items.product.units', 'convertedSale']);

        $itemsWithStock = $quotation->items->map(function ($item) {
            $product = $item->product;
            $currentStock = $product ? (float) $product->available_stock : 0;
            $conversionFactor = (float) ($item->conversion_factor ?: 1);
            $orderedBaseQty = (float) $item->quantity * $conversionFactor;
            $projectedStock = $currentStock - $orderedBaseQty;

            return array_merge($item->toArray(), [
                'available_stock' => $currentStock,
                'base_unit' => $product?->base_unit ?? $item->unit_name,
                'ordered_base_qty' => $orderedBaseQty,
                'projected_stock' => $projectedStock,
                'needs_reorder' => $projectedStock < 0,
                'deficit_qty' => $projectedStock < 0 ? abs($projectedStock) : 0,
            ]);
        });

        return response()->json([
            'success' => true,
            'quotation' => $quotation,
            'items_with_stock' => $itemsWithStock,
        ]);
    }

    /**
     * Update quotation status (Draft -> Sent -> Accepted -> Rejected -> Expired)
     */
    public function updateStatus(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired,converted',
            'remarks' => 'nullable|string',
        ]);

        $oldStatus = $quotation->status;
        $quotation->status = $validated['status'];
        if (!empty($validated['remarks'])) {
            $quotation->remarks = ($quotation->remarks ? $quotation->remarks . "\n" : '') . "Status note: " . $validated['remarks'];
        }
        $quotation->save();

        AuditLog::log('quotation_status_changed', Quotation::class, $quotation->id, ['status' => $oldStatus], ['status' => $quotation->status], "Updated quotation status to {$quotation->status}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Quotation marked as {$quotation->status} successfully!",
                'quotation' => $quotation,
            ]);
        }

        return back()->with('success', "Quotation marked as {$quotation->status} successfully!");
    }

    /**
     * Printable view supporting "With Price" and "Without Price"
     */
    public function print(Request $request, Quotation $quotation)
    {
        $quotation->load(['customer', 'preparer', 'items.product']);
        
        $withPrice = true;
        if ($request->has('no_price') && ($request->no_price == '1' || $request->no_price === true || $request->no_price === 'true')) {
            $withPrice = false;
        } elseif ($request->has('with_price')) {
            $withPrice = filter_var($request->with_price, FILTER_VALIDATE_BOOLEAN);
        }

        return view('quotations.print', compact('quotation', 'withPrice'));
    }

    /**
     * Convert Accepted Quotation to Sales Order / Sale
     * Automatically creates sale or exports items to POS terminal without retyping
     */
    public function convertToSale(Request $request, Quotation $quotation)
    {
        $quotation->load(['items.product.units', 'customer']);

        if ($quotation->status === 'converted') {
            return response()->json([
                'success' => false,
                'message' => 'This quotation has already been converted to Sale #' . ($quotation->convertedSale?->sale_number ?? ''),
            ], 422);
        }

        // If action is returning cart items to load inside POS terminal
        if ($request->input('mode') === 'load_to_pos') {
            $cartItems = $quotation->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'name' => $item->description,
                    'unit_name' => $item->unit_name,
                    'conversion_factor' => (float) $item->conversion_factor,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_discount' => (float) $item->discount,
                    'line_tax' => (float) $item->tax,
                    'subtotal' => (float) $item->total,
                ];
            });

            return response()->json([
                'success' => true,
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'customer_id' => $quotation->customer_id,
                'customer_name' => $quotation->customer_display_name,
                'discount_amount' => (float) $quotation->discount_amount,
                'items' => $cartItems,
            ]);
        }

        // Otherwise, direct convert to Sale via request
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,credit,mixed',
            'amount_paid' => 'required|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
        ]);

        try {
            $sale = DB::transaction(function () use ($quotation, $validated) {
                $saleNumber = 'SI-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));
                $amountPaid = (float) $validated['amount_paid'];
                $totalAmount = (float) $quotation->total_amount;
                $changeAmount = max(0, $amountPaid - $totalAmount);

                $paymentStatus = 'paid';
                if ($validated['payment_method'] === 'credit') {
                    $paymentStatus = $amountPaid >= $totalAmount ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');
                } elseif ($amountPaid < $totalAmount) {
                    $paymentStatus = 'partial';
                }

                $sale = Sale::create([
                    'sale_number' => $saleNumber,
                    'customer_id' => $quotation->customer_id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'cashier_id' => auth()->id(),
                    'sale_date' => now(),
                    'subtotal' => $quotation->subtotal,
                    'discount_type' => $quotation->discount_amount > 0 ? 'fixed' : 'none',
                    'discount_value' => $quotation->discount_amount,
                    'discount_amount' => $quotation->discount_amount,
                    'discount_reason' => 'Converted from Quotation ' . $quotation->quotation_number,
                    'total_amount' => $totalAmount,
                    'total_cogs' => 0,
                    'payment_method' => $validated['payment_method'],
                    'amount_paid' => min($amountPaid, $totalAmount),
                    'change_amount' => $changeAmount,
                    'payment_status' => $paymentStatus,
                    'due_date' => $validated['due_date'] ?? null,
                    'payment_terms' => $validated['payment_terms'] ?? ($quotation->customer?->payment_terms_days ? $quotation->customer->payment_terms_days . ' Days' : 'Cash'),
                    'reference_number' => $validated['reference_number'] ?? null,
                    'status' => 'completed',
                    'notes' => 'Generated from Quotation ' . $quotation->quotation_number . ($quotation->remarks ? " | Notes: " . $quotation->remarks : ''),
                ]);

                // Prepare cart items for inventory deduction
                $cartItems = [];
                foreach ($quotation->items as $item) {
                    $cartItems[] = [
                        'product_id' => $item->product_id,
                        'unit_name' => $item->unit_name,
                        'conversion_factor' => (float) $item->conversion_factor,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'line_discount' => (float) $item->discount,
                    ];
                }

                // Process Sale inventory deduction and FIFO batch costing
                $this->inventoryService->processSale($sale, $cartItems, auth()->id());

                // Update Quotation status
                $quotation->status = 'converted';
                $quotation->converted_sale_id = $sale->id;
                $quotation->converted_at = now();
                $quotation->save();

                AuditLog::log('quotation_converted_to_sale', Quotation::class, $quotation->id, null, [
                    'quotation_number' => $quotation->quotation_number,
                    'sale_number' => $saleNumber,
                    'total_amount' => $totalAmount,
                ], "Quotation #{$quotation->quotation_number} converted to Sale #{$saleNumber}");

                return $sale;
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quotation successfully converted to Sale #' . $sale->sale_number . '!',
                    'sale' => $sale,
                ]);
            }

            return back()->with('success', 'Quotation successfully converted to Sale #' . $sale->sale_number . '!');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete draft quotation
     */
    public function destroy(Quotation $quotation)
    {
        if ($quotation->status === 'converted') {
            return back()->with('error', 'Cannot delete a quotation that has already been converted to a sale.');
        }

        $num = $quotation->quotation_number;
        $quotation->delete();

        AuditLog::log('quotation_deleted', Quotation::class, null, null, ['quotation_number' => $num], "Deleted Quotation #{$num}");

        return back()->with('success', "Quotation {$num} deleted successfully.");
    }
}
