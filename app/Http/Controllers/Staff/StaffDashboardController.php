<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReceivingBatch;
use App\Models\Spoilage;
use App\Models\Supplier;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $pendingPurchasesCount = Purchase::where('status', 'ordered')->count();
        $recentBatches = ReceivingBatch::with(['product', 'supplier', 'warehouse'])
            ->latest('receipt_date')
            ->limit(8)
            ->get();

        $allProducts = Product::with('activeBatches')->get();
        $lowStockProducts = $allProducts->filter(function ($p) {
            return $p->available_stock <= $p->low_stock_threshold;
        });

        $mySpoilages = Spoilage::with('product')
            ->where('submitted_by', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        $suppliersCount = Supplier::where('is_active', true)->count();
        $totalActiveBatches = ReceivingBatch::where('status', 'active')->where('current_quantity', '>', 0)->count();

        return view('staff.dashboard.index', compact(
            'pendingPurchasesCount',
            'recentBatches',
            'lowStockProducts',
            'mySpoilages',
            'suppliersCount',
            'totalActiveBatches'
        ));
    }
}
