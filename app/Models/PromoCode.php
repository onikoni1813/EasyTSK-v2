<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'reward_points',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reward_points' => 'decimal:2',
            'is_active'     => 'boolean',
            'expires_at'    => 'datetime',
        ];
    }

    public function uses()
    {
        return $this->hasMany(PromoCodeUse::class);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->used_count >= $this->max_uses) return false;
        return true;
    }
}
