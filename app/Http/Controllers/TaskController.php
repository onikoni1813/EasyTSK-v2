<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\Notification;
use App\Models\OfferwallLog;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\UserTask;
use App\Services\GamificationService;
use App\Services\ReferralService;
use App\Services\StorageSaverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class TaskController extends Controller
{
    protected StorageSaverService $storageSaverService;
    protected GamificationService $gamificationService;
    protected ReferralService $referralService;

    public function __construct(
        StorageSaverService $storageSaverService,
        GamificationService $gamificationService,
        ReferralService $referralService
    ) {
        $this->storageSaverService = $storageSaverService;
        $this->gamificationService = $gamificationService;
        $this->referralService = $referralService;
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $ip = $request->ip();

        $tasks = Task::where('status', 'active')->get()->map(function (Task $task) use ($user, $ip) {
            $userTask = UserTask::where('user_id', $user->id)
                ->where('task_id', $task->id)
                ->latest()
                ->first();

            $lastCompletion = UserTask::where('task_id', $task->id)
                ->where(function($q) use ($user, $ip) {
                    $q->where('user_id', $user->id)
                      ->orWhere('ip_address', $ip);
                })
                ->whereIn('status', ['pending', 'approved'])
                ->latest()
                ->first();

            // If task rotation limit reached, hide it
            if ($task->cooldown_hours == 0 && $lastCompletion) {
                return null;
            }
            if ($task->cooldown_hours > 0 && $lastCompletion) {
                $hoursSince = $lastCompletion->created_at->diffInHours(now());
                if ($hoursSince < $task->cooldown_hours) {
                    return null;
                }
            }

            $task->user_status = $userTask ? $userTask->status : null;
            $task->admin_note = $userTask ? $userTask->admin_note : null;

            if ($task->type === 'secret_code' && $task->secret_code) {
                $task->secret_code_count = count(explode(',', $task->secret_code));
            } else {
                $task->secret_code_count = 1;
            }
            // Ensure the secret code and provider name are never exposed to the frontend!
            $task->makeHidden(['secret_code', 'provider_name']);

            return $task;
        })->filter()->values();

        $offerwalls = \App\Models\Offerwall::where('status', true)->orderBy('order')->get();
        $pendingTasksCount = $tasks->filter(function($t) {
            return !in_array($t->user_status, ['pending', 'approved']);
        })->count();
        $isLocked = $pendingTasksCount > 0;

        $taskHistory = UserTask::where('user_id', $user->id)
            ->with(['task:id,title,reward_coins,type', 'campaign:id,title,cost_per_click,type'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn(UserTask $ut) => [
                'id'          => $ut->id,
                'task_title'  => $ut->campaign_id 
                    ? ($ut->campaign?->title ?? 'Deleted Campaign') 
                    : ($ut->task?->title ?? 'Deleted Task'),
                'task_type'   => $ut->campaign_id 
                    ? 'community' 
                    : ($ut->task?->type ?? 'shortlink'),
                'reward_coins'=> (float) ($ut->campaign_id 
                    ? ($ut->campaign?->cost_per_click ?? 0) 
                    : ($ut->task?->reward_coins ?? 0)),
                'status'      => $ut->status,
                'admin_note'  => $ut->admin_note,
                'submitted_at'=> $ut->created_at->format('M d, Y · H:i'),
            ]);

        $offerwallLogs = OfferwallLog::where('user_id', $user->id)
            ->latest()
            ->take(15)
            ->get()
            ->map(fn(OfferwallLog $log) => [
                'id'             => $log->id,
                'provider'       => $log->provider,
                'transaction_id' => $log->transaction_id,
                'amount'         => (float) $log->amount,
                'status'         => $log->status,
                'reason'         => $log->reason,
                'release_time'   => $log->release_time ? $log->release_time->toIso8601String() : null,
                'created_at'     => $log->created_at->format('M d, Y · H:i'),
            ]);

        $offerwallStats = [
            'total_earned'    => (float) OfferwallLog::where('user_id', $user->id)->where('status', 'approved')->sum('amount'),
            'pending_amount'  => (float) OfferwallLog::where('user_id', $user->id)->where('status', 'pending')->sum('amount'),
            'completed_count' => OfferwallLog::where('user_id', $user->id)->whereIn('status', ['approved', 'pending'])->count(),
        ];

        // Active Community Campaigns (rendered in background when locked or live)
        $communityCampaigns = \App\Models\Campaign::with(['service', 'user:id,name'])
            ->where('status', 'active')
            ->whereRaw('total_clicks < target_clicks')
            ->latest()
            ->get()
            ->map(function (\App\Models\Campaign $campaign) use ($user) {
                $isOwn = (int) $campaign->user_id === (int) $user->id;
                $userSubmission = UserTask::where('user_id', $user->id)
                    ->where('campaign_id', $campaign->id)
                    ->latest()
                    ->first();

                // If user has already submitted (pending review or approved), hide from actionable feed
                if ($userSubmission && in_array($userSubmission->status, ['pending', 'approved'])) {
                    return null;
                }

                return [
                    'id'                => $campaign->id,
                    'is_own'            => $isOwn,
                    'title'             => $campaign->title,
                    'description'       => $campaign->description,
                    'target_url'        => $campaign->target_url,
                    'platform'          => $campaign->type ?: ($campaign->service->platform ?? 'other'),
                    'action'            => $campaign->action ?: ($campaign->service->action ?? ''),
                    'proof_type'        => $campaign->proof_type ?: 'screenshot',
                    'proof_instruction' => $campaign->proof_instruction,
                    'cost_per_click'    => (float) $campaign->cost_per_click,
                    'total_clicks'      => $campaign->total_clicks,
                    'target_clicks'     => $campaign->target_clicks,
                    'creator_name'      => $isOwn ? 'You' : ($campaign->user?->name ?? 'Community Advertiser'),
                    'user_status'       => $userSubmission ? $userSubmission->status : null,
                    'admin_note'        => $userSubmission ? $userSubmission->admin_note : null,
                ];
            })
            ->filter()
            ->values();

        // ── Progression Tier Calculations ──
        // Tier 1: System Tasks remaining
        $pendingSystemTasksCount = $tasks->filter(function($t) {
            return !in_array($t->user_status, ['pending', 'approved']);
        })->count();

        // Tier 2: Community Campaigns remaining & locked state (excluding user's own campaigns)
        $communityLocked = $pendingSystemTasksCount > 0;
        $pendingCommunityCount = $communityCampaigns
            ->filter(fn($c) => !$c['is_own'])
            ->count();

        // Tier 3: Offerwall locked state (Unlocks after System Tasks + Community Campaigns)
        $isOfferwallLocked = $pendingSystemTasksCount > 0 || $pendingCommunityCount > 0;
        $totalPendingForOfferwall = $pendingSystemTasksCount + $pendingCommunityCount;

        return Inertia::render('Tasks/Index', [
            'tasks'                      => $tasks,
            'communityCampaigns'         => $communityCampaigns,
            'community_locked'           => $communityLocked,
            'pending_system_tasks_count' => $pendingSystemTasksCount,
            'community_pending_count'    => $pendingCommunityCount,
            'userLevel'                  => $user->level,
            'offerwalls'                 => $offerwalls,
            'is_locked'                  => $isOfferwallLocked,
            'pending_tasks_count'        => $totalPendingForOfferwall,
            'health_gate_active'         => $user->isHealthGateActive(),
            'health_gate_expires_at'     => $user->isHealthGateActive()
                ? $user->health_depleted_at->addHours(24)->toIso8601String()
                : null,
            'taskHistory'                => $taskHistory,
            'offerwallPendingHours'      => AppSetting::offerwallPendingHours(),
            'offerwallLogs'              => $offerwallLogs,
            'offerwallStats'             => $offerwallStats,
        ]);
    }


    public function submitSocialProof(Request $request, Task $task)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isHealthGateActive()) {
            return back()->withErrors(['message' => 'Your Health has hit 0 due to recent rejections/incorrect codes. Proof submissions are temporarily locked for 24 hours to prevent abuse. Complete Shortlink/Secret Code tasks to regenerate Health.']);
        }

        // Prevent concurrent duplicate submissions (race condition)
        $lock = \Illuminate\Support\Facades\Cache::lock("task_submit_{$user->id}_{$task->id}", 15);
        if (!$lock->get()) {
            return back()->withErrors(['message' => 'Please wait a moment before submitting again.']);
        }

        try {
            if (!$this->checkTaskRotationLimit($task, $user, $request->ip())) {
                return back()->withErrors(['message' => 'You have already completed this task.']);
            }

        $proofRequirements = $task->proof_requirements;

        // ── Dynamic proof requirements path ──
        if ($request->input('is_dynamic') && is_array($proofRequirements) && count($proofRequirements) > 0) {
            return $this->handleDynamicProof($request, $task, $user, $proofRequirements);
        }

        // ── Legacy fallback path ──
        $request->validate([
            'screenshot' => 'nullable|image|max:5120',
            'text_proof' => 'nullable|string|max:2000',
            'secret_codes' => 'nullable|array|max:10',
            'secret_codes.*' => 'nullable|string|max:255',
        ]);

        if (!$request->hasFile('screenshot') && empty($request->text_proof) && empty($request->input('secret_codes'))) {
            return back()->withErrors(['message' => 'You must provide either a screenshot, text proof, or secret code to complete this task.']);
        }

        $userTask = UserTask::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        $submittedData = [];

        if ($request->hasFile('screenshot')) {
            $result = $this->storageSaverService->processAndVerifyScreenshot(
                $request->file('screenshot'),
                $user->id,
                $userTask->id
            );

            if (!$result['success']) {
                $userTask->delete();
                return back()->withErrors(['screenshot' => $result['message']]);
            }
            $submittedData['screenshot_hash'] = $result['hash'];
        }

        $status = 'pending';
        $message = 'Proof submitted successfully! It is now pending admin review.';

        if (!empty($request->text_proof)) {
            $submittedData['text_proof'] = $request->text_proof;
        }

        if ($request->input('secret_codes')) {
            $codes = $request->input('secret_codes');
            $submittedData['secret_codes'] = is_array($codes) ? implode(', ', $codes) : $codes;
        }

        if (!$request->hasFile('screenshot') && empty($request->text_proof) && empty($request->input('secret_codes'))) {
            $userTask->delete();
            return back()->withErrors(['message' => 'You must provide either a screenshot, text proof, or secret code to complete this task.']);
        }

        if ($task->type === 'blog_reward') {
            $submittedCodes = $request->input('secret_codes');
            $submittedCode = '';
            if (is_array($submittedCodes) && !empty($submittedCodes)) {
                $submittedCode = trim($submittedCodes[0] ?? '');
            } elseif (!empty($request->text_proof)) {
                $submittedCode = trim($request->text_proof);
            }

            if (empty($submittedCode)) {
                $userTask->delete();
                return back()->withErrors(['message' => 'Please provide the secret code from the blog article.']);
            }

            $submittedData['secret_codes'] = $submittedCode;

            $verification = $this->verifyBlogRewardCode($task, $submittedCode);
            if ($verification['valid']) {
                $status = 'approved';
                $message = 'Blog Reading Task verified! Reward coins and XP credited automatically.';
            } else {
                if (empty($verification['is_network_error'])) {
                    $user->deductHealth(10);
                    $penaltyText = ' (10 Health deducted)';
                } else {
                    $penaltyText = '';
                }
                $userTask->delete();
                return back()->withErrors(['message' => $verification['message'] . $penaltyText]);
            }
        } elseif ($task->type === 'secret_code' && !empty($task->secret_code)) {
            $requiredCodes = array_map('trim', explode(',', $task->secret_code));
            
            // Allow submission via text_proof (if 1 code) or secret_codes array
            $submittedCodes = $request->input('secret_codes');
            if (!$submittedCodes && !empty($request->text_proof)) {
                // If legacy text_proof was sent and it's a comma separated string, split it, or just use as array of 1
                $submittedCodes = array_map('trim', explode(',', $request->text_proof));
            }

            if (!empty($submittedCodes)) {
                $submittedData['secret_codes'] = is_array($submittedCodes) ? implode(', ', $submittedCodes) : $submittedCodes;
            }

            if (!is_array($submittedCodes) || count($submittedCodes) === 0) {
                $userTask->delete();
                return back()->withErrors(['message' => 'You must provide the required secret code(s) for this task.']);
            }

            $allMatch = true;
            if (count($requiredCodes) !== count($submittedCodes)) {
                $allMatch = false;
            } else {
                foreach ($requiredCodes as $index => $reqCode) {
                    $subCode = trim($submittedCodes[$index] ?? '');
                    if (strcasecmp($reqCode, $subCode) !== 0) {
                        $allMatch = false;
                        break;
                    }
                }
            }

            if ($allMatch) {
                $status = 'approved';
                $message = 'Secret codes matched! Task approved automatically and reward credited.';
            } else {
                $user->deductHealth(10);
                $userTask->delete(); // Delete pending task since it was wrong
                return back()->withErrors(['message' => 'Incorrect secret codes! 10 Health points deducted.']);
            }
        } elseif ($task->secret_code && !empty($request->text_proof) && strcasecmp(trim($request->text_proof), trim($task->secret_code)) === 0) {
            $status = 'approved';
            $message = 'Secret code matched! Task approved automatically and reward credited.';
        }

        $userTask->update([
            'submitted_data' => $submittedData,
            'status' => $status,
        ]);

        if ($status === 'approved') {
            DB::transaction(function () use ($user, $task) {
                $reward = (float) $task->reward_coins * AppSetting::rewardMultiplier();
                $user->addMainBalance($reward);
                $this->gamificationService->awardXp($user, $task->reward_xp);
                $this->referralService->recordReferredUserEarning($user, $reward);
                $user->addHealth(1);
            });
        }

        return back()->with('success', $message);
    } finally {
        $lock->release();
    }
}

    /**
     * Handle dynamic proof submission based on task's proof_requirements config.
     */
    private function handleDynamicProof(Request $request, Task $task, \App\Models\User $user, array $proofRequirements)
    {
        // Build robust validation rules for dynamic inputs to prevent large payloads (DoS)
        $rules = [];
        $messages = [];
        foreach ($proofRequirements as $req) {
            $reqId = $req['id'];
            $isRequired = !empty($req['is_required']);
            
            if ($req['type'] === 'text') {
                $rules["proofs.{$reqId}.text"] = ($isRequired ? 'required' : 'nullable') . '|string|max:3000';
                $messages["proofs.{$reqId}.text.required"] = "\"{$req['label']}\" text is required.";
                $messages["proofs.{$reqId}.text.max"] = "\"{$req['label']}\" must not exceed 3000 characters.";
            } elseif ($req['type'] === 'image') {
                $rules["proofs.{$reqId}.image"] = ($isRequired ? 'required' : 'nullable') . '|image|max:5120';
                $messages["proofs.{$reqId}.image.required"] = "\"{$req['label']}\" screenshot is required.";
                $messages["proofs.{$reqId}.image.image"] = "\"{$req['label']}\" must be a valid image file.";
                $messages["proofs.{$reqId}.image.max"] = "\"{$req['label']}\" must be less than 5MB.";
            }
        }
        $request->validate($rules, $messages);

        $userTask = UserTask::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        $submittedData = [];

        foreach ($proofRequirements as $req) {
            $reqId = $req['id'];
            $entry = ['type' => $req['type'], 'label' => $req['label']];

            if ($req['type'] === 'text') {
                $entry['value'] = $request->input("proofs.{$reqId}.text", null);
            } elseif ($req['type'] === 'image' && $request->hasFile("proofs.{$reqId}.image")) {
                $result = $this->storageSaverService->processAndVerifyScreenshot(
                    $request->file("proofs.{$reqId}.image"),
                    $user->id,
                    $userTask->id
                );

                if (!$result['success']) {
                    $userTask->delete();
                    return back()->withErrors(['message' => $result['message']]);
                }
                $entry['screenshot_hash'] = $result['hash'];
            }

            $submittedData[$reqId] = $entry;
        }

        // Auto-approve if secret_code matches any text proof
        $status = 'pending';
        $message = 'Proof submitted successfully! It is now pending admin review.';

        if ($task->type === 'blog_reward') {
            $submittedCode = '';
            foreach ($submittedData as $entry) {
                if (($entry['type'] ?? '') === 'text' && !empty($entry['value'])) {
                    $submittedCode = trim($entry['value']);
                    break;
                }
            }

            if (empty($submittedCode) && !empty($request->input('secret_codes'))) {
                $codes = $request->input('secret_codes');
                $submittedCode = is_array($codes) ? ($codes[0] ?? '') : $codes;
            }

            $verification = $this->verifyBlogRewardCode($task, (string)$submittedCode);
            if ($verification['valid']) {
                $status = 'approved';
                $message = 'Blog Reading Task verified! Reward coins and XP credited automatically.';
            } else {
                if (empty($verification['is_network_error'])) {
                    $user->deductHealth(10);
                    $penaltyText = ' (10 Health deducted)';
                } else {
                    $penaltyText = '';
                }
                $userTask->delete();
                return back()->withErrors(['message' => $verification['message'] . $penaltyText]);
            }
        } elseif ($task->type === 'secret_code' && !empty($task->secret_code)) {
            $requiredCodes = array_map('trim', explode(',', $task->secret_code));
            $submittedCodes = [];
            foreach ($submittedData as $entry) {
                if (($entry['type'] ?? '') === 'text' && !empty($entry['value'])) {
                    $submittedCodes[] = trim($entry['value']);
                }
            }

            $allMatch = true;
            if (count($requiredCodes) !== count($submittedCodes)) {
                $allMatch = false;
            } else {
                foreach ($requiredCodes as $index => $reqCode) {
                    if (strcasecmp($reqCode, $submittedCodes[$index] ?? '') !== 0) {
                        $allMatch = false;
                        break;
                    }
                }
            }

            if ($allMatch) {
                $status = 'approved';
                $message = 'Secret codes matched! Task approved automatically and reward credited.';
            } else {
                $user->deductHealth(10);
                $userTask->delete();
                return back()->withErrors(['message' => 'Incorrect secret codes! 10 Health points deducted.']);
            }
        } elseif ($task->secret_code) {
            foreach ($submittedData as $entry) {
                if (($entry['type'] ?? '') === 'text' && !empty($entry['value']) && strcasecmp(trim($entry['value']), trim($task->secret_code)) === 0) {
                    $status = 'approved';
                    $message = 'Secret code matched! Task approved automatically and reward credited.';
                    break;
                }
            }
        }

        $userTask->update([
            'submitted_data' => $submittedData,
            'status' => $status,
        ]);

        if ($status === 'approved') {
            DB::transaction(function () use ($user, $task) {
                $reward = (float) $task->reward_coins * AppSetting::rewardMultiplier();
                $user->addMainBalance($reward);
                $this->gamificationService->awardXp($user, $task->reward_xp);
                $this->referralService->recordReferredUserEarning($user, $reward);
                $user->addHealth(1);
            });
        }

        return back()->with('success', $message);
    }

    /**
     * Check if a user can complete a task based on rotation limit
     */
    private function checkTaskRotationLimit(Task $task, \App\Models\User $user, string $ip): bool
    {
        $lastCompletion = UserTask::where('task_id', $task->id)
            ->where(function($q) use ($user, $ip) {
                $q->where('user_id', $user->id)
                  ->orWhere('ip_address', $ip);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->first();

        if ($task->cooldown_hours == 0 && $lastCompletion) {
            return false;
        }

        if ($task->cooldown_hours > 0 && $lastCompletion) {
            $hoursSince = $lastCompletion->created_at->diffInHours(now());
            if ($hoursSince < $task->cooldown_hours) {
                return false;
            }
        }

        return true;
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        
        $taskHistory = UserTask::where('user_id', $user->id)
            ->with(['task:id,title,reward_coins,type', 'campaign:id,title,cost_per_click,type'])
            ->latest()
            ->paginate(15)
            ->through(fn(UserTask $ut) => [
                'id'          => $ut->id,
                'task_title'  => $ut->campaign_id 
                    ? ($ut->campaign?->title ?? 'Deleted Campaign') 
                    : ($ut->task?->title ?? 'Deleted Task'),
                'task_type'   => $ut->campaign_id 
                    ? 'community' 
                    : ($ut->task?->type ?? 'shortlink'),
                'reward_coins'=> (float) ($ut->campaign_id 
                    ? ($ut->campaign?->cost_per_click ?? 0) 
                    : ($ut->task?->reward_coins ?? 0)),
                'status'      => $ut->status,
                'admin_note'  => $ut->admin_note,
                'submitted_at'=> $ut->created_at->format('M d, Y · H:i'),
            ]);

        return Inertia::render('Tasks/History', [
            'taskHistory' => $taskHistory,
        ]);
    }

    /**
     * Submit Proof for a Community Campaign Task
     */
    public function submitCampaignProof(Request $request, \App\Models\Campaign $campaign)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isHealthGateActive()) {
            return back()->withErrors(['message' => 'Your Health has hit 0 due to recent rejections. Submissions are locked for 24 hours.']);
        }

        if ($campaign->status !== 'active') {
            return back()->withErrors(['message' => 'This campaign is no longer active.']);
        }

        if ($campaign->user_id === $user->id) {
            return back()->withErrors(['message' => 'You cannot complete your own campaign task.']);
        }

        if ($campaign->total_clicks >= $campaign->target_clicks) {
            return back()->withErrors(['message' => 'This campaign has already reached its target limit.']);
        }

        // Enforce progression tier: Must complete all active official tasks first
        $activeSystemTasks = Task::where('status', 'active')->get();
        foreach ($activeSystemTasks as $st) {
            $lastCompletion = UserTask::where('task_id', $st->id)
                ->where(function($q) use ($user, $request) {
                    $q->where('user_id', $user->id)
                      ->orWhere('ip_address', $request->ip());
                })
                ->whereIn('status', ['pending', 'approved'])
                ->latest()
                ->first();

            // If task has already been completed and is in cooldown (or one-time completed), it is not pending
            if ($st->cooldown_hours == 0 && $lastCompletion) {
                continue;
            }
            if ($st->cooldown_hours > 0 && $lastCompletion) {
                $hoursSince = $lastCompletion->created_at->diffInHours(now());
                if ($hoursSince < $st->cooldown_hours) {
                    continue;
                }
            }

            $done = UserTask::where('user_id', $user->id)
                ->where('task_id', $st->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
            if (!$done) {
                return back()->withErrors(['message' => 'Please complete all official Task Engine tasks first to unlock Community Campaigns.']);
            }
        }

        // Prevent duplicate active submission
        $existing = UserTask::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return back()->withErrors(['message' => 'You have already submitted proof for this campaign.']);
        }

        // Validate proofs based on campaign proof_type
        $proofType = $campaign->proof_type ?: 'screenshot';
        $rules = [];

        if (in_array($proofType, ['screenshot', 'screenshot_username', 'screenshot_code', 'all'])) {
            $rules['screenshot'] = 'required|image|max:5120';
        }
        if (in_array($proofType, ['username_link', 'screenshot_username', 'username_code', 'all'])) {
            $rules['username_link'] = 'required|string|max:500';
        }
        if (in_array($proofType, ['secret_code', 'screenshot_code', 'username_code', 'all'])) {
            $rules['secret_code'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        // Concurrency lock per user & campaign
        $lock = \Illuminate\Support\Facades\Cache::lock("campaign_submit_{$user->id}_{$campaign->id}", 15);
        if (!$lock->get()) {
            return back()->withErrors(['message' => 'Please wait a moment before submitting again.']);
        }

        try {
            $submittedData = [
                'proof_type'    => $proofType,
                'username_link' => $request->input('username_link'),
                'secret_code'   => $request->input('secret_code'),
            ];

            // ── Verify Secret Code if required ──
            if (in_array($proofType, ['secret_code', 'screenshot_code', 'username_code', 'all']) && !empty($campaign->secret_code)) {
                $submittedCode = trim((string) $request->input('secret_code'));
                $expectedCode  = trim((string) $campaign->secret_code);

                if (strcasecmp($submittedCode, $expectedCode) !== 0) {
                    // Incorrect secret code -> Deduct 10 HP and log rejected attempt for creator
                    $user->deductHealth(10);

                    UserTask::create([
                        'user_id'        => $user->id,
                        'campaign_id'    => $campaign->id,
                        'task_id'        => null,
                        'status'         => 'rejected',
                        'admin_note'     => 'Incorrect secret code entered (-10 Health points deducted)',
                        'ip_address'     => $request->ip(),
                        'submitted_data' => $submittedData,
                    ]);

                    return back()->withErrors(['message' => 'Incorrect secret code! 10 Health points deducted.']);
                }
            }

            // ── Auto-Approve pure Secret Code / Username + Code tasks ──
            if (in_array($proofType, ['secret_code', 'username_code'])) {
                DB::transaction(function () use ($user, $campaign, $submittedData, $request) {
                    UserTask::create([
                        'user_id'        => $user->id,
                        'campaign_id'    => $campaign->id,
                        'task_id'        => null,
                        'status'         => 'approved',
                        'admin_note'     => 'Auto-approved (Secret code verified)',
                        'ip_address'     => $request->ip(),
                        'submitted_data' => $submittedData,
                    ]);

                    $reward = (float) $campaign->cost_per_click * AppSetting::rewardMultiplier();
                    $user->addMainBalance($reward);
                    $this->gamificationService->awardXp($user, 10);
                    $this->referralService->recordReferredUserEarning($user, $reward);
                    $user->addHealth(1);

                    // Increment campaign clicks & complete if target reached
                    $lockedCampaign = Campaign::where('id', $campaign->id)->lockForUpdate()->first();
                    $lockedCampaign->increment('total_clicks');
                    if ($lockedCampaign->total_clicks >= $lockedCampaign->target_clicks) {
                        $lockedCampaign->update(['status' => 'completed']);
                    }

                    Transaction::log(
                        $user,
                        'credit',
                        $reward,
                        "Earned from Community Campaign: {$campaign->title}",
                        'campaign_earn',
                        (string) $campaign->id
                    );

                    Notification::send(
                        $user->id,
                        '🎉 Campaign Task Auto-Approved!',
                        "Secret code matched! +{$reward} coins added to your main balance.",
                        'success',
                        '/tasks'
                    );
                });

                return back()->with('success', 'Secret code verified! Task approved automatically and reward credited.');
            }

            // ── Tasks with Screenshot -> Pending Admin Review ──
            $userTask = UserTask::create([
                'user_id'        => $user->id,
                'campaign_id'    => $campaign->id,
                'task_id'        => null,
                'status'         => 'pending',
                'ip_address'     => $request->ip(),
                'submitted_data' => $submittedData,
            ]);

            if ($request->hasFile('screenshot')) {
                $result = $this->storageSaverService->processAndVerifyScreenshot(
                    $request->file('screenshot'),
                    $user->id,
                    $userTask->id
                );

                if (!$result['success']) {
                    $userTask->delete();
                    return back()->withErrors(['screenshot' => $result['message']]);
                }

                $submittedData['screenshot_hash'] = $result['hash'];
                $submittedData['screenshot_path'] = $result['path'];
                $userTask->update(['submitted_data' => $submittedData]);
            }

            return back()->with('success', 'Campaign task submitted successfully! It is now pending admin review.');

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Could not submit task proof. Please try again.']);
        }
    }

    /**
     * Verify dynamic one-time code against Blog Engine API.
     * Supports multi-site subdomains (blog1..blog8), custom URLs, and automatic failovers.
     */
    protected function verifyBlogRewardCode(Task $task, string $submittedCode): array
    {
        $submittedCode = trim($submittedCode);
        if (empty($submittedCode)) {
            return ['valid' => false, 'is_network_error' => false, 'message' => 'Please provide the secret code from the blog article.'];
        }

        // Build list of candidate verification endpoints
        $candidates = [];

        // 1. Check custom configured blog engine URL
        $configuredUrl = env('BLOG_ENGINE_URL') ?: config('services.blog_engine.url');
        if ($configuredUrl) {
            $base = rtrim($configuredUrl, '/');
            $candidates[] = str_ends_with($base, '/api/task/verify-code') ? $base : $base . '/api/task/verify-code';
        }

        // 2. From task target_url host (if provided)
        $targetUrl = trim((string) $task->target_url);
        if (!empty($targetUrl)) {
            if (str_contains($targetUrl, 'blog_engine')) {
                $candidates[] = 'http://localhost/Easytsk%20v2/blog_engine/public/api/task/verify-code';
                $candidates[] = 'http://127.0.0.1/Easytsk%20v2/blog_engine/public/api/task/verify-code';
            } else {
                $parsed = parse_url($targetUrl);
                if (!empty($parsed['host'])) {
                    $scheme = $parsed['scheme'] ?? 'https';
                    $host = $parsed['host'];
                    $port = !empty($parsed['port']) && !in_array($parsed['port'], [80, 443]) ? ':' . $parsed['port'] : '';
                    $candidates[] = "{$scheme}://{$host}{$port}/api/task/verify-code";
                }
            }
        }

        // 3. Known live blog subdomains (All subdomains connect to the multi-tenant database)
        for ($i = 1; $i <= 8; $i++) {
            $candidates[] = "https://blog{$i}.easytsk.com/api/task/verify-code";
        }
        $candidates[] = 'https://easytsk.com/blog_engine/public/api/task/verify-code';
        $candidates[] = 'https://blogs.easytsk.com/api/task/verify-code';

        // 4. Local development fallbacks
        $candidates[] = 'http://localhost/Easytsk%20v2/blog_engine/public/api/task/verify-code';
        $candidates[] = 'http://127.0.0.1/Easytsk%20v2/blog_engine/public/api/task/verify-code';
        $candidates[] = 'http://127.0.0.1:8000/api/task/verify-code';
        $candidates[] = 'http://localhost:8000/api/task/verify-code';

        $candidates = array_values(array_unique($candidates));

        $lastErrorMessage = null;
        $anyServerResponded = false;

        foreach ($candidates as $verifyUrl) {
            try {
                $apiRes = Http::timeout(5)->post($verifyUrl, [
                    'code' => $submittedCode,
                ]);

                if ($apiRes->successful() && $apiRes->json('valid') === true) {
                    return [
                        'valid' => true,
                        'is_network_error' => false,
                        'message' => 'Blog task verified!',
                        'data' => $apiRes->json('data') ?? [],
                    ];
                }

                $status = $apiRes->status();
                // 409: Already used
                if ($status === 409) {
                    return [
                        'valid' => false,
                        'is_network_error' => false,
                        'message' => $apiRes->json('message') ?? 'This blog code has already been submitted and used.',
                    ];
                }

                // 410: Expired
                if ($status === 410) {
                    return [
                        'valid' => false,
                        'is_network_error' => false,
                        'message' => $apiRes->json('message') ?? 'This blog code has expired (15-minute time limit exceeded).',
                    ];
                }

                if ($apiRes->json('message')) {
                    $anyServerResponded = true;
                    $lastErrorMessage = $apiRes->json('message');
                }
            } catch (\Exception $e) {
                // Connection or DNS failed for this candidate endpoint, continue to next
                continue;
            }
        }

        if ($anyServerResponded && $lastErrorMessage) {
            return ['valid' => false, 'is_network_error' => false, 'message' => $lastErrorMessage];
        }

        return ['valid' => false, 'is_network_error' => true, 'message' => 'Could not connect to blog verification server. Please try again.'];
    }
}
