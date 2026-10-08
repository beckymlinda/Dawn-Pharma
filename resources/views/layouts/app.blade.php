@php($brand = business_settings())
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-auth" content="{{ auth()->check() ? '1' : '0' }}">
    <script src="{{ asset('js/session-guard.js') }}?v={{ filemtime(public_path('js/session-guard.js')) }}"></script>
    <title>@yield('title', setting('system_name'))</title>
    <!-- Favicon -->
    <link rel="icon" href="{{ brand_favicon_url() }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <style>
        :root {
            --theme-primary: {{ $brand['primary_color'] ?? '#004d3d' }};
            --theme-secondary: {{ $brand['secondary_color'] ?? '#006b54' }};
        }
        .bg-primary { background-color: var(--theme-primary) !important; }
        .text-primary { color: var(--theme-primary) !important; }
        .btn-primary { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
        .btn-outline-primary { color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
        .btn-outline-primary:hover { background-color: var(--theme-primary) !important; color: white !important; }
    </style>
</head>
<body>
<div class="d-flex" id="wrapper">

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="border-end bg-white" id="sidebar-wrapper" style="width: 250px;">
    
    <div class="sidebar-heading border-bottom d-flex justify-content-between align-items-center">
        <img src="{{ brand_logo_url() }}" alt="{{ setting('system_name') }}" style="height: 50px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
        <span class="text-success">&bull;</span>
    </div>

    <div class="list-group list-group-flush">

        <a href="{{ route('dashboard') }}" 
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-house me-2"></i> Home
        </a>

        
        
        <!-- PRODUCTS DROPDOWN -->
        @if(auth()->user()->hasPermission('view_product') || auth()->user()->hasPermission('add_product') || auth()->user()->hasPermission('view_category') || auth()->user()->hasPermission('view_brand') || auth()->user()->hasPermission('view_unit'))
        <a class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center"
           data-bs-toggle="collapse"
           href="#productsMenu"
           role="button"
           aria-expanded="{{ request()->is('products*') || request()->is('units*') || request()->is('categories*') || request()->is('brands*') ? 'true' : 'false' }}">
            
            <span><i class="bi bi-box-seam me-2"></i> Products</span>
            <i class="bi bi-chevron-down small"></i>
        </a>

        <div class="collapse {{ request()->is('products*') || request()->is('units*') || request()->is('categories*') || request()->is('brands*') ? 'show' : '' }}" 
             id="productsMenu">

            @if(auth()->user()->hasPermission('view_product'))
            <a href="{{ route('products.index') }}" 
               class="list-group-item list-group-item-action ps-5 {{ request()->routeIs('products.*') ? 'active' : '' }}">
                All Products
            </a>
            @endif

            @if(auth()->user()->hasPermission('add_product'))
            <a href="{{ route('products.create') }}" 
               class="list-group-item list-group-item-action ps-5">
                Add Product
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_unit'))
            <a href="{{ route('units.index') }}" 
               class="list-group-item list-group-item-action ps-5 {{ request()->routeIs('units.*') ? 'active' : '' }}">
                Units
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_category'))
            <a href="{{ route('categories.index') }}" 
               class="list-group-item list-group-item-action ps-5 {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                Categories
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_brand'))
            <a href="{{ route('brands.index') }}" 
               class="list-group-item list-group-item-action ps-5 {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                Brands
            </a>
            @endif
        </div>
        @endif

        <!-- END PRODUCTS DROPDOWN -->

       
        <!-- POS Screen -->
        @if(auth()->user()->hasPermission('add_pos_sell'))
        <a href="{{ route('pos.new') }}" 
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('pos.new') ? 'active' : '' }}">
            <i class="bi bi-cart-check me-2"></i> POS Screen
        </a>
        @endif

        <!-- All Sales -->
        @if(auth()->user()->hasPermission('view_pos_sell'))
        <a href="{{ route('sales.index') }}" 
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('sales.*') ? 'active' : '' }}">
            <i class="bi bi-receipt me-2"></i> All Sales
        </a>
        @endif

