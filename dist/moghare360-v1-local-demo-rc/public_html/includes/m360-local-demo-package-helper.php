<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11 — Local demo package manifest (read-only; zip via PowerShell only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-lock-helper.php';

const M360_LOCAL_PACKAGE_DIR = 'dist/moghare360-v1-local-demo-rc';
const M360_LOCAL_PACKAGE_ZIP = 'dist/moghare360-v1-local-demo-rc.zip';
const M360_LOCAL_PACKAGE_SCRIPT = 'tools/package-moghare360-v1-local-demo.ps1';

/**
 * @return list<string>
 */
function m360_local_package_include_patterns(): array
{
    return [
        'public_html/*.php',
        'public_html/includes/m360-*.php',
        'public_html/includes/erp-*.php',
        'public_html/includes/moghare360-*.php',
        'public_html/api/**/*.php',
        'public_html/assets/css/m360-*.css',
        'public_html/assets/js/m360-*.js',
        'public_html/assets/moghare360-ui/*.css',
        'database/migrations/P*.sql',
        'docs/release/*',
        'docs/demo/*',
        'docs/missions/**/*',
        'tools/test-p*.php',
        'tools/package-moghare360-v1-local-demo.ps1',
    ];
}

/**
 * @return list<string>
 */
function m360_local_package_exclude_rules(): array
{
    return [
        'private/',
        '.env',
        '.env.*',
        'config.php',
        'mirror-config.php',
        'erp-config.php',
        '*.bak',
        '*.backup',
        '*.tmp',
        '*.log',
        '*.zip',
        'node_modules/',
        'vendor/',
        '.git/',
        '.github/',
        'uploads/',
        'cache/',
        'temp/',
        'session/',
        'release/',
        'dist/',
    ];
}

/**
 * @return list<string>
 */
function m360_local_package_credential_scan_patterns(): array
{
    return [
        'api_key',
        'api-key',
        'password',
        'secret',
        'bearer',
        'token',
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_local_package_status(): array
{
    $root = m360_release_lock_root();
    $scriptPath = $root . '/' . M360_LOCAL_PACKAGE_SCRIPT;
    $distDir = $root . '/' . M360_LOCAL_PACKAGE_DIR;
    $zipPath = $root . '/' . M360_LOCAL_PACKAGE_ZIP;
    $manifestPath = $distDir . '/MANIFEST.txt';
    $hashPath = $distDir . '/PACKAGE_SHA256.txt';

    $blockers = [];
    $warnings = [];
    if (!is_file($scriptPath)) {
        $blockers[] = 'Package script not found';
    }

    $sha256 = null;
    if (is_file($hashPath)) {
        $sha256 = trim((string)file_get_contents($hashPath));
    } elseif (is_file($zipPath)) {
        $warnings[] = 'Zip exists but PACKAGE_SHA256.txt missing';
    }

    $built = is_dir($distDir) && is_file($manifestPath);
    $zipped = is_file($zipPath);

    return [
        'package_script' => M360_LOCAL_PACKAGE_SCRIPT,
        'dist_dir' => M360_LOCAL_PACKAGE_DIR,
        'zip_path' => M360_LOCAL_PACKAGE_ZIP,
        'script_exists' => is_file($scriptPath),
        'dist_built' => $built,
        'zip_built' => $zipped,
        'manifest_exists' => is_file($manifestPath),
        'sha256' => $sha256,
        'include_patterns' => m360_local_package_include_patterns(),
        'exclude_rules' => m360_local_package_exclude_rules(),
        'scan_patterns' => m360_local_package_credential_scan_patterns(),
        'ui_builds_zip' => false,
        'blockers' => $blockers,
        'warnings' => $warnings,
        'status' => $blockers === [] ? ($zipped ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING) : M360_RC_STATUS_BLOCKED,
    ];
}
