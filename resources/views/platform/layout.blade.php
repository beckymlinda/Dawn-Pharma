<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Dashboard') · Admin portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .portal-sidebar { width: 240px; background: #0f172a; min-height: 100vh; position: fixed; inset: 0 auto 0 0; z-index: 1040;
            display: flex; flex-direction: column; transition: transform .2s ease; }
        .portal-brand { color: #fff; font-weight: 700; font-size: 1.1rem; padding: 1.1rem 1.25rem; text-decoration: none; display: flex; align-items: center; gap: .5rem; }
        .portal-nav a { color: #cbd5e1; text-decoration: none; display: flex; align-items: center; gap: .7rem; padding: .7rem 1.25rem; border-left: 3px solid transparent; }
        .portal-nav a:hover { color: #fff; background: rgba(255, 255, 255, .05); }
        .portal-nav a.active { color: #fff; background: rgba(255, 255, 255, .08); border-left-color: #22c55e; }
        .portal-nav .badge { margin-left: auto; }
        .portal-user { margin-top: auto; padding: 1rem 1.25rem; border-top: 1px solid rgba(255, 255, 255, .08); color: #94a3b8; font-size: .85rem; }
        .portal-main { margin-left: 240px; min-height: 100vh; }
        .portal-topbar { display: none; }
        .card { border: 0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
        .stat-value { font-size: clamp(1.15rem, 1.6vw, 1.6rem); font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .state-badge { font-size: .75rem; }
        .table > :not(caption) > * > * { padding: .7rem .75rem; }
        @media (max-width: 991.98px) {
            .portal-sidebar { transform: translateX(-100%); }
            .portal-sidebar.open { transform: none; box-shadow: 0 0 40px rgba(0, 0, 0, .4); }
            .portal-main { margin-left: 0; }
            .portal-topbar { display: flex; background: #0f172a; color: #fff; padding: .6rem 1rem; align-items: center; gap: .75rem; position: sticky; top: 0; z-index: 1030; }
        }
    </style>
</head>
<body>
@php($waitingProofs = \App\Models\PaymentSubmission::where('status', 'pending')->count())

<aside class="portal-sidebar" id="portalSidebar" aria-label="Admin portal">
    <a class="portal-brand" href="{{ route('platform.index') }}"><i class="bi bi-grid-3x3-gap-fill"></i> Admin portal</a>
    <nav class="portal-nav">
        <a href="{{ route('platform.index') }}" class="{{ request()->routeIs('platform.index') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('platform.income') }}" class="{{ request()->routeIs('platform.income') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Income
        </a>
        <a href="{{ route('platform.payments.index') }}" class="{{ request()->routeIs('platform.payments.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Payments to review
            @if($waitingProofs)<span class="badge rounded-pill bg-warning text-dark">{{ $waitingProofs }}</span>@endif
        </a>
        <a href="{{ route('platform.tenants.index') }}" class="{{ request()->routeIs('platform.tenants.index', 'platform.tenants.show') ? 'active' : '' }}">
            <i class="bi bi-shop"></i> Shops
        </a>
        <a href="{{ route('platform.tenants.create') }}" class="{{ request()->routeIs('platform.tenants.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle"></i> New shop
        </a>
    </nav>
    <div class="portal-user">
        <div class="text-white">{{ auth()->user()->name }}</div>
        <div class="text-truncate mb-2">{{ auth()->user()->email }}</div>
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i> Log out</button>
        </form>
    </div>
</aside>

<div class="portal-main">
    <div class="portal-topbar">
        <button class="btn btn-sm btn-outline-light" type="button" aria-label="Menu" aria-controls="portalSidebar"
                onclick="document.getElementById('portalSidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
        <span class="fw-semibold">Admin portal</span>
        @if($waitingProofs)<a href="{{ route('platform.payments.index') }}" class="badge bg-warning text-dark ms-auto text-decoration-none">{{ $waitingProofs }} to review</a>@endif
    </div>

    <main class="container-fluid px-3 px-md-4 py-4" style="max-width: 1400px;">
        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Close the phone menu when tapping outside it
    document.addEventListener('click', function (e) {
        var sidebar = document.getElementById('portalSidebar');
        if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('[aria-controls="portalSidebar"]')) {
            sidebar.classList.remove('open');
        }
    });
</script>
</body>
</html>
