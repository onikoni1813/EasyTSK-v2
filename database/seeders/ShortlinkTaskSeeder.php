<?php

namespace Database\Seeders;

use App\Models\ShortlinkProvider;
use App\Models\Task;
use Illuminate\Database\Seeder;

class ShortlinkTaskSeeder extends Seeder
{
    /**
     * Seed risk-free, optimized shortlink tasks disguised as premium sponsored partner tasks.
     */
    public function run(): void
    {
        $configs = [
            'GPLinks' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০১ (Diamond Sponsor)',
                'description' => 'স্পন্সর পার্টনার পেজটি ভিজিট করে ভেরিফিকেশন সম্পন্ন করুন। সফলভাবে ডেসটিনেশনে পৌঁছালে সর্বোচ্চ রিওয়ার্ড অটো ব্যালেন্সে যোগ হবে।',
                'reward_coins' => 15.00,
                'reward_xp' => 5,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'ShrinkMe.io' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০২ (Platinum Sponsor)',
                'description' => 'নির্ধারিত স্পন্সর লিংক ওপেন করে সহজ ক্যাপচা পূরণ করুন এবং ফাইনাল পেজে পৌঁছালে সাথে সাথে রিওয়ার্ড পেয়ে যাবেন।',
                'reward_coins' => 10.00,
                'reward_xp' => 5,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'Exe.io' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৩ (Gold Sponsor)',
                'description' => 'পার্টনার সাইটে ভিজিট করে ভেরিফিকেশন শেষ করুন। কোনো স্ক্রিনশট বা প্রুফ দেওয়া লাগবে না, সরাসরি অটোমেটিক ক্রেডিট হবে।',
                'reward_coins' => 10.00,
                'reward_xp' => 5,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'Droplink.co' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৪ (Silver Sponsor)',
                'description' => 'স্পন্সর পেজটি ভিজিট করে সহজ কয়েকটি ক্লিক ও ভেরিফিকেশন ধাপ পার করে মূল পেজে পৌঁছান।',
                'reward_coins' => 10.00,
                'reward_xp' => 5,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'Cuty.io' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৫ (Ruby Sponsor)',
                'description' => 'স্পন্সর লিংকটি ওপেন করে সিকিউরিটি স্টেপ সম্পন্ন করুন এবং ফাইনাল পেজে গিয়ে তাৎক্ষণিক কয়েন সংগ্রহ করুন।',
                'reward_coins' => 10.00,
                'reward_xp' => 5,
                'cooldown_hours' => 24, // Safe 1 view/24h
            ],
            'Clk.sh' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৬ (Emerald Sponsor)',
                'description' => 'স্পন্সর পেজের টাইমার ও ভেরিফিকেশন স্টেপ শেষ করে মূল পেজে রিডাইরেক্ট হলেই কয়েন যোগ হয়ে যাবে।',
                'reward_coins' => 8.00,
                'reward_xp' => 4,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'CutWin' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৭ (Sapphire Sponsor)',
                'description' => 'স্পন্সর পেজের সহজ নির্দেশনা অনুসরণ করে ডেস্টিনেশন পেজে প্রবেশ করুন এবং রিওয়ার্ড উপভোগ করুন।',
                'reward_coins' => 8.00,
                'reward_xp' => 4,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'Fc.lc' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৮ (Crystal Sponsor)',
                'description' => 'সহজ ভেরিফিকেশন ধাপগুলো পার করে পেজের শেষে পৌঁছান, সম্পূর্ণ স্বয়ংক্রিয়ভাবে অ্যাকাউন্ট ব্যালেন্স ক্রেডিট হবে।',
                'reward_coins' => 8.00,
                'reward_xp' => 4,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'Kut.li' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #০৯ (Amber Sponsor)',
                'description' => 'পার্টনার পেজে গিয়ে ক্যাপচা পূরণ করে ডেস্টিনেশন পেজে রিডাইরেক্ট হোন।',
                'reward_coins' => 8.00,
                'reward_xp' => 4,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'ShrinkEarn' => [
                'title' => 'স্পন্সর ওয়েব ভিজিট #১০ (Pearl Sponsor)',
                'description' => 'স্পন্সর পেজটি ব্রাউজ করে ভেরিফিকেশন শেষ করলেই আপনার ওয়ালেটে বোনাস যোগ হবে।',
                'reward_coins' => 8.00,
                'reward_xp' => 4,
                'cooldown_hours' => 24, // Strict 1 view/24h
            ],
            'ShrtFly.com' => [
                'title' => 'দৈনিক মাল্টি বোনাস #০১ (Twin Star - দিনে ২ বার)',
                'description' => 'সহজ স্পন্সর ভিজিট টাস্ক। এই বিশেষ টাস্কটি আপনি প্রতি ১২ ঘণ্টা পর পর (দিনে ২ বার) করতে পারবেন।',
                'reward_coins' => 7.00,
                'reward_xp' => 3,
                'cooldown_hours' => 12, // ShrtFly allows 2 views/24h
            ],
            'AdFoc.us' => [
                'title' => 'দৈনিক মাল্টি বোনাস #০২ (Speed Flash - দিনে ৩ বার)',
                'description' => 'পেজের উপরের ডানদিকের "SKIP" বাটনে ক্লিক করে ডেস্টিনেশনে যান। এই দ্রুত টাস্কটি প্রতি ৮ ঘণ্টা পর পর (দিনে ৩ বার) করতে পারবেন।',
                'reward_coins' => 5.00,
                'reward_xp' => 3,
                'cooldown_hours' => 8, // AdFoc.us allows 3-4 views/24h
            ],
        ];

        foreach ($configs as $providerName => $cfg) {
            $provider = ShortlinkProvider::where('name', $providerName)->first();
            if (!$provider) {
                continue;
            }

            Task::updateOrCreate(
                [
                    'type' => 'shortlink',
                    'provider_name' => $provider->name,
                ],
                [
                    'title' => $cfg['title'],
                    'description' => $cfg['description'],
                    'target_url' => $provider->api_url,
                    'secret_code' => $provider->api_key,
                    'reward_coins' => $cfg['reward_coins'],
                    'reward_xp' => $cfg['reward_xp'],
                    'cooldown_hours' => $cfg['cooldown_hours'],
                    'status' => 'active',
                    'proof_requirements' => [],
                ]
            );
        }
    }
}
