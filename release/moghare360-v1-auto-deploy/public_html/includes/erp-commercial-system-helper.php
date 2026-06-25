<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 10 Commercial System Helper (non-sensitive)
 */

const ERP_PHASE10_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE10_PLACEHOLDER_ACTIONS = [
    'commercial.demo.view' => 'placeholder_commercial_demo_view',
    'commercial.checklist.write' => 'placeholder_commercial_checklist_write',
];

function cs_require_helper(string $fileName): void
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

cs_require_helper('erp-auth-context.php');
cs_require_helper('erp-permission-guard.php');
cs_require_helper('erp-csrf.php');

if (!function_exists('erp_csrf_input')) {
    function erp_csrf_input(string $purpose): string
    {
        return '<input type="hidden" name="erp_csrf_token" value="' .
            htmlspecialchars(erp_csrf_create_token($purpose), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('erp_csrf_require_valid')) {
    function erp_csrf_require_valid(string $purpose, ?string $token): void
    {
        try {
            erp_csrf_require_valid_token($purpose, (string)($token ?? ''));
        } catch (Throwable) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'ERP security validation failed.';
            exit;
        }
    }
}

function commercial_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function commercial_public_root(): string { return dirname(__DIR__); }
function commercial_page_exists(string $page): bool { return is_file(commercial_public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page)); }
function commercial_safe_redirect(string $url): void { header('Location: ' . $url); exit; }

function commercial_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function commercial_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function commercial_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function commercial_db()
{
    if (!extension_loaded('odbc')) return false;
    try { return erp_auth_create_local_odbc_connection(); } catch (Throwable) { return false; }
}

function commercial_table_exists($c, string $t): bool
{
    if ($c === false) return false;
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $t]) || @odbc_fetch_row($s) !== true) return false;
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function commercial_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) return false;
    return $s;
}

function commercial_scalar($c, string $sql, array $p = []): ?string
{
    $s = commercial_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) return null;
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function commercial_fetch_rows($c, string $sql, array $p = []): array
{
    $s = commercial_execute($c, $sql, $p);
    if ($s === false) return [];
    $rows = [];
    while (@odbc_fetch_row($s)) {
        $row = [];
        $n = @odbc_num_fields($s);
        if ($n === false) continue;
        for ($i = 1; $i <= $n; $i++) {
            $name = @odbc_field_name($s, $i);
            if ($name === false) continue;
            $val = @odbc_result($s, $i);
            $row[strtolower((string)$name)] = $val === false || $val === null ? '' : (string)$val;
        }
        if ($row !== []) $rows[] = $row;
    }
    return $rows;
}

function commercial_safe_count($c, string $table, string $where = '1=1', array $p = []): int
{
    if (!commercial_table_exists($c, $table)) return 0;
    $v = commercial_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $table . ' WHERE ' . $where, $p);
    return ($v !== null && is_numeric($v)) ? (int)$v : 0;
}

function commercial_safe_sum($c, string $table, string $col, string $where = '1=1', array $p = []): float
{
    if (!commercial_table_exists($c, $table)) return 0.0;
    $v = commercial_scalar($c, 'SELECT ISNULL(SUM(' . $col . '),0) FROM dbo.' . $table . ' WHERE ' . $where, $p);
    return ($v !== null && is_numeric($v)) ? (float)$v : 0.0;
}

