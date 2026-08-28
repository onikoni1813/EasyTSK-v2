<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $table = 'be_sites';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'niche',
        'tagline',
        'description',
        'logo',
        'favicon',
        'theme_color',
        'theme_layout',
        'task_timer_seconds',
        'fixed_secret_code',
        'task_reward_enabled',
        'adblock_detection_enabled',
        'seo_defaults',
        'social_links',
        'header_scripts',
        'footer_scripts',
        'ads_txt',
        'is_active',
    ];

    protected $casts = [
        'seo_defaults' => 'array',
        'social_links' => 'array',
        'is_active' => 'boolean',
        'task_reward_enabled' => 'boolean',
        'adblock_detection_enabled' => 'boolean',
        'task_timer_seconds' => 'integer',
    ];

    public function taskCodes(): HasMany
    {
        return $this->hasMany(TaskCode::class, 'site_id');
    }

    public function rootFiles(): HasMany
    {
        return $this->hasMany(RootFile::class, 'site_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'site_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'site_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'site_id');
    }

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class, 'site_id');
    }

    public function adPlacements(): HasMany
    {
        return $this->hasMany(AdPlacement::class, 'site_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(SitePage::class, 'site_id');
    }

    public function dailyAnalytics(): HasMany
    {
        return $this->hasMany(DailyAnalytics::class, 'site_id');
    }

    public function getUrlAttribute(): string
    {
        if ($this->domain) {
            return 'https://' . $this->domain;
        }
        $appHost = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST);
        return 'http://' . $this->subdomain . '.' . $appHost;
    }
}
