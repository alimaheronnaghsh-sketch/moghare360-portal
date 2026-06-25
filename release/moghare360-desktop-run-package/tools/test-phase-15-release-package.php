<?php
/**
 * MOGHARE360 ERP — Phase 15 Downloadable Release Package CLI Test
 */

declare(strict_types=1);

const P15_BUILT = [
    'public_html/erp-release-package-dashboard.php',
    'public_html/moghare360-release-download.php',
    'public_html/includes/moghare360-release-package-helper.php',
    'public_html/assets/moghare360-ui/moghare360-release-package.css',
    'tools/package-moghare360-local-release.ps1',
    'tools/package-moghare360-demo.ps1',
];

const P15_PHP_SYNTAX = [
    'public_html/erp-release-package-dashboard.php',
    'public_html/moghare360-release-download.php',
    'public_html/includes/moghare360-release-package-helper.php',
];

const P15_RELEASE_DOCS = [
    'docs/release/MOGHARE360_RELEASE_PACKAGE_MANIFEST.md',
    'docs/release/MOGHARE360_DEMO_PACKAGE_MANIFEST.md',
    'docs/release/MOGHARE360_LOCAL_RC1_RELEASE_NOTES.md',
];

const P15_MISSION_DOCS = [
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_00_INDEX.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_01_SCOPE.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_02_PACKAGE_BOUNDARIES.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_03_DEMO_PACKAGE.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_04_LOCAL_RELEASE_PACKAGE.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_05_SECURITY_EXCLUSIONS.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_06_RELEASE_NOTES.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_90_TEST_RESULT.md',
    'docs/missions/phase_15_downloadable_release_package/PHASE_15_99_SIGNOFF.md',
];

const P15_SCRIPT_EXCLUSIONS = [
    'private', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
    '.git', 'logs', 'uploads', 'backups', 'release', '*.bak', '*.log',
    'real customer data', 'Compress-Archive', 'tar -tf', 'FORBIDDEN CONTENT FOUND',
];

const P15_ZIP_FORBIDDEN_DIRS = ['private', 'logs', 'uploads', 'backups', 'release'];

const P15_ZIP_FORBIDDEN_FILES = [
    'config.php', 'config.example.php', 'erp-config.php', 'erp-config.example.php',
];

const P15_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

const P15_ZIP_PATHS = [
    'release/moghare360-demo-package.zip',
    'release/moghare360-local-rc1.zip',
];

function p15_root(): string { return dirname(__DIR__); }
function p15_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p15_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

function p15_normalize_zip_entry(string $entry): string
{
    return strtolower(str_replace('\\', '/', ltrim($entry, './')));
}

function p15_zip_entry_forbidden(string $entry): ?string
{
    $norm = p15_normalize_zip_entry($entry);
    if ($norm === '') {
        return null;
    }

    if (str_contains($norm, '.git/') || str_starts_with($norm, '.git/')) {
        return '.git/';
    }

    foreach (P15_ZIP_FORBIDDEN_DIRS as $dir) {
        if ($dir === 'release') {
            if (preg_match('#^release(/|$)#', $norm) || preg_match('#^public_html/release(/|$)#', $norm)) {
                return 'release/';
            }
            continue;
        }
        if (preg_match('#(^|/)' . preg_quote($dir, '#') . '(/|$)#', $norm)) {
            return $dir . '/';
        }
    }

    $leaf = basename($norm);
    foreach (P15_ZIP_FORBIDDEN_FILES as $file) {
        if ($leaf === strtolower($file)) {
            return $file;
        }
    }

    if (preg_match('/\.bak/i', $leaf)) {
        return '.bak';
    }
    if (preg_match('/\.log$/i', $leaf)) {
        return '.log';
    }
    if (preg_match('/\.tmp$/i', $leaf)) {
        return '.tmp';
    }

    return null;
}

/** @return list<string> */
function p15_inspect_zip(string $zipPath): array
{
    if (!is_file($zipPath)) {
        return [];
    }

    $violations = [];
    if (!class_exists('ZipArchive')) {
        $out = [];
        $code = 0;
        exec('tar -tf ' . escapeshellarg($zipPath) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            return ['<tar failed>'];
        }
        foreach ($out as $entry) {
            $match = p15_zip_entry_forbidden((string)$entry);
            if ($match !== null) {
                $violations[] = (string)$entry . ' -> ' . $match;
            }
        }
        return $violations;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['<zip open failed>'];
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = (string)$zip->getNameIndex($i);
        $match = p15_zip_entry_forbidden($entry);
        if ($match !== null) {
            $violations[] = $entry . ' -> ' . $match;
        }
    }
    $zip->close();
    return $violations;
}

