<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Other boot logic...

        Blade::if('subscribedTo', function ($tenant) {
            $user = auth()->user();

            if (! $user || ! $tenant) {
                return false;
            }

            return $user->hasActiveSubscriptionForTenant($tenant);
        });
    }
}