<?php
declare(strict_types=1);

/**
 * Brand leak scanner — customer-visible old Moghare/Moghareh branding.
 * CLI only. Does not modify files.
 *
 * Usage: php tools/p360-brand-leak-scanner.php [--runtime=PATH] [--json=PATH]
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI_ONLY\n");
    exit(1);
}

$root = dirname(__DIR__);
$args = getopt('', ['runtime::', 'json::']);
$scanRoot = (string)($args['runtime'] ?? ($root . DIRECTORY_SEPARATOR . 'public_html'));
$jsonOut = (string)($args['json'] ?? '');

$extOk = ['php', 'html', 'htm', 'js', 'css', 'webmanifest', 'json', 'xml', 'md', 'txt'];

/** @return list<array{path:string,line:int,class:string,snippet:string}> */
function p360_brand_scan_file(string $rel, string $abs): array
{
    $hits = [];
    $raw = @file_get_contents($abs);
    if ($raw === false || $raw === '') {
        return $hits;
    }
    if (str_contains($raw, "\0")) {
        return $hits;
    }

    $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
    foreach ($lines as $i => $line) {
        $class = p360_brand_classify_line($rel, $line);
        if ($class === null) {
            continue;
        }
        $hits[] = [
            'path' => $rel,
            'line' => $i + 1,
            'class' => $class,
            'snippet' => mb_substr(trim($line), 0, 160),
        ];
    }

    return $hits;
}

function p360_brand_has_old_fa(string $line): bool
{
    $probe = p360_brand_line_without_tech_filenames($line);

    return (bool)preg_match('/مقاره\s*۳?\s*۶?\s*۰?|مقاره۳۶۰|مقاره\s*360|مقاره360|مقاره\s*موتور/u', $probe);
}

function p360_brand_line_without_tech_filenames(string $line): string
{
    // Strip technical artifact names so href="moghare360-foo.php" is not a brand leak.
    $out = preg_replace('/moghare360-[a-z0-9\-_]+\.(php|css|js|html|sql|md|zip)/i', '', $line) ?? $line;
    $out = preg_replace('/assets\/moghare360-ui\/[^\s"\']+/i', '', $out) ?? $out;
    $out = preg_replace('/moghareh360\.ir/i', '', $out) ?? $out;
    $out = preg_replace('/\/moghare360\b/i', '', $out) ?? $out;
    $out = preg_replace('/\\\\moghare360\\\\/i', '', $out) ?? $out;
    $out = preg_replace('/moghare360_ERP|MOGHARE360_ERP|ir\.moghare360\.app/i', '', $out) ?? $out;
    $out = preg_replace('/moghare360-contract-[a-z0-9\-]*/i', '', $out) ?? $out;
    $out = preg_replace('/source\s*[:=]\s*["\']moghare360[^"\']*["\']/i', '', $out) ?? $out;

    return $out;
}

function p360_brand_has_old_en(string $line): bool
{
    $probe = p360_brand_line_without_tech_filenames($line);

    return (bool)preg_match('/\bMOGHAREH?\s*360\b|\bMOGHAREH360\b|\bMOGHARE360\b|\bMoghareh?\s*360\b|\bMoghareh?360\b|\bMOGHARE\s+MOTORS\b|\bMoghareh?\s+Motors\b|\bMoghare\s+Ready\b|\bMOGHARE\s+READY\b/i', $probe);
}

function p360_brand_has_old_logo(string $line): bool
{
    return (bool)preg_match('/moghareh-motors-logo\.(jpg|png|webp|svg)/i', $line);
}

function p360_brand_is_technical_context(string $rel, string $line): bool
{
    $trim = ltrim($line);

    // Comments / docblocks
    if (preg_match('/^\s*(\*|\/\/|#|\/\*)/', $trim)) {
        return true;
    }

    // Infrastructure: domain, local path, app id, DB name
    if (preg_match('/moghareh360\.ir|\/moghare360\b|\\\\moghare360\\\\|moghare360_ERP|MOGHARE360_ERP|ir\.moghare360\.app/i', $line)) {
        return true;
    }

    // Technical filenames / CSS / JS / PHP module paths (not display brand)
    if (preg_match('/moghare360-[a-z0-9\-_]+\.(php|css|js|html|sql|md|zip)/i', $line)) {
        return true;
    }
    if (preg_match('/assets\/moghare360-ui\//i', $line)) {
        return true;
    }
    if (preg_match('/href=["\'][^"\']*moghare360[^"\']*\.css/i', $line)) {
        return true;
    }

    // require/include of technical helpers
    if (preg_match('/(require|include)(_once)?\s*\(?[^;]*moghare360-/i', $line)) {
        return true;
    }

    // Version / lock / package / constant identifiers
    if (preg_match('/MOGHARE360-(INTAKE|V1|P\d|RC)|MOGHARE360_V1_|MOGHARE360_P\d|M360_.*VERSION.*MOGHARE|const\s+\w*VERSION\s*=\s*[\'"]MOGHARE/i', $line)) {
        return true;
    }
    if (preg_match('/MOGHARE360_[A-Z0-9_]+|function\s+\w*moghare|class\s+\w*Moghare|define\s*\(\s*[\'"]MOGHARE/i', $line)) {
        return true;
    }

    // Logs / console (not customer UI)
    if (preg_match('/error_log\s*\(|console\.(error|warn|log|info|debug)\s*\(/i', $line)) {
        return true;
    }

    // Generated PDF technical filename (not shown as brand lockup)
    if (preg_match('/moghare360-contract-|filename\s*=\s*[\'"]moghare360/i', $line)) {
        return true;
    }

    // Localization audit dictionaries listing old terms to flag
    if (str_contains($rel, 'moghare360-localization-helper.php') && preg_match('/terms_needing_review|\'MOGHARE/i', $line)) {
        return true;
    }

    // Storage / base_path config
    if (preg_match('/base_path|storageRoot|htdocs\\\\moghare360|CANONICAL_LOCAL_BASE/i', $line)) {
        return true;
    }

    // Source attribution / telemetry keys
    if (preg_match('/[\'"]source[\'"]\s*=>\s*[\'"]moghareh360/i', $line)) {
        return true;
    }

    return false;
}

