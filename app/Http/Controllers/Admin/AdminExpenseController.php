<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use Illuminate\Http\Request;

class AdminExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with('creator')->latest('expense_date')->get();
        $totalMonth = Expense::whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount');
        $totalToday = Expense::whereDate('expense_date', today())->sum('amount');

        return view('admin.expenses.index', compact('expenses', 'totalMonth', 'totalToday'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payee' => 'required|string|max:255',
            'payment_method' => 'required|in:cash,gcash,bank_transfer',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $expNumber = 'EXP-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

        $expense = Expense::create([
            'expense_number' => $expNumber,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'payee' => $validated['payee'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('expense_recorded', Expense::class, $expense->id, null, $expense->toArray(), 'Operating expense recorded');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully!',
            ]);
        }

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded successfully!');
    }
}
