<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ReceivingBatch;
use App\Models\SellingPriceHistory;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StaffProductController extends Controller
{
    /**
     * Display listing of products for Purchasing Staff
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'units', 'suppliers', 'activeBatches']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereHas('activeBatches', function ($q) {
                    $q->havingRaw('SUM(current_quantity) <= products.low_stock_threshold');
                });
            } elseif ($request->stock_status === 'out') {
                $query->whereDoesntHave('activeBatches', function ($q) {
                    $q->where('current_quantity', '>', 0);
                });
            }
        }

        $products = $query->latest()->get();
        $categories = Category::all();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('staff.products.index', compact('products', 'categories', 'suppliers', 'warehouses'));
    }

    /**
     * Store new product masterfile (Purchasing Staff)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50|unique:products,barcode',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_unit' => 'required|string|max:20',
            'default_srp' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required_with:units|string',
            'units.*.conversion_factor' => 'required_with:units|numeric|min:0.0001',
            'units.*.srp' => 'required_with:units|numeric|min:0',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'opening_stock' => 'nullable|numeric|min:0',
            'opening_cost' => 'nullable|numeric|min:0',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = 'prod_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $targetDir = public_path('uploads/products');
                if (!File::exists($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }
                $image->move($targetDir, $filename);
                $imagePath = 'uploads/products/' . $filename;
            }

            $product = Product::create([
                'sku' => strtoupper(trim($validated['sku'])),
                'barcode' => $validated['barcode'] ? trim($validated['barcode']) : null,
                'name' => trim($validated['name']),
                'category_id' => $validated['category_id'],
                'base_unit' => trim($validated['base_unit']),
                'default_srp' => $validated['default_srp'],
                'average_cost' => 0,
                'low_stock_threshold' => $validated['low_stock_threshold'],
                'image_path' => $imagePath,
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            // Base Unit creation
            ProductUnit::create([
                'product_id' => $product->id,
                'unit_name' => $product->base_unit,
                'conversion_factor' => 1.0000,
                'srp' => $product->default_srp,
                'is_default_selling' => true,
                'is_default_purchasing' => true,
            ]);

            // Additional units
            if (!empty($validated['units'])) {
                foreach ($validated['units'] as $u) {
                    if (strtolower(trim($u['unit_name'])) !== strtolower($product->base_unit)) {
                        ProductUnit::create([
                            'product_id' => $product->id,
                            'unit_name' => trim($u['unit_name']),
                            'conversion_factor' => $u['conversion_factor'],
                            'srp' => $u['srp'],
                            'is_default_selling' => false,
                            'is_default_purchasing' => false,
                        ]);
                    }
                }
            }

            // Link Suppliers
            if (!empty($validated['supplier_ids'])) {
                $product->suppliers()->sync($validated['supplier_ids']);
            }

            // Initial price history
            SellingPriceHistory::create([
                'product_id' => $product->id,
                'unit_name' => $product->base_unit,
                'old_srp' => 0,
                'new_srp' => $product->default_srp,
                'effective_date' => now(),
                'reason' => 'Product registered by Purchasing Staff',
                'updated_by' => auth()->id(),
            ]);

            // Opening / Initial Stock initialization
            $openingStock = (float) ($validated['opening_stock'] ?? 0);
            $openingCost = (float) ($validated['opening_cost'] ?? 0);

            if ($openingStock > 0) {
                $warehouseId = $validated['warehouse_id'] ?? Warehouse::where('is_active', true)->first()?->id;

                if ($warehouseId) {
                    $product->average_cost = $openingCost;
                    $product->save();

                    $batchCode = 'BATCH-INIT-' . $product->sku . '-' . strtoupper(substr(uniqid(), -4));
                    $batch = ReceivingBatch::create([
                        'batch_code' => $batchCode,
                        'product_id' => $product->id,
                        'supplier_id' => !empty($validated['supplier_ids']) ? $validated['supplier_ids'][0] : null,
                        'warehouse_id' => $warehouseId,
                        'receipt_date' => now()->format('Y-m-d'),
                        'invoice_dr_number' => 'OPENING-STOCK',
                        'initial_quantity' => $openingStock,
                        'current_quantity' => $openingStock,
                        'unit_cost' => $openingCost,
                        'status' => 'active',
                    ]);

                    $adjNum = 'ADJ-INIT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
                    $adj = StockAdjustment::create([
                        'adjustment_number' => $adjNum,
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                        'batch_id' => $batch->id,
                        'type' => 'increase',
                        'quantity' => $openingStock,
                        'unit_cost' => $openingCost,
                        'reason' => 'Opening Stock / Panimulang Imbentaryo',
                        'status' => 'approved',
                        'submitted_by' => auth()->id(),
                        'approved_by' => auth()->id(),
                    ]);

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'batch_id' => $batch->id,
                        'warehouse_id' => $warehouseId,
                        'movement_type' => 'adjustment_in',
                        'reference_type' => 'adjustment',
                        'reference_id' => $adj->id,
                        'quantity_change' => $openingStock,
                        'unit_cost' => $openingCost,
                        'unit_price' => $product->default_srp,
                        'stock_before' => 0,
                        'stock_after' => $openingStock,
                        'notes' => 'Opening inventory balance upon product registration by Staff',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            AuditLog::log(
                action: 'product_created',
                modelType: Product::class,
                modelId: $product->id,
                before: null,
                after: ['sku' => $product->sku, 'name' => $product->name, 'srp' => $product->default_srp],
                reason: 'New product masterfile registered by Purchasing Staff'
            );
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully!',
            ]);
        }

        return redirect()->route('staff.products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Show product details
     */
    public function show(Product $product)
    {
        $product->load(['category', 'units', 'suppliers', 'batches.supplier', 'batches.warehouse', 'priceHistories.updater', 'movements.user']);
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update product details (Purchasing Staff)
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:50|unique:products,barcode,' . $product->id,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_unit' => 'required|string|max:20',
            'default_srp' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'units' => 'nullable|array',
            'supplier_ids' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $product, $validated) {
            $oldValues = $product->toArray();

            if ($request->hasFile('image')) {
                if ($product->image_path && File::exists(public_path($product->image_path))) {
                    File::delete(public_path($product->image_path));
                }

                $image = $request->file('image');
                $filename = 'prod_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $targetDir = public_path('uploads/products');
                if (!File::exists($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }
                $image->move($targetDir, $filename);
                $product->image_path = 'uploads/products/' . $filename;
            }

            // Track SRP change
            if ((float)$product->default_srp !== (float)$validated['default_srp']) {
                SellingPriceHistory::create([
                    'product_id' => $product->id,
                    'unit_name' => $validated['base_unit'],
                    'old_srp' => $product->default_srp,
                    'new_srp' => $validated['default_srp'],
                    'effective_date' => now(),
                    'reason' => $request->input('srp_change_reason', 'Purchasing Staff SRP Update'),
                    'updated_by' => auth()->id(),
                ]);
            }

            $product->sku = strtoupper(trim($validated['sku']));
            $product->barcode = $validated['barcode'] ? trim($validated['barcode']) : null;
            $product->name = trim($validated['name']);
            $product->category_id = $validated['category_id'];
            $product->base_unit = trim($validated['base_unit']);
            $product->default_srp = $validated['default_srp'];
            $product->low_stock_threshold = $validated['low_stock_threshold'];
            $product->description = $validated['description'] ?? null;
            $product->is_active = $validated['is_active'];
            $product->save();

            // Sync Suppliers
            if (isset($validated['supplier_ids'])) {
                $product->suppliers()->sync($validated['supplier_ids']);
            }

            // Update Base Unit
            $baseUnit = $product->units()->where('conversion_factor', 1)->first();
            if ($baseUnit) {
                $baseUnit->update([
                    'unit_name' => $product->base_unit,
                    'srp' => $product->default_srp,
                ]);
            }

            // Update / Add Secondary Units
            if (isset($validated['units']) && is_array($validated['units'])) {
                foreach ($validated['units'] as $u) {
                    if (!empty($u['unit_name']) && strtolower(trim($u['unit_name'])) !== strtolower($product->base_unit)) {
                        if (!empty($u['id'])) {
                            ProductUnit::where('id', $u['id'])->where('product_id', $product->id)->update([
                                'unit_name' => trim($u['unit_name']),
                                'conversion_factor' => $u['conversion_factor'],
                                'srp' => $u['srp'],
                            ]);
                        } else {
                            ProductUnit::create([
                                'product_id' => $product->id,
                                'unit_name' => trim($u['unit_name']),
                                'conversion_factor' => $u['conversion_factor'],
                                'srp' => $u['srp'],
                                'is_default_selling' => false,
                                'is_default_purchasing' => false,
                            ]);
                        }
                    }
                }
            }

            AuditLog::log(
                action: 'product_updated',
                modelType: Product::class,
                modelId: $product->id,
                before: $oldValues,
                after: $product->toArray(),
                reason: 'Product masterfile updated by Purchasing Staff'
            );
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
            ]);
        }

        return redirect()->route('staff.products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Delete or safely deactivate product
     */
    public function destroy(Product $product)
    {
        if ($product->batches()->exists() || $product->movements()->exists()) {
            $product->update(['is_active' => false]);

            AuditLog::log(
                action: 'product_deactivated',
                modelType: Product::class,
                modelId: $product->id,
                before: ['is_active' => true],
                after: ['is_active' => false],
                reason: 'Product deactivated by Purchasing Staff due to transaction history'
            );

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product has existing transaction history. It has been safely deactivated instead of hard-deleted.',
                ]);
            }
            return back()->with('success', 'Product has been deactivated.');
        }

        $prodName = $product->name;
        if ($product->image_path && File::exists(public_path($product->image_path))) {
            File::delete(public_path($product->image_path));
        }

        $product->units()->delete();
        $product->priceHistories()->delete();
        $product->suppliers()->detach();
        $product->delete();

        AuditLog::log(
            action: 'product_deleted',
            modelType: Product::class,
            modelId: null,
            before: null,
            after: ['name' => $prodName],
            reason: "Product '{$prodName}' deleted by Purchasing Staff"
        );

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully!',
            ]);
        }

        return redirect()->route('staff.products.index')->with('success', 'Product deleted successfully!');
    }

    /**
     * Store new unit conversion for product
     */
    public function storeUnit(Request $request, Product $product)
    {
        $validated = $request->validate([
            'unit_name' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'srp' => 'required|numeric|min:0.01',
        ]);

        $exists = $product->units()->whereRaw('LOWER(unit_name) = ?', [strtolower(trim($validated['unit_name']))])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "Unit '{$validated['unit_name']}' already exists for this product.",
            ], 422);
        }

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'unit_name' => trim($validated['unit_name']),
            'conversion_factor' => $validated['conversion_factor'],
            'srp' => $validated['srp'],
            'is_default_selling' => false,
            'is_default_purchasing' => false,
        ]);

        SellingPriceHistory::create([
            'product_id' => $product->id,
            'unit_name' => $unit->unit_name,
            'old_srp' => 0,
            'new_srp' => $unit->srp,
            'effective_date' => now(),
            'reason' => 'New unit registered by Purchasing Staff',
            'updated_by' => auth()->id(),
        ]);

        AuditLog::log(
            action: 'unit_created',
            modelType: Product::class,
            modelId: $product->id,
            before: null,
            after: ['unit_name' => $unit->unit_name, 'factor' => $unit->conversion_factor, 'srp' => $unit->srp],
            reason: "Added unit {$unit->unit_name} to {$product->name}"
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Unit '{$unit->unit_name}' added successfully!",
                'unit' => $unit,
            ]);
        }

        return back()->with('success', "Unit '{$unit->unit_name}' added successfully!");
    }

    /**
     * Delete unit conversion
     */
    public function destroyUnit(ProductUnit $unit)
    {
        $product = $unit->product;

        if ($unit->conversion_factor == 1 && strtolower(trim($unit->unit_name)) === strtolower(trim($product->base_unit))) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the base unit of the product.',
            ], 422);
        }

        $unitName = $unit->unit_name;
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => "Unit '{$unitName}' deleted successfully!",
        ]);
    }
}

