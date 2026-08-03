<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * Idempotent daily contract-expiry reminder generator.
 * Usage: C:\xampp\php\php.exe tools\p360hr-reminder-generate.php
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/public_html/peopleos360/includes/p360-hr-occ-med-manager.php';

$res = p360hr_reminder_refresh_all_r1();
echo 'REMINDER_OK created=' . (int)$res['created'] . ' updated=' . (int)$res['updated'] . ' missing_manager=' . (int)($res['missing_manager_count'] ?? 0) . "\n";
