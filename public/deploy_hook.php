<?php
/**
 * Standalone Emergency GitHub Webhook Deployer for EasyTSK v2
 * Works independently of Laravel framework boot state.
 */

header('Content-Type: application/json');

$secret = 'easytsk_secure_deploy_key_2026';

// 1. Verify Authentication
$isAuthorized = false;

$hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$rawBody = file_get_contents('php://input');

if ($hubSignature) {
    $computed = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
    if (hash_equals($computed, $hubSignature)) {
        $isAuthorized = true;
    }
}

$querySecret = $_GET['secret'] ?? $_POST['secret'] ?? ($_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '');
if ($querySecret && hash_equals($secret, (string)$querySecret)) {
    $isAuthorized = true;
}

if (!$isAuthorized) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Invalid deploy secret.',
    ]);
    exit;
}

// 2. Handle GitHub Ping
if (($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '') === 'ping') {
    echo json_encode(['success' => true, 'message' => 'Pong! Standalone hook connected.']);
    exit;
}

$projectPath = dirname(__DIR__); // default parent
// If in cPanel where project is in /easytsk v2
$possiblePaths = [
    '/home/easytskc/easytsk-v2',
    dirname(dirname(__DIR__)) . '/easytsk-v2',
    dirname(__DIR__) . '/easytsk-v2',
    dirname(dirname(__DIR__)) . '/easytsk v2',
    dirname(__DIR__) . '/easytsk v2',
    dirname(__DIR__),
];

$validProjectRoot = null;
foreach ($possiblePaths as $p) {
    if (file_exists($p . '/artisan')) {
        $validProjectRoot = realpath($p);
        break;
    }
}

if (!$validProjectRoot) {
    $validProjectRoot = realpath(dirname(__DIR__));
}

// 3. Run Git Pull if git is available
$gitOutput = '';
$syncOutput = '';
if (function_exists('shell_exec')) {
    $gitOutput = shell_exec("cd " . escapeshellarg($validProjectRoot) . " && git pull origin main 2>&1");
    $syncOutput = shell_exec("cd " . escapeshellarg($validProjectRoot) . " && php artisan deploy:sync --migrate 2>&1");
}

// 4. Fallback manual clear of Laravel bootstrap/cache files directly
$cacheDir = $validProjectRoot . '/bootstrap/cache';
$clearedFiles = [];
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.php');
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore') {
            @unlink($file);
            $clearedFiles[] = basename($file);
        }
    }
}

echo json_encode([
    'success'       => true,
    'message'       => 'EasyTSK auto-deploy executed successfully!',
    'project_root'  => $validProjectRoot,
    'git_output'    => trim((string)$gitOutput),
    'sync_output'   => trim((string)$syncOutput),
    'cache_cleared' => $clearedFiles,
    'timestamp'     => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);
