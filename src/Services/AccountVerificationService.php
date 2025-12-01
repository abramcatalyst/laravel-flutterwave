<?php

namespace AbramCatalyst\Flutterwave\Services;

use AbramCatalyst\Flutterwave\FlutterwaveService;

class AccountVerificationService
{
    /**
     * The Flutterwave service instance.
     *
     * @var \AbramCatalyst\Flutterwave\FlutterwaveService
     */
    protected $flutterwave;

    /**
     * Create a new account verification service instance.
     *
     * @param  \AbramCatalyst\Flutterwave\FlutterwaveService  $flutterwave
     * @return void
     */
    public function __construct(FlutterwaveService $flutterwave)
    {
        $this->flutterwave = $flutterwave;
    }

    /**
     * Verify bank account.
     *
     * @param  array  $data
     * @return array
     */
    public function verifyBankAccount(array $data): array
    {
        return $this->flutterwave->post('accounts/resolve', $data);
    }

    /**
     * Verify BVN (Bank Verification Number).
     *
     * @param  array  $data
     * @return array
     */
    public function verifyBVN(array $data): array
    {
        return $this->flutterwave->post('kyc/bvn', $data);
    }

    /**
     * Verify card BIN.
     *
     * @param  string  $bin
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function verifyCardBin(string $bin): array
    {
        // Validate BIN format (should be 6 digits)
        if (!preg_match('/^\d{6}$/', $bin)) {
            throw new \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException('Invalid card BIN format. BIN must be exactly 6 digits.');
        }

        return $this->flutterwave->get("card-bins/{$bin}");
    }

    /**
     * Verify account number with bank code.
     *
     * @param  string  $accountNumber
     * @param  string  $bankCode
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function verifyAccountNumber(string $accountNumber, string $bankCode): array
    {
        // Validate account number (should be numeric)
        if (!preg_match('/^\d+$/', $accountNumber)) {
            throw new \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException('Invalid account number format.');
        }

        // Validate bank code (should be numeric)
        if (!preg_match('/^\d+$/', $bankCode)) {
            throw new \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException('Invalid bank code format.');
        }

        return $this->verifyBankAccount([
            'account_number' => $accountNumber,
            'account_bank' => $bankCode,
        ]);
    }

    /**
     * Get banks.
     *
     * @param  string|null  $country
     * @return array
     */
    public function getBanks(?string $country = null): array
    {
        $query = $country ? ['country' => $country] : [];
        return $this->flutterwave->get('banks', $query);
    }

    /**
     * Get bank branches.
     *
     * @param  string  $bankId
     * @return array
     */
    public function getBankBranches(string $bankId): array
    {
        return $this->flutterwave->get("banks/{$bankId}/branches");
    }
}

