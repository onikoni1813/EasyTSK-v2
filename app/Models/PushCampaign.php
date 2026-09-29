<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushCampaign extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'body',
        'target_url',
        'icon_url',
        'image_url',
        'audience_filter',
        'total_targeted',
        'total_sent',
        'total_failed',
        'status',
        'error_summary',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
