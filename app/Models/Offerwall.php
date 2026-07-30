<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offerwall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'iframe_url_pattern',
        'secret_key',
        'reward_ratio',
        'status',
        'is_api',
        'order',
        'param_user_id',
        'param_amount',
        'param_transaction_id',
        'param_status',
        'param_secret_key',
        'status_chargeback_value',
        'allowed_ips',
    ];

    protected $casts = [
        'reward_ratio' => 'decimal:2',
        'status' => 'boolean',
        'is_api' => 'boolean',
        'order' => 'integer',
    ];
}
