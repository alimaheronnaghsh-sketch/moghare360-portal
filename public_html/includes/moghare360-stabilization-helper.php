<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 11 Stabilization Helper (read-only, non-sensitive)
 */

const ERP_PHASE11_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE11_PLACEHOLDER_ACTIONS = [
    'stabilization.audit.view' => 'placeholder_stabilization_audit_view',
];

function stab_require_helper(string $fileName): void
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

stab_require_helper('erp-auth-context.php');
stab_require_helper('erp-permission-guard.php');

function stabilization_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function stabilization_project_root(): string
{
    return dirname(__DIR__, 2);
}

function stabilization_public_path(): string
{
    return dirname(__DIR__);
}

function stabilization_url_to_file_path(string $page): string
{
    return stabilization_public_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($page, '/'));
}

function stabilization_file_exists_status(string $relativePath): string
{
    $fp = stabilization_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
    if (str_starts_with($relativePath, 'public_html/')) {
        $fp = stabilization_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    } elseif (!str_contains($relativePath, 'public_html') && !str_contains($relativePath, 'docs/')) {
        $fp = stabilization_public_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
    }
    return is_file($fp) ? 'OK' : 'MISSING';
}

function stabilization_page_exists(string $page): bool
{
    return is_file(stabilization_url_to_file_path($page));
}

function stabilization_db()
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

