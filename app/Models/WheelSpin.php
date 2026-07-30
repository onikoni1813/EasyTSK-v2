<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WheelSpin extends Model
{
    protected $fillable = [
        'user_id',
        'prize_label',
        'prize_value',
        'prize_type',
    ];

    protected function casts(): array
    {
        return [
            'prize_value' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
