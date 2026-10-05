<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPurchaseRequestController extends Controller
{
    /**
     * Display list of purchase requests
     */
    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['requester', 'approver', 'warehouse', 'items.product', 'items.supplier', 'purchases']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('pr_number', 'like', "%{$s}%")
                    ->orWhere('purpose', 'like', "%{$s}%")
                    ->orWhereHas('requester', function ($rq) use ($s) {
                        $rq->where('name', 'like', "%{$s}%");
                    });
            });
        }

        $purchaseRequests = $query->latest('request_date')->latest('id')->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::with(['units', 'suppliers'])->where('is_active', true)->orderBy('name')->get();

        return view('admin.purchases.requests_index', compact('purchaseRequests', 'warehouses', 'suppliers', 'products'));
    }

    /**
     * Store new PR (Admin can also create directly)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'request_date' => 'required|date',
            'needed_by_date' => 'nullable|date|after_or_equal:request_date',
            'purpose' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|in:draft,submitted,approved',
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

            $status = $validated['status'] ?? 'submitted';

            $pr = PurchaseRequest::create([
                'pr_number' => $prNumber,
                'request_date' => $validated['request_date'],
                'needed_by_date' => $validated['needed_by_date'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'status' => $status,
                'total_estimated_amount' => $totalEstimated,
                'purpose' => $validated['purpose'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'requested_by' => auth()->id(),
                'approved_by' => $status === 'approved' ? auth()->id() : null,
                'approved_at' => $status === 'approved' ? now() : null,
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
                'status' => $status,
            ], "Created Purchase Request #{$prNumber}");

            return $pr;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase Request created successfully!',
                'pr' => $pr->load(['items.product', 'items.supplier', 'warehouse']),
            ]);
        }

        return redirect()->route('admin.purchase-requests.index')->with('success', "Purchase Request {$pr->pr_number} created successfully.");
    }

    /**
     * Show PR details (JSON)
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['warehouse', 'requester', 'approver', 'items.product.units', 'items.supplier', 'items.originalSupplier', 'purchases.supplier']);
        return response()->json([
            'success' => true,
            'purchase_request' => $purchaseRequest,
        ]);
    }

    /**
     * Supervisor/Admin Review, Modify & Approval Action
     * Allows supervisor to edit items, quantities, costs, suppliers, approve, reject, or return for revision
     */
    public function review(Request $request, PurchaseRequest $purchaseRequest)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,return',
            'rejection_reason' => 'nullable|string',
            'revision_notes' => 'nullable|string',
            'remarks' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:purchase_request_items,id',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.supplier_id' => 'required_with:items|exists:suppliers,id',
            'items.*.unit_name' => 'required_with:items|string',
            'items.*.conversion_factor' => 'required_with:items|numeric|min:0.0001',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.estimated_unit_cost' => 'required_with:items|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ]);

        $action = $validated['action'];

        DB::transaction(function () use ($purchaseRequest, $validated, $action) {
            $beforeValues = [
                'status' => $purchaseRequest->status,
                'total_amount' => $purchaseRequest->total_estimated_amount,
                'items' => $purchaseRequest->items->toArray(),
            ];

            // If modified items provided by supervisor during review
            if (!empty($validated['items'])) {
                $totalEstimated = 0;
                $processedItemIds = [];

                foreach ($validated['items'] as $itemData) {
                    $qty = (float) $itemData['quantity'];
                    $cost = (float) $itemData['estimated_unit_cost'];
                    $subtotal = $qty * $cost;
                    $totalEstimated += $subtotal;

                    if (!empty($itemData['id'])) {
                        $existingItem = PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)->find($itemData['id']);
                        if ($existingItem) {
                            $existingItem->update([
                                'product_id' => $itemData['product_id'],
                                'supplier_id' => $itemData['supplier_id'],
                                'unit_name' => $itemData['unit_name'],
                                'conversion_factor' => (float) $itemData['conversion_factor'],
                                'quantity' => $qty,
                                'estimated_unit_cost' => $cost,
                                'estimated_subtotal' => $subtotal,
                                'remarks' => $itemData['remarks'] ?? $existingItem->remarks,
                            ]);
                            $processedItemIds[] = $existingItem->id;
                        }
                    } else {
                        // New item added by supervisor
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
                            'remarks' => $itemData['remarks'] ?? 'Added during supervisor approval',
                            'status' => 'pending',
                        ]);
                        $processedItemIds[] = $newItem->id;
                    }
                }

                // Delete any removed items
                $purchaseRequest->items()->whereNotIn('id', $processedItemIds)->delete();
                $purchaseRequest->total_estimated_amount = $totalEstimated;
            }

            if ($action === 'approve') {
                $purchaseRequest->status = 'approved';
                $purchaseRequest->approved_by = auth()->id();
                $purchaseRequest->approved_at = now();
                $purchaseRequest->rejection_reason = null;
                $purchaseRequest->items()->update(['status' => 'approved']);
            } elseif ($action === 'reject') {
                $purchaseRequest->status = 'rejected';
                $purchaseRequest->rejection_reason = $validated['rejection_reason'] ?? 'Rejected by supervisor';
                $purchaseRequest->items()->update(['status' => 'rejected']);
            } elseif ($action === 'return') {
                $purchaseRequest->status = 'returned';
                $purchaseRequest->revision_notes = $validated['revision_notes'] ?? 'Returned for revision';
            }

            if (!empty($validated['remarks'])) {
                $purchaseRequest->remarks = ($purchaseRequest->remarks ? $purchaseRequest->remarks . "\n" : '') . "Review Note: " . $validated['remarks'];
            }

            $purchaseRequest->save();

            AuditLog::log(
                action: "pr_{$action}",
                modelType: PurchaseRequest::class,
                modelId: $purchaseRequest->id,
                before: $beforeValues,
                after: [
                    'status' => $purchaseRequest->status,
                    'total_amount' => $purchaseRequest->total_estimated_amount,
                    'rejection_reason' => $purchaseRequest->rejection_reason,
                    'revision_notes' => $purchaseRequest->revision_notes,
                ],
                reason: "PR #{$purchaseRequest->pr_number} action: {$action}"
            );
        });

        $msg = match ($action) {
            'approve' => "Purchase Request #{$purchaseRequest->pr_number} has been approved!",
            'reject' => "Purchase Request #{$purchaseRequest->pr_number} has been rejected.",
            'return' => "Purchase Request #{$purchaseRequest->pr_number} has been returned for revision.",
        };

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'purchase_request' => $purchaseRequest->fresh(['items.product', 'items.supplier', 'warehouse', 'approver']),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Convert Approved PR to Purchase Order(s)
     * CRITICAL REQUIREMENT:
     * If the PR contains multiple suppliers, automatically separate into individual POs by supplier!
     * Creating/approving a PO does NOT increase inventory.
     */
    public function convertToPOs(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only Approved Purchase Requests can be converted to Purchase Orders.',
            ], 422);
        }

        $purchaseRequest->load(['items.product', 'warehouse']);

        if ($purchaseRequest->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create Purchase Orders from an empty request.',
            ], 422);
        }

        $createdPOs = DB::transaction(function () use ($purchaseRequest) {
            // Group items by supplier_id
            $groupedBySupplier = $purchaseRequest->items->groupBy('supplier_id');
            $generatedPurchases = [];

            foreach ($groupedBySupplier as $supplierId => $items) {
                if (!$supplierId) continue;

                $supplier = Supplier::findOrFail($supplierId);
                $poNumber = 'PO-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));
                $poTotal = $items->sum('estimated_subtotal');

                $purchase = Purchase::create([
                    'purchase_number' => $poNumber,
                    'purchase_request_id' => $purchaseRequest->id,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $purchaseRequest->warehouse_id ?? Warehouse::where('is_active', true)->first()->id,
                    'purchase_date' => now()->toDateString(),
                    'payment_terms' => $supplier->payment_terms ?? 'Cash',
                    'payment_status' => 'unpaid',
                    'status' => 'ordered', // DOES NOT INCREASE INVENTORY!
                    'total_amount' => $poTotal,
                    'paid_amount' => 0,
                    'notes' => "Generated from PR #{$purchaseRequest->pr_number}" . ($purchaseRequest->purpose ? " (Purpose: {$purchaseRequest->purpose})" : ''),
                    'created_by' => auth()->id(),
                ]);

                foreach ($items as $item) {
                    $convFactor = (float) $item->conversion_factor;
                    $unitCost = (float) $item->estimated_unit_cost;
                    $baseCost = $convFactor > 0 ? ($unitCost / $convFactor) : $unitCost;

                    PurchaseLine::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item->product_id,
                        'supplier_id' => $item->supplier_id ?? $supplier->id,
                        'unit_name' => $item->unit_name,
                        'conversion_factor' => $convFactor,
                        'quantity_ordered' => $item->quantity,
                        'quantity_received' => 0, // Inventory remains 0 until actual receiving!
                        'unit_cost' => $unitCost,
                        'base_cost' => $baseCost,
                        'subtotal' => $item->estimated_subtotal,
                    ]);

                    $item->generated_purchase_id = $purchase->id;
                    $item->save();
                }

                $generatedPurchases[] = $purchase;

                AuditLog::log('po_generated_from_pr', Purchase::class, $purchase->id, null, [
                    'po_number' => $poNumber,
                    'pr_number' => $purchaseRequest->pr_number,
                    'supplier_id' => $supplier->id,
                    'total_amount' => $poTotal,
                ], "Generated PO #{$poNumber} for {$supplier->name} from PR #{$purchaseRequest->pr_number}");
            }

            $purchaseRequest->status = 'converted_to_po';
            $purchaseRequest->save();

            return $generatedPurchases;
        });

        $poCount = count($createdPOs);
        $poNumbers = collect($createdPOs)->pluck('purchase_number')->implode(', ');
        $message = "Successfully created {$poCount} Purchase Order(s) ({$poNumbers}) grouped by supplier!";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'created_pos' => $createdPOs,
            ]);
        }

        return back()->with('success', $message);
    }
}
