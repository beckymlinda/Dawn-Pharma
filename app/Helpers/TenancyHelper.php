<?php

use App\Models\Tenant;

/*
 * The shop the current request belongs to. Set by App\Http\Middleware\IdentifyTenant:
 *   tenant()      the shop selected in this browser (shop link /{slug}), otherwise null
 *   is_central()  true in the admin portal (/platform)
 * A web request with no shop selected sees no shop data (set_no_tenant).
 * Command-line code (migrations, backups) has no context and sees all rows.
 */

if (!function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        return app()->bound('tenancy.tenant') ? app('tenancy.tenant') : null;
    }
}

if (!function_exists('is_central')) {
    function is_central(): bool
    {
        return app()->bound('tenancy.central') && app('tenancy.central') === true;
    }
}

if (!function_exists('set_tenant')) {
    /** Switch the current shop (null clears it); also drops per-shop caches */
    function set_tenant(?Tenant $tenant): void
    {
        app()->instance('tenancy.tenant', $tenant);
        app()->instance('tenancy.central', false);
        app()->instance('tenancy.unresolved', false);
        app()->forgetInstance('business.settings');
    }
}

if (!function_exists('set_central')) {
    function set_central(): void
    {
        app()->instance('tenancy.tenant', null);
        app()->instance('tenancy.central', true);
        app()->instance('tenancy.unresolved', false);
        app()->forgetInstance('business.settings');
    }
}

if (!function_exists('with_tenant')) {
    /**
     * Run code as a given shop, or as the admin portal when $tenant is null,
     * then put the previous context back.
     */
    function with_tenant(?Tenant $tenant, callable $callback)
    {
        $previous = tenant();
        $wasCentral = is_central();

        $tenant ? set_tenant($tenant) : set_central();
        try {
            return $callback();
        } finally {
            if ($previous) {
                set_tenant($previous);
            } elseif ($wasCentral) {
                set_central();
            } else {
                app()->forgetInstance('tenancy.tenant');
                app()->forgetInstance('tenancy.central');
                app()->forgetInstance('tenancy.unresolved');
                app()->forgetInstance('business.settings');
            }
        }
    }
}

if (!function_exists('set_no_tenant')) {
    /**
     * A web request for which no shop is selected yet: shop data is hidden
     * entirely until the shop is chosen (login page, shop link).
     */
    function set_no_tenant(): void
    {
        app()->instance('tenancy.tenant', null);
        app()->instance('tenancy.central', false);
        app()->instance('tenancy.unresolved', true);
        app()->forgetInstance('business.settings');
    }
}

if (!function_exists('tenant_unresolved')) {
    function tenant_unresolved(): bool
    {
        return app()->bound('tenancy.unresolved') && app('tenancy.unresolved') === true;
    }
}
