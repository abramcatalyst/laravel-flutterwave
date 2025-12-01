# Quick Start Guide

## For Local Testing

### 1. In Your Laravel Application

Add to `composer.json`:

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

### 2. Install Package

```bash
composer require abramcatalyst/laravel-flutterwave:@dev
```

### 3. Publish Config

```bash
php artisan vendor:publish --tag=flutterwave-config
```

### 4. Add to .env

```env
FLUTTERWAVE_PUBLIC_KEY=your_test_public_key
FLUTTERWAVE_SECRET_KEY=your_test_secret_key
FLUTTERWAVE_ENVIRONMENT=test
```

### 5. Test

```bash
php artisan tinker
```

```php
use AbramCatalyst\Flutterwave\Facades\Flutterwave;

// Test 1: Check service
app('flutterwave');

// Test 2: Get banks
Flutterwave::verification()->getBanks('NG');
```

## For GitHub Installation

### Add Repository

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

### Install

```bash
composer require abramcatalyst/laravel-flutterwave:dev-main
```

## Troubleshooting

```bash
# Clear caches
composer dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

