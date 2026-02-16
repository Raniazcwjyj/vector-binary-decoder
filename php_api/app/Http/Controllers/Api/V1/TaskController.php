<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Jobs\CaptureConvertJob;
use App\Models\ApiKey;
use App\Models\ConversionTask;
use App\Services\TaskResultService;
use App\Support\TaskErrorCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key_model');

        $payload = [
            'url' => (string) $request->input('url'),
            'width' => (int) $request->input('width', 400),
            'height' => (int) $request->input('height', 400),
            'channel' => (string) $request->input('channel', 'auto'),
            'headless' => (bool) $request->input('headless', true),
            'max_wait_seconds' => (int) $request->input('max_wait_seconds', 240),
            'idle_seconds' => (int) $request->input('idle_seconds', 6),
        ];

        $task = ConversionTask::query()->create([
            'id' => (string) Str::uuid(),
            'api_key_id' => $apiKey->id,
            'status' => TaskStatus::QUEUED->value,
            'request_json' => $payload,
            'attempts' => 0,
            'queued_at' => now(),
        ]);

        CaptureConvertJob::dispatch($task->id)->onQueue(config('vector_decoder.queue_name', 'conversions'));

        return response()->json([
            'ok' => true,
            'task_id' => $task->id,
            'status' => $task->status,
        ], 202);
    }

    public function show(Request $request, string $task_id): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key_model');

        $task = ConversionTask::query()
            ->where('id', $task_id)
            ->where('api_key_id', $apiKey->id)
            ->first();

        if (!$task) {
            return response()->json([
                'ok' => false,
                'error_code' => TaskErrorCodes::E_PARAM,
                'message' => 'Task not found.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'task_id' => $task->id,
            'status' => $task->status,
            'progress' => $this->progressForStatus($task->status),
            'error_code' => $task->error_code,
            'error_message' => $task->error_message,
            'meta' => $task->meta_json,
            'attempts' => $task->attempts,
            'queued_at' => optional($task->queued_at)?->toISOString(),
            'started_at' => optional($task->started_at)?->toISOString(),
            'finished_at' => optional($task->finished_at)?->toISOString(),
        ]);
    }

    public function result(Request $request, string $task_id, TaskResultService $resultService)
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key_model');

        $task = ConversionTask::query()
            ->where('id', $task_id)
            ->where('api_key_id', $apiKey->id)
            ->first();

        if (!$task) {
            return response()->json([
                'ok' => false,
                'error_code' => TaskErrorCodes::E_PARAM,
                'message' => 'Task not found.',
            ], 404);
        }

        if ($task->status !== TaskStatus::SUCCEEDED->value) {
            return response()->json([
                'ok' => false,
                'error_code' => TaskErrorCodes::E_PARAM,
                'message' => 'Task result is not ready.',
                'status' => $task->status,
            ], 409);
        }

        $svg = $task->result_path ? $resultService->getSvg($task->result_path) : null;
        if ($svg === null) {
            return response()->json([
                'ok' => false,
                'error_code' => TaskErrorCodes::E_INTERNAL,
                'message' => 'Result file not found.',
            ], 500);
        }

        if ($request->query('format') === 'json') {
            return response()->json([
                'ok' => true,
                'task_id' => $task->id,
                'svg' => $svg,
                'meta' => $task->meta_json,
            ]);
        }

        return response($svg, 200, ['Content-Type' => 'image/svg+xml; charset=utf-8']);
    }

    private function progressForStatus(string $status): int
    {
        return match ($status) {
            TaskStatus::QUEUED->value => 5,
            TaskStatus::RUNNING->value => 60,
            TaskStatus::SUCCEEDED->value => 100,
            TaskStatus::FAILED->value => 100,
            default => 0,
        };
    }
}
