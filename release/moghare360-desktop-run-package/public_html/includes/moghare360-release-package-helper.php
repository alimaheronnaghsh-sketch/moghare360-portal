<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 15 Release Package Helper (read-only utility)
 */

const ERP_PHASE15_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE15_PLACEHOLDER_ACTIONS = [
    'release.package.view' => 'placeholder_release_package_view',
];

function mogh_rel_require_helper(string $fileName): void
{
    foreach ([__DIR__, dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes'] as $base) {
        $path = $base . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
    throw new RuntimeException('Required ERP file not found: ' . $fileName);
}

mogh_rel_require_helper('erp-auth-context.php');
mogh_rel_require_helper('erp-permission-guard.php');

function mogh_rel_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mogh_rel_project_root(): string
{
    return dirname(__DIR__, 2);
}

function mogh_rel_status_badge(string $status): string
{
    $u = strtoupper(str_replace([' ', '-'], '_', $status));
    return match (true) {
        in_array($u, ['OK', 'READY', 'FOUND', 'YES'], true) => 'p15rel-badge-ok',
        in_array($u, ['PENDING_SCRIPT_RUN', 'PENDING', 'NOT_BUILT'], true) => 'p15rel-badge-warn',
        default => 'p15rel-badge-muted',
    };
}

/** @return list<array<string, string>> */
function mogh_rel_package_types(): array
{
    return [
        [
            'type' => 'Desktop Run Package',
            'zip' => 'release/moghare360-desktop-run-package.zip',
            'folder' => 'release/moghare360-desktop-run-package/',
            'script' => 'tools/package-moghare360-desktop-run.ps1',
            'desc' => 'Desktop Run Package: برای اجرای محلی روی لپ‌تاپ/سیستم مقصد',
        ],
        [
            'type' => 'Mirror Website Package',
            'zip' => 'release/moghare360-mirror-site-package.zip',
            'folder' => 'release/moghare360-mirror-site-package/',
            'script' => 'tools/package-moghare360-mirror-site.ps1',
            'desc' => 'Mirror Website Package: برای آپلود روی moghareh360.ir به‌عنوان رابط سایت، بدون دیتابیس و بدون ذخیره داده روی هاست',
        ],
        [
            'type' => 'Final V1 Delivery Bundle',
            'zip' => 'release/moghare360-v1-final-delivery.zip',
            'folder' => 'release/moghare360-v1-final-delivery/',
            'script' => 'tools/package-moghare360-v1-final-delivery.ps1',
            'desc' => 'Final V1 Delivery Bundle: بسته نهایی شامل هر دو خروجی، راهنماها و گزارش‌ها',
        ],
        [
            'type' => 'Demo Package',
            'zip' => 'release/moghare360-demo-package.zip',
            'folder' => 'release/moghare360-demo-package/',
            'script' => 'tools/package-moghare360-demo.ps1',
            'desc' => 'بسته نمایشی تجاری، برند، مستندات محصول — بدون DB و private config',
        ],
        [
            'type' => 'Local Release Package',
            'zip' => 'release/moghare360-local-rc1.zip',
            'folder' => 'release/moghare360-local-rc1/',
            'script' => 'tools/package-moghare360-local-release.ps1',
            'desc' => 'MOGHARE360 Local RC1 — public_html، docs، SQL، tools — بدون secret و private',
        ],
    ];
}

/** @return array<string, mixed> */
function mogh_rel_zip_status(string $relativeZip): array
{
    $basename = basename(str_replace('\\', '/', $relativeZip));
    $candidates = [
        mogh_rel_public_path() . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . $basename,
        mogh_rel_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativeZip, '/')),
    ];
    $path = null;
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            $path = $candidate;
            break;
        }
    }
    if ($path === null) {
        return ['exists' => false, 'status' => 'PENDING SCRIPT RUN', 'size' => '—', 'modified' => '—', 'web_path' => ''];
    }
    $size = filesize($path);
    $sizeHuman = $size !== false ? mogh_rel_format_bytes((int)$size) : '—';
    $mtime = filemtime($path);
    $webPath = 'release/' . $basename;
    if (!is_file(mogh_rel_public_path() . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . $basename)) {
        $webPath = '';
    }
    return [
        'exists' => true,
        'status' => 'OK',
        'size' => $sizeHuman,
        'modified' => $mtime !== false ? date('Y-m-d H:i', $mtime) : '—',
        'web_path' => $webPath,
    ];
}

