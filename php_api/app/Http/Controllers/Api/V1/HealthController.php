<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EngineClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(EngineClient $engineClient): JsonResponse
    {
        $mysql = $this->checkMysql();
        $redis = $this->checkRedis();
        $engine = $engineClient->health();

        $ok = $mysql['ok'] && $redis['ok'] && $engine['ok'];

        return response()->json([
            'ok' => $ok,
            'php' => ['ok' => true, 'version' => PHP_VERSION],
            'mysql' => $mysql,
            'redis' => $redis,
            'engine' => $engine,
        ], $ok ? 200 : 503);
    }

    private function checkMysql(): array
    {
        try {
            DB::select('SELECT 1');
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
