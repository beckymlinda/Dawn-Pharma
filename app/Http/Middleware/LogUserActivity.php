<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log if user is authenticated and it's not an AJAX request
        if (auth()->check() && !$request->ajax() && !$request->wantsJson()) {
            // Don't log GET requests to activity log page to avoid infinite loops
            if (!$request->is('activity-log*')) {
                $this->logActivity($request);
            }
        }

        return $response;
    }

    /**
     * Log user activity
     */
    private function logActivity(Request $request)
    {
        $route = $request->route();
        if (!$route) return;

        $routeName = $route->getName();
        $method = $request->method();

        // Define which routes to log and how
        $loggableRoutes = [
            // Products
            'products.store' => ['action' => 'created', 'description' => 'Created new product'],
            'products.update' => ['action' => 'updated', 'description' => 'Updated product'],
            'products.destroy' => ['action' => 'deleted', 'description' => 'Deleted product'],
            
            // Sales
            'pos.finalize' => ['action' => 'sold', 'description' => 'Completed sale'],
            'sales.store' => ['action' => 'created', 'description' => 'Created sale'],
            'sales.update' => ['action' => 'updated', 'description' => 'Updated sale'],
            
            // Purchases
            'purchases.store' => ['action' => 'purchased', 'description' => 'Created purchase'],
            'purchases.update' => ['action' => 'updated', 'description' => 'Updated purchase'],
            'products.purchase.store' => ['action' => 'purchased', 'description' => 'Added purchase through product'],
            
            // Stock Adjustments
            'stock-adjustments.store' => ['action' => 'adjusted', 'description' => 'Created stock adjustment'],
            
            // Users
            'admin.users.store' => ['action' => 'created', 'description' => 'Created new user'],
            'admin.users.update' => ['action' => 'updated', 'description' => 'Updated user'],
            'admin.users.delete' => ['action' => 'deleted', 'description' => 'Deleted user'],
            
            // Registers
            'register.store' => ['action' => 'opened', 'description' => 'Opened register'],
            'register.update' => ['action' => 'closed', 'description' => 'Closed register'],
        ];

        if (isset($loggableRoutes[$routeName])) {
            $config = $loggableRoutes[$routeName];
            
            // Try to get the subject model
            $subject = null;
            if ($route->hasParameter('product')) {
                $subject = $route->parameter('product');
            } elseif ($route->hasParameter('sale')) {
                $subject = $route->parameter('sale');
            } elseif ($route->hasParameter('purchase')) {
                $subject = $route->parameter('purchase');
            } elseif ($route->hasParameter('stockAdjustment')) {
                $subject = $route->parameter('stockAdjustment');
            } elseif ($route->hasParameter('user')) {
                $subject = $route->parameter('user');
            } elseif ($route->hasParameter('register')) {
                $subject = $route->parameter('register');
            }

            ActivityLog::logActivity(
                $config['action'],
                $config['description'],
                $subject
            );
        }
    }
}
