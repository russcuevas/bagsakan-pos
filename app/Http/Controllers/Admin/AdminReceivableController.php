<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\InvoiceAllocation;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReceivableController extends Controller
{
    public function index()
    {
        $customers = Customer::where('current_balance', '>', 0)->with('sales')->get();
        $totalAR = Customer::sum('current_balance');

        $creditSales = Sale::where('payment_method', 'credit')
            ->orWhere('payment_status', '!=', 'paid')
            ->with(['customer', 'allocations.payment'])
            ->latest('sale_date')
            ->get();

        $collections = Payment::where('type', 'customer_collection')
            ->with(['customer', 'allocations.sale', 'receiver'])
            ->latest('payment_date')
            ->get();

        return view('admin.receivables.index', compact('customers', 'totalAR', 'creditSales', 'collections'));
    }

    public function recordCollection(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,check',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $customer = Customer::findOrFail($validated['customer_id']);
            $amount = (float) $validated['amount'];

            $paymentNumber = 'COL-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $customer->id,
                'type' => 'customer_collection',
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'amount' => $amount,
                'notes' => $validated['notes'] ?? null,
                'received_by' => auth()->id(),
            ]);

            // Allocate to specific sale or oldest unpaid invoices
            $remainingPayment = $amount;

            if (!empty($validated['sale_id'])) {
                $sale = Sale::findOrFail($validated['sale_id']);
                $unpaidOnSale = $sale->total_amount - $sale->amount_paid;
                $allocated = min($remainingPayment, $unpaidOnSale);

                $sale->amount_paid += $allocated;
                if ($sale->amount_paid >= $sale->total_amount) {
                    $sale->payment_status = 'paid';
                } else {
                    $sale->payment_status = 'partial';
                }
                $sale->save();

                InvoiceAllocation::create([
                    'payment_id' => $payment->id,
                    'sale_id' => $sale->id,
                    'allocated_amount' => $allocated,
                ]);

                $remainingPayment -= $allocated;
            } else {
                // Auto allocate to oldest unpaid credit sales of customer
                $unpaidSales = Sale::where('customer_id', $customer->id)
                    ->where('payment_status', '!=', 'paid')
                    ->orderBy('sale_date', 'asc')
                    ->get();

                foreach ($unpaidSales as $sale) {
                    if ($remainingPayment <= 0) break;
                    $unpaid = $sale->total_amount - $sale->amount_paid;
                    $alloc = min($remainingPayment, $unpaid);

                    $sale->amount_paid += $alloc;
                    if ($sale->amount_paid >= $sale->total_amount) {
                        $sale->payment_status = 'paid';
                    } else {
                        $sale->payment_status = 'partial';
                    }
                    $sale->save();

                    InvoiceAllocation::create([
                        'payment_id' => $payment->id,
                        'sale_id' => $sale->id,
                        'allocated_amount' => $alloc,
                    ]);

                    $remainingPayment -= $alloc;
                }
            }

            // Decrement Customer AR Balance
            $customer->decrement('current_balance', min((float)$customer->current_balance, $amount));

            AuditLog::log('collection_recorded', Payment::class, $payment->id, null, [
                'customer_id' => $customer->id,
                'amount' => $amount,
                'payment_number' => $paymentNumber,
            ], "Collection payment received from {$customer->name}");
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection payment recorded and AR balance updated successfully!',
            ]);
        }

        return back()->with('success', 'Collection payment recorded successfully!');
    }
}
