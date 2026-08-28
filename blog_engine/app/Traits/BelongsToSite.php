<?php

namespace App\Traits;

use App\Models\Scopes\SiteScope;
use App\Models\Site;
use App\Services\SiteContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSite
{
    protected static function bootBelongsToSite(): void
    {
        static::addGlobalScope(new SiteScope());

        static::creating(function ($model) {
            $siteContext = app(SiteContext::class);
            if (empty($model->site_id) && $siteContext->check()) {
                $model->site_id = $siteContext->id();
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
