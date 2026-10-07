<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        // Register Gates for permissions
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasPermission($ability) ? true : null;
        });

        // Share settings globally
        try {
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            \Illuminate\Support\Facades\View::share('settings', $settings);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\View::share('settings', []);
        }
    }
}
