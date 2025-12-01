<?php

namespace AbramCatalyst\Flutterwave\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use AbramCatalyst\Flutterwave\FlutterwaveService;

class WebhookService
{
    /**
     * The Flutterwave service instance.
     *
     * @var \AbramCatalyst\Flutterwave\FlutterwaveService
     */
    protected $flutterwave;

    /**
     * Create a new webhook service instance.
     *
     * @param  \AbramCatalyst\Flutterwave\FlutterwaveService  $flutterwave
     * @return void
     */
    public function __construct(FlutterwaveService $flutterwave)
    {
        $this->flutterwave = $flutterwave;
    }

    /**
     * Verify webhook signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function verifySignature(Request $request): bool
    {
        $config = $this->flutterwave->getConfig();
        $secretHash = $config['webhook_secret_hash'] ?? '';

        if (empty($secretHash)) {
            Log::warning('Flutterwave webhook secret hash not configured');
            return false;
        }

        $signature = $request->header('verif-hash');

        if (empty($signature)) {
            return false;
        }

        return hash_equals($secretHash, $signature);
    }

    /**
     * Sanitize sensitive data from webhook payload for logging.
     *
     * @param  array  $data
     * @return array
     */
    protected function sanitizeWebhookData(array $data): array
    {
        $sensitiveKeys = ['secret', 'password', 'pin', 'cvv', 'card_number', 'account_number', 'bvn', 'token'];
        $sanitized = $data;

        foreach ($sanitized as $key => $value) {
            $lowerKey = strtolower($key);
            
            foreach ($sensitiveKeys as $sensitive) {
                if (strpos($lowerKey, $sensitive) !== false) {
                    $sanitized[$key] = '***REDACTED***';
                    break;
                }
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeWebhookData($value);
            }
        }

        return $sanitized;
    }

    /**
     * Handle webhook event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    public function handle(Request $request): ?array
    {
        if (!$this->verifySignature($request)) {
            Log::warning('Flutterwave webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            return null;
        }

        $payload = $request->all();

        // Sanitize sensitive data before logging
        $sanitizedPayload = $this->sanitizeWebhookData($payload);

        Log::info('Flutterwave webhook received', [
            'event' => $payload['event'] ?? 'unknown',
            'data' => $sanitizedPayload['data'] ?? [],
        ]);

        return $payload;
    }

    /**
     * Process payment webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    public function processPayment(Request $request): ?array
    {
        $payload = $this->handle($request);

        if (!$payload) {
            return null;
        }

        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        // Handle different payment events
        switch ($event) {
            case 'charge.completed':
            case 'charge.successful':
                return $this->handleSuccessfulPayment($data);
            case 'charge.failed':
                return $this->handleFailedPayment($data);
            default:
                Log::info("Unhandled Flutterwave webhook event: {$event}");
                return $payload;
        }
    }

    /**
     * Handle successful payment.
     *
     * @param  array  $data
     * @return array
     */
    protected function handleSuccessfulPayment(array $data): array
    {
        Log::info('Flutterwave payment successful', $data);
        return $data;
    }

    /**
     * Handle failed payment.
     *
     * @param  array  $data
     * @return array
     */
    protected function handleFailedPayment(array $data): array
    {
        Log::warning('Flutterwave payment failed', $data);
        return $data;
    }
}

