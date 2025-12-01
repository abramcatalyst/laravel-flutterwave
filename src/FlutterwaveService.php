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
     * The OAuth client instance (reused for performance).
     *
     * @var \GuzzleHttp\Client|null
     */
    protected $oauthClient;

    /**
     * Cached service instances.
     *
     * @var array
     */
    protected $serviceCache = [];

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
        // Optimized for connection reuse and HTTP/2 support
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
                CURLOPT_TCP_KEEPALIVE => 1, // Enable TCP keepalive
                CURLOPT_TCP_KEEPIDLE => 60, // Start keepalive after 60 seconds
                CURLOPT_TCP_KEEPINTVL => 10, // Keepalive interval
            ],
            'http_errors' => false, // We handle errors manually
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => true,
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

        // Validate base_url to prevent SSRF attacks
        if (!empty($config['base_url'])) {
            $this->validateBaseUrl($config['base_url']);
        }
    }

    /**
     * Validate base URL to prevent SSRF attacks.
     *
     * @param  string  $url
     * @return void
     * @throws \AbramCatalyst\Flutterwave\Exceptions\FlutterwaveException
     */
    protected function validateBaseUrl(string $url): void
    {
        // Only allow HTTPS URLs
        if (!preg_match('/^https:\/\//i', $url)) {
            throw new FlutterwaveException('Base URL must use HTTPS protocol');
        }

        // Whitelist allowed domains
        $allowedDomains = [
            'api.flutterwave.com',
            'api.flutterwave.dev',
        ];

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        // Check if host matches allowed domains
        $isAllowed = false;
        foreach ($allowedDomains as $allowedDomain) {
            if ($host === $allowedDomain || substr($host, -strlen('.' . $allowedDomain)) === '.' . $allowedDomain) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            throw new FlutterwaveException('Base URL must be from an allowed Flutterwave domain');
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
     * Get OAuth client instance (reused for performance).
     *
     * @return \GuzzleHttp\Client
     */
    protected function getOAuthClient(): Client
    {
        if ($this->oauthClient === null) {
            $this->oauthClient = new Client([
                'base_uri' => 'https://idp.flutterwave.com/',
                'timeout' => $this->config['timeout'] ?? 30,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);
        }

        return $this->oauthClient;
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

        $maxRetries = 3;
        $retryDelay = 1; // Start with 1 second

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $oauthClient = $this->getOAuthClient();

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

                $statusCode = $response->getStatusCode();
                if ($statusCode < 200 || $statusCode >= 300) {
                    throw new FlutterwaveException("OAuth request failed with status code: {$statusCode}");
                }

                $body = json_decode($response->getBody()->getContents(), true);

                // Validate token response structure
                if (!is_array($body)) {
                    throw new FlutterwaveException('Invalid OAuth token response format');
                }

                if (!isset($body['access_token']) || empty($body['access_token'])) {
                    $errorDescription = $body['error_description'] ?? $body['error'] ?? 'Unknown error';
                    throw new FlutterwaveException('Failed to obtain access token: ' . $errorDescription);
                }

                if (!isset($body['expires_in']) || !is_numeric($body['expires_in'])) {
                    throw new FlutterwaveException('Invalid token expiration time in OAuth response');
                }

                $this->accessToken = $body['access_token'];
                // Token expires in specified time, but we'll refresh 1 minute early
                $this->tokenExpiresAt = time() + (int)$body['expires_in'] - 60;

                return $this->accessToken;
            } catch (GuzzleException $e) {
                // If this is the last attempt, throw the exception
                if ($attempt === $maxRetries) {
                    throw new FlutterwaveException('Failed to authenticate with Flutterwave after ' . $maxRetries . ' attempts: ' . $e->getMessage(), 0, $e);
                }

                // Exponential backoff: wait before retrying
                sleep($retryDelay);
                $retryDelay *= 2; // Double the delay for next retry
            } catch (FlutterwaveException $e) {
                // Re-throw FlutterwaveException immediately (no retry for validation errors)
                throw $e;
            }
        }

        throw new FlutterwaveException('Failed to obtain access token after ' . $maxRetries . ' attempts');
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
            throw new FlutterwaveException('Invalid transaction ID format');
        }

        // Limit length to prevent buffer overflow attacks
        if (strlen($id) > 100) {
            throw new FlutterwaveException('Transaction ID exceeds maximum length');
        }

        return $id;
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
        
        // Prevent path traversal attempts (enhanced)
        if (str_contains($endpoint, '..') || 
            str_contains($endpoint, '//') || 
            str_contains($endpoint, '\\') ||
            str_contains($endpoint, '%2e%2e') || // URL encoded ..
            str_contains($endpoint, '%2f%2f')) { // URL encoded //
            throw new FlutterwaveException('Invalid endpoint path: path traversal detected');
        }

        // Ensure endpoint doesn't start with http:// or https:// (SSRF protection)
        if (preg_match('/^https?:\/\//i', $endpoint)) {
            throw new FlutterwaveException('Invalid endpoint: absolute URLs not allowed');
        }

        // Block other dangerous protocols
        if (preg_match('/^(file|ftp|gopher|ldap|data):/i', $endpoint)) {
            throw new FlutterwaveException('Invalid endpoint: dangerous protocol detected');
        }

        // Limit endpoint length
        if (strlen($endpoint) > 500) {
            throw new FlutterwaveException('Invalid endpoint: exceeds maximum length');
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
                if (str_contains($lowerKey, $sensitive)) {
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
            $isV4 = str_contains($this->baseUrl, '/v4/');
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
            $responseBody = $response->getBody()->getContents();
            $body = json_decode($responseBody, true);

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
                    // Read response body once and reuse
                    $errorBodyContent = $response->getBody()->getContents();
                    $errorBody = json_decode($errorBodyContent, true);
                    
                    if (is_array($errorBody)) {
                        if (isset($errorBody['message']) && !empty($errorBody['message'])) {
                            $message = $errorBody['message'];
                        } elseif (isset($errorBody['data']['message'])) {
                            $message = $errorBody['data']['message'];
                        }
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
            if (str_contains($errorMessage, 'cURL error') || 
                str_contains($errorMessage, 'Connection') ||
                str_contains($errorMessage, 'timeout')) {
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
        return $this->serviceCache['payment'] ??= new PaymentService($this);
    }

    /**
     * Get the transfer service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\TransferService
     */
    public function transfer(): TransferService
    {
        return $this->serviceCache['transfer'] ??= new TransferService($this);
    }

    /**
     * Get the subscription service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\SubscriptionService
     */
    public function subscription(): SubscriptionService
    {
        return $this->serviceCache['subscription'] ??= new SubscriptionService($this);
    }

    /**
     * Get the webhook service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\WebhookService
     */
    public function webhook(): WebhookService
    {
        return $this->serviceCache['webhook'] ??= new WebhookService($this);
    }

    /**
     * Get the account verification service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\AccountVerificationService
     */
    public function verification(): AccountVerificationService
    {
        return $this->serviceCache['verification'] ??= new AccountVerificationService($this);
    }

    /**
     * Get the virtual account service instance.
     *
     * @return \AbramCatalyst\Flutterwave\Services\VirtualAccountService
     */
    public function virtualAccount(): VirtualAccountService
    {
        return $this->serviceCache['virtualAccount'] ??= new VirtualAccountService($this);
    }
}

