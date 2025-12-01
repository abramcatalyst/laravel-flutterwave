<?php

namespace AbramCatalyst\Flutterwave\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
}

