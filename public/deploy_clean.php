<?php
/**
 * Direct Browser Deploy & Cache Clearing Helper
 * Access via: https://yourdomain.com/deploy_clean.php?secret=easytsk_secure_2026
 */

$requiredSecret = 'easytsk_secure_2026';
$providedSecret = $_GET['secret'] ?? '';

if ($providedSecret !== $requiredSecret) {
    http_response_code(403);
    die('<h1>403 Forbidden: Invalid Secret Key</h1>');
}

header('Content-Type: text/html; charset=utf-8');
echo '<body style="font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px;">';
echo '<h2 style="color: #10b981;">🚀 EasyTSK Live Maintenance & Cache Clearer</h2>';

$corePaths = [
    __DIR__ . '/../easytsk v2',
    __DIR__ . '/..',
    dirname(__DIR__),
    '/home/easytskc/easytsk v2',
];

$coreBase = null;
foreach ($corePaths as $p) {
    if (file_exists($p . '/artisan')) {
        $coreBase = realpath($p);
        break;
    }
}

if (!$coreBase) {
    echo '<p style="color: #ef4444;">❌ Could not locate core project base path automatically.</p>';
} else {
    echo "<p>📁 Located Project Base: <strong>{$coreBase}</strong></p>";

    // Find git clone repository directory if separate
    $repoDir = null;
    $repoBaseParent = is_dir('/home/easytskc/repositories') ? '/home/easytskc/repositories' : (is_dir(dirname($coreBase) . '/repositories') ? dirname($coreBase) . '/repositories' : null);
    
    if ($repoBaseParent) {
        $dirs = glob($repoBaseParent . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $d) {
            if (file_exists($d . '/.cpanel.yml') || file_exists($d . '/artisan')) {
                $repoDir = realpath($d);
                break;
            }
        }
    }

    if (!$repoDir) {
        $repoCandidates = [
            '/home/easytskc/repositories/EasyTSK-v2',
            '/home/easytskc/repositories/easytsk-v2',
            '/home/easytskc/repositories/easytsk v2',
            dirname($coreBase) . '/repositories/EasyTSK-v2',
            dirname($coreBase) . '/repositories/easytsk-v2',
        ];
        foreach ($repoCandidates as $rc) {
            if (is_dir($rc) && (file_exists($rc . '/.cpanel.yml') || file_exists($rc . '/artisan'))) {
                $repoDir = realpath($rc);
                break;
            }
        }
    }

    if ($repoDir) {
        echo "<p style=\"color: #a78bfa;\">📦 Found Git Repository at: <strong>{$repoDir}</strong></p>";
        $syncDirs = ['app', 'bootstrap', 'config', 'routes', 'resources', 'database', 'public'];
        
        $copyRecursive = function ($src, $dst) use (&$copyRecursive) {
            $dir = opendir($src);
            @mkdir($dst, 0755, true);
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $copyRecursive($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        };

        foreach ($syncDirs as $sd) {
            $srcPath = $repoDir . '/' . $sd;
            $dstPath = $coreBase . '/' . $sd;
            if (is_dir($srcPath)) {
                $copyRecursive($srcPath, $dstPath);
                echo "<p style=\"color: #38bdf8;\">✓ Synced {$sd} to core</p>";
            }
        }

        // Sync public build to public_html
        $publicHtml = '/home/easytskc/public_html';
        if (is_dir($publicHtml) && is_dir($repoDir . '/public')) {
            $copyRecursive($repoDir . '/public', $publicHtml);
            echo "<p style=\"color: #38bdf8;\">✓ Synced public assets to public_html</p>";
        }
    }

    // 1. Manually delete bootstrap cache files
    $cacheDir = $coreBase . '/bootstrap/cache';
    if (is_dir($cacheDir)) {
        $filesToClean = ['routes-v7.php', 'config.php', 'packages.php', 'services.php'];
        foreach ($filesToClean as $f) {
            $fp = $cacheDir . '/' . $f;
            if (file_exists($fp)) {
                @unlink($fp);
                echo "<p style=\"color: #38bdf8;\">🧹 Deleted cache file: {$f}</p>";
            }
        }
    }

    // 2. Boot Laravel and run artisan optimize:clear and migrate
    try {
        require_once $coreBase . '/vendor/autoload.php';
        $app = require_once $coreBase . '/bootstrap/app.php';
        
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $optimizeOutput = \Illuminate\Support\Facades\Artisan::output();
        echo "<pre style=\"background: #1e293b; padding: 12px; border-radius: 8px;\">" . htmlspecialchars($optimizeOutput) . "</pre>";

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();
        echo "<pre style=\"background: #1e293b; padding: 12px; border-radius: 8px;\">" . htmlspecialchars($migrateOutput) . "</pre>";

        echo '<h3 style="color: #34d399;">✅ Everything updated, all caches cleared, and database migrations applied successfully!</h3>';
        echo '<p>You can now go back to <a href="/secret-panel/notifications" style="color: #818cf8;">Admin Notifications</a> and send updates.</p>';
    } catch (\Throwable $e) {
        echo '<p style="color: #ef4444;">❌ Error running Artisan: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '</body>';
