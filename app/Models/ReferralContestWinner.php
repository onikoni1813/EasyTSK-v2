<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralContestWinner extends Model
{
    use HasFactory;

    protected $fillable = [
        'contest_id',
        'user_id',
        'rank',
        'unlocked_count',
        'reward_amount',
    ];

    protected $casts = [
        'rank' => 'integer',
        'unlocked_count' => 'integer',
        'reward_amount' => 'float',
    ];

    public function contest()
    {
        return $this->belongsTo(ReferralContest::class, 'contest_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
