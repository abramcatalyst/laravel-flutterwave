<?php

namespace AbramCatalyst\Flutterwave\Services;

use AbramCatalyst\Flutterwave\FlutterwaveService;

class TransferService
{
    /**
     * The Flutterwave service instance.
     *
     * @var \AbramCatalyst\Flutterwave\FlutterwaveService
     */
    protected $flutterwave;

    /**
     * Create a new transfer service instance.
     *
     * @param  \AbramCatalyst\Flutterwave\FlutterwaveService  $flutterwave
     * @return void
     */
    public function __construct(FlutterwaveService $flutterwave)
    {
        $this->flutterwave = $flutterwave;
    }

    /**
     * Create a transfer.
     *
     * @param  array  $data
     * @return array
     */
    public function create(array $data): array
    {
        return $this->flutterwave->post('transfers', $data);
    }

    /**
     * Create a bulk transfer.
     *
     * @param  array  $data
     * @return array
     */
    public function createBulk(array $data): array
    {
        return $this->flutterwave->post('bulk-transfers', $data);
    }

    /**
     * Get transfer details.
     *
     * @param  string|int  $transferId
     * @return array
     */
    public function get($transferId): array
    {
        return $this->flutterwave->get("transfers/{$transferId}");
    }

    /**
     * List all transfers.
     *
     * @param  array  $filters
     * @return array
     */
    public function list(array $filters = []): array
    {
        return $this->flutterwave->get('transfers', $filters);
    }

    /**
     * Get transfer rates.
     *
     * @param  array  $data
     * @return array
     */
    public function getRates(array $data): array
    {
        return $this->flutterwave->get('transfers/rates', $data);
    }

    /**
     * Get transfer fees.
     *
     * @param  array  $data
     * @return array
     */
    public function getFees(array $data): array
    {
        return $this->flutterwave->get('transfers/fee', $data);
    }

    /**
     * Retry a failed transfer.
     *
     * @param  string|int  $transferId
     * @return array
     */
    public function retry($transferId): array
    {
        return $this->flutterwave->post("transfers/{$transferId}/retry");
    }

    /**
     * Get bulk transfer status.
     *
     * @param  string  $batchId
     * @return array
     */
    public function getBulkStatus(string $batchId): array
    {
        return $this->flutterwave->get("bulk-transfers/{$batchId}");
    }
}

