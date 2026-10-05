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
            <p class="card-description">Recorded customer payments and tax withholding credits (BIR 2307) allocated to invoices</p>
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
                        <th>Payment (Cash/Bank)</th>
                        <th>Tax Withheld (2307)</th>
                        <th>Total Settled Credit</th>
                        <th>Reference / Tax Doc</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collections as $col)
                        @php
                            $taxWithheld = (float)($col->tax_withheld ?? 0);
                            $totalSettled = (float)$col->amount + $taxWithheld;
                        @endphp
                        <tr>
                            <td><strong class="text-primary">{{ $col->payment_number }}</strong></td>
                            <td>{{ $col->customer->name ?? 'Unknown Customer' }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $col->payment_date->format('M d, Y') }}</td>
                            <td><span class="badge badge-primary">{{ strtoupper($col->payment_method) }}</span></td>
                            <td style="font-weight: 700; color: #065F46;">₱{{ number_format($col->amount, 2) }}</td>
                            <td>
                                @if($taxWithheld > 0)
                                    <span class="badge bg-warning-subtle text-dark border border-warning" title="{{ $col->tax_type }}">
                                        +₱{{ number_format($taxWithheld, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted small">₱0.00</span>
                                @endif
                            </td>
                            <td style="font-weight: 800; color: #1E40AF; font-size: 0.95rem;">
                                ₱{{ number_format($totalSettled, 2) }}
                            </td>
                            <td>
                                <div>{{ $col->reference_number ?? 'Direct' }}</div>
                                @if($col->tax_doc_number)
                                    <div class="small text-muted">2307 #: {{ $col->tax_doc_number }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge-navy">{{ $col->receiver->name ?? 'Staff' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Record Collection Modal with Tax / 2307 Tab -->
<div id="addCollectionModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 580px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cash-stack text-success me-2"></i> Receive AR Customer Payment & Tax Credit</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addCollectionModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.receivables.collect') }}" method="POST" class="ajax-form">
            @csrf
            <input type="hidden" id="col_sale_id" name="sale_id">
            <div class="modal-body">
                <div id="col_specific_banner" style="display: none; background: #eff6ff; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #bfdbfe;">
                    <div style="font-weight: 700; color: #1e3a8a;" id="col_cust_name_banner"></div>
                    <div style="font-size: 0.82rem; color: #2563eb;">Invoice: <strong id="col_inv_banner"></strong> | Outstanding Balance: <strong id="col_bal_banner" style="color: #b91c1c;"></strong></div>
                </div>

                <div class="form-group" id="col_customer_select_group">
                    <label class="form-label" for="col_customer_id">Customer <span class="text-danger">*</span></label>
                    <select id="col_customer_id" name="customer_id" class="form-select select2-init" required>
                        <option value="">Select customer...</option>
                        @foreach($allCustomers ?? $customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Balance: ₱{{ number_format($c->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment & Tax Input Fields -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                        <div class="form-group mb-0">
                            <label class="form-label" for="col_amount">Amount Paid / Received (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="col_amount" name="amount" class="form-control" placeholder="0.00" value="0.00" oninput="calculateSettlementTotal()" required>
                            <small class="text-muted">Cash / Bank transfer amount</small>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label" for="col_tax_withheld">Tax Withheld (₱)</label>
                            <input type="number" step="0.01" id="col_tax_withheld" name="tax_withheld" class="form-control" placeholder="0.00" value="0.00" oninput="calculateSettlementTotal()">
                            <small class="text-muted">BIR 2307 / Tax deduction</small>
                        </div>
                    </div>

                    <!-- Tax Document / Type (Optional) -->
                    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 12px;">
                        <div>
                            <label class="form-label" style="font-size: 0.78rem;">Tax Type / Deduction Description</label>
                            <input type="text" name="tax_type" class="form-control form-control-sm" placeholder="e.g. Creditable Withholding Tax (1%)">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.78rem;">Form 2307 / Doc Ref #</label>
                            <input type="text" name="tax_doc_number" class="form-control form-control-sm" placeholder="e.g. 2307-2026-001">
                        </div>
                    </div>

                    <!-- Total Settlement Summary Banner -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 10px; border-top: 2px dashed #cbd5e1;">
                        <span style="font-weight: 700; color: #334155;">Total AR Credit Settlement:</span>
                        <strong style="font-size: 1.15rem; color: #16a34a;" id="col_settlement_display">₱0.00</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">
                        <i class="bi bi-info-circle"></i> Example: ₱99 cash + ₱1 tax withheld = ₱100 total credit applied to clear receivable.
                    </div>
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
                    <label class="form-label" for="col_ref">Bank / Transaction Reference #</label>
                    <input type="text" id="col_ref" name="reference_number" class="form-control" placeholder="e.g. BDO-REF-88219 or Check #">
                </div>

                <div class="form-group">
                    <label class="form-label" for="col_notes">Notes</label>
                    <textarea id="col_notes" name="notes" class="form-control" rows="2" placeholder="Collection receipt notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addCollectionModal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Post Collection & Settle</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function calculateSettlementTotal() {
        const cash = parseFloat(document.getElementById('col_amount')?.value || 0);
        const tax = parseFloat(document.getElementById('col_tax_withheld')?.value || 0);
        const total = cash + tax;
        const display = document.getElementById('col_settlement_display');
        if (display) {
            display.innerText = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function openCollectForInvoice(customerId, saleId, customerName, saleNumber, unpaidAmount) {
        document.getElementById('col_sale_id').value = saleId;
        document.getElementById('col_customer_id').value = customerId;
        document.getElementById('col_customer_select_group').style.display = 'none';

        document.getElementById('col_cust_name_banner').innerText = customerName;
        document.getElementById('col_inv_banner').innerText = saleNumber;
        document.getElementById('col_bal_banner').innerText = '₱' + parseFloat(unpaidAmount).toFixed(2);
        document.getElementById('col_specific_banner').style.display = 'block';

        document.getElementById('col_amount').value = unpaidAmount;
        document.getElementById('col_tax_withheld').value = '0.00';
        calculateSettlementTotal();
        openModal('addCollectionModal');
    }
</script>
@endpush
