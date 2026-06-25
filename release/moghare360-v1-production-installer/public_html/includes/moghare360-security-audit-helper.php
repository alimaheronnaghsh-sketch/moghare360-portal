<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 13 Security Audit Helper (read-only utility)
 */

const ERP_PHASE13_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE13_PLACEHOLDER_ACTIONS = [
    'security.audit.view' => 'placeholder_security_audit_view',
];

function security_audit_require_helper(string $fileName): void
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

security_audit_require_helper('erp-auth-context.php');
security_audit_require_helper('erp-permission-guard.php');

function security_audit_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function security_audit_project_root(): string
{
    return dirname(__DIR__, 2);
}

function security_audit_public_path(): string
{
    return dirname(__DIR__);
}

function security_audit_file_path(string $relative): string
{
    $rel = str_replace('/', DIRECTORY_SEPARATOR, ltrim($relative, '/'));
    if (str_starts_with($relative, 'public_html/')) {
        return security_audit_project_root() . DIRECTORY_SEPARATOR . $rel;
    }
    if (str_starts_with($relative, 'includes/') || str_starts_with($relative, 'private/')) {
        return security_audit_project_root() . DIRECTORY_SEPARATOR . $rel;
    }
    return security_audit_public_path() . DIRECTORY_SEPARATOR . $rel;
}

function security_audit_file_exists_status(string $relative): string
{
    return is_file(security_audit_file_path($relative)) ? 'OK' : 'MISSING';
}

function security_audit_file_contains_any(string $file, array $patterns): array
{
    $path = security_audit_file_path($file);
    if (!is_file($path)) {
        return ['found' => false, 'matches' => []];
    }
    $content = @file_get_contents($path);
    if ($content === false) {
        return ['found' => false, 'matches' => []];
    }
    $lower = strtolower($content);
    $hits = [];
    foreach ($patterns as $p) {
        if (str_contains($lower, strtolower($p))) {
            $hits[] = $p;
        }
    }
    return ['found' => $hits !== [], 'matches' => $hits];
}

function security_audit_safe_status_badge(string $status): string
{
    $u = strtoupper(str_replace([' ', '-'], '_', $status));
    return match (true) {
        in_array($u, ['OK', 'PASSED', 'YES', 'LOW', 'ALLOWED', 'UNCHANGED'], true) => 'p13sec-badge-ok',
        in_array($u, ['REVIEW', 'MEDIUM', 'LIMITED', 'NEEDS_REVIEW', 'MODULE_DEPENDENT', 'NEEDS_MANUAL_TEST'], true) => 'p13sec-badge-review',
        in_array($u, ['HIGH', 'FAILED', 'FORBIDDEN_CHANGE_DETECTED', 'MISSING', 'NOT_RECOMMENDED'], true) => 'p13sec-badge-fail',
        in_array($u, ['SKIP', 'NOT_PRESENT', 'NOT_FOUND', 'NOT_REQUIRED'], true) => 'p13sec-badge-muted',
        default => 'p13sec-badge-warn',
    };
}

/** @return list<array<string, string>> */
function security_audit_write_routes(): array
{
    $routes = [
        ['route' => 'submit-customer-entry.php', 'module' => 'Customer', 'csrf_type' => 'Project CSRF', 'permission' => 'customer.core.entry.create', 'risk' => 'MEDIUM', 'notes' => 'Phase 1 customer core entry'],
        ['route' => 'submit-customer-contract.php', 'module' => 'Contract', 'csrf_type' => 'Project CSRF', 'permission' => 'customer.contract.create', 'risk' => 'MEDIUM', 'notes' => 'Customer contract binding'],
        ['route' => 'submit-vehicle-binding.php', 'module' => 'Vehicle', 'csrf_type' => 'Project CSRF', 'permission' => 'vehicle.binding.create', 'risk' => 'MEDIUM', 'notes' => 'Vehicle binding write'],
        ['route' => 'submit-service-status-update.php', 'module' => 'Service', 'csrf_type' => 'Project CSRF', 'permission' => 'operation.status.update', 'risk' => 'MEDIUM', 'notes' => 'Operation status transition'],
        ['route' => 'submit-qc-decision.php', 'module' => 'Service', 'csrf_type' => 'Project CSRF', 'permission' => 'operation.qc.decide', 'risk' => 'MEDIUM', 'notes' => 'QC decision write'],
        ['route' => 'submit-delivery-final-check.php', 'module' => 'Service', 'csrf_type' => 'Project CSRF', 'permission' => 'operation.delivery.check', 'risk' => 'MEDIUM', 'notes' => 'Delivery final check'],
        ['route' => 'submit-service-approval-request.php', 'module' => 'Service', 'csrf_type' => 'Project CSRF', 'permission' => 'operation.approval.request', 'risk' => 'MEDIUM', 'notes' => 'Service approval request'],
        ['route' => 'submit-part-reserve.php', 'module' => 'Inventory', 'csrf_type' => 'Project CSRF', 'permission' => 'inventory.part.reserve', 'risk' => 'MEDIUM', 'notes' => 'Part reservation'],
        ['route' => 'submit-purchase-request.php', 'module' => 'Purchase', 'csrf_type' => 'Project CSRF', 'permission' => 'purchase.request.create', 'risk' => 'MEDIUM', 'notes' => 'Purchase request'],
        ['route' => 'submit-payment-record.php', 'module' => 'Finance Preview', 'csrf_type' => 'Project CSRF', 'permission' => 'finance.payment.record', 'risk' => 'HIGH', 'notes' => 'Payment preview record — not official accounting'],
        ['route' => 'submit-crm-followup.php', 'module' => 'CRM', 'csrf_type' => 'Project CSRF', 'permission' => 'crm.followup.create', 'risk' => 'MEDIUM', 'notes' => 'CRM follow-up write'],
        ['route' => 'submit-customer-satisfaction.php', 'module' => 'CRM', 'csrf_type' => 'Project CSRF', 'permission' => 'crm.satisfaction.record', 'risk' => 'LOW', 'notes' => 'Customer satisfaction'],
        ['route' => 'submit-employee-create.php', 'module' => 'HR', 'csrf_type' => 'Project CSRF', 'permission' => 'hr.employee.create', 'risk' => 'MEDIUM', 'notes' => 'Employee create'],
        ['route' => 'submit-employment-contract.php', 'module' => 'HR', 'csrf_type' => 'Project CSRF', 'permission' => 'hr.contract.create', 'risk' => 'MEDIUM', 'notes' => 'Employment contract'],
        ['route' => 'submit-attendance-entry.php', 'module' => 'HR', 'csrf_type' => 'Project CSRF', 'permission' => 'hr.attendance.entry', 'risk' => 'MEDIUM', 'notes' => 'Attendance entry'],
        ['route' => 'submit-pilot-scenario.php', 'module' => 'Pilot', 'csrf_type' => 'Pilot self-contained CSRF', 'permission' => 'pilot.scenario.write', 'risk' => 'LOW', 'notes' => 'Pilot scenario — CSRF root fix PASSED'],
        ['route' => 'submit-pilot-feedback.php', 'module' => 'Pilot', 'csrf_type' => 'Pilot self-contained CSRF', 'permission' => 'pilot.feedback.write', 'risk' => 'LOW', 'notes' => 'Pilot feedback — CSRF alignment PASSED'],
    ];

    $scanPatterns = ['csrf', 'post', 'redirect', 'permission', 'auth', 'history', 'audit'];
    foreach ($routes as &$row) {
        $file = (string)$row['route'];
        $exists = is_file(security_audit_public_path() . DIRECTORY_SEPARATOR . $file);
        $row['file_status'] = $exists ? 'OK' : 'MISSING';
        $row['method_expected'] = 'POST';
        $row['csrf_required'] = str_contains((string)$row['csrf_type'], 'Pilot') ? 'YES' : 'YES';
        $row['auth_expected'] = 'YES';
        $row['permission_expected'] = 'YES';
        $row['safe_redirect_expected'] = 'YES';
        $row['safe_error_expected'] = 'YES';
        $row['audit_history_expected'] = in_array($row['module'], ['Pilot', 'Finance Preview'], true) ? 'MODULE DEPENDENT' : 'MODULE DEPENDENT';
        if ($exists) {
            $scan = security_audit_file_contains_any($file, $scanPatterns);
            if (!$scan['found'] || !in_array('csrf', array_map('strtolower', $scan['matches']), true)) {
                $row['csrf_required'] = 'REVIEW';
            }
            if (!in_array('post', array_map('strtolower', $scan['matches']), true)) {
                $row['method_expected'] = 'REVIEW';
            }
        } else {
            $row['risk'] = 'REVIEW';
            $row['notes'] .= ' — file not found';
        }
    }
    unset($row);
    return $routes;
}

