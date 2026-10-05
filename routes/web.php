<?php

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminExpenseController;
use App\Http\Controllers\Admin\AdminInventoryController;
use App\Http\Controllers\Admin\AdminPricingController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminPurchaseController;
use App\Http\Controllers\Admin\AdminPurchaseRequestController;
use App\Http\Controllers\Admin\AdminReceivableController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSpoilageController;
use App\Http\Controllers\Admin\AdminSupplierController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWarehouseController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cashier\CashierPOSController;
use App\Http\Controllers\Cashier\CashierSalesHistoryController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffInventoryController;
use App\Http\Controllers\Staff\StaffPurchaseController;
use App\Http\Controllers\Staff\StaffPurchaseRequestController;
use App\Http\Controllers\Staff\StaffReceivingController;
use App\Http\Controllers\Staff\StaffSpoilageController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isAdmin()) return redirect()->route('admin.dashboard');
        if ($user->isPurchasing()) return redirect()->route('staff.dashboard');
        if ($user->isCashier()) return redirect()->route('cashier.pos');
    }
    return redirect()->route('login');
});

// Authentication & Profile Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update')->middleware('auth');

// Shared Quotation Routes (Print & Modal data accessible to all authenticated roles)
Route::middleware(['auth'])->group(function () {
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::put('/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status');
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convertToSale'])->name('quotations.convert');
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Full System Control & Financial Visibility)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Products Masterfile
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{product}/units', [AdminProductController::class, 'storeUnit'])->name('products.units.store');
    Route::delete('/products/units/{unit}', [AdminProductController::class, 'destroyUnit'])->name('products.units.destroy');

    // Pricing & SRP History
    Route::get('/pricing', [AdminPricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing/update', [AdminPricingController::class, 'updatePrice'])->name('pricing.update');

    // Multiple Warehouses & Transfers
    Route::get('/warehouses', [AdminWarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('/warehouses', [AdminWarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('/warehouses/{warehouse}', [AdminWarehouseController::class, 'update'])->name('warehouses.update');
    Route::post('/warehouses/transfer', [AdminWarehouseController::class, 'transferStock'])->name('warehouses.transfer');

    // Quotations Module
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');

    // Purchase Requests (PR Approval & Multi-Supplier PO Splitting)
    Route::get('/purchase-requests', [AdminPurchaseRequestController::class, 'index'])->name('purchase-requests.index');
    Route::post('/purchase-requests', [AdminPurchaseRequestController::class, 'store'])->name('purchase-requests.store');
    Route::get('/purchase-requests/{purchaseRequest}', [AdminPurchaseRequestController::class, 'show'])->name('purchase-requests.show');
    Route::post('/purchase-requests/{purchaseRequest}/review', [AdminPurchaseRequestController::class, 'review'])->name('purchase-requests.review');
    Route::post('/purchase-requests/{purchaseRequest}/convert-to-pos', [AdminPurchaseRequestController::class, 'convertToPOs'])->name('purchase-requests.convert-to-pos');

    // Suppliers & AP
    Route::get('/suppliers', [AdminSupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [AdminSupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}', [AdminSupplierController::class, 'show'])->name('suppliers.show');
    Route::put('/suppliers/{supplier}', [AdminSupplierController::class, 'update'])->name('suppliers.update');
    Route::post('/suppliers/{supplier}/payment', [AdminSupplierController::class, 'recordPayment'])->name('suppliers.payment');

    // Customers & Credit Terms
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [AdminCustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');

    // Purchases & Orders
    Route::get('/purchases', [AdminPurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases', [AdminPurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [AdminPurchaseController::class, 'show'])->name('purchases.show');
    Route::post('/purchases/{purchase}/receive', [AdminPurchaseController::class, 'receive'])->name('purchases.receive');

    // Inventory & Adjustments
    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [AdminInventoryController::class, 'adjustStock'])->name('inventory.adjust');

    // Spoilage & Wastage Approvals
    Route::get('/spoilage', [AdminSpoilageController::class, 'index'])->name('spoilage.index');
    Route::post('/spoilage', [AdminSpoilageController::class, 'store'])->name('spoilage.store');
    Route::post('/spoilage/{spoilage}/approve', [AdminSpoilageController::class, 'approve'])->name('spoilage.approve');
    Route::post('/spoilage/{spoilage}/reject', [AdminSpoilageController::class, 'reject'])->name('spoilage.reject');

    // Operating Expenses
    Route::get('/expenses', [AdminExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [AdminExpenseController::class, 'store'])->name('expenses.store');

    // Accounts Receivable & Collections (with Tax / 2307 handling)
    Route::get('/receivables', [AdminReceivableController::class, 'index'])->name('receivables.index');
    Route::post('/receivables/collect', [AdminReceivableController::class, 'recordCollection'])->name('receivables.collect');

    // Financial & Inventory Reports
    Route::get('/reports/sales', [AdminReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/inventory', [AdminReportController::class, 'inventoryValuation'])->name('reports.inventory');
    Route::get('/reports/suppliers', [AdminReportController::class, 'supplierPurchases'])->name('reports.suppliers');
    Route::get('/reports/pnl', [AdminReportController::class, 'profitAndLoss'])->name('reports.pnl');

    // Users Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    // Audit Trail
    Route::get('/audit', [AdminAuditLogController::class, 'index'])->name('audit.index');
});

/*
|--------------------------------------------------------------------------
| Purchasing & Inventory Staff Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:purchasing,admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

    // Quotations Module for Staff/Purchaser
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');

    // Purchase Requests
    Route::get('/purchase-requests', [StaffPurchaseRequestController::class, 'index'])->name('purchase-requests.index');
    Route::post('/purchase-requests', [StaffPurchaseRequestController::class, 'store'])->name('purchase-requests.store');
    Route::post('/purchase-requests/{purchaseRequest}/resubmit', [StaffPurchaseRequestController::class, 'resubmit'])->name('purchase-requests.resubmit');

    // Purchase Orders
    Route::get('/purchases', [StaffPurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases', [StaffPurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [StaffPurchaseController::class, 'show'])->name('purchases.show');

    // Inbound Receiving
    Route::get('/receiving', [StaffReceivingController::class, 'index'])->name('receiving.index');
    Route::post('/receiving', [StaffReceivingController::class, 'store'])->name('receiving.store');

    // Inventory View
    Route::get('/inventory', [StaffInventoryController::class, 'index'])->name('inventory.index');

    // Spoilage Submissions
    Route::get('/spoilage', [StaffSpoilageController::class, 'index'])->name('spoilage.index');
    Route::post('/spoilage', [StaffSpoilageController::class, 'store'])->name('spoilage.store');
});

/*
|--------------------------------------------------------------------------
| Cashier & POS Terminal Routes (Strict Cost Privacy Enforced)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:cashier,admin'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/pos', [CashierPOSController::class, 'index'])->name('pos');
    Route::get('/pos/products', [CashierPOSController::class, 'searchProducts'])->name('pos.products');
    Route::post('/pos/checkout', [CashierPOSController::class, 'checkout'])->name('pos.checkout');

    // Cashier Quotations list
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');

    Route::get('/sales', [CashierSalesHistoryController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [CashierSalesHistoryController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/void', [CashierSalesHistoryController::class, 'voidSale'])->name('sales.void');
});

