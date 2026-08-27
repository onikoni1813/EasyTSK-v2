<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AppSetting;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegister(Request $request)
    {
        $refCode = $request->query('ref') ?? $request->query('ref_code');
        return Inertia::render('Auth/Register', [
            'ref'      => $refCode,
            'ref_code' => $refCode,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|unique:users',
            'email'        => 'nullable|string|email|max:255|unique:users',
            'password'     => 'required|string|min:6|confirmed',
            'recovery_pin' => 'required|digits:4',
            'device_hash'  => 'required|string',
            'ref_code'     => 'nullable|exists:users,referral_code',
        ]);

        // 1 Device = 1 Account Anti-Fraud Check (Bypassed on local environment)
        if (!app()->environment('local')) {
            $existingDevice = User::where('device_hash', $request->device_hash)->first();
            if ($existingDevice) {
                return back()->withErrors([
                    'device_hash' => 'An account is already registered from this device. Multiple accounts are strictly forbidden.',
                ]);
            }
        }

        $welcomeBonus = (float) AppSetting::getByKey('welcome_bonus', '50.0');

        $deviceHash = $request->device_hash;
        if (app()->environment('local') && User::where('device_hash', $deviceHash)->exists()) {
            $deviceHash = $deviceHash . '_' . \Illuminate\Support\Str::random(6);
        }

        $user = User::create([
            'name'          => $request->name,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'recovery_pin'  => $request->recovery_pin,
            'device_hash'   => $deviceHash,
            'role'          => 'user',
            'main_balance'  => 0,
            'pending_balance' => 0,
            'locked_balance'  => $welcomeBonus,
            'welcome_bonus_amount' => $welcomeBonus,
            'has_claimed_welcome_bonus' => false,
            'referral_code' => User::generateReferralCode(),
            'ref_by'        => $request->ref_code
                ? User::where('referral_code', $request->ref_code)->value('id')
                : null,
        ]);

        if ($request->ref_code) {
            $referrer = User::where('referral_code', $request->ref_code)->first();
            if ($referrer) {
                $this->referralService->setupNewReferral($user, $referrer->id);
            }
        }

        if ($welcomeBonus > 0) {
            \App\Models\Notification::send(
                $user,
                "Congrats! You've Received {$welcomeBonus} Welcome Bonus Points! 🎁",
                "Welcome to EasyTSK, {$user->name}! Your {$welcomeBonus} bonus points are held in your locked balance. Complete your required task target to unlock and withdraw them!",
                'info',
                '/tasks',
                true
            );
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'device_hash' => 'required|string',
        ]);

        $loginInput = trim($request->phone);
        $remember = $request->boolean('remember');

        // Check whether user provided email or phone
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $primaryCredentials = $isEmail
            ? ['email' => $loginInput, 'password' => $request->password]
            : ['phone' => $loginInput, 'password' => $request->password];

        $secondaryCredentials = $isEmail
            ? ['phone' => $loginInput, 'password' => $request->password]
            : ['email' => $loginInput, 'password' => $request->password];

        if (Auth::attempt($primaryCredentials, $remember) || Auth::attempt($secondaryCredentials, $remember)) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            if ($user->is_banned) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'phone' => 'Your account has been banned. Please contact support for assistance.',
                ]);
            }

            if (!$user->device_hash) {
                $hashInUse = User::where('device_hash', $request->device_hash)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if (!$hashInUse) {
                    $user->update(['device_hash' => $request->device_hash]);
                }
            }

            $user->update([
                'last_login_ip' => $request->ip(),
                'last_login_device' => substr($request->userAgent() ?? '', 0, 255),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'phone' => 'The provided credentials do not match our records.',
        ]);
    }

    public function recoverAccount(Request $request)
    {
        $request->validate([
            'phone'        => 'required|string',
            'recovery_pin' => 'required|digits:4',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $input = trim($request->phone);

        $user = User::where('phone', $input)
            ->orWhere('email', $input)
            ->first();

        if (!$user) {
            return back()->withErrors([
                'phone' => 'No account found with this phone number or email address.',
            ]);
        }

        if ((string) $user->recovery_pin !== (string) $request->recovery_pin) {
            return back()->withErrors([
                'recovery_pin' => 'Invalid 4-digit Recovery PIN.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('login')->with('success', 'Password reset successfully! You can now log in.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
