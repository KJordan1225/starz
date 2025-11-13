<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Tenancy middleware
use App\Http\Middleware\AttachTenantContext;
use App\Http\Middleware\SetTenantRouteDefaults;
use App\Http\Middleware\PreventSelfSubscription;
use App\Providers\TenantBrandingServiceProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // Initialize tenancy from a path parameter {tenant}
            'tenant'          => InitializeTenancyByPath::class,
            // Helper to auto-inject {tenant} into route() URLs while inside tenant pages
            'tenant.defaults' => SetTenantRouteDefaults::class,
            'universal'       => PreventAccessFromCentralDomains::class,
            'ctx.tenant'      => AttachTenantContext::class,
            'noselfsub'       => PreventSelfSubscription::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
