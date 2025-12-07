<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

class StripeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // Optional but recommended
        Stripe::setApiVersion('2024-04-10');
    }
}
