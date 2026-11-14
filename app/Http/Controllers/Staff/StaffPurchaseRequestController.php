<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffPurchaseRequestController extends Controller
{
    /**
     * Display list of PRs submitted by or accessible to staff
     */
    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['requester', 'approver', 'warehouse', 'items.product', 'items.supplier', 'purchases']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchaseRequests = $query->latest('request_date')->latest('id')->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::with(['units', 'suppliers'])->where('is_active', true)->orderBy('name')->get();

        return view('staff.purchases.requests_index', compact('purchaseRequests', 'warehouses', 'suppliers', 'products'));
    }

    /**
     * Store new PR
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'request_date' => 'required|date',
            'needed_by_date' => 'nullable|date|after_or_equal:request_date',
            'purpose' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|in:draft,submitted',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.supplier_id' => 'required|exists:suppliers,id',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.estimated_unit_cost' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ]);

        $pr = DB::transaction(function () use ($validated) {
            $prNumber = 'PR-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));
            $totalEstimated = 0;

            foreach ($validated['items'] as $item) {
                $totalEstimated += ($item['quantity'] * $item['estimated_unit_cost']);
            }

            $pr = PurchaseRequest::create([
                'pr_number' => $prNumber,
                'request_date' => $validated['request_date'],
                'needed_by_date' => $validated['needed_by_date'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'status' => $validated['status'] ?? 'submitted',
                'total_estimated_amount' => $totalEstimated,
                'purpose' => $validated['purpose'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'requested_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['estimated_unit_cost'];
                $pr->items()->create([
                    'product_id' => $item['product_id'],
                    'supplier_id' => $item['supplier_id'],
                    'unit_name' => $item['unit_name'],
                    'conversion_factor' => (float) $item['conversion_factor'],
                    'quantity' => (float) $item['quantity'],
                    'estimated_unit_cost' => (float) $item['estimated_unit_cost'],
                    'estimated_subtotal' => $subtotal,
                    'original_quantity' => (float) $item['quantity'],
                    'original_unit_cost' => (float) $item['estimated_unit_cost'],
                    'original_supplier_id' => $item['supplier_id'],
                    'remarks' => $item['remarks'] ?? null,
                    'status' => 'pending',
                ]);
            }

            AuditLog::log('purchase_request_created', PurchaseRequest::class, $pr->id, null, [
                'pr_number' => $prNumber,
                'total_amount' => $totalEstimated,
                'status' => $pr->status,
            ], "Staff submitted Purchase Request #{$prNumber}");

            return $pr;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase Request submitted for approval!',
                'pr' => $pr->load(['items.product', 'items.supplier', 'warehouse']),
            ]);
        }

        return redirect()->route('staff.purchase-requests.index')->with('success', "Purchase Request {$pr->pr_number} submitted for approval.");
    }

    /**
     * Resubmit returned PR
     */
    public function resubmit(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'returned' && $purchaseRequest->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Only Draft or Returned PRs can be resubmitted.'], 422);
        }

        $purchaseRequest->status = 'submitted';
        $purchaseRequest->save();

        AuditLog::log('pr_resubmitted', PurchaseRequest::class, $purchaseRequest->id, null, ['status' => 'submitted'], "Resubmitted PR #{$purchaseRequest->pr_number}");

        return back()->with('success', "Purchase Request #{$purchaseRequest->pr_number} resubmitted for approval.");
    }

    /**
     * Show PR details (JSON for modal / viewing)
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'warehouse',
            'requester',
            'approver',
            'items.product.units',
            'items.supplier',
            'items.originalSupplier',
            'items.generatedPurchase.supplier',
            'purchases.supplier'
        ]);
        return response()->json([
            'success' => true,
            'purchase_request' => $purchaseRequest,
        ]);
    }

    /**
     * Update / Revise PR by Purchasing Staff
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (in_array($purchaseRequest->status, ['approved', 'converted_to_po'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit an approved or converted Purchase Request.',
            ], 422);
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'needed_by_date' => 'nullable|date',
            'purpose' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|in:draft,submitted',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_request_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.supplier_id' => 'required|exists:suppliers,id',
            'items.*.unit_name' => 'required|string',
            'items.*.conversion_factor' => 'required|numeric|min:0.0001',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.estimated_unit_cost' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ]);

        $pr = DB::transaction(function () use ($purchaseRequest, $validated) {
            $totalEstimated = 0;
            $processedItemIds = [];

            foreach ($validated['items'] as $itemData) {
                $qty = (float) $itemData['quantity'];
                $cost = (float) $itemData['estimated_unit_cost'];
                $subtotal = $qty * $cost;
                $totalEstimated += $subtotal;

                if (!empty($itemData['id'])) {
                    $item = PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'product_id' => $itemData['product_id'],
                            'supplier_id' => $itemData['supplier_id'],
                            'unit_name' => $itemData['unit_name'],
                            'conversion_factor' => (float) $itemData['conversion_factor'],
                            'quantity' => $qty,
                            'estimated_unit_cost' => $cost,
                            'estimated_subtotal' => $subtotal,
                            'remarks' => $itemData['remarks'] ?? $item->remarks,
                        ]);
                        $processedItemIds[] = $item->id;
                    }
                } else {
                    $newItem = $purchaseRequest->items()->create([
                        'product_id' => $itemData['product_id'],
                        'supplier_id' => $itemData['supplier_id'],
                        'unit_name' => $itemData['unit_name'],
                        'conversion_factor' => (float) $itemData['conversion_factor'],
                        'quantity' => $qty,
                        'estimated_unit_cost' => $cost,
                        'estimated_subtotal' => $subtotal,
                        'original_quantity' => $qty,
                        'original_unit_cost' => $cost,
                        'original_supplier_id' => $itemData['supplier_id'],
                        'remarks' => $itemData['remarks'] ?? 'Added during revision',
                        'status' => 'pending',
                    ]);
                    $processedItemIds[] = $newItem->id;
                }
            }

            // Delete removed items
            $purchaseRequest->items()->whereNotIn('id', $processedItemIds)->delete();

            // Set status to submitted (or draft if requested)
            $newStatus = $validated['status'] ?? 'submitted';

            $purchaseRequest->update([
                'warehouse_id' => $validated['warehouse_id'],
                'needed_by_date' => $validated['needed_by_date'] ?? $purchaseRequest->needed_by_date,
                'purpose' => $validated['purpose'] ?? $purchaseRequest->purpose,
                'remarks' => $validated['remarks'] ?? $purchaseRequest->remarks,
                'total_estimated_amount' => $totalEstimated,
                'status' => $newStatus,
            ]);

            AuditLog::log('pr_revised_by_staff', PurchaseRequest::class, $purchaseRequest->id, null, [
                'pr_number' => $purchaseRequest->pr_number,
                'total_amount' => $totalEstimated,
                'status' => $newStatus,
            ], "Purchasing Staff revised and resubmitted PR #{$purchaseRequest->pr_number}");

            return $purchaseRequest;
        });

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase Request #{$pr->pr_number} updated and submitted to Admin for checking!",
                'pr' => $pr->load(['items.product', 'items.supplier', 'warehouse']),
            ]);
        }

        return redirect()->route('staff.purchase-requests.index')->with('success', "Purchase Request #{$pr->pr_number} updated and resubmitted successfully.");
    }
}
