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
        'git_pull_current'   => ['git', 'pull'],
        'git_status'         => ['git', 'status'],
        'composer_install'   => ['composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'],
        'composer_update'    => ['composer', 'update', '--no-interaction'],
        'npm_install'        => ['npm', 'install'],
        'npm_build'          => ['npm', 'run', 'build'],
        'migrate'            => ['php', 'artisan', 'migrate', '--force'],
        'migrate_fresh'      => ['php', 'artisan', 'migrate:fresh', '--force', '--seed'],
        'db_seed'            => ['php', 'artisan', 'db:seed', '--force'],
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

    public function index(Request $request)
    {
        // Gather system & admin info
        $gitLog = $this->getGitLog();
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $user = $request->user();

        return Inertia::render('Admin/Deploy/Index', [
            'gitLog'          => $gitLog,
            'phpVersion'      => $phpVersion,
            'laravelVersion'  => $laravelVersion,
            'appEnv'          => config('app.env'),
            'appUrl'          => config('app.url'),
            'serverOs'        => PHP_OS_FAMILY,
            'isMaintenance'   => app()->isDownForMaintenance(),
            'adminUser'       => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ] : null,
        ]);
    }

    private array $artisanCommands = [
        'migrate'         => ['command' => 'migrate', 'params' => ['--force' => true]],
        'migrate_fresh'   => ['command' => 'migrate:fresh', 'params' => ['--force' => true, '--seed' => true]],
        'db_seed'         => ['command' => 'db:seed', 'params' => ['--force' => true]],
        'cache_clear'     => ['command' => 'cache:clear', 'params' => []],
        'config_cache'    => ['command' => 'config:cache', 'params' => []],
        'route_cache'     => ['command' => 'route:cache', 'params' => []],
        'view_cache'      => ['command' => 'view:cache', 'params' => []],
        'optimize'        => ['command' => 'optimize', 'params' => []],
        'optimize_clear'  => ['command' => 'optimize:clear', 'params' => []],
        'queue_restart'   => ['command' => 'queue:restart', 'params' => []],
        'storage_link'    => ['command' => 'storage:link', 'params' => []],
        'down'            => ['command' => 'down', 'params' => []],
        'up'              => ['command' => 'up', 'params' => []],
    ];

    public function runCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string|in:' . implode(',', array_keys($this->allowedCommands)),
        ]);

        $commandKey = $request->input('command');
        $startTime = microtime(true);

        // If it's an artisan command, execute directly via Artisan::call() to bypass proc_open/terminal restrictions
        if (isset($this->artisanCommands[$commandKey])) {
            try {
                $info = $this->artisanCommands[$commandKey];
                Artisan::call($info['command'], $info['params']);
                $output = trim(Artisan::output());
                if (empty($output)) {
                    $output = "Command 'artisan {$info['command']}' executed successfully.";
                }

                $duration = round((microtime(true) - $startTime) * 1000);

                Log::info("Deploy Artisan command executed by Admin (ID: {$request->user()?->id})", [
                    'command'  => 'artisan ' . $info['command'],
                    'success'  => true,
                    'duration' => $duration . 'ms',
                ]);

                return response()->json([
                    'success'  => true,
                    'output'   => $output,
                    'command'  => 'php artisan ' . $info['command'],
                    'duration' => $duration,
                    'exitCode' => 0,
                ]);
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $startTime) * 1000);
                return response()->json([
                    'success'  => false,
                    'output'   => 'Artisan Error: ' . $e->getMessage(),
                    'command'  => 'php artisan ' . $commandKey,
                    'duration' => $duration,
                    'exitCode' => 1,
                ], 500);
            }
        }

        $commandArgs = $this->allowedCommands[$commandKey];

        // Ensure 'php' uses current PHP_BINARY for max compatibility
        if (isset($commandArgs[0]) && $commandArgs[0] === 'php') {
            $commandArgs[0] = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
        }

        try {
            $process = new Process($commandArgs, base_path(), [
                'COMPOSER_HOME' => base_path('vendor'),
            ]);
            $process->setTimeout(300); // 5 minutes max
            $process->run();

            $duration = round((microtime(true) - $startTime) * 1000);
            $success = $process->isSuccessful();
            
            // Combine stdout and stderr outputs for complete console output
            $stdout = trim($process->getOutput());
            $stderr = trim($process->getErrorOutput());
            
            $output = $stdout;
            if (!empty($stderr)) {
                $output = $output ? ($output . "\n\n[STDERR / INFO]:\n" . $stderr) : $stderr;
            }

            // Log activity
            Log::info("Deploy process command executed by Admin (ID: {$request->user()?->id})", [
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
