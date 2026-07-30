<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class AdminDeployController extends Controller
{
    /**
     * Allowlisted deployment commands.
     * Only these commands can be executed from the admin panel.
     */
    private array $allowedCommands = [
        'git_pull'           => ['git', 'pull', 'origin', 'main'],
        'git_pull_master'    => ['git', 'pull', 'origin', 'master'],
        'git_status'         => ['git', 'status'],
        'composer_install'   => ['composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'],
        'composer_update'    => ['composer', 'update', '--no-interaction'],
        'npm_install'        => ['npm', 'install'],
        'npm_build'          => ['npm', 'run', 'build'],
        'migrate'            => ['php', 'artisan', 'migrate', '--force'],
        'migrate_fresh'      => ['php', 'artisan', 'migrate:fresh', '--force', '--seed'],
        'cache_clear'        => ['php', 'artisan', 'cache:clear'],
        'config_cache'       => ['php', 'artisan', 'config:cache'],
        'route_cache'        => ['php', 'artisan', 'route:cache'],
        'view_cache'         => ['php', 'artisan', 'view:cache'],
        'optimize'           => ['php', 'artisan', 'optimize'],
        'optimize_clear'     => ['php', 'artisan', 'optimize:clear'],
        'queue_restart'      => ['php', 'artisan', 'queue:restart'],
        'storage_link'       => ['php', 'artisan', 'storage:link'],
        'down'               => ['php', 'artisan', 'down'],
        'up'                 => ['php', 'artisan', 'up'],
    ];

    public function index()
    {
        // Gather system info
        $gitLog = $this->getGitLog();
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();

        return Inertia::render('Admin/Deploy/Index', [
            'gitLog'          => $gitLog,
            'phpVersion'      => $phpVersion,
            'laravelVersion'  => $laravelVersion,
            'appEnv'          => config('app.env'),
            'appUrl'          => config('app.url'),
        ]);
    }

    public function runCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string|in:' . implode(',', array_keys($this->allowedCommands)),
        ]);

        $commandKey = $request->input('command');
        $commandArgs = $this->allowedCommands[$commandKey];

        $startTime = microtime(true);

        try {
            $process = new Process($commandArgs, base_path(), [
                'COMPOSER_HOME' => base_path('vendor'),
            ]);
            $process->setTimeout(300); // 5 minutes max
            $process->run();

            $duration = round((microtime(true) - $startTime) * 1000);
            $success = $process->isSuccessful();
            $output = $process->getOutput() ?: $process->getErrorOutput();

            // Log activity
            Log::info("Deploy command executed by Admin (ID: {$request->user()?->id})", [
                'command'  => implode(' ', $commandArgs),
                'success'  => $success,
                'duration' => $duration . 'ms',
                'admin_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success'  => $success,
                'output'   => $output,
                'command'  => implode(' ', $commandArgs),
                'duration' => $duration,
                'exitCode' => $process->getExitCode(),
            ]);
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            Log::error("Deploy command failed to run", [
                'command'  => implode(' ', $commandArgs),
                'error'    => $e->getMessage(),
                'admin_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success'  => false,
                'output'   => 'Error: ' . $e->getMessage(),
                'command'  => implode(' ', $commandArgs),
                'duration' => $duration,
                'exitCode' => 1,
            ], 500);
        }
    }

    private function getGitLog(): array
    {
        try {
            $process = new Process(['git', 'log', '--oneline', '-10'], base_path());
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                $lines = array_filter(explode("\n", trim($process->getOutput())));
                return array_values($lines);
            }
        } catch (\Exception $e) {
            // Git might not be available
        }

        return ['Git log unavailable'];
    }
}