function p360_brand_is_ui_surface(string $line): bool
{
    return (bool)preg_match(
        '/<(title|h1|h2|h3|span|small|p|label|button|a|strong|em)\b|alt\s*=|aria-label\s*=|placeholder\s*=|"short_name"\s*:|application-name|apple-mobile-web-app-title|wordmark|برند|cs_render_head|stab_render_head|bl_render_head|v1mc_render_head|v1ctrl_render_head|mogh_loc_render_head|mogh_rel_render_head|render_shell_start\s*\(/u',
        $line
    );
}

function p360_brand_classify_line(string $rel, string $line): ?string
{
    $hasFa = p360_brand_has_old_fa($line);
    $hasEn = p360_brand_has_old_en($line);
    $hasLogo = p360_brand_has_old_logo($line);

    if (!$hasFa && !$hasEn && !$hasLogo) {
        return null;
    }

    // Legal entity — do not auto-rebrand
    if (preg_match('/LEGAL_ENTITY_REVIEW|M360_CONTRACT_COMPANY|خدمات فنی مقاره عابد|مجموعه خدمات فنی مهندسی مقاره موتورز/u', $line)) {
        return 'LEGAL_ENTITY_REVIEW';
    }

    if (preg_match('#(docs/audits|docs/00_CANONICAL|tools/_generated|release/|_quarantine|dist/|_runtime_proof/|runtime_proof/)#i', $rel)) {
        return 'LEGACY_QUARANTINED';
    }

    // Active old logo path in runtime UI = always a leak
    if ($hasLogo) {
        if (p360_brand_is_technical_context($rel, $line) && !p360_brand_is_ui_surface($line) && !preg_match('/\bsrc\s*=/i', $line)) {
            return 'TECHNICAL_ALLOWED';
        }

        return 'CUSTOMER_VISIBLE_LEAK';
    }

    if (p360_brand_is_technical_context($rel, $line) && !p360_brand_is_ui_surface($line)) {
        return 'TECHNICAL_ALLOWED';
    }

    // UI surface with old brand
    if (p360_brand_is_ui_surface($line)) {
        return 'CUSTOMER_VISIBLE_LEAK';
    }

    // Persian commercial brand in active string literals (not legal)
    if ($hasFa) {
        return 'CUSTOMER_VISIBLE_LEAK';
    }

    // echo/print of English product brand to users
    if ($hasEn && preg_match('/\b(echo|print|printf)\b/i', $line) && preg_match('/[\'"].*(MOGHAREH?\s*360|MOGHARE360|Moghare)/i', $line)) {
        // Skip if only technical path inside the echoed HTML (css href already handled)
        if (preg_match('/moghare360-[a-z0-9\-]+\.(css|js|php)/i', $line) && !preg_match('/<(title|h1)|render_head/i', $line)) {
            return 'TECHNICAL_ALLOWED';
        }

        return 'CUSTOMER_VISIBLE_LEAK';
    }

    if ($hasEn) {
        return 'TECHNICAL_ALLOWED';
    }

    return 'FALSE_POSITIVE';
}

$hits = [];
$allowedRoot = $root . DIRECTORY_SEPARATOR . 'public_html';
$hasAllowedRoot = is_dir($allowedRoot);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $extOk, true)) {
        continue;
    }
    $abs = $file->getPathname();
    $rel = str_replace('\\', '/', substr($abs, strlen($scanRoot) + 1));
    if (str_contains($rel, '/_generated/') || str_contains($rel, '/vendor/')) {
        continue;
    }

    // When scanning runtime trees (eg XAMPP), ignore stale artifacts that are not part
    // of the current repo package. This prevents false "customer-visible" leaks.
    if ($hasAllowedRoot && str_replace('\\', '/', rtrim($scanRoot, '\\/')) !== str_replace('\\', '/', rtrim($allowedRoot, '\\/'))) {
        $allowedAbs = $allowedRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($allowedAbs)) {
            continue;
        }
    }

    foreach (p360_brand_scan_file($rel, $abs) as $h) {
        $hits[] = $h;
    }
}

$counts = [
    'CUSTOMER_VISIBLE_LEAK' => 0,
    'TECHNICAL_ALLOWED' => 0,
    'LEGAL_ENTITY_REVIEW' => 0,
    'LEGACY_QUARANTINED' => 0,
    'FALSE_POSITIVE' => 0,
];
foreach ($hits as $h) {
    $counts[$h['class']] = ($counts[$h['class']] ?? 0) + 1;
    echo $h['class'] . "\t" . $h['path'] . ':' . $h['line'] . "\t" . $h['snippet'] . "\n";
}

echo "SUMMARY";
foreach ($counts as $k => $v) {
    echo " {$k}={$v}";
}
echo "\n";
echo 'CUSTOMER_VISIBLE_OLD_BRAND_LEAKS=' . (int)$counts['CUSTOMER_VISIBLE_LEAK'] . "\n";

if ($jsonOut !== '') {
    file_put_contents($jsonOut, json_encode(['counts' => $counts, 'hits' => $hits], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
}

exit($counts['CUSTOMER_VISIBLE_LEAK'] > 0 ? 1 : 0);
