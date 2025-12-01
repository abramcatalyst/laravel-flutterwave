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
        $validatedId = $this->validateTransactionId($transactionId);
        return $this->flutterwave->get("transactions/{$validatedId}/verify");
    }

    /**
     * Get transaction details by transaction ID.
     *
     * @param  string|int  $transactionId
     * @return array
     */
    public function getTransaction($transactionId): array
    {
        $validatedId = $this->validateTransactionId($transactionId);
        return $this->flutterwave->get("transactions/{$validatedId}");
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
        $validatedId = $this->validateTransactionId($transactionId);
        return $this->flutterwave->post("transactions/{$validatedId}/resend-webhook");
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
        $validatedId = $this->validateTransactionId($refundId);
        return $this->flutterwave->get("refunds/{$validatedId}");
    }

    /**
     * Validate transaction ID to prevent injection attacks.
     *
     * @param  string|int  $transactionId
     * @return string
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    protected function validateTransactionId($transactionId): string
    {
        $id = (string) $transactionId;
        
        // Only allow alphanumeric characters, hyphens, and underscores
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException('Invalid transaction ID format');
        }

        // Limit length to prevent buffer overflow attacks
        if (strlen($id) > 100) {
            throw new \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException('Transaction ID exceeds maximum length');
        }

        return $id;
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

