<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'proof_requirements',
        'type',
        'provider_name',
        'target_url',
        'secret_code',
        'reward_coins',
        'reward_xp',
        'cooldown_hours',
        'status',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return '/storage/' . ltrim($this->image_path, '/');
        }
        return null;
    }

    protected $casts = [
        'reward_coins' => 'decimal:2',
        'reward_xp' => 'integer',
        'cooldown_hours' => 'integer',
        'proof_requirements' => 'array',
    ];

    public function userTasks()
    {
        return $this->hasMany(UserTask::class);
    }
}
