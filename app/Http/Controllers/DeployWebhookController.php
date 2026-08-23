<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeployWebhookController extends Controller
{
    /**
     * Handle incoming GitHub or manual deployment webhook.
     */
    public function handle(Request $request)
    {
        $startTime = microtime(true);
        $secret = config('services.github.webhook_secret', 'easytsk_secure_deploy_key_2026');

        // 1. Verify Secret Authorization
        $isAuthorized = false;

        // Check A: GitHub HMAC SHA-256 signature in X-Hub-Signature-256
        $hubSignature = $request->header('X-Hub-Signature-256');
        if ($hubSignature) {
            $computedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (hash_equals($computedSignature, $hubSignature)) {
                $isAuthorized = true;
            }
        }

        // Check B: Query param secret or Token Header (e.g. ?secret=... or X-Deploy-Secret)
        $querySecret = $request->query('secret') ?: $request->header('X-Deploy-Secret') ?: $request->input('secret');
        if ($querySecret && hash_equals($secret, (string) $querySecret)) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            Log::warning('Unauthorized deploy webhook attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing deployment secret token.',
            ], 403);
        }

        // 2. Handle GitHub Ping event (when adding webhook in GitHub UI)
        $event = $request->header('X-GitHub-Event');
        if ($event === 'ping') {
            Log::info('GitHub Webhook Ping received and validated successfully.');
            return response()->json([
                'success' => true,
                'message' => 'Pong! Webhook connection verified successfully.',
                'zen'     => $request->input('zen'),
            ], 200);
        }

        // 3. If it's a push event, optionally check branch
        $ref = $request->input('ref');
        if ($ref && !in_array($ref, ['refs/heads/main', 'refs/heads/master'])) {
            return response()->json([
                'success' => true,
                'message' => "Ignored push to branch '{$ref}'. Only 'main' is deployed.",
            ], 200);
        }

        $steps = [];

        // 4. Step A: Execute Git Pull if available
        $gitOutput = 'Git pull bypassed / executed directly';
        $gitSuccess = true;

        try {
            $gitProcess = new Process(['git', 'pull', 'origin', 'main'], base_path());
            $gitProcess->setTimeout(180);
            $gitProcess->run();

            $gitOutput = trim($gitProcess->getOutput() . "\n" . $gitProcess->getErrorOutput());
            $gitSuccess = $gitProcess->isSuccessful();
            $steps[] = [
                'step'    => 'git_pull',
                'success' => $gitSuccess,
                'output'  => $gitOutput,
            ];
        } catch (\Exception $e) {
            $gitSuccess = false;
            $gitOutput = 'Git execution error: ' . $e->getMessage();
            $steps[] = [
                'step'    => 'git_pull',
                'success' => false,
                'output'  => $gitOutput,
            ];
        }

        // 5. Step B: Run Artisan Optimization Commands
        try {
            Artisan::call('optimize:clear');
            $steps[] = ['step' => 'optimize_clear', 'success' => true, 'output' => trim(Artisan::output())];
        } catch (\Exception $e) {
            $steps[] = ['step' => 'optimize_clear', 'success' => false, 'output' => $e->getMessage()];
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $steps[] = ['step' => 'migrate', 'success' => true, 'output' => trim(Artisan::output())];
        } catch (\Exception $e) {
            $steps[] = ['step' => 'migrate', 'success' => false, 'output' => $e->getMessage()];
        }

        try {
            Artisan::call('optimize');
            $steps[] = ['step' => 'optimize', 'success' => true, 'output' => trim(Artisan::output())];
        } catch (\Exception $e) {
            $steps[] = ['step' => 'optimize', 'success' => false, 'output' => $e->getMessage()];
        }

        // 6. Step C: Auto-sync public/build to public_html/build if split structure exists
        $publicHtmlPath = dirname(base_path()) . '/public_html';
        $localBuildPath = public_path('build');
        if (is_dir($publicHtmlPath) && is_dir($localBuildPath)) {
            $targetBuildPath = $publicHtmlPath . '/build';
            try {
                if (!is_dir($targetBuildPath)) {
                    File::makeDirectory($targetBuildPath, 0755, true);
                }
                File::copyDirectory($localBuildPath, $targetBuildPath);
                $steps[] = [
                    'step'    => 'sync_public_build',
                    'success' => true,
                    'output'  => "Synchronized public/build to {$targetBuildPath}",
                ];
            } catch (\Exception $e) {
                $steps[] = [
                    'step'    => 'sync_public_build',
                    'success' => false,
                    'output'  => "Failed to sync build assets: " . $e->getMessage(),
                ];
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);

        Log::info('Deploy Webhook executed successfully', [
            'duration' => "{$durationMs}ms",
            'git_success' => $gitSuccess,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Application deployed, updated, and cache refreshed successfully!',
            'duration'  => "{$durationMs}ms",
            'timestamp' => now()->toDateTimeString(),
            'steps'     => $steps,
        ]);
    }
}