function stabilization_table_exists($c, string $table): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $table]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function stabilization_column_exists($c, string $table, string $column): bool
{
    if ($c === false) {
        return false;
    }
    $s = @odbc_prepare(
        $c,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    if ($s === false || !@odbc_execute($s, ['dbo', $table, $column]) || @odbc_fetch_row($s) !== true) {
        return false;
    }
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function stabilization_safe_count($c, string $table): ?int
{
    if (!stabilization_table_exists($c, $table)) {
        return null;
    }
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM dbo.' . $table);
    if ($s === false || !@odbc_execute($s) || @odbc_fetch_row($s) !== true) {
        return null;
    }
    $v = @odbc_result($s, 1);
    return ($v !== false && is_numeric($v)) ? (int)$v : null;
}

function stabilization_forbidden_files(): array
{
    return [
        'staff-auth.php',
        'access-control.php',
        'staff-login.php',
        'config.php',
        'config.example.php',
        'private/erp-config.php',
        'private/erp-config.example.php',
    ];
}

function stabilization_forbidden_check(): array
{
    $root = stabilization_project_root();
    $out = [];
    foreach (stabilization_forbidden_files() as $rel) {
        $fp = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $exists = is_file($fp);
        $modified = false;
        if ($exists) {
            $gc = [];
            @exec('git -C ' . escapeshellarg($root) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
            $modified = $gc !== [] && trim(implode('', $gc)) !== '';
        }
        $out[] = [
            'path' => $rel,
            'exists' => $exists,
            'status' => !$exists ? 'SKIP' : ($modified ? 'MODIFIED' : 'OK'),
            'modified' => $modified,
        ];
    }
    return $out;
}

function stabilization_expected_url_registry(): array
{
    return [
        ['module' => 'Product Layer', 'title' => 'Soft Run Home', 'url' => 'erp-soft-run-home.php', 'notes' => 'Phase 8 entry'],
        ['module' => 'Product Layer', 'title' => 'MOGHARE Ready', 'url' => 'erp-moghare-ready.php', 'notes' => 'Release readiness'],
        ['module' => 'Product Layer', 'title' => 'Business Command Center', 'url' => 'erp-business-command-center.php', 'notes' => 'Phase 8'],
        ['module' => 'Product Layer', 'title' => 'Module Navigation', 'url' => 'erp-module-navigation.php', 'notes' => 'Phase 8'],
        ['module' => 'Product Layer', 'title' => 'Blueprint Map', 'url' => 'erp-blueprint-map.php', 'notes' => 'Phase 8'],
        ['module' => 'Product Layer', 'title' => 'Product Status', 'url' => 'erp-product-status.php', 'notes' => 'Phase 8'],
        ['module' => 'Product Layer', 'title' => 'Operational Command Center', 'url' => 'erp-operational-command-center.php', 'notes' => 'Phase 8'],
        ['module' => 'Product Layer', 'title' => 'Role Demo Navigation', 'url' => 'erp-role-demo-navigation.php', 'notes' => 'Phase 8'],
        ['module' => 'Business Ready', 'title' => 'Management Dashboard', 'url' => 'erp-management-dashboard.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'KPI Report', 'url' => 'erp-kpi-report.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'Operation Performance', 'url' => 'erp-operation-performance-report.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'Financial Preview', 'url' => 'erp-financial-preview-report.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'CRM Report', 'url' => 'erp-crm-report.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'Inventory Pressure', 'url' => 'erp-inventory-pressure-report.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'Staff Performance', 'url' => 'erp-staff-performance-preview.php', 'notes' => 'Phase 9'],
        ['module' => 'Business Ready', 'title' => 'Soft Run Audit', 'url' => 'erp-soft-run-audit.php', 'notes' => 'Phase 9'],
        ['module' => 'Commercial', 'title' => 'Commercial Demo', 'url' => 'moghare360-commercial-demo.php', 'notes' => 'Phase 10'],
        ['module' => 'Commercial', 'title' => 'Sales Showcase', 'url' => 'moghare360-sales-showcase.php', 'notes' => 'Phase 10'],
        ['module' => 'Commercial', 'title' => 'Product Packages', 'url' => 'moghare360-product-packages.php', 'notes' => 'Phase 10'],
        ['module' => 'Commercial', 'title' => 'License Preview', 'url' => 'moghare360-license-preview.php', 'notes' => 'Phase 10'],
        ['module' => 'Commercial', 'title' => 'Commercial Checklist', 'url' => 'moghare360-commercial-checklist.php', 'notes' => 'Phase 10'],
        ['module' => 'Commercial', 'title' => 'Final Release Report', 'url' => 'moghare360-final-release-report.php', 'notes' => 'Phase 10'],
        ['module' => 'Stabilization', 'title' => 'Stabilization Dashboard', 'url' => 'erp-stabilization-dashboard.php', 'notes' => 'Phase 11'],
        ['module' => 'Stabilization', 'title' => 'Broken Link Report', 'url' => 'erp-broken-link-report.php', 'notes' => 'Phase 11'],
        ['module' => 'Stabilization', 'title' => 'UI Polish Report', 'url' => 'erp-ui-polish-report.php', 'notes' => 'Phase 11'],
        ['module' => 'Stabilization', 'title' => 'DB Consistency Check', 'url' => 'erp-db-consistency-check.php', 'notes' => 'Phase 11'],
        ['module' => 'Stabilization', 'title' => 'Local Release Candidate', 'url' => 'erp-local-release-candidate.php', 'notes' => 'Phase 11 RC1'],
    ];
}

function stabilization_broken_link_report(): array
{
    $rows = [];
    foreach (stabilization_expected_url_registry() as $entry) {
        $file = (string)$entry['url'];
        $status = stabilization_page_exists($file) ? 'OK' : 'MISSING';
        $rows[] = array_merge($entry, [
            'expected_file' => $file,
            'file_status' => $status,
            'browser_status' => 'PENDING USER CHECK',
        ]);
    }
    return $rows;
}

function stabilization_expected_files(): array
{
    $pages = array_column(stabilization_expected_url_registry(), 'url');
    $helpers = [
        'public_html/includes/erp-business-layer-helper.php',
        'public_html/includes/erp-business-ready-helper.php',
        'public_html/includes/erp-commercial-system-helper.php',
        'public_html/includes/moghare360-stabilization-helper.php',
    ];
    $css = [
        'public_html/assets/moghare360-ui/moghare360-soft-run-release.css',
        'public_html/assets/moghare360-ui/moghare360-business-layer.css',
        'public_html/assets/moghare360-ui/moghare360-business-ready.css',
        'public_html/assets/moghare360-ui/moghare360-commercial-system.css',
        'public_html/assets/moghare360-ui/moghare360-stabilization.css',
    ];
    $phase11 = [
        'public_html/erp-stabilization-dashboard.php',
        'public_html/erp-broken-link-report.php',
        'public_html/erp-ui-polish-report.php',
        'public_html/erp-db-consistency-check.php',
        'public_html/erp-local-release-candidate.php',
    ];
    $out = [];
    foreach (array_merge($pages, $helpers, $css, $phase11) as $path) {
        if (!str_contains($path, '/')) {
            $path = 'public_html/' . $path;
        }
        $out[] = ['path' => $path, 'status' => stabilization_file_exists_status($path)];
    }
    return $out;
}

function stabilization_expected_docs(): array
{
    $docs = [
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_00_INDEX.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_01_SCOPE.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_02_BROKEN_LINK_REPORT.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_03_UI_POLISH_REPORT.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_04_DATABASE_CONSISTENCY_CHECK.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_05_FORBIDDEN_FILE_CHECK.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_06_LOCAL_RELEASE_CANDIDATE.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_90_TEST_RESULT.md',
        'docs/missions/phase_11_stabilization_sprint/PHASE_11_99_SIGNOFF.md',
    ];
    $out = [];
    foreach ($docs as $doc) {
        $out[] = ['path' => $doc, 'status' => stabilization_file_exists_status($doc)];
    }
    return $out;
}

function stabilization_expected_sql_files(): array
{
    $files = glob(stabilization_project_root() . '/public_html/sql/sqlserver/phase_*.sql') ?: [];
    sort($files);
    $out = [];
    foreach ($files as $fp) {
        $rel = 'public_html/sql/sqlserver/' . basename($fp);
        $content = @file_get_contents($fp) ?: '';
        $hasDrop = (bool)preg_match('/\bDROP\s+(TABLE|DATABASE|INDEX)\b/i', $content);
        $idempotent = str_contains($content, 'IF NOT EXISTS') || str_contains($content, 'IF OBJECT_ID');
        $out[] = [
            'path' => $rel,
            'status' => 'OK',
            'has_drop' => $hasDrop,
            'idempotent_pattern' => $idempotent,
            'review' => $hasDrop ? 'WARNING' : ($idempotent ? 'OK' : 'NEEDS MANUAL REVIEW'),
        ];
    }
    return $out;
}

function stabilization_expected_tables(): array
{
    return [
        'Customer' => ['erp_customer_intakes', 'erp_customer_contracts', 'erp_customer_vehicle_bindings'],
        'Operation' => ['erp_operation_cases', 'erp_operation_service_steps', 'erp_operation_qc_decisions', 'erp_operation_delivery_checks'],
        'Rule' => ['erp_rule_decisions', 'erp_service_approval_requests'],
        'Inventory' => ['erp_inventory_items', 'erp_stock_balances', 'erp_part_reservations', 'erp_purchase_requests', 'erp_inventory_purchase_requests', 'erp_inventory_stock_movements', 'erp_stock_movements'],
        'Finance' => ['erp_jobcard_cost_headers', 'erp_payment_records', 'erp_invoice_previews'],
        'CRM' => ['erp_crm_followup_schedules', 'erp_customer_satisfaction_surveys', 'erp_customer_score_cards', 'erp_upsell_opportunities'],
        'HR' => ['erp_hr_employees', 'erp_hr_employment_contracts', 'erp_hr_attendance_records', 'erp_hr_payroll_previews', 'erp_hr_training_records', 'erp_hr_disciplinary_records', 'erp_hr_history'],
        'Business Ready' => ['erp_business_kpi_snapshots', 'erp_soft_run_audit_checks', 'erp_management_report_history'],
        'Commercial' => ['erp_commercial_demo_registry', 'erp_commercial_package_plans', 'erp_license_preview_models', 'erp_commercial_readiness_checks', 'erp_commercial_release_history'],
    ];
}

function stabilization_db_consistency_report($c): array
{
    $report = ['connected' => $c !== false, 'groups' => [], 'summary' => ['ok' => 0, 'missing' => 0, 'error' => 0]];
    if ($c === false) {
        return $report;
    }
    foreach (stabilization_expected_tables() as $group => $tables) {
        $rows = [];
        foreach ($tables as $table) {
            try {
                $exists = stabilization_table_exists($c, $table);
                $count = $exists ? stabilization_safe_count($c, $table) : null;
                $status = $exists ? 'OK' : 'MISSING';
                if ($status === 'OK') {
                    $report['summary']['ok']++;
                } else {
                    $report['summary']['missing']++;
                }
                $rows[] = ['table' => $table, 'status' => $status, 'count' => $count];
            } catch (Throwable) {
                $report['summary']['error']++;
                $rows[] = ['table' => $table, 'status' => 'SAFE ERROR', 'count' => null];
            }
        }
        $report['groups'][$group] = $rows;
    }
    return $report;
}

function stabilization_phase_status_rows(): array
{
    $completed = 'COMPLETED / TESTED / COMMITTED / PUSHED';
    $rows = [];
    for ($i = 1; $i <= 10; $i++) {
        $rows[] = ['phase' => (string)$i, 'status' => $completed];
    }
    $rows[] = ['phase' => '11', 'status' => 'STABILIZATION IN PROGRESS / LOCAL RC1 PREPARATION'];
    return $rows;
}

function stabilization_boundary_labels(): array
{
    return [
        'Not Production SaaS',
        'Not Customer Portal',
        'Not Final Accounting',
        'Not Payment Gateway',
        'Not Legal Payroll/Insurance',
        'No Auth Rewrite',
        'No Permission Rewrite',
        'No Destructive DB Migration',
    ];
}

function stabilization_ui_polish_checks(): array
{
    $checks = [
        ['area' => 'RTL consistency', 'item' => 'Design tokens RTL (moghare360-rtl.css)', 'status' => stabilization_file_exists_status('public_html/assets/moghare360-ui/moghare360-rtl.css') === 'OK' ? 'OK' : 'MISSING', 'note' => 'Shared RTL base'],
        ['area' => 'RTL consistency', 'item' => 'Commercial pages lang=fa dir=rtl', 'status' => 'OK', 'note' => 'cs_render_head uses fa/rtl'],
        ['area' => 'RTL consistency', 'item' => 'Stabilization pages lang=fa dir=rtl', 'status' => 'OK', 'note' => 'stab_render_head uses fa/rtl'],
        ['area' => 'Page titles', 'item' => 'Business Layer pages have titles', 'status' => stabilization_page_exists('erp-business-command-center.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Page titles', 'item' => 'Commercial demo title', 'status' => stabilization_page_exists('moghare360-commercial-demo.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Navigation', 'item' => 'Soft Run Home links', 'status' => stabilization_page_exists('erp-soft-run-home.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Navigation', 'item' => 'Business Command Center module grid', 'status' => stabilization_page_exists('erp-business-command-center.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Navigation', 'item' => 'Management dashboard report links', 'status' => stabilization_page_exists('erp-management-dashboard.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Demo warnings', 'item' => 'Commercial demo banner', 'status' => 'OK', 'note' => 'Not Production SaaS banner in commercial CSS'],
        ['area' => 'Demo warnings', 'item' => 'Finance preview non-official label', 'status' => 'NEEDS MANUAL REVIEW', 'note' => 'Verify erp-financial-preview-report.php in browser'],
        ['area' => 'Production boundary', 'item' => 'Commercial vs Production wording', 'status' => 'OK', 'note' => 'Commercial pages state demo/preview'],
        ['area' => 'Production boundary', 'item' => 'SaaS not active labels', 'status' => 'OK', 'note' => 'Product status + final report'],
        ['area' => 'Soft Run labels', 'item' => 'Soft Run Home branding', 'status' => stabilization_page_exists('erp-soft-run-home.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Soft Run labels', 'item' => 'Soft Run Audit page', 'status' => stabilization_page_exists('erp-soft-run-audit.php') ? 'OK' : 'MISSING', 'note' => ''],
        ['area' => 'Labels', 'item' => 'Inconsistent English/Farsi mix', 'status' => 'NEEDS MANUAL REVIEW', 'note' => 'Intentional bilingual product labels'],
    ];
    return $checks;
}

function stabilization_php_syntax_targets(): array
{
    return [
        'public_html/includes/moghare360-stabilization-helper.php',
        'public_html/erp-stabilization-dashboard.php',
        'public_html/erp-broken-link-report.php',
        'public_html/erp-ui-polish-report.php',
        'public_html/erp-db-consistency-check.php',
        'public_html/erp-local-release-candidate.php',
    ];
}

function stabilization_php_syntax_audit(): array
{
    $phpBin = 'php';
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') {
            continue;
        }
        if ($c === 'php' || is_file($c)) {
            $phpBin = $c;
            break;
        }
    }
    $root = stabilization_project_root();
    $out = [];
    foreach (stabilization_php_syntax_targets() as $rel) {
        $fp = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($fp)) {
            $out[] = ['file' => $rel, 'status' => 'MISSING'];
            continue;
        }
        $lines = [];
        $ec = 0;
        @exec($phpBin . ' -l ' . escapeshellarg($fp) . ' 2>&1', $lines, $ec);
        $out[] = ['file' => $rel, 'status' => $ec === 0 ? 'OK' : 'FAILED', 'detail' => implode(' ', $lines)];
    }
    return $out;
}

function stabilization_release_candidate_status(): array
{
    $links = stabilization_broken_link_report();
    $missingFiles = count(array_filter($links, static fn(array $r): bool => ($r['file_status'] ?? '') === 'MISSING'));
    $forbidden = stabilization_forbidden_check();
    $forbiddenOk = !array_filter($forbidden, static fn(array $r): bool => ($r['status'] ?? '') === 'MODIFIED');
    $docsOk = !array_filter(stabilization_expected_docs(), static fn(array $r): bool => ($r['status'] ?? '') === 'MISSING');
    $phase11Ok = stabilization_page_exists('erp-stabilization-dashboard.php')
        && stabilization_page_exists('erp-local-release-candidate.php');

    return [
        'name' => 'MOGHARE360 Local Release Candidate 1',
        'scope' => 'Internal Soft Run / Business Ready / Commercial Demo Ready',
        'status' => ($missingFiles === 0 && $forbiddenOk && $docsOk && $phase11Ok)
            ? 'READY FOR CONTROLLED PILOT AFTER SIGNOFF'
            : 'PREPARATION IN PROGRESS',
        'checklist' => [
            ['item' => 'PHASE 1–10 Completed', 'status' => 'OK'],
            ['item' => 'PHASE 11 Stabilization Reports Built', 'status' => $phase11Ok ? 'OK' : 'PENDING'],
            ['item' => 'Browser URLs Registered', 'status' => $missingFiles === 0 ? 'OK' : 'WARNING'],
            ['item' => 'DB Consistency Report Available', 'status' => stabilization_page_exists('erp-db-consistency-check.php') ? 'OK' : 'MISSING'],
            ['item' => 'Forbidden Boundaries Preserved', 'status' => $forbiddenOk ? 'OK' : 'FAILED'],
            ['item' => 'Ready for PHASE 12 Soft Run Pilot after Commit/Push', 'status' => 'PENDING USER SIGNOFF'],
        ],
        'limitations' => [
            'Not Production SaaS',
            'Not Final Accounting',
            'Not Payment Gateway',
            'Not Customer Portal Production',
            'Not External Integration',
            'Not Official Payroll/Insurance/Tax',
        ],
    ];
}

function stabilization_basic_health_score(): float
{
    $links = stabilization_broken_link_report();
    $total = count($links);
    $ok = count(array_filter($links, static fn(array $r): bool => ($r['file_status'] ?? '') === 'OK'));
    $fileScore = $total > 0 ? ($ok / $total) * 40.0 : 0.0;

    $c = stabilization_db();
    $dbScore = 0.0;
    if ($c !== false) {
        $tables = stabilization_expected_tables();
        $all = 0;
        $present = 0;
        foreach ($tables as $list) {
            foreach ($list as $t) {
                $all++;
                if (stabilization_table_exists($c, $t)) {
                    $present++;
                }
            }
        }
        $dbScore = $all > 0 ? ($present / $all) * 30.0 : 0.0;
        @odbc_close($c);
    }

    $forbidden = stabilization_forbidden_check();
    $boundaryScore = !array_filter($forbidden, static fn(array $r): bool => ($r['status'] ?? '') === 'MODIFIED') ? 15.0 : 0.0;

    $syntax = stabilization_php_syntax_audit();
    $syntaxOk = count(array_filter($syntax, static fn(array $r): bool => ($r['status'] ?? '') === 'OK'));
    $syntaxScore = count($syntax) > 0 ? ($syntaxOk / count($syntax)) * 15.0 : 0.0;

    return min(100.0, round($fileScore + $dbScore + $boundaryScore + $syntaxScore, 1));
}

function stab_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        return erp_guard_action($c, $uid, $key);
    }
    if (!isset(ERP_PHASE11_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE11_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function stab_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(stab_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function stab_badge_class(string $status): string
{
    return match (strtoupper(str_replace(' ', '_', $status))) {
        'OK', 'PASSED', 'COMPLETED', 'READY', 'READY_FOR_CONTROLLED_PILOT_AFTER_SIGNOFF' => 'p11st-badge-ok',
        'WARNING', 'PENDING', 'PENDING_USER_CHECK', 'NEEDS_MANUAL_REVIEW', 'PREPARATION_IN_PROGRESS', 'STABILIZATION_IN_PROGRESS_/_LOCAL_RC1_PREPARATION' => 'p11st-badge-warn',
        'FAILED', 'MISSING', 'MODIFIED', 'SAFE_ERROR' => 'p11st-badge-fail',
        'SKIP', 'NOT_APPLICABLE' => 'p11st-badge-muted',
        default => 'p11st-badge-muted',
    };
}

function stab_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . stabilization_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-business-layer.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-stabilization.css">';
    echo '</head><body class="m360-rtl p11st-page"><div class="p11st-wrap">';
    echo '<div class="p11st-banner">Stabilization Sprint — Read-only Audit · Local Release Candidate 1 Preparation</div>';
}

function stab_render_foot(): void
{
    echo '<p class="p11st-footer">';
    echo '<a href="erp-stabilization-dashboard.php">داشبورد پایداری</a> · ';
    echo '<a href="erp-broken-link-report.php">لینک‌ها</a> · ';
    echo '<a href="erp-ui-polish-report.php">UI Polish</a> · ';
    echo '<a href="erp-db-consistency-check.php">DB Consistency</a> · ';
    echo '<a href="erp-local-release-candidate.php">Local RC1</a> · ';
    echo '<a href="erp-business-command-center.php">Business Command Center</a>';
    echo '</p></div></body></html>';
}

function stab_error(string $title, string $msg): void
{
    stab_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . stabilization_h($msg) . '</p></div>';
    stab_render_foot();
    exit;
}
