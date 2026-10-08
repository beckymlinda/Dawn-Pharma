<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a shop that has been suspended or whose subscription has run out
 * (after the grace period). Login and logout keep working so staff see why;
 * a platform admin's support session ("log in as") is not blocked.
 */
class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        // Logged in, but this browser has no shop selected (e.g. a session from before
        // multi-shop): log out so the login page asks for the Shop code
        if (tenant_unresolved() && $request->user() && !$request->is('logout')) {
            \Illuminate\Support\Facades\Auth::logout();

            return redirect()->route('login');
        }

        // A login only ever works in its own shop (e.g. after switching shops in the same browser)
        if ($tenant && ($user = $request->user()) && (int) $user->tenant_id !== (int) $tenant->id) {
            \Illuminate\Support\Facades\Auth::logout();
            abort(403, 'This account does not belong to this shop.');
        }

        if (!$tenant || $tenant->canBeUsed() || $request->session()->has('impersonated_by')
            || $request->is('login', 'logout', 'session/token', 'up', 'sw.js', 'billing', 'billing/*')) {
            // (a blocked shop can still log in and open Billing to pay and upload proof)
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'error' => $this->message($tenant)], 402);
        }

        return response()->view('tenancy.blocked', ['tenant' => $tenant, 'message' => $this->message($tenant)], 402);
    }

    private function message($tenant): string
    {
        return $tenant->isSuspended()
            ? 'This shop has been suspended' . ($tenant->suspended_reason ? ': ' . $tenant->suspended_reason : '.')
            : 'The subscription for this shop ended on ' . $tenant->paid_until->format('d M Y') . '.';
    }
}
