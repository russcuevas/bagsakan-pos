@extends('layouts.app')

@section('title', 'User Accounts')
@section('page_title', 'Staff & User Access Management')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">User Accounts Directory</h2>
            <p class="card-description">Manage roles, staff accounts, and access permissions</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('addUserModal')">
            <i class="bi bi-person-plus-fill me-1"></i> Add User Account
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table datatable-init">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Assigned Role</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td><strong>{{ $u->name }}</strong></td>
                            <td><span class="badge badge-navy">{{ $u->username ?? 'N/A' }}</span></td>
                            <td>{{ $u->email }}</td>
                            <td>
                                @if($u->role === 'admin')
                                    <span class="badge badge-primary"><i class="bi bi-shield-lock me-1"></i> Admin / Owner</span>
                                @elseif($u->role === 'purchasing')
                                    <span class="badge badge-warning"><i class="bi bi-cart-check me-1"></i> Purchasing Staff</span>
                                @else
                                    <span class="badge badge-success"><i class="bi bi-person-badge me-1"></i> Cashier</span>
                                @endif
                            </td>
                            <td>{{ $u->phone ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $u->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($u->status) }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-outline" onclick="editUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->username }}', '{{ $u->email }}', '{{ $u->role }}', '{{ $u->phone }}', '{{ $u->status }}')">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-person-plus text-primary me-2"></i> Register New Staff Account</h3>
            <button type="button" class="modal-close-btn" data-close-modal="addUserModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.users.store') }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="u_name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" id="u_name" name="name" class="form-control" placeholder="e.g. Maria Santos" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="u_username">Username <span class="text-danger">*</span></label>
                        <input type="text" id="u_username" name="username" class="form-control" placeholder="e.g. maria_santos" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="u_email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="u_email" name="email" class="form-control" placeholder="maria@bagsakan.com" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="u_role">Role <span class="text-danger">*</span></label>
                        <select id="u_role" name="role" class="form-select" required>
                            <option value="cashier">Cashier (POS & Sales)</option>
                            <option value="purchasing">Purchasing / Inventory Staff</option>
                            <option value="admin">Admin / Business Owner</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="u_phone">Phone Number</label>
                        <input type="text" id="u_phone" name="phone" class="form-control" placeholder="09171234567">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="u_pass">Password <span class="text-danger">*</span></label>
                    <input type="password" id="u_pass" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="addUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit User Account</h3>
            <button type="button" class="modal-close-btn" data-close-modal="editUserModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editUserForm" method="POST" class="ajax-form">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_u_name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" id="edit_u_name" name="name" class="form-control" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="edit_u_username">Username <span class="text-danger">*</span></label>
                        <input type="text" id="edit_u_username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_u_email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="edit_u_email" name="email" class="form-control" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="edit_u_role">Role <span class="text-danger">*</span></label>
                        <select id="edit_u_role" name="role" class="form-select" required>
                            <option value="cashier">Cashier (POS & Sales)</option>
                            <option value="purchasing">Purchasing / Inventory Staff</option>
                            <option value="admin">Admin / Business Owner</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_u_status">Status <span class="text-danger">*</span></label>
                        <select id="edit_u_status" name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_u_phone">Phone Number</label>
                    <input type="text" id="edit_u_phone" name="phone" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_u_pass">Reset Password (Leave blank to keep current)</label>
                    <input type="password" id="edit_u_pass" name="password" class="form-control" placeholder="New password...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal="editUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Account</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editUser(id, name, username, email, role, phone, status) {
        document.getElementById('editUserForm').action = `/admin/users/${id}`;
        document.getElementById('edit_u_name').value = name;
        document.getElementById('edit_u_username').value = username;
        document.getElementById('edit_u_email').value = email;
        document.getElementById('edit_u_role').value = role;
        document.getElementById('edit_u_phone').value = phone || '';
        document.getElementById('edit_u_status').value = status;
        document.getElementById('edit_u_pass').value = '';
        openModal('editUserModal');
    }
</script>
@endpush
