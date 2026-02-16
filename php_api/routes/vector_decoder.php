<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([ApiKeyMiddleware::class])->group(function () {
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task_id}', [TaskController::class, 'show']);
    Route::get('/tasks/{task_id}/result', [TaskController::class, 'result']);
    Route::get('/health', HealthController::class);
});
