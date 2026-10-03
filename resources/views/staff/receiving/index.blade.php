@extends('layouts.app')

@section('title', 'Inbound Goods Receiving')
@section('page_title', 'Inbound Goods Receiving & Batch Intake')

@section('content')
<!-- Pending Delivery Orders -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Pending Purchase Orders Awaiting Delivery</h2>
            <p class="card-description">Confirm delivered quantities, supplier DR #, and post batches directly into warehouse stock</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Order Date</th>
                        <th>Items to Receive</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingPurchases as $p)
                        <tr>
                            <td><strong>{{ $p->purchase_number }}</strong></td>
                            <td>{{ $p->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $p->warehouse->name ?? 'Main' }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $p->purchase_date->format('M d, Y') }}</td>
                            <td>{{ $p->lines->pluck('product.name')->implode(', ') }}</td>
                            <td><span class="badge badge-warning">Awaiting DR Intake</span></td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-success" onclick="openStaffReceiveModal({{ $p->id }})">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Receive Goods
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Inbound Batches History -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="bi bi-boxes text-primary me-1"></i> Stock Intake History & Active Batches</h3>
            <p class="card-description">Recently created batches with supplier reference</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Product SKU</th>
                        <th>Supplier</th>
                        <th>DR #</th>
                        <th>Initial Qty</th>
                        <th>Current Remaining</th>
                        <th>Receipt Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBatches as $b)
                        <tr>
                            <td><strong>{{ $b->batch_code }}</strong></td>
                            <td>
                                <strong>{{ $b->product->name ?? 'N/A' }}</strong>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $b->product->sku ?? '' }}</div>
                            </td>
                            <td>{{ $b->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $b->invoice_dr_number ?? 'N/A' }}</td>
                            <td>{{ number_format($b->initial_quantity, 2) }} {{ $b->product->base_unit ?? '' }}</td>
                            <td style="font-weight: 800; color: #065F46;">
                                {{ number_format($b->current_quantity, 2) }} {{ $b->product->base_unit ?? '' }}
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-light);">{{ $b->receipt_date->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Receive Goods Modal -->
<div id="staffReceiveModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-box-arrow-in-down text-success me-2"></i> Receive Inbound Shipment</h3>
            <button type="button" class="modal-close-btn" data-close-modal="staffReceiveModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="staffReceiveForm" method="POST" action="{{ route('staff.receiving.store') }}" class="ajax-form">
            @csrf
            <input type="hidden" id="staff_recv_po_id" name="purchase_id">
            <div class="modal-body" id="staffReceiveBody">
                <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="staffReceiveModal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Confirm & Generate Batches</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openStaffReceiveModal(id) {
        document.getElementById('staff_recv_po_id').value = id;
        openModal('staffReceiveModal');
        document.getElementById('staffReceiveBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';

        fetch(`/staff/purchases/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const p = res.data;
                document.getElementById('staffReceiveBody').innerHTML = `
                    <div style="background: var(--color-mist-blue); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div style="font-weight: 700; color: var(--color-deep-navy);">Inbound Receiving for PO: ${p.purchase_number}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Supplier: <strong>${p.supplier ? p.supplier.name : ''}</strong> | Dest: <strong>${p.warehouse ? p.warehouse.name : ''}</strong></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label">Delivery Receipt (DR) / Invoice # <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_dr_number" class="form-control" value="${p.invoice_dr_number || ''}" placeholder="e.g. DR-10558" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">Verify Actual Delivered Quantities</h4>
                    <table class="custom-table" style="font-size: 0.84rem;">
                        <thead>
                            <tr>
                                <th>Product SKU</th>
                                <th>Unit</th>
                                <th>Ordered</th>
                                <th>Actual Quantity to Receive</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${p.lines.map((l, i) => `
                                <tr>
                                    <td>
                                        <strong>${l.product ? l.product.name : 'N/A'}</strong>
                                        <input type="hidden" name="items[${i}][line_id]" value="${l.id}">
                                    </td>
                                    <td><span class="badge badge-navy">${l.unit_name}</span></td>
                                    <td>${parseFloat(l.quantity_ordered).toFixed(2)}</td>
                                    <td>
                                        <input type="number" step="0.01" name="items[${i}][quantity_received]" class="form-control form-control-sm" value="${parseFloat(l.quantity_ordered) - parseFloat(l.quantity_received)}" required>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            });
    }
</script>
@endpush
