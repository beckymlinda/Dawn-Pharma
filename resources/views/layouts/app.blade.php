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
    @php($sbUser = auth()->user())
    @php($sbAdmin = $sbUser->hasRole('admin') || $sbUser->hasRole('super_admin'))
    @php($sbProducts = $sbUser->hasPermission('view_product') || $sbUser->hasPermission('add_product') || $sbUser->hasPermission('view_category') || $sbUser->hasPermission('view_brand') || $sbUser->hasPermission('view_unit'))
    @php($sbStock = $sbUser->hasPermission('view_stock_adjustments') || $sbUser->hasPermission('create_stock_adjustment'))
    @php($sbExpenses = $sbUser->hasPermission('access_all_expenses'))
    @php($sbReports = $sbUser->hasPermission('view_profit_loss_report') || $sbUser->hasPermission('view_register_report') || $sbUser->hasPermission('view_stock_report') || $sbUser->hasPermission('view_stock_expiry_report'))
    @php($sbPeople = $sbUser->hasPermission('view_user') || $sbUser->hasPermission('view_role') || $sbAdmin)
    @php($sbProductsOpen = request()->is('products*') || request()->is('units*') || request()->is('categories*') || request()->is('brands*'))
    @php($sbStockOpen = request()->routeIs('stock-adjustments.*'))
    @php($sbReportsOpen = request()->routeIs('reports.*') || request()->routeIs('stock.report'))
    @php($sbHasLogo = brand_image_exists(setting('logo')))
    <aside id="sidebar-wrapper" class="sb" aria-label="Main menu">

        {{-- Brand: logo tile, shop name and where it is --}}
        <a href="{{ route('dashboard') }}" class="sb-brand" title="{{ setting('system_name') }}">
            <span class="sb-logo {{ $sbHasLogo ? 'has-image' : '' }}">
                <img src="{{ brand_logo_url() }}" alt="">
            </span>
            <span class="sb-brand-text">
                <span class="sb-name">{{ setting('system_name') }}</span>
                <span class="sb-place">{{ setting('location') ?: 'Pharmacy POS' }}</span>
            </span>
        </a>

        <nav class="sb-nav" id="sbNav">
            <a href="{{ route('dashboard') }}" class="sb-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
            </a>
            @if($sbUser->hasPermission('add_pos_sell'))
            <a href="{{ route('pos.new') }}" class="sb-link {{ request()->routeIs('pos.new') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i><span>POS Screen</span>
            </a>
            @endif
            @if($sbUser->hasPermission('view_pos_sell'))
            <a href="{{ route('sales.index') }}" class="sb-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i><span>Sales</span>
            </a>
            @endif

            {{-- Inventory --}}
            @if($sbProducts || $sbStock)
            <div class="sb-label">Inventory</div>
            @endif
            @if($sbProducts)
            <button type="button" class="sb-link sb-toggle {{ $sbProductsOpen ? 'has-active' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#sbProducts" aria-expanded="{{ $sbProductsOpen ? 'true' : 'false' }}" aria-controls="sbProducts">
                <i class="bi bi-box-seam"></i><span>Products</span><i class="bi bi-chevron-down sb-caret"></i>
            </button>
            <div class="collapse {{ $sbProductsOpen ? 'show' : '' }}" id="sbProducts" data-bs-parent="#sbNav">
                <div class="sb-sub sb-sub-grid">
                    @if($sbUser->hasPermission('view_product'))
                    <a href="{{ route('products.index') }}" class="sb-sublink {{ request()->routeIs('products.index') || request()->routeIs('products.show') || request()->routeIs('products.edit') ? 'active' : '' }}">All products</a>
                    @endif
                    @if($sbUser->hasPermission('add_product'))
                    <a href="{{ route('products.create') }}" class="sb-sublink {{ request()->routeIs('products.create') ? 'active' : '' }}">Add product</a>
                    @endif
                    @if($sbUser->hasPermission('view_category'))
                    <a href="{{ route('categories.index') }}" class="sb-sublink {{ request()->routeIs('categories.*') ? 'active' : '' }}">Categories</a>
                    @endif
                    @if($sbUser->hasPermission('view_brand'))
                    <a href="{{ route('brands.index') }}" class="sb-sublink {{ request()->routeIs('brands.*') ? 'active' : '' }}">Brands</a>
                    @endif
                    @if($sbUser->hasPermission('view_unit'))
                    <a href="{{ route('units.index') }}" class="sb-sublink {{ request()->routeIs('units.*') ? 'active' : '' }}">Units</a>
                    @endif
                </div>
            </div>
            @endif
            @if($sbStock)
            <button type="button" class="sb-link sb-toggle {{ $sbStockOpen ? 'has-active' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#sbStock" aria-expanded="{{ $sbStockOpen ? 'true' : 'false' }}" aria-controls="sbStock">
                <i class="bi bi-arrow-left-right"></i><span>Stock adjustments</span><i class="bi bi-chevron-down sb-caret"></i>
            </button>
            <div class="collapse {{ $sbStockOpen ? 'show' : '' }}" id="sbStock" data-bs-parent="#sbNav">
                <div class="sb-sub">
                    @if($sbUser->hasPermission('view_stock_adjustments'))
                    <a href="{{ route('stock-adjustments.index') }}" class="sb-sublink {{ request()->routeIs('stock-adjustments.index') ? 'active' : '' }}">All adjustments</a>
                    @endif
                    @if($sbUser->hasPermission('create_stock_adjustment'))
                    <a href="{{ route('stock-adjustments.create') }}" class="sb-sublink {{ request()->routeIs('stock-adjustments.create') ? 'active' : '' }}">New adjustment</a>
                    <a href="{{ route('stock-adjustments.bulk') }}" class="sb-sublink {{ request()->routeIs('stock-adjustments.bulk*') ? 'active' : '' }}">Bulk upload</a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Money --}}
            @if($sbExpenses || $sbReports)
            <div class="sb-label">Money</div>
            @endif
            @if($sbExpenses)
            <a href="{{ route('expenses.index') }}" class="sb-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i><span>Expenses</span>
            </a>
            @endif
            @if($sbReports)
            <button type="button" class="sb-link sb-toggle {{ $sbReportsOpen ? 'has-active' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#sbReports" aria-expanded="{{ $sbReportsOpen ? 'true' : 'false' }}" aria-controls="sbReports">
                <i class="bi bi-bar-chart-line"></i><span>Reports</span><i class="bi bi-chevron-down sb-caret"></i>
            </button>
            <div class="collapse {{ $sbReportsOpen ? 'show' : '' }}" id="sbReports" data-bs-parent="#sbNav">
                <div class="sb-sub sb-sub-grid">
                    @if($sbUser->hasPermission('view_profit_loss_report'))
                    <a href="{{ route('reports.profit-loss') }}" class="sb-sublink {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">Profit &amp; loss</a>
                    @endif
                    @if($sbUser->hasPermission('view_register_report'))
                    <a href="{{ route('reports.register-report') }}" class="sb-sublink {{ request()->routeIs('reports.register-report') ? 'active' : '' }}">Registers</a>
                    @endif
                    @if($sbUser->hasPermission('view_stock_report'))
                    <a href="{{ route('stock.report') }}" class="sb-sublink {{ request()->routeIs('stock.report') ? 'active' : '' }}">Stock</a>
                    <a href="{{ route('reports.products.index') }}" class="sb-sublink {{ request()->routeIs('reports.products.*') ? 'active' : '' }}">Products</a>
                    <a href="{{ route('reports.low-stock') }}" class="sb-sublink {{ request()->routeIs('reports.low-stock') ? 'active' : '' }}">Low stock</a>
                    @endif
                    @if($sbUser->hasPermission('view_stock_expiry_report'))
                    <a href="{{ route('reports.stock-expiry') }}" class="sb-sublink {{ request()->routeIs('reports.stock-expiry') ? 'active' : '' }}">Expiry</a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Manage (users, roles and notifications are under Settings) --}}
            @if($sbPeople)
            <div class="sb-label">Manage</div>
            @endif
            @if($sbAdmin)
            <a href="{{ route('integrity.index') }}" class="sb-link {{ request()->routeIs('integrity.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i><span>Loss prevention</span>
            </a>
            <a href="{{ route('activity-log.index') }}" class="sb-link {{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i><span>Activity log</span>
            </a>
            @endif
            @if($sbPeople)
            <a href="{{ $sbAdmin ? route('settings.index') : ($sbUser->hasPermission('view_user') ? route('admin.users.index') : route('admin.roles.index')) }}"
               class="sb-link {{ request()->routeIs('settings.*', 'notifications.*', 'admin.users.*', 'admin.roles.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i><span>Settings</span>
            </a>
            @endif
        </nav>

        {{-- Billing stays in view at the bottom, with the days left --}}
        @if(tenant() && $sbAdmin)
            @php($billingDays = tenant()->daysLeft())
            @php($billingTone = ['grace' => 'is-danger', 'expired' => 'is-danger', 'due_soon' => 'is-warning'][tenant()->billingState()] ?? '')
        <div class="sb-footer">
            <a href="{{ route('billing.index') }}" class="sb-link {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-front"></i><span>Billing</span>
                @if($billingDays !== null)
                    <span class="sb-badge {{ $billingTone }}" title="Days of subscription left">{{ $billingDays >= 0 ? $billingDays . 'd left' : 'Overdue' }}</span>
                @endif
            </a>
        </div>
        @endif
    </aside>
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

    /* Sidebar */
    #sidebar-wrapper.sb {
        width: 250px;
        display: flex;
        flex-direction: column;
        background: var(--theme-primary);
        background: linear-gradient(180deg, color-mix(in srgb, var(--theme-primary) 94%, #000) 0%, color-mix(in srgb, var(--theme-primary) 72%, #000) 100%);
        color: rgba(255, 255, 255, .82);
    }
    .sb-brand {
        display: flex; align-items: center; gap: .7rem;
        min-height: 60px; padding: .6rem 1rem; flex-shrink: 0;
        color: #fff; text-decoration: none;
        border-bottom: 1px solid rgba(255, 255, 255, .1);
    }
    .sb-brand:hover { color: #fff; }
    .sb-logo {
        width: 40px; height: 40px; flex-shrink: 0; border-radius: 11px; overflow: hidden;
        display: grid; place-items: center;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, .9), 0 4px 10px rgba(0, 0, 0, .25);
    }
    .sb-logo img { width: 100%; height: 100%; display: block; }
    .sb-logo.has-image { background: #fff; padding: 3px; }
    .sb-logo.has-image img { object-fit: contain; }
    .sb-brand-text { display: flex; flex-direction: column; min-width: 0; }
    .sb-name { font-weight: 700; font-size: .95rem; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sb-place { font-size: .72rem; color: rgba(255, 255, 255, .6); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .sb-nav { flex: 1; overflow-y: auto; padding: .4rem .6rem .35rem; scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, .25) transparent; }
    .sb-nav::-webkit-scrollbar { width: 5px; }
    .sb-nav::-webkit-scrollbar-track { background: transparent; }
    .sb-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .25); }
    .sb-label {
        padding: .5rem .7rem .15rem; font-size: .66rem; font-weight: 700;
        letter-spacing: .09em; text-transform: uppercase; color: rgba(255, 255, 255, .45);
    }
    .sb-link {
        display: flex; align-items: center; gap: .7rem; width: 100%;
        padding: .38rem .7rem; margin-bottom: 1px; border: 0; border-radius: 8px;
        background: none; color: rgba(255, 255, 255, .82); text-align: left;
        font-size: .875rem; line-height: 1.3; text-decoration: none;
        transition: background-color .15s, color .15s;
    }
    .sb-link > i:first-child { width: 1.15rem; font-size: 1.05rem; text-align: center; flex-shrink: 0; }
    .sb-link:hover { background: rgba(255, 255, 255, .09); color: #fff; }
    .sb-link:focus-visible, .sb-sublink:focus-visible { outline: 2px solid #fff; outline-offset: -2px; }
    .sb-link.active { background: #fff; color: var(--theme-primary); font-weight: 600; box-shadow: 0 2px 8px rgba(0, 0, 0, .18); }
    .sb-toggle.has-active { color: #fff; font-weight: 600; }
    .sb-caret { margin-left: auto; font-size: .7rem; opacity: .7; transition: transform .2s; }
    .sb-toggle:not(.collapsed) .sb-caret { transform: rotate(180deg); }
    .sb-sub { margin: .1rem 0 .3rem 1.3rem; padding-left: .6rem; border-left: 1px solid rgba(255, 255, 255, .16); }
    .sb-sub-grid { display: grid; grid-template-columns: auto auto; justify-content: start; gap: 1px 2px; }
    .sb-sub-cols { grid-auto-flow: row dense; }
    .sb-sub-cols .sb-sublink { grid-column: 1; }
    .sb-sub-cols .sb-sublink.sb-col-2 { grid-column: 2; }
    .sb-sub-grid .sb-sublink { padding-left: .5rem; padding-right: .5rem; font-size: .8rem; white-space: nowrap; }
    .sb-sublink {
        display: block; padding: .3rem .55rem; border-radius: 6px;
        font-size: .83rem; color: rgba(255, 255, 255, .7); text-decoration: none;
    }
    .sb-sublink:hover { color: #fff; background: rgba(255, 255, 255, .07); }
    .sb-sublink.active { color: #fff; font-weight: 600; background: rgba(255, 255, 255, .14); }

    .sb-footer { flex-shrink: 0; padding: .55rem .6rem; border-top: 1px solid rgba(255, 255, 255, .1); }
    .sb-footer .sb-link { margin: 0; }
    .sb-badge {
        margin-left: auto; padding: .15rem .5rem; border-radius: 999px;
        font-size: .7rem; font-weight: 600; background: rgba(255, 255, 255, .16); color: #fff;
    }
    .sb-link.active .sb-badge { background: color-mix(in srgb, var(--theme-primary) 12%, #fff); color: var(--theme-primary); }
    .sb-badge.is-warning { background: #fbbf24; color: #422006; }
    .sb-badge.is-danger { background: #ef4444; color: #fff; }


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