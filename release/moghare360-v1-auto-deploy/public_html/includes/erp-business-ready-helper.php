<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 9 Business Ready Helper (non-sensitive, read-only reports)
 */

const ERP_PHASE9_PLATFORM_OWNER_ID = 10001;

/** @var array<string, string> */
const ERP_PHASE9_PLACEHOLDER_ACTIONS = [
    'business.ready.dashboard' => 'placeholder_business_ready_dashboard',
    'business.ready.report' => 'placeholder_business_ready_report',
    'business.ready.snapshot' => 'placeholder_business_ready_snapshot',
    'business.ready.audit' => 'placeholder_business_ready_audit',
];

function br_require_helper(string $fileName): void
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

br_require_helper('erp-auth-context.php');
br_require_helper('erp-permission-guard.php');
br_require_helper('erp-csrf.php');

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

function br_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function br_public_root(): string { return dirname(__DIR__); }
function br_page_exists(string $page): bool { return is_file(br_public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page)); }
function br_safe_redirect(string $url): void { header('Location: ' . $url); exit; }

function br_safe_current_user(): string
{
    erp_auth_context_start();
    if (!empty($_SESSION['erp_username']) && is_string($_SESSION['erp_username'])) {
        return trim($_SESSION['erp_username']);
    }
    return 'ERP_STAFF';
}

function br_client_ip(): string
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ip !== '' ? substr($ip, 0, 100) : '';
}

function br_user_agent(): string
{
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua !== '' ? substr($ua, 0, 500) : '';
}

function business_ready_db()
{
    if (!extension_loaded('odbc')) return false;
    try { return erp_auth_create_local_odbc_connection(); } catch (Throwable) { return false; }
}

function business_ready_table_exists($c, string $t): bool
{
    if ($c === false) return false;
    $s = @odbc_prepare($c, 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    if ($s === false || !@odbc_execute($s, ['dbo', $t]) || @odbc_fetch_row($s) !== true) return false;
    $n = @odbc_result($s, 1);
    return $n !== false && (int)$n > 0;
}

function business_ready_execute($c, string $sql, array $p = [])
{
    $s = @odbc_prepare($c, $sql);
    if ($s === false || !@odbc_execute($s, $p)) return false;
    return $s;
}

function business_ready_scalar($c, string $sql, array $p = []): ?string
{
    $s = business_ready_execute($c, $sql, $p);
    if ($s === false || @odbc_fetch_row($s) !== true) return null;
    $v = @odbc_result($s, 1);
    return $v === false || $v === null ? null : (string)$v;
}

function business_ready_fetch_rows($c, string $sql, array $p = []): array
{
    $s = business_ready_execute($c, $sql, $p);
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

function business_ready_safe_count($c, string $table, string $where = '1=1', array $p = []): int
{
    if (!business_ready_table_exists($c, $table)) return 0;
    $v = business_ready_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $table . ' WHERE ' . $where, $p);
    return ($v !== null && is_numeric($v)) ? (int)$v : 0;
}

function business_ready_safe_sum($c, string $table, string $column, string $where = '1=1', array $p = []): float
{
    if (!business_ready_table_exists($c, $table)) return 0.0;
    $v = business_ready_scalar($c, 'SELECT ISNULL(SUM(' . $column . '),0) FROM dbo.' . $table . ' WHERE ' . $where, $p);
    return ($v !== null && is_numeric($v)) ? (float)$v : 0.0;
}

function business_ready_generate_snapshot_code(): string
{
    return 'KPI-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function business_ready_generate_report_code(): string
{
    return 'RPT-' . date('Ymd-His') . '-' . random_int(1000, 9999);
}

function br_guard_eval($c, int $uid, string $key): array
{
    $map = erp_guard_action_map();
    if (isset($map[$key])) {
        $r = erp_guard_action($c, $uid, $key);
        $r['label'] = !empty($r['allowed']) ? 'OK' : 'FAIL';
        return $r;
    }
    if (!isset(ERP_PHASE9_PLACEHOLDER_ACTIONS[$key])) return ['allowed' => false];
    return $uid === ERP_PHASE9_PLATFORM_OWNER_ID ? ['allowed' => true, 'placeholder' => true] : ['allowed' => false, 'placeholder' => true];
}

function br_require_auth($c, string $key): int
{
    erp_auth_context_start();
    $uid = erp_auth_current_user_id();
    if ($uid === null || $uid < 1 || empty(br_guard_eval($c, $uid, $key)['allowed'])) {
        throw new RuntimeException('دسترسی رد شد.');
    }
    return $uid;
}

function business_ready_format_amount(float|string $a): string
{
    return number_format((float)$a, 0, '.', ',');
}

function br_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'PASSED', 'READY', 'BUILT', 'PAID', 'COMPLETED', 'ACTIVE' => 'p9br-badge-ok',
        'WARNING', 'PARTIAL', 'PENDING', 'OPEN' => 'p9br-badge-warn',
        'FAILED', 'AT_RISK', 'UNPAID' => 'p9br-badge-fail',
        default => 'p9br-badge-muted',
    };
}

