<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingAccount extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_code',
        'balance_credits',
        'total_spent_credits',
        'vip_expires_at',
    ];

    protected $casts = [
        'balance_credits' => 'integer',
        'total_spent_credits' => 'integer',
        'vip_expires_at' => 'datetime',
    ];

    public function ledgers(): HasMany
    {
        return $this->hasMany(BillingLedger::class);
    }

    public function redeemLogs(): HasMany
    {
        return $this->hasMany(BillingRedeemLog::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ConversionTask::class);
    }

    public function hasVipAccess(): bool
    {
        return $this->vip_expires_at !== null && $this->vip_expires_at->isFuture();
    }
}

