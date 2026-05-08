<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EDUC PHARMACY')</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>
<div class="d-flex" id="wrapper">

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="border-end bg-white" id="sidebar-wrapper" style="width: 250px;">
    
    <div class="sidebar-heading border-bottom d-flex justify-content-center align-items-center py-2">
        <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC PHARMACY" class="sidebar-logo">
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
            @endif

        </div>
        @endif

        <!-- Activity Log -->
        <a href="{{ route('activity-log.index') }}" 
           class="list-group-item list-group-item-action p-3 {{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history me-2"></i> Activity Log
        </a>

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

</div>
         
    </div>
</div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper" class="flex-fill">

        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark border-bottom shadow-sm">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-light me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
                </div>
                
                <div class="collapse navbar-collapse justify-content-end">
                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                        <li class="nav-item me-3">
                            <a href="{{ route('pos.new') }}" class="btn btn-light btn-sm rounded-pill px-3" title="Open POS">
                                <i class="bi bi-qr-code-scan me-1"></i> POS
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name ?? 'Ishmael Nyirenda' }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="#">Profile</a></li>
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
        background: linear-gradient(90deg, #1e40af 0%, #1e3a8a 100%);
        color: #ffffff;
        border: none !important;
        height: 60px;
        padding: 0 1rem;
    }

    .sidebar-logo {
        height: 40px;
        width: auto;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
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
        background: #f8fafc;
        color: #1e40af;
        font-weight: 600;
        padding: 10px;
        height: 60px; /* Match navbar height */
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Sidebar Links */
    .list-group-item {
        border: none;
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
    }
    .list-group-item.active {
        background-color: #eff6ff;
        color: #1e40af;
        border-right: 4px solid #1e40af;
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
<script src="{{ asset('assets/js/layout.js') }}"></script>

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

        // Session Keep-Alive to prevent 419 Page Expired
        setInterval(function() {
            fetch('/dashboard', { 
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(response => console.log('Session refreshed'))
              .catch(error => console.error('Session refresh failed', error));
        }, 15 * 60 * 1000); // Refresh every 15 minutes
    });
</script>

@yield('scripts')

</body>
</html>