<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortlinkSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'token',
        'status',
        'ip_address',
        'user_agent',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }
}
