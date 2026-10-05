<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Worthy Acosta - Bagsakan POS & Inventory</title>

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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom Bagsakan Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/bagsakan.css') }}?v={{ time() }}">
    @stack('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <div class="sidebar-header">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Worthy Acosta Logo" class="sidebar-brand-logo">
                <div class="sidebar-brand-text">
                    <span class="brand-title">BAGSAKAN</span>
                    <span class="brand-subtitle">POS & Inventory</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                @if(auth()->user()->isAdmin())
                    <!-- Admin Navigation -->
                    <div class="nav-section-title">Core Management</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam"></i>
                        <span>Products & Units</span>
                    </a>
                    <a href="{{ route('admin.pricing.index') }}" class="nav-item {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>
                        <span>Pricing & SRP History</span>
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="nav-item {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                        <i class="bi bi-boxes"></i>
                        <span>Stock & Batch Ledger</span>
                    </a>
                    <a href="{{ route('admin.warehouses.index') }}" class="nav-item {{ request()->routeIs('admin.warehouses.*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>
                        <span>Warehouses & Transfers</span>
                    </a>

                    <div class="nav-section-title">Purchasing & Inbound</div>
                    <a href="{{ route('admin.purchase-requests.index') }}" class="nav-item {{ request()->routeIs('admin.purchase-requests.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Purchase Requests (PR)</span>
                    </a>
                    <a href="{{ route('admin.purchases.index') }}" class="nav-item {{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}">
                        <i class="bi bi-cart-check"></i>
                        <span>Purchase Orders (PO)</span>
                    </a>
                    <a href="{{ route('admin.suppliers.index') }}" class="nav-item {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                        <i class="bi bi-truck"></i>
                        <span>Suppliers & Payables</span>
                    </a>

                    <div class="nav-section-title">Sales, Quotes & AR</div>
                    <a href="{{ route('cashier.pos') }}" class="nav-item" target="_blank">
                        <i class="bi bi-display"></i>
                        <span>Launch POS Terminal</span>
                    </a>
                    <a href="{{ route('admin.quotations.index') }}" class="nav-item {{ request()->routeIs('admin.quotations.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Customer Quotations</span>
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="nav-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>Customers & Credit</span>
                    </a>
                    <a href="{{ route('admin.receivables.index') }}" class="nav-item {{ request()->routeIs('admin.receivables.*') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i>
                        <span>Receivables & Tax Collections</span>
                    </a>
                    <a href="{{ route('admin.expenses.index') }}" class="nav-item {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i>
                        <span>Operating Expenses</span>
                    </a>
                    <a href="{{ route('admin.spoilage.index') }}" class="nav-item {{ request()->routeIs('admin.spoilage.*') ? 'active' : '' }}">
                        <i class="bi bi-trash3"></i>
                        <span>Spoilage & Losses</span>
                    </a>

                    <div class="nav-section-title">Reports & Control</div>
                    <a href="{{ route('admin.reports.sales') }}" class="nav-item {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Sales & COGS Report</span>
                    </a>
                    <a href="{{ route('admin.reports.inventory') }}" class="nav-item {{ request()->routeIs('admin.reports.inventory') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-data"></i>
                        <span>Inventory Valuation</span>
                    </a>
                    <a href="{{ route('admin.reports.suppliers') }}" class="nav-item {{ request()->routeIs('admin.reports.suppliers') ? 'active' : '' }}">
                        <i class="bi bi-shop"></i>
                        <span>Supplier Purchases</span>
                    </a>
                    <a href="{{ route('admin.reports.pnl') }}" class="nav-item {{ request()->routeIs('admin.reports.pnl') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Profit & Loss</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i>
                        <span>User Accounts</span>
                    </a>
                    <a href="{{ route('admin.audit.index') }}" class="nav-item {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Audit Trail Log</span>
                    </a>
                @elseif(auth()->user()->isPurchasing())
                    <!-- Purchasing / Staff Navigation -->
                    <div class="nav-section-title">Staff Operations</div>
                    <a href="{{ route('staff.dashboard') }}" class="nav-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('staff.quotations.index') }}" class="nav-item {{ request()->routeIs('staff.quotations.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Customer Quotations</span>
                    </a>
                    <a href="{{ route('staff.purchase-requests.index') }}" class="nav-item {{ request()->routeIs('staff.purchase-requests.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Purchase Requests (PR)</span>
                    </a>
                    <a href="{{ route('staff.purchases.index') }}" class="nav-item {{ request()->routeIs('staff.purchases.*') ? 'active' : '' }}">
                        <i class="bi bi-cart-plus"></i>
                        <span>Purchase Orders</span>
                    </a>
                    <a href="{{ route('staff.receiving.index') }}" class="nav-item {{ request()->routeIs('staff.receiving.*') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-down"></i>
                        <span>Inbound Goods Receiving</span>
                    </a>
                    <a href="{{ route('staff.inventory.index') }}" class="nav-item {{ request()->routeIs('staff.inventory.*') ? 'active' : '' }}">
                        <i class="bi bi-boxes"></i>
                        <span>Stock & Batch Ledger</span>
                    </a>
                    <a href="{{ route('staff.spoilage.index') }}" class="nav-item {{ request()->routeIs('staff.spoilage.*') ? 'active' : '' }}">
                        <i class="bi bi-trash3"></i>
                        <span>Report Spoilage</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile-card" onclick="openModal('myProfileModal')" title="Edit Profile & Account Settings">
                    <div class="avatar-initials-wrapper">
                        <div class="avatar-initials">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        <span class="avatar-status-dot"></span>
                    </div>
                    <div class="user-meta-info">
                        <div class="user-meta-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                        <div class="user-meta-role-tag">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                    <button type="button" class="profile-settings-btn" title="Edit Profile">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="sidebar-logout-btn" title="Sign Out">
                        <i class="bi bi-box-arrow-right"></i> <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="app-main">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <button type="button" class="mobile-toggle-btn" id="mobileSidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="page-headline">@yield('page_title', 'Dashboard')</h1>
                </div>

                <div class="header-right">
                    <div class="system-clock-badge">
                        <i class="bi bi-clock"></i>
                        <span id="liveClock">{{ now()->setTimezone('Asia/Manila')->format('M d, Y | h:i A') }}</span>
                    </div>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('cashier.pos') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-cart3"></i> POS Terminal
                        </a>
                    @endif
                </div>
            </header>

            <!-- Page Content -->
            <div class="app-content">
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

                @yield('content')
            </div>
        </main>
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
                        <label class="form-label" for="my_profile_name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="my_profile_name" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label class="form-label" for="my_profile_username">Username <span class="text-danger">*</span></label>
                            <input type="text" id="my_profile_username" name="username" class="form-control" value="{{ auth()->user()->username }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="my_profile_phone">Phone / Mobile</label>
                            <input type="text" id="my_profile_phone" name="phone" class="form-control" value="{{ auth()->user()->phone }}" placeholder="e.g. 0917-123-4567">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="my_profile_email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="my_profile_email" name="email" class="form-control" value="{{ auth()->user()->email }}" required>
                    </div>

                    <hr style="border-top: 1px dashed var(--card-border); margin: 16px 0;">

                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                        <i class="bi bi-shield-lock me-1"></i> Change Password (Optional)
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="my_profile_current_pw">Current Password</label>
                        <input type="password" id="my_profile_current_pw" name="current_password" class="form-control" placeholder="Required only if changing password">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label class="form-label" for="my_profile_new_pw">New Password</label>
                            <input type="password" id="my_profile_new_pw" name="password" class="form-control" placeholder="Min. 6 characters">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="my_profile_pw_conf">Confirm Password</label>
                            <input type="password" id="my_profile_pw_conf" name="password_confirmation" class="form-control" placeholder="Repeat new password">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="{{ asset('assets/js/bagsakan.js') }}"></script>

    <script>
        // Real-time clock update (Asia/Manila)
        setInterval(() => {
            const now = new Date();
            const options = { timeZone: 'Asia/Manila', month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const el = document.getElementById('liveClock');
            if (el) el.innerText = now.toLocaleString('en-US', options);
        }, 1000);
    </script>
    @stack('scripts')
</body>
</html>
