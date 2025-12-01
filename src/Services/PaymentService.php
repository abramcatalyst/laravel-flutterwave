<?php

namespace AbramCatalyst\Flutterwave\Services;

use AbramCatalyst\Flutterwave\FlutterwaveService;

class PaymentService
{
    /**
     * The Flutterwave service instance.
     *
     * @var \AbramCatalyst\Flutterwave\FlutterwaveService
     */
    protected $flutterwave;

    /**
     * Create a new payment service instance.
     *
     * @param  \AbramCatalyst\Flutterwave\FlutterwaveService  $flutterwave
     * @return void
     */
    public function __construct(FlutterwaveService $flutterwave)
    {
        $this->flutterwave = $flutterwave;
    }

    /**
     * Initialize a payment transaction.
     *
     * @param  array  $data
     * @return array
     */
    public function initialize(array $data): array
    {
        return $this->flutterwave->post('payments', $data);
    }

    /**
     * Verify a payment transaction.
     *
     * @param  string|int  $transactionId
     * @return array
     */
    public function verify($transactionId): array
    {
        return $this->flutterwave->get("transactions/{$transactionId}/verify");
    }

    /**
     * Get transaction details by transaction ID.
     *
     * @param  string|int  $transactionId
     * @return array
     */
    public function getTransaction($transactionId): array
    {
        return $this->flutterwave->get("transactions/{$transactionId}");
    }

    /**
     * Get transaction details by transaction reference.
     *
     * @param  string  $reference
     * @return array
     */
    public function getTransactionByReference(string $reference): array
    {
        return $this->flutterwave->get("transactions", ['tx_ref' => $reference]);
    }

    /**
     * List all transactions.
     *
     * @param  array  $filters
     * @return array
     */
    public function listTransactions(array $filters = []): array
    {
        return $this->flutterwave->get('transactions', $filters);
    }

    /**
     * Get transaction fees.
     *
     * @param  array  $data
     * @return array
     */
    public function getTransactionFees(array $data): array
    {
        return $this->flutterwave->post('transactions/fee', $data);
    }

    /**
     * Resend transaction webhook.
     *
     * @param  string|int  $transactionId
     * @return array
     */
    public function resendWebhook($transactionId): array
    {
        return $this->flutterwave->post("transactions/{$transactionId}/resend-webhook");
    }

    /**
     * Refund a transaction.
     *
     * @param  array  $data
     * @return array
     */
    public function refund(array $data): array
    {
        return $this->flutterwave->post('transactions/refund', $data);
    }

    /**
     * Get refund details.
     *
     * @param  string|int  $refundId
     * @return array
     */
    public function getRefund($refundId): array
    {
        return $this->flutterwave->get("refunds/{$refundId}");
    }

    /**
     * List all refunds.
     *
     * @param  array  $filters
     * @return array
     */
    public function listRefunds(array $filters = []): array
    {
        return $this->flutterwave->get('refunds', $filters);
    }
}

