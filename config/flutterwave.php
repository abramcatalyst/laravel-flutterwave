<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flutterwave Public Key
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave public key from your dashboard.
    | Get it from: https://app.flutterwave.com/dashboard/settings/apis/live
    |
    */
    'public_key' => trim(env('FLUTTERWAVE_PUBLIC_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave secret key from your dashboard.
    | Get it from: https://dashboard.flutterwave.com/settings/apis
    |
    */
    'secret_key' => trim(env('FLUTTERWAVE_SECRET_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Encryption Key
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave encryption key from your dashboard.
    | Get it from: https://dashboard.flutterwave.com/settings/apis
    |
    */
    'encryption_key' => trim(env('FLUTTERWAVE_ENCRYPTION_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Webhook Secret Hash
    |--------------------------------------------------------------------------
    |
    | Your Flutterwave webhook secret hash for verifying webhook signatures.
    | Get it from: https://dashboard.flutterwave.com/settings/webhooks
    |
    */
    'webhook_secret_hash' => trim(env('FLUTTERWAVE_WEBHOOK_SECRET_HASH', '')),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set to 'live' for production or 'test' for testing.
    |
    */
    'environment' => env('FLUTTERWAVE_ENVIRONMENT', 'live'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Flutterwave API version to use (v3 or v4).
    | Note: v4 uses OAuth 2.0 authentication, v3 uses Bearer token.
    | Some endpoints may only be available in v3.
    |
    */
    'api_version' => env('FLUTTERWAVE_API_VERSION', 'v3'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Flutterwave API base URL. Automatically set based on environment and API version.
    | Set this to override the default base URL.
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

