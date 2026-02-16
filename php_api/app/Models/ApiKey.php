<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key_hash',
        'is_active',
        'rate_limit_per_min',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate_limit_per_min' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(ConversionTask::class);
    }
}
