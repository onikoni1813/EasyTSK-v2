<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PublisherAccount;
use App\Models\Site;
use App\Models\SiteRevenueLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminRevenueController extends Controller
{
    public function index()
    {
        $totalRevenue = SiteRevenueLog::sum('revenue_usd');
        $totalImpressions = SiteRevenueLog::sum('impressions');
        $totalClicks = SiteRevenueLog::sum('clicks');
        $avgCpm = $totalImpressions > 0 ? ($totalRevenue / $totalImpressions) * 1000 : 0.00;

        $revenueLogs = SiteRevenueLog::with(['site', 'publisherAccount'])
            ->latest('log_date')
            ->take(50)
            ->get();

        $sites = Site::where('status', 'active')->get();
        $publisherAccounts = PublisherAccount::where('status', 'active')->get();

        return Inertia::render('Admin/Revenue/Index', [
            'totalRevenue' => number_format($totalRevenue, 2),
            'totalImpressions' => $totalImpressions,
            'totalClicks' => $totalClicks,
            'avgCpm' => number_format($avgCpm, 2),
            'revenueLogs' => $revenueLogs,
            'sites' => $sites,
            'publisherAccounts' => $publisherAccounts,
        ]);
    }

    public function publisherAccounts()
    {
        return Inertia::render('Admin/Revenue/PublisherAccounts', [
            'accounts' => PublisherAccount::latest()->get(),
        ]);
    }

    public function storePublisherAccount(Request $request)
    {
        $validated = $request->validate([
            'network' => 'required|in:adsterra,monetag,admaven,hilltopads,mybid',
            'account_name' => 'required|string|max:255',
            'account_id_or_email' => 'required|string|max:255',
            'payout_method' => 'required|in:wire,usdt,paypal,paxum,webmoney',
            'min_payout_amount' => 'required|numeric|min:1',
            'status' => 'required|in:active,pending_approval,suspended',
        ]);

        PublisherAccount::create($validated);

        return back()->with('success', 'Publisher account registered.');
    }

    public function updatePublisherAccount(Request $request, PublisherAccount $account)
    {
        $validated = $request->validate([
            'network' => 'required|in:adsterra,monetag,admaven,hilltopads,mybid',
            'account_name' => 'required|string|max:255',
            'account_id_or_email' => 'required|string|max:255',
            'payout_method' => 'required|in:wire,usdt,paypal,paxum,webmoney',
            'min_payout_amount' => 'required|numeric|min:1',
            'status' => 'required|in:active,pending_approval,suspended',
        ]);

        $account->update($validated);

        return back()->with('success', 'Publisher account updated.');
    }

    public function destroyPublisherAccount(PublisherAccount $account)
    {
        $account->delete();

        return back()->with('success', 'Publisher account deleted.');
    }

    public function storeRevenueLog(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'publisher_account_id' => 'nullable|exists:publisher_accounts,id',
            'network' => 'required|in:adsterra,monetag,admaven,hilltopads,mybid',
            'log_date' => 'required|date',
            'impressions' => 'required|integer|min:0',
            'clicks' => 'required|integer|min:0',
            'revenue_usd' => 'required|numeric|min:0',
            'payment_status' => 'required|in:unpaid,pending_payout,paid',
        ]);

        $impressions = (int) $validated['impressions'];
        $revenue = (float) $validated['revenue_usd'];
        $cpm = $impressions > 0 ? ($revenue / $impressions) * 1000 : 0.00;

        SiteRevenueLog::updateOrCreate(
            [
                'site_id' => $validated['site_id'],
                'network' => $validated['network'],
                'log_date' => $validated['log_date'],
            ],
            [
                'publisher_account_id' => $validated['publisher_account_id'],
                'impressions' => $impressions,
                'clicks' => (int) $validated['clicks'],
                'revenue_usd' => $revenue,
                'cpm_rate' => $cpm,
                'payment_status' => $validated['payment_status'],
            ]
        );

        return back()->with('success', 'Daily revenue log recorded.');
    }
}
