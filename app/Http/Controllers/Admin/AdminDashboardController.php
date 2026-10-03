<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ReceivingBatch;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\Spoilage;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Today's Key Metrics
        $todaySales = Sale::whereDate('sale_date', $today)->where('status', 'completed')->sum('total_amount');
        $todayCogs = Sale::whereDate('sale_date', $today)->where('status', 'completed')->sum('total_cogs');
        $todayGrossProfit = $todaySales - $todayCogs;
        $todayExpenses = Expense::whereDate('expense_date', $today)->sum('amount');
        $todaySpoilageLoss = Spoilage::whereDate('spoilage_date', $today)->where('status', 'approved')->sum('total_cost');

        // 2. Month to Date Metrics
        $monthSales = Sale::whereBetween('sale_date', [$startOfMonth, Carbon::now()])->where('status', 'completed')->sum('total_amount');
        $monthCogs = Sale::whereBetween('sale_date', [$startOfMonth, Carbon::now()])->where('status', 'completed')->sum('total_cogs');
        $monthGrossProfit = $monthSales - $monthCogs;
        $monthExpenses = Expense::whereBetween('expense_date', [$startOfMonth, Carbon::now()])->sum('amount');
        $monthNetProfit = $monthGrossProfit - $monthExpenses;

        // 3. Inventory Valuation (Sum of remaining batches: qty * unit_cost)
        $totalInventoryValue = ReceivingBatch::where('status', 'active')
            ->selectRaw('SUM(current_quantity * unit_cost) as val')
            ->value('val') ?? 0;

        // 4. Receivables (AR) and Payables (AP)
        $totalReceivables = Customer::sum('current_balance');
        $totalPayables = Supplier::sum('outstanding_balance');

        // 5. Stock status counts
        $allProducts = Product::with('activeBatches')->get();
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($allProducts as $p) {
            $avail = $p->available_stock;
            if ($avail <= 0) {
                $outOfStockCount++;
            } elseif ($avail <= $p->low_stock_threshold) {
                $lowStockCount++;
            }
        }

        // 6. Top Selling Products (by quantity and revenue)
        $topSelling = SaleLine::join('sales', 'sale_lines.sale_id', '=', 'sales.id')
            ->join('products', 'sale_lines.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->where('sales.sale_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.base_unit')
            ->select(
                'products.name',
                'products.sku',
                'products.base_unit',
                DB::raw('SUM(sale_lines.base_quantity) as total_qty'),
                DB::raw('SUM(sale_lines.subtotal) as total_revenue'),
                DB::raw('SUM(sale_lines.subtotal - sale_lines.line_cogs) as total_profit')
            )
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // 7. Recent Sales Transactions
        $recentSales = Sale::with(['customer', 'cashier'])
            ->latest('sale_date')
            ->limit(7)
            ->get();

        // 8. Pending Approvals (Spoilage)
        $pendingSpoilages = Spoilage::with(['product', 'submitter'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        // 9. 7-Day Sales Trend Chart Data
        $chartDates = [];
        $chartSales = [];
        $chartProfits = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $dateLabel = $date->format('M d');

            $daySale = Sale::whereDate('sale_date', $dateStr)->where('status', 'completed')->sum('total_amount');
            $dayCogs = Sale::whereDate('sale_date', $dateStr)->where('status', 'completed')->sum('total_cogs');

            $chartDates[] = $dateLabel;
            $chartSales[] = (float) $daySale;
            $chartProfits[] = (float) ($daySale - $dayCogs);
        }

        return view('admin.dashboard.index', compact(
            'todaySales',
            'todayCogs',
            'todayGrossProfit',
            'todayExpenses',
            'todaySpoilageLoss',
            'monthSales',
            'monthGrossProfit',
            'monthExpenses',
            'monthNetProfit',
            'totalInventoryValue',
            'totalReceivables',
            'totalPayables',
            'lowStockCount',
            'outOfStockCount',
            'topSelling',
            'recentSales',
            'pendingSpoilages',
            'chartDates',
            'chartSales',
            'chartProfits'
        ));
    }
}