<!-- Stock Adjustments Dropdown -->
        @if(auth()->user()->hasPermission('view_stock_adjustments') || auth()->user()->hasPermission('create_stock_adjustment'))
        <a class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center"
           data-bs-toggle="collapse"
           href="#stockAdjustmentsMenu"
           role="button"
           aria-expanded="{{ request()->routeIs('stock-adjustments.*') ? 'true' : 'false' }}"
           aria-controls="stockAdjustmentsMenu">
            <span>
                <i class="bi bi-arrow-left-right me-2"></i> Stock Adjustments
            </span>
            <i class="bi bi-chevron-down small"></i>
        </a>

        <!-- Dropdown Items -->
        <div class="collapse ps-4 {{ request()->routeIs('stock-adjustments.*') ? 'show' : '' }}" id="stockAdjustmentsMenu">

            <!-- View Adjustments -->
            @if(auth()->user()->hasPermission('view_stock_adjustments'))
            <a href="{{ route('stock-adjustments.index') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('stock-adjustments.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul me-2"></i> View Adjustments
            </a>
            @endif

            <!-- New Adjustment -->
            @if(auth()->user()->hasPermission('create_stock_adjustment'))
            <a href="{{ route('stock-adjustments.create') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('stock-adjustments.create') ? 'active' : '' }}">
                <i class="bi bi-plus-lg me-2"></i> New Adjustment
            </a>

            <!-- Bulk Upload -->
            <a href="{{ route('stock-adjustments.bulk') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('stock-adjustments.bulk*') ? 'active' : '' }}">
                <i class="bi bi-cloud-upload me-2"></i> Bulk Upload
            </a>
            @endif

        </div>
        @endif

        <!-- Billing: the shop's subscription, with days left -->
        @if(tenant() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin')))
            @php($billingDays = tenant()->daysLeft())
            @php($billingTone = ['grace' => 'bg-danger', 'expired' => 'bg-danger', 'due_soon' => 'bg-warning text-dark'][tenant()->billingState()] ?? 'bg-light text-dark border')
        <a href="{{ route('billing.index') }}"
           class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center {{ request()->routeIs('billing.*') ? 'active' : '' }}">
            <span><i class="bi bi-credit-card-2-front me-2"></i> Billing</span>
            @if($billingDays !== null)
                <span class="badge rounded-pill {{ $billingTone }}" title="Days of subscription left">{{ $billingDays >= 0 ? $billingDays . 'd left' : 'Overdue' }}</span>
            @endif
        </a>
        @endif

        <!-- Expenses -->
        @if(auth()->user()->hasPermission('access_all_expenses'))
        <a href="{{ route('expenses.index') }}"
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <i class="bi bi-wallet2 me-2"></i> Expenses
        </a>
        @endif

        <!-- Activity Log -->
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin'))
        <a href="{{ route('activity-log.index') }}"
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history me-2"></i> Activity Log
        </a>
        @endif

<div class="list-group-item p-0 border-0">

    <!-- Reports Toggle -->
        @if(auth()->user()->hasPermission('view_profit_loss_report') || auth()->user()->hasPermission('view_register_report') || auth()->user()->hasPermission('view_stock_report') || auth()->user()->hasPermission('view_stock_expiry_report'))
        <a class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center {{ request()->routeIs('reports.*') || request()->routeIs('stock.report') ? '' : '' }}"
           data-bs-toggle="collapse"
           href="#reportsMenu"
           role="button"
           aria-expanded="{{ request()->routeIs('reports.*') || request()->routeIs('stock.report') ? 'true' : 'false' }}"
           aria-controls="reportsMenu">
            <span>
                <i class="bi bi-file-earmark-text me-2"></i> Reports
            </span>
            <i class="bi bi-chevron-down small"></i>
        </a>

        <!-- Dropdown Items -->
        <div class="collapse ps-4 {{ request()->routeIs('reports.*') || request()->routeIs('stock.report') ? 'show' : '' }}" id="reportsMenu">

            <!-- Profit / Loss -->
            @if(auth()->user()->hasPermission('view_profit_loss_report'))
            <a href="{{ route('reports.profit-loss') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">
                <i class="bi bi-graph-up me-2"></i> Profit / Loss Report
            </a>
            @endif

            <!-- Register Report -->
            @if(auth()->user()->hasPermission('view_register_report'))
            <a href="{{ route('reports.register-report') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('reports.register-report') ? 'active' : '' }}">
                <i class="bi bi-cash-register me-2"></i> Register Report
            </a>
            @endif

            <!-- Stock Report -->
            @if(auth()->user()->hasPermission('view_stock_report'))
            <a href="{{ route('stock.report') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('stock.report') ? 'active' : '' }}">
                <i class="bi bi-box-seam me-2"></i> Stock Report
            </a>
            @endif

            <!-- Product Report -->
            @if(auth()->user()->hasPermission('view_stock_report'))
            <a href="{{ route('reports.products.index') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('reports.products.*') ? 'active' : '' }}">
                <i class="bi bi-capsule me-2"></i> Product Report
            </a>
            @endif

            <!-- Low Stock Report -->
            @if(auth()->user()->hasPermission('view_stock_report'))
            <a href="{{ route('reports.low-stock') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('reports.low-stock') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle me-2"></i> Low Stock Report
            </a>
            @endif

            <!-- Stock Expiry Report -->
            @if(auth()->user()->hasPermission('view_stock_expiry_report'))
            <a href="{{ route('reports.stock-expiry') }}"
               class="list-group-item list-group-item-action border-0 {{ request()->routeIs('reports.stock-expiry') ? 'active' : '' }}">
                <i class="bi bi-clock-history me-2"></i> Stock Expiry Report
            </a>
            @endif

        </div>
        @endif


<!-- User Management Dropdown -->
        @if(auth()->user()->hasPermission('view_user') || auth()->user()->hasPermission('view_role'))
        <a class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center"
           data-bs-toggle="collapse"
           href="#userManagementMenu"
           role="button"
           aria-expanded="{{ request()->is('admin/users*') || request()->is('admin/roles*') ? 'true' : 'false' }}">
            
            <span><i class="bi bi-people me-2"></i> User Management</span>
            <i class="bi bi-chevron-down small"></i>
        </a>

        <div class="collapse {{ request()->is('admin/users*') || request()->is('admin/roles*') ? 'show' : '' }}" 
             id="userManagementMenu">

            @if(auth()->user()->hasPermission('view_user'))
            <a href="{{ route('admin.users.index') }}" 
               class="list-group-item list-group-item-action ps-5 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-person me-2"></i> Users
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_role'))
            <a href="{{ route('admin.roles.index') }}" 
               class="list-group-item list-group-item-action ps-5 {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield me-2"></i> Roles
            </a>
            @endif
        </div>
        @endif

        <!-- Loss Prevention -->
        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
        <a href="{{ route('integrity.index') }}"
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('integrity.*') ? 'active' : '' }}">
            <i class="bi bi-shield-exclamation me-2"></i> Loss Prevention
        </a>
        @endif

        <!-- Notifications -->
        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
        <a href="{{ route('notifications.index') }}"
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i class="bi bi-bell-fill me-2"></i> Notifications
        </a>
        @endif

        <!-- Settings -->
        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
        <a href="{{ route('settings.index') }}" 
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill me-2"></i> System Settings
        </a>
        @endif

