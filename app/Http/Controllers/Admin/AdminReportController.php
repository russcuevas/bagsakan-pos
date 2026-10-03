<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\ReceivingBatch;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\Spoilage;
use App\Models\Supplier;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $query = Sale::with(['customer', 'cashier', 'lines.product'])
            ->whereDate('sale_date', '>=', $startDate)
            ->whereDate('sale_date', '<=', $endDate)
            ->where('status', 'completed');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $sales = $query->latest('sale_date')->get();

        $totalSales = $sales->sum('total_amount');
        $totalCogs = $sales->sum('total_cogs');
        $totalGrossProfit = $totalSales - $totalCogs;
        $profitMargin = $totalSales > 0 ? (($totalGrossProfit / $totalSales) * 100) : 0;

        $customers = Customer::all();

        return view('admin.reports.sales', compact('sales', 'totalSales', 'totalCogs', 'totalGrossProfit', 'profitMargin', 'startDate', 'endDate', 'customers'));
    }

    public function inventoryValuation(Request $request)
    {
        $warehouses = Warehouse::all();
        $categories = Category::all();

        $query = ReceivingBatch::with(['product.category', 'supplier', 'warehouse'])
            ->where('status', 'active')
            ->where('current_quantity', '>', 0);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $batches = $query->latest('receipt_date')->get();
        $totalValuation = $batches->sum(function ($b) {
            return $b->current_quantity * $b->unit_cost;
        });

        return view('admin.reports.inventory', compact('batches', 'totalValuation', 'warehouses', 'categories'));
    }

    public function supplierPurchases(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $suppliers = Supplier::all();
        $products = Product::all();

        $query = PurchaseLine::join('purchases', 'purchase_lines.purchase_id', '=', 'purchases.id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->join('products', 'purchase_lines.product_id', '=', 'products.id')
            ->whereDate('purchases.purchase_date', '>=', $startDate)
            ->whereDate('purchases.purchase_date', '<=', $endDate)
            ->select(
                'suppliers.name as supplier_name',
                'products.name as product_name',
                'products.sku as product_sku',
                'purchase_lines.unit_name',
                'purchase_lines.quantity_ordered',
                'purchase_lines.quantity_received',
                'purchase_lines.unit_cost',
                'purchase_lines.subtotal',
                'purchases.purchase_number',
                'purchases.invoice_dr_number',
                'purchases.purchase_date'
            );

        if ($request->filled('supplier_id')) {
            $query->where('purchases.supplier_id', $request->supplier_id);
        }
        if ($request->filled('product_id')) {
            $query->where('purchase_lines.product_id', $request->product_id);
        }

        $records = $query->latest('purchases.purchase_date')->get();
        $totalSpent = $records->sum('subtotal');

        return view('admin.reports.suppliers', compact('records', 'suppliers', 'products', 'totalSpent', 'startDate', 'endDate'));
    }

    public function profitAndLoss(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $totalSales = Sale::whereDate('sale_date', '>=', $startDate)
            ->whereDate('sale_date', '<=', $endDate)
            ->where('status', 'completed')
            ->sum('total_amount');

        $totalCogs = Sale::whereDate('sale_date', '>=', $startDate)
            ->whereDate('sale_date', '<=', $endDate)
            ->where('status', 'completed')
            ->sum('total_cogs');

        $grossProfit = $totalSales - $totalCogs;

        $expenses = Expense::whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->get();
        $totalExpenses = $expenses->sum('amount');

        $spoilageLoss = Spoilage::whereDate('spoilage_date', '>=', $startDate)
            ->whereDate('spoilage_date', '<=', $endDate)
            ->where('status', 'approved')
            ->sum('total_cost');

        $netProfit = $grossProfit - $totalExpenses - $spoilageLoss;

        return view('admin.reports.pnl', compact('totalSales', 'totalCogs', 'grossProfit', 'expenses', 'totalExpenses', 'spoilageLoss', 'netProfit', 'startDate', 'endDate'));
    }
}