function business_ready_get_kpi_summary($c): array
{
    $kpi = [
        'operation_open' => 0, 'operation_ready' => 0, 'operation_delivered' => 0,
        'waiting_approval' => 0, 'waiting_parts' => 0,
        'unpaid_count' => 0, 'partial_paid_count' => 0, 'paid_count' => 0,
        'total_payable' => 0.0, 'total_paid' => 0.0, 'total_remaining' => 0.0,
        'crm_pending_followup' => 0, 'inventory_low_pressure' => 0, 'active_employees' => 0,
        'readiness_score' => 0.0,
    ];
    if ($c === false) return $kpi;

    if (business_ready_table_exists($c, 'erp_operation_cases')) {
        $kpi['operation_open'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage NOT IN ('DELIVERED','CANCELLED')");
        $kpi['operation_ready'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'READY_FOR_DELIVERY'");
        $kpi['operation_delivered'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'DELIVERED'");
        $kpi['waiting_approval'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'WAITING_APPROVAL'");
        $kpi['waiting_parts'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'WAITING_PARTS'");
    }
    if (business_ready_table_exists($c, 'erp_jobcard_cost_headers')) {
        $agg = business_ready_fetch_rows($c, "SELECT ISNULL(SUM(payable_total),0) AS tp, ISNULL(SUM(paid_total),0) AS tpd, ISNULL(SUM(remaining_total),0) AS tr,
            SUM(CASE WHEN payment_status='UNPAID' THEN 1 ELSE 0 END) AS uc,
            SUM(CASE WHEN payment_status='PARTIAL_PAID' THEN 1 ELSE 0 END) AS pc,
            SUM(CASE WHEN payment_status IN ('PAID','OVERPAID') THEN 1 ELSE 0 END) AS pac
            FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED'");
        if ($agg !== []) {
            $kpi['total_payable'] = (float)($agg[0]['tp'] ?? 0);
            $kpi['total_paid'] = (float)($agg[0]['tpd'] ?? 0);
            $kpi['total_remaining'] = (float)($agg[0]['tr'] ?? 0);
            $kpi['unpaid_count'] = (int)($agg[0]['uc'] ?? 0);
            $kpi['partial_paid_count'] = (int)($agg[0]['pc'] ?? 0);
            $kpi['paid_count'] = (int)($agg[0]['pac'] ?? 0);
        }
    }
    if (business_ready_table_exists($c, 'erp_crm_followup_schedules')) {
        $kpi['crm_pending_followup'] = business_ready_safe_count($c, 'erp_crm_followup_schedules', "followup_status IN ('PENDING','NO_ANSWER','RESCHEDULED','COMPLAINT')");
    }
    if (business_ready_table_exists($c, 'erp_stock_balances')) {
        $kpi['inventory_low_pressure'] = business_ready_safe_count($c, 'erp_stock_balances', 'available_qty <= 0 OR available_qty < 2');
    } elseif (business_ready_table_exists($c, 'erp_inventory_items')) {
        $kpi['inventory_low_pressure'] = business_ready_safe_count($c, 'erp_inventory_items', "item_status = 'LOW_STOCK' OR item_status = 'OUT_OF_STOCK'");
    }
    if (business_ready_table_exists($c, 'erp_hr_employees')) {
        $kpi['active_employees'] = business_ready_safe_count($c, 'erp_hr_employees', "employment_status = 'ACTIVE'");
    }
    $kpi['readiness_score'] = business_ready_calculate_readiness_score($c);
    return $kpi;
}

function business_ready_get_operation_performance($c): array
{
    $data = ['stages' => [], 'step_status' => [], 'qc' => [], 'delivery' => [], 'bottlenecks' => [], 'unavailable' => []];
    if ($c === false) { $data['unavailable'][] = 'db'; return $data; }

    if (business_ready_table_exists($c, 'erp_operation_cases')) {
        $rows = business_ready_fetch_rows($c, 'SELECT current_stage, COUNT(*) AS cnt FROM dbo.erp_operation_cases GROUP BY current_stage ORDER BY current_stage');
        $data['stages'] = $rows;
        $data['bottlenecks']['waiting_approval'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'WAITING_APPROVAL'");
        $data['bottlenecks']['waiting_parts'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'WAITING_PARTS'");
        $data['bottlenecks']['qc_hold'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage IN ('QC','RETURNED_FROM_QC')");
    } else { $data['unavailable'][] = 'erp_operation_cases'; }

    if (business_ready_table_exists($c, 'erp_operation_service_steps')) {
        $data['step_status'] = business_ready_fetch_rows($c, 'SELECT step_status, COUNT(*) AS cnt FROM dbo.erp_operation_service_steps GROUP BY step_status ORDER BY step_status');
    } else { $data['unavailable'][] = 'erp_operation_service_steps'; }

    if (business_ready_table_exists($c, 'erp_operation_qc_decisions')) {
        $data['qc'] = business_ready_fetch_rows($c, 'SELECT decision_status, COUNT(*) AS cnt FROM dbo.erp_operation_qc_decisions GROUP BY decision_status ORDER BY decision_status');
    } else { $data['unavailable'][] = 'erp_operation_qc_decisions'; }

    if (business_ready_table_exists($c, 'erp_operation_delivery_checks')) {
        $ready = business_ready_safe_count($c, 'erp_operation_delivery_checks', 'is_ready_for_delivery = 1');
        $notReady = business_ready_safe_count($c, 'erp_operation_delivery_checks', 'is_ready_for_delivery = 0');
        $data['delivery'] = [['status' => 'READY', 'cnt' => (string)$ready], ['status' => 'NOT_READY', 'cnt' => (string)$notReady]];
    } else { $data['unavailable'][] = 'erp_operation_delivery_checks'; }

    return $data;
}

function business_ready_get_financial_preview($c): array
{
    $data = ['payable' => 0.0, 'paid_amount' => 0.0, 'remaining' => 0.0, 'unpaid' => 0, 'partial' => 0, 'paid_count' => 0, 'overpaid' => 0, 'top_receivables' => [], 'unavailable' => []];
    if ($c === false) { $data['unavailable'][] = 'db'; return $data; }
    if (!business_ready_table_exists($c, 'erp_jobcard_cost_headers')) {
        $data['unavailable'][] = 'erp_jobcard_cost_headers';
        return $data;
    }
    $agg = business_ready_fetch_rows($c, "SELECT ISNULL(SUM(payable_total),0) AS tp, ISNULL(SUM(paid_total),0) AS tpd, ISNULL(SUM(remaining_total),0) AS tr,
        SUM(CASE WHEN payment_status='UNPAID' THEN 1 ELSE 0 END) AS uc,
        SUM(CASE WHEN payment_status='PARTIAL_PAID' THEN 1 ELSE 0 END) AS pc,
        SUM(CASE WHEN payment_status='PAID' THEN 1 ELSE 0 END) AS pac,
        SUM(CASE WHEN payment_status='OVERPAID' THEN 1 ELSE 0 END) AS oc
        FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED'");
    if ($agg !== []) {
        $data['payable'] = (float)($agg[0]['tp'] ?? 0);
        $data['paid_amount'] = (float)($agg[0]['tpd'] ?? 0);
        $data['remaining'] = (float)($agg[0]['tr'] ?? 0);
        $data['unpaid'] = (int)($agg[0]['uc'] ?? 0);
        $data['partial'] = (int)($agg[0]['pc'] ?? 0);
        $data['paid_count'] = (int)($agg[0]['pac'] ?? 0);
        $data['overpaid'] = (int)($agg[0]['oc'] ?? 0);
    }
    $data['top_receivables'] = business_ready_fetch_rows($c, "SELECT TOP 10 cost_header_id, cost_code, payable_total, paid_total, remaining_total, payment_status FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED' AND remaining_total > 0 ORDER BY remaining_total DESC");
    return $data;
}

function business_ready_get_crm_summary($c): array
{
    $data = ['followup' => [], 'satisfaction_avg' => null, 'vip_counts' => [], 'upsell' => [], 'highlights' => [], 'unavailable' => []];
    if ($c === false) { $data['unavailable'][] = 'db'; return $data; }

    if (business_ready_table_exists($c, 'erp_crm_followup_schedules')) {
        $data['followup'] = business_ready_fetch_rows($c, 'SELECT followup_status, COUNT(*) AS cnt FROM dbo.erp_crm_followup_schedules GROUP BY followup_status');
        $data['highlights']['complaints'] = business_ready_safe_count($c, 'erp_crm_followup_schedules', "followup_status = 'COMPLAINT' OR followup_reason LIKE '%COMPLAINT%'");
        $data['highlights']['needs_manager'] = business_ready_safe_count($c, 'erp_crm_followup_schedules', "priority_level IN ('HIGH','URGENT') AND followup_status IN ('PENDING','COMPLAINT')");
    } else { $data['unavailable'][] = 'erp_crm_followup_schedules'; }

    if (business_ready_table_exists($c, 'erp_customer_satisfaction_surveys')) {
        $avg = business_ready_scalar($c, 'SELECT AVG(CAST(overall_score AS FLOAT)) FROM dbo.erp_customer_satisfaction_surveys');
        $data['satisfaction_avg'] = $avg !== null ? round((float)$avg, 2) : null;
    } else { $data['unavailable'][] = 'erp_customer_satisfaction_surveys'; }

    if (business_ready_table_exists($c, 'erp_customer_score_cards')) {
        $data['vip_counts'] = business_ready_fetch_rows($c, 'SELECT vip_status, COUNT(*) AS cnt FROM dbo.erp_customer_score_cards GROUP BY vip_status');
    } else { $data['unavailable'][] = 'erp_customer_score_cards'; }

    if (business_ready_table_exists($c, 'erp_upsell_opportunities')) {
        $data['upsell'] = business_ready_fetch_rows($c, 'SELECT opportunity_status, COUNT(*) AS cnt FROM dbo.erp_upsell_opportunities GROUP BY opportunity_status');
    } else { $data['unavailable'][] = 'erp_upsell_opportunities'; }

    return $data;
}

function business_ready_get_inventory_pressure($c): array
{
    $data = ['items' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'pending_receive' => 0.0, 'purchase_by_status' => [], 'reservations' => [], 'waiting_parts_ops' => 0, 'hints' => [], 'unavailable' => []];
    if ($c === false) { $data['unavailable'][] = 'db'; return $data; }

    if (business_ready_table_exists($c, 'erp_inventory_items')) {
        $data['items'] = business_ready_safe_count($c, 'erp_inventory_items');
    }
    if (business_ready_table_exists($c, 'erp_stock_balances')) {
        $data['low_stock'] = business_ready_safe_count($c, 'erp_stock_balances', 'available_qty > 0 AND available_qty < 2');
        $data['out_of_stock'] = business_ready_safe_count($c, 'erp_stock_balances', 'available_qty <= 0');
        $data['pending_receive'] = business_ready_safe_sum($c, 'erp_stock_balances', 'pending_receive_qty');
    } elseif (!business_ready_table_exists($c, 'erp_inventory_items')) {
        $data['unavailable'][] = 'erp_stock_balances';
    }

    foreach (['erp_purchase_requests', 'erp_inventory_purchase_requests'] as $pt) {
        if (!business_ready_table_exists($c, $pt)) continue;
        $rows = business_ready_fetch_rows($c, "SELECT request_status AS st, COUNT(*) AS cnt FROM dbo.{$pt} GROUP BY request_status");
        if ($rows === [] && $pt === 'erp_inventory_purchase_requests') {
            $rows = business_ready_fetch_rows($c, "SELECT purchase_status AS st, COUNT(*) AS cnt FROM dbo.{$pt} GROUP BY purchase_status");
        }
        if ($rows !== []) {
            $data['purchase_by_status'] = $rows;
            break;
        }
    }

    if (business_ready_table_exists($c, 'erp_part_reservations')) {
        $data['reservations'] = business_ready_fetch_rows($c, 'SELECT reservation_status, COUNT(*) AS cnt FROM dbo.erp_part_reservations GROUP BY reservation_status');
    } else { $data['unavailable'][] = 'erp_part_reservations'; }

    if (business_ready_table_exists($c, 'erp_operation_cases')) {
        $data['waiting_parts_ops'] = business_ready_safe_count($c, 'erp_operation_cases', "current_stage = 'WAITING_PARTS'");
    }

    if ($data['low_stock'] > 0 || $data['out_of_stock'] > 0) $data['hints'][] = 'فشار موجودی — اقلام کم‌موجودی یا تمام‌شده';
    if ($data['waiting_parts_ops'] > 0) $data['hints'][] = 'عملیات در انتظار قطعه';
    if ($data['purchase_by_status'] !== []) $data['hints'][] = 'درخواست‌های خرید فعال';

    return $data;
}

function business_ready_get_staff_preview($c): array
{
    $data = ['active' => 0, 'attendance' => 0, 'overtime_hours' => 0.0, 'absence_hours' => 0.0, 'training' => 0, 'disciplinary' => 0, 'reward' => 0, 'payroll_net' => 0.0, 'unavailable' => []];
    if ($c === false) { $data['unavailable'][] = 'db'; return $data; }

    if (business_ready_table_exists($c, 'erp_hr_employees')) {
        $data['active'] = business_ready_safe_count($c, 'erp_hr_employees', "employment_status = 'ACTIVE'");
    } else { $data['unavailable'][] = 'erp_hr_employees'; }

    if (business_ready_table_exists($c, 'erp_hr_attendance_records')) {
        $data['attendance'] = business_ready_safe_count($c, 'erp_hr_attendance_records');
        $data['overtime_hours'] = business_ready_safe_sum($c, 'erp_hr_attendance_records', 'overtime_hours');
        $data['absence_hours'] = business_ready_safe_sum($c, 'erp_hr_attendance_records', 'absence_hours');
    } else { $data['unavailable'][] = 'erp_hr_attendance_records'; }

    if (business_ready_table_exists($c, 'erp_hr_training_records')) {
        $data['training'] = business_ready_safe_count($c, 'erp_hr_training_records');
    }
    if (business_ready_table_exists($c, 'erp_hr_disciplinary_records')) {
        $data['disciplinary'] = business_ready_safe_count($c, 'erp_hr_disciplinary_records', "record_type IN ('WARNING','DISCIPLINARY_NOTE')");
        $data['reward'] = business_ready_safe_count($c, 'erp_hr_disciplinary_records', "record_type IN ('REWARD','PROMOTION_NOTE')");
    }
    if (business_ready_table_exists($c, 'erp_hr_payroll_previews')) {
        $data['payroll_net'] = business_ready_safe_sum($c, 'erp_hr_payroll_previews', 'net_preview_amount', "preview_status <> 'CANCELLED'");
    } else { $data['unavailable'][] = 'erp_hr_payroll_previews'; }

    return $data;
}

/**
 * @return list<array<string, mixed>>
 */
function business_ready_evaluate_audit_checks($c): array
{
    $checks = [
        ['CUSTOMER_CORE_READY', 'PHASE_1', 'Customer Core', fn() => business_ready_table_exists($c, 'erp_customer_intakes') && br_page_exists('erp-customer-core-dashboard.php')],
        ['OPERATION_ENGINE_READY', 'PHASE_2', 'Operation Engine', fn() => business_ready_table_exists($c, 'erp_operation_cases')],
        ['RULE_ENGINE_READY', 'PHASE_3', 'Rule Engine', fn() => business_ready_table_exists($c, 'erp_rule_decisions') || business_ready_table_exists($c, 'erp_service_approval_requests')],
        ['INVENTORY_PURCHASE_READY', 'PHASE_4', 'Inventory & Purchase', fn() => business_ready_table_exists($c, 'erp_inventory_items') || business_ready_table_exists($c, 'erp_stock_balances')],
        ['FINANCIAL_PREVIEW_READY', 'PHASE_5', 'Financial Preview', fn() => business_ready_table_exists($c, 'erp_jobcard_cost_headers')],
        ['CRM_READY', 'PHASE_6', 'CRM System', fn() => business_ready_table_exists($c, 'erp_crm_followup_schedules')],
        ['HR_READY', 'PHASE_7', 'HR System', fn() => business_ready_table_exists($c, 'erp_hr_employees')],
        ['UI_PRODUCTIZED', 'PHASE_8', 'UI Productization', fn() => br_page_exists('erp-business-command-center.php')],
        ['BUSINESS_REPORTING_READY', 'PHASE_9', 'Business Reporting', fn() => br_page_exists('erp-management-dashboard.php')],
        ['COMMERCIAL_PENDING', 'PHASE_10', 'Commercial Readiness', fn() => false],
    ];
    $out = [];
    foreach ($checks as [$code, $group, $title, $eval]) {
        $ok = $eval();
        if ($code === 'COMMERCIAL_PENDING') {
            $status = 'PENDING';
            $score = 0.0;
        } elseif ($ok) {
            $status = 'PASSED';
            $score = 10.0;
        } elseif ($c !== false && (business_ready_table_exists($c, 'erp_customer_intakes') || business_ready_table_exists($c, 'erp_operation_cases'))) {
            $status = 'WARNING';
            $score = 5.0;
        } else {
            $status = 'PENDING';
            $score = 0.0;
        }
        $out[] = ['check_code' => $code, 'check_group' => $group, 'check_title' => $title, 'check_status' => $status, 'check_score' => $score];
    }
    return $out;
}

function business_ready_calculate_readiness_score($c): float
{
    $checks = business_ready_evaluate_audit_checks($c);
    $total = 0.0;
    foreach ($checks as $ch) {
        if ($ch['check_code'] === 'COMMERCIAL_PENDING') continue;
        $total += (float)$ch['check_score'];
    }
    return min(100.0, round($total, 2));
}

function business_ready_insert_report_history($c, string $type, string $title, string $summary): bool
{
    if (!business_ready_table_exists($c, 'erp_management_report_history')) return false;
    $code = business_ready_generate_report_code();
    return business_ready_execute(
        $c,
        'INSERT INTO dbo.erp_management_report_history (report_code, report_type, report_title, report_status, report_summary, created_by, source_ip, user_agent) VALUES (?,?,?,?,?,?,?,?)',
        [$code, $type, $title, 'GENERATED', $summary, br_safe_current_user(), br_client_ip(), br_user_agent()]
    ) !== false;
}

function business_ready_insert_kpi_snapshot($c, array $kpi, string $note = ''): ?int
{
    if (!business_ready_table_exists($c, 'erp_business_kpi_snapshots')) return null;
    $code = business_ready_generate_snapshot_code();
    $ok = business_ready_execute(
        $c,
        'INSERT INTO dbo.erp_business_kpi_snapshots (snapshot_code, snapshot_scope, operation_open_count, operation_ready_count, operation_delivered_count, waiting_approval_count, waiting_parts_count, unpaid_count, partial_paid_count, paid_count, crm_pending_followup_count, inventory_low_pressure_count, active_employee_count, total_payable, total_paid, total_remaining, readiness_score, snapshot_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $code, 'GLOBAL',
            (int)$kpi['operation_open'], (int)$kpi['operation_ready'], (int)$kpi['operation_delivered'],
            (int)$kpi['waiting_approval'], (int)$kpi['waiting_parts'],
            (int)$kpi['unpaid_count'], (int)$kpi['partial_paid_count'], (int)$kpi['paid_count'],
            (int)$kpi['crm_pending_followup'], (int)$kpi['inventory_low_pressure'], (int)$kpi['active_employees'],
            (float)$kpi['total_payable'], (float)$kpi['total_paid'], (float)$kpi['total_remaining'],
            (float)$kpi['readiness_score'], $note ?: null, br_safe_current_user(),
        ]
    );
    if ($ok === false) return null;
    $id = business_ready_scalar($c, 'SELECT CAST(SCOPE_IDENTITY() AS BIGINT)');
    business_ready_insert_report_history($c, 'KPI_SNAPSHOT', 'KPI Snapshot', 'Snapshot ' . $code);
    return ($id !== null && is_numeric($id)) ? (int)$id : null;
}

function business_ready_fetch_audit_rows($c): array
{
    if ($c === false || !business_ready_table_exists($c, 'erp_soft_run_audit_checks')) {
        return business_ready_evaluate_audit_checks($c);
    }
    $dbRows = business_ready_fetch_rows($c, 'SELECT check_code, check_group, check_title, check_status, check_score, check_note FROM dbo.erp_soft_run_audit_checks ORDER BY audit_check_id');
    if ($dbRows === []) return business_ready_evaluate_audit_checks($c);
    $live = business_ready_evaluate_audit_checks($c);
    $liveMap = [];
    foreach ($live as $r) $liveMap[$r['check_code']] = $r;
    foreach ($dbRows as &$row) {
        $code = $row['check_code'] ?? '';
        if (isset($liveMap[$code]) && ($row['check_status'] ?? '') === 'PENDING') {
            $row['live_status'] = $liveMap[$code]['check_status'];
        }
    }
    return $dbRows;
}

function business_ready_product_boundaries(): array
{
    return [
        'No production login change', 'No auth rewrite', 'No permission rewrite',
        'No destructive DB migration', 'No official tax/final invoice',
        'No customer portal login', 'No SaaS active',
    ];
}

function br_render_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . br_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-customer-core.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-business-layer.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-business-ready.css">';
    echo '</head><body class="m360-rtl p9br-page"><div class="p9br-wrap">';
    echo '<div class="p9br-readonly-banner">گزارش مدیریتی read-only — بدون حسابداری رسمی / مالیات / فاکتور نهایی</div>';
}

function br_render_foot(): void
{
    echo '<p class="p9br-footer">';
    echo '<a href="erp-management-dashboard.php">داشبورد مدیریت</a> · ';
    echo '<a href="erp-kpi-report.php">KPI</a> · ';
    echo '<a href="erp-soft-run-audit.php">Soft Run Audit</a> · ';
    echo '<a href="erp-business-command-center.php">مرکز فرماندهی</a>';
    echo '</p></div></body></html>';
}

function br_error(string $title, string $msg): void
{
    br_render_head($title);
    echo '<div class="p1cc-card p1cc-error"><p>' . br_h($msg) . '</p></div>';
    br_render_foot();
    exit;
}

function br_flash(string $key): string
{
    return match ($key) {
        'snapshot_ok' => 'Snapshot KPI با موفقیت ثبت شد.',
        'audit_ok' => 'وضعیت audit با موفقیت به‌روز شد.',
        default => '',
    };
}

function br_render_table(string $title, array $rows, array $cols): void
{
    echo '<div class="p1cc-card"><h2 class="p9br-section-title">' . br_h($title) . '</h2>';
    if ($rows === []) { echo '<p class="p1cc-hint">داده‌ای نیست یا جدول در دسترس نیست.</p></div>'; return; }
    echo '<table class="p1cc-table"><thead><tr>';
    foreach ($cols as $l) echo '<th>' . br_h($l) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach (array_keys($cols) as $k) echo '<td>' . br_h((string)($row[$k] ?? '—')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
