<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default currency & fee
    |--------------------------------------------------------------------------
    */

    'currency' => env('STRIPE_MARKETPLACE_CURRENCY', 'usd'),

    // Platform fee as a percent of the total (e.g. 20 = 20%)
    'platform_fee_percent' => (float) env('STRIPE_PLATFORM_FEE_PERCENT', 20),

    /*
    |--------------------------------------------------------------------------
    | Checkout URLs
    |--------------------------------------------------------------------------
    |
    | Use for success/cancel routes. You can still override per-order.
    |
    */

    'success_url' => env('STRIPE_CHECKOUT_SUCCESS_URL'), // e.g. https://app.test/{tenant}/orders/success
    'cancel_url'  => env('STRIPE_CHECKOUT_CANCEL_URL'),  // e.g. https://app.test/{tenant}/orders/cancel
];
