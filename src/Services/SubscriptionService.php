<?php

namespace AbramCatalyst\Flutterwave\Services;

use AbramCatalyst\Flutterwave\FlutterwaveService;

class SubscriptionService
{
    /**
     * The Flutterwave service instance.
     *
     * @var \AbramCatalyst\Flutterwave\FlutterwaveService
     */
    protected $flutterwave;

    /**
     * Create a new subscription service instance.
     *
     * @param  \AbramCatalyst\Flutterwave\FlutterwaveService  $flutterwave
     * @return void
     */
    public function __construct(FlutterwaveService $flutterwave)
    {
        $this->flutterwave = $flutterwave;
    }

    /**
     * Create a subscription.
     *
     * @param  array  $data
     * @return array
     */
    public function create(array $data): array
    {
        return $this->flutterwave->post('subscriptions', $data);
    }

    /**
     * Get subscription details.
     *
     * @param  string|int  $subscriptionId
     * @return array
     */
    public function get($subscriptionId): array
    {
        return $this->flutterwave->get("subscriptions/{$subscriptionId}");
    }

    /**
     * List all subscriptions.
     *
     * @param  array  $filters
     * @return array
     */
    public function list(array $filters = []): array
    {
        return $this->flutterwave->get('subscriptions', $filters);
    }

    /**
     * Cancel a subscription.
     *
     * @param  string|int  $subscriptionId
     * @return array
     */
    public function cancel($subscriptionId): array
    {
        return $this->flutterwave->put("subscriptions/{$subscriptionId}/cancel");
    }

    /**
     * Activate a subscription.
     *
     * @param  string|int  $subscriptionId
     * @return array
     */
    public function activate($subscriptionId): array
    {
        return $this->flutterwave->put("subscriptions/{$subscriptionId}/activate");
    }
}

