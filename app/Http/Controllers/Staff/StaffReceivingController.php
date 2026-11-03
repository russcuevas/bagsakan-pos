<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\ReceivingBatch;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffReceivingController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $pendingPurchases = Purchase::with(['supplier', 'warehouse', 'lines.product'])
            ->whereIn('status', ['ordered', 'partial', 'draft'])
            ->latest('purchase_date')
            ->get();

        $recentBatches = ReceivingBatch::with(['product', 'supplier', 'warehouse'])
            ->latest('receipt_date')
            ->limit(50)
            ->get();

        return view('staff.receiving.index', compact('pendingPurchases', 'recentBatches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'invoice_dr_number' => 'required|string|max:100',
            'receipt_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.line_id' => 'required|exists:purchase_lines,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
        ]);

        $purchase = Purchase::findOrFail($validated['purchase_id']);

        DB::transaction(function () use ($purchase, $validated) {
            $receivingItems = [];

            foreach ($validated['items'] as $itemData) {
                $line = PurchaseLine::where('purchase_id', $purchase->id)->findOrFail($itemData['line_id']);
                $line->quantity_received += $itemData['quantity_received'];
                $line->save();

                $receivingItems[] = [
                    'purchase_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'supplier_id' => $line->supplier_id ?? $purchase->supplier_id,
                    'unit_name' => $line->unit_name,
                    'conversion_factor' => $line->conversion_factor,
                    'quantity_received' => $itemData['quantity_received'],
                    'unit_cost' => $line->unit_cost,
                ];
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

            $this->inventoryService->receiveGoods(
                purchaseId: $purchase->id,
                supplierId: $purchase->supplier_id,
                warehouseId: $purchase->warehouse_id,
                invoiceDrNumber: $validated['invoice_dr_number'],
                receiptDate: $validated['receipt_date'],
                items: $receivingItems,
                userId: auth()->id()
            );

            AuditLog::log('staff_goods_received', Purchase::class, $purchase->id, null, [
                'dr_number' => $validated['invoice_dr_number'],
                'receipt_date' => $validated['receipt_date'],
                'status' => $newStatus,
                'is_partial' => !$allCompleted,
            ], $allCompleted ? 'Inbound stock fully received by staff' : 'Inbound stock partially received by staff (Received Partial)');
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Goods received, batch created, and inventory stock updated successfully!',
            ]);
        }

        return redirect()->route('staff.receiving.index')->with('success', 'Goods received and inventory updated!');
    }
}
