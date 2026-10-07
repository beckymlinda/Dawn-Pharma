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

        // Loss prevention: record wrong-password attempts on existing accounts (password is never stored)
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            if (!$event->user) {
                return;
            }
            $recent = \App\Models\IntegrityEvent::where('type', 'failed_login')
                ->where('user_id', $event->user->id)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->count();
            \App\Models\IntegrityEvent::record('failed_login', $recent >= 4 ? 'high' : 'low',
                "Wrong password entered for {$event->user->email}" . ($recent >= 4 ? ' (' . ($recent + 1) . ' attempts in 15 minutes)' : ''),
                ['user_id' => $event->user->id, 'register_id' => null]);
        });

        // Share settings globally (saved Settings with config/business.php defaults filled in)
        // (read when a view renders, so a change saved earlier in the request is shown;
        // pages that pass their own $settings, e.g. loss-prevention thresholds, keep theirs)
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (!array_key_exists('settings', $view->getData())) {
                $view->with('settings', business_settings());
            }
        });
    }
}
