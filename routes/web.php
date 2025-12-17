<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MicrositeController;
use App\Http\Controllers\TenantOrderController;
use App\Http\Controllers\VideoStreamController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StripeCheckoutController;
use App\Http\Controllers\TenantPlanAdminController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Tenant\TenantVideoController;
use App\Http\Controllers\TenantSubscriptionController;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PrivateTenantImagesController;
use App\Http\Controllers\TenantCarouselImageController;
use App\Http\Controllers\TenantStripeConnectController;
use App\Http\Controllers\Tenant\OnboardStripeController;
use App\Http\Controllers\TenantExclusiveImageController;
use App\Http\Controllers\Tenant\TenantCarouselController;
use App\Http\Controllers\TenantSubscribeReturnController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\StripeCreatorPlanController;;
use App\Http\Controllers\Tenant\StripeTenantSubscriptionController;


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

       

        // ****************************************************************
        // *  Create/UpdaTE sUBSCRIPTION pLAN PRICING ROUTES
        // *****************************************************************

        // Route::get('/plans', [TenantSubscriptionController::class, 'index'])
        //     ->name('tenant.plans.index');
        Route::get('/plans/{plan}/edit-price', [TenantSubscriptionController::class, 'editPrice'])
            ->name('tenant.plans.edit_price');
        Route::put('/plans/{plan}/update-price', [TenantSubscriptionController::class, 'updatePrice'])
            ->name('tenant.plans.update_price');

        // ****************************************************************
        // *  Tenant Subscription Plans creation routing
        // *****************************************************************

        
        Route::get('/subscribe/{plan}', [TenantSubscriptionController::class, 'subscribe'])
            ->name('tenant.plans.subscribeTo');

        // ****************************************************************
        // *  Tenant Stripe Onboarding routing
        // *****************************************************************

        // Admin: Connect onboarding entry
        Route::get('/admin/stripe/connect', [TenantStripeConnectController::class, 'index'])
            ->name('tenant.stripe.connect.index');

        // Admin: Start (or resume) onboarding -> redirects to Stripe
        Route::post('/admin/stripe/connect/start', [TenantStripeConnectController::class, 'start'])
            ->name('tenant.stripe.connect.start');

        // Admin: Stripe returns here after onboarding (return_url)
        Route::get('/admin/stripe/connect/return', [TenantStripeConnectController::class, 'return'])
            ->name('tenant.stripe.connect.return');

        // Admin: Stripe refreshes here if user abandons (refresh_url)
        Route::get('/admin/stripe/connect/refresh', [TenantStripeConnectController::class, 'refresh'])
            ->name('tenant.stripe.connect.refresh');

        // *******************************************************************************

        Route::prefix('admin')
            ->middleware(['auth']) // and your tenant-admin middleware if you have it
            ->group(function () {

                Route::get('/plans', [TenantPlanAdminController::class, 'index'])
                    ->name('tenant.admin.plans.index');

                Route::get('/plans/create', [TenantPlanAdminController::class, 'create'])
                    ->name('tenant.admin.plans.create');

                Route::post('/plans', [TenantPlanAdminController::class, 'store'])
                    ->name('tenant.admin.plans.store');

                Route::get('/plans/{plan}/edit', [TenantPlanAdminController::class, 'edit'])
                    ->name('tenant.admin.plans.edit');

                Route::put('/plans/{plan}', [TenantPlanAdminController::class, 'update'])
                    ->name('tenant.admin.plans.update');
            });


        // ****************************************************************
        // *  New Subscription Marketplace routing
        // *****************************************************************

        // Show available subscription plans for this tenant
        Route::get('/plans', [TenantSubscriptionController::class, 'index'])
            ->name('tenant.plans.index');

        // Start checkout for a plan subscription
        Route::post('/plans/{plan}/subscribe', [TenantSubscriptionController::class, 'start'])
            ->middleware('auth') // recommended: user must be logged in to subscribe
            ->name('tenant.plans.subscribe');

        // Return URLs from Stripe Checkout
        Route::get('/subscribe/success', [TenantSubscribeReturnController::class, 'success'])
            ->name('tenant.subscribe.success');

        Route::get('/subscribe/cancel', [TenantSubscribeReturnController::class, 'cancel'])
            ->name('tenant.subscribe.cancel');





            

        // Manage tenant exclusive images
        Route::get('/tenant-images', [TenantExclusiveImageController::class, 'index'])
            ->name('tenant.exclusive.images.index');

        Route::delete('/tenant-images/{media}', [TenantExclusiveImageController::class, 'destroy'])
            ->name('tenant.exclusive.images.destroy');

        // Manage tenant homepage carousel images
        Route::get('/carousel-images', [TenantCarouselImageController::class, 'index'])
            ->name('tenant.carousel.images.index');

        Route::delete('/carousel-images/{media}', [TenantCarouselImageController::class, 'destroy'])
            ->name('tenant.carousel.images.destroy');
        // END:  Manage tenant homepage carousel images

        // Route::get('/creator/stripe-plan', [StripeCreatorPlanController::class, 'edit'])
        //     ->name('edit.subscription-plan');

        // Route::post('/creator/stripe-plan', [StripeCreatorPlanController::class, 'save'])
        //     ->name('save.subscription-plan');

        // // Subscriptions
        // Route::get('/plans', [StripeTenantSubscriptionController::class, 'getList'])
        //     ->name('tenant.plans.index');

        // Route::post('/plans/{plan}/subscribe', [StripeTenantSubscriptionController::class, 'subscribe'])
        //     ->name('tenant.plans.subscribe');

        // Route::get('/orders/create', [TenantOrderController::class, 'create'])
        //     ->name('tenant.orders.create');

        // Route::post('/orders/checkout', [TenantOrderController::class, 'checkout'])
        //     ->name('tenant.orders.checkout');

        // Route::get('/orders/success', [TenantOrderController::class, 'success'])
        //     ->name('tenant.orders.success');

        // Route::get('/orders/cancel', [TenantOrderController::class, 'cancel'])
        //     ->name('tenant.orders.cancel');
        
        // Route::middleware(['auth'])->group(function () {
        //     Route::get('/checkout/order/{order}', [StripeCheckoutController::class, 'start'])
        //         ->name('stripe.checkout.start');

        //     Route::get('/checkout/order/{order}/success', [StripeCheckoutController::class, 'success'])
        //         ->name('stripe.checkout.success');

        //     Route::get('/checkout/order/{order}/cancel', [StripeCheckoutController::class, 'cancel'])
        //         ->name('stripe.checkout.cancel');
        // });

        // Route::middleware(['auth'])
        //     ->prefix('creator/stripe')
        //     ->group(function () {
        //         Route::get('/onboard', [StripeConnectController::class, 'start'])
        //             ->name('stripe.creator.onboarding.start');

        //         Route::get('/onboard/refresh', [StripeConnectController::class, 'refresh'])
        //             ->name('stripe.creator.onboarding.refresh');

        //         Route::get('/onboard/return', [StripeConnectController::class, 'return'])
        //             ->name('stripe.creator.onboarding.return');
        //     });

        //User Dashboard Route
        Route::get('/user/dashboard', function () {
            return view('tenant.user.home');
        })->name('tenant.user.home');
       
        // Video streaming route
        Route::get('/media/stream/{media}', [VideoStreamController::class, 'stream'])
            ->name('media.stream');
        
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
            ->middleware('guest')
            ->name('password.reset');

        Route::post('/reset-password', [NewPasswordController::class, 'store'])
            ->middleware('guest')
            ->name('password.store');

        // // NEW: Cancel at period end
        // Route::post(
        //     '/stripe/subscriptions/{subscription}/cancel-period-end',
        //     [StripeTenantSubscriptionController::class, 'cancelAtPeriodEnd']
        // )->name('tenant.stripe.subscriptions.cancel.period_end');
        // // NEW: Cancel immediately
        // Route::post(
        //     '/stripe/subscriptions/{subscription}/cancel-now',
        //     [StripeTenantSubscriptionController::class, 'cancelNow']
        // )->name('tenant.stripe.subscriptions.cancel.now');
        
        
        // // Stripe Connect onboarding
        // Route::get('/stripe/onboard', [OnboardStripeController::class, 'start'])
        //     ->name('tenant.stripe.onboard.start');
        // Route::get('/stripe/onboard/complete', [OnboardStripeController::class, 'complete'])
        //     ->name('tenant.stripe.onboard.complete');

        // Creator config for Stripe plan
        // Route::get('/creator/stripe-plan', [StripeCreatorPlanController::class, 'edit'])
        //     ->name('tenant.stripe.plan.edit');
        // Route::post('/creator/stripe-plan', [StripeCreatorPlanController::class, 'update'])
        //     ->name('tenant.stripe.plan.update');
        // // Subscription provider choice (Stripe vs PayPal)
        // Route::get('/subscriptions/choose', function () {
        //     $tenantId = tenant('id');
        //     $plan = \App\Models\SubscriptionPlan::where('tenant_id', $tenantId)->first();
        //     return view('tenant.subscriptions.choose', compact('plan'));
        // })->name('tenant.subscriptions.choose');
        // // Stripe-only subscription flow
        // Route::get('/stripe/subscriptions', [StripeTenantSubscriptionController::class, 'index'])
        //     ->name('tenant.stripe.subscriptions.index');
        // Route::post('/stripe/subscriptions/start', [StripeTenantSubscriptionController::class, 'start'])
        //     ->name('tenant.stripe.subscriptions.start');
        // Route::get('/stripe/subscriptions/success', [StripeTenantSubscriptionController::class, 'success'])
        //     ->name('tenant.stripe.subscriptions.success');
        // Route::get('/stripe/subscriptions/cancel', [StripeTenantSubscriptionController::class, 'cancel'])
        //     ->name('tenant.stripe.subscriptions.cancel');


        // Route::get('/subscriptions', [StripeTenantSubscriptionController::class, 'getList'])
        //     ->name('tenant.subscriptions.index');
        // Route::post('/subscriptions/start', [StripeTenantSubscriptionController::class, 'start'])
        //     ->name('tenant.subscriptions.start');
        // Route::get('/subscriptions/approve', [StripeTenantSubscriptionController::class, 'approve'])
        //     ->name('tenant.subscriptions.approve');
        // Route::get('/subscriptions/cancel', [StripeTenantSubscriptionController::class, 'cancelView'])
        //     ->name('tenant.subscriptions.cancel');
        // // NEW: cancel at period end
        // Route::post('/subscriptions/{subscription}/cancel-period-end', [StripeTenantSubscriptionController::class, 'cancelAtPeriodEnd'])
        //     ->name('tenant.subscriptions.cancel.period_end');
        // // NEW: cancel immediately
        // Route::post('/subscriptions/{subscription}/cancel-now', [StripeTenantSubscriptionController::class, 'cancelNow'])
        //     ->name('tenant.subscriptions.cancel.now');
            
        Route::get('/tenant/admin/home', function () {
            return view('tenant.admin.home');
        })->name('tenant.admin.home');

        Route::get('register', [RegisteredUserController::class, 'tenantCreate'])
            ->name('tenant.register');
        Route::post('register', [RegisteredUserController::class, 'tenantStore'])
            ->name('tenant.register.store');

        Route::get('/', [AuthenticatedSessionController::class, 'tenantCreate'])
            ->name('tenant.logn');
        Route::post('/', [AuthenticatedSessionController::class, 'tenantStore'])
            ->name('tenant.login.store');

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
            // ->middleware('subscribed.to.tenant')
            ->name('tenant.creator.images.creatorImagePageTwo');

        Route::get('/creator/video', [TenantCarouselController::class, 'videoEdit'])
            ->name('tenant.creator.video.edit');
        Route::post('/creator/video', [TenantCarouselController::class, 'videoStore'])
            ->name('tenant.creator.video.store');
        Route::delete('/creator/video', [TenantCarouselController::class, 'videoClear'])
            ->name('tenant.creator.video.clear');

        // Display Creator Video
        // Route::get('/creator/videos/show', [TenantVideoController::class, 'creatorVideoPage'])
            // ->name('tenant.tenant.video.show');
        Route::get('videos/stream/{media}', [TenantVideoController::class, 'stream'])
            // ->middleware('subscribed.to.tenant')
            ->name('tenant.videos.stream');
        Route::get('/videos/display', [TenantVideoController::class, 'creatorVideoPage'])
            ->middleware('subscribed.to.tenant')
            ->name('tenant.videos.display');


    });

    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
        ->name('stripe.webhook');

require __DIR__.'/auth.php';
