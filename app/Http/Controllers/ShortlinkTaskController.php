<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\ShortlinkSession;
use App\Models\Task;
use App\Models\UserTask;
use App\Services\GamificationService;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShortlinkTaskController extends Controller
{
    protected GamificationService $gamificationService;
    protected ReferralService $referralService;

    public function __construct(GamificationService $gamificationService, ReferralService $referralService)
    {
        $this->gamificationService = $gamificationService;
        $this->referralService = $referralService;
    }

    /**
     * Generate dynamic shortlink for a specific user and task via Shortener API.
     */
    public function start(Request $request, Task $task): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($task->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'This task is currently inactive.'], 422);
        }

        // Check cooldown / daily limits
        if ($task->cooldown_hours > 0) {
            $recentCompleted = UserTask::where('user_id', $user->id)
                ->where('task_id', $task->id)
                ->where('status', 'approved')
                ->where('created_at', '>=', now()->subHours($task->cooldown_hours))
                ->first();

            if ($recentCompleted) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already completed this shortlink. Please wait {$task->cooldown_hours} hours before trying again.",
                ], 429);
            }
        }

        // Check health
        if ($user->health <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Your Health (HP) is 0. Please wait for health recovery before doing tasks.',
            ], 403);
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('shortlink_sessions')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 1. Generate unique session token
        $token = Str::random(48);
        $session = ShortlinkSession::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'token' => $token,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        // 2. Build Destination Callback URL
        $callbackUrl = url('/tasks/shortlink/verify/' . $token);

        // 3. Call Shortener API if API details configured
        $apiEndpoint = trim($task->target_url ?? '');
        $apiKey = trim($task->secret_code ?? '');

        // If apiKey is empty on the task, look up from ShortlinkProvider table
        if (empty($apiKey)) {
            $provider = \App\Models\ShortlinkProvider::where('name', $task->provider_name)
                ->orWhere('api_url', $apiEndpoint)
                ->orWhere('slug', Str::slug($task->provider_name ?? ''))
                ->first();

            if ($provider && $provider->is_active && !empty($provider->api_key)) {
                $apiKey = $provider->api_key;
                if (empty($apiEndpoint)) {
                    $apiEndpoint = $provider->api_url;
                }
            }
        }

        // If target_url looks like an API endpoint (e.g. https://shrinkme.io/api)
        if (!empty($apiEndpoint) && !empty($apiKey) && str_contains($apiEndpoint, '/api')) {
            try {
                $response = Http::timeout(10)->get($apiEndpoint, [
                    'api' => $apiKey,
                    'url' => $callbackUrl,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $shortenedUrl = $json['shortenedUrl'] ?? $json['url'] ?? $json['short'] ?? null;

                    if (!empty($shortenedUrl)) {
                        return response()->json([
                            'success' => true,
                            'shortened_url' => $shortenedUrl,
                            'token' => $token,
                            'provider' => $task->provider_name ?? 'Shortlink',
                        ]);
                    }
                }

                $err = $response->json('message') ?? 'Shortlink provider API error. Please try again.';
                return response()->json(['success' => false, 'message' => $err], 502);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not connect to shortlink provider API. Please try again.',
                ], 504);
            }
        }

        // If target_url is already a fixed direct shortlink (fallback mode)
        if (!empty($apiEndpoint)) {
            return response()->json([
                'success' => true,
                'shortened_url' => $apiEndpoint,
                'token' => $token,
                'provider' => $task->provider_name ?? 'Shortlink',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Shortlink task is not properly configured with an API Key or URL.',
        ], 422);
    }

    /**
     * Auto-Verify and credit reward upon returning from shortlink.
     */
    public function verify(Request $request, string $token)
    {
        $session = ShortlinkSession::where('token', $token)->first();

        if (!$session) {
            return redirect('/tasks')->withErrors(['message' => 'Invalid or expired shortlink session.']);
        }

        if ($session->status === 'completed') {
            return redirect('/tasks')->with('warning', 'This shortlink reward has already been claimed.');
        }

        if ($session->isExpired()) {
            $session->update(['status' => 'expired']);
            return redirect('/tasks')->withErrors(['message' => 'Shortlink session expired. Please start the task again.']);
        }

        // Anti-bypass minimum time elapsed check (at least 5 seconds)
        if ($session->started_at && (now()->timestamp - $session->started_at->timestamp) < 5) {
            return redirect('/tasks')->withErrors(['message' => 'Verification failed: Shortlink was bypassed too fast.']);
        }

        $user = $session->user;
        $task = $session->task;

        if (!$user || !$task) {
            return redirect('/tasks')->withErrors(['message' => 'Task or user not found.']);
        }

        // Execute reward distribution in DB transaction
        DB::transaction(function () use ($user, $task, $session) {
            // Mark session as completed
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create approved UserTask record
            UserTask::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'status' => 'approved',
                'submitted_data' => [
                    'provider' => $task->provider_name ?? 'Shortlink',
                    'session_token' => $session->token,
                    'verified_via' => 'api_direct_callback',
                ],
            ]);

            // Award Coins and XP
            $reward = (float) $task->reward_coins * AppSetting::rewardMultiplier();
            $user->addMainBalance($reward);
            $this->gamificationService->awardXp($user, $task->reward_xp);
            $this->referralService->recordReferredUserEarning($user, $reward);
            $user->addHealth(1);
        });

        $coinsFormatted = number_format((float) $task->reward_coins, 2);
        return redirect('/tasks')->with('success', "🎉 Awesome! Shortlink completed successfully! +{$coinsFormatted} Coins and +{$task->reward_xp} XP added to your balance.");
    }
}
