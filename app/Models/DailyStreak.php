<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyStreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'streak_count',
        'tasks_completed_today',
        'last_completed_date',
    ];

    protected $casts = [
        'last_completed_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
