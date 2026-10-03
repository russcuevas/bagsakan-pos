@extends('layouts.app')

@section('title', 'Receivables & Collections')
@section('page_title', 'Accounts Receivable & Collections Ledger')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card accent-amber">
        <div class="stat-header">
            <span class="stat-label">Total Outstanding AR</span>
            <div class="stat-icon"><i class="bi bi-cash-coin text-warning"></i></div>
        </div>
        <div class="stat-value">₱{{ number_format($totalAR, 2) }}</div>
        <div class="stat-helper">Customer credit invoices receivable</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Customers with Open Balances</span>
            <div class="stat-icon"><i class="bi bi-people"></i></div>
        </div>
        <div class="stat-value">{{ $customers->count() }} accounts</div>
        <div class="stat-helper">Restaurants & wholesale buyers</div>
    </div>
</div>

<!-- Unpaid / Partial Credit Sales Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Credit Invoices & Open Sales</h2>
            <p class="card-description">Track outstanding wholesale customer bills, payment terms, and due dates</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addCollectionModal')">
            <i class="bi bi-cash-stack me-1"></i> Record Customer Collection
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Invoice / SI #</th>
                        <th>Customer</th>
                        <th>Sale Date</th>
                        <th>Terms & Due Date</th>
                        <th>Invoice Total</th>
                        <th>Amount Paid</th>
                        <th>Remaining AR</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditSales as $sale)
                        @php
                            $unpaid = $sale->total_amount - $sale->amount_paid;
                        @endphp
                        <tr>
                            <td><strong>{{ $sale->sale_number }}</strong></td>
                            <td>
                                <strong>{{ $sale->customer->name ?? 'Walk-in' }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $sale->customer->business_name ?? '' }}</div>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $sale->sale_date->format('M d, Y') }}</td>
                            <td>
                                <div><span class="badge badge-navy">{{ $sale->payment_terms ?? 'On-Account' }}</span></div>
                                <div style="font-size: 0.74rem; color: var(--danger-red);">Due: {{ $sale->due_date ? $sale->due_date->format('M d, Y') : 'Immediate' }}</div>
                            </td>
                            <td style="font-weight: 700;">₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td style="color: #065F46; font-weight: 600;">₱{{ number_format($sale->amount_paid, 2) }}</td>
                            <td style="font-weight: 800; color: #991B1B; font-size: 0.95rem;">₱{{ number_format($unpaid, 2) }}</td>
                            <td>
                                @if($sale->payment_status === 'paid')
                                    <span class="badge badge-success">Paid</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="badge badge-warning">Partial</span>
                                @else
                                    <span class="badge badge-danger">Unpaid</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                @if($unpaid > 0 && $sale->customer_id)
                                    <button type="button" class="btn btn-sm btn-success" onclick="openCollectForInvoice({{ $sale->customer_id }}, {{ $sale->id }}, '{{ addslashes($sale->customer->name) }}', '{{ $sale->sale_number }}', {{ $unpaid }})">
                                        <i class="bi bi-wallet2 me-1"></i> Collect
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Collections History Ledger -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="bi bi-journal-check text-primary me-1"></i> Customer Collections Ledger</h3>
            <p class="card-description">Recorded customer payments allocated to invoices</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Customer</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th>Amount Collected</th>
                        <th>Reference #</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collections as $col)
                        <tr>
                            <td><strong>{{ $col->payment_number }}</strong></td>
                            <td>{{ $col->customer->name ?? 'Unknown Customer' }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $col->payment_date->format('M d, Y') }}</td>
                            <td><span class="badge badge-primary">{{ strtoupper($col->payment_method) }}</span></td>
                            <td style="font-weight: 800; color: #065F46; font-size: 0.95rem;">₱{{ number_format($col->amount, 2) }}</td>
                            <td>{{ $col->reference_number ?? 'Cash' }}</td>
                            <td><span class="badge badge-navy">{{ $col->receiver->name ?? 'Staff' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Record Collection Modal -->
<div id="addCollectionModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cash-stack text-success me-2"></i> Receive AR Customer Payment</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addCollectionModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.receivables.collect') }}" method="POST" class="ajax-form">
            @csrf
            <input type="hidden" id="col_sale_id" name="sale_id">
            <div class="modal-body">
                <div id="col_specific_banner" style="display: none; background: var(--color-mist-blue); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div style="font-weight: 700; color: var(--color-deep-navy);" id="col_cust_name_banner"></div>
                    <div style="font-size: 0.8rem; color: var(--color-primary-blue);">Invoice: <strong id="col_inv_banner"></strong> | Outstanding: <strong id="col_bal_banner"></strong></div>
                </div>

                <div class="form-group" id="col_customer_select_group">
                    <label class="form-label" for="col_customer_id">Customer <span class="text-danger">*</span></label>
                    <select id="col_customer_id" name="customer_id" class="form-select select2-init" required>
                        <option value="">Select customer with balance...</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Balance: ₱{{ number_format($c->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="col_amount">Amount Collected (₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="col_amount" name="amount" class="form-control" placeholder="0.00" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="col_date">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" id="col_date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="col_method">Payment Method <span class="text-danger">*</span></label>
                        <select id="col_method" name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="gcash">GCash</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="col_ref">Transaction / Reference #</label>
                    <input type="text" id="col_ref" name="reference_number" class="form-control" placeholder="e.g. BDO-REF-88219 or GCash Ref">
                </div>

                <div class="form-group">
                    <label class="form-label" for="col_notes">Notes</label>
                    <textarea id="col_notes" name="notes" class="form-control" rows="2" placeholder="Collection receipt notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addCollectionModal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Post Collection</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openCollectForInvoice(customerId, saleId, customerName, saleNumber, unpaidAmount) {
        document.getElementById('col_sale_id').value = saleId;
        document.getElementById('col_customer_id').value = customerId;
        document.getElementById('col_customer_select_group').style.display = 'none';

        document.getElementById('col_cust_name_banner').innerText = customerName;
        document.getElementById('col_inv_banner').innerText = saleNumber;
        document.getElementById('col_bal_banner').innerText = '₱' + parseFloat(unpaidAmount).toFixed(2);
        document.getElementById('col_specific_banner').style.display = 'block';

        document.getElementById('col_amount').value = unpaidAmount;
        openModal('addCollectionModal');
    }
</script>
@endpush
