<?php

namespace AbramCatalyst\Flutterwave;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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
     * The OAuth access token (for v4 API).
     *
     * @var string|null
     */
    protected $accessToken;

    /**
     * The timestamp when the access token expires.
     *
     * @var int|null
     */
    protected $tokenExpiresAt;

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
        
        // Initialize client - authentication will be handled per request
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'curl' => [
                CURLOPT_DNS_CACHE_TIMEOUT => 3600, // Cache DNS for 1 hour
                CURLOPT_RESOLVE => [], // Will be set per request if needed
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

        // Check if API version is specified in config, default to v3 for compatibility
        $apiVersion = $this->config['api_version'] ?? 'v3';
        
        // Both live and test use the same base URL, environment affects authentication
        return "https://api.flutterwave.com/{$apiVersion}/";
    }

    /**
     * Get OAuth 2.0 access token for v4 API.
     *
     * @return string
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    protected function getAccessToken(): string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $oauthClient = new Client([
                'base_uri' => 'https://idp.flutterwave.com/',
                'timeout' => $this->config['timeout'] ?? 30,
            ]);

            $response = $oauthClient->post('realms/flutterwave/protocol/openid-connect/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'client_id' => trim($this->config['public_key']),
                    'client_secret' => trim($this->config['secret_key']),
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['access_token'])) {
                throw new FlutterwaveException('Failed to obtain access token: ' . ($body['error_description'] ?? 'Unknown error'));
            }

            $this->accessToken = $body['access_token'];
            // Token expires in 10 minutes, but we'll refresh 1 minute early
            $this->tokenExpiresAt = time() + ($body['expires_in'] ?? 600) - 60;

            return $this->accessToken;
        } catch (GuzzleException $e) {
            throw new FlutterwaveException('Failed to authenticate with Flutterwave: ' . $e->getMessage(), 0, $e);
        }
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

            // Get authentication token (OAuth 2.0 for v4, Bearer token for v3)
            $isV4 = strpos($this->baseUrl, '/v4/') !== false;
            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }
            
            if ($isV4) {
                $accessToken = $this->getAccessToken();
                $options['headers']['Authorization'] = 'Bearer ' . $accessToken;
            } else {
                $options['headers']['Authorization'] = 'Bearer ' . trim($this->config['secret_key']);
            }

            if ($this->config['log_requests'] ?? false) {
                // Sanitize sensitive data before logging
                $sanitizedOptions = $this->sanitizeLogData($options);
                
                // Log the full URL and auth header info (without exposing the full key)
                $fullUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
                $authHeader = $options['headers']['Authorization'] ?? 'Not set';
                $authKeyPreview = substr($authHeader, 0, 20) . '...' . substr($authHeader, -10);
                
                Log::info('Flutterwave API Request', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'full_url' => $fullUrl,
                    'auth_header_preview' => $authKeyPreview,
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
            // Log the full error for debugging
            if ($this->config['log_requests'] ?? false) {
                Log::error('Flutterwave API Error', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'endpoint' => $endpoint,
                ]);
            }
            
            // Only RequestException and its subclasses have responses
            // ConnectException (network/DNS issues) doesn't have a response
            if ($e instanceof RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $message = 'Flutterwave API request failed';
                
                try {
                    $body = json_decode($response->getBody()->getContents(), true);
                    if (isset($body['message']) && !empty($body['message'])) {
                        $message = $body['message'];
                    } elseif (isset($body['data']['message'])) {
                        $message = $body['data']['message'];
                    }
                } catch (\Exception $ex) {
                    // If we can't parse the response, use generic message
                }
                
                throw new FlutterwaveException($message, $statusCode, $e);
            }

            // For connection errors or other exceptions without response
            $errorMessage = $e->getMessage();
            $code = method_exists($e, 'getCode') ? $e->getCode() : 0;
            
            // Provide more helpful error messages for common connection issues
            if (strpos($errorMessage, 'cURL error') !== false || 
                strpos($errorMessage, 'Connection') !== false ||
                strpos($errorMessage, 'timeout') !== false) {
                $message = "Flutterwave API connection failed: {$errorMessage}. Please check your network connection and API endpoint.";
            } else {
                $message = "Flutterwave API request failed: {$errorMessage}";
            }
            
            throw new FlutterwaveException($message, $code, $e);
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

