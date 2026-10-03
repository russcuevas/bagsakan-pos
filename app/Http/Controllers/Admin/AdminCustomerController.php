<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('sales')->latest()->get();
        return view('admin.customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0',
        ]);

        $customer = Customer::create($validated);

        AuditLog::log('customer_created', Customer::class, $customer->id, null, $customer->toArray(), 'New wholesale/retail customer added');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully!',
                'data' => $customer,
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Customer registered successfully!');
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales.lines.product', 'payments']);
        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $old = $customer->toArray();
        $customer->update($validated);

        AuditLog::log('customer_updated', Customer::class, $customer->id, $old, $customer->toArray(), 'Customer updated');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully!',
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully!');
    }
}
