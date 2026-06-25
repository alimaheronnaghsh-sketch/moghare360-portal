<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 14 Deployment Helper (read-only planning utility)
 */

const ERP_PHASE14_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE14_PLACEHOLDER_ACTIONS = [
    'deployment.plan.view' => 'placeholder_deployment_plan_view',
];

function mogh_deploy_require_helper(string $fileName): void
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

mogh_deploy_require_helper('erp-auth-context.php');
mogh_deploy_require_helper('erp-permission-guard.php');

function mogh_deploy_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mogh_deploy_project_root(): string
{
    return dirname(__DIR__, 2);
}

function mogh_deploy_public_path(): string
{
    return dirname(__DIR__);
}

function mogh_deploy_doc_exists(string $rel): bool
{
    return is_file(mogh_deploy_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($rel, '/')));
}

function mogh_deploy_status_badge(string $status): string
{
    $u = strtoupper(str_replace([' ', '-'], '_', $status));
    return match (true) {
        in_array($u, ['READY', 'OK', 'YES', 'PASSED', 'READY_FOR_CONTROLLED_USE'], true) => 'p14dep-badge-ok',
        in_array($u, ['REQUIRED', 'PENDING', 'PLANNED', 'REVIEW'], true) => 'p14dep-badge-required',
        in_array($u, ['NOT_ACTIVE', 'NOT_DEPLOYED', 'NO', 'BLOCKED'], true) => 'p14dep-badge-muted',
        in_array($u, ['BLOCKED_UNTIL_APPROVAL', 'NOT_READY'], true) => 'p14dep-badge-blocked',
        default => 'p14dep-badge-warn',
    };
}

