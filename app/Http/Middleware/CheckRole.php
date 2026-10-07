<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Passes when the user has ANY of the listed roles, e.g. ->middleware('role:admin,super_admin').
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user || !$user->roles()->whereIn('name', $roles)->exists()) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