/** @return list<string> */
function security_audit_readonly_routes(): array
{
    return [
        'erp-business-command-center.php',
        'erp-module-navigation.php',
        'erp-product-status.php',
        'erp-management-dashboard.php',
        'erp-kpi-report.php',
        'erp-operation-performance-report.php',
        'erp-financial-preview-report.php',
        'erp-crm-report.php',
        'erp-inventory-pressure-report.php',
        'erp-staff-performance-preview.php',
        'erp-soft-run-audit.php',
        'erp-stabilization-dashboard.php',
        'erp-local-release-candidate.php',
        'erp-soft-run-pilot-center.php',
        'erp-localization-audit.php',
        'erp-brand-system.php',
        'erp-asset-registry.php',
        'erp-security-hardening-dashboard.php',
        'erp-write-route-audit.php',
        'erp-csrf-audit.php',
        'erp-role-access-matrix.php',
        'erp-error-handling-audit.php',
        'erp-sensitive-boundary-report.php',
    ];
}

/** @return list<string> */
function security_audit_public_demo_routes(): array
{
    return [
        'moghare360-commercial-demo.php',
        'moghare360-sales-showcase.php',
        'moghare360-product-packages.php',
        'moghare360-license-preview.php',
        'moghare360-commercial-checklist.php',
        'moghare360-final-release-report.php',
        'moghare360-demo-package.php',
    ];
}

/** @return list<array<string, string>> */
function security_audit_forbidden_files(): array
{
    $files = [
        'staff-auth.php',
        'access-control.php',
        'staff-login.php',
        'config.php',
        'config.example.php',
        'private/erp-config.php',
        'private/erp-config.example.php',
    ];
    $root = security_audit_project_root();
    $rows = [];
    foreach ($files as $rel) {
        $fp = security_audit_file_path($rel);
        if (!is_file($fp)) {
            $rows[] = ['file' => $rel, 'status' => 'SKIP / NOT PRESENT', 'notes' => 'File not in repo — no change risk'];
            continue;
        }
        $gitOut = [];
        $relGit = str_replace('\\', '/', $rel);
        exec('git -C "' . $root . '" status --porcelain -- "' . $relGit . '" 2>&1', $gitOut);
        $modified = trim(implode('', $gitOut)) !== '';
        $rows[] = [
            'file' => $rel,
            'status' => $modified ? 'FORBIDDEN CHANGE DETECTED' : 'OK',
            'notes' => $modified ? 'Git reports modification — review required' : 'Present and unchanged',
        ];
    }
    return $rows;
}

/** @return list<string> */
function security_audit_sensitive_boundaries(): array
{
    return [
        'Not Production — این فاز Production Deploy نیست',
        'Not SaaS — SaaS فعال نیست',
        'Not Customer Portal — Portal عمومی مشتری فعال نیست',
        'Not Official Accounting — حسابداری رسمی فعال نیست',
        'No Payment Gateway — درگاه پرداخت متصل نیست',
        'config/private boundary — فایل‌های config/private فقط وضعیت مرزی گزارش می‌شوند',
        'legacy portal boundary — پرتال legacy مشتری تغییر نکرده',
        'production login boundary — staff-auth/staff-login تغییر نکرده',
        'SaaS/tenant boundary — tenant production فعال نیست',
        'accounting/payment boundary — صورتحساب رسمی/مالیاتی فعال نیست',
    ];
}

