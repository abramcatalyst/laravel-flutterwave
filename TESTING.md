# Testing Guide

This guide will help you test the package locally before publishing.

## Prerequisites

- Laravel 9.x, 10.x, 11.x, or 12.x application
- Flutterwave test credentials (get from https://dashboard.flutterwave.com)

## Step 1: Local Installation

### Option A: Path Repository (Recommended for Testing)

1. In your Laravel application's `composer.json`, add:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-flutterwave"
        }
    ]
}
```

2. Install the package:

```bash
composer require abramcatalyst/laravel-flutterwave:@dev
```

### Option B: Symlink

```bash
cd /path/to/your/laravel/app
composer config repositories.flutterwave path ../laravel-flutterwave
composer require abramcatalyst/laravel-flutterwave:@dev
```

## Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=flutterwave-config
```

## Step 3: Configure Environment

Add to your `.env` file:

```env
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_TEST-xxxxxxxxxxxxx
FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST-xxxxxxxxxxxxx
FLUTTERWAVE_ENCRYPTION_KEY=xxxxxxxxxxxxx
FLUTTERWAVE_WEBHOOK_SECRET_HASH=your_webhook_secret_hash
FLUTTERWAVE_ENVIRONMENT=test
FLUTTERWAVE_DEFAULT_CURRENCY=NGN
FLUTTERWAVE_DEFAULT_COUNTRY=NG
FLUTTERWAVE_LOG_REQUESTS=true
```

## Step 4: Test Basic Functionality

### Test 1: Service Provider Registration

```bash
php artisan tinker
```

```php
// Check if service is registered
app('flutterwave');

// Check config
config('flutterwave.public_key');

// Should return your public key
```

### Test 2: Facade Access

```php
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

// Test facade
Flutterwave::getConfig();

// Should return config array
```

### Test 3: API Call - Get Banks

```php
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

$banks = Flutterwave::verification()->getBanks('NG');

// Should return array of Nigerian banks
dd($banks);
```

### Test 4: Payment Initialization

```php
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

$payment = Flutterwave::payment()->initialize([
    'tx_ref' => 'test-' . time(),
    'amount' => 1000,
    'currency' => 'NGN',
    'payment_options' => 'card',
    'redirect_url' => 'https://your-site.com/callback',
    'customer' => [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'phone_number' => '08012345678',
    ],
    'customizations' => [
        'title' => 'Test Payment',
        'description' => 'Testing package',
    ],
]);

// Should return payment link
dd($payment);
```

### Test 5: Webhook Route

1. Check if route is registered:

```bash
php artisan route:list | grep flutterwave
```

2. Test webhook endpoint (you'll need to use a tool like Postman or ngrok):

```bash
# Using curl (will fail signature verification, but tests route)
curl -X POST http://your-app.test/flutterwave/webhook \
  -H "Content-Type: application/json" \
  -H "verif-hash: test" \
  -d '{"event":"test","data":{}}'
```

## Step 5: Test Optional Features

### Test Migrations (Optional)

```bash
php artisan vendor:publish --tag=flutterwave-migrations
php artisan migrate
```

### Test Model (Optional)

```bash
php artisan vendor:publish --tag=flutterwave-models
```

Then in tinker:

```php
use AbramCatalyst\Flutterwave\Models\FlutterwaveTransaction;

// Create test transaction
FlutterwaveTransaction::create([
    'transaction_id' => '12345',
    'transaction_ref' => 'test-ref',
    'amount' => 1000,
    'currency' => 'NGN',
    'status' => 'successful',
    'customer_email' => 'test@example.com',
]);

// Query
FlutterwaveTransaction::successful()->count();
```

## Common Issues

### Issue: "Class not found"

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: "Secret key is required"

**Solution:** Make sure your `.env` file has the required keys and run:
```bash
php artisan config:clear
```

### Issue: Routes not loading

**Solution:** Check if routes are enabled:
```env
FLUTTERWAVE_ENABLE_ROUTES=true
```

Then:
```bash
php artisan config:clear
php artisan route:clear
```

## Next Steps

Once all tests pass:

1. Commit your changes
2. Push to GitHub
3. Create a release tag
4. Submit to Packagist (optional)

## Test Checklist

- [ ] Package installs without errors
- [ ] Service provider registers correctly
- [ ] Configuration publishes successfully
- [ ] Facade is accessible
- [ ] API calls work (getBanks test)
- [ ] Payment initialization works
- [ ] Webhook route is accessible
- [ ] Migrations publish (if using)
- [ ] Model works (if using)
- [ ] No errors in Laravel logs

