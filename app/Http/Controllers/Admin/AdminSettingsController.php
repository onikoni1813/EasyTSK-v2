<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $conversionRate = AppSetting::getByKey('conversion_rate', '100');
        $welcomeBonus = AppSetting::getByKey('welcome_bonus', '50.0');
        $happyHourActive = AppSetting::getByKey('happy_hour', 'false');
        $campaignCreatorCost = AppSetting::getByKey('campaign_creator_cost', '2.0');
        $campaignClickerReward = AppSetting::getByKey('campaign_clicker_reward', '1.0');

        $firstWithdrawLimit = AppSetting::getByKey('first_withdraw_limit', '1000');
        $nextWithdrawLimit = AppSetting::getByKey('next_withdraw_limit', '500');

        $referralBonus = AppSetting::getByKey('referral_bonus', '500');
        $referralTarget = AppSetting::getByKey('referral_target', '1000');
        $offerwallPendingHours = AppSetting::getByKey('offerwall_pending_hours', '24');
        $withdrawalChargePercent = AppSetting::getByKey('withdrawal_charge_percent', '0');
        $mobileRechargeMinLimit = AppSetting::getByKey('mobile_recharge_min_limit', '500');
        $mobileRechargeFixedCharge = AppSetting::getByKey('mobile_recharge_fixed_charge', '10');
        $minWithdrawalHealth = AppSetting::getByKey('min_withdrawal_health', '40');

        $demoUsers = AppSetting::getByKey('demo_users', '1200');
        $demoTasks = AppSetting::getByKey('demo_tasks', '45000');
        $demoPayouts = AppSetting::getByKey('demo_payouts', '280000');

        $supportEmail = AppSetting::getByKey('support_email', 'support@easytsk.com');
        $contactEmail = AppSetting::getByKey('contact_email', 'contact@easytsk.com');
        $companyAddress = AppSetting::getByKey('company_address', 'Dhaka, Bangladesh');

        $siteLogo = AppSetting::getByKey('site_logo', null);
        $siteFavicon = AppSetting::getByKey('site_favicon', '/favicon.ico');
        $googleClientId = AppSetting::getByKey('google_client_id', config('services.google.client_id', ''));
        $googleClientSecret = AppSetting::getByKey('google_client_secret', config('services.google.client_secret', ''));

        $telegramAdminBotEnabled = AppSetting::getByKey('telegram_admin_bot_enabled', 'false') === 'true';
        $telegramAdminBotToken = AppSetting::getByKey('telegram_admin_bot_token', '');
        $telegramAdminChatId = AppSetting::getByKey('telegram_admin_chat_id', '');

        $telegramSuccessBotEnabled = AppSetting::getByKey('telegram_success_bot_enabled', 'false') === 'true';
        $telegramSuccessBotToken = AppSetting::getByKey('telegram_success_bot_token', '');
        $telegramSuccessChatId = AppSetting::getByKey('telegram_success_chat_id', '');

        $maintenanceMode = AppSetting::getByKey('maintenance_mode', 'false') === 'true';
        $maintenanceMessage = AppSetting::getByKey('maintenance_message', 'We are currently performing scheduled maintenance to upgrade our platform. Please check back shortly!');

        return Inertia::render('Admin/Settings/Index', [
            'conversionRate' => $conversionRate,
            'welcomeBonus' => (float) $welcomeBonus,
            'happyHourActive' => $happyHourActive === 'true',
            'campaignCreatorCost' => (float) $campaignCreatorCost,
            'campaignClickerReward' => (float) $campaignClickerReward,
            'firstWithdrawLimit' => (int) $firstWithdrawLimit,
            'nextWithdrawLimit' => (int) $nextWithdrawLimit,
            'referralBonus' => (float) $referralBonus,
            'referralTarget' => (float) $referralTarget,
            'offerwallPendingHours' => (int) $offerwallPendingHours,
            'withdrawalChargePercent' => (float) $withdrawalChargePercent,
            'mobileRechargeMinLimit' => (int) $mobileRechargeMinLimit,
            'mobileRechargeFixedCharge' => (float) $mobileRechargeFixedCharge,
            'minWithdrawalHealth' => (int) $minWithdrawalHealth,
            'demoUsers' => (int) $demoUsers,
            'demoTasks' => (int) $demoTasks,
            'demoPayouts' => (float) $demoPayouts,
            'supportEmail' => $supportEmail,
            'contactEmail' => $contactEmail,
            'companyAddress' => $companyAddress,
            'siteLogo' => $siteLogo,
            'siteFavicon' => $siteFavicon,
            'googleClientId' => $googleClientId,
            'googleClientSecret' => $googleClientSecret,
            'telegramAdminBotEnabled' => $telegramAdminBotEnabled,
            'telegramAdminBotToken' => $telegramAdminBotToken,
            'telegramAdminChatId' => $telegramAdminChatId,
            'telegramSuccessBotEnabled' => $telegramSuccessBotEnabled,
            'telegramSuccessBotToken' => $telegramSuccessBotToken,
            'telegramSuccessChatId' => $telegramSuccessChatId,
            'maintenanceMode' => $maintenanceMode,
            'maintenanceMessage' => $maintenanceMessage,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'conversion_rate' => 'required|numeric|min:1',
            'welcome_bonus' => 'required|numeric|min:0',
            'happy_hour' => 'required|boolean',
            'campaign_creator_cost' => 'nullable|numeric|min:0.1',
            'campaign_clicker_reward' => 'nullable|numeric|min:0.1',
            'first_withdraw_limit' => 'required|numeric|min:1',
            'next_withdraw_limit' => 'required|numeric|min:1',
            'referral_bonus' => 'required|numeric|min:1',
            'referral_target' => 'required|numeric|min:1',
            'offerwall_pending_hours' => 'required|integer|min:0',
            'withdrawal_charge_percent' => 'required|numeric|min:0|max:100',
            'mobile_recharge_min_limit' => 'required|numeric|min:1',
            'mobile_recharge_fixed_charge' => 'required|numeric|min:0',
            'min_withdrawal_health' => 'required|integer|min:0|max:100',
            'demo_users' => 'required|numeric|min:0',
            'demo_tasks' => 'required|numeric|min:0',
            'demo_payouts' => 'required|numeric|min:0',
            'support_email' => 'required|email|max:255',
            'contact_email' => 'required|email|max:255',
            'company_address' => 'required|string|max:255',
            'site_logo_file' => 'nullable|file|max:5120',
            'site_favicon_file' => 'nullable|file|max:2048',
            'site_logo_url' => 'nullable|string|max:500',
            'site_favicon_url' => 'nullable|string|max:500',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'telegram_admin_bot_enabled' => 'nullable|boolean',
            'telegram_admin_bot_token' => 'nullable|string|max:255',
            'telegram_admin_chat_id' => 'nullable|string|max:255',
            'telegram_success_bot_enabled' => 'nullable|boolean',
            'telegram_success_bot_token' => 'nullable|string|max:255',
            'telegram_success_chat_id' => 'nullable|string|max:255',
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:1000',
        ]);

        AppSetting::setByKey('conversion_rate', $request->conversion_rate);
        AppSetting::setByKey('welcome_bonus', $request->welcome_bonus);
        AppSetting::setByKey('happy_hour', $request->happy_hour ? 'true' : 'false');
        AppSetting::setByKey('maintenance_mode', $request->maintenance_mode ? 'true' : 'false');
        if ($request->has('maintenance_message')) {
            AppSetting::setByKey('maintenance_message', $request->maintenance_message ?? 'We are currently performing scheduled maintenance to upgrade our platform. Please check back shortly!');
        }
        if ($request->filled('campaign_creator_cost')) {
            AppSetting::setByKey('campaign_creator_cost', $request->campaign_creator_cost);
        }
        if ($request->filled('campaign_clicker_reward')) {
            AppSetting::setByKey('campaign_clicker_reward', $request->campaign_clicker_reward);
        }
        AppSetting::setByKey('first_withdraw_limit', $request->first_withdraw_limit);
        AppSetting::setByKey('next_withdraw_limit', $request->next_withdraw_limit);
        AppSetting::setByKey('referral_bonus', $request->referral_bonus);
        AppSetting::setByKey('referral_target', $request->referral_target);
        AppSetting::setByKey('offerwall_pending_hours', $request->offerwall_pending_hours);
        AppSetting::setByKey('withdrawal_charge_percent', $request->withdrawal_charge_percent);
        AppSetting::setByKey('mobile_recharge_min_limit', $request->mobile_recharge_min_limit);
        AppSetting::setByKey('mobile_recharge_fixed_charge', $request->mobile_recharge_fixed_charge);
        AppSetting::setByKey('min_withdrawal_health', $request->min_withdrawal_health);
        AppSetting::setByKey('demo_users', $request->demo_users);
        AppSetting::setByKey('demo_tasks', $request->demo_tasks);
        AppSetting::setByKey('demo_payouts', $request->demo_payouts);
        AppSetting::setByKey('support_email', $request->support_email);
        AppSetting::setByKey('contact_email', $request->contact_email);
        AppSetting::setByKey('company_address', $request->company_address);
        if ($request->has('google_client_id')) {
            AppSetting::setByKey('google_client_id', $request->google_client_id ?? '');
        }
        if ($request->has('google_client_secret')) {
            AppSetting::setByKey('google_client_secret', $request->google_client_secret ?? '');
        }

        AppSetting::setByKey('telegram_admin_bot_enabled', $request->telegram_admin_bot_enabled ? 'true' : 'false');
        AppSetting::setByKey('telegram_admin_bot_token', $request->telegram_admin_bot_token ?? '');
        AppSetting::setByKey('telegram_admin_chat_id', $request->telegram_admin_chat_id ?? '');

        AppSetting::setByKey('telegram_success_bot_enabled', $request->telegram_success_bot_enabled ? 'true' : 'false');
        AppSetting::setByKey('telegram_success_bot_token', $request->telegram_success_bot_token ?? '');
        AppSetting::setByKey('telegram_success_chat_id', $request->telegram_success_chat_id ?? '');

        if ($request->hasFile('site_logo_file')) {
            $path = $request->file('site_logo_file')->store('branding', 'public');
            AppSetting::setByKey('site_logo', '/storage/' . $path);
        } elseif ($request->filled('site_logo_url')) {
            AppSetting::setByKey('site_logo', $request->site_logo_url);
        }

        if ($request->hasFile('site_favicon_file')) {
            $path = $request->file('site_favicon_file')->store('branding', 'public');
            AppSetting::setByKey('site_favicon', '/storage/' . $path);
        } elseif ($request->filled('site_favicon_url')) {
            AppSetting::setByKey('site_favicon', $request->site_favicon_url);
        }

        return back()->with('success', 'System settings updated successfully.');
    }

    public function testTelegram(Request $request)
    {
        $request->validate([
            'bot_token' => 'required|string',
            'chat_id' => 'required|string',
            'bot_type' => 'required|string|in:admin,success',
        ]);

        $message = "🤖 <b>Test Notification from EasyTsk</b>\n\nType: " . strtoupper($request->bot_type) . " Telegram Bot\nStatus: Connection Successful! ✅\nTime: " . now()->format('d M Y, h:i A');

        $success = \App\Services\TelegramService::sendMessage($request->bot_token, $request->chat_id, $message);

        if ($success) {
            return back()->with('success', 'Test message sent successfully to Telegram!');
        }

        return back()->withErrors(['telegram_test' => 'Failed to send Telegram test message. Please verify Bot Token and Chat ID.']);
    }

    public function cronJobs()
    {
        return Inertia::render('Admin/Settings/CronJobs', [
            'base_path' => base_path(),
        ]);
    }

    public function runCronJob(Request $request)
    {
        $request->validate([
            'target' => 'required|string|in:offerwall:release-pending,proofs:cleanup-screenshots,health:regenerate-daily,referral-contest:distribute,all',
        ]);

        $target = $request->target;

        try {
            if ($target === 'all') {
                Artisan::call('offerwall:release-pending');
                Artisan::call('proofs:cleanup-screenshots');
                Artisan::call('health:regenerate-daily');
                Artisan::call('referral-contest:distribute');
                $output = "All scheduled cron jobs executed successfully!";
            } else {
                Artisan::call($target);
                $rawOutput = trim(Artisan::output());
                $output = $rawOutput ?: "Cron job '{$target}' executed successfully!";
            }

            return back()->with('success', $output);
        } catch (\Throwable $e) {
            return back()->withErrors(['cron' => 'Cron execution failed: ' . $e->getMessage()]);
        }
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'creator_cost' => 'required|numeric|min:0.1',
            'clicker_reward' => 'required|numeric|min:0.1',
            'is_active' => 'boolean',
        ]);

        CampaignService::create($validated);
        return back()->with('success', 'Campaign service added successfully.');
    }

    public function updateService(Request $request, CampaignService $service)
    {
        $validated = $request->validate([
            'platform' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'creator_cost' => 'required|numeric|min:0.1',
            'clicker_reward' => 'required|numeric|min:0.1',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);
        return back()->with('success', 'Campaign service updated successfully.');
    }

    public function deleteService(CampaignService $service)
    {
        $service->delete();
        return back()->with('success', 'Campaign service deleted successfully.');
    }

    public function campaignServices()
    {
        $campaignServices = CampaignService::orderBy('platform')->get();
        return Inertia::render('Admin/CampaignServices/Index', [
            'campaignServices' => $campaignServices,
        ]);
    }
}