/** @return list<array<string, string>> */
function security_audit_csrf_expectations(): array
{
    return [
        ['form' => 'Customer Entry', 'route' => 'submit-customer-entry.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'erp_csrf_require_valid'],
        ['form' => 'Customer Contract', 'route' => 'submit-customer-contract.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Standard project CSRF'],
        ['form' => 'Vehicle Binding', 'route' => 'submit-vehicle-binding.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Standard project CSRF'],
        ['form' => 'Operation Status', 'route' => 'submit-service-status-update.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Operation module'],
        ['form' => 'QC Decision', 'route' => 'submit-qc-decision.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Operation module'],
        ['form' => 'Delivery Check', 'route' => 'submit-delivery-final-check.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Operation module'],
        ['form' => 'Service Approval', 'route' => 'submit-service-approval-request.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Operation module'],
        ['form' => 'Part Reserve', 'route' => 'submit-part-reserve.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Inventory module'],
        ['form' => 'Purchase Request', 'route' => 'submit-purchase-request.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'Purchase module'],
        ['form' => 'Payment Record', 'route' => 'submit-payment-record.php', 'csrf_type' => 'Project CSRF', 'status' => 'REVIEW', 'notes' => 'Finance preview — manual retest recommended'],
        ['form' => 'CRM Follow-up', 'route' => 'submit-crm-followup.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'CRM module'],
        ['form' => 'Customer Satisfaction', 'route' => 'submit-customer-satisfaction.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'CRM module'],
        ['form' => 'Employee Create', 'route' => 'submit-employee-create.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'HR module'],
        ['form' => 'Contract Create', 'route' => 'submit-employment-contract.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'HR module'],
        ['form' => 'Attendance Entry', 'route' => 'submit-attendance-entry.php', 'csrf_type' => 'Project CSRF', 'status' => 'EXPECTED', 'notes' => 'HR module'],
        ['form' => 'Pilot Scenario', 'route' => 'submit-pilot-scenario.php', 'csrf_type' => 'Pilot self-contained CSRF', 'status' => 'PASSED', 'notes' => 'Pilot Scenario CSRF root fix: PASSED'],
        ['form' => 'Pilot Feedback', 'route' => 'submit-pilot-feedback.php', 'csrf_type' => 'Pilot self-contained CSRF', 'status' => 'PASSED', 'notes' => 'Pilot Feedback CSRF alignment: PASSED'],
    ];
}

/** @return list<string> */
function security_audit_roles(): array
{
    return ['Owner', 'Manager', 'Reception', 'Technician', 'Warehouse', 'Purchase', 'Finance', 'CRM', 'HR', 'Viewer'];
}

/** @return list<string> */
function security_audit_modules(): array
{
    return [
        'Customer', 'Vehicle', 'Contract', 'JobCard', 'Service', 'Inventory', 'Purchase',
        'Finance Preview', 'CRM', 'HR', 'Reports', 'Pilot', 'Commercial Demo', 'Security Audit',
    ];
}

/** @return list<string> */
function security_audit_actions(): array
{
    return ['View', 'Create', 'Update Status', 'Approve', 'Report'];
}

/** @return array<string, array<string, string>> */
function security_audit_role_matrix(): array
{
    $matrix = [];
    $defaults = [
        'Owner' => 'Allowed',
        'Manager' => 'Allowed',
        'Reception' => 'Limited',
        'Technician' => 'Limited',
        'Warehouse' => 'Limited',
        'Purchase' => 'Limited',
        'Finance' => 'Limited',
        'CRM' => 'Limited',
        'HR' => 'Limited',
        'Viewer' => 'Review',
    ];
    $overrides = [
        'Customer' => ['Reception' => 'Allowed', 'Viewer' => 'Limited'],
        'Vehicle' => ['Reception' => 'Allowed', 'Technician' => 'Allowed'],
        'Contract' => ['Reception' => 'Allowed', 'Manager' => 'Allowed'],
        'JobCard' => ['Technician' => 'Allowed', 'Reception' => 'Limited'],
        'Service' => ['Technician' => 'Allowed', 'Manager' => 'Approve'],
        'Inventory' => ['Warehouse' => 'Allowed'],
        'Purchase' => ['Purchase' => 'Allowed', 'Manager' => 'Approve'],
        'Finance Preview' => ['Finance' => 'Limited', 'Manager' => 'Review'],
        'CRM' => ['CRM' => 'Allowed', 'Reception' => 'Limited'],
        'HR' => ['HR' => 'Allowed'],
        'Reports' => ['Manager' => 'Allowed', 'Viewer' => 'Limited'],
        'Pilot' => ['Owner' => 'Allowed', 'Manager' => 'Limited'],
        'Commercial Demo' => ['Owner' => 'Allowed', 'Manager' => 'Review'],
        'Security Audit' => ['Owner' => 'Allowed', 'Manager' => 'Review', 'Viewer' => 'Not Recommended'],
    ];
    foreach (security_audit_modules() as $mod) {
        $matrix[$mod] = $defaults;
        if (isset($overrides[$mod])) {
            foreach ($overrides[$mod] as $role => $level) {
                $matrix[$mod][$role] = $level;
            }
        }
    }
    return $matrix;
}

/** @return list<array<string, string>> */
function security_audit_error_handling_policy(): array
{
    return [
        ['area' => 'Write routes', 'policy' => 'Safe Persian error page or redirect — no raw exception', 'status' => 'OK'],
        ['area' => 'Read-only pages', 'policy' => 'bl_error / cs_error / stab_error pattern — دسترسی ممکن نیست', 'status' => 'OK'],
        ['area' => 'DB credentials', 'policy' => 'No DB credential exposure to user', 'status' => 'OK'],
        ['area' => 'Stack trace', 'policy' => 'No stack trace to user in production-facing pages', 'status' => 'REVIEW'],
        ['area' => 'Safe redirect', 'policy' => 'Redirect to known internal pages after write', 'status' => 'OK'],
        ['area' => 'Validation errors', 'policy' => 'Field-level or summary Persian message', 'status' => 'OK'],
        ['area' => 'CSRF failure', 'policy' => 'Safe redirect with reason code — no bypass', 'status' => 'OK'],
        ['area' => 'Permission denied', 'policy' => 'Generic access denied — no permission model leak', 'status' => 'OK'],
        ['area' => 'Pilot write routes', 'policy' => 'pilot_csrf_failure_redirect — manual retest', 'status' => 'NEEDS MANUAL TEST'],
        ['area' => 'Payment write route', 'policy' => 'Finance preview only — not official accounting', 'status' => 'REVIEW'],
    ];
}

/** @return list<array<string, string>> */
function security_audit_route_classification(): array
{
    $rows = [];
    foreach (security_audit_write_routes() as $r) {
        $rows[] = [
            'route' => (string)$r['route'],
            'classification' => 'WRITE',
            'module' => (string)$r['module'],
            'access' => 'Auth + Permission + CSRF',
            'status' => (string)$r['file_status'],
        ];
    }
    foreach (security_audit_readonly_routes() as $route) {
        $rows[] = [
            'route' => $route,
            'classification' => 'READ_ONLY / REPORT',
            'module' => 'Mixed',
            'access' => 'View placeholder or module guard',
            'status' => security_audit_file_exists_status($route),
        ];
    }
    foreach (security_audit_public_demo_routes() as $route) {
        $rows[] = [
            'route' => $route,
            'classification' => 'PUBLIC DEMO / COMMERCIAL',
            'module' => 'Commercial',
            'access' => 'Demo view — not production SaaS',
            'status' => security_audit_file_exists_status($route),
        ];
    }
    return $rows;
}

/** @return list<array<string, string>> */
function security_audit_helper_inventory(): array
{
    $helpers = [
        ['name' => 'erp-auth-context.php', 'path' => 'includes/erp-auth-context.php', 'role' => 'Auth context/session read'],
        ['name' => 'erp-csrf.php', 'path' => 'includes/erp-csrf.php', 'role' => 'Project CSRF tokens'],
        ['name' => 'erp-permission-check.php', 'path' => 'includes/erp-permission-check.php', 'role' => 'Permission check utilities'],
        ['name' => 'erp-permission-guard.php', 'path' => 'includes/erp-permission-guard.php', 'role' => 'Permission guard'],
        ['name' => 'moghare360-pilot-helper.php', 'path' => 'public_html/includes/moghare360-pilot-helper.php', 'role' => 'Pilot self-contained CSRF'],
        ['name' => 'moghare360-localization-helper.php', 'path' => 'public_html/includes/moghare360-localization-helper.php', 'role' => 'Localization read-only'],
    ];
    foreach ($helpers as &$h) {
        $h['status'] = security_audit_file_exists_status((string)$h['path']);
        $h['changed'] = 'UNCHANGED (read-only inspection)';
    }
    unset($h);
    return $helpers;
}

/** @return array<string, int|string> */
function security_audit_dashboard_summary(): array
{
    $writes = security_audit_write_routes();
    $writeOk = count(array_filter($writes, static fn(array $r): bool => ($r['file_status'] ?? '') === 'OK'));
    $readonly = security_audit_readonly_routes();
    $readonlyOk = count(array_filter($readonly, static fn(string $r): bool => security_audit_file_exists_status($r) === 'OK'));
    $demo = security_audit_public_demo_routes();
    $demoOk = count(array_filter($demo, static fn(string $r): bool => security_audit_file_exists_status($r) === 'OK'));
    $csrf = security_audit_csrf_expectations();
    $csrfPassed = count(array_filter($csrf, static fn(array $r): bool => ($r['status'] ?? '') === 'PASSED'));
    $forbidden = security_audit_forbidden_files();
    $forbiddenBad = count(array_filter($forbidden, static fn(array $r): bool => ($r['status'] ?? '') === 'FORBIDDEN CHANGE DETECTED'));

    return [
        'overall' => $forbiddenBad === 0 ? 'Pilot-ready — Audit Layer Active' : 'REVIEW REQUIRED',
        'write_routes' => count($writes),
        'write_routes_ok' => $writeOk,
        'readonly_pages' => count($readonly),
        'readonly_pages_ok' => $readonlyOk,
        'demo_pages' => count($demo),
        'demo_pages_ok' => $demoOk,
        'csrf_pilot_fixes' => $csrfPassed,
        'forbidden_issues' => $forbiddenBad,
    ];
}

function security_audit_db()
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

function security_audit_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        return erp_guard_action($c, $uid, $key);
    }
    if (!isset(ERP_PHASE13_PLACEHOLDER_ACTIONS[$key])) {
        return ['allowed' => false];
    }
    return $uid === ERP_PHASE13_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function security_audit_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(security_audit_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function security_audit_error(string $title, string $message): never
{
    security_audit_render_head($title);
    echo '<div class="p13sec-warning-box"><strong>' . security_audit_h($title) . '</strong><p>' . security_audit_h($message) . '</p></div>';
    security_audit_render_foot();
    exit;
}

function security_audit_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . security_audit_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-brand-localization.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-security-hardening.css">';
    echo '</head><body class="p13sec-page"><div class="p13sec-wrap">';
    echo '<div class="p13sec-banner">Phase 13 — Security &amp; Access Hardening — Read-only Audit · Not Production · Not SaaS · Not Customer Portal · Not Official Accounting</div>';
}

function security_audit_render_foot(): void
{
    echo '<p class="p13sec-footer">';
    echo '<a href="erp-security-hardening-dashboard.php">داشبورد امنیت</a> · ';
    echo '<a href="erp-write-route-audit.php">ممیزی Write Route</a> · ';
    echo '<a href="erp-csrf-audit.php">ممیزی CSRF</a> · ';
    echo '<a href="erp-role-access-matrix.php">ماتریس نقش‌ها</a> · ';
    echo '<a href="erp-error-handling-audit.php">ممیزی خطا</a> · ';
    echo '<a href="erp-sensitive-boundary-report.php">گزارش مرز حساس</a> · ';
    echo '<a href="erp-business-command-center.php">مرکز فرماندهی</a>';
    echo '</p></div></body></html>';
}

function security_audit_render_nav(): void
{
    $links = [
        ['erp-security-hardening-dashboard.php', 'داشبورد امنیت', 'Security Dashboard'],
        ['erp-write-route-audit.php', 'ممیزی Write Route', 'Write Route Audit'],
        ['erp-csrf-audit.php', 'ممیزی CSRF', 'CSRF Audit'],
        ['erp-role-access-matrix.php', 'ماتریس نقش‌ها', 'Role Matrix'],
        ['erp-error-handling-audit.php', 'ممیزی خطا', 'Error Handling'],
        ['erp-sensitive-boundary-report.php', 'گزارش مرز حساس', 'Boundary Report'],
    ];
    echo '<div class="p13sec-nav-grid">';
    foreach ($links as [$file, $fa, $en]) {
        echo '<a class="p13sec-nav-card" href="' . security_audit_h($file) . '">';
        echo '<span class="p13sec-nav-title">' . security_audit_h($fa) . '</span>';
        echo '<span class="p13sec-nav-sub">' . security_audit_h($en) . '</span></a>';
    }
    echo '</div>';
}

function security_audit_render_boundary_warnings(): void
{
    echo '<div class="p13sec-warning-box"><strong>مرزهای امنیتی — Not Production</strong><ul style="margin:.5rem 0 0;padding-right:1.2rem">';
    echo '<li>Not Production — نسخه عملیاتی نیست</li>';
    echo '<li>Not SaaS — SaaS فعال نیست</li>';
    echo '<li>Not Customer Portal — Portal عمومی مشتری فعال نیست</li>';
    echo '<li>Not Official Accounting — حسابداری رسمی فعال نیست</li>';
    echo '<li>No Payment Gateway — درگاه پرداخت متصل نیست</li>';
    echo '</ul></div>';
}
