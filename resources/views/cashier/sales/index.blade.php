@extends('layouts.cashier')

@section('content')
<div style="max-width: 1200px; margin: 0 auto;">
    <!-- Top Summary Banner -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--color-mist-blue); color: var(--color-primary-blue);">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value">{{ $sales->where('status', 'completed')->count() }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #D1FAE5; color: #065F46;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="stat-label">Total Sales Collected</div>
                <div class="stat-value" style="color: #065F46;">₱{{ number_format($sales->where('status', 'completed')->sum('total_amount'), 2) }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #EFF6FF; color: #1D4ED8;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-label">Cash Tendered</div>
                <div class="stat-value">₱{{ number_format($sales->where('status', 'completed')->where('payment_method', 'cash')->sum('total_amount'), 2) }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FEF3C7; color: #92400E;">
                <i class="bi bi-phone"></i>
            </div>
            <div>
                <div class="stat-label">Digital / AR Sales</div>
                <div class="stat-value">₱{{ number_format($sales->where('status', 'completed')->where('payment_method', '!=', 'cash')->sum('total_amount'), 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="card-title"><i class="bi bi-clock-history me-1 text-primary"></i> Cashier Sales Log</h2>
                <p class="card-description">Receipts completed during your cashier shift</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <form method="GET" action="{{ route('cashier.sales.index') }}" style="display: flex; gap: 8px; align-items: center;">
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date', date('Y-m-d')) }}" onchange="this.form.submit()">
                </form>
                <a href="{{ route('cashier.pos') }}" class="btn btn-primary">
                    <i class="bi bi-cart3 me-1"></i> Back to POS Terminal
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="cashierSalesTable" class="custom-table datatable-init" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Items Count</th>
                            <th>Payment Method</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            <tr>
                                <td><strong>{{ $sale->sale_number }}</strong></td>
                                <td style="font-size: 0.8rem; color: var(--text-light);" data-order="{{ $sale->sale_date->timestamp }}">{{ $sale->sale_date->format('h:i A') }}</td>
                                <td>{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</td>
                                <td>{{ $sale->lines->count() }} item(s)</td>
                                <td>
                                    <span class="badge {{ $sale->payment_method === 'cash' ? 'badge-primary' : ($sale->payment_method === 'gcash' ? 'badge-info' : ($sale->payment_method === 'credit' ? 'badge-warning' : 'badge-secondary')) }}">
                                        {{ strtoupper($sale->payment_method) }}
                                    </span>
                                </td>
                                <td style="font-weight: 800; font-size: 0.95rem; color: var(--color-deep-navy);">₱{{ number_format($sale->total_amount, 2) }}</td>
                                <td>
                                    @if($sale->status === 'completed')
                                        <span class="badge badge-success">Completed</span>
                                    @else
                                        <span class="badge badge-danger">Voided</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="reprintReceipt({{ $sale->id }})" title="Reprint Thermal Receipt">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                        @if($sale->status === 'completed')
                                            <button type="button" class="btn btn-sm btn-danger" onclick="openVoidModal({{ $sale->id }}, '{{ $sale->sale_number }}')" title="Void Transaction">
                                                <i class="bi bi-x-circle"></i> Void
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Void Modal -->
<div id="voidSaleModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i> Supervisor Void Authorization</h3>
            <button type="button" class="modal-close-btn" data-close-modal="voidSaleModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="voidSaleForm" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="background: #FEE2E2; color: #991B1B; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                    Voiding will restore inventory back to stock and reverse customer accounts.
                    <div style="font-weight: 700; margin-top: 4px;" id="voidSaleNumDisplay"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="void_reason">Void Reason <span class="text-danger">*</span></label>
                    <input type="text" id="void_reason" name="reason" class="form-control" placeholder="e.g. Customer cancelled order, wrong item keyed" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin_password">Admin / Supervisor Password <span class="text-danger">*</span></label>
                    <input type="password" id="admin_password" name="admin_password" class="form-control" placeholder="Enter admin password to authorize" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="voidSaleModal">Cancel</button>
                <button type="submit" class="btn btn-danger">Authorize & Void Sale</button>
            </div>
        </form>
    </div>
</div>

<!-- Receipt Modal -->
<div id="reprintReceiptModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 380px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-receipt text-success me-2"></i> Sale Receipt</h3>
            <button type="button" class="modal-close-btn" data-close-modal="reprintReceiptModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" style="background: #FFF; padding: 15px;">
            <div id="printableReceipt" style="font-family: 'Courier New', monospace; font-size: 12px; color: #000; line-height: 1.4;">
                <!-- Populated via JS -->
            </div>
        </div>
        <div class="modal-footer" style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" data-close-modal="reprintReceiptModal">Close</button>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openVoidModal(id, saleNumber) {
        document.getElementById('voidSaleNumDisplay').innerText = `Target Invoice: ${saleNumber}`;
        document.getElementById('voidSaleForm').action = `/cashier/sales/${id}/void`;
        openModal('voidSaleModal');
    }

    function reprintReceipt(id) {
        fetch(`/cashier/sales/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const s = res.data;
                printThermalReceiptIframe(s);
            })
            .catch(err => {
                showToast('error', 'Reprint Error', 'Unable to fetch receipt data.');
            });
    }

    function printThermalReceiptIframe(sale) {
        let iframe = document.getElementById('receiptPrintIframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'receiptPrintIframe';
            iframe.style.position = 'fixed';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.visibility = 'hidden';
            iframe.style.left = '-9999px';
            document.body.appendChild(iframe);
        }

        const linesHtml = (sale.lines || []).map(l => `
            <tr>
                <td colspan="2" style="font-weight: 700; padding-top: 4px;">${l.name || l.product_name}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 4px; font-size: 11px;">${l.quantity} ${l.unit || l.unit_name} @ ₱${parseFloat(l.unit_price).toFixed(2)}</td>
                <td style="text-align: right; padding-bottom: 4px; font-weight: 700;">₱${parseFloat(l.subtotal).toFixed(2)}</td>
            </tr>
        `).join('');

        const receiptDoc = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt - ${sale.sale_number}</title>
                <style>
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    body {
                        width: 74mm;
                        margin: 0 auto;
                        padding: 8px 4px;
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 12px;
                        line-height: 1.35;
                        color: #000000;
                    }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .bold { font-weight: bold; }
                    .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
                    table { width: 100%; font-size: 11.5px; border-collapse: collapse; }
                    .row-flex { display: flex; justify-content: space-between; align-items: center; }
                </style>
            </head>
            <body>
                <div class="text-center bold" style="font-size: 14px; letter-spacing: 0.5px;">WORTHY ACOSTA BAGSAKAN</div>
                <div class="text-center" style="font-size: 11px;">Wholesale & Retail Produce</div>
                <div class="text-center" style="font-size: 10px;">Trading Post Hub, Balintawak</div>
                <div class="text-center" style="font-size: 10px; font-weight: 700; color: #555; margin-top: 2px;">[DUPLICATE REPRINT]</div>
                <div class="divider"></div>
                <div>Invoice: <strong>${sale.sale_number}</strong></div>
                <div>Date: ${sale.sale_date}</div>
                <div>Cashier: ${sale.cashier_name || '{{ auth()->user()->name }}'}</div>
                <div>Customer: ${sale.customer_name || 'Walk-in Customer'}</div>
                <div class="divider"></div>
                <table>
                    ${linesHtml}
                </table>
                <div class="divider"></div>
                <div class="row-flex">
                    <span>Subtotal:</span>
                    <span>₱${parseFloat(sale.subtotal).toFixed(2)}</span>
                </div>
                ${parseFloat(sale.discount_amount) > 0 ? `
                <div class="row-flex">
                    <span>Discount:</span>
                    <span>-₱${parseFloat(sale.discount_amount).toFixed(2)}</span>
                </div>` : ''}
                <div class="row-flex bold" style="font-size: 13px; margin: 4px 0;">
                    <span>TOTAL DUE:</span>
                    <span>₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                </div>
                <div class="row-flex">
                    <span>Payment (${sale.payment_method ? sale.payment_method.toUpperCase() : 'CASH'}):</span>
                    <span>₱${parseFloat(sale.amount_paid).toFixed(2)}</span>
                </div>
                <div class="row-flex bold">
                    <span>Change:</span>
                    <span>₱${parseFloat(sale.change_amount).toFixed(2)}</span>
                </div>
                <div class="divider"></div>
                <div class="text-center" style="font-size: 10px; margin-top: 6px;">
                    Thank you for your business!<br>
                    Please inspect goods upon delivery.
                </div>
            </body>
            </html>
        `;

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(receiptDoc);
        doc.close();

        setTimeout(() => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (err) {
                console.warn('Print failed on iframe', err);
            }
        }, 300);
    }
</script>
@endpush
