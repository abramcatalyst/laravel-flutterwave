<?php

namespace AbramCatalyst\Flutterwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AbramCatalyst\Flutterwave\Services\PaymentService payment()
 * @method static \AbramCatalyst\Flutterwave\Services\TransferService transfer()
 * @method static \AbramCatalyst\Flutterwave\Services\SubscriptionService subscription()
 * @method static \AbramCatalyst\Flutterwave\Services\WebhookService webhook()
 * @method static \AbramCatalyst\Flutterwave\Services\AccountVerificationService verification()
 * @method static \AbramCatalyst\Flutterwave\Services\VirtualAccountService virtualAccount()
 * @method static \GuzzleHttp\Client getClient()
 * @method static array getConfig()
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 *
 * @see \AbramCatalyst\Flutterwave\FlutterwaveService
 */
class Flutterwave extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'flutterwave';
    }
}

