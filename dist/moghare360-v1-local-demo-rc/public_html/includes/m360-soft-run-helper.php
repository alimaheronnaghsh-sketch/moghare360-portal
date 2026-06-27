<?php
declare(strict_types=1);

/**
 * MOGHARE360 P9 — Soft run core helper (demo tracking; no operational workflow mutation).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_SOFT_RUN_DEMO_PREFIX = 'M360-DEMO';
const M360_SOFT_RUN_DEMO_PREFIX_ALT = 'DEMO';
const M360_SOFT_RUN_CSRF = 'soft_run_p9';
const M360_SOFT_RUN_SCENARIO_CODE = 'M360-DEMO-E2E-V1';

const M360_SOFT_RUN_TABLE_SCENARIOS = 'erp_soft_run_scenarios';
const M360_SOFT_RUN_TABLE_EVENTS = 'erp_soft_run_events';
const M360_SOFT_RUN_TABLE_CHECKLIST = 'erp_soft_run_checklist';

const M360_SOFT_RUN_STATUS_PASS = 'PASS';
const M360_SOFT_RUN_STATUS_WARNING = 'WARNING';
const M360_SOFT_RUN_STATUS_BLOCKED = 'BLOCKED';
const M360_SOFT_RUN_STATUS_NOT_RUN = 'NOT_RUN';

/** @var array<string, string> */
const M360_SOFT_RUN_STATUS_LABELS_FA = [
    M360_SOFT_RUN_STATUS_PASS => 'گذر',
    M360_SOFT_RUN_STATUS_WARNING => 'هشدار',
    M360_SOFT_RUN_STATUS_BLOCKED => 'مسدود',
    M360_SOFT_RUN_STATUS_NOT_RUN => 'اجرا نشده',
];

function m360_soft_run_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_soft_run_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_soft_run_is_demo_marker(string $value): bool
{
    $v = strtoupper(trim($value));
    return str_starts_with($v, M360_SOFT_RUN_DEMO_PREFIX)
        || str_starts_with($v, M360_SOFT_RUN_DEMO_PREFIX_ALT . '-')
        || str_starts_with($v, M360_SOFT_RUN_DEMO_PREFIX_ALT . '_');
}

function m360_soft_run_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

/** @return list<array{href:string,label:string}> */
function m360_soft_run_nav(): array
{
    return [
        ['href' => 'erp-soft-run-control-center.php', 'label' => 'کنترل‌سنتر Soft Run'],
        ['href' => 'erp-end-to-end-demo-scenario.php', 'label' => 'سناریوی End-to-End'],
        ['href' => 'erp-soft-run-checklist.php', 'label' => 'چک‌لیست Soft Run'],
        ['href' => 'erp-demo-flow-map.php', 'label' => 'نقشه Demo Flow'],
        ['href' => 'erp-demo-readiness-report.php', 'label' => 'گزارش Readiness'],
        ['href' => 'erp-management-dashboard.php', 'label' => 'داشبورد P8'],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function m360_soft_run_fetch_scenario($conn, string $code = M360_SOFT_RUN_SCENARIO_CODE): ?array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_SOFT_RUN_TABLE_SCENARIOS)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_SOFT_RUN_TABLE_SCENARIOS . ' WHERE scenario_code = ? ORDER BY soft_run_id DESC',
        [$code]
    );
    return $rows[0] ?? null;
}

/**
 * @return array<string, mixed>|null
 */
function m360_soft_run_find_demo_jobcard($conn): ?array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return null;
    }

    $scenario = m360_soft_run_fetch_scenario($conn);
    $demoId = (int)($scenario['demo_jobcard_id'] ?? 0);
    if ($demoId > 0) {
        $rows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 j.*, c.full_name AS customer_name, v.plate_number FROM dbo.erp_jobcards j LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id WHERE j.jobcard_id = ?',
            [$demoId]
        );
        if (($rows[0] ?? null) !== null) {
            return $rows[0];
        }
    }

    if (customer_core_column_exists($conn, 'erp_jobcards', 'jobcard_number')) {
        $rows = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 1 j.*, c.full_name AS customer_name, v.plate_number FROM dbo.erp_jobcards j LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id WHERE j.jobcard_number LIKE N'M360-DEMO%' OR j.jobcard_number LIKE N'DEMO-%' ORDER BY j.jobcard_id DESC"
        );
        if (($rows[0] ?? null) !== null) {
            return $rows[0];
        }
    }

    if (customer_core_table_exists($conn, 'erp_customers')) {
        $rows = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 1 j.*, c.full_name AS customer_name, v.plate_number FROM dbo.erp_jobcards j INNER JOIN dbo.erp_customers c ON c.customer_id = j.customer_id LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id WHERE c.full_name LIKE N'M360-DEMO%' ORDER BY j.jobcard_id DESC"
        );
        return $rows[0] ?? null;
    }

    return null;
}

/**
 * @return array<string, string>
 */
