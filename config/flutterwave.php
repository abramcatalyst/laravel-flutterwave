<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flutterwave Client Key
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave client key from your dashboard.
    | Get it from: https://dashboard.flutterwave.com/settings/apis
    |
    */
    'client_key' => env('FLUTTERWAVE_CLIENT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave secret key from your dashboard.
    | Get it from: https://dashboard.flutterwave.com/settings/apis
    |
    */
    'secret_key' => env('FLUTTERWAVE_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Encryption Key
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave encryption key from your dashboard.
    | Get it from: https://dashboard.flutterwave.com/settings/apis
    |
    */
    'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Webhook Secret Hash
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave webhook secret hash for verifying webhook signatures.
    | Get it from: https://dashboard.flutterwave.com/settings/webhooks
    |
    */
    'webhook_secret_hash' => env('FLUTTERWAVE_WEBHOOK_SECRET_HASH', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set to 'live' for production or 'test' for testing.
    |
    */
    'environment' => env('FLUTTERWAVE_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Flutterwave API base URL. Automatically set based on environment.
    |
    */
    'base_url' => env('FLUTTERWAVE_BASE_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency code for transactions (e.g., NGN, USD, KES, GHS, ZAR).
    |
    */
    'default_currency' => env('FLUTTERWAVE_DEFAULT_CURRENCY', 'NGN'),

    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | Default country code for transactions (e.g., NG, US, KE, GH, ZA).
    |
    */
    'default_country' => env('FLUTTERWAVE_DEFAULT_COUNTRY', 'NG'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds.
    |
    */
    'timeout' => env('FLUTTERWAVE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Log Requests
    |--------------------------------------------------------------------------
    |
    | Whether to log all API requests and responses.
    |
    */
    'log_requests' => env('FLUTTERWAVE_LOG_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Routes
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register the webhook route.
    | Set to false if you want to register the route manually.
    |
    */
    'enable_routes' => env('FLUTTERWAVE_ENABLE_ROUTES', true),
];

