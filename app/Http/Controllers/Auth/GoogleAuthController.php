<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private function setupGoogleConfig(): bool
    {
        $clientId = AppSetting::getByKey('google_client_id') ?: config('services.google.client_id');
        $clientSecret = AppSetting::getByKey('google_client_secret') ?: config('services.google.client_secret');
        $redirectUrl = AppSetting::getByKey('google_redirect_uri') 
            ?: (config('services.google.redirect') ?: route('auth.google.callback'));

        if (empty($clientId) || empty($clientSecret)) {
            return false;
        }

        config([
            'services.google.client_id' => $clientId,
            'services.google.client_secret' => $clientSecret,
            'services.google.redirect' => $redirectUrl,
        ]);

        return true;
    }

    public function redirectToGoogle(Request $request)
    {
        if (!$this->setupGoogleConfig()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google Login is currently not configured by administrator.',
            ]);
        }

        $deviceHash = $request->query('device_hash') ?: $request->cookie('device_hash');
        if ($deviceHash) {
            session(['google_device_hash' => $deviceHash]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        if (!$this->setupGoogleConfig()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google Login is currently not configured by administrator.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            $deviceHash = session('google_device_hash') 
                ?: ($request->cookie('device_hash') ?: $request->query('device_hash'));

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                if ($user->is_banned) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'Your account has been banned. Please contact support for assistance.',
                    ]);
                }

                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }

                // Anti-Fraud Check for existing accounts:
                // Prevent logging in if this device is already bound to a DIFFERENT account.
                if ($deviceHash) {
                    $hashInUse = User::where('device_hash', $deviceHash)
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if ($hashInUse) {
                        return redirect()->route('login')->withErrors([
                            'email' => 'An account is already registered from this device. Multiple accounts are strictly forbidden.',
                        ]);
                    }

                    // Bind if missing
                    if (!$user->device_hash) {
                        $user->update(['device_hash' => $deviceHash]);
                    }
                }

                $user->update([
                    'last_login_ip' => $request->ip(),
                    'last_login_device' => substr($request->userAgent() ?? '', 0, 255),
                ]);

                Auth::login($user);
            } else {
                // Anti-Fraud Check: 1 Device = 1 Account Limit
                if ($deviceHash) {
                    $existingDeviceUser = User::where('device_hash', $deviceHash)->first();
                    if ($existingDeviceUser) {
                        return redirect()->route('login')->withErrors([
                            'email' => 'An account is already registered from this device. Multiple accounts are strictly forbidden.',
                        ]);
                    }
                }

                $welcomeBonus = (float) AppSetting::getByKey('welcome_bonus', '50.0');

                // IP Risk check: if 3+ accounts exist from this IP, flag risk score
                $ipCount = User::where('last_login_ip', $request->ip())->count();
                $riskScore = $ipCount >= 3 ? 75.0 : 0.0;

                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'device_hash' => $deviceHash,
                    'last_login_ip' => $request->ip(),
                    'last_login_device' => substr($request->userAgent() ?? '', 0, 255),
                    'risk_score' => $riskScore,
                    'password' => null,
                    'role' => 'user',
                    'main_balance' => 0,
                    'pending_balance' => 0,
                    'locked_balance' => $welcomeBonus,
                    'welcome_bonus_amount' => $welcomeBonus,
                    'has_claimed_welcome_bonus' => false,
                    'referral_code' => User::generateReferralCode(),
                ]);

                Auth::login($user);
            }

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to authenticate with Google. Please try again.',
            ]);
        }
    }
}

