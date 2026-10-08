<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login for the admin portal (/platform/login). Only platform admins: accounts
 * that belong to no shop. Runs in the portal context, so shop users are not found.
 */
class PlatformAuthController extends Controller
{
    public function show()
    {
        if (Auth::check() && Auth::user()->is_platform_admin) {
            return redirect()->route('platform.index');
        }

        return view('platform.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'platform-login|' . Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.']);
        }

        if (!Auth::attempt($credentials + ['is_platform_admin' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget(['tenant_id', 'impersonated_by']);
        $request->session()->regenerate();

        return redirect()->route('platform.index');
    }
}
