@extends('layouts.app')

@section('title', 'Customers & Credit Terms')
@section('page_title', 'Customer Profiles & Credit Terms')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Customer Masterfile</h2>
            <p class="card-description">Manage restaurant clients, catering accounts, credit limits, and terms</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addCustomerModal')">
            <i class="bi bi-person-plus me-1"></i> Add New Customer
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>Customer / Business Name</th>
                        <th>Contact Number</th>
                        <th>Credit Limit</th>
                        <th>Payment Terms</th>
                        <th>Outstanding AR</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $cust)
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--color-deep-navy);">{{ $cust->name }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-light);">{{ $cust->business_name ?? 'Individual' }} &bull; {{ $cust->address }}</div>
                            </td>
                            <td>{{ $cust->contact_number ?? 'N/A' }}</td>
                            <td style="font-weight: 600;">₱{{ number_format($cust->credit_limit, 2) }}</td>
                            <td><span class="badge badge-navy">{{ $cust->payment_terms_days }} Days Term</span></td>
                            <td>
                                @if($cust->current_balance > 0)
                                    <span style="font-weight: 800; color: #991B1B; font-size: 0.95rem;">
                                        ₱{{ number_format($cust->current_balance, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-success">₱0.00 (Cleared)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $cust->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $cust->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="{{ route('admin.receivables.index') }}" class="btn btn-sm btn-secondary" title="View Invoices & Collections">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="editCustomer({{ $cust->id }})" title="Edit Profile">
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

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-person-plus text-primary me-2"></i> Register Wholesale / Credit Customer</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addCustomerModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.customers.store') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="cust_name">Customer / Contact Name <span class="text-danger">*</span></label>
                    <input type="text" id="cust_name" name="name" class="form-control" placeholder="e.g. Sentro 88 Foods Inc." required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cust_biz">Business / Store Name</label>
                    <input type="text" id="cust_biz" name="business_name" class="form-control" placeholder="e.g. Sentro 88 Restaurant Group">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="cust_phone">Mobile / Phone Number</label>
                        <input type="text" id="cust_phone" name="contact_number" class="form-control" placeholder="0917-555-8888">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cust_email">Email Address</label>
                        <input type="email" id="cust_email" name="email" class="form-control" placeholder="client@domain.com">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="cust_limit">Credit Limit (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="cust_limit" name="credit_limit" class="form-control" value="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cust_bal">Beginning Balance (₱)</label>
                        <input type="number" step="0.01" id="cust_bal" name="current_balance" class="form-control" value="0.00" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cust_terms">Payment Terms (Days) <span class="text-danger">*</span></label>
                        <input type="number" id="cust_terms" name="payment_terms_days" class="form-control" value="0" placeholder="0 = Cash, 30 = 30-Day terms" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cust_address">Delivery / Billing Address</label>
                    <textarea id="cust_address" name="address" class="form-control" rows="2" placeholder="e.g. Quezon City Commercial Complex"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addCustomerModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Customer</h3>
            <button type="button" class="modal-close-btn" data-close-modal="editCustomerModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editCustomerForm" method="POST" class="ajax-form">
            @csrf
            @method('PUT')
            <div class="modal-body" id="editCustomerBody">
                <div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="editCustomerModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editCustomer(id) {
        openModal('editCustomerModal');
        document.getElementById('editCustomerBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border text-primary"></div></div>';
        document.getElementById('editCustomerForm').action = `/admin/customers/${id}`;

        fetch(`/admin/customers/${id}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(res => {
                const c = res.data;
                document.getElementById('editCustomerBody').innerHTML = `
                    <div class="form-group">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="name" class="form-control" value="${c.name}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Business Name</label>
                        <input type="text" name="business_name" class="form-control" value="${c.business_name || ''}">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="contact_number" class="form-control" value="${c.contact_number || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="${c.email || ''}">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label class="form-label">Credit Limit (₱)</label>
                            <input type="number" step="0.01" name="credit_limit" class="form-control" value="${c.credit_limit}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Current Balance (₱)</label>
                            <input type="number" step="0.01" name="current_balance" class="form-control" value="${c.current_balance || '0.00'}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Terms (Days)</label>
                            <input type="number" name="payment_terms_days" class="form-control" value="${c.payment_terms_days}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" ${c.is_active ? 'selected' : ''}>Active</option>
                            <option value="0" ${!c.is_active ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2">${c.address || ''}</textarea>
                    </div>
                `;
            });
    }
</script>
@endpush
