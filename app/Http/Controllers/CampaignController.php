<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignClick;
use App\Models\AppSetting;
use App\Models\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $campaigns = Campaign::with('service')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn(Campaign $c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'description'    => $c->description,
                'target_url'     => $c->target_url,
                'type'           => $c->type ?: ($c->service->platform ?? 'other'),
                'action'         => $c->action ?: ($c->service->action ?? ''),
                'budget_points'  => (float) $c->budget_points,
                'cost_per_click' => (float) $c->cost_per_click,
                'total_clicks'   => $c->total_clicks,
                'target_clicks'  => $c->target_clicks,
                'status'         => $c->status,
                'admin_note'     => $c->admin_note,
                'progress'       => $c->progressPercent(),
                'created_at'     => $c->created_at->diffForHumans(),
            ]);

        // Active campaigns from OTHER users that this user can click
        $activeCampaigns = Campaign::with('service')
            ->where('status', 'active')
            ->where('user_id', '!=', $user->id)
            ->whereRaw('total_clicks < target_clicks')
            ->whereDoesntHave('clicks', fn($q) => $q->where('user_id', $user->id))
            ->get()
            ->map(function ($campaign) {
                return [
                    'id'             => $campaign->id,
                    'title'          => $campaign->title,
                    'type'           => $campaign->type ?: ($campaign->service->platform ?? 'other'),
                    'action'         => $campaign->action ?: ($campaign->service->action ?? ''),
                    'cost_per_click' => (float) $campaign->cost_per_click,
                    'target_url'     => $campaign->target_url,
                ];
            });

        $services = CampaignService::where('is_active', true)->get();

        return Inertia::render('Campaigns/Index', [
            'user'            => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'main_balance'    => (float) $user->main_balance,
                'pending_balance' => (float) $user->pending_balance,
                'level'           => (int) $user->level,
                'xp_points'       => (int) $user->xp_points,
                'health'          => (int) $user->health,
            ],
            'myCampaigns'     => $campaigns,
            'activeCampaigns' => $activeCampaigns,
            'services'        => $services,
            'settings'        => [
                'min_budget'      => 100,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'               => 'required|string|max:100',
            'description'         => 'nullable|string|max:1000',
            'target_url'          => 'required|url|max:2048',
            'campaign_service_id' => 'required|exists:campaign_services,id',
            'target_clicks'       => 'required|integer|min:50|max:100000',
        ]);

        /** @var \App\Models\User $user */
        $user          = Auth::user();
        $service       = CampaignService::findOrFail($request->campaign_service_id);

        if (!$service->is_active) {
            return back()->withErrors(['campaign_service_id' => 'The selected campaign service is not active.']);
        }
        
        $costPerClick  = (float) $service->clicker_reward;
        $costPerClickForCreator = (float) $service->creator_cost;
        $totalBudget   = $request->target_clicks * $costPerClickForCreator;

        DB::beginTransaction();
        try {
            // Lock user row to prevent race condition when checking/deducting balance
            $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

            if ($lockedUser->main_balance < $totalBudget) {
                DB::rollBack();
                return back()->withErrors(['target_clicks' => 'Insufficient balance for this campaign.']);
            }

            // Deduct balance securely
            $lockedUser->decrement('main_balance', $totalBudget);

            // Create campaign
            $newCampaign = Campaign::create([
                'user_id'             => $lockedUser->id,
                'title'               => $request->title,
                'description'         => $request->description,
                'target_url'          => $request->target_url,
                'campaign_service_id' => $service->id,
                'type'                => strtolower($service->platform), // fallback for old UI logic
                'action'              => $service->action, // fallback for old UI logic
                'target_clicks'       => $request->target_clicks,
                'cost_per_click'      => $costPerClick,
                'budget_points'       => $totalBudget,
                'status'              => 'pending',
            ]);

            \App\Models\Transaction::log($lockedUser, 'debit', $totalBudget, "Created Campaign: {$request->title}", 'campaign_create', (string) $newCampaign->id);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Could not create campaign.']);
        }

        return back()->with('success', 'Campaign submitted for review! It will go live once approved.');
    }

    /**
     * User clicks on another user's campaign to earn points
     */
    public function click(Campaign $campaign)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Basic checks before hitting the DB locks
        if ($campaign->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Campaign is not active.'], 403);
        }
        if ($campaign->user_id === $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot click your own campaign.'], 403);
        }

        try {
            $result = DB::transaction(function () use ($user, $campaign) {
                // Lock the campaign to prevent exceeding total clicks concurrently
                $lockedCampaign = Campaign::where('id', $campaign->id)->lockForUpdate()->first();

                if ($lockedCampaign->status !== 'active' || $lockedCampaign->total_clicks >= $lockedCampaign->target_clicks) {
                    return ['success' => false, 'code' => 403, 'message' => 'This campaign has reached its click limit or is no longer active.'];
                }

                $alreadyClicked = CampaignClick::where('campaign_id', $lockedCampaign->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate() // Prevent concurrent duplicate clicks by the same user
                    ->exists();

                if ($alreadyClicked) {
                    return ['success' => false, 'code' => 403, 'message' => 'Already clicked.'];
                }

                CampaignClick::create([
                    'campaign_id' => $lockedCampaign->id,
                    'user_id'     => $user->id,
                    'ip_address'  => request()->ip(),
                ]);
                
                $lockedCampaign->increment('total_clicks');
                
                if ($lockedCampaign->fresh()->total_clicks >= $lockedCampaign->target_clicks) {
                    $lockedCampaign->update(['status' => 'completed']);
                }

                // Lock the user to safely update balance and XP
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                $lockedUser->increment('main_balance', (float) $lockedCampaign->cost_per_click);
                $lockedUser->addXp(1);
                \App\Models\Transaction::log($lockedUser, 'credit', (float) $lockedCampaign->cost_per_click, "Reward for Ad Click: {$lockedCampaign->title}", 'campaign_click', (string) $lockedCampaign->id);

                return [
                    'success'     => true,
                    'code'        => 200,
                    'target_url'  => $lockedCampaign->target_url,
                    'new_balance' => (float) $lockedUser->fresh()->main_balance,
                ];
            });

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['message']], $result['code']);
            }

            return response()->json([
                'success'     => true,
                'target_url'  => $result['target_url'],
                'new_balance' => $result['new_balance'],
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred. Please try again later.'], 500);
        }
    }

    /**
     * Show the full history of user campaigns
     */
    public function history()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $campaigns = Campaign::with('service')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn(Campaign $c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'description'    => $c->description,
                'target_url'     => $c->target_url,
                'type'           => $c->type ?: ($c->service->platform ?? 'other'),
                'action'         => $c->action ?: ($c->service->action ?? ''),
                'budget_points'  => (float) $c->budget_points,
                'cost_per_click' => (float) $c->cost_per_click,
                'total_clicks'   => $c->total_clicks,
                'target_clicks'  => $c->target_clicks,
                'status'         => $c->status,
                'admin_note'     => $c->admin_note,
                'progress'       => $c->progressPercent(),
                'created_at'     => $c->created_at->diffForHumans(),
            ]);

        return Inertia::render('Campaigns/History', [
            'myCampaigns' => $campaigns,
        ]);
    }
}
