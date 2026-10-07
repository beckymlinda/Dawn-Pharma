<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Passes when the user has ANY of the listed permissions,
     * e.g. ->middleware('permission:view_pos_sell,add_pos_sell').
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        $user = auth()->user();
        
        if (!collect($permissions)->contains(fn ($permission) => $user->hasPermission($permission))) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
