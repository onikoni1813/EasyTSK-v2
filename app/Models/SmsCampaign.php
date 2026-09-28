<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'filter_type',
        'message',
        'sender_id',
        'recipient_count',
        'sent_count',
        'failed_count',
        'cost_estimate',
        'status',
    ];

    protected $casts = [
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'cost_estimate' => 'decimal:2',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
