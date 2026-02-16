<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\TaskErrorCodes;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyValue = $request->header('X-API-Key');
        if (!$apiKeyValue) {
            return $this->unauthorized('Missing API key.');
        }

        $keyHash = hash('sha256', $apiKeyValue);
        $apiKey = ApiKey::query()->where('key_hash', $keyHash)->where('is_active', true)->first();

        if (!$apiKey) {
            return $this->unauthorized('Invalid API key.');
        }

        $ip = $request->ip() ?? 'unknown';
        $rateLimit = max(1, (int) $apiKey->rate_limit_per_min);
        $minuteBucket = now()->format('YmdHi');
        $cacheKey = "vdecode:ratelimit:key:{$apiKey->id}:ip:{$ip}:{$minuteBucket}";

        $count = Cache::increment($cacheKey);
        if ($count === 1) {
            Cache::put($cacheKey, 1, 70);
        }

        if ($count > $rateLimit) {
            return response()->json([
                'ok' => false,
                'error_code' => 'E_RATE_LIMIT',
                'message' => 'Rate limit exceeded.',
            ], 429);
        }

        $request->attributes->set('api_key_model', $apiKey);
        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error_code' => TaskErrorCodes::E_AUTH,
            'message' => $message,
        ], 401);
    }
}
