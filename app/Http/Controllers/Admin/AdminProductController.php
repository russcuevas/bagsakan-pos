<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SellingPriceHistory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdminProductController extends Controller
{
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

        return view('admin.products.index', compact('products', 'categories', 'suppliers'));
    }

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required_with:units|string',
            'units.*.conversion_factor' => 'required_with:units|numeric|min:0.0001',
            'units.*.srp' => 'required_with:units|numeric|min:0',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
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

            // Additional wholesale/retail conversion units
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

            // Record initial price history
            SellingPriceHistory::create([
                'product_id' => $product->id,
                'unit_name' => $product->base_unit,
                'old_srp' => 0,
                'new_srp' => $product->default_srp,
                'effective_date' => now(),
                'reason' => 'Initial product creation SRP',
                'updated_by' => auth()->id(),
            ]);

            AuditLog::log(
                action: 'product_created',
                modelType: Product::class,
                modelId: $product->id,
                before: null,
                after: ['sku' => $product->sku, 'name' => $product->name, 'srp' => $product->default_srp],
                reason: 'New product masterfile registered'
            );
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully!',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'units', 'suppliers', 'batches.supplier', 'batches.warehouse', 'priceHistories.updater', 'movements.user']);
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

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
                // Remove old image if exists
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
                    'reason' => $request->input('srp_change_reason', 'Admin SRP Update'),
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

            // Update / Add Secondary Units if supplied
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
                reason: 'Product masterfile updated'
            );
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        if ($product->batches()->exists() || $product->movements()->exists()) {
            // If has transactions, deactivate it for safety
            $product->update(['is_active' => false]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product has existing transaction history. It has been safely deactivated instead of hard-deleted.',
                ]);
            }
            return back()->with('success', 'Product has been deactivated.');
        }

        if ($product->image_path && File::exists(public_path($product->image_path))) {
            File::delete(public_path($product->image_path));
        }

        $product->units()->delete();
        $product->priceHistories()->delete();
        $product->suppliers()->detach();
        $product->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully!',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

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
            'reason' => 'New packaging / wholesale unit registered',
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

    public function destroyUnit(ProductUnit $unit)
    {
        $product = $unit->product;

        // Prevent deleting base unit (conversion factor 1)
        if ($unit->conversion_factor == 1 && strtolower(trim($unit->unit_name)) === strtolower(trim($product->base_unit))) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the base unit of the product.',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => "Unit '{$unit->unit_name}' deleted successfully!",
        ]);
    }
}
