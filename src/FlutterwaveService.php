<?php

namespace AbramCatalyst\Flutterwave;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException;
use AbramCatalyst\Flutterwave\Services\PaymentService;
use AbramCatalyst\Flutterwave\Services\TransferService;
use AbramCatalyst\Flutterwave\Services\SubscriptionService;
use AbramCatalyst\Flutterwave\Services\WebhookService;
use AbramCatalyst\Flutterwave\Services\AccountVerificationService;
use AbramCatalyst\Flutterwave\Services\VirtualAccountService;

class FlutterwaveService
{
    /**
     * The HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * The base URL for the API.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Create a new Flutterwave service instance.
     *
     * @param  array  $config
     * @return void
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        
        $this->config = $config;
        $this->baseUrl = $this->getBaseUrl();
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $config['secret_key'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Validate configuration.
     *
     * @param  array  $config
     * @return void
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    protected function validateConfig(array $config): void
    {
        if (empty($config['secret_key'])) {
            throw new FlutterwaveException('Flutterwave secret key is required');
        }

        if (empty($config['public_key'])) {
            throw new FlutterwaveException('Flutterwave public key is required');
        }
    }

    /**
     * Get the base URL based on environment.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        if (!empty($this->config['base_url'])) {
            return $this->config['base_url'];
        }

        return $this->config['environment'] === 'live'
            ? 'https://api.flutterwave.com/v3/'
            : 'https://api.flutterwave.com/v3/';
    }

    /**
     * Make a GET request to the API.
     *
     * @param  string  $endpoint
     * @param  array  $query
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request to the API.
     *
     * @param  string  $endpoint
     * @param  array  $data
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PUT request to the API.
     *
     * @param  string  $endpoint
     * @param  array  $data
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request to the API.
     *
     * @param  string  $endpoint
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Sanitize endpoint to prevent path traversal and SSRF attacks.
     *
     * @param  string  $endpoint
     * @return string
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    protected function sanitizeEndpoint(string $endpoint): string
    {
        // Remove leading/trailing slashes and normalize
        $endpoint = trim($endpoint, '/');
        
        // Prevent path traversal attempts
        if (strpos($endpoint, '..') !== false || strpos($endpoint, '//') !== false) {
            throw new FlutterwaveException('Invalid endpoint path');
        }

        // Ensure endpoint doesn't start with http:// or https:// (SSRF protection)
        if (preg_match('/^https?:\/\//i', $endpoint)) {
            throw new FlutterwaveException('Invalid endpoint: absolute URLs not allowed');
        }

        return $endpoint;
    }

    /**
     * Sanitize sensitive data from logs.
     *
     * @param  array  $data
     * @return array
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['secret_key', 'secret', 'password', 'pin', 'cvv', 'card_number', 'account_number', 'bvn'];
        $sanitized = $data;

        foreach ($sanitized as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Check if key contains sensitive information
            foreach ($sensitiveKeys as $sensitive) {
                if (strpos($lowerKey, $sensitive) !== false) {
                    $sanitized[$key] = '***REDACTED***';
                    break;
                }
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeLogData($value);
            }
        }

        return $sanitized;
    }

    /**
     * Make an HTTP request to the API.
     *
     * @param  string  $method
     * @param  string  $endpoint
     * @param  array  $options
     * @return array
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            // Sanitize endpoint to prevent path traversal and SSRF
            $endpoint = $this->sanitizeEndpoint($endpoint);

            if ($this->config['log_requests'] ?? false) {
                // Sanitize sensitive data before logging
                $sanitizedOptions = $this->sanitizeLogData($options);
                
                Log::info('Flutterwave API Request', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'options' => $sanitizedOptions,
                ]);
            }

            $response = $this->client->request($method, $endpoint, $options);
            $body = json_decode($response->getBody()->getContents(), true);

            if ($this->config['log_requests'] ?? false) {
                // Sanitize response data before logging
                $sanitizedBody = $this->sanitizeLogData($body ?? []);
                
                Log::info('Flutterwave API Response', [
                    'status' => $response->getStatusCode(),
                    'body' => $sanitizedBody,
                ]);
            }

            if (isset($body['status']) && $body['status'] === 'error') {
                throw new FlutterwaveException(
                    $body['message'] ?? 'An error occurred',
                    $response->getStatusCode()
                );
            }

            return $body;
        } catch (GuzzleException $e) {
            // Don't expose sensitive error details
            $message = 'Flutterwave API request failed';
            
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                
                try {
                    $body = json_decode($response->getBody()->getContents(), true);
                    if (isset($body['message']) && !empty($body['message'])) {
                        $message = $body['message'];
                    }
                } catch (\Exception $ex) {
                    // If we can't parse the response, use generic message
                }
                
                throw new FlutterwaveException($message, $statusCode, $e);
            }

            throw new FlutterwaveException($message, $e->getCode(), $e);
        }
    }

    /**
     * Get the HTTP client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the payment service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\PaymentService
     */
    public function payment(): PaymentService
    {
        return new PaymentService($this);
    }

    /**
     * Get the transfer service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\TransferService
     */
    public function transfer(): TransferService
    {
        return new TransferService($this);
    }

    /**
     * Get the subscription service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\SubscriptionService
     */
    public function subscription(): SubscriptionService
    {
        return new SubscriptionService($this);
    }

    /**
     * Get the webhook service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\WebhookService
     */
    public function webhook(): WebhookService
    {
        return new WebhookService($this);
    }

    /**
     * Get the account verification service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\AccountVerificationService
     */
    public function verification(): AccountVerificationService
    {
        return new AccountVerificationService($this);
    }

    /**
     * Get the virtual account service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\VirtualAccountService
     */
    public function virtualAccount(): VirtualAccountService
    {
        return new VirtualAccountService($this);
    }
}

