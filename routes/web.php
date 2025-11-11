<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MicrositeController;
use App\Http\Controllers\TenantHomeController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('guest.home');
})->name('guest.home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//Create micro-sites
Route::get('/create-microsite', [MicrositeController::class, 'show'])
    ->name('landlord.create.microsite'); 
Route::post('/create-microsite', [MicrositeController::class, 'store'])
    ->name('landlord.store.microsite');

// ----- Tenant Routes-----
Route::prefix('{tenant}')
    ->middleware(['web', 'tenant', 'tenant.defaults'])
    ->group(function () {
        // Tenant-auth routes (prefixed names to avoid clashes with landlord)
        if (file_exists(__DIR__.'/tenant_auth.php')) {
            require __DIR__.'/tenant_auth.php';
        }         
        
        // Tenant-auth routes (prefixed names to avoid clashes with landlord)
        if (file_exists(__DIR__.'/tenant_auth.php')) {
            require __DIR__.'/tenant_auth.php';
        } 

        Route::get('/', [TenantHomeController::class, 'show'])
            ->name('tenant.home');        
        
        // Route::get('/tenant-admin/dashboard', [DashboardController::class, 'tenantAdminDashboard'])
        //     ->name('tenant.admin.dashboard');
        
        // Route::get('/', function () {
        //     return view('tenant.home');
        // })->name('tenant.home');
          
        
    });


require __DIR__.'/auth.php';
