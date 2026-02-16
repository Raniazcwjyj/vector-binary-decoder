<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'level',
        'message',
        'context_json',
    ];

    protected $casts = [
        'context_json' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ConversionTask::class, 'task_id');
    }
}