</div>
         
    </div>
</div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper" class="flex-fill">

        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
                {{-- Phones: say which shop this is (the sidebar with the logo is hidden) --}}
                <span class="navbar-shop-name d-md-none text-white fw-semibold text-truncate ms-2">{{ setting('system_name') }}</span>
                {{-- Always visible, including on phones (there is no other way to log out) --}}
                <div class="d-flex align-items-center ms-auto">
                    <ul class="navbar-nav flex-row align-items-center gap-2">
                        @if(auth()->user()->hasPermission('add_pos_sell'))
                        <li class="nav-item">
                            <a href="{{ route('pos.new') }}" class="nav-link text-white d-flex align-items-center gap-1" title="Open POS">
                                <i class="bi bi-cart3" aria-hidden="true"></i><span class="d-none d-md-inline">POS</span>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" id="userDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                                <i class="bi bi-person-circle d-md-none" aria-hidden="true"></i>
                                <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end position-absolute" aria-labelledby="userDropdown">
                                <li class="d-md-none"><span class="dropdown-item-text small text-muted">{{ Auth::user()->name }}</span></li>
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="dropdown-item" style="border: none; background: none; width: 100%; text-align: left; padding: 0.5rem 1rem;">
                                            Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="content-container">
            @if(session('impersonated_by'))
                <div class="alert alert-dark alert-persistent d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3" role="status">
                    <span><i class="bi bi-person-badge me-2"></i><strong>Support session:</strong> logged in as {{ auth()->user()->name }} by {{ session('impersonated_by') }}.</span>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-sm btn-light">End support session</button></form>
                </div>
            @endif
            @if(tenant() && in_array(tenant()->billingState(), ['due_soon', 'grace']) && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin')))
                @php($days = tenant()->daysLeft())
                <div class="alert alert-warning alert-persistent mb-3" role="status">
                    <i class="bi bi-calendar-x me-2"></i>
                    @if(tenant()->billingState() === 'grace')
                        <strong>Your subscription ended on {{ tenant()->paid_until->format('d M Y') }}.</strong>
                        The system will stop working after {{ tenant()->blockedAfter()->format('d M Y') }} unless it is renewed. <a href="{{ route('billing.index') }}" class="alert-link">See billing</a>
                    @else
                        <strong>Your subscription {{ $days === 0 ? 'ends today' : 'ends in ' . $days . ' ' . ($days === 1 ? 'day' : 'days') }}</strong> ({{ tenant()->paid_until->format('d M Y') }}).
                        Please renew to keep using the system. <a href="{{ route('billing.index') }}" class="alert-link">See billing</a>
                    @endif
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
    <!-- /#page-content-wrapper -->

</div>
<!-- /#wrapper -->
          <!-- #region 
    -->

<style>
    /* Fixed Layout with Independent Scrolling */
    html, body {
        height: 100%;
        margin: 0;
        overflow: hidden; /* Prevent body scroll */
    }

    #wrapper {
        height: 100vh;
        width: 100vw;
        display: flex;
        overflow: hidden;
    }

    #sidebar-wrapper {
        height: 100vh;
        overflow-y: auto;
        flex-shrink: 0;
        z-index: 1000;
        transition: all 0.25s ease-in-out;
    }

    #page-content-wrapper {
        height: 100vh;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow: hidden;
    }

    .navbar {
        flex-shrink: 0;
        z-index: 999;
        background: linear-gradient(90deg, var(--theme-primary) 0%, var(--theme-secondary) 100%);
        color: #ffffff;
        border: none !important;
    }

    /* Main Content Area with Scroll */
    .content-container {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background-color: #f8f9fa;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    /* Top bar stays on one line on phones */
    .navbar > .container-fluid { flex-wrap: nowrap; }
    .navbar-shop-name { min-width: 0; max-width: 55vw; }

    /* Navbar text */
    .navbar .navbar-text,
    .navbar .nav-link,
    .navbar .dropdown-toggle {
        color: #ffffff !important;
    }

    /* Hover effect */
    .navbar .nav-link:hover,
    .navbar .dropdown-toggle:hover {
        color: #e0e0e0 !important;
    }

    /* QR Code icon styling */
    .navbar .nav-link .bi-qr-code-scan {
        font-size: 1.2rem;
        transition: transform 0.2s ease;
    }

    .navbar .nav-link:hover .bi-qr-code-scan {
        transform: scale(1.1);
    }

    /* Date input styling inside navbar */
    .navbar input[type="date"] {
        background-color: rgba(255,255,255,0.15);
        border: none;
        color: #ffffff;
    }

    .navbar input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    /* Sidebar header */
    .sidebar-heading {
        background: #fff9e6;
        color: #004d3d;
        font-weight: 600;
        padding: 15px;
    }

    /* Sidebar Links */
    .list-group-item {
        border: none;
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
    }
    .list-group-item.active {
        background-color: #f0fdf4;
        color: var(--theme-primary);
        border-right: 4px solid var(--theme-primary);
        font-weight: 600;
    }
    .list-group-item:hover:not(.active) {
        background-color: #f8f9fa;
    }

    /* Mobile Responsive Sidebar */
    @media (max-width: 768px) {
        #sidebar-wrapper {
            position: fixed;
            left: -250px;
        }
        #sidebar-wrapper.show {
            left: 0;
        }
        #sidebarToggle {
            display: block !important;
        }
    }

    @media (min-width: 769px) {
        #sidebarToggle {
            display: block !important; /* Always show toggle on desktop too */
        }
        #wrapper.toggled #sidebar-wrapper {
            margin-left: -250px;
        }
    }
