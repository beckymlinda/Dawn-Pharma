{{-- Settings header and tabs, shared by Settings, Notifications, Users and Roles --}}
@php
    $tabUser = auth()->user();
    $tabAdmin = $tabUser->hasRole('admin') || $tabUser->hasRole('super_admin');
    $tabs = array_filter([
        $tabAdmin ? ['settings.index', 'settings.index', 'bi-shop', 'Shop details'] : null,
        $tabAdmin ? ['settings.branding', 'settings.branding', 'bi-palette', 'Branding'] : null,
        $tabAdmin ? ['settings.payment-methods', 'settings.payment-methods', 'bi-credit-card', 'Payment methods'] : null,
        $tabAdmin ? ['notifications.index', 'notifications.*', 'bi-bell', 'Notifications'] : null,
        $tabUser->hasPermission('view_user') ? ['admin.users.index', 'admin.users.*', 'bi-people', 'Users'] : null,
        $tabUser->hasPermission('view_role') ? ['admin.roles.index', 'admin.roles.*', 'bi-shield-lock', 'Roles'] : null,
    ]);
@endphp
<div class="settings-head">
    <h4 class="fw-bold mb-1">Settings</h4>
    <p class="text-muted small mb-3">Your shop, how it looks, how customers pay, who gets reports and who can log in.</p>
    <nav class="settings-tabs" aria-label="Settings sections">
        @foreach($tabs as [$route, $pattern, $icon, $label])
            <a href="{{ route($route) }}" class="settings-tab {{ request()->routeIs($pattern) ? 'active' : '' }}"
               @if(request()->routeIs($pattern)) aria-current="page" @endif>
                <i class="bi {{ $icon }}"></i><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
</div>
<style>
    .settings-head { margin-bottom: 1.5rem; }
    .settings-tabs {
        display: flex; gap: .25rem; padding: .3rem; overflow-x: auto; scrollbar-width: none;
        background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }
    .settings-tabs::-webkit-scrollbar { display: none; }
    .settings-tab {
        display: inline-flex; align-items: center; gap: .45rem; white-space: nowrap;
        padding: .5rem .9rem; border-radius: 9px; font-size: .88rem; font-weight: 500;
        color: #475569; text-decoration: none; transition: background-color .15s, color .15s;
    }
    .settings-tab:hover { background: #f1f5f9; color: #0f172a; }
    .settings-tab.active { background: var(--theme-primary); color: #fff; font-weight: 600; }
    .settings-card { background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
    .settings-card-head { padding: 1.1rem 1.4rem .2rem; }
    .settings-card-head h6 { font-weight: 700; margin-bottom: .15rem; }
    .settings-card-body { padding: 1rem 1.4rem 1.4rem; }
    .settings-label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
</style>
