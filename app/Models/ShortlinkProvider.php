<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortlinkProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_url',
        'api_key',
        'daily_limit',
        'is_active',
        'icon',
    ];

    protected $casts = [
        'daily_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public const PRESETS = [
        'shrinkme' => [
            'name' => 'ShrinkMe.io',
            'api_url' => 'https://shrinkme.io/api',
            'icon' => '🔗',
        ],
        'exe' => [
            'name' => 'Exe.io',
            'api_url' => 'https://exe.io/api',
            'icon' => '⚡',
        ],
        'gplinks' => [
            'name' => 'GPLinks.in',
            'api_url' => 'https://gplinks.in/api',
            'icon' => '💎',
        ],
        'droplink' => [
            'name' => 'Droplink.co',
            'api_url' => 'https://droplink.co/api',
            'icon' => '💧',
        ],
        'clicksfly' => [
            'name' => 'Clicksfly.com',
            'api_url' => 'https://clicksfly.com/api',
            'icon' => '🚀',
        ],
        'custom' => [
            'name' => 'Custom Shortener',
            'api_url' => '',
            'icon' => '🌐',
        ],
    ];
}