function mogh_rel_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 2) . ' MB';
}

/** @return list<string> */
function mogh_rel_included_items(): array
{
    return [
        'public_html safe pages (per package type)',
        'assets/moghare360-ui CSS (required set)',
        'assets/moghare360-brand (demo/local)',
        'docs/product, docs/release, docs/deployment (per package)',
        'public_html/sql/sqlserver migration scripts (local release only)',
        'tools test scripts (local release only)',
        'release manifest docs',
    ];
}

/** @return list<string> */
function mogh_rel_excluded_items(): array
{
    return [
        'private/ — private config',
        'config.php, config.example.php',
        'private/erp-config.php, private/erp-config.example.php',
        'credentials and secrets',
        'real customer data',
        'logs/, backups/',
        'uploads/ with real files',
        '.git/, node_modules/, vendor/',
        'DB backup files',
        'Codex ZIP archive',
        '*.bak, *.log, *.tmp',
    ];
}

/** @return list<string> */
function mogh_rel_security_warnings(): array
{
    return [
        'این پکیج Production Installer نیست.',
        'این پکیج Auto Deploy واقعی نیست.',
        'این پکیج شامل private config نیست.',
        'این پکیج شامل credential نیست.',
        'این پکیج شامل real customer data نیست.',
        'Not Production — نسخه عملیاتی نیست.',
        'Not SaaS — SaaS فعال نیست.',
    ];
}

function mogh_rel_db()
{
    if (!extension_loaded('odbc')) {
        return false;
    }
    try {
        return erp_auth_create_local_odbc_connection();
    } catch (Throwable) {
        return false;
    }
}

function mogh_rel_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        return erp_guard_action($c, $uid, $key);
    }
    if (!isset(ERP_PHASE15_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE15_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function mogh_rel_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(mogh_rel_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function mogh_rel_error(string $title, string $message): never
{
    mogh_rel_render_head($title);
    echo '<div class="p15rel-warning-box"><strong>' . mogh_rel_h($title) . '</strong><p>' . mogh_rel_h($message) . '</p></div>';
    mogh_rel_render_foot();
    exit;
}

function mogh_rel_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . mogh_rel_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-brand-localization.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-release-package.css">';
    echo '</head><body class="p15rel-page"><div class="p15rel-wrap">';
    echo '<div class="p15rel-banner">Phase 15 — Downloadable Release Package — Local/Demo only · Not Production Installer</div>';
}

function mogh_rel_render_foot(): void
{
    echo '<p class="p15rel-footer">';
    echo '<a href="erp-release-package-dashboard.php">داشبورد بسته خروجی</a> · ';
    echo '<a href="moghare360-release-download.php">دانلود بسته</a> · ';
    echo '<a href="moghare360-demo-package.php">بسته نمایشی</a> · ';
    echo '<a href="erp-deployment-readiness-dashboard.php">استقرار (Phase 14)</a> · ';
    echo '<a href="erp-business-command-center.php">مرکز فرماندهی</a>';
    echo '</p></div></body></html>';
}

function mogh_rel_render_warnings(): void
{
    echo '<div class="p15rel-warning-box"><strong>هشدارهای امنیتی بسته</strong><ul class="p15rel-manifest-list">';
    foreach (mogh_rel_security_warnings() as $w) {
        echo '<li>' . mogh_rel_h($w) . '</li>';
    }
    echo '</ul></div>';
}

function mogh_rel_script_command(string $script): string
{
    return 'powershell -ExecutionPolicy Bypass -File "' . $script . '"';
}
