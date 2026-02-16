<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversionTask extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'api_key_id',
        'billing_account_id',
        'status',
        'request_json',
        'result_path',
        'result_svg_size',
        'meta_json',
        'error_code',
        'error_message',
        'attempts',
        'billing_mode',
        'billing_credits_cost',
        'queued_at',
        'billed_at',
        'refunded_at',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'request_json' => 'array',
        'meta_json' => 'array',
        'result_svg_size' => 'integer',
        'attempts' => 'integer',
        'billing_credits_cost' => 'integer',
        'queued_at' => 'datetime',
        'billed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class, 'billing_account_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TaskEvent::class, 'task_id');
    }

    public function markRunning(int $attempt): void
    {
        $this->status = TaskStatus::RUNNING->value;
        $this->attempts = $attempt;
        $this->started_at = now();
        $this->save();
    }

    public function markSucceeded(string $resultPath, int $svgSize, array $meta): void
    {
        $this->status = TaskStatus::SUCCEEDED->value;
        $this->result_path = $resultPath;
        $this->result_svg_size = $svgSize;
        $this->meta_json = $meta;
        $this->error_code = null;
        $this->error_message = null;
        $this->finished_at = now();
        $this->save();
    }

    public function markFailed(string $errorCode, string $message): void
    {
        $this->status = TaskStatus::FAILED->value;
        $this->error_code = $errorCode;
        $this->error_message = $message;
        $this->finished_at = now();
        $this->save();
    }
}
