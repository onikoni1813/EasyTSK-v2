<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCode extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_task_codes';

    protected $fillable = [
        'site_id',
        'post_id',
        'session_token',
        'code',
        'ip_hash',
        'dwell_time_seconds',
        'started_at',
        'generated_at',
        'is_used',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'generated_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'dwell_time_seconds' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
