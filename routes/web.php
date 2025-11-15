<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MicrositeController;
use App\Http\Controllers\Tenant\TenantVideoController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PrivateTenantImagesController;
use App\Http\Controllers\Tenant\TenantCarouselController;

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

        // Route::get('login', function () {
        //     return view('tenant.login');
        // })->name('tenant.login');

        Route::get('register', [RegisteredUserController::class, 'tenantCreate'])
            ->name('tenant.register');
        Route::post('register', [RegisteredUserController::class, 'tenantStore'])
            ->name('tenant.register.store');

        // Microsite homepage with tenant-scoped carousel
        Route::get('/', [TenantCarouselController::class, 'homepage'])
            ->name('tenant.home');
        Route::get('/showSubscribe', [TenantCarouselController::class, 'showSubscribe'])
            ->name('tenant.show.subscribe');


        // Manage carousel (upload / clear)
        Route::get('/carousel', [TenantCarouselController::class, 'edit'])
            ->name('tenant.carousel.edit');
        Route::post('/carousel', [TenantCarouselController::class, 'store'])
            ->name('tenant.carousel.store');
        Route::delete('/carousel', [TenantCarouselController::class, 'clear'])
            ->name('tenant.carousel.clear');

        // Manage private creator images (upload / clear)
        Route::get('/creator/images', [PrivateTenantImagesController::class, 'creatorImageEdit'])
            ->name('tenant.creator.images.edit');
        Route::post('/creator/images', [PrivateTenantImagesController::class, 'creatorImageStore'])
            ->name('tenant.creator.images.store');
        Route::delete('/creator/images/clear', [PrivateTenantImagesController::class, 'creatorImageClear'])
            ->name('tenant.creator.image.clear');

        // Display all tenant images in rows of 4
        Route::get('/creator/images/display', [PrivateTenantImagesController::class, 'creatorImagePageTwo'])
            ->name('tenant.creator.images.creatorImagePageTwo');


        // Route::get('/carousel/video', [TenantCarouselController::class, 'videoEdit'])
        //     ->name('tenant.carousel.video.edit');
        // Route::post('/carousel/video', [TenantCarouselController::class, 'videoStore'])
        //     ->name('tenant.carousel.video.store');
        // Route::delete('/carousel/video', [TenantCarouselController::class, 'videoClear'])
        //     ->name('tenant.carousel.video.clear');

        // Display Creator Video
        // Route::get('/videos', [TenantVideoController::class, 'index'])
        //     ->name('tenant.videos.index');


    });


require __DIR__.'/auth.php';
