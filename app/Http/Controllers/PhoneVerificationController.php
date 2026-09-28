<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\BulkSmsDhakaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneVerificationController extends Controller
{
    /**
     * Generate and send SMS OTP to user's phone for qualification.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:11|max:16',
        ]);

        $formattedPhone = BulkSmsDhakaService::formatNumber($request->phone);

        if (strlen($formattedPhone) !== 11 || !str_starts_with($formattedPhone, '01')) {
            return back()->withErrors(['phone' => 'দয়া করে সঠিক ১১ ডিজিটের বাংলাদেশি মোবাইল নাম্বার দিন (যেমন: 017XXXXXXXX)']);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if phone number is already verified by another account
        $existsOnOther = User::where('phone', $formattedPhone)
            ->where('id', '!=', $user->id)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($existsOnOther) {
            return back()->withErrors(['phone' => 'এই মোবাইল নাম্বারটি ইতোমধ্যে অন্য একটি ভেরিফাইড একাউন্টে যুক্ত আছে।']);
        }

        // Anti-spam cooldown: 60 seconds
        $lastVerification = PhoneVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        if ($lastVerification) {
            $cooldownExpiresAt = $lastVerification->created_at->copy()->addSeconds(60);
            if (now()->lessThan($cooldownExpiresAt)) {
                $remaining = (int) ceil(now()->diffInSeconds($cooldownExpiresAt, true));
                return back()->withErrors(['otp' => "নতুন ওটিপি কোড পাঠানোর জন্য অনুগ্রহ করে আরও {$remaining} সেকেন্ড অপেক্ষা করুন।"]);
            }
        }

        // Daily limit: max 5 OTPs per 24 hours
        $dailyCount = PhoneVerification::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($dailyCount >= 5) {
            return back()->withErrors(['otp' => 'আপনার দৈনিক ওটিপি রিকোয়েস্টের সর্বোচ্চ লিমিট (৫ বার) শেষ হয়েছে। আগামী ২৪ ঘণ্টা পর আবার চেষ্টা করুন।']);
        }

        // Generate 6-digit random code
        $code = (string) random_int(100000, 999999);

        // Dispatch SMS via BulkSmsDhakaService
        $smsService = new BulkSmsDhakaService();
        $message = "EasyTsk: Your verification OTP is {$code}. Valid for 5 minutes. Do not share this code.";

        $result = $smsService->sendSms($formattedPhone, $message);

        if (!$result['success']) {
            return back()->withErrors(['phone' => 'এসএমএস পাঠাতে সমস্যা হয়েছে: ' . $result['message']]);
        }

        // Store verification record only on successful SMS delivery
        PhoneVerification::create([
            'user_id' => $user->id,
            'phone' => $formattedPhone,
            'otp_code' => $code,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'ip_address' => $request->ip(),
        ]);

        // Update user phone number if different
        if ($user->phone !== $formattedPhone) {
            $user->phone = $formattedPhone;
            $user->save();
        }

        return back()->with('success', "আপনার মোবাইল নাম্বার {$formattedPhone}-এ একটি ৬-ডিজিটের ভেরিফিকেশন কোড পাঠানো হয়েছে।");
    }

    /**
     * Verify submitted OTP and qualify the user's account.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $verification = PhoneVerification::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$verification) {
            return back()->withErrors(['otp_code' => 'কোনো পেন্ডিং ওটিপি পাওয়া যায়নি। অনুগ্রহ করে নতুন কোড পাঠান।']);
        }

        if ($verification->isExpired()) {
            return back()->withErrors(['otp_code' => 'ওটিপির মেয়াদ শেষ হয়ে গেছে (৫ মিনিট)। অনুগ্রহ করে নতুন কোড রিকোয়েস্ট করুন।']);
        }

        if ($verification->attempts >= 3) {
            return back()->withErrors(['otp_code' => 'সর্বোচ্চ ৩ বার ভুল কোড চেষ্টা করা হয়েছে। কোডটি বাতিল করা হয়েছে, নতুন কোড নিন।']);
        }

        if ($verification->otp_code !== trim($request->otp_code)) {
            $verification->increment('attempts');
            $remaining = 3 - $verification->attempts;
            return back()->withErrors(['otp_code' => "ভুল ওটিপি কোড। পুনরায় চেষ্টা করুন। (বাকি চেষ্টা: {$remaining} বার)"]);
        }

        // Mark verified
        $verification->update([
            'verified_at' => now(),
        ]);

        $user->phone_verified_at = now();
        $user->phone = $verification->phone;
        $user->save();

        return back()->with('success', 'অভিনন্দন! আপনার একাউন্ট সফলভাবে ভেরিফাইড ও কোয়ালিফাইড হয়েছে। এখন আপনি উইথড্র করতে পারবেন।');
    }
}
