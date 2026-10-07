<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Load custom helpers
require_once __DIR__ . '/../app/Helpers/RegisterHelper.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/auth.php'],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'log.activity' => \App\Http\Middleware\LogUserActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Expired CSRF token (419): instead of the dead-end "Page Expired" page, send the user
        // back to the form with what they typed, so they can simply submit again.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please try again.',
                    'csrf_token' => csrf_token(),
                ], 419);
            }
            if ($request->is('logout')) {
                return redirect()->route('login');
            }
            if ($request->is('login')) {
                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->with('status', 'The page had been open too long. Please sign in again.');
            }

            return redirect()->back()
                ->withInput($request->except(['_token', '_method', 'password', 'password_confirmation', 'current_password']))
                ->with('error', 'Your session had expired, so nothing was saved. Please check the form and submit it again.');
        });
    })->create();
