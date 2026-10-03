<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(50);

        $categories = Category::pluck('name', 'id')->all();
        $customers = Customer::pluck('name', 'id')->all();
        $suppliers = Supplier::pluck('name', 'id')->all();
        $warehouses = Warehouse::pluck('name', 'id')->all();
        $users = User::pluck('name', 'id')->all();
        $products = Product::pluck('name', 'id')->all();

        return view('admin.audit.index', compact(
            'logs',
            'categories',
            'customers',
            'suppliers',
            'warehouses',
            'users',
            'products'
        ));
    }
}
