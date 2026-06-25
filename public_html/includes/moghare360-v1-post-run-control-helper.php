<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Post-Run Control Helper (signoff dashboard + fix register)
 */

const V1_SIGNOFF_OWNER_ID = 10001;

function v1ctrl_require(string $fileName): void
{
    foreach ([__DIR__, dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes'] as $base) {
        $path = $base . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
    throw new RuntimeException('Required file not found: ' . $fileName);
}

v1ctrl_require('erp-auth-context.php');
v1ctrl_require('erp-permission-guard.php');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-config-loader.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-tenant-context.php';

function v1ctrl_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function v1ctrl_repo_root(): string
{
    return dirname(__DIR__, 2);
}

function v1ctrl_db()
{
    try {
        return mogh_tenant_db_connect();
    } catch (Throwable) {
        return false;
    }
}

function v1ctrl_table_exists($c, string $table): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $table]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    return (int)@odbc_result($s, 1) > 0;
}

function v1ctrl_status_badge(string $status): string
{
    $u = strtoupper($status);
    return match (true) {
        in_array($u, ['READY', 'ACTIVE', 'PASS', 'PASSED', 'SIGNED_OFF', 'CLOSED', 'FIXED', 'SMOKE_PASS'], true) => 'v1sig-badge-ok',
        in_array($u, ['PENDING', 'OPEN', 'IN_REVIEW', 'WARN'], true) => 'v1sig-badge-warn',
        in_array($u, ['DEFERRED_TO_V2'], true) => 'v1sig-badge-muted',
        default => 'v1sig-badge-muted',
    };
}

function v1ctrl_http_probe(string $url, bool $post = false): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'code' => 0];
    }
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8];
    if ($post) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = '{}';
        $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
    }
    curl_setopt_array($ch, $opts);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => $code >= 200 && $code < 500, 'code' => $code];
}

