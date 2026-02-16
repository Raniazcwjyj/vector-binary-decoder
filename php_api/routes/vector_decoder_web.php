<?php

use App\Http\Controllers\Web\BillingController;
use App\Http\Controllers\Web\UploadController;
use App\Http\Middleware\EnsurePaidAccessForUpload;
use Illuminate\Support\Facades\Route;

Route::prefix('vector')->group(function () {
    Route::get('/', [UploadController::class, 'index'])->name('vector-web.upload');
    Route::view('/kami', 'vector_decoder.kami')->name('vector-web.kami');
    Route::post('/upload', [UploadController::class, 'submit'])
        ->middleware([EnsurePaidAccessForUpload::class])
        ->name('vector-web.upload.submit');
    Route::get('/tasks/{task_id}', [UploadController::class, 'show'])->name('vector-web.tasks.show');
    Route::get('/tasks/{task_id}/status', [UploadController::class, 'status'])->name('vector-web.tasks.status');
    Route::get('/tasks/{task_id}/result', [UploadController::class, 'result'])->name('vector-web.tasks.result');

    Route::get('/billing', [BillingController::class, 'index'])->name('vector-web.billing.index');
    Route::post('/billing/redeem', [BillingController::class, 'redeem'])->name('vector-web.billing.redeem');
    Route::post('/billing/bind', [BillingController::class, 'bind'])->name('vector-web.billing.bind');
});