$root = p15_root();
$ok = true;
$fail = [];

echo 'PHASE 15 RELEASE PACKAGE TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
p15_line('SQL', 'NOT REQUIRED');

foreach (P15_BUILT as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p15_line('Built ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

foreach (P15_RELEASE_DOCS as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p15_line('Release doc ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'PHP syntax:' . PHP_EOL;
$phpBin = p15_php();
foreach (P15_PHP_SYNTAX as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = [];
    $code = 0;
    exec('"' . $phpBin . '" -l "' . $fp . '" 2>&1', $out, $code);
    p15_line('Syntax ' . basename($rel), $code === 0 ? 'PASSED' : 'FAILED');
    if ($code !== 0) { $ok = false; $fail[] = 'syntax:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Mission docs:' . PHP_EOL;
foreach (P15_MISSION_DOCS as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p15_line('Doc ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Packaging scripts:' . PHP_EOL;
foreach (['tools/package-moghare360-local-release.ps1', 'tools/package-moghare360-demo.ps1'] as $rel) {
    $content = @file_get_contents($root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel));
    if ($content === false) {
        p15_line('Script ' . basename($rel), 'FAILED');
        $ok = false;
        continue;
    }
    $missing = [];
    foreach (P15_SCRIPT_EXCLUSIONS as $needle) {
        if (!str_contains($content, $needle)) {
            $missing[] = $needle;
        }
    }
    p15_line('Script exclusions ' . basename($rel), $missing === [] ? 'PASSED' : 'FAILED');
    if ($missing !== []) { $ok = false; $fail[] = 'script:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'ZIP content inspection:' . PHP_EOL;
foreach (P15_ZIP_PATHS as $zipRel) {
    $zipPath = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $zipRel);
    if (!is_file($zipPath)) {
        p15_line('ZIP ' . basename($zipRel), 'PENDING SCRIPT RUN');
        continue;
    }
    $violations = p15_inspect_zip($zipPath);
    if ($violations === []) {
        p15_line('ZIP ' . basename($zipRel), 'OK');
        continue;
    }
    p15_line('ZIP ' . basename($zipRel), 'FAILED');
    foreach ($violations as $violation) {
        echo '  Forbidden ZIP content: ' . $zipRel . ' -> ' . $violation . PHP_EOL;
        $ok = false;
        $fail[] = 'zip:' . $zipRel . ':' . $violation;
    }
}

echo str_repeat('-', 52) . PHP_EOL . 'Helper load:' . PHP_EOL;
try {
    require_once $root . '/public_html/includes/moghare360-release-package-helper.php';
    $pkgs = mogh_rel_package_types();
    p15_line('Helper packages', count($pkgs) >= 2 ? 'PASSED' : 'FAILED');
    if (count($pkgs) < 2) { $ok = false; }
} catch (Throwable $e) {
    p15_line('Helper load', 'FAILED: ' . $e->getMessage());
    $ok = false;
}

echo str_repeat('-', 52) . PHP_EOL . 'Forbidden files:' . PHP_EOL;
foreach (P15_FORBIDDEN as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p15_line('Forbidden ' . $rel, 'SKIP');
        continue;
    }
    $gitOut = [];
    exec('git -C "' . $root . '" status --porcelain -- "' . str_replace('\\', '/', $rel) . '" 2>&1', $gitOut);
    $modified = trim(implode('', $gitOut)) !== '';
    p15_line('Forbidden ' . $rel, $modified ? 'FAILED (modified)' : 'OK');
    if ($modified) { $ok = false; $fail[] = 'forbidden:' . $rel; }
}

echo str_repeat('=', 52) . PHP_EOL;
echo 'RESULT: ' . ($ok ? 'PASSED' : 'FAILED') . PHP_EOL;
if (!$ok && $fail !== []) {
    echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
}
echo 'PHASE 15 RELEASE PACKAGE TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