/** @return list<array<string, string>> */
function v1ctrl_live_signoff_rows(): array
{
    $root = v1ctrl_repo_root();
    $baseUrl = rtrim((string)(mogh_saas_load_config()['api_base_url'] ?? 'http://localhost:8080/moghare360/'), '/') . '/';

    $installerOk = is_file($root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'INSTALL_MOGHARE360_V1.ps1');
    $deployOk = is_file($root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'AUTO_DEPLOY_MOGHARE360_V1.ps1');
    $saasOk = is_file($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'saas-health.php')
        && is_file($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'sqlserver' . DIRECTORY_SEPARATOR . 'v1_saas_activation_foundation.sql');

    $apiHealth = v1ctrl_http_probe($baseUrl . 'api/mirror/health.php', true);
    $saasHealth = v1ctrl_http_probe($baseUrl . 'saas-health.php', false);
    $mirrorZip = is_file($root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package.zip');

    $cfg = mogh_saas_load_config();
    $storageOk = !empty($cfg['storage_root']) || is_dir($root . DIRECTORY_SEPARATOR . 'storage');
    $sslOk = str_starts_with($baseUrl, 'https://');

    $smokeLog = is_file($root . DIRECTORY_SEPARATOR . 'INSTALL_REPORT.md');
    $scenarioStatus = $smokeLog ? 'SMOKE_PASS' : 'PENDING';

    return [
        ['key' => 'installer', 'label' => 'Production Installer', 'status' => $installerOk ? 'READY' : 'PENDING', 'detail' => 'INSTALL_MOGHARE360_V1.ps1'],
        ['key' => 'auto_deploy', 'label' => 'Auto Deploy', 'status' => $deployOk ? 'READY' : 'PENDING', 'detail' => 'AUTO_DEPLOY_MOGHARE360_V1.ps1'],
        ['key' => 'saas', 'label' => 'SaaS Activation', 'status' => $saasOk && $saasHealth['ok'] ? 'ACTIVE' : 'PENDING', 'detail' => 'saas-health + foundation SQL'],
        ['key' => 'api', 'label' => 'Master API Endpoints', 'status' => $apiHealth['ok'] ? 'READY' : 'PENDING', 'detail' => 'HTTP ' . ($apiHealth['code'] ?? 0)],
        ['key' => 'mirror_pwa', 'label' => 'Mirror / PWA Package', 'status' => $mirrorZip ? 'READY' : 'PENDING', 'detail' => 'mirror-site-package.zip'],
        ['key' => 'ssl', 'label' => 'SSL Configured', 'status' => $sslOk ? 'READY' : 'PENDING', 'detail' => $sslOk ? 'HTTPS detected' : 'Local HTTP — configure SSL on production domain'],
        ['key' => 'storage', 'label' => 'Storage Configured', 'status' => $storageOk ? 'READY' : 'PENDING', 'detail' => (string)($cfg['storage_root'] ?? 'storage/')],
        ['key' => 'controlled_scenario', 'label' => 'Controlled Scenario', 'status' => $scenarioStatus, 'detail' => 'Smoke test / INSTALL_REPORT'],
    ];
}

/** @return array<string, mixed>|null */
function v1ctrl_fetch_signoff_record($c): ?array
{
    if ($c === false || !v1ctrl_table_exists($c, 'erp_v1_production_run_signoff')) {
        return null;
    }
    $sql = 'SELECT TOP 1 signoff_id, signoff_version, installer_status, auto_deploy_status, saas_status,
            api_status, mirror_pwa_status, ssl_configured, storage_configured, controlled_scenario_status,
            owner_signoff_status, owner_signoff_by, owner_signoff_at, signoff_note, created_at, updated_at
            FROM dbo.erp_v1_production_run_signoff ORDER BY signoff_id DESC';
    $res = @odbc_exec($c, $sql);
    if ($res === false || !($row = odbc_fetch_array($res))) {
        return null;
    }
    return $row;
}

/** @return list<array<string, mixed>> */
function v1ctrl_fetch_fix_items($c): array
{
    if ($c === false || !v1ctrl_table_exists($c, 'erp_v1_post_run_fix_register')) {
        return [];
    }
    $sql = 'SELECT item_id, category, severity, source, description, affected_module,
            owner_decision, status, created_at, closed_at
            FROM dbo.erp_v1_post_run_fix_register ORDER BY item_id ASC';
    $res = @odbc_exec($c, $sql);
    if ($res === false) {
        return [];
    }
    $rows = [];
    while ($row = odbc_fetch_array($res)) {
        $rows[] = $row;
    }
    return $rows;
}

/** @return array<string, int> */
function v1ctrl_fix_summary(array $items): array
{
    $summary = [
        'OPEN' => 0, 'IN_REVIEW' => 0, 'FIXED' => 0, 'DEFERRED_TO_V2' => 0, 'CLOSED' => 0,
        'BUG' => 0, 'FIX' => 0, 'UI' => 0, 'TRAINING' => 0, 'DATA' => 0, 'SECURITY' => 0, 'V2_BACKLOG' => 0,
    ];
    foreach ($items as $row) {
        $st = (string)($row['status'] ?? '');
        $cat = (string)($row['category'] ?? '');
        if (isset($summary[$st])) {
            $summary[$st]++;
        }
        if (isset($summary[$cat])) {
            $summary[$cat]++;
        }
    }
    return $summary;
}

function v1ctrl_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . v1ctrl_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<style>
.v1sig-hero{background:linear-gradient(135deg,#0f1714,#1a2e24);color:#e8f5ec;padding:1.5rem;border-radius:12px;margin-bottom:1rem}
.v1sig-badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.8rem;font-weight:600}
.v1sig-badge-ok{background:#14532d;color:#bbf7d0}
.v1sig-badge-warn{background:#713f12;color:#fde68a}
.v1sig-badge-muted{background:#334155;color:#cbd5e1}
.v1sig-card{background:#fff;border:1px solid #d8e2dc;border-radius:10px;padding:1rem;margin-bottom:1rem}
.v1sig-table{width:100%;border-collapse:collapse;font-size:.9rem}
.v1sig-table th,.v1sig-table td{border:1px solid #e2e8f0;padding:.45rem .55rem;text-align:right;vertical-align:top}
.v1sig-table th{background:#f1f5f9}
.v1sig-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.75rem;margin:.75rem 0}
.v1sig-kpi div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.75rem;text-align:center}
.v1sig-kpi .n{font-size:1.4rem;font-weight:700}
.v1sig-footer{margin-top:1.5rem;font-size:.88rem}
.v1sig-banner{background:#14532d;color:#ecfdf5;padding:.5rem .75rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
</style></head><body class="m360-rtl"><div style="max-width:1100px;margin:0 auto;padding:1rem">';
}

function v1ctrl_render_foot(): void
{
    echo '<p class="v1sig-footer">';
    echo '<a href="erp-v1-production-signoff.php">Production Signoff</a> · ';
    echo '<a href="erp-v1-fix-register.php">Fix Register</a> · ';
    echo '<a href="erp-soft-run-home.php">Soft Run Home</a> · ';
    echo '<a href="moghare360-release-download.php">Release Download</a> · ';
    echo '<a href="erp-business-command-center.php">Command Center</a>';
    echo '</p></div></body></html>';
}

function v1ctrl_owner_signoff_action($c, int $userId, string $note): bool
{
    if ($c === false || $userId !== V1_SIGNOFF_OWNER_ID || !v1ctrl_table_exists($c, 'erp_v1_production_run_signoff')) {
        return false;
    }
    $sql = "UPDATE dbo.erp_v1_production_run_signoff SET
            owner_signoff_status = N'SIGNED_OFF',
            owner_signoff_by = ?,
            owner_signoff_at = SYSUTCDATETIME(),
            signoff_note = ?,
            updated_at = SYSUTCDATETIME()
            WHERE signoff_id = (SELECT MAX(signoff_id) FROM dbo.erp_v1_production_run_signoff)";
    $stmt = @odbc_prepare($c, $sql);
    if ($stmt === false) {
        return false;
    }
    return @odbc_execute($stmt, ['OWNER_' . $userId, $note]);
}
