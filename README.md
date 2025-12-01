# Laravel Flutterwave Package

A comprehensive Flutterwave payment gateway package for Laravel 9.x, 10.x, 11.x, 12.x and above.

## Features

- 💳 **Payment Processing** - Initialize and verify payments
- 🔄 **Transfers** - Create and manage transfers
- 🔁 **Subscriptions** - Handle recurring payments
- 🔔 **Webhooks** - Secure webhook handling with signature verification
- ✅ **Account Verification** - Verify bank accounts, BVN, and cards
- 🏦 **Virtual Accounts** - Create and manage virtual accounts
- 📊 **Transaction Tracking** - Optional database models and migrations
- 🛡️ **Security** - Built-in webhook signature verification

## Installation

### Via Composer (Packagist)

```bash
composer require abramcatalyst/laravel-flutterwave
```

### Via GitHub (Development)

If you want to install directly from GitHub before publishing to Packagist:

1. Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/abramcatalyst/laravel-flutterwave"
        }
    ],
    "require": {
        "abramcatalyst/laravel-flutterwave": "dev-main"
    }
}
```

2. Then install:

```bash
composer require abramcatalyst/laravel-flutterwave:dev-main
```

### Local Development/Testing

For local testing, you can use a path repository:

1. Add to your Laravel app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-flutterwave"
        }
    ],
    "require": {
        "abramcatalyst/laravel-flutterwave": "*"
    }
}
```

2. Install:

```bash
composer require abramcatalyst/laravel-flutterwave:@dev
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=flutterwave-config
```

Add your Flutterwave credentials to your `.env` file:

```env
FLUTTERWAVE_PUBLIC_KEY=your_public_key
FLUTTERWAVE_SECRET_KEY=your_secret_key
FLUTTERWAVE_ENCRYPTION_KEY=your_encryption_key
FLUTTERWAVE_WEBHOOK_SECRET_HASH=your_webhook_secret_hash
FLUTTERWAVE_ENVIRONMENT=test
FLUTTERWAVE_DEFAULT_CURRENCY=NGN
FLUTTERWAVE_DEFAULT_COUNTRY=NG
```

## Optional: Database Models and Migrations

The core package is **API-only** and works without a database. If you want to store transaction records in your database, you'll need to:

1. **Install the database package** (if not already installed):
   ```bash
   composer require illuminate/database
   ```

2. **Publish the migrations and models**:
   ```bash
   # Publish migrations
   php artisan vendor:publish --tag=flutterwave-migrations

   # Publish models
   php artisan vendor:publish --tag=flutterwave-models

   # Run migrations
   php artisan migrate
   ```

> **Note:** The database package is optional. The core API functionality works perfectly without it.

## Usage

### Payments

#### Initialize a Payment

```php
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

$payment = Flutterwave::payment()->initialize([
    'tx_ref' => 'unique-transaction-reference',
    'amount' => 1000,
    'currency' => 'NGN',
    'payment_options' => 'card,banktransfer,ussd',
    'redirect_url' => 'https://your-site.com/callback',
    'customer' => [
        'email' => 'customer@example.com',
        'name' => 'John Doe',
        'phone_number' => '08012345678',
    ],
    'customizations' => [
        'title' => 'My Store',
        'description' => 'Payment for items',
    ],
]);

// Redirect to payment link
return redirect($payment['data']['link']);
```

#### Verify a Payment

```php
$transactionId = 1234567890;
$verification = Flutterwave::payment()->verify($transactionId);

if ($verification['status'] === 'success') {
    // Payment successful
}
```

#### Get Transaction Details

```php
// By transaction ID
$transaction = Flutterwave::payment()->getTransaction($transactionId);

// By transaction reference
$transaction = Flutterwave::payment()->getTransactionByReference('unique-ref');
```

#### List Transactions

```php
$transactions = Flutterwave::payment()->listTransactions([
    'from' => '2024-01-01',
    'to' => '2024-12-31',
    'page' => 1,
]);
```

#### Refund a Transaction

```php
$refund = Flutterwave::payment()->refund([
    'id' => $transactionId,
    'amount' => 500, // Optional: partial refund
]);
```

### Transfers

#### Create a Transfer

```php
$transfer = Flutterwave::transfer()->create([
    'account_bank' => '044',
    'account_number' => '0690000032',
    'amount' => 500,
    'narration' => 'Payment for services',
    'currency' => 'NGN',
    'reference' => 'unique-transfer-reference',
    'beneficiary_name' => 'John Doe',
]);
```

#### Create Bulk Transfer

```php
$bulkTransfer = Flutterwave::transfer()->createBulk([
    'title' => 'Bulk Transfer',
    'bulk_data' => [
        [
            'bank_code' => '044',
            'account_number' => '0690000032',
            'amount' => 500,
            'currency' => 'NGN',
            'narration' => 'Payment 1',
            'reference' => 'ref-1',
        ],
        [
            'bank_code' => '058',
            'account_number' => '1234567890',
            'amount' => 1000,
            'currency' => 'NGN',
            'narration' => 'Payment 2',
            'reference' => 'ref-2',
        ],
    ],
]);
```

#### Get Transfer Status

```php
$transfer = Flutterwave::transfer()->get($transferId);
```

### Subscriptions

#### Create a Subscription

