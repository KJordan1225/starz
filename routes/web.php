<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MicrositeController;

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
//----- Microsite Creation Route -----
Route::get('/microsite/create', [MicrositeController::class, 'show'])
    ->name('landlord.microsite.create');
Route::post('/microsite/create', [MicrositeController::class, 'store'])
    ->name('landlord.microsite.store');


// ----- Tenant (PATH-based: /{tenant}/...) -----
Route::prefix('{tenant}')
    ->middleware(['web', 'tenant']) 
    ->group(function () {
        // Tenant-auth routes
        if (file_exists(__DIR__ . '/tenant_auth.php')) {
            require __DIR__ . '/tenant_auth.php';
        }

        Route::get('login', function () {
            return view('tenant.login');
        })->name('tenant.login');
    });


require __DIR__.'/auth.php';
