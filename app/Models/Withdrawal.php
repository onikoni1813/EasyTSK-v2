<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount_coins',
        'charge_coins',
        'amount_bdt',
        'currency',
        'currency_symbol',
        'payment_method',
        'account_details',
        'status',
        'admin_note',
        'transaction_id',
        'rejection_reason',
    ];

    protected $casts = [
        'amount_coins' => 'decimal:2',
        'charge_coins' => 'decimal:2',
        'amount_bdt' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