</style>
    <!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="{{ asset('assets/js/layout.js') }}?v={{ filemtime(public_path('assets/js/layout.js')) }}"></script>

<script>
    // Enhanced mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing sidebar...');
        
        const sidebarToggle = document.getElementById('sidebarToggle');
        const wrapper = document.getElementById('wrapper');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        console.log('Sidebar elements:', {
            sidebarToggle: !!sidebarToggle,
            wrapper: !!wrapper,
            sidebarWrapper: !!sidebarWrapper,
            sidebarOverlay: !!sidebarOverlay
        });
        
        // Check if mobile
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        // Toggle sidebar
        function toggleSidebar() {
            console.log('Toggle sidebar clicked, isMobile:', isMobile());
            
            if (isMobile()) {
                // Mobile behavior
                sidebarWrapper.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                console.log('Mobile sidebar toggled');
            } else {
                // Desktop behavior
                wrapper.classList.toggle('toggled');
                console.log('Desktop sidebar toggled');
            }
        }
        
        // Close sidebar (mobile only)
        function closeSidebar() {
            console.log('Closing sidebar...');
            if (isMobile()) {
                sidebarWrapper.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        }
        
        // Event listeners
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
            console.log('Sidebar toggle event listener added');
        } else {
            console.error('Sidebar toggle button not found');
        }
        
        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
            console.log('Sidebar overlay event listener added');
        }
        
        // Close sidebar on window resize if switching to desktop
        window.addEventListener('resize', function() {
            if (!isMobile()) {
                sidebarWrapper.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
        
        // Initialize sidebar state on mobile
        if (isMobile()) {
            sidebarWrapper.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            console.log('Mobile sidebar initialized as hidden');
        }

        // Session keep-alive and CSRF token refresh are handled by public/js/session-guard.js
    });
</script>

@yield('scripts')

</body>
</html>