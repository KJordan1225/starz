<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Tenancy middleware
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1) Aliases you can reference in route definitions
        $middleware->alias([
            // Pick ONE initializer you actually use and reference its alias in routes:
            'tenant.by_path'      => InitializeTenancyByPath::class,      // e.g. /{tenant}/...
            'tenant.by_subdomain' => InitializeTenancyBySubdomain::class, // e.g. {tenant}.yourapp.com
            'tenant.by_domain'    => InitializeTenancyByDomain::class,    // e.g. foo.com maps to a tenant

            // Guards to keep traffic where it belongs
            'prevent.central' => PreventAccessFromCentralDomains::class,  // blocks tenant routes on central domains
            'prevent.tenant'  => PreventAccessFromTenantDomains::class,   // blocks central routes on tenant domains

            // Optional: your own “defaults” middleware (guards, cache prefix, etc.)
            'tenant.defaults' => TenantDefaults::class,
        ]);

        // 2) A handy tenant route group you can reuse
        //    If you use PATH-based tenants, keep 'tenant.by_path' here.
        //    If you use SUBDOMAIN or DOMAIN, swap accordingly.
        $middleware->group('tenant.web', [
            'web',
            'tenant.by_path',     // <— swap to 'tenant.by_subdomain' or 'tenant.by_domain' if that’s your mode
            'prevent.central',
            'tenant.defaults',    // optional
        ]);

        // 3) A central (landlord) group that refuses tenant domains (optional but recommended)
        $middleware->group('central.web', [
            'web',
            'prevent.tenant',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
