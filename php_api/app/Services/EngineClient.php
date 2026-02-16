<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;

class EngineClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function captureConvert(array $payload): array
    {
        $baseUrl = rtrim((string) config('vector_decoder.engine_url'), '/');
        $token = (string) config('vector_decoder.engine_internal_token');
        $maxWaitSeconds = (int) Arr::get($payload, 'max_wait_seconds', 120);
        $requestTimeout = max(190, min(600, $maxWaitSeconds + 90));

        try {
            $response = $this->http
                ->baseUrl($baseUrl)
                ->timeout($requestTimeout)
                ->acceptJson()
                ->withHeaders(['X-Internal-Token' => $token])
                ->post('/internal/v1/capture-convert', $payload)
                ->throw();
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'error_code' => 'E_ENGINE',
                'message' => 'Engine connection timeout/unreachable.',
                'details' => $e->getMessage(),
            ];
        } catch (RequestException $e) {
            $body = $e->response?->json();
            $status = $e->response?->status() ?? 500;
            $detail = Arr::get($body, 'detail');

            return [
                'ok' => false,
                'error_code' => Arr::get(
                    $body,
                    'error_code',
                    ($status >= 400 && $status < 500) ? 'E_PARAM' : 'E_ENGINE'
                ),
                'message' => Arr::get($body, 'message', $detail ?: 'Engine request failed.'),
                'details' => Arr::get($body, 'details', $detail),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error_code' => 'E_ENGINE',
                'message' => 'Unexpected engine client failure.',
                'details' => $e->getMessage(),
            ];
        }

        $json = $response->json();
        return [
            'ok' => true,
            'output_file' => Arr::get($json, 'output_file'),
            'svg' => Arr::get($json, 'svg'),
            'meta' => Arr::get($json, 'meta', []),
        ];
    }

    public function health(): array
    {
        $baseUrl = rtrim((string) config('vector_decoder.engine_url'), '/');
        $token = (string) config('vector_decoder.engine_internal_token');

        try {
            $response = $this->http
                ->baseUrl($baseUrl)
                ->timeout(5)
                ->acceptJson()
                ->withHeaders(['X-Internal-Token' => $token])
                ->get('/internal/v1/health')
                ->throw();
            return ['ok' => true, 'payload' => $response->json()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
