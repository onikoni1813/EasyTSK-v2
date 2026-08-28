<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAnalytics extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_daily_analytics';

    protected $fillable = [
        'site_id',
        'date',
        'page_views',
        'unique_visitors',
    ];

    protected $casts = [
        'date' => 'date',
        'page_views' => 'integer',
        'unique_visitors' => 'integer',
    ];
}
