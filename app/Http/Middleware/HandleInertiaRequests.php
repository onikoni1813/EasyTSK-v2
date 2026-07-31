<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return md5_file($manifest);
        }
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'main_balance' => (float) $request->user()->main_balance,
                    'pending_balance' => (float) $request->user()->pending_balance,
                    'locked_balance' => (float) $request->user()->locked_balance,
                    'level' => $request->user()->level,
                    'xp_points' => $request->user()->xp_points,
                    'role' => $request->user()->role,
                    'health' => $request->user()->health,
                    'has_claimed_welcome_bonus' => (bool) $request->user()->has_claimed_welcome_bonus,
                ] : null,
                'notifications' => $request->user() 
                    ? (function() use ($request) {
                        $user = $request->user();
                        if ($user->has_claimed_welcome_bonus) {
                            \App\Models\Notification::where('user_id', $user->id)
                                ->where('title', 'like', '%Welcome Bonus%')
                                ->whereNull('read_at')
                                ->update(['read_at' => now()]);
                        }
                        return \App\Models\Notification::where('user_id', $user->id)->latest()->take(15)->get();
                    })()
                    : [],
                'unreadNotificationsCount' => $request->user() 
                    ? \App\Models\Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count() 
                    : 0,
            ],
            'siteSettings' => [
                'site_name'           => \App\Models\AppSetting::getByKey('site_name', 'Easytsk V2'),
                'site_short_name'     => \App\Models\AppSetting::getByKey('site_short_name', 'Easytsk'),
                'support_email'       => \App\Models\AppSetting::getByKey('support_email', 'support@easytsk.com'),
                'contact_email'       => \App\Models\AppSetting::getByKey('contact_email', 'contact@easytsk.com'),
                'company_address'     => \App\Models\AppSetting::getByKey('company_address', 'Dhaka, Bangladesh'),
                'site_logo'           => \App\Models\AppSetting::getByKey('site_logo', null),
                'site_favicon'        => \App\Models\AppSetting::getByKey('site_favicon', '/favicon.ico'),
                'is_maintenance_mode' => \App\Models\AppSetting::getByKey('maintenance_mode', 'false') === 'true',
            ],
            'admin_path' => env('ADMIN_PATH', 'admin'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
