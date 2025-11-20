<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayPal Mode: "sandbox" or "live"
    |--------------------------------------------------------------------------
    */
    'mode' => env('PAYPAL_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Live Credentials
    |--------------------------------------------------------------------------
    */
    'live' => [
        'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'secret'    => env('PAYPAL_LIVE_SECRET', ''),
        'base_uri'  => 'https://api-m.paypal.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sandbox Credentials
    |--------------------------------------------------------------------------
    */
    'sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'secret'    => env('PAYPAL_SANDBOX_SECRET', ''),
        'base_uri'  => 'https://api-m.sandbox.paypal.com',
    ],

];
