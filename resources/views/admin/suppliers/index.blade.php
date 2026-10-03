@extends('layouts.app')

@section('title', 'Suppliers & Payables')
@section('page_title', 'Supplier Masterfile & Accounts Payable')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Suppliers Directory</h2>
            <p class="card-description">Manage farm growers, wholesale distributors, payment terms, and accounts payable</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addSupplierModal')">
            <i class="bi bi-person-plus-fill me-1"></i> Register Supplier
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone / Email</th>
                        <th>Terms</th>
                        <th>Bank / Payment Info</th>
                        <th>Outstanding AP</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suppliers as $sup)
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--color-deep-navy);">{{ $sup->name }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $sup->address }}</div>
                            </td>
                            <td>{{ $sup->contact_person ?? 'N/A' }}</td>
                            <td>
                                <div>{{ $sup->mobile_number ?? 'N/A' }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $sup->email }}</div>
                            </td>
                            <td><span class="badge badge-navy">{{ $sup->payment_terms }}</span></td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);">{{ $sup->bank_info ?? 'Cash Basis' }}</td>
                            <td>
                                @if($sup->outstanding_balance > 0)
                                    <span style="font-weight: 800; color: #991B1B; font-size: 0.95rem;">
                                        ₱{{ number_format($sup->outstanding_balance, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-success">Fully Paid</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    @if($sup->outstanding_balance > 0)
                                        <button type="button" class="btn btn-sm btn-success" onclick="openPaySupplierModal({{ $sup->id }}, '{{ addslashes($sup->name) }}', {{ $sup->outstanding_balance }})" title="Disburse Payment">
                                            <i class="bi bi-cash-stack me-1"></i> Pay
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline" onclick="editSupplier({{ $sup->id }})" title="Edit Profile">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-truck text-primary me-2"></i> Register New Supplier</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addSupplierModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.suppliers.store') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="sup_name">Company / Trade Name <span class="text-danger">*</span></label>
                    <input type="text" id="sup_name" name="name" class="form-control" placeholder="e.g. Baguio Highlands Agro Trading" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="sup_contact">Contact Person</label>
                        <input type="text" id="sup_contact" name="contact_person" class="form-control" placeholder="e.g. Robert Valdez">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sup_mobile">Mobile Number</label>
                        <input type="text" id="sup_mobile" name="mobile_number" class="form-control" placeholder="e.g. 09171234567">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="sup_email">Email Address</label>
                        <input type="email" id="sup_email" name="email" class="form-control" placeholder="supplier@domain.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sup_tin">TIN Number</label>
                        <input type="text" id="sup_tin" name="tin" class="form-control" placeholder="000-000-000-000">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="sup_terms">Payment Terms <span class="text-danger">*</span></label>
                        <input type="text" id="sup_terms" name="payment_terms" class="form-control" placeholder="e.g. Cash, 7 Days, 15 Days, 30 Days" value="Cash" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sup_bank">Bank / Payment Details</label>
                        <input type="text" id="sup_bank" name="bank_info" class="form-control" placeholder="e.g. BDO: 0045-8899-2311">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sup_addr">Address / Origin</label>
                    <textarea id="sup_addr" name="address" class="form-control" rows="2" placeholder="e.g. La Trinidad, Benguet"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addSupplierModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div id="editSupplierModal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Supplier Profile</h3>
            <button type="button" class="modal-close-btn" data-close-modal="editSupplierModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editSupplierForm" method="POST" class="ajax-form">
            @csrf
            @method('PUT')
            <div class="modal-body" id="editSupplierBody">
                <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="editSupplierModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Disburse Supplier Payment Modal -->
<div id="supplierPaymentModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cash-coin text-success me-2"></i> Record Supplier Payment</h3>
            <button type="button" class="modal-close-btn" data-close-modal="supplierPaymentModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="supplierPaymentForm" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div style="background: var(--color-mist-blue); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div style="font-weight: 700; color: var(--color-deep-navy);" id="pay_sup_name"></div>
                    <div style="font-size: 0.85rem; color: #991B1B;">Outstanding Payables: <strong id="pay_sup_balance"></strong></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pay_amount">Payment Amount (₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="pay_amount" name="amount" class="form-control" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="pay_date">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" id="pay_date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pay_method">Payment Method <span class="text-danger">*</span></label>
                        <select id="pay_method" name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="gcash">GCash</option>
                            <option value="check">Bank Check</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pay_ref">Reference / Check #</label>
                    <input type="text" id="pay_ref" name="reference_number" class="form-control" placeholder="e.g. BDO-REF-10992">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pay_notes">Notes</label>
                    <textarea id="pay_notes" name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="supplierPaymentModal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Disburse Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editSupplier(id) {
        openModal('editSupplierModal');
        document.getElementById('editSupplierBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';
        document.getElementById('editSupplierForm').action = `/admin/suppliers/${id}`;

        fetch(`/admin/suppliers/${id}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            const s = res.data;
            document.getElementById('editSupplierBody').innerHTML = `
                <div class="form-group">
                    <label class="form-label" for="edit_sup_name">Company / Trade Name <span class="text-danger">*</span></label>
                    <input type="text" id="edit_sup_name" name="name" class="form-control" value="${s.name || ''}" placeholder="e.g. Baguio Highlands Agro Trading" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_contact">Contact Person</label>
                        <input type="text" id="edit_sup_contact" name="contact_person" class="form-control" value="${s.contact_person || ''}" placeholder="e.g. Robert Valdez">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_mobile">Mobile Number</label>
                        <input type="text" id="edit_sup_mobile" name="mobile_number" class="form-control" value="${s.mobile_number || ''}" placeholder="e.g. 09171234567">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_email">Email Address</label>
                        <input type="email" id="edit_sup_email" name="email" class="form-control" value="${s.email || ''}" placeholder="supplier@domain.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_tin">TIN Number</label>
                        <input type="text" id="edit_sup_tin" name="tin" class="form-control" value="${s.tin || ''}" placeholder="000-000-000-000">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_terms">Payment Terms <span class="text-danger">*</span></label>
                        <input type="text" id="edit_sup_terms" name="payment_terms" class="form-control" value="${s.payment_terms || 'Cash'}" placeholder="e.g. Cash, 7 Days, 15 Days" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_bank">Bank / Payment Details</label>
                        <input type="text" id="edit_sup_bank" name="bank_info" class="form-control" value="${s.bank_info || ''}" placeholder="e.g. BDO: 0045-8899-2311">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_sup_status">Status</label>
                        <select id="edit_sup_status" name="is_active" class="form-select">
                            <option value="1" ${s.is_active ? 'selected' : ''}>Active</option>
                            <option value="0" ${!s.is_active ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_sup_addr">Address / Origin</label>
                    <textarea id="edit_sup_addr" name="address" class="form-control" rows="2" placeholder="e.g. La Trinidad, Benguet">${s.address || ''}</textarea>
                </div>
            `;
        })
        .catch(err => {
            console.error(err);
            document.getElementById('editSupplierBody').innerHTML = '<div class="alert alert-danger">Failed to load supplier details.</div>';
        });
    }

    function openPaySupplierModal(id, name, balance) {
        document.getElementById('pay_sup_name').innerText = name;
        document.getElementById('pay_sup_balance').innerText = '₱' + parseFloat(balance).toFixed(2);
        document.getElementById('pay_amount').value = balance;
        document.getElementById('supplierPaymentForm').action = `/admin/suppliers/${id}/payment`;
        openModal('supplierPaymentModal');
    }
</script>
@endpush
