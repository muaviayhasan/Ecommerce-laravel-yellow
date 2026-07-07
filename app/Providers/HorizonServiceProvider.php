<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Queue infrastructure is admin-only. super-admin also passes via the
        // global Gate::before bypass (CONVENTIONS §4.1); this keeps the rule
        // explicit and lets us widen it later without touching the bypass.
        Gate::define('viewHorizon', function ($user = null) {
            return $user !== null && $user->hasRole('super-admin');
        });
    }
}
