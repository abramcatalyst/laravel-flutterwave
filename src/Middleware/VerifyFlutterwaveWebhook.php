<?php

namespace AbramCatalyst\Flutterwave\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class VerifyFlutterwaveWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate request size
        $maxSize = config('flutterwave.webhook_max_size', 1048576);
        $contentLength = $request->header('Content-Length');
        
        if ($contentLength && (int)$contentLength > $maxSize) {
            Log::warning('Flutterwave webhook request size exceeded', [
                'ip' => $request->ip(),
                'size' => $contentLength,
                'max_size' => $maxSize,
            ]);
            return response()->json(['error' => 'Request too large'], 413);
        }

        // Validate IP whitelist (if configured)
        if (!$this->validateWebhookIP($request)) {
            Log::warning('Flutterwave webhook IP not whitelisted', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Rate limiting
        $rateLimit = config('flutterwave.webhook_rate_limit', 60);
        if ($rateLimit > 0) {
            $key = 'flutterwave-webhook:' . $request->ip();
            
            if (RateLimiter::tooManyAttempts($key, $rateLimit)) {
                Log::warning('Flutterwave webhook rate limit exceeded', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Too many requests'], 429);
            }
            
            RateLimiter::hit($key, 60); // 60 seconds window
        }

        // Validate timestamp to prevent replay attacks
        if (config('flutterwave.webhook_validate_timestamp', true)) {
            if (!$this->validateTimestamp($request)) {
                Log::warning('Flutterwave webhook timestamp validation failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Request timestamp invalid'], 401);
            }
        }

        $secretHash = config('flutterwave.webhook_secret_hash');

        if (empty($secretHash)) {
            Log::warning('Flutterwave webhook secret hash not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $signature = $request->header('verif-hash');

        if (empty($signature)) {
            Log::warning('Flutterwave webhook signature missing', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!hash_equals($secretHash, $signature)) {
            // Don't log the actual hash values for security
            Log::warning('Flutterwave webhook signature verification failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    /**
     * Validate webhook IP address against whitelist.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function validateWebhookIP(Request $request): bool
    {
        $allowedIPs = config('flutterwave.webhook_allowed_ips', []);
        
        // If no whitelist configured, allow all (not recommended for production)
        if (empty($allowedIPs)) {
            return true;
        }

        $clientIP = $request->ip();

        foreach ($allowedIPs as $allowedIP) {
            $allowedIP = trim($allowedIP);
            
            // Check exact match
            if ($clientIP === $allowedIP) {
                return true;
            }

            // Check CIDR notation
            if (str_contains($allowedIP, '/')) {
                if ($this->ipInRange($clientIP, $allowedIP)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP address is within CIDR range.
     *
     * @param  string  $ip
     * @param  string  $range
     * @return bool
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return false;
        }

        [$subnet, $mask] = explode('/', $range);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Validate webhook timestamp to prevent replay attacks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function validateTimestamp(Request $request): bool
    {
        // Check if timestamp header exists
        $timestamp = $request->header('X-Flutterwave-Timestamp');
        
        if (empty($timestamp)) {
            // If no timestamp header, allow (for backward compatibility)
            // But log a warning
            Log::debug('Flutterwave webhook timestamp header missing');
            return true;
        }

        $requestTime = (int) $timestamp;
        $currentTime = time();
        $timeDifference = abs($currentTime - $requestTime);

        // Reject requests older than 5 minutes (300 seconds)
        if ($timeDifference > 300) {
            return false;
        }

        return true;
    }
}

