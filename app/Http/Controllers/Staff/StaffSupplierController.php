<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffSupplierController extends Controller
{
    /**
     * Display listing of suppliers for Purchasing Staff
     */
    public function index()
    {
        $suppliers = Supplier::withCount('purchases')->withCount('products')->latest()->get();
        return view('staff.suppliers.index', compact('suppliers'));
    }

    /**
     * Store new supplier masterfile (Purchasing Staff)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tin' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:50',
            'bank_info' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        AuditLog::log('supplier_created', Supplier::class, $supplier->id, null, $supplier->toArray(), 'New supplier registered by Purchasing Staff');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier added successfully!',
                'data' => $supplier,
            ]);
        }

        return redirect()->route('staff.suppliers.index')->with('success', 'Supplier created successfully!');
    }

    /**
     * Show supplier details
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['products', 'purchases.lines.product', 'receivingBatches.product', 'payments']);
        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update supplier profile (Purchasing Staff)
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tin' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:50',
            'bank_info' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        }

        $old = $supplier->toArray();
        $supplier->update($validated);

        AuditLog::log('supplier_updated', Supplier::class, $supplier->id, $old, $supplier->toArray(), 'Supplier details updated by Purchasing Staff');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully!',
            ]);
        }

        return redirect()->route('staff.suppliers.index')->with('success', 'Supplier updated successfully!');
    }

    /**
     * Delete or safely deactivate supplier (Purchasing Staff)
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists() || $supplier->receivingBatches()->exists() || $supplier->payments()->exists()) {
            $supplier->update(['is_active' => false]);

            AuditLog::log(
                action: 'supplier_deactivated',
                modelType: Supplier::class,
                modelId: $supplier->id,
                before: ['is_active' => true],
                after: ['is_active' => false],
                reason: 'Supplier deactivated by Purchasing Staff due to transaction history'
            );

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier has existing transaction history. It has been safely deactivated instead of permanently deleted.',
                ]);
            }
            return back()->with('success', 'Supplier has been safely deactivated.');
        }

        $supplierName = $supplier->name;
        $supplier->products()->detach();
        $supplier->delete();

        AuditLog::log(
            action: 'supplier_deleted',
            modelType: Supplier::class,
            modelId: null,
            before: null,
            after: ['name' => $supplierName],
            reason: "Deleted supplier '{$supplierName}' by Purchasing Staff"
        );

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier '{$supplierName}' deleted successfully.",
            ]);
        }

        return redirect()->route('staff.suppliers.index')->with('success', "Supplier '{$supplierName}' deleted successfully.");
    }

    /**
     * Record supplier payment disbursement
     */
    public function recordPayment(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . max(1, $supplier->outstanding_balance),
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,check',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($supplier, $validated) {
            $paymentNumber = 'PAY-SUP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $supplier->id,
                'type' => 'supplier_payment',
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'received_by' => auth()->id(),
            ]);

            $supplier->decrement('outstanding_balance', $validated['amount']);

            AuditLog::log('supplier_payment_recorded', Payment::class, $payment->id, null, [
                'supplier_id' => $supplier->id,
                'amount' => $validated['amount'],
                'payment_number' => $paymentNumber,
            ], 'Supplier payment disbursed by Purchasing Staff');
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier payment recorded successfully!',
            ]);
        }

        return back()->with('success', 'Supplier payment recorded successfully!');
    }
}

