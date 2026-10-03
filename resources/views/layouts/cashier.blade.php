<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Checkout Terminal | Worthy Acosta</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <!-- Custom Bagsakan Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/bagsakan.css') }}?v={{ time() }}">
    @stack('styles')
</head>
<body style="background-color: #EEF4F9;">
    <!-- POS Top Navigation Bar -->
    <header class="pos-top-header">
        <div class="pos-header-brand">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" class="pos-header-logo">
            <div class="pos-header-titles">
                <div class="pos-header-title">BAGSAKAN POS TERMINAL</div>
                <div class="pos-header-subtitle">COUNTER CHECKOUT</div>
            </div>
        </div>

        <div class="pos-header-actions">
            <div class="pos-header-user" onclick="openModal('myProfileModal')" style="cursor: pointer;" title="Click to edit profile">
                <i class="bi bi-person-badge text-info me-1"></i> Cashier: <strong>{{ auth()->user()->name }}</strong> <i class="bi bi-pencil-fill ms-1" style="font-size: 0.65rem; opacity: 0.7;"></i>
            </div>

            <a href="{{ route('cashier.sales.index') }}" class="btn btn-sm btn-secondary pos-header-btn">
                <i class="bi bi-clock-history"></i> <span class="d-none d-sm-inline">Today's Sales</span>
            </a>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline pos-header-btn" style="color: var(--color-deep-navy); border-color: #CBD5E1; background: #FFFFFF;">
                    <i class="bi bi-arrow-left-circle text-primary"></i> <span class="d-none d-md-inline">Back to Admin</span>
                </a>
            @endif

            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger pos-header-btn" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </header>

    <div class="pos-main-wrapper">
        @yield('content')
    </div>

    <!-- My Profile & Account Settings Modal -->
    <div id="myProfileModal" class="modal-overlay">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-person-gear text-primary me-2"></i> My Account Profile</h3>
                <button type="button" class="modal-close-btn" data-close-modal="myProfileModal"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST" class="ajax-form">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div style="display: flex; align-items: center; gap: 14px; background: var(--color-mist-blue); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div class="avatar-initials" style="width: 46px; height: 46px; font-size: 1.1rem; background: var(--color-primary-blue); color: #fff;">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        <div>
                            <div style="font-weight: 800; color: var(--color-deep-navy); font-size: 1rem;">{{ auth()->user()->name }}</div>
                            <div style="font-size: 0.76rem; color: var(--text-light); text-transform: uppercase; font-weight: 700;">Role: <span class="badge badge-navy">{{ auth()->user()->role }}</span> &bull; Status: <span class="badge badge-success">{{ auth()->user()->status }}</span></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pos_profile_name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="pos_profile_name" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label class="form-label" for="pos_profile_username">Username <span class="text-danger">*</span></label>
                            <input type="text" id="pos_profile_username" name="username" class="form-control" value="{{ auth()->user()->username }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pos_profile_phone">Phone / Mobile</label>
                            <input type="text" id="pos_profile_phone" name="phone" class="form-control" value="{{ auth()->user()->phone }}" placeholder="e.g. 0917-123-4567">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pos_profile_email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="pos_profile_email" name="email" class="form-control" value="{{ auth()->user()->email }}" required>
                    </div>

                    <hr style="border-top: 1px dashed var(--card-border); margin: 16px 0;">

                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                        <i class="bi bi-shield-lock me-1"></i> Change Password (Optional)
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pos_profile_current_pw">Current Password</label>
                        <input type="password" id="pos_profile_current_pw" name="current_password" class="form-control" placeholder="Required only if changing password">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label class="form-label" for="pos_profile_new_pw">New Password</label>
                            <input type="password" id="pos_profile_new_pw" name="password" class="form-control" placeholder="Min. 6 characters">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pos_profile_pw_conf">Confirm Password</label>
                            <input type="password" id="pos_profile_pw_conf" name="password_confirmation" class="form-control" placeholder="Repeat new password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-close-modal="myProfileModal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Global Toast Container -->
    <div id="globalToastContainer" class="toast-container"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/bagsakan.js') }}"></script>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('success', 'Success', @json(session('success')));
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('error', 'Error', @json(session('error')));
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>
