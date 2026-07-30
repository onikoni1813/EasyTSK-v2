<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralContest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'min_unlocked_required',
        'prizes',
        'status',
        'distributed_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'distributed_at' => 'datetime',
        'min_unlocked_required' => 'integer',
        'prizes' => 'array',
    ];

    public function winners()
    {
        return $this->hasMany(ReferralContestWinner::class, 'contest_id');
    }

    /**
     * Scope for currently active contests based on status and dates
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}
