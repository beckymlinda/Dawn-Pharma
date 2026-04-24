<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EDUC Pharmacy')</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
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
    
    <div class="sidebar-heading border-bottom bg-light d-flex justify-content-between align-items-center">
        <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC Pharmacy" style="height: 40px; width: auto;">
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
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <span class="navbar-text ms-3">{{ now()->format('l, F j, Y') }} 👋</span>
                <div class="collapse navbar-collapse justify-content-end">
                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                        <li class="nav-item me-3">
                            <a href="{{ route('pos.new') }}" class="nav-link text-white" title="Open POS">
                                <i class="bi bi-qr-code-scan"></i>
                            </a>
                        </li>
                        <li class="nav-item me-3">
                            <input type="date" class="form-control form-control-sm" />
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
        <div class="container-fluid p-4">
            @yield('content')
        </div>
    </div>
    <!-- /#page-content-wrapper -->

</div>
<!-- /#wrapper -->
          <!-- #region 
    -->

<style>

    /* ===== Top Navbar Blue Gradient ===== */
.navbar {
    background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);
    color: #ffffff;
    border: none !important;
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

/* Sidebar header (to match top blue section in screenshot) */
.sidebar-heading {
    background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);
    color: #ffffff;
    font-weight: 600;
    padding: 15px;
}

/* POS Screen Styling */
.pos-wrapper {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #f5f5f5;
    overflow: hidden;
}

.pos-header {
    background: #ffffff;
    border-bottom: 1px solid #ddd;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    height: auto;
    flex-shrink: 0;
}

.pos-header .badge {
    padding: 6px 12px;
    font-size: 12px;
    white-space: nowrap;
}

.toolbar-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    white-space: nowrap;
    transition: all 0.2s;
}

.toolbar-btn:hover {
    background: #e8e8e8;
    border-color: #999;
}

.pos-bottom-bar {
    background: #ffffff;
    border-top: 1px solid #ddd;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    height: auto;
    flex-shrink: 0;
}

.pos-bottom-bar .btn {
    margin: 0;
}

.pos-product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    height: 100%;
}

.pos-product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #2563eb;
}

.pos-product-img {
    width: 100%;
    height: 100px;
    background: #f0f0f0;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    font-size: 32px;
    color: #ccc;
}

.pos-product-card .small {
    display: block;
    margin: 4px 0;
    word-wrap: break-word;
}

/* Ensure flex layout works correctly */
.d-flex {
    display: flex;
}

.flex-grow-1 {
    flex-grow: 1;
}

.flex-column {
    flex-direction: column;
}

.overflow-hidden {
    overflow: hidden;
}

.overflow-auto {
    overflow: auto;
}

.border-end {
    border-right: 1px solid #ddd;
}

/* Mobile Responsive Sidebar */
@media (max-width: 768px) {
    #sidebar-wrapper {
        position: fixed;
        top: 0;
        left: -250px;
        width: 250px;
        height: 100vh;
        z-index: 1050;
        transition: left 0.3s ease;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        background: white !important;
        border-right: 1px solid #ddd;
    }
    
    #sidebar-wrapper.show {
        left: 0 !important;
        display: block !important;
    }
    
    #page-content-wrapper {
        margin-left: 0;
    }
    
    #wrapper.toggled #sidebar-wrapper {
        left: 0 !important;
    }
    
    #wrapper.toggled #page-content-wrapper {
        margin-left: 0;
    }
    
    /* Mobile overlay when sidebar is open */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .sidebar-overlay.show {
        display: block !important;
        opacity: 1 !important;
    }
    
    /* Show toggle button on mobile */
    #sidebarToggle {
        display: block !important;
        background: transparent;
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    #sidebarToggle:hover {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.5);
    }
}

/* Desktop styles */
@media (min-width: 769px) {
    #sidebar-wrapper {
        position: relative;
        left: 0;
    }
    
    #page-content-wrapper {
        margin-left: 0;
    }
    
    #wrapper.toggled #sidebar-wrapper {
        margin-left: -250px;
    }
    
    #wrapper.toggled #page-content-wrapper {
        margin-left: 0;
    }
    
    #sidebarToggle {
        display: none !important;
    }
    
    .sidebar-overlay {
        display: none !important;
    }
}

/* Ensure sidebar is hidden by default on mobile */
@media (max-width: 768px) {
    #sidebar-wrapper {
        left: -250px !important;
        display: block !important;
    }
    
    .sidebar-overlay {
        display: none !important;
        opacity: 0 !important;
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
    });
</script>

@yield('scripts')

</body>
</html>