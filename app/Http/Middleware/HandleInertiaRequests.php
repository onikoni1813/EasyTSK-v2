<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $parentProps = parent::share($request);

        $site = SiteContext::current();

        if ($site) {
            $adPlacements = $site->adPlacements()
                ->where('is_active', true)
                ->get()
                ->keyBy('placement_slot');

            return array_merge($parentProps, [
                'isExternal' => true,
                'admin_path' => config('app.admin_path', 'secret-panel'),
                'currentSite' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'slug' => $site->slug,
                    'primary_domain' => $site->primary_domain,
                    'theme' => $site->theme,
                    'logo' => $site->getSetting('logo_url'),
                    'logo_url' => $site->getSetting('logo_url'),
                    'primary_color' => $site->getSetting('primary_color', '#10B981'),
                    'header_nav' => $site->getSetting('header_nav', []),
                    'footer_links' => $site->getSetting('footer_links', []),
                    'ad_placements' => $adPlacements,
                ],
                'siteSettings' => null,
                'auth' => [
                    'user' => null,
                ],
            ]);
        }

        $user = $request->user();

        $siteSettings = Schema::hasTable('app_settings')
            ? AppSetting::getAllCached()
            : [];

        if (!empty($siteSettings)) {
            $siteSettings['is_maintenance_mode'] = ($siteSettings['maintenance_mode'] ?? 'false') === 'true';
            $siteSettings['logo'] = $siteSettings['site_logo'] ?? null;
            $siteSettings['favicon'] = $siteSettings['site_favicon'] ?? '/favicon.ico';
        }

        $flash = [
            'success' => fn () => $request->hasSession() ? $request->session()->get('success') : null,
            'error'   => fn () => $request->hasSession() ? $request->session()->get('error') : null,
        ];

        return array_merge($parentProps, [
            'isExternal' => false,
            'admin_path' => config('app.admin_path', 'secret-panel'),
            'currentSite' => null,
            'flash' => $flash,
            'impersonating' => [
                'is_active' => $request->hasSession() && $request->session()->has('impersonated_by_admin_id'),
                'admin_id'  => $request->hasSession() ? $request->session()->get('impersonated_by_admin_id') : null,
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'main_balance' => $user->main_balance,
                    'pending_balance' => $user->pending_balance,
                    'level' => $user->level,
                    'health' => $user->health ?? 100,
                    'payment_method' => $user->payment_method,
                    'payment_number' => $user->payment_number,
                    'has_recovery_pin' => !empty($user->recovery_pin),
                ] : null,
                'notifications' => fn () => $user ? \App\Models\Notification::where('user_id', $user->id)->latest()->take(10)->get() : [],
                'unreadNotificationsCount' => fn () => $user ? \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')->count() : 0,
            ],
            'siteSettings' => $siteSettings,
        ]);
    }
}
