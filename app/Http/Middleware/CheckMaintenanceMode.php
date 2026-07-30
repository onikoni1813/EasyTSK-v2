<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = AppSetting::getByKey('maintenance_mode', 'false') === 'true';

        if ($isMaintenance) {
            // Always allow admin control panel routes
            $adminPrefix = env('ADMIN_PATH', 'admin');
            if ($request->is($adminPrefix . '*')) {
                return $next($request);
            }

            // Always allow authentication & webhook routes
            if (
                $request->is('login') ||
                $request->is('logout') ||
                $request->is('postback/*') ||
                $request->is('manifest.json') ||
                $request->is('auth/google*')
            ) {
                return $next($request);
            }

            // Handle Admin Bypass clearing
            if ($request->has('clear_bypass')) {
                $request->session()->forget('admin_bypass_maintenance');
            }

            // Allow Admin to bypass if explicitly requested via ?bypass=1 query param or active session
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if ($user && $user->isAdmin()) {
                if ($request->has('bypass')) {
                    $request->session()->put('admin_bypass_maintenance', true);
                }

                if ($request->session()->get('admin_bypass_maintenance')) {
                    return $next($request);
                }
            }

            $message = AppSetting::getByKey(
                'maintenance_message',
                'We are currently performing scheduled maintenance to upgrade our platform. Please check back shortly!'
            );

            return Inertia::render('Maintenance', [
                'message' => $message,
                'isAdmin' => $user && $user->isAdmin(),
            ])->toResponse($request)->setStatusCode(503);
        }

        // If maintenance is OFF, clear any bypass session
        if ($request->session()->has('admin_bypass_maintenance')) {
            $request->session()->forget('admin_bypass_maintenance');
        }

        return $next($request);
    }
}
