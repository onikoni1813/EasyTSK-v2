<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferwallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'transaction_id',
        'amount',
        'status',
        'reason',
        'release_time',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'release_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
