@extends('layouts.app')

@section('title', 'Operating Expenses')
@section('page_title', 'Business Operating Expenses')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-amber">
        <div class="stat-header">
            <span class="stat-label">Total Expenses (This Month)</span>
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalMonth, 2) }}</div>
        <div class="stat-helper">Logistics, labor, utilities, rent</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Today's Operating Expenses</span>
            <div class="stat-icon"><i class="bi bi-cash"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalToday, 2) }}</div>
        <div class="stat-helper">Disbursed today</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Expense Transactions</h2>
            <p class="card-description">Track store overhead separate from COGS and supplier inventory purchases</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addExpenseModal')">
            <i class="bi bi-plus-lg me-1"></i> Record New Expense
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Expense Ref #</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Payee / Vendor</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                        <th>Reference / OR #</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $exp)
                        <tr>
                            <td><strong>{{ $exp->expense_number }}</strong></td>
                            <td><span class="badge badge-navy">{{ $exp->category }}</span></td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $exp->expense_date->format('M d, Y') }}</td>
                            <td><strong>{{ $exp->payee }}</strong></td>
                            <td><span class="badge badge-primary">{{ strtoupper($exp->payment_method) }}</span></td>
                            <td style="font-weight: 800; color: #991B1B; font-size: 0.95rem;">₱{{ number_format($exp->amount, 2) }}</td>
                            <td style="font-size: 0.8rem;">{{ $exp->reference_number ?? 'None' }}</td>
                            <td><span class="badge badge-navy">{{ $exp->creator->name ?? 'Admin' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-receipt text-primary me-2"></i> Record Operating Expense</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addExpenseModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.expenses.store') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="exp_cat">Expense Category <span class="text-danger">*</span></label>
                        <select id="exp_cat" name="category" class="form-select" required>
                            <option value="Logistics & Fuel">Logistics & Fuel</option>
                            <option value="Labor & Hauling">Labor & Hauling</option>
                            <option value="Packaging & Crates">Packaging & Crates</option>
                            <option value="Electricity & Water">Electricity & Water</option>
                            <option value="Rent & Stall Fees">Rent & Stall Fees</option>
                            <option value="Equipment Maintenance">Equipment Maintenance</option>
                            <option value="Store Supplies">Store Supplies</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="exp_amount">Amount (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="exp_amount" name="amount" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="exp_date">Date <span class="text-danger">*</span></label>
                        <input type="date" id="exp_date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="exp_method">Payment Method <span class="text-danger">*</span></label>
                        <select id="exp_method" name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="exp_payee">Payee / Recipient <span class="text-danger">*</span></label>
                    <input type="text" id="exp_payee" name="payee" class="form-control" placeholder="e.g. Petron Balintawak, Porters Union" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="exp_ref">Official Receipt (OR) / Voucher #</label>
                    <input type="text" id="exp_ref" name="reference_number" class="form-control" placeholder="e.g. OR-99120">
                </div>

                <div class="form-group">
                    <label class="form-label" for="exp_notes">Notes</label>
                    <textarea id="exp_notes" name="notes" class="form-control" rows="2" placeholder="Description of expense..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addExpenseModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Expense</button>
            </div>
        </form>
    </div>
</div>
@endsection
