<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_type_id',
        'name',
        'slug',
        'subdomain',
        'primary_domain',
        'status',
        'theme',
        'default_language',
        'analytics_id',
        'meta_title',
        'meta_description',
    ];

    public function siteType(): BelongsTo
    {
        return $this->belongsTo(SiteType::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(SiteDomain::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(SiteSetting::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(SitePage::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SitePost::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(SiteCategory::class);
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'site_tools')
            ->withPivot('is_featured', 'custom_title')
            ->withTimestamps();
    }

    public function adPlacements(): HasMany
    {
        return $this->hasMany(SiteAdPlacement::class, 'site_id');
    }

    public function revenueLogs(): HasMany
    {
        return $this->hasMany(SiteRevenueLog::class, 'site_id');
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings->firstWhere('key', $key);
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public function setSetting(string $key, mixed $value, string $type = 'string'): void
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type]
        );
    }
}
