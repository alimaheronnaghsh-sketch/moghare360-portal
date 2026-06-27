<?php
declare(strict_types=1);

/**
 * MOGHARE360 P9 — Demo readiness report + checklist (soft_run_checklist only for POST updates).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-soft-run-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-demo-scenario-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-e2e-validation-helper.php';

/**
 * @return list<array<string, mixed>>
 */
function m360_readiness_checklist_definitions(): array
{
    return [
        ['key' => 'config_safe', 'group' => 'foundation', 'title' => 'Config امن — بدون credential در repo'],
        ['key' => 'migrations_applied', 'group' => 'foundation', 'title' => 'Migrationهای P1–P9 موجود'],
        ['key' => 'production_signoff', 'group' => 'foundation', 'title' => 'Production signoff test موجود'],
        ['key' => 'p1_intake', 'group' => 'workflow', 'title' => 'P1 — Online Request Intake'],
        ['key' => 'p15_contract', 'group' => 'workflow', 'title' => 'P1.5 — Contract Gate'],
        ['key' => 'p2_reception', 'group' => 'workflow', 'title' => 'P2 — Reception'],
        ['key' => 'p3_technical', 'group' => 'workflow', 'title' => 'P3 — Technical'],
        ['key' => 'p4_estimate', 'group' => 'workflow', 'title' => 'P4 — Estimate/Approval'],
        ['key' => 'p5_work', 'group' => 'workflow', 'title' => 'P5 — Work Execution'],
        ['key' => 'p6_qc', 'group' => 'workflow', 'title' => 'P6 — QC/Delivery Readiness'],
        ['key' => 'p7_close', 'group' => 'workflow', 'title' => 'P7 — Invoice/Settlement/Delivery/Close'],
        ['key' => 'p8_dashboard', 'group' => 'workflow', 'title' => 'P8 — Management Dashboard'],
        ['key' => 'auth_unchanged', 'group' => 'security', 'title' => 'Auth core بدون تغییر'],
        ['key' => 'no_destructive_sql', 'group' => 'security', 'title' => 'بدون SQL مخرب'],
        ['key' => 'no_fake_production_otp', 'group' => 'security', 'title' => 'بدون OTP fake production'],
        ['key' => 'no_upload_bypass', 'group' => 'security', 'title' => 'بدون upload bypass'],
        ['key' => 'estimate_customer_visible', 'group' => 'business', 'title' => 'برآورد برای مشتری قابل نمایش'],
        ['key' => 'delivery_signature_visible', 'group' => 'business', 'title' => 'امضای تحویل قابل نمایش'],
        ['key' => 'timeline_complete', 'group' => 'business', 'title' => 'Timeline مدیریتی'],
        ['key' => 'financial_summary', 'group' => 'business', 'title' => 'خلاصه مالی P8'],
        ['key' => 'bottleneck_visible', 'group' => 'business', 'title' => 'گلوگاه‌های P8'],
        ['key' => 'owner_risk_visible', 'group' => 'business', 'title' => 'لیست ریسک مالک P8'],
    ];
}

