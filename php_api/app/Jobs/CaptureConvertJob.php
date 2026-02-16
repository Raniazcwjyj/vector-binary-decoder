<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\ConversionTask;
use App\Models\TaskEvent;
use App\Services\BillingService;
use App\Services\EngineClient;
use App\Services\TaskResultService;
use App\Support\TaskErrorCodes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CaptureConvertJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(public string $taskId)
    {
    }

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(EngineClient $engineClient, TaskResultService $resultService, BillingService $billingService): void
    {
        /** @var ConversionTask|null $task */
        $task = ConversionTask::query()->find($this->taskId);
        if (!$task) {
            return;
        }
        if (in_array($task->status, [TaskStatus::SUCCEEDED->value, TaskStatus::FAILED->value], true)) {
            return;
        }

        $task->markRunning($this->attempts());
        $this->event($task->id, 'info', 'Task started.', ['attempt' => $this->attempts()]);

        $payload = $task->request_json ?? [];
        $payload['task_id'] = $task->id;

        $response = $this->runEngineWithFallback($engineClient, $task, $payload);
        if (!($response['ok'] ?? false)) {
            $code = (string) ($response['error_code'] ?? TaskErrorCodes::E_ENGINE);
            $message = (string) ($response['message'] ?? 'Engine call failed.');
            $retryable = in_array($code, [TaskErrorCodes::E_BROWSER_LAUNCH], true);
            if ($retryable && $this->attempts() < $this->tries) {
                $task->status = TaskStatus::QUEUED->value;
                $task->error_code = $code;
                $task->error_message = $message;
                $task->save();
                $this->event($task->id, 'warning', 'Retryable failure, re-queueing.', [
                    'error_code' => $code,
                    'message' => $message,
                    'attempt' => $this->attempts(),
                    'details' => $response['details'] ?? null,
                ]);
                throw new \RuntimeException("Retryable engine error: {$code} {$message}");
            }

            $task->markFailed($code, $message);
            $billingService->refundTaskOnFailure($task, "{$code}: {$message}");
            $this->cleanupUploadedFile($payload);
            $this->event($task->id, 'error', 'Task failed.', [
                'error_code' => $code,
                'message' => $message,
                'details' => $response['details'] ?? null,
            ]);
            return;
        }

        $svg = (string) ($response['svg'] ?? '');
        if ($svg === '') {
            $task->markFailed(TaskErrorCodes::E_INTERNAL, 'Engine returned empty SVG.');
            $billingService->refundTaskOnFailure($task, 'E_INTERNAL: Engine returned empty SVG.');
            $this->event($task->id, 'error', 'Task failed: empty SVG.', []);
            return;
        }

        $stored = $resultService->saveSvg($task->id, $svg);
        $meta = (array) ($response['meta'] ?? []);
        $meta['storage'] = [
            'disk' => $stored['disk'],
            'path' => $stored['path'],
        ];
        $task->markSucceeded($stored['path'], (int) $stored['size'], $meta);
        $this->cleanupUploadedFile($payload);

        $this->event($task->id, 'info', 'Task succeeded.', [
            'result_path' => $stored['path'],
            'size' => $stored['size'],
        ]);
    }

    private function runEngineWithFallback(EngineClient $engineClient, ConversionTask $task, array $payload): array
    {
        $response = $engineClient->captureConvert($payload);
        if (($response['ok'] ?? false) === true) {
            return $response;
        }

        $code = (string) ($response['error_code'] ?? '');
        $headless = (bool) ($payload['headless'] ?? true);
        if ($code !== TaskErrorCodes::E_TIMEOUT || $headless === false) {
            return $response;
        }

        $fallback = $payload;
        $fallback['headless'] = false;
        $fallback['max_wait_seconds'] = max((int) ($payload['max_wait_seconds'] ?? 240), 300);

        $this->event($task->id, 'warning', 'Timeout in headless mode, retrying once with headless=false.', [
            'error_code' => $code,
            'message' => $response['message'] ?? null,
            'details' => $response['details'] ?? null,
            'fallback_max_wait_seconds' => $fallback['max_wait_seconds'],
        ]);

        $fallbackResponse = $engineClient->captureConvert($fallback);
        if (($fallbackResponse['ok'] ?? false) !== true) {
            return $fallbackResponse;
        }

        $meta = (array) ($fallbackResponse['meta'] ?? []);
        $meta['fallback'] = [
            'trigger' => TaskErrorCodes::E_TIMEOUT,
            'headless' => false,
            'max_wait_seconds' => $fallback['max_wait_seconds'],
        ];
        $fallbackResponse['meta'] = $meta;

        $request = (array) ($task->request_json ?? []);
        $request['headless'] = false;
        $request['max_wait_seconds'] = $fallback['max_wait_seconds'];
        $task->request_json = $request;
        $task->save();

        return $fallbackResponse;
    }

    public function failed(Throwable $e): void
    {
        $task = ConversionTask::query()->find($this->taskId);
        if (!$task || $task->status === TaskStatus::FAILED->value) {
            return;
        }

        $task->markFailed(TaskErrorCodes::E_INTERNAL, $e->getMessage());
        app(BillingService::class)->refundTaskOnFailure($task, 'JOB_FAILED: ' . $e->getMessage());
        $this->cleanupUploadedFile($task->request_json ?? []);
        $this->event($task->id, 'error', 'Job failed after retries.', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);
    }

    private function event(string $taskId, string $level, string $message, array $context): void
    {
        TaskEvent::query()->create([
            'task_id' => $taskId,
            'level' => $level,
            'message' => $message,
            'context_json' => $context,
        ]);
    }

    private function cleanupUploadedFile(array $payload): void
    {
        $path = $payload['image_path'] ?? null;
        if (!is_string($path) || $path === '') {
            return;
        }

        $real = realpath($path);
        $storageRoot = realpath(storage_path('app'));
        if ($real === false || $storageRoot === false) {
            return;
        }
        if (!str_starts_with($real, $storageRoot . DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_file($real)) {
            @unlink($real);
        }
    }
}
