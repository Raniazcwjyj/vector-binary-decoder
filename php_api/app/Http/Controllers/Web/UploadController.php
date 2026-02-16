<?php

namespace App\Http\Controllers\Web;

use App\Enums\TaskStatus;
use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Jobs\CaptureConvertJob;
use App\Models\ApiKey;
use App\Models\ConversionTask;
use App\Services\BillingService;
use App\Services\TaskResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function index(Request $request, BillingService $billingService)
    {
        $account = $billingService->ensureSessionAccount($request);

        return view('vector_decoder.upload', [
            'uploadMaxKb' => (int) config('vector_decoder.web_ui_upload_max_kb', 10240),
            'defaultMaxWaitSeconds' => (int) config('vector_decoder.web_ui_default_max_wait_seconds', 240),
            'defaultIdleSeconds' => (int) config('vector_decoder.web_ui_default_idle_seconds', 6),
            'billingEnabled' => $billingService->isEnabled(),
            'billingAccount' => $account,
            'billingCanUpload' => $billingService->canCreateTask($account),
            'creditCostPerTask' => $billingService->creditCostPerTask(),
        ]);
    }

    public function submit(Request $request, BillingService $billingService)
    {
        $maxKb = (int) config('vector_decoder.web_ui_upload_max_kb', 10240);
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:' . $maxKb],
            'width' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'height' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'max_wait_seconds' => ['nullable', 'integer', 'min:1', 'max:300'],
            'idle_seconds' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $file = $request->file('image');
        $storedPath = $file->store('vector-decoder/uploads', 'local');
        $absolutePath = Storage::disk('local')->path($storedPath);
        $defaultHeadless = (bool) config('vector_decoder.web_ui_default_headless', false);
        $defaultMaxWait = (int) config('vector_decoder.web_ui_default_max_wait_seconds', 240);
        $defaultIdle = (int) config('vector_decoder.web_ui_default_idle_seconds', 6);
        $account = $billingService->ensureSessionAccount($request);

        $apiKey = $this->resolveWebUiApiKey();
        $task = ConversionTask::query()->create([
            'id' => (string) Str::uuid(),
            'api_key_id' => $apiKey->id,
            'billing_account_id' => $account->id,
            'status' => TaskStatus::QUEUED->value,
            'request_json' => [
                'url' => null,
                'image_path' => $absolutePath,
                'upload_name' => (string) $file->getClientOriginalName(),
                'width' => (int) ($validated['width'] ?? 400),
                'height' => (int) ($validated['height'] ?? 400),
                'channel' => 'auto',
                'headless' => $defaultHeadless,
                'max_wait_seconds' => (int) ($validated['max_wait_seconds'] ?? $defaultMaxWait),
                'idle_seconds' => (int) ($validated['idle_seconds'] ?? $defaultIdle),
                'verbose' => true,
            ],
            'attempts' => 0,
            'queued_at' => now(),
        ]);

        try {
            $billingService->chargeForTask($account, $task);
        } catch (BillingException $e) {
            $this->cleanupUploadedFile($absolutePath);
            $task->delete();
            return redirect()
                ->route('vector-web.billing.index')
                ->with('billing_error', $e->getMessage());
        }

        CaptureConvertJob::dispatch($task->id)->onQueue(config('vector_decoder.queue_name', 'conversions'));
        $this->rememberTaskId($request, $task->id);

        return redirect()->route('vector-web.tasks.show', ['task_id' => $task->id]);
    }

    public function show(Request $request, string $task_id)
    {
        $task = $this->resolveTaskForSession($request, $task_id);
        return view('vector_decoder.task', ['task' => $task]);
    }

    public function status(Request $request, string $task_id): JsonResponse
    {
        $task = $this->resolveTaskForSession($request, $task_id);

        return response()->json([
            'ok' => true,
            'task_id' => $task->id,
            'status' => $task->status,
            'progress' => $this->progressForStatus($task->status),
            'error_code' => $task->error_code,
            'error_message' => $task->error_message,
            'meta' => $task->meta_json,
            'result_url' => $task->status === TaskStatus::SUCCEEDED->value
                ? route('vector-web.tasks.result', ['task_id' => $task->id], false)
                : null,
        ]);
    }

    public function result(Request $request, string $task_id, TaskResultService $resultService)
    {
        $task = $this->resolveTaskForSession($request, $task_id);
        if ($task->status !== TaskStatus::SUCCEEDED->value) {
            return response('Result not ready.', 409);
        }

        $candidatePaths = [];
        if (is_string($task->result_path) && $task->result_path !== '') {
            $candidatePaths[] = $task->result_path;
        }
        $defaultPath = trim((string) config('vector_decoder.results_prefix', 'vector-decoder/results'), '/') . "/{$task->id}/output.svg";
        if (!in_array($defaultPath, $candidatePaths, true)) {
            $candidatePaths[] = $defaultPath;
        }

        $svg = null;
        $resolvedPath = null;
        foreach ($candidatePaths as $path) {
            $content = $resultService->getSvg($path);
            if ($content !== null) {
                $svg = $content;
                $resolvedPath = $path;
                break;
            }
        }

        if ($svg !== null && $resolvedPath !== null && $task->result_path !== $resolvedPath) {
            $task->result_path = $resolvedPath;
            $task->result_svg_size = strlen($svg);
            $task->save();
        }

        if ($svg === null) {
            return response('Result file not found.', 404);
        }

        if ($request->query('download') === '1') {
            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$task->id}.svg\"",
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

    private function resolveWebUiApiKey(): ApiKey
    {
        $name = (string) config('vector_decoder.web_ui_api_key_name', 'web-ui');
        $model = ApiKey::query()->where('name', $name)->orderByDesc('id')->first();
        if ($model instanceof ApiKey) {
            if (!$model->is_active) {
                $model->is_active = true;
                $model->save();
            }
            return $model;
        }

        $plain = (string) Str::uuid() . Str::random(16);
        return ApiKey::query()->create([
            'name' => $name,
            'key_hash' => hash('sha256', $plain),
            'is_active' => true,
            'rate_limit_per_min' => 600,
        ]);
    }

    private function rememberTaskId(Request $request, string $taskId): void
    {
        $ids = $request->session()->get('vector_decoder_task_ids', []);
        $ids[] = $taskId;
        $ids = array_values(array_unique(array_slice($ids, -100)));
        $request->session()->put('vector_decoder_task_ids', $ids);
    }

    private function resolveTaskForSession(Request $request, string $taskId): ConversionTask
    {
        $task = ConversionTask::query()->find($taskId);
        if (!$task) {
            abort(404, 'Task not found.');
        }
        return $task;
    }

    private function cleanupUploadedFile(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
