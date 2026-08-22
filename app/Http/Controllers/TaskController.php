<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\OfferwallLog;
use App\Models\Task;
use App\Models\UserTask;
use App\Services\GamificationService;
use App\Services\ReferralService;
use App\Services\StorageSaverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->with('task:id,title,reward_coins,type')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn(UserTask $ut) => [
                'id'          => $ut->id,
                'task_title'  => $ut->task?->title ?? 'Deleted Task',
                'task_type'   => $ut->task?->type ?? 'shortlink',
                'reward_coins'=> (float) ($ut->task?->reward_coins ?? 0),
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

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'userLevel' => $user->level,
            'offerwalls' => $offerwalls,
            'is_locked' => $isLocked,
            'pending_tasks_count' => $pendingTasksCount,
            'health_gate_active' => $user->isHealthGateActive(),
            'health_gate_expires_at' => $user->isHealthGateActive()
                ? $user->health_depleted_at->addHours(24)->toIso8601String()
                : null,
            'taskHistory' => $taskHistory,
            'offerwallPendingHours' => AppSetting::offerwallPendingHours(),
            'offerwallLogs' => $offerwallLogs,
            'offerwallStats' => $offerwallStats,
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

        if ($task->type === 'secret_code' && !empty($task->secret_code)) {
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

        if ($task->type === 'secret_code' && !empty($task->secret_code)) {
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
            ->with('task:id,title,reward_coins,type')
            ->latest()
            ->paginate(15)
            ->through(fn(UserTask $ut) => [
                'id'          => $ut->id,
                'task_title'  => $ut->task?->title ?? 'Deleted Task',
                'task_type'   => $ut->task?->type ?? 'shortlink',
                'reward_coins'=> (float) ($ut->task?->reward_coins ?? 0),
                'status'      => $ut->status,
                'admin_note'  => $ut->admin_note,
                'submitted_at'=> $ut->created_at->format('M d, Y · H:i'),
            ]);

        return Inertia::render('Tasks/History', [
            'taskHistory' => $taskHistory,
        ]);
    }
}
