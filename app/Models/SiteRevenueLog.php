<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteRevenueLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'publisher_account_id',
        'network',
        'log_date',
        'impressions',
        'clicks',
        'revenue_usd',
        'cpm_rate',
        'payment_status',
    ];

    protected $casts = [
        'log_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'revenue_usd' => 'decimal:4',
        'cpm_rate' => 'decimal:4',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function publisherAccount(): BelongsTo
    {
        return $this->belongsTo(PublisherAccount::class, 'publisher_account_id');
    }
}