```php
$subscription = Flutterwave::subscription()->create([
    'card_token' => 'card_token_here',
    'customer_email' => 'customer@example.com',
    'amount' => 1000,
    'currency' => 'NGN',
    'plan' => 'plan_id_here',
]);
```

#### List Subscriptions

```php
$subscriptions = Flutterwave::subscription()->list([
    'email' => 'customer@example.com',
]);
```

#### Cancel a Subscription

```php
$result = Flutterwave::subscription()->cancel($subscriptionId);
```

#### Activate a Subscription

```php
$result = Flutterwave::subscription()->activate($subscriptionId);
```

### Account Verification

#### Verify Bank Account

```php
$verification = Flutterwave::verification()->verifyBankAccount([
    'account_number' => '0690000032',
    'account_bank' => '044',
]);
```

#### Verify BVN

```php
$verification = Flutterwave::verification()->verifyBVN([
    'bvn' => '12345678901',
]);
```

#### Verify Card BIN

```php
$verification = Flutterwave::verification()->verifyCardBin('539983');
```

#### Get Banks

```php
// All banks
$banks = Flutterwave::verification()->getBanks();

// Banks by country
$banks = Flutterwave::verification()->getBanks('NG');
```

### Virtual Accounts

#### Create Virtual Account

```php
$virtualAccount = Flutterwave::virtualAccount()->create([
    'email' => 'customer@example.com',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'phonenumber' => '08012345678',
    'narration' => 'John Doe',
    'tx_ref' => 'unique-reference',
]);
```

#### List Virtual Accounts

```php
$accounts = Flutterwave::virtualAccount()->list();
```

#### Get Virtual Account Details

```php
$account = Flutterwave::virtualAccount()->get($accountId);
```

### Webhooks

The package includes a webhook route that automatically verifies the signature. The route is available at `/flutterwave/webhook`.

You can customize the webhook handler in `routes/web.php`:

```php
Route::post('/flutterwave/webhook', function () {
    $webhook = Flutterwave::webhook();
    $payload = $webhook->processPayment(request());

    if ($payload) {
        // Handle successful payment
        $transactionId = $payload['data']['id'];
        $amount = $payload['data']['amount'];
        $customerEmail = $payload['data']['customer']['email'];
        
        // Update your database, send notifications, etc.
        
        return response()->json(['status' => 'success'], 200);
    }

    return response()->json(['status' => 'failed'], 400);
})->middleware(\AbramCatalyst\Flutterwave\Middleware\VerifyFlutterwaveWebhook::class);
```

#### Manual Webhook Verification

```php
use Illuminate\Http\Request;

$webhook = Flutterwave::webhook();

// Verify signature
if ($webhook->verifySignature(request())) {
    // Process webhook
    $payload = $webhook->handle(request());
}
```

### Using the Transaction Model

If you've published the migrations, you can use the `FlutterwaveTransaction` model directly from the package:

```php
use AbramCatalyst\Flutterwave\Models\FlutterwaveTransaction;

// Create a transaction record
FlutterwaveTransaction::create([
    'transaction_id' => '1234567890',
    'transaction_ref' => 'unique-ref',
    'amount' => 1000,
    'currency' => 'NGN',
    'status' => 'successful',
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
]);

// Query transactions
$successful = FlutterwaveTransaction::successful()->get();
$failed = FlutterwaveTransaction::failed()->get();
$pending = FlutterwaveTransaction::pending()->get();

// Check status
$transaction = FlutterwaveTransaction::find(1);
if ($transaction->isSuccessful()) {
    // Handle successful transaction
}
```

## Direct Service Access

You can also access services directly without using the facade:

```php
use AbramCatalyst\Flutterwave\FlutterwaveService;

$flutterwave = app('flutterwave');
$payment = $flutterwave->payment()->initialize([...]);
```

## Error Handling

The package throws `FlutterwaveException` for API errors:

```php
use AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException;

try {
    $payment = Flutterwave::payment()->initialize([...]);
} catch (FlutterwaveException $e) {
    // Handle error
    logger()->error('Flutterwave error: ' . $e->getMessage());
}
```

## Logging

Enable request/response logging by setting in your `.env`:

```env
FLUTTERWAVE_LOG_REQUESTS=true
```

This will log all API requests and responses to your Laravel log file.

## Testing

For testing, set the environment to `test` in your `.env`:

```env
FLUTTERWAVE_ENVIRONMENT=test
```

Use Flutterwave test credentials from your dashboard.

## Security

- Always use HTTPS in production
- Never expose your secret keys in client-side code
- Use the webhook middleware to verify signatures
- Store sensitive credentials in environment variables

## Support

For Flutterwave API documentation, visit: https://developer.flutterwave.com/docs

## Testing Locally

After installing the package locally (using path repository), test the basic setup:

```php
// In your Laravel app, test in tinker or a controller
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

// Test service provider registration
$service = app('flutterwave');
dd($service->getConfig()); // Should show your config

// Test facade
$banks = Flutterwave::verification()->getBanks('NG');
dd($banks); // Should return banks list
```

## Troubleshooting

### Routes Not Loading

If webhook routes are not loading, check your config:

```bash
php artisan config:clear
```

Or set in your `.env`:
```env
FLUTTERWAVE_ENABLE_ROUTES=true
```

### Service Provider Not Found

Make sure the package is properly installed:
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Repository

GitHub: https://github.com/abramcatalyst/laravel-flutterwave

