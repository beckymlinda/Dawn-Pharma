<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The admin portal: only on the admin host, only for platform admins */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!is_central()) {
            abort(404);
        }
        if (!$request->user() || !$request->user()->is_platform_admin) {
            abort(403);
        }

        return $next($request);
    }
}
