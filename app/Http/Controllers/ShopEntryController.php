<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The shop link, e.g. https://pos.example.com/area25: selects that shop in this
 * browser and opens its login (or its dashboard if already logged in there).
 * Registered as the fallback route, so it never shadows a real page address.
 */
class ShopEntryController extends Controller
{
    public function __invoke(Request $request)
    {
        $slug = strtolower(trim($request->path(), '/'));
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $slug)) {
            abort(404);
        }

        $tenant = Tenant::where('slug', $slug)->first();
        if (!$tenant) {
            abort(404, 'Shop not found.');
        }

        // Switching to another shop: whoever was logged in here is logged out first
        if ((int) $request->session()->get('tenant_id') !== $tenant->id && Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $request->session()->put('tenant_id', $tenant->id);
        set_tenant($tenant);

        return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
    }
}
