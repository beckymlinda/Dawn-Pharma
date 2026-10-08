<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Works out which shop a request is for:
 *   /platform...                  the admin portal (no shop)
 *   shop chosen in this browser   via the shop link /{slug} or the login form's Shop code
 *   otherwise                     the fallback shop, if one is configured
 * Runs after the session has started.
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Server setup works before the tenancy tables exist (token-protected)
        if ($request->is('setup', 'setup/*')) {
            return $next($request);
        }

        if ($request->is('platform', 'platform/*')) {
            set_central();

            return $next($request);
        }

        $tenant = null;
        if ($id = $request->session()->get('tenant_id')) {
            $tenant = Tenant::find($id);
        }
        if (!$tenant && ($slug = config('tenancy.fallback_slug'))) {
            $tenant = Tenant::where('slug', $slug)->first();
        }

        if ($tenant) {
            set_tenant($tenant);
        } else {
            // No shop selected yet: no shop data is visible; the login page asks for the Shop code
            set_no_tenant();
        }

        return $next($request);
    }
}
