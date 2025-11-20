<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MicrositeController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\Tenant\TenantVideoController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PrivateTenantImagesController;
use App\Http\Controllers\Tenant\TenantCarouselController;
use App\Http\Controllers\Tenant\StripeCreatorPlanController;
use App\Http\Controllers\Tenant\TenantSubscriptionController;
use App\Http\Controllers\Tenant\CreatorSubscriptionPlanController;
use App\Http\Controllers\Tenant\StripeTenantSubscriptionController;
use App\Http\Controllers\Tenant\OnboardStripeController;


if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
} 

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('guest.home');
})->name('guest.home');

Route::get('login', function () {
    return view('tenant.login');
})->name('login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//----- Route to SuperAdmin Dashboard
Route::get('/superAdmin/dashboard', [DashboardController::class, 'superAdminDashboard'])
    ->name('super-admin.dashboard');

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
        
        // Stripe Connect onboarding
        Route::get('/stripe/onboard', [OnboardStripeController::class, 'start'])
            ->name('tenant.stripe.onboard.start');
        Route::get('/stripe/onboard/complete', [OnboardStripeController::class, 'complete'])
            ->name('tenant.stripe.onboard.complete');


        // Creator config for Stripe plan
        Route::get('/creator/stripe-plan', [StripeCreatorPlanController::class, 'edit'])
            ->name('tenant.stripe.plan.edit');
        Route::post('/creator/stripe-plan', [StripeCreatorPlanController::class, 'update'])
            ->name('tenant.stripe.plan.update');
        // Subscription provider choice (Stripe vs PayPal)
        Route::get('/subscriptions/choose', function () {
            $tenantId = tenant('id');
            $plan = \App\Models\SubscriptionPlan::where('tenant_id', $tenantId)->first();
            return view('tenant.subscriptions.choose', compact('plan'));
        })->name('tenant.subscriptions.choose');
        // Stripe-only subscription flow
        Route::get('/stripe/subscriptions', [StripeTenantSubscriptionController::class, 'index'])
            ->name('tenant.stripe.subscriptions.index');
        Route::post('/stripe/subscriptions/start', [StripeTenantSubscriptionController::class, 'start'])
            ->name('tenant.stripe.subscriptions.start');
        Route::get('/stripe/subscriptions/success', [StripeTenantSubscriptionController::class, 'success'])
            ->name('tenant.stripe.subscriptions.success');
        Route::get('/stripe/subscriptions/cancel', [StripeTenantSubscriptionController::class, 'cancel'])
            ->name('tenant.stripe.subscriptions.cancel');


        Route::get('/subscriptions', [TenantSubscriptionController::class, 'index'])
            ->name('tenant.subscriptions.index');
        Route::post('/subscriptions/start', [TenantSubscriptionController::class, 'start'])
            ->name('tenant.subscriptions.start');
        Route::get('/subscriptions/approve', [TenantSubscriptionController::class, 'approve'])
            ->name('tenant.subscriptions.approve');
        Route::get('/subscriptions/cancel', [TenantSubscriptionController::class, 'cancelView'])
            ->name('tenant.subscriptions.cancel');
        // NEW: cancel at period end
        Route::post('/subscriptions/{subscription}/cancel-period-end', [TenantSubscriptionController::class, 'cancelAtPeriodEnd'])
            ->name('tenant.subscriptions.cancel.period_end');
        // NEW: cancel immediately
        Route::post('/subscriptions/{subscription}/cancel-now', [TenantSubscriptionController::class, 'cancelNow'])
            ->name('tenant.subscriptions.cancel.now');


         // Creator plan config
        Route::get('/creator/plan', [CreatorSubscriptionPlanController::class, 'edit'])
            ->name('tenant.creator.plan.edit');
        Route::post('/creator/plan', [CreatorSubscriptionPlanController::class, 'update'])
            ->name('tenant.creator.plan.update');

        // Subscriber subscription flow
        // Route::get('/subscriptions', [TenantSubscriptionController::class, 'index'])
        //     ->name('tenant.subscriptions.index');
        // Route::post('/subscriptions/start', [TenantSubscriptionController::class, 'start'])
        //     ->name('tenant.subscriptions.start');
        // Route::get('/subscriptions/approve', [TenantSubscriptionController::class, 'approve'])
        //     ->name('tenant.subscriptions.approve');
        // Route::get('/subscriptions/cancel', [TenantSubscriptionController::class, 'cancelView'])
        //     ->name('tenant.subscriptions.cancel');

        
        Route::get('/tenant/admin/home', function () {
            return view('tenant.admin.home');
        })->name('tenant.admin.home');

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


        Route::get('/creator/video', [TenantCarouselController::class, 'videoEdit'])
            ->name('tenant.creator.video.edit');
        Route::post('/creator/video', [TenantCarouselController::class, 'videoStore'])
            ->name('tenant.creator.video.store');
        Route::delete('/creator/video', [TenantCarouselController::class, 'videoClear'])
            ->name('tenant.creator.video.clear');

        // Display Creator Video
        Route::get('/creator/videos/show', [TenantVideoController::class, 'creatorVideoPage'])
            ->name('tenant.creator.video.show');


    });

// PayPal webhook (not tenant-prefixed)
Route::post('/paypal/webhook', [PayPalWebhookController::class, 'handle'])
    ->name('paypal.webhook');

require __DIR__.'/auth.php';