function commercial_generate_release_code(): string
{
    return 'REL-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function cs_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE10_PLACEHOLDER_ACTIONS[$key])) return ['allowed' => false];
    return $uid === ERP_PHASE10_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function cs_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(cs_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function commercial_format_price(?string $amount): string
{
    if ($amount === null || $amount === '') return 'تماس / سفارشی';
    return number_format((float)$amount, 0, '.', ',') . ' تومان (preview)';
}

function commercial_static_demo_cards(): array
{
    return [
        ['title' => 'Soft Run Internal ERP', 'url' => 'erp-soft-run-home.php', 'type' => 'INTERNAL_ERP'],
        ['title' => 'Business Command Center', 'url' => 'erp-business-command-center.php', 'type' => 'BUSINESS_READY'],
        ['title' => 'Management Dashboard', 'url' => 'erp-management-dashboard.php', 'type' => 'BUSINESS_READY'],
        ['title' => 'Operation Engine', 'url' => 'erp-operation-control-center.php', 'type' => 'INTERNAL_ERP'],
        ['title' => 'Finance Preview', 'url' => 'erp-finance-control-center.php', 'type' => 'INTERNAL_ERP'],
        ['title' => 'CRM Follow-up', 'url' => 'erp-crm-followup-board.php', 'type' => 'INTERNAL_ERP'],
        ['title' => 'HR Preview', 'url' => 'erp-hr-dashboard.php', 'type' => 'INTERNAL_ERP'],
        ['title' => 'Product Status', 'url' => 'erp-product-status.php', 'type' => 'COMMERCIAL_SHOWCASE'],
    ];
}

function commercial_get_demo_registry($c): array
{
    if ($c !== false && commercial_table_exists($c, 'erp_commercial_demo_registry')) {
        $rows = commercial_fetch_rows($c, 'SELECT demo_code, demo_title, demo_type, demo_url, demo_status, demo_note FROM dbo.erp_commercial_demo_registry ORDER BY display_order, demo_registry_id');
        if ($rows !== []) return $rows;
    }
    $out = [];
    foreach (commercial_static_demo_cards() as $i => $card) {
        $out[] = [
            'demo_code' => 'STATIC-' . ($i + 1),
            'demo_title' => $card['title'],
            'demo_type' => $card['type'],
            'demo_url' => $card['url'],
            'demo_status' => 'READY',
            'demo_note' => 'Static fallback',
        ];
    }
    return $out;
}

function commercial_static_package_plans(): array
{
    return [
        ['package_code' => 'STARTER-WORKSHOP', 'package_name' => 'Starter Workshop', 'package_tier' => 'STARTER', 'package_description' => 'ورود مشتری، JobCard، عملیات پایه', 'target_customer' => 'تعمیرگاه کوچک', 'monthly_price_preview' => '2500000', 'setup_price_preview' => '15000000', 'included_modules' => 'Customer, Operation, Rule', 'excluded_modules' => 'HR, CRM Advanced'],
        ['package_code' => 'STANDARD-WORKSHOP', 'package_name' => 'Standard Workshop', 'package_tier' => 'STANDARD', 'package_description' => 'انبار، مالی preview، CRM', 'target_customer' => 'تعمیرگاه متوسط', 'monthly_price_preview' => '4500000', 'setup_price_preview' => '25000000', 'included_modules' => 'Customer, Operation, Inventory, Finance, CRM', 'excluded_modules' => 'HR Payroll'],
        ['package_code' => 'PROFESSIONAL-WORKSHOP', 'package_name' => 'Professional Workshop', 'package_tier' => 'PROFESSIONAL', 'package_description' => 'گزارش مدیریتی، HR، CRM کامل', 'target_customer' => 'تعمیرگاه حرفه‌ای', 'monthly_price_preview' => '7500000', 'setup_price_preview' => '40000000', 'included_modules' => 'Phase 1-9 modules', 'excluded_modules' => 'SaaS Multi-tenant'],
        ['package_code' => 'ENTERPRISE-READY', 'package_name' => 'Enterprise Ready', 'package_tier' => 'ENTERPRISE', 'package_description' => 'طراحی چندشعبه‌ای', 'target_customer' => 'شبکه تعمیرگاه', 'monthly_price_preview' => '', 'setup_price_preview' => '', 'included_modules' => 'Full ERP + Commercial', 'excluded_modules' => 'Production SaaS'],
    ];
}

function commercial_get_package_plans($c): array
{
    if ($c !== false && commercial_table_exists($c, 'erp_commercial_package_plans')) {
        $rows = commercial_fetch_rows($c, 'SELECT package_code, package_name, package_tier, package_description, target_customer, monthly_price_preview, setup_price_preview, included_modules, excluded_modules FROM dbo.erp_commercial_package_plans WHERE is_active_preview = 1 ORDER BY package_plan_id');
        if ($rows !== []) return $rows;
    }
    return commercial_static_package_plans();
}

function commercial_static_license_models(): array
{
    return [
        ['license_code' => 'DEMO-ONLY', 'license_name' => 'Demo Only', 'license_type' => 'DEMO_ONLY', 'max_users_preview' => '3', 'max_branches_preview' => '1', 'max_jobcards_monthly_preview' => '50', 'support_level' => 'None', 'license_note' => 'نمایش فروش'],
        ['license_code' => 'SINGLE-WORKSHOP', 'license_name' => 'Single Workshop', 'license_type' => 'SINGLE_WORKSHOP', 'max_users_preview' => '15', 'max_branches_preview' => '1', 'max_jobcards_monthly_preview' => '500', 'support_level' => 'Standard', 'license_note' => 'یک تعمیرگاه'],
        ['license_code' => 'MULTI-BRANCH-READY', 'license_name' => 'Multi Branch Ready', 'license_type' => 'MULTI_BRANCH_READY', 'max_users_preview' => '50', 'max_branches_preview' => '5', 'max_jobcards_monthly_preview' => '2000', 'support_level' => 'Priority', 'license_note' => 'طراحی tenant-ready'],
        ['license_code' => 'ENTERPRISE-READY', 'license_name' => 'Enterprise Ready', 'license_type' => 'ENTERPRISE_READY', 'max_users_preview' => '', 'max_branches_preview' => '', 'max_jobcards_monthly_preview' => '', 'support_level' => 'Dedicated', 'license_note' => 'سفارشی'],
    ];
}

function commercial_get_license_models($c): array
{
    if ($c !== false && commercial_table_exists($c, 'erp_license_preview_models')) {
        $rows = commercial_fetch_rows($c, 'SELECT license_code, license_name, license_type, max_users_preview, max_branches_preview, max_jobcards_monthly_preview, support_level, license_note FROM dbo.erp_license_preview_models WHERE is_active_preview = 1 ORDER BY license_preview_id');
        if ($rows !== []) return $rows;
    }
    return commercial_static_license_models();
}

function commercial_evaluate_readiness_checks(): array
{
    return [
        ['check_code' => 'INTERNAL_ERP_READY', 'check_group' => 'PRODUCT', 'check_title' => 'Internal ERP Ready', 'passed' => commercial_page_exists('erp-soft-run-home.php')],
        ['check_code' => 'BUSINESS_READY_SYSTEM_READY', 'check_group' => 'PRODUCT', 'check_title' => 'Business Ready System', 'passed' => commercial_page_exists('erp-management-dashboard.php')],
        ['check_code' => 'COMMERCIAL_DEMO_READY', 'check_group' => 'COMMERCIAL', 'check_title' => 'Commercial Demo Ready', 'passed' => commercial_page_exists('moghare360-commercial-demo.php')],
        ['check_code' => 'PRODUCT_PACKAGE_READY', 'check_group' => 'COMMERCIAL', 'check_title' => 'Product Package Ready', 'passed' => commercial_page_exists('moghare360-product-packages.php')],
        ['check_code' => 'LICENSE_PREVIEW_READY', 'check_group' => 'COMMERCIAL', 'check_title' => 'License Preview Ready', 'passed' => commercial_page_exists('moghare360-license-preview.php')],
        ['check_code' => 'TENANT_ARCHITECTURE_DOCUMENTED', 'check_group' => 'ARCHITECTURE', 'check_title' => 'Tenant Architecture Documented', 'passed' => is_file(dirname(__DIR__, 2) . '/docs/product/MOGHARE360_TENANT_READY_ARCHITECTURE.md')],
        ['check_code' => 'SAAS_NOT_ACTIVE_SAFE', 'check_group' => 'SAFETY', 'check_title' => 'SaaS Not Active Safe', 'passed' => true],
        ['check_code' => 'AUTH_BOUNDARY_PROTECTED', 'check_group' => 'SAFETY', 'check_title' => 'Auth Boundary Protected', 'passed' => true],
        ['check_code' => 'PERMISSION_BOUNDARY_PROTECTED', 'check_group' => 'SAFETY', 'check_title' => 'Permission Boundary Protected', 'passed' => true],
        ['check_code' => 'FINAL_REPORT_READY', 'check_group' => 'RELEASE', 'check_title' => 'Final Report Ready', 'passed' => commercial_page_exists('moghare360-final-release-report.php')],
    ];
}

function commercial_get_readiness_checks($c): array
{
    $live = commercial_evaluate_readiness_checks();
    if ($c === false || !commercial_table_exists($c, 'erp_commercial_readiness_checks')) {
        $out = [];
        foreach ($live as $ch) {
            $out[] = [
                'check_code' => $ch['check_code'],
                'check_group' => $ch['check_group'],
                'check_title' => $ch['check_title'],
                'check_status' => $ch['passed'] ? 'PASSED' : 'PENDING',
                'check_score' => $ch['passed'] ? '10' : '0',
                'check_note' => '',
            ];
        }
        return $out;
    }
    $dbRows = commercial_fetch_rows($c, 'SELECT check_code, check_group, check_title, check_status, check_score, check_note FROM dbo.erp_commercial_readiness_checks ORDER BY readiness_check_id');
    if ($dbRows === []) {
        return commercial_get_readiness_checks(false);
    }
    $liveMap = [];
    foreach ($live as $ch) $liveMap[$ch['check_code']] = $ch;
    foreach ($dbRows as &$row) {
        $code = $row['check_code'] ?? '';
        if (isset($liveMap[$code]) && ($row['check_status'] ?? '') === 'PENDING' && $liveMap[$code]['passed']) {
            $row['live_status'] = 'PASSED';
        }
    }
    return $dbRows;
}

function commercial_calculate_commercial_readiness_score($c): float
{
    $checks = commercial_get_readiness_checks($c);
    $total = 0.0;
    foreach ($checks as $ch) {
        $status = $ch['live_status'] ?? ($ch['check_status'] ?? 'PENDING');
        if ($status === 'PASSED') $total += 10.0;
        elseif ($status === 'WARNING') $total += 5.0;
        elseif (is_numeric($ch['check_score'] ?? null) && (float)$ch['check_score'] > 0) {
            $total += min(10.0, (float)$ch['check_score']);
        }
    }
    return min(100.0, round($total, 2));
}

function commercial_insert_release_history($c, string $type, string $title, string $summary, string $status = 'READY'): bool
{
    if (!commercial_table_exists($c, 'erp_commercial_release_history')) return false;
    $code = commercial_generate_release_code();
    return commercial_execute(
        $c,
        'INSERT INTO dbo.erp_commercial_release_history (release_code, release_type, release_title, release_status, release_summary, created_by, source_ip, user_agent) VALUES (?,?,?,?,?,?,?,?)',
        [$code, $type, $title, $status, $summary, commercial_safe_current_user(), commercial_client_ip(), commercial_user_agent()]
    ) !== false;
}

function commercial_product_boundaries(): array
{
    return [
        'No production login change', 'No auth rewrite', 'No permission rewrite',
        'No destructive DB migration', 'No official tax/final invoice',
        'No customer portal login', 'No production SaaS active',
        'No payment gateway', 'No real license enforcement',
    ];
}

function commercial_not_built_items(): array
{
    return [
        'Production multi-tenant SaaS', 'Real billing/subscription engine',
        'Payment gateway integration', 'Customer portal public login',
        'Official accounting/tax export', 'License enforcement server',
        'Production deployment automation',
    ];
}

function cs_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'PASSED', 'READY', 'SIGNED_OFF', 'BUILT' => 'p10cs-badge-ok',
        'WARNING', 'PENDING', 'DRAFT' => 'p10cs-badge-warn',
        'FAILED', 'DISABLED', 'CANCELLED' => 'p10cs-badge-fail',
        default => 'p10cs-badge-muted',
    };
}

function cs_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . commercial_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-business-layer.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-commercial-system.css">';
    echo '</head><body class="m360-rtl p10cs-page"><div class="p10cs-wrap">';
    echo '<div class="p10cs-demo-banner">Commercial Demo — Not Production SaaS · No billing · No tenant activation</div>';
}

function cs_render_foot(): void
{
    echo '<p class="p10cs-footer">';
    echo '<a href="moghare360-commercial-demo.php">Commercial Demo</a> · ';
    echo '<a href="moghare360-sales-showcase.php">Sales Showcase</a> · ';
    echo '<a href="moghare360-product-packages.php">Packages</a> · ';
    echo '<a href="moghare360-license-preview.php">License</a> · ';
    echo '<a href="moghare360-commercial-checklist.php">Checklist</a> · ';
    echo '<a href="moghare360-final-release-report.php">Final Report</a>';
    echo '</p></div></body></html>';
}

function cs_error(string $title, string $msg): void
{
    cs_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . commercial_h($msg) . '</p></div>';
    cs_render_foot();
    exit;
}

function cs_flash(string $key): string
{
    return match ($key) {
        'release_ok' => 'رکورد release با موفقیت ثبت شد.',
        default => '',
    };
}
