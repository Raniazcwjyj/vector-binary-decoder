<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'task_id',
        'type',
        'credits_delta',
        'balance_after',
        'note',
        'meta_json',
    ];

    protected $casts = [
        'credits_delta' => 'integer',
        'balance_after' => 'integer',
        'meta_json' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class, 'billing_account_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ConversionTask::class, 'task_id');
    }
}

