<?php

namespace App\Services;

use App\Models\Site;

class SiteContext
{
    public static function current(): ?Site
    {
        return app()->has('current_site') ? app('current_site') : null;
    }

    public static function id(): ?int
    {
        $site = static::current();
        return $site ? $site->id : null;
    }

    public static function isExternal(): bool
    {
        return static::current() !== null;
    }
}
