<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingRedeemCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'code',
        'status',
        'days',
        'credits',
        'max_uses',
        'used_count',
        'expires_at',
        'last_redeemed_at',
        'created_by',
        'note',
    ];

    protected $casts = [
        'days' => 'integer',
        'credits' => 'integer',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
        'last_redeemed_at' => 'datetime',
    ];

    public function redeemLogs(): HasMany
    {
        return $this->hasMany(BillingRedeemLog::class);
    }
}