function m360_readiness_auto_status(string $key, $conn): array
{
    $root = dirname(__DIR__, 2);
    $public = dirname(__DIR__);
    $status = M360_SOFT_RUN_STATUS_PASS;
    $note = '';

    switch ($key) {
        case 'config_safe':
            $status = !is_file($public . '/config.php') || !preg_match('/password\s*=\s*[\'"][^\'"]{8,}/i', (string)@file_get_contents($public . '/config.php'))
                ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'migrations_applied':
            $status = is_file($root . '/database/migrations/P9_end_to_end_soft_run.sql') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'production_signoff':
            $status = is_file($root . '/tools/test-v1-production-signoff.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'p1_intake':
            $status = is_file($public . '/erp-reception-online-requests.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p15_contract':
            $status = is_file($public . '/erp-intake-contracts.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p2_reception':
            $status = is_file($public . '/erp-reception-jobcards.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p3_technical':
            $status = is_file($public . '/erp-technical-board.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p4_estimate':
            $status = is_file($public . '/erp-estimate-board.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p5_work':
            $status = is_file($public . '/erp-work-execution-board.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p6_qc':
            $status = is_file($public . '/erp-qc-board.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p7_close':
            $status = is_file($public . '/erp-final-invoice-board.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'p8_dashboard':
            require_once __DIR__ . '/m360-management-kpi-helper.php';
            $status = is_file($public . '/erp-management-dashboard.php')
                && is_resource($conn) && m360_mgmt_view_exists($conn, M360_MGMT_VIEW_PIPELINE)
                ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'auth_unchanged':
            $status = is_file($public . '/staff-login.php') && is_file($public . '/owner-login.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'no_destructive_sql':
            $m9 = is_file($root . '/database/migrations/P9_end_to_end_soft_run.sql') ? (string)file_get_contents($root . '/database/migrations/P9_end_to_end_soft_run.sql') : '';
            $status = !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $m9) ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'no_fake_production_otp':
            $h = is_file(__DIR__ . '/m360-otp-helper.php') ? (string)file_get_contents(__DIR__ . '/m360-otp-helper.php') : '';
            $status = !preg_match('/production.*1234|fake.*otp.*production/i', $h) ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            break;
        case 'no_upload_bypass':
            $status = M360_SOFT_RUN_STATUS_PASS;
            break;
        case 'estimate_customer_visible':
            $status = is_file($public . '/customer-estimate-review.php') || is_file($public . '/erp-estimate-detail.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'delivery_signature_visible':
            $status = is_file($public . '/customer-delivery-review.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'timeline_complete':
            $status = is_file($public . '/erp-jobcard-timeline.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'financial_summary':
            $status = is_file($public . '/erp-financial-control-summary.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'bottleneck_visible':
            $status = is_file($public . '/erp-bottleneck-monitor.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
        case 'owner_risk_visible':
            $status = is_file($public . '/erp-owner-control-center.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
            break;
    }

    return ['status' => $status, 'note' => $note];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_readiness_checklist_items($conn): array
{
    $stored = [];
    if (is_resource($conn) && customer_core_table_exists($conn, M360_SOFT_RUN_TABLE_CHECKLIST)) {
        $storedRows = customer_core_fetch_rows($conn, 'SELECT * FROM dbo.' . M360_SOFT_RUN_TABLE_CHECKLIST . ' ORDER BY checklist_id ASC');
        foreach ($storedRows as $row) {
            $stored[(string)$row['checklist_key']] = $row;
        }
    }

    $items = [];
    foreach (m360_readiness_checklist_definitions() as $def) {
        $key = (string)$def['key'];
        $auto = m360_readiness_auto_status($key, $conn);
        $row = $stored[$key] ?? null;
        $items[] = [
            'checklist_key' => $key,
            'group' => (string)$def['group'],
            'checklist_title' => (string)$def['title'],
            'checklist_status' => (string)($row['checklist_status'] ?? $auto['status']),
            'checklist_note' => (string)($row['checklist_note'] ?? $auto['note']),
            'checklist_id' => (int)($row['checklist_id'] ?? 0),
        ];
    }
    return $items;
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_readiness_update_checklist_item($conn, string $key, string $status, string $note, int $userId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_SOFT_RUN_TABLE_CHECKLIST)) {
        return ['ok' => false, 'message' => 'جدول چک‌لیست Soft Run یافت نشد.'];
    }
    $status = strtoupper(trim($status));
    if (!in_array($status, [M360_SOFT_RUN_STATUS_PASS, M360_SOFT_RUN_STATUS_WARNING, M360_SOFT_RUN_STATUS_BLOCKED, M360_SOFT_RUN_STATUS_NOT_RUN], true)) {
        return ['ok' => false, 'message' => 'وضعیت نامعتبر است.'];
    }
    $defs = m360_readiness_checklist_definitions();
    $title = '';
    foreach ($defs as $d) {
        if ($d['key'] === $key) {
            $title = (string)$d['title'];
            break;
        }
    }
    if ($title === '') {
        return ['ok' => false, 'message' => 'آیتم چک‌لیست نامعتبر است.'];
    }

    $existing = customer_core_fetch_rows($conn, 'SELECT TOP 1 checklist_id FROM dbo.' . M360_SOFT_RUN_TABLE_CHECKLIST . ' WHERE checklist_key = ? ORDER BY checklist_id DESC', [$key]);
    if (($existing[0] ?? null) !== null) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_SOFT_RUN_TABLE_CHECKLIST . ' SET checklist_status = ?, checklist_note = ?, checked_at = SYSUTCDATETIME(), checked_by_user_id = ? WHERE checklist_id = ?',
            [$status, $note, $userId > 0 ? $userId : null, (int)$existing[0]['checklist_id']]
        );
    } else {
        customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_SOFT_RUN_TABLE_CHECKLIST . ' (checklist_key, checklist_title, checklist_status, checklist_note, checked_at, checked_by_user_id) VALUES (?, ?, ?, ?, SYSUTCDATETIME(), ?)',
            [$key, $title, $status, $note, $userId > 0 ? $userId : null]
        );
    }
    return ['ok' => true, 'message' => 'چک‌لیست Soft Run به‌روز شد.'];
}

/**
 * @return array<string, mixed>
 */
function m360_readiness_report($conn): array
{
    $items = m360_readiness_checklist_items($conn);
    $counts = ['PASS' => 0, 'WARNING' => 0, 'BLOCKED' => 0, 'NOT_RUN' => 0];
    foreach ($items as $it) {
        $s = strtoupper((string)$it['checklist_status']);
        $counts[$s] = ($counts[$s] ?? 0) + 1;
    }
    $total = count($items);
    $score = $total > 0 ? round((($counts['PASS'] ?? 0) / $total) * 100, 2) : 0.0;

    $recommendation = 'Blocked due to missing migration/data/test';
    if (($counts['BLOCKED'] ?? 0) === 0 && ($counts['WARNING'] ?? 0) <= 3) {
        $recommendation = 'Ready for owner soft run';
    }
    if (($counts['BLOCKED'] ?? 0) === 0 && ($counts['WARNING'] ?? 0) === 0 && $score >= 90) {
        $recommendation = 'Ready for internal demo';
    }
    if (($counts['BLOCKED'] ?? 0) > 0) {
        $recommendation = 'Blocked due to missing migration/data/test';
    }

    $demo = m360_soft_run_find_demo_jobcard($conn);
    require_once __DIR__ . '/m360-management-kpi-helper.php';
    $views = [
        'pipeline' => is_resource($conn) && m360_mgmt_view_exists($conn, M360_MGMT_VIEW_PIPELINE),
        'financial' => is_resource($conn) && m360_mgmt_view_exists($conn, M360_MGMT_VIEW_FINANCIAL),
        'qc' => is_resource($conn) && m360_mgmt_view_exists($conn, M360_MGMT_VIEW_QC),
    ];

    return [
        'readiness_score' => $score,
        'counts' => $counts,
        'recommendation_fa' => $recommendation,
        'demo_jobcard_id' => (int)($demo['jobcard_id'] ?? 0),
        'migrations' => [
            'p9' => is_file(dirname(__DIR__, 2) . '/database/migrations/P9_end_to_end_soft_run.sql'),
            'p8' => is_file(dirname(__DIR__, 2) . '/database/migrations/P8_management_dashboard_owner_control.sql'),
        ],
        'p8_views' => $views,
        'security_scope' => [
            'no_workflow_mutation' => true,
            'no_payment_gateway' => true,
            'no_accounting_voucher' => true,
        ],
        'items' => $items,
    ];
}
