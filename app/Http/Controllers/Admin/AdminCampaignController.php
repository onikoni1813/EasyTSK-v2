<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignService;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminCampaignController extends Controller
{
    /**
     * Display list of all campaigns for admin review.
     */
    public function index()
    {
        $campaigns = Campaign::with(['user', 'service'])
            ->latest()
            ->get()
            ->map(function (Campaign $c) {
                return [
                    'id'             => $c->id,
                    'title'          => $c->title,
                    'description'    => $c->description,
                    'target_url'     => $c->target_url,
                    'type'           => $c->type ?? ($c->service->platform ?? 'other'),
                    'action'         => $c->action ?? ($c->service->action ?? ''),
                    'budget_points'  => (float) $c->budget_points,
                    'cost_per_click' => (float) $c->cost_per_click,
                    'total_clicks'   => (int) $c->total_clicks,
                    'target_clicks'  => (int) $c->target_clicks,
                    'status'         => $c->status,
                    'admin_note'     => $c->admin_note,
                    'progress'       => $c->progressPercent(),
                    'user'           => [
                        'id'    => $c->user->id ?? 0,
                        'name'  => $c->user->name ?? 'Unknown',
                        'email' => $c->user->email ?? 'N/A',
                    ],
                    'created_at'     => $c->created_at ? $c->created_at->diffForHumans() : '',
                ];
            });

        return Inertia::render('Admin/Campaigns', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Approve a pending campaign.
     */
    public function approve(Campaign $campaign)
    {
        if ($campaign->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending campaigns can be approved.']);
        }

        $campaign->update(['status' => 'active']);

        Notification::send(
            $campaign->user_id,
            'Campaign Approved! 🎉',
            "Your campaign '{$campaign->title}' has been approved and is now live.",
            'success',
            '/campaigns'
        );

        return back()->with('success', 'Campaign approved successfully.');
    }

    /**
     * Reject a campaign and refund unspent budget to the campaign owner.
     */
    public function reject(Request $request, Campaign $campaign)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if (!in_array($campaign->status, ['pending', 'active'])) {
            return back()->withErrors(['error' => 'Campaign cannot be rejected in its current status.']);
        }

        DB::transaction(function () use ($campaign, $request) {
            /** @var Campaign $lockedCampaign */
            $lockedCampaign = Campaign::where('id', $campaign->id)->lockForUpdate()->first();
            
            // Calculate remaining budget to refund
            $creatorCostPerClick = $lockedCampaign->target_clicks > 0 
                ? ($lockedCampaign->budget_points / $lockedCampaign->target_clicks) 
                : 0;
            $remainingClicks = max(0, $lockedCampaign->target_clicks - $lockedCampaign->total_clicks);
            $refundAmount = round($remainingClicks * $creatorCostPerClick, 2);

            $note = $request->admin_note ?: ($lockedCampaign->status === 'active' ? 'Stopped by admin' : 'Rejected by admin');

            $lockedCampaign->update([
                'status'     => 'rejected',
                'admin_note' => $note,
            ]);

            if ($refundAmount > 0) {
                /** @var User $user */
                $user = User::where('id', $lockedCampaign->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->increment('main_balance', $refundAmount);
                    Transaction::log(
                        $user,
                        'credit',
                        $refundAmount,
                        "Refund for rejected campaign #{$lockedCampaign->id}: {$lockedCampaign->title}",
                        'campaign_refund',
                        (string) $lockedCampaign->id
                    );
                }
            }

            Notification::send(
                $lockedCampaign->user_id,
                'Campaign Status Update 📢',
                "Your campaign '{$lockedCampaign->title}' was " . ($lockedCampaign->status === 'rejected' ? 'rejected/stopped' : 'updated') . ". Note: {$note}" . ($refundAmount > 0 ? " ({$refundAmount} pts refunded)" : ''),
                'warning',
                '/campaigns'
            );
        });

        return back()->with('success', 'Campaign rejected and unspent budget refunded to user.');
    }

    /**
     * Delete campaign record permanently.
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return back()->with('success', 'Campaign record deleted successfully.');
    }

    /**
     * Export all campaigns to CSV backup.
     */
    public function exportCsv()
    {
        $campaigns = Campaign::with(['user', 'service'])->latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaigns_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ];

        $callback = function () use ($campaigns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'User ID', 'User Name', 'User Email', 'Title', 'Platform/Type', 'Action', 'Target URL', 'Target Clicks', 'Total Clicks', 'Cost Per Click', 'Budget Points', 'Status', 'Admin Note', 'Created At']);

            foreach ($campaigns as $c) {
                fputcsv($file, [
                    $c->id,
                    $c->user_id,
                    $c->user->name ?? 'N/A',
                    $c->user->email ?? 'N/A',
                    $c->title,
                    $c->type ?? ($c->service->platform ?? 'N/A'),
                    $c->action ?? ($c->service->action ?? 'N/A'),
                    $c->target_url,
                    $c->target_clicks,
                    $c->total_clicks,
                    $c->cost_per_click,
                    $c->budget_points,
                    $c->status,
                    $c->admin_note,
                    $c->created_at,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Campaign Services Index for Admin.
     */
    public function servicesIndex()
    {
        $campaignServices = CampaignService::orderBy('platform')->get();

        return Inertia::render('Admin/CampaignServices/Index', [
            'campaignServices' => $campaignServices,
        ]);
    }

    /**
     * Store new Campaign Service pricing.
     */
    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'platform'       => 'required|string|max:255',
            'action'         => 'required|string|max:255',
            'creator_cost'   => 'required|numeric|min:0.01|gte:clicker_reward',
            'clicker_reward' => 'required|numeric|min:0.01',
            'requires_proof' => 'boolean',
            'is_active'      => 'boolean',
        ], [
            'creator_cost.gte' => 'Creator Cost must be greater than or equal to User Reward to maintain a positive margin.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['requires_proof'] = $request->boolean('requires_proof', false);

        CampaignService::create($validated);

        return back()->with('success', 'Campaign service created successfully.');
    }

    /**
     * Update existing Campaign Service.
     */
    public function updateService(Request $request, CampaignService $service)
    {
        $validated = $request->validate([
            'platform'       => 'required|string|max:255',
            'action'         => 'required|string|max:255',
            'creator_cost'   => 'required|numeric|min:0.01|gte:clicker_reward',
            'clicker_reward' => 'required|numeric|min:0.01',
            'requires_proof' => 'boolean',
            'is_active'      => 'boolean',
        ], [
            'creator_cost.gte' => 'Creator Cost must be greater than or equal to User Reward to maintain a positive margin.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['requires_proof'] = $request->boolean('requires_proof', false);

        $service->update($validated);

        return back()->with('success', 'Campaign service updated successfully.');
    }

    /**
     * Delete Campaign Service.
     */
    public function deleteService(CampaignService $service)
    {
        $service->delete();

        return back()->with('success', 'Campaign service deleted successfully.');
    }
}
