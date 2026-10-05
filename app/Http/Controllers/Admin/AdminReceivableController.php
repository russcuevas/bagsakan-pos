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
        $allCustomers = Customer::where('is_active', true)->orderBy('name')->get();
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

        return view('admin.receivables.index', compact('customers', 'allCustomers', 'totalAR', 'creditSales', 'collections'));
    }

    public function recordCollection(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0',
            'tax_withheld' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|string|max:100',
            'tax_doc_number' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,check',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $cashAmount = (float) $validated['amount'];
        $taxWithheld = (float) ($validated['tax_withheld'] ?? 0);
        $totalSettled = $cashAmount + $taxWithheld;

        if ($totalSettled <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Total collection settlement (Payment Amount + Tax Withheld) must be greater than 0.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $cashAmount, $taxWithheld, $totalSettled) {
            $customer = Customer::findOrFail($validated['customer_id']);
            $paymentNumber = 'COL-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $customer->id,
                'type' => 'customer_collection',
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'amount' => $cashAmount,
                'tax_withheld' => $taxWithheld,
                'tax_type' => $validated['tax_type'] ?? ($taxWithheld > 0 ? 'Creditable Withholding Tax (BIR Form 2307)' : null),
                'tax_doc_number' => $validated['tax_doc_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'received_by' => auth()->id(),
            ]);

            // Allocate full settlement credit (Cash + Tax Withheld) to specific sale or oldest unpaid invoices
            $remainingSettlement = $totalSettled;

            if (!empty($validated['sale_id'])) {
                $sale = Sale::findOrFail($validated['sale_id']);
                $unpaidOnSale = max(0, $sale->total_amount - $sale->amount_paid);
                $allocated = min($remainingSettlement, $unpaidOnSale);

                $sale->amount_paid += $allocated;
                if ($sale->amount_paid >= $sale->total_amount - 0.001) {
                    $sale->payment_status = 'paid';
                } else {
                    $sale->payment_status = 'partial';
                }
                $sale->save();

                // Proportionally calculate tax allocation for this line
                $taxAlloc = $totalSettled > 0 ? ($allocated * ($taxWithheld / $totalSettled)) : 0;
                $cashAlloc = $allocated - $taxAlloc;

                InvoiceAllocation::create([
                    'payment_id' => $payment->id,
                    'sale_id' => $sale->id,
                    'allocated_amount' => $cashAlloc,
                    'tax_allocated' => $taxAlloc,
                ]);

                $remainingSettlement -= $allocated;
            } else {
                // Auto allocate to oldest unpaid credit sales of customer
                $unpaidSales = Sale::where('customer_id', $customer->id)
                    ->where('payment_status', '!=', 'paid')
                    ->orderBy('sale_date', 'asc')
                    ->get();

                foreach ($unpaidSales as $sale) {
                    if ($remainingSettlement <= 0) break;
                    $unpaid = max(0, $sale->total_amount - $sale->amount_paid);
                    $alloc = min($remainingSettlement, $unpaid);

                    $sale->amount_paid += $alloc;
                    if ($sale->amount_paid >= $sale->total_amount - 0.001) {
                        $sale->payment_status = 'paid';
                    } else {
                        $sale->payment_status = 'partial';
                    }
                    $sale->save();

                    $taxAlloc = $totalSettled > 0 ? ($alloc * ($taxWithheld / $totalSettled)) : 0;
                    $cashAlloc = $alloc - $taxAlloc;

                    InvoiceAllocation::create([
                        'payment_id' => $payment->id,
                        'sale_id' => $sale->id,
                        'allocated_amount' => $cashAlloc,
                        'tax_allocated' => $taxAlloc,
                    ]);

                    $remainingSettlement -= $alloc;
                }
            }

            // Decrement Customer AR Balance by total settled credit (Payment + Tax)
            $customer->decrement('current_balance', min((float)$customer->current_balance, $totalSettled));

            AuditLog::log('collection_recorded', Payment::class, $payment->id, null, [
                'customer_id' => $customer->id,
                'amount_paid' => $cashAmount,
                'tax_withheld' => $taxWithheld,
                'total_settled' => $totalSettled,
                'payment_number' => $paymentNumber,
            ], "Collection payment received: ₱" . number_format($cashAmount, 2) . " (Tax Withheld: ₱" . number_format($taxWithheld, 2) . ", Total Settled: ₱" . number_format($totalSettled, 2) . ") from {$customer->name}");
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection payment recorded! ₱' . number_format($cashAmount, 2) . ' received + ₱' . number_format($taxWithheld, 2) . ' tax credit applied.',
            ]);
        }

        return back()->with('success', 'Collection payment recorded! ₱' . number_format($cashAmount, 2) . ' received + ₱' . number_format($taxWithheld, 2) . ' tax credit applied.');
    }
}