/** @return list<array<string, string>> */
function mogh_deploy_environment_registry(): array
{
    return [
        ['env' => 'Local', 'purpose' => 'توسعه و تست داخلی', 'status' => 'READY', 'url_pattern' => 'localhost / XAMPP', 'notes' => 'مسیر runtime جدا از repo'],
        ['env' => 'Pilot', 'purpose' => 'اجرای آزمایشی کنترل‌شده تعمیرگاه', 'status' => 'READY FOR CONTROLLED USE', 'url_pattern' => 'شبکه داخلی / محدود', 'notes' => 'بدون SaaS عمومی'],
        ['env' => 'Production', 'purpose' => 'نسخه عملیاتی', 'status' => 'NOT DEPLOYED', 'url_pattern' => 'تعریف نشده — نیاز تأیید', 'notes' => 'استقرار واقعی در این فاز انجام نمی‌شود'],
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_readiness_items(): array
{
    return [
        ['item' => 'Login hardening required', 'status' => 'REQUIRED', 'notes' => 'بدون rewrite در این فاز — مستندسازی'],
        ['item' => 'Permission matrix required', 'status' => 'REQUIRED', 'notes' => 'ماتریس Phase 13 — اجرای production جدا'],
        ['item' => 'Backup required', 'status' => 'REQUIRED', 'notes' => 'قبل از migration — بدون backup واقعی در این فاز'],
        ['item' => 'Deployment config required', 'status' => 'REQUIRED', 'notes' => 'Environment Config Plan'],
        ['item' => 'Private config required', 'status' => 'REQUIRED', 'notes' => 'خارج از repo — boundary only'],
        ['item' => 'Secrets must not be in repo', 'status' => 'READY', 'notes' => 'No credentials in repo'],
        ['item' => 'Database migration order required', 'status' => 'REQUIRED', 'notes' => 'phase_1 تا phase_12 ترتیب'],
        ['item' => 'Rollback plan required', 'status' => 'READY', 'notes' => 'مستند Rollback Plan'],
        ['item' => 'Monitoring plan required', 'status' => 'READY', 'notes' => 'مستند Monitoring Plan'],
        ['item' => 'Error log review required', 'status' => 'REQUIRED', 'notes' => 'قبل از go-live'],
        ['item' => 'Accounting not active', 'status' => 'NOT ACTIVE', 'notes' => 'Not Official Accounting'],
        ['item' => 'SaaS not active', 'status' => 'NOT ACTIVE', 'notes' => 'Not SaaS'],
        ['item' => 'Customer portal not active', 'status' => 'NOT ACTIVE', 'notes' => 'Not Customer Portal'],
        ['item' => 'Payment gateway not active', 'status' => 'NOT ACTIVE', 'notes' => 'No payment gateway'],
        ['item' => 'Final approval required', 'status' => 'BLOCKED UNTIL APPROVAL', 'notes' => 'مالک محصول / مدیر فنی'],
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_backup_requirements(): array
{
    return [
        ['type' => 'DB backup', 'frequency' => 'قبل از هر migration + روزانه پیشنهادی', 'owner' => 'DBA / مالک سیستم', 'status' => 'PLANNED'],
        ['type' => 'File backup', 'frequency' => 'قبل از deploy + هفتگی', 'owner' => 'DevOps / مالک', 'status' => 'PLANNED'],
        ['type' => 'Release snapshot', 'frequency' => 'هر RC / قبل از production', 'owner' => 'Release manager', 'status' => 'PLANNED'],
        ['type' => 'Rollback copy', 'frequency' => 'همزمان با deploy', 'owner' => 'DevOps', 'status' => 'PLANNED'],
        ['type' => 'Backup verification', 'frequency' => 'پس از هر backup', 'owner' => 'DBA', 'status' => 'REQUIRED'],
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_migration_plan_summary(): array
{
    $sqlDir = mogh_deploy_public_path() . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'sqlserver';
    $order = [
        'phase_1_customer_core_system.sql',
        'phase_2_operation_engine.sql',
        'phase_3_rule_engine.sql',
        'phase_4_inventory_purchase_system.sql',
        'phase_5_financial_system.sql',
        'phase_6_crm_system.sql',
        'phase_7_hr_internal_admin.sql',
        'phase_9_business_ready_system.sql',
        'phase_10_commercial_system.sql',
        'phase_12_soft_run_pilot.sql',
    ];
    $rows = [];
    $seq = 1;
    foreach ($order as $file) {
        $exists = is_file($sqlDir . DIRECTORY_SEPARATOR . $file);
        $rows[] = [
            'seq' => (string)$seq++,
            'file' => 'public_html/sql/sqlserver/' . $file,
            'status' => $exists ? 'OK' : 'MISSING',
            'rule' => 'Idempotent — no DROP without approval',
        ];
    }
    return $rows;
}

/** @return list<array<string, string>> */
function mogh_deploy_rollback_plan_summary(): array
{
    return [
        ['step' => '1', 'action' => 'File rollback', 'detail' => 'بازگردانی public_html از snapshot قبلی', 'status' => 'PLANNED'],
        ['step' => '2', 'action' => 'DB rollback', 'detail' => 'بازگردانی از backup تأییدشده — بدون اجرا در این فاز', 'status' => 'PLANNED'],
        ['step' => '3', 'action' => 'Config rollback', 'detail' => 'بازگردانی private config از نسخه امن', 'status' => 'PLANNED'],
        ['step' => '4', 'action' => 'Emergency freeze', 'detail' => 'توقف write routes تا رفع مشکل', 'status' => 'PLANNED'],
        ['step' => '5', 'action' => 'Rollback validation', 'detail' => 'تست smoke پس از rollback', 'status' => 'REQUIRED'],
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_monitoring_plan_summary(): array
{
    return [
        ['area' => 'Error log review', 'frequency' => 'روزانه', 'status' => 'PLANNED'],
        ['area' => 'DB connectivity', 'frequency' => 'مداوم', 'status' => 'PLANNED'],
        ['area' => 'Failed write routes', 'frequency' => 'هفتگی', 'status' => 'PLANNED'],
        ['area' => 'Backup success', 'frequency' => 'پس از هر backup', 'status' => 'REQUIRED'],
        ['area' => 'Access violation audit', 'frequency' => 'هفتگی', 'status' => 'PLANNED'],
        ['area' => 'Storage/disk', 'frequency' => 'هفتگی', 'status' => 'PLANNED'],
        ['area' => 'Response time', 'frequency' => 'ماهانه', 'status' => 'PLANNED'],
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_config_boundary(): array
{
    $privateExists = is_file(mogh_deploy_project_root() . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php');
    return [
        ['boundary' => 'Private config', 'status' => $privateExists ? 'PRESENT (boundary OK)' : 'NOT IN REPO', 'policy' => 'خارج از git — محتوا نمایش داده نمی‌شود'],
        ['boundary' => 'config.php', 'status' => is_file(mogh_deploy_project_root() . DIRECTORY_SEPARATOR . 'config.php') ? 'REVIEW' : 'NOT IN REPO', 'policy' => 'Forbidden change'],
        ['boundary' => 'public_html', 'status' => 'DEPLOY TARGET', 'policy' => 'Sync به runtime — بدون .md'],
        ['boundary' => 'Secrets in repo', 'status' => 'PROHIBITED', 'policy' => 'No credentials in repo'],
        ['boundary' => 'Runtime path', 'status' => 'SEPARATE', 'policy' => 'XAMPP/htdocs یا سرور production'],
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_security_prerequisites(): array
{
    return [
        ['item' => 'Phase 13 Security Audit', 'status' => 'READY', 'link' => 'erp-security-hardening-dashboard.php'],
        ['item' => 'CSRF coverage reviewed', 'status' => 'READY', 'link' => 'erp-csrf-audit.php'],
        ['item' => 'Write route audit', 'status' => 'READY', 'link' => 'erp-write-route-audit.php'],
        ['item' => 'Forbidden files unchanged', 'status' => 'REQUIRED', 'link' => 'erp-sensitive-boundary-report.php'],
        ['item' => 'Login hardening (production)', 'status' => 'BLOCKED UNTIL APPROVAL', 'link' => ''],
    ];
}

/** @return list<string> */
function mogh_deploy_production_blockers(): array
{
    return [
        'Not Production Deployed — استقرار واقعی انجام نشده',
        'Production execution approval not granted',
        'Official accounting not active',
        'SaaS tenant production not active',
        'Customer public portal not active',
        'Payment gateway not connected',
        'Downloadable ZIP package not built (PHASE 15)',
    ];
}

/** @return list<array<string, string>> */
function mogh_deploy_forbidden_files(): array
{
    $files = [
        'staff-auth.php', 'access-control.php', 'staff-login.php',
        'config.php', 'config.example.php',
        'private/erp-config.php', 'private/erp-config.example.php',
    ];
    $root = mogh_deploy_project_root();
    $rows = [];
    foreach ($files as $rel) {
        $fp = mogh_deploy_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($fp)) {
            $rows[] = ['file' => $rel, 'status' => 'SKIP / NOT PRESENT', 'notes' => 'Not in repo'];
            continue;
        }
        $gitOut = [];
        exec('git -C "' . $root . '" status --porcelain -- "' . str_replace('\\', '/', $rel) . '" 2>&1', $gitOut);
        $modified = trim(implode('', $gitOut)) !== '';
        $rows[] = [
            'file' => $rel,
            'status' => $modified ? 'FORBIDDEN CHANGE DETECTED' : 'OK',
            'notes' => $modified ? 'Review required' : 'Unchanged',
        ];
    }
    return $rows;
}

/** @return list<array{title:string,path:string}> */
function mogh_deploy_doc_links(): array
{
    return [
        ['title' => 'Environment Config Plan', 'path' => 'docs/deployment/MOGHARE360_ENVIRONMENT_CONFIG_PLAN.md'],
        ['title' => 'Backup Strategy', 'path' => 'docs/deployment/MOGHARE360_BACKUP_STRATEGY.md'],
        ['title' => 'Database Migration Plan', 'path' => 'docs/deployment/MOGHARE360_DATABASE_MIGRATION_PLAN.md'],
        ['title' => 'Rollback Plan', 'path' => 'docs/deployment/MOGHARE360_ROLLBACK_PLAN.md'],
        ['title' => 'Monitoring Plan', 'path' => 'docs/deployment/MOGHARE360_MONITORING_PLAN.md'],
    ];
}

function mogh_deploy_db()
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

function mogh_deploy_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        return erp_guard_action($c, $uid, $key);
    }
    if (!isset(ERP_PHASE14_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE14_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function mogh_deploy_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(mogh_deploy_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function mogh_deploy_error(string $title, string $message): never
{
    mogh_deploy_render_head($title);
    echo '<div class="p14dep-warning-box"><strong>' . mogh_deploy_h($title) . '</strong><p>' . mogh_deploy_h($message) . '</p></div>';
    mogh_deploy_render_foot();
    exit;
}

function mogh_deploy_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . mogh_deploy_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-brand-localization.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-deployment.css">';
    echo '</head><body class="p14dep-page"><div class="p14dep-wrap">';
    echo '<div class="p14dep-banner">Phase 14 — Production Deployment Plan — Read-only · Not Production Deployed · No deployment executed</div>';
}

function mogh_deploy_render_foot(): void
{
    echo '<p class="p14dep-footer">';
    echo '<a href="erp-deployment-readiness-dashboard.php">داشبورد آمادگی استقرار</a> · ';
    echo '<a href="erp-production-readiness-checklist.php">چک‌لیست Production</a> · ';
    echo '<a href="erp-security-hardening-dashboard.php">امنیت (Phase 13)</a> · ';
    echo '<a href="moghare360-demo-package.php">بسته نمایشی (Phase 15 prep)</a> · ';
    echo '<a href="erp-business-command-center.php">مرکز فرماندهی</a>';
    echo '</p></div></body></html>';
}

function mogh_deploy_render_warnings(): void
{
    echo '<div class="p14dep-warning-box"><strong>هشدار مرز استقرار</strong><ul style="margin:.5rem 0 0;padding-right:1.2rem">';
    echo '<li>Not Production — این صفحه Production Deploy انجام نمی‌دهد</li>';
    echo '<li>Not Production Deployed — استقرار واقعی انجام نشده</li>';
    echo '<li>Not SaaS — SaaS فعال نیست</li>';
    echo '<li>Not Customer Portal — Portal عمومی فعال نیست</li>';
    echo '<li>Not Official Accounting — حسابداری رسمی فعال نیست</li>';
    echo '<li>No credentials in repo — هیچ secret در repo نیست</li>';
    echo '<li>No deployment executed — هیچ deploy اجرا نشده</li>';
    echo '</ul></div>';
}
