<?php

use Illuminate\Support\Facades\Route;
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

/*
|--------------------------------------------------------------------------
| Flutterwave Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle Flutterwave webhook callbacks. Make sure to protect
| these routes with the VerifyFlutterwaveWebhook middleware.
|
| Note: Webhook routes are excluded from CSRF protection by default in Laravel
| when using the 'api' middleware group or when explicitly excluded.
|
*/

Route::post('/flutterwave/webhook', function () {
    $webhook = Flutterwave::webhook();
    $payload = $webhook->processPayment(request());

    if ($payload) {
        // Handle the webhook payload here
        // You can dispatch events, update database, etc.
        
        return response()->json(['status' => 'success'], 200);
    }

    return response()->json(['status' => 'failed'], 400);
})->middleware(\AbramCatalyst\Flutterwave\Middleware\VerifyFlutterwaveWebhook::class);

