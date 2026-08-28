<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Site;
use App\Models\TaskCode;
use App\Services\SiteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskRewardController extends Controller
{
    /**
     * Initialize reading session timer when user opens an article.
     */
    public function startSession(Request $request, SiteContext $siteContext): JsonResponse
    {
        $site = $siteContext->get();
        if (!$site) {
            $siteId = $request->input('site_id');
            $site = $siteId ? Site::find($siteId) : Site::where('is_active', true)->first();
        }

        if (!$site || !$site->task_reward_enabled) {
            return response()->json([
                'enabled' => false,
                'message' => 'Task reward is not enabled on this site.',
            ]);
        }

        $sessionToken = Str::random(40);
        $timerSeconds = $site->task_timer_seconds ?: 60;
        $ipHash = hash('sha256', ($request->ip() ?? '127.0.0.1') . date('Y-m-d'));

        // Pre-create initial tracking record
        TaskCode::create([
            'site_id' => $site->id,
            'post_id' => $request->input('post_id'),
            'session_token' => $sessionToken,
            'code' => 'PENDING_' . Str::upper(Str::random(10)),
            'ip_hash' => $ipHash,
            'dwell_time_seconds' => $timerSeconds,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'is_used' => false,
        ]);

        return response()->json([
            'enabled' => true,
            'session_token' => $sessionToken,
            'timer_seconds' => $timerSeconds,
            'adblock_detection' => (bool) $site->adblock_detection_enabled,
        ]);
    }

    /**
     * Issue one-time secret code once the required dwell time (e.g. 60s) has passed.
     */
    public function claimCode(Request $request, SiteContext $siteContext): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string|max:64',
            'post_id' => 'nullable|integer',
        ]);

        $site = $siteContext->get();
        $token = $request->input('session_token');

        $taskRecord = TaskCode::withoutGlobalScopes()
            ->where('session_token', $token)
            ->first();

        if (!$taskRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired task session.',
            ], 403);
        }

        if ($taskRecord->generated_at !== null && !str_starts_with($taskRecord->code, 'PENDING_')) {
            // Already generated, return existing code if not expired
            if ($taskRecord->isExpired()) {
                return response()->json(['success' => false, 'message' => 'Secret code expired.'], 410);
            }
            return response()->json([
                'success' => true,
                'code' => $taskRecord->code,
                'expires_at' => $taskRecord->expires_at->toIso8601String(),
            ]);
        }

        // Server-Side Anti-Cheat Dwell Time Validation
        $elapsedSeconds = $taskRecord->started_at ? abs(now()->diffInSeconds($taskRecord->started_at)) : 0;
        $requiredSeconds = $taskRecord->dwell_time_seconds ?: 60;

        // Allow 3 seconds tolerance for network latency
        if ($elapsedSeconds < ($requiredSeconds - 3)) {
            return response()->json([
                'success' => false,
                'message' => "Anti-cheat: You must read for at least {$requiredSeconds} seconds before requesting reward code. Elapsed: {$elapsedSeconds}s.",
            ], 422);
        }

        // Check if a fixed secret code is specified on the post or site
        $post = $taskRecord->post;
        if ($post && !empty($post->fixed_secret_code)) {
            $uniqueCode = trim($post->fixed_secret_code);
        } elseif ($site && !empty($site->fixed_secret_code)) {
            $uniqueCode = trim($site->fixed_secret_code);
        } else {
            // Generate unique, readable dynamic code e.g. TSK-9A4B2C
            $uniqueCode = 'TSK-' . strtoupper(Str::random(6));
            while (TaskCode::withoutGlobalScopes()->where('code', $uniqueCode)->exists()) {
                $uniqueCode = 'TSK-' . strtoupper(Str::random(6));
            }
        }

        $taskRecord->update([
            'code' => $uniqueCode,
            'generated_at' => now(),
            'expires_at' => now()->addMinutes(15), // Valid for 15 minutes
        ]);

        return response()->json([
            'success' => true,
            'code' => $uniqueCode,
            'expires_at' => $taskRecord->expires_at->toIso8601String(),
            'message' => 'Secret code generated! Submit this on EasyTSK to complete your task.',
        ]);
    }

    /**
     * API Endpoint for Main Site (EasyTSK) to verify and claim the secret code.
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $code = trim($request->input('code', ''));

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Code is required.',
            ], 400);
        }

        $taskRecord = TaskCode::withoutGlobalScopes()
            ->with(['post', 'site'])
            ->where('code', $code)
            ->first();

        if (!$taskRecord || str_starts_with($taskRecord->code, 'PENDING_')) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Invalid secret code. Please ensure you copied the exact code.',
            ], 404);
        }

        if ($taskRecord->is_used) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'This code has already been submitted and used.',
                'used_at' => $taskRecord->used_at?->toIso8601String(),
            ], 409);
        }

        if ($taskRecord->isExpired()) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'This code has expired (15-minute time limit exceeded).',
            ], 410);
        }

        // Mark as used (Single-use lock)
        $taskRecord->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Secret code successfully verified and approved!',
            'data' => [
                'code' => $taskRecord->code,
                'site_id' => $taskRecord->site_id,
                'site_name' => $taskRecord->site?->name,
                'site_subdomain' => $taskRecord->site?->subdomain,
                'post_id' => $taskRecord->post_id,
                'post_title' => $taskRecord->post?->title,
                'dwell_time_seconds' => $taskRecord->dwell_time_seconds,
                'verified_at' => now()->toIso8601String(),
            ]
        ]);
    }
}
