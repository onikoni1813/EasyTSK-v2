<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DeploySyncCommand extends Command
{
    protected $signature = 'deploy:sync 
                            {--migrate : Run database migrations automatically}
                            {--install-hook : Automatically install Git post-merge hook so git pull runs this command}';

    protected $description = 'Sync compiled Vite assets and public files to public_html, clear caches, and ensure zero-downtime updates';

    public function handle(): int
    {
        // 1. If --install-hook is requested, configure Git to use .githooks
        if ($this->option('install-hook')) {
            return $this->installGitHook();
        }

        $this->info('🚀 Starting EasyTSK Deployment & Public Sync...');

        // 2. Identify public_html directory
        $possiblePaths = [
            dirname(base_path()) . '/public_html',
            '/home/easytskc/public_html',
        ];

        $publicHtml = null;
        foreach ($possiblePaths as $p) {
            if (is_dir($p) && realpath($p) !== realpath(base_path('public'))) {
                $publicHtml = realpath($p);
                break;
            }
        }

        if ($publicHtml) {
            $this->line("📁 Target public directory: <comment>{$publicHtml}</comment>");

            // 3. Sync compiled Vite build folder
            $repoBuild = base_path('public/build');
            $publicHtmlBuild = $publicHtml . '/build';

            if (is_dir($repoBuild)) {
                if (!is_dir($publicHtmlBuild)) {
                    @mkdir($publicHtmlBuild, 0755, true);
                }

                try {
                    File::copyDirectory($repoBuild, $publicHtmlBuild);
                    $this->info('✓ Synced compiled Vite assets to public_html/build');
                } catch (\Throwable $e) {
                    $this->warn('⚠️ Failed to copy Vite build directory: ' . $e->getMessage());
                }
            }

            // 4. Sync root assets
            $syncFiles = [
                'favicon.svg',
                'favicon.ico',
                'manifest.json',
                'sw.js',
                'icon-192.png',
                'icon-512.png',
                'robots.txt',
                'deploy_hook.php',
                'deploy_clean.php',
            ];

            foreach ($syncFiles as $file) {
                $src = base_path('public/' . $file);
                $dst = $publicHtml . '/' . $file;
                if (file_exists($src)) {
                    @copy($src, $dst);
                }
            }
            $this->info('✓ Synced root public assets to public_html');

            // 5. Check & adapt public_html/index.php paths if necessary
            $this->adaptPublicHtmlIndex($publicHtml);
        } else {
            $this->comment('ℹ️ Standard monolithic setup detected (public folder is inside repository root).');
        }

        // 6. Run database migrations if requested
        if ($this->option('migrate')) {
            $this->line('📦 Checking database migrations...');
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->info(trim(Artisan::output()) ?: '✓ Database migrations up to date.');
            } catch (\Throwable $e) {
                $this->error('Migration error: ' . $e->getMessage());
            }
        }

        // 7. Clear Laravel application caches
        $this->line('🧹 Clearing Laravel caches (route, config, view, optimize)...');
        try {
            Artisan::call('optimize:clear');
            $this->info('✓ All application caches cleared successfully.');
        } catch (\Throwable $e) {
            $this->warn('Cache clear warning: ' . $e->getMessage());
        }

        $this->info('🎉 EasyTSK deployment & public_html sync completed successfully!');

        return self::SUCCESS;
    }

    private function adaptPublicHtmlIndex(string $publicHtml): void
    {
        $indexPath = $publicHtml . '/index.php';
        if (!file_exists($indexPath)) {
            return;
        }

        $content = file_get_contents($indexPath);
        $repoName = basename(base_path());

        // Ensure autoload and app paths point correctly to the project root
        $needsRewrite = false;
        if (str_contains($content, "dirname(__DIR__).'/vendor/autoload.php'") || str_contains($content, "'/../vendor/autoload.php'")) {
            $content = str_replace(
                ["dirname(__DIR__).'/vendor/autoload.php'", "'/../vendor/autoload.php'"],
                ["dirname(__DIR__).'/{$repoName}/vendor/autoload.php'", "'/../{$repoName}/vendor/autoload.php'"],
                $content
            );
            $needsRewrite = true;
        }

        if (str_contains($content, "dirname(__DIR__).'/bootstrap/app.php'") || str_contains($content, "'/../bootstrap/app.php'")) {
            $content = str_replace(
                ["dirname(__DIR__).'/bootstrap/app.php'", "'/../bootstrap/app.php'"],
                ["dirname(__DIR__).'/{$repoName}/bootstrap/app.php'", "'/../{$repoName}/bootstrap/app.php'"],
                $content
            );
            $needsRewrite = true;
        }

        if ($needsRewrite) {
            @file_put_contents($indexPath, $content);
            $this->info("✓ Adjusted public_html/index.php paths to point to /../{$repoName}");
        }
    }

    private function installGitHook(): int
    {
        $hooksDir = base_path('.githooks');
        if (!is_dir($hooksDir)) {
            @mkdir($hooksDir, 0755, true);
        }

        $postMergeHook = $hooksDir . '/post-merge';
        $hookScript = <<<'BASH'
#!/bin/bash
# Automatically executed by Git after every git pull or git merge
echo ""
echo "🚀 [EasyTSK Auto-Deploy] Git pull completed. Syncing public_html & clearing caches..."
if [ -f "artisan" ]; then
    php artisan deploy:sync --migrate
else
    echo "⚠️ artisan not found in current directory."
fi
echo "✨ [EasyTSK Auto-Deploy] All public assets & caches updated successfully!"
echo ""
BASH;

        file_put_contents($postMergeHook, str_replace("\r\n", "\n", $hookScript));
        @chmod($postMergeHook, 0755);

        // Configure git to use .githooks
        exec('git config core.hooksPath .githooks');

        $this->info("✅ Git post-merge hook installed successfully in {$postMergeHook}!");
        $this->info("👉 From now on, whenever you run 'git pull', it will automatically sync public_html and clear caches!");

        return self::SUCCESS;
    }
}
