<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ReceivingBatch;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StaffInventoryController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::with(['category', 'activeBatches.supplier', 'activeBatches.warehouse'])->get();

        $batches = ReceivingBatch::with(['product', 'supplier', 'warehouse'])
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->latest('receipt_date')
            ->get();

        $movements = InventoryMovement::with(['product', 'warehouse', 'user'])
            ->latest()
            ->limit(50)
            ->get();

        return view('staff.inventory.index', compact('products', 'warehouses', 'batches', 'movements'));
    }
}
