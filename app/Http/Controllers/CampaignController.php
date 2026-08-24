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
        $user = Auth::user();

        $hasCampaignId = \Illuminate\Support\Facades\Schema::hasColumn('user_tasks', 'campaign_id');

        $query = Campaign::with('service')
            ->where('user_id', $user->id)
            ->latest()
            ->take(10);

        if ($hasCampaignId) {
            $query->withCount(['userTasks as submissions_count']);
        }

        $campaigns = $query->get()
            ->map(fn(Campaign $c) => [
                'id'                => $c->id,
                'title'             => $c->title,
                'description'       => $c->description ?? '',
                'target_url'        => $c->target_url,
                'type'              => $c->type ?: ($c->service->platform ?? 'other'),
                'action'            => $c->action ?: ($c->service->action ?? ''),
                'proof_type'        => $c->proof_type ?? 'screenshot',
                'proof_instruction' => $c->proof_instruction ?? '',
                'secret_code'       => $c->secret_code ?? '',
                'budget_points'     => (float) ($c->budget_points ?? 0),
                'cost_per_click'    => (float) ($c->cost_per_click ?? 0),
                'total_clicks'      => (int) ($c->total_clicks ?? 0),
                'target_clicks'     => (int) ($c->target_clicks ?? 0),
                'submissions_count' => (int) ($c->submissions_count ?? 0),
                'status'            => $c->status ?? 'pending',
                'admin_note'        => $c->admin_note ?? '',
                'progress'          => method_exists($c, 'progressPercent') ? $c->progressPercent() : 0,
                'created_at'        => $c->created_at ? $c->created_at->diffForHumans() : '',
            ]);

        $services = CampaignService::where('is_active', true)->get();

        return Inertia::render('Campaigns/Index', [
            'user'            => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'main_balance'    => (float) $user->main_balance,
                'pending_balance' => (float) $user->pending_balance,
                'level'           => (int) ($user->level ?? 1),
                'xp_points'       => (int) ($user->xp_points ?? 0),
                'health'          => (int) ($user->health ?? 100),
            ],
            'myCampaigns'     => $campaigns,
            'services'        => $services,
            'settings'        => [
                'min_budget'      => 100,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'campaign_service_id' => 'required|exists:campaign_services,id',
        ]);

        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $service = CampaignService::findOrFail($request->campaign_service_id);

        if (!$service->is_active) {
            return back()->withErrors(['campaign_service_id' => 'The selected campaign service is not active.']);
        }

        $minClicks = (int) ($service->min_clicks ?: 50);
        $maxClicks = (int) ($service->max_clicks ?: 5000);

        $proofType = $request->input('proof_type', 'screenshot');
        $needsSecretCode = in_array($proofType, ['secret_code', 'screenshot_code', 'username_code', 'all']);

        $request->validate([
            'title'               => 'required|string|max:100',
            'description'         => 'nullable|string|max:1000',
            'target_url'          => 'required|url|max:2048',
            'campaign_service_id' => 'required|exists:campaign_services,id',
            'proof_type'          => 'required|in:screenshot,username_link,secret_code,screenshot_username,screenshot_code,username_code,all',
            'proof_instruction'   => 'nullable|string|max:1000',
            'secret_code'         => $needsSecretCode ? 'required|string|min:1|max:255' : 'nullable|string|max:255',
            'target_clicks'       => "required|integer|min:{$minClicks}|max:{$maxClicks}",
        ], [
            'target_clicks.min'    => "Minimum clicks required for {$service->platform} is {$minClicks}.",
            'target_clicks.max'    => "Maximum clicks allowed for {$service->platform} is {$maxClicks}.",
            'secret_code.required' => 'Expected Secret Code is required when Secret Code verification is selected.',
        ]);
        
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
            $instruction = $request->proof_instruction ?: $request->description;
            $newCampaign = Campaign::create([
                'user_id'             => $lockedUser->id,
                'title'               => $request->title,
                'description'         => $instruction,
                'target_url'          => $request->target_url,
                'campaign_service_id' => $service->id,
                'type'                => strtolower($service->platform),
                'action'              => $service->action,
                'proof_type'          => $request->proof_type,
                'proof_instruction'   => $instruction,
                'secret_code'         => $request->secret_code,
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

        return back()->with('success', 'Campaign submitted for review! It will go live in the Tasks section once approved.');
    }

    /**
     * Get submissions & proofs for a campaign (for the campaign owner)
     */
    public function submissions(Campaign $campaign)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($campaign->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $submissions = \App\Models\UserTask::with(['user:id,name,phone', 'screenshotHashes'])
            ->where('campaign_id', $campaign->id)
            ->latest()
            ->get()
            ->map(function ($ut) {
                return [
                    'id'              => $ut->id,
                    'user_name'       => $ut->user ? $ut->user->name : 'Worker',
                    'user_phone'      => $ut->user ? (substr($ut->user->phone, 0, 3) . '***' . substr($ut->user->phone, -3)) : '',
                    'status'          => $ut->status,
                    'submitted_data'  => $ut->submitted_data,
                    'screenshot_path' => $ut->submitted_data['screenshot_path'] ?? ($ut->screenshotHashes->first()?->file_path ?? null),
                    'admin_note'      => $ut->admin_note,
                    'submitted_at'    => $ut->created_at ? $ut->created_at->format('M d, Y · H:i') : '',
                ];
            });

        return response()->json([
            'campaign' => [
                'id'                => $campaign->id,
                'title'             => $campaign->title,
                'total_clicks'      => $campaign->total_clicks,
                'target_clicks'     => $campaign->target_clicks,
                'proof_type'        => $campaign->proof_type,
                'proof_instruction' => $campaign->proof_instruction,
            ],
            'submissions' => $submissions,
        ]);
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
        
        $hasCampaignId = \Illuminate\Support\Facades\Schema::hasColumn('user_tasks', 'campaign_id');

        $query = Campaign::with('service')
            ->where('user_id', $user->id)
            ->latest();

        if ($hasCampaignId) {
            $query->withCount(['userTasks as submissions_count']);
        }

        $campaigns = $query->paginate(15)
            ->through(fn(Campaign $c) => [
                'id'                => $c->id,
                'title'             => $c->title,
                'description'       => $c->description ?? '',
                'target_url'        => $c->target_url,
                'type'              => $c->type ?: ($c->service->platform ?? 'other'),
                'action'            => $c->action ?: ($c->service->action ?? ''),
                'proof_type'        => $c->proof_type ?? 'screenshot',
                'proof_instruction' => $c->proof_instruction ?? '',
                'budget_points'     => (float) ($c->budget_points ?? 0),
                'cost_per_click'    => (float) ($c->cost_per_click ?? 0),
                'total_clicks'      => (int) ($c->total_clicks ?? 0),
                'target_clicks'     => (int) ($c->target_clicks ?? 0),
                'submissions_count' => (int) ($c->submissions_count ?? 0),
                'status'            => $c->status ?? 'pending',
                'admin_note'        => $c->admin_note ?? '',
                'progress'          => method_exists($c, 'progressPercent') ? $c->progressPercent() : 0,
                'created_at'        => $c->created_at ? $c->created_at->diffForHumans() : '',
            ]);

        return Inertia::render('Campaigns/History', [
            'myCampaigns' => $campaigns,
        ]);
    }
}