function m360_soft_run_readiness_categories($conn): array
{
    $dbOk = is_resource($conn) && customer_core_table_exists($conn, 'erp_jobcards');
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';
    $viewsOk = $dbOk && m360_mgmt_view_exists($conn, M360_MGMT_VIEW_PIPELINE);

    return [
        'database' => $dbOk ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED,
        'workflow' => $dbOk ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_NOT_RUN,
        'gates' => $dbOk ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_NOT_RUN,
        'ui' => is_file(dirname(__DIR__) . '/erp-end-to-end-demo-scenario.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED,
        'demo_data' => m360_soft_run_find_demo_jobcard($conn) !== null ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING,
        'management' => $viewsOk ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING,
        'security' => M360_SOFT_RUN_STATUS_PASS,
    ];
}

/**
 * @return list<array{phase:string,label_fa:string,status:string,href:string}>
 */
function m360_soft_run_phase_status($conn): array
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-e2e-validation-helper.php';
    $jobcard = m360_soft_run_find_demo_jobcard($conn);
    $jobcardId = (int)($jobcard['jobcard_id'] ?? 0);
    $stages = $jobcardId > 0 && is_resource($conn) ? m360_e2e_validate_jobcard($conn, $jobcardId) : [];

    $phaseMap = [
        'P1' => ['ONLINE_REQUEST', 'RECEPTION'],
        'P1.5' => ['CONTRACT'],
        'P2' => ['RECEPTION'],
        'P3' => ['TECHNICAL'],
        'P4' => ['ESTIMATE', 'CUSTOMER_APPROVAL', 'PARTS_GATE', 'FINANCE_GATE'],
        'P5' => ['WORK_EXECUTION', 'PARTS_CONSUMPTION', 'TECHNICAL_COMPLETION'],
        'P6' => ['QC', 'DELIVERY_READINESS'],
        'P7' => ['FINAL_INVOICE', 'SETTLEMENT', 'CUSTOMER_DELIVERY', 'VEHICLE_RELEASE', 'JOBCARD_CLOSED'],
        'P8' => ['MANAGEMENT_DASHBOARD'],
    ];

    $out = [];
    foreach ($phaseMap as $phase => $codes) {
        $status = M360_SOFT_RUN_STATUS_NOT_RUN;
        foreach ($stages as $st) {
            if (!in_array((string)($st['stage_code'] ?? ''), $codes, true)) {
                continue;
            }
            $s = strtoupper((string)($st['stage_status'] ?? M360_SOFT_RUN_STATUS_NOT_RUN));
            if ($s === M360_SOFT_RUN_STATUS_BLOCKED) {
                $status = M360_SOFT_RUN_STATUS_BLOCKED;
                break;
            }
            if ($s === M360_SOFT_RUN_STATUS_WARNING && $status !== M360_SOFT_RUN_STATUS_BLOCKED) {
                $status = M360_SOFT_RUN_STATUS_WARNING;
            }
            if ($s === M360_SOFT_RUN_STATUS_PASS && $status === M360_SOFT_RUN_STATUS_NOT_RUN) {
                $status = M360_SOFT_RUN_STATUS_PASS;
            }
        }
        if ($jobcardId < 1 && $phase !== 'P8') {
            $status = M360_SOFT_RUN_STATUS_WARNING;
        }
        if ($phase === 'P8') {
            $status = is_file(dirname(__DIR__) . '/erp-management-dashboard.php') ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
        }
        $out[] = [
            'phase' => $phase,
            'label_fa' => 'فاز ' . $phase,
            'status' => $status,
            'href' => m360_soft_run_phase_href($phase, $jobcardId),
        ];
    }
    return $out;
}

function m360_soft_run_phase_href(string $phase, int $jobcardId): string
{
    return match ($phase) {
        'P1' => 'erp-reception-online-requests.php',
        'P1.5' => 'erp-intake-contracts.php',
        'P2' => $jobcardId > 0 ? 'erp-reception-jobcard-detail.php?jobcard_id=' . $jobcardId : 'erp-reception-jobcards.php',
        'P3' => $jobcardId > 0 ? 'erp-technical-jobcard-detail.php?jobcard_id=' . $jobcardId : 'erp-technical-board.php',
        'P4' => $jobcardId > 0 ? 'erp-estimate-detail.php?jobcard_id=' . $jobcardId : 'erp-estimate-board.php',
        'P5' => $jobcardId > 0 ? 'erp-work-execution-detail.php?jobcard_id=' . $jobcardId : 'erp-work-execution-board.php',
        'P6' => $jobcardId > 0 ? 'erp-qc-detail.php?jobcard_id=' . $jobcardId : 'erp-qc-board.php',
        'P7' => $jobcardId > 0 ? 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId : 'erp-final-invoice-board.php',
        'P8' => 'erp-management-dashboard.php',
        default => 'erp-soft-run-control-center.php',
    };
}
