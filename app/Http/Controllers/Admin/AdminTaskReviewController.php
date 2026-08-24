<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\UserTask;
use App\Services\GamificationService;
use App\Services\ReferralService;
use App\Services\StorageSaverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminTaskReviewController extends Controller
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

    public function index()
    {
        $pendingReviews = UserTask::with([
            'user' => function ($query) {
                $query->withCount([
                    'userTasks as approved_tasks_count' => fn ($q) => $q->where('status', 'approved'),
                    'userTasks as rejected_tasks_count' => fn ($q) => $q->where('status', 'rejected'),
                ]);
            },
            'task',
            'campaign.service',
            'campaign.user',
            'screenshotHashes',
        ])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/TaskReviews/Index', [
            'pendingReviews' => $pendingReviews,
        ]);
    }

    public function approve(UserTask $userTask)
    {
        if ($userTask->status !== 'pending') {
            return back()->withErrors(['message' => 'Task review has already been processed.']);
        }

        $this->approveUserTask($userTask);

        return back()->with('success', 'Task approved! Reward credited successfully.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:user_tasks,id',
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, UserTask> $userTasks */
        $userTasks = UserTask::with(['user', 'task', 'campaign'])
            ->whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->get();

        foreach ($userTasks as $userTask) {
            /** @var UserTask $userTask */
            $this->approveUserTask($userTask);
        }

        return back()->with('success', $userTasks->count() . ' task(s) approved and rewards credited.');
    }

    /**
     * Shared approval logic used by both single and bulk approve, so reward crediting,
     * XP, referral progress, and proof cleanup always stay consistent.
     */
    private function approveUserTask(UserTask $userTask): void
    {
        $isCampaign = false;

        $success = DB::transaction(function () use ($userTask, &$isCampaign) {
            $lockedTask = UserTask::where('id', $userTask->id)->lockForUpdate()->first();
            
            // Double check inside lock to prevent race conditions
            if (!$lockedTask || $lockedTask->status !== 'pending') {
                return false;
            }

            $lockedTask->update(['status' => 'approved']);
            $user = $lockedTask->user;

            if ($lockedTask->campaign_id) {
                $isCampaign = true;
                $campaign = \App\Models\Campaign::where('id', $lockedTask->campaign_id)->lockForUpdate()->first();
                if ($campaign) {
                    $reward = (float) $campaign->cost_per_click;
                    $user->addMainBalance($reward);
                    \App\Models\Transaction::log($user, 'credit', $reward, "Reward for Campaign Task #{$campaign->id}: {$campaign->title}", 'campaign_task', (string)$campaign->id);
                    \App\Models\Notification::send($user, 'Campaign Task Approved! 🎉', "Your submission for '{$campaign->title}' was approved! +{$reward} points credited.", 'success', '/tasks-history');

                    $this->gamificationService->awardXp($user, 1);
                    $this->referralService->recordReferredUserEarning($user, $reward);
                    $user->addHealth(1);

                    $campaign->increment('total_clicks');
                    if ($campaign->fresh()->total_clicks >= $campaign->target_clicks) {
                        $campaign->update(['status' => 'completed']);
                    }
                }
            } else {
                $task = $lockedTask->task;
                if ($task) {
                    $reward = (float) $task->reward_coins * AppSetting::rewardMultiplier();
                    $user->addMainBalance($reward);
                    \App\Models\Transaction::log($user, 'credit', $reward, "Reward for Task #{$task->id}: {$task->title}", 'task', (string)$task->id);
                    \App\Models\Notification::send($user, 'Task Approved! 🎉', "Your submission for '{$task->title}' was approved! +{$reward} coins credited.", 'success', '/tasks-history');

                    $this->gamificationService->awardXp($user, $task->reward_xp);
                    $this->referralService->recordReferredUserEarning($user, $reward);
                    $user->addHealth(1);
                }
            }
            
            return true;
        });

        // Delete proof images on approval ONLY for standard admin tasks (keep for campaign creator inspection)
        if ($success && !$isCampaign) {
            $this->storageSaverService->deleteUserTaskScreenshots($userTask);
        }
    }

    public function reject(Request $request, UserTask $userTask)
    {
        $request->validate([
            'admin_note' => 'required|string|max:255',
        ]);

        if ($userTask->status !== 'pending') {
            return back()->withErrors(['message' => 'Task review has already been processed.']);
        }

        $isCampaign = false;

        $success = DB::transaction(function () use ($userTask, $request, &$isCampaign) {
            $lockedTask = UserTask::where('id', $userTask->id)->lockForUpdate()->first();

            // Double check inside lock to prevent race conditions
            if (!$lockedTask || $lockedTask->status !== 'pending') {
                return false;
            }

            $lockedTask->update([
                'status' => 'rejected',
                'admin_note' => $request->admin_note,
            ]);

            // Deduct health since the task was rejected
            if ($lockedTask->user) {
                $lockedTask->user->deductHealth(10);
                $taskTitle = $lockedTask->campaign ? $lockedTask->campaign->title : ($lockedTask->task ? $lockedTask->task->title : 'Micro Task');
                \App\Models\Notification::send($lockedTask->user, 'Task Rejected ❌', "Your submission for '{$taskTitle}' was rejected. Reason: {$request->admin_note}", 'danger', '/tasks-history');
            }

            if ($lockedTask->campaign_id) {
                $isCampaign = true;
            }
            
            return true;
        });

        if (!$success) {
            return back()->withErrors(['message' => 'Task review has already been processed concurrently.']);
        }

        // Delete proof images on rejection for standard admin tasks
        if (!$isCampaign) {
            $this->storageSaverService->deleteUserTaskScreenshots($userTask);
        }

        return back()->with('success', 'Task rejected. User health decreased by 10.');
    }
}
