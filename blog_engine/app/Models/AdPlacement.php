<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdPlacement extends Model
{
    use HasFactory, BelongsToSite;

    protected $table = 'be_ad_placements';

    protected $fillable = [
        'site_id',
        'network',
        'placement_slot',
        'title',
        'ad_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Standard preset slots
    public const SLOTS = [
        'header' => 'Header Banner (728x90 / Responsive)',
        'before_content' => 'Before Article Content',
        'in_content_p2' => 'In-Content (After Paragraph 2)',
        'in_content_p5' => 'In-Content (After Paragraph 5)',
        'after_content' => 'After Article Content',
        'sidebar_top' => 'Sidebar Top',
        'sidebar_sticky' => 'Sidebar Sticky Bottom',
        'footer' => 'Footer Banner',
        'popunder' => 'Popunder / OnClick Script',
        'native_banner' => 'Native Widget Banner',
    ];

    // Standard popular networks
    public const NETWORKS = [
        'adsterra' => 'Adsterra',
        'monetag' => 'Monetag',
        'adsense' => 'Google AdSense',
        'propellerads' => 'PropellerAds',
        'popcash' => 'PopCash',
        'adcash' => 'AdCash',
        'ezoic' => 'Ezoic',
        'exoclick' => 'ExoClick',
        'yllix' => 'Yllix',
        'custom' => 'Custom / Other',
    ];
}
