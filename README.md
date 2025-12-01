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
- 🔐 **API Version Support** - Supports both Flutterwave API v3 and v4 (with OAuth 2.0)
- 🌐 **Live & Test Environments** - Seamless switching between live and test modes

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
FLUTTERWAVE_ENVIRONMENT=live
FLUTTERWAVE_API_VERSION=v3
FLUTTERWAVE_DEFAULT_CURRENCY=NGN
FLUTTERWAVE_DEFAULT_COUNTRY=NG
```

### API Version Support

This package supports both Flutterwave API v3 and v4:

- **v3 (Default)**: Uses Bearer token authentication with your secret key
- **v4**: Uses OAuth 2.0 authentication (automatically handles token retrieval and refresh)

Set `FLUTTERWAVE_API_VERSION=v3` or `FLUTTERWAVE_API_VERSION=v4` in your `.env` file.

**Note**: Some endpoints (like `/banks`) are only available in v3. The package defaults to v3 for maximum compatibility.

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

## Health Check

After installation, verify your setup with the health check command:

```bash
php artisan flutterwave:health-check
```

For detailed information:

```bash
php artisan flutterwave:health-check --verbose
```

This command checks:
- PHP version compatibility
- Required extensions (curl, json, openssl)
- Configuration (keys, environment, API version)
- API connectivity

## Security Best Practices

### 1. Credential Protection
- **Always use HTTPS** in production environments
- **Never expose secret keys** in client-side code or public repositories
- **Store credentials in environment variables** - never hardcode them
- **Use different keys** for test and production environments

### 2. Webhook Security
- **Enable IP whitelisting** for production webhooks:
  ```env
  FLUTTERWAVE_WEBHOOK_ALLOWED_IPS=52.31.139.75,52.49.173.169,52.214.14.220
  ```
- **Enable rate limiting** to prevent abuse:
  ```env
  FLUTTERWAVE_WEBHOOK_RATE_LIMIT=60
  ```
- **Enable timestamp validation** to prevent replay attacks:
  ```env
  FLUTTERWAVE_WEBHOOK_VALIDATE_TIMESTAMP=true
  ```
- **Set request size limits**:
  ```env
  FLUTTERWAVE_WEBHOOK_MAX_SIZE=1048576
  ```

### 3. Configuration Security
- **Validate base URLs** - The package automatically validates base URLs to prevent SSRF attacks
- **Use webhook middleware** - Always use `VerifyFlutterwaveWebhook` middleware on webhook routes
- **Disable request logging in production**:
  ```env
  FLUTTERWAVE_LOG_REQUESTS=false
  ```

### 4. Input Validation
- Transaction IDs are automatically validated to prevent injection attacks
- Endpoints are sanitized to prevent path traversal and SSRF attacks
- All user inputs should be validated before passing to the package

### 5. Error Handling
- Don't expose detailed error messages to end users
- Log errors securely without exposing sensitive data
- Use try-catch blocks around Flutterwave API calls

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

## Performance Tuning

### 1. Service Instance Caching
The package automatically caches service instances (PaymentService, TransferService, etc.) for better performance. No configuration needed.

### 2. OAuth Token Caching
OAuth tokens for API v4 are automatically cached and reused until expiration. The package handles token refresh automatically.

### 3. Connection Pooling
The HTTP client is optimized for connection reuse:
- DNS caching (1 hour)
- TCP keepalive enabled
- Connection pooling for better performance

### 4. Request Timeout Configuration
Adjust timeouts based on your needs:
```env
FLUTTERWAVE_TIMEOUT=30
```

### 5. Disable Logging in Production
For better performance, disable request logging in production:
```env
FLUTTERWAVE_LOG_REQUESTS=false
```

### 6. API Version Selection
- **v3**: Faster (no OAuth overhead), but some newer features may not be available
- **v4**: More features, but requires OAuth token (automatically handled)

## Troubleshooting

### Installation Issues

#### Health Check Fails
Run the health check command to diagnose issues:
```bash
php artisan flutterwave:health-check --verbose
```

#### Missing PHP Extensions
Ensure required extensions are installed:
```bash
# Check if extensions are loaded
php -m | grep -E "curl|json|openssl"
```

Install missing extensions:
```bash
# Ubuntu/Debian
sudo apt-get install php-curl php-json php-openssl

# macOS (Homebrew)
brew install php-curl
```

#### Configuration Not Loading
Clear configuration cache:
```bash
php artisan config:clear
php artisan config:cache
```

### API Connection Issues

#### "Secret key is required" Error
1. Check your `.env` file has the required keys:
   ```env
   FLUTTERWAVE_PUBLIC_KEY=your_public_key
   FLUTTERWAVE_SECRET_KEY=your_secret_key
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

#### OAuth Authentication Fails (v4 API)
- Verify your public and secret keys are correct
- Check that your keys are for the correct environment (test/live)
- The package automatically retries with exponential backoff

#### Connection Timeout
- Increase timeout in config:
  ```env
  FLUTTERWAVE_TIMEOUT=60
  ```
- Check your network connection and firewall settings
- Verify Flutterwave API is accessible from your server

### Webhook Issues

#### Routes Not Loading
If webhook routes are not loading:
1. Check your config:
   ```bash
   php artisan config:clear
   ```
2. Verify routes are enabled:
   ```env
   FLUTTERWAVE_ENABLE_ROUTES=true
   ```
3. Check route list:
   ```bash
   php artisan route:list | grep flutterwave
   ```

#### Webhook Signature Verification Fails
1. Verify webhook secret hash is configured:
   ```env
   FLUTTERWAVE_WEBHOOK_SECRET_HASH=your_webhook_secret
   ```
2. Check that the secret hash matches your Flutterwave dashboard
3. Ensure the middleware is applied to the webhook route

#### Rate Limiting Issues
If webhooks are being rate limited:
1. Check current rate limit setting:
   ```env
   FLUTTERWAVE_WEBHOOK_RATE_LIMIT=60
   ```
2. Increase if needed (be careful in production)
3. Check Laravel logs for rate limit messages

#### IP Whitelist Blocking Valid Requests
1. Verify Flutterwave IP addresses are whitelisted:
   ```env
   FLUTTERWAVE_WEBHOOK_ALLOWED_IPS=52.31.139.75,52.49.173.169
   ```
2. Check your server's actual IP (may be behind a proxy)
3. Temporarily disable IP whitelist for testing:
   ```env
   FLUTTERWAVE_WEBHOOK_ALLOWED_IPS=
   ```

### Service Provider Issues

#### Service Provider Not Found
1. Clear autoload cache:
   ```bash
   composer dump-autoload
   ```
2. Clear Laravel caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
3. Verify package is installed:
   ```bash
   composer show abramcatalyst/laravel-flutterwave
   ```

#### Facade Not Working
1. Ensure service provider is registered (auto-discovered in Laravel 5.5+)
2. Check alias is registered in `config/app.php` (if using older Laravel)
3. Clear config cache:
   ```bash
   php artisan config:clear
   ```

### Common Error Messages

#### "Invalid endpoint path"
- Endpoint contains invalid characters or path traversal attempts
- Ensure endpoints are properly formatted
- Check that transaction IDs are valid (alphanumeric, hyphens, underscores only)

#### "Base URL must use HTTPS protocol"
- Custom base URLs must use HTTPS
- Only Flutterwave domains are allowed

#### "Transaction ID exceeds maximum length"
- Transaction IDs are limited to 100 characters
- Verify your transaction IDs meet this requirement

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Repository

GitHub: https://github.com/abramcatalyst/laravel-flutterwave

