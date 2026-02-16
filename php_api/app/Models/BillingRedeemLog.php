<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRedeemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'billing_redeem_code_id',
        'code',
        'days',
        'credits',
        'meta_json',
    ];

    protected $casts = [
        'days' => 'integer',
        'credits' => 'integer',
        'meta_json' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class, 'billing_account_id');
    }

    public function redeemCode(): BelongsTo
    {
        return $this->belongsTo(BillingRedeemCode::class, 'billing_redeem_code_id');
    }
}

