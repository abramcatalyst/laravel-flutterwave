<?php

namespace AbramCatalyst\Flutterwave\Services;

use AbramCatalyst\Flutterwave\FlutterwaveService;

class VirtualAccountService
{
    /**
     * The Flutterwave service instance.
     *
     * @var \AbramCatalyst\Flutterwave\FlutterwaveService
     */
    protected $flutterwave;

    /**
     * Create a new virtual account service instance.
     *
     * @param  \AbramCatalyst\Flutterwave\FlutterwaveService  $flutterwave
     * @return void
     */
    public function __construct(FlutterwaveService $flutterwave)
    {
        $this->flutterwave = $flutterwave;
    }

    /**
     * Create a virtual account.
     *
     * @param  array  $data
     * @return array
     */
    public function create(array $data): array
    {
        return $this->flutterwave->post('virtual-account-numbers', $data);
    }

    /**
     * Create bulk virtual accounts.
     *
     * @param  array  $data
     * @return array
     */
    public function createBulk(array $data): array
    {
        return $this->flutterwave->post('virtual-account-numbers/bulk', $data);
    }

    /**
     * Get virtual account details.
     *
     * @param  string|int  $accountId
     * @return array
     */
    public function get($accountId): array
    {
        return $this->flutterwave->get("virtual-account-numbers/{$accountId}");
    }

    /**
     * List all virtual accounts.
     *
     * @param  array  $filters
     * @return array
     */
    public function list(array $filters = []): array
    {
        return $this->flutterwave->get('virtual-account-numbers', $filters);
    }

    /**
     * Update virtual account.
     *
     * @param  string|int  $accountId
     * @param  array  $data
     * @return array
     */
    public function update($accountId, array $data): array
    {
        return $this->flutterwave->put("virtual-account-numbers/{$accountId}", $data);
    }

    /**
     * Delete virtual account.
     *
     * @param  string|int  $accountId
     * @return array
     */
    public function delete($accountId): array
    {
        return $this->flutterwave->delete("virtual-account-numbers/{$accountId}");
    }
}

