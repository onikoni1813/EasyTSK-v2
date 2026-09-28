<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Services\BulkSmsDhakaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminSmsCampaignController extends Controller
{
    /**
     * Display SMS Campaign dashboard, live balances, audience counts & history.
     */
    public function index(Request $request)
    {
        $smsService = new BulkSmsDhakaService();
        $balanceData = $smsService->getBalance();

        // Calculate audience counts for all filters
        $basePhoneQuery = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw('LENGTH(phone) >= 11');

        $audienceCounts = [
            'all'            => (clone $basePhoneQuery)->count(),
            'inactive_3d'    => (clone $basePhoneQuery)->where('updated_at', '<', now()->subDays(3))->count(),
            'inactive_7d'    => (clone $basePhoneQuery)->where('updated_at', '<', now()->subDays(7))->count(),
            'balance_gt_500' => (clone $basePhoneQuery)->where('main_balance', '>=', 500)->count(),
            'today_new'      => (clone $basePhoneQuery)->whereDate('created_at', today())->count(),
            'verified_only'  => (clone $basePhoneQuery)->whereNotNull('phone_verified_at')->count(),
        ];

        // Sample recipients preview for each filter
        $sampleContacts = (clone $basePhoneQuery)->latest()->take(5)->pluck('phone')->map(function ($p) {
            return substr($p, 0, 4) . '****' . substr($p, -3);
        });

        // Campaign history
        $campaigns = SmsCampaign::with('admin:id,name,email')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/SmsCampaign/Index', [
            'balance'         => $balanceData['balance'] ?? 0,
            'balanceMessage'  => $balanceData['message'] ?? '',
            'isConfigured'    => $smsService->isConfigured(),
            'isEnabled'       => AppSetting::getByKey('bulksmsdhaka_enabled', 'false') === 'true',
            'senderId'        => AppSetting::getByKey('bulksmsdhaka_sender_id', '1234'),
            'audienceCounts'  => $audienceCounts,
            'sampleContacts'  => $sampleContacts,
            'campaigns'       => $campaigns,
            'adminPhone'      => Auth::user()?->phone ?? '',
        ]);
    }

    /**
     * Get live audience count for selected filter, including dynamic balance threshold.
     */
    public function audienceCount(Request $request)
    {
        $filterType = $request->query('filter_type', 'all');
        $minBalance = (float) $request->query('min_balance', 500);

        $query = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw('LENGTH(phone) >= 11');

        switch ($filterType) {
            case 'inactive_3d':
                $query->where('updated_at', '<', now()->subDays(3));
                break;
            case 'inactive_7d':
                $query->where('updated_at', '<', now()->subDays(7));
                break;
            case 'balance_gt_500':
            case 'balance_min':
                $query->where('main_balance', '>=', max(0, $minBalance));
                break;
            case 'today_new':
                $query->whereDate('created_at', today());
                break;
            case 'verified_only':
                $query->whereNotNull('phone_verified_at');
                break;
        }

        $count = $query->count();
        $sample = (clone $query)->latest()->take(3)->pluck('phone')->map(function ($p) {
            return substr($p, 0, 4) . '****' . substr($p, -3);
        });

        return response()->json([
            'count'  => $count,
            'sample' => $sample,
        ]);
    }

    /**
     * Send a single test SMS to admin's phone before launching a blast.
     */
    public function testSend(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string|min:11|max:16',
            'message' => 'required|string|min:3|max:800',
        ]);

        $smsService = new BulkSmsDhakaService();
        $senderId = AppSetting::getByKey('bulksmsdhaka_sender_id', '1234');

        $result = $smsService->sendSms($request->phone, $request->message, $senderId);

        if (!$result['success']) {
            return back()->withErrors(['test_phone' => 'টেস্ট এসএমএস পাঠাতে ব্যর্থ: ' . $result['message']]);
        }

        return back()->with('success', "টেস্ট এসএমএস সফলভাবে {$request->phone}-এ পাঠানো হয়েছে! 📲");
    }

    /**
     * Launch bulk SMS campaign to filtered audience.
     */
    public function send(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:all,inactive_3d,inactive_7d,balance_gt_500,balance_min,today_new,verified_only',
            'min_balance' => 'nullable|numeric|min:0',
            'title'       => 'nullable|string|max:100',
            'message'     => 'required|string|min:5|max:800',
        ]);

        $isEnabled = AppSetting::getByKey('bulksmsdhaka_enabled', 'false') === 'true';
        if (!$isEnabled) {
            return back()->withErrors(['message' => 'BulkSMS Gateway বর্তমানে সিস্টেম সেটিংসে Disabled রয়েছে। অনুগ্রহ করে সেটিংস থেকে চালু করুন।']);
        }

        // Query target users based on selected filter
        $query = User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw('LENGTH(phone) >= 11');

        $minBalance = max(0, (float) ($request->min_balance ?? 500));

        switch ($request->filter_type) {
            case 'inactive_3d':
                $query->where('updated_at', '<', now()->subDays(3));
                break;
            case 'inactive_7d':
                $query->where('updated_at', '<', now()->subDays(7));
                break;
            case 'balance_gt_500':
            case 'balance_min':
                $query->where('main_balance', '>=', $minBalance);
                break;
            case 'today_new':
                $query->whereDate('created_at', today());
                break;
            case 'verified_only':
                $query->whereNotNull('phone_verified_at');
                break;
        }

        $phoneNumbers = $query->pluck('phone')->all();

        if (empty($phoneNumbers)) {
            return back()->withErrors(['filter_type' => 'নির্বাচিত ফিল্টারে কোনো বৈধ ফোন নাম্বার পাওয়া যায়নি।']);
        }

        $smsService = new BulkSmsDhakaService();
        $senderId = AppSetting::getByKey('bulksmsdhaka_sender_id', '1234');
        $message = trim($request->message);

        // Approximate cost estimate (0.35 BDT per SMS part)
        $isUnicode = preg_match('/[^\x00-\x7F]/', $message);
        $charsPerPart = $isUnicode ? 70 : 160;
        $parts = max(1, (int) ceil(mb_strlen($message) / $charsPerPart));
        $estimatedCost = round(count($phoneNumbers) * $parts * 0.35, 2);

        // Execute bulk dispatch
        $result = $smsService->sendBulkSms($phoneNumbers, $message, $senderId);

        $filterLabel = ($request->filter_type === 'balance_min' || $request->filter_type === 'balance_gt_500')
            ? "balance >= {$minBalance} Pts"
            : $request->filter_type;

        // Record campaign in database
        SmsCampaign::create([
            'admin_id'        => Auth::id(),
            'title'           => $request->title ?: 'SMS Campaign (' . strtoupper($filterLabel) . ')',
            'filter_type'     => $filterLabel,
            'message'         => $message,
            'sender_id'       => $senderId,
            'recipient_count' => count($phoneNumbers),
            'sent_count'      => $result['sent'],
            'failed_count'    => $result['failed'],
            'cost_estimate'   => $estimatedCost,
            'status'          => $result['sent'] > 0 ? ($result['failed'] > 0 ? 'partial' : 'completed') : 'failed',
        ]);

        return back()->with('success', "🚀 এসএমএস ক্যাম্পেইন সফলভাবে পরিচালিত হয়েছে! পাঠানো হয়েছে: {$result['sent']} টি, ব্যর্থ: {$result['failed']} টি।");
    }
}
