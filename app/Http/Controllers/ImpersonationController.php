<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Log in as shop owner" for support: the platform admin's browser switches to
 * the shop, logged in as its owner. Recorded in the shop's activity log; a banner
 * shows on every page, and logging out returns to the admin portal login.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Tenant $tenant)
    {
        $owner = User::withoutTenantScope()->where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->withoutGlobalScope('tenant')->where('is_superadmin', true))
            ->orderBy('id')->first();

        if (!$owner) {
            return back()->with('error', 'This shop has no owner (super admin) account to log in as.');
        }

        $by = $request->user()->name . ' (' . $request->user()->email . ')';

        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put(['tenant_id' => $tenant->id, 'impersonated_by' => $by]);

        with_tenant($tenant, fn () => ActivityLog::create([
            'user_id' => $owner->id,
            'action' => 'support_login',
            'description' => "Support login as {$owner->name} by {$by}",
            'ip_address' => $request->ip(),
        ]));

        return redirect()->route('dashboard');
    }
}
