<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\URL;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        ResetPassword::createUrlUsing(function ($user, string $token) {
            // Try tenancy first, then URL segment/route
            $tenantKey = tenancy()->tenant?->id
                ?? request()->route('tenant')
                ?? request()->segment(1);

            $returnUrl = URL::route('password.reset', [
                'tenant' => $tenantKey,
                'token'  => $token,
                'email'  => $user->getEmailForPasswordReset(),
            ]);
            dd( $returnUrl);
            return $returnUrl;
        });
    }
}