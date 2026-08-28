<?php

namespace App\Models\Scopes;

use App\Services\SiteContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SiteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $siteContext = app(SiteContext::class);
        if ($siteContext->check()) {
            $builder->where($model->getTable() . '.site_id', $siteContext->id());
        }
    }
}
