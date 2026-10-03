<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SellingPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPricingController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'units', 'priceHistories.updater'])->get();
        $histories = SellingPriceHistory::with(['product', 'updater'])->latest('effective_date')->limit(50)->get();

        return view('admin.pricing.index', compact('products', 'histories'));
    }

    public function updatePrice(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'nullable|exists:product_units,id',
            'new_srp' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);

            if (!empty($validated['unit_id'])) {
                $unit = ProductUnit::findOrFail($validated['unit_id']);
                $oldSrp = $unit->srp;
                $unit->srp = $validated['new_srp'];
                $unit->save();

                if ($unit->conversion_factor == 1) {
                    $product->default_srp = $validated['new_srp'];
                    $product->save();
                }

                $unitName = $unit->unit_name;
            } else {
                $oldSrp = $product->default_srp;
                $product->default_srp = $validated['new_srp'];
                $product->save();

                // Update base unit
                $product->units()->where('conversion_factor', 1)->update(['srp' => $validated['new_srp']]);
                $unitName = $product->base_unit;
            }

            // Record History
            SellingPriceHistory::create([
                'product_id' => $product->id,
                'unit_name' => $unitName,
                'old_srp' => $oldSrp,
                'new_srp' => $validated['new_srp'],
                'effective_date' => now(),
                'reason' => $validated['reason'],
                'updated_by' => auth()->id(),
            ]);

            AuditLog::log(
                action: 'srp_updated',
                modelType: Product::class,
                modelId: $product->id,
                before: ['unit' => $unitName, 'srp' => $oldSrp],
                after: ['unit' => $unitName, 'srp' => $validated['new_srp']],
                reason: $validated['reason']
            );
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'SRP updated and price history logged successfully!',
            ]);
        }

        return back()->with('success', 'SRP updated successfully!');
    }
}
