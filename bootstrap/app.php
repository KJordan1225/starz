<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Tenancy middleware
use App\Http\Middleware\AttachTenantContext;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\SetTenantRouteDefaults;
use App\Http\Middleware\PreventSelfSubscription;
use App\Providers\TenantBrandingServiceProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Middleware\InitializeTenantFromPath;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // 'tenant'          => TenantMiddleware::class,
            'tenant.defaults' => TenantDefaults::class,
            'tenant' => InitializeTenantFromPath::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
