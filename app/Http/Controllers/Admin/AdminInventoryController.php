<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ReceivingBatch;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInventoryController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $categories = Category::all();

        $query = Product::with(['category', 'activeBatches.supplier', 'activeBatches.warehouse']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

        // Calculate total inventory valuation
        $totalValuation = ReceivingBatch::where('status', 'active')
            ->selectRaw('SUM(current_quantity * unit_cost) as total')
            ->value('total') ?? 0;

        $activeBatches = ReceivingBatch::with(['product', 'supplier', 'warehouse'])
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->latest('receipt_date')
            ->get();

        $movements = InventoryMovement::with(['product', 'batch', 'warehouse', 'user'])
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.inventory.index', compact('products', 'warehouses', 'categories', 'totalValuation', 'activeBatches', 'movements'));
    }

    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'batch_id' => 'nullable|exists:receiving_batches,id',
            'type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);
            $currentStock = $product->available_stock;
            $qty = (float) $validated['quantity'];
            $adjNum = 'ADJ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            if ($validated['type'] === 'decrease') {
                if ($currentStock < $qty) {
                    throw new \Exception("Cannot decrease {$qty} {$product->base_unit}. Current stock is only {$currentStock} {$product->base_unit}.");
                }

                // Deduct from batch or active batches
                if (!empty($validated['batch_id'])) {
                    $batch = ReceivingBatch::findOrFail($validated['batch_id']);
                    $deduct = min((float)$batch->current_quantity, $qty);
                    $batch->current_quantity -= $deduct;
                    if ($batch->current_quantity <= 0) $batch->status = 'depleted';
                    $batch->save();
                    $unitCost = (float)$batch->unit_cost;
                } else {
                    $batch = $product->activeBatches()->first();
                    if ($batch) {
                        $deduct = min((float)$batch->current_quantity, $qty);
                        $batch->current_quantity -= $deduct;
                        if ($batch->current_quantity <= 0) $batch->status = 'depleted';
                        $batch->save();
                    }
                    $unitCost = (float)$product->average_cost;
                }

                $qtyChange = -$qty;
                $mType = 'adjustment_out';
            } else {
                // Increase stock
                $unitCost = (float)$product->average_cost;
                if (!empty($validated['batch_id'])) {
                    $batch = ReceivingBatch::findOrFail($validated['batch_id']);
                    $batch->current_quantity += $qty;
                    $batch->status = 'active';
                    $batch->save();
                    $unitCost = (float)$batch->unit_cost;
                } else {
                    // Create adjustment batch
                    $batch = ReceivingBatch::create([
                        'batch_code' => 'BATCH-ADJ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                        'product_id' => $product->id,
                        'supplier_id' => $product->suppliers()->first()->id ?? 1,
                        'warehouse_id' => $validated['warehouse_id'],
                        'receipt_date' => now()->format('Y-m-d'),
                        'invoice_dr_number' => $adjNum,
                        'initial_quantity' => $qty,
                        'current_quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'status' => 'active',
                    ]);
                }

                $qtyChange = $qty;
                $mType = 'adjustment_in';
            }

            // Save Adjustment record
            $adj = StockAdjustment::create([
                'adjustment_number' => $adjNum,
                'product_id' => $product->id,
                'warehouse_id' => $validated['warehouse_id'],
                'batch_id' => $batch->id ?? null,
                'type' => $validated['type'],
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'reason' => $validated['reason'],
                'status' => 'approved',
                'submitted_by' => auth()->id(),
                'approved_by' => auth()->id(),
            ]);

            // Movement
            InventoryMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'movement_type' => $mType,
                'reference_type' => 'adjustment',
                'reference_id' => $adj->id,
                'quantity_change' => $qtyChange,
                'unit_cost' => $unitCost,
                'unit_price' => null,
                'stock_before' => $currentStock,
                'stock_after' => $currentStock + $qtyChange,
                'notes' => "Stock Adjustment ({$validated['type']}): {$validated['reason']}",
                'created_by' => auth()->id(),
            ]);

            AuditLog::log('stock_adjusted', StockAdjustment::class, $adj->id, null, [
                'type' => $validated['type'],
                'quantity' => $qty,
                'reason' => $validated['reason'],
            ], "Stock adjusted for product {$product->sku}");
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment posted successfully!',
            ]);
        }

        return back()->with('success', 'Stock adjustment posted successfully!');
    }
}
