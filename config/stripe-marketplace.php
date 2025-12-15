<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Default currency & fee
    |---------------------------------------------------------------------------
    */

    'currency' => env('STRIPE_MARKETPLACE_CURRENCY', 'usd'),

    // Platform fee as a percent of subscription invoices (e.g. 20 = 20%)
    'platform_fee_percent' => (float) env('STRIPE_PLATFORM_FEE_PERCENT', 20),

    /*
    |---------------------------------------------------------------------------
    | Checkout URLs
    |---------------------------------------------------------------------------
    */

    'success_url' => env('STRIPE_CHECKOUT_SUCCESS_URL'),
    'cancel_url'  => env('STRIPE_CHECKOUT_CANCEL_URL'),
];