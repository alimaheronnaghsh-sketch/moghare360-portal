<?php
declare(strict_types=1);

/**
 * MOGHARE360 P8 — Management KPI helper (read-only; no workflow mutation).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-settlement-helper.php';

const M360_MGMT_VIEW_PIPELINE = 'vw_m360_owner_jobcard_pipeline';
const M360_MGMT_VIEW_FINANCIAL = 'vw_m360_owner_financial_control';
const M360_MGMT_VIEW_QC = 'vw_m360_owner_qc_control';

const M360_MGMT_STAGE_ONLINE = 'ONLINE_REQUEST';
const M360_MGMT_STAGE_CONTRACT = 'CONTRACT_PENDING';
const M360_MGMT_STAGE_RECEPTION = 'RECEPTION';
const M360_MGMT_STAGE_TECHNICAL = 'TECHNICAL';
const M360_MGMT_STAGE_ESTIMATE = 'ESTIMATE_APPROVAL';
const M360_MGMT_STAGE_PARTS = 'PARTS_GATE';
const M360_MGMT_STAGE_FINANCE = 'FINANCE_GATE';
const M360_MGMT_STAGE_WORK = 'WORK_EXECUTION';
const M360_MGMT_STAGE_QC = 'QC';
const M360_MGMT_STAGE_DELIVERY_READY = 'DELIVERY_READY';
const M360_MGMT_STAGE_FINAL_INVOICE = 'FINAL_INVOICE';
const M360_MGMT_STAGE_SETTLEMENT = 'SETTLEMENT';
const M360_MGMT_STAGE_CUSTOMER_DELIVERY = 'CUSTOMER_DELIVERY';
const M360_MGMT_STAGE_CLOSED = 'CLOSED';

/** @var array<string, string> */
const M360_MGMT_STAGE_LABELS_FA = [
    M360_MGMT_STAGE_ONLINE => 'درخواست آنلاین',
    M360_MGMT_STAGE_CONTRACT => 'قرارداد پذیرش',
    M360_MGMT_STAGE_RECEPTION => 'پذیرش',
    M360_MGMT_STAGE_TECHNICAL => 'عملیات فنی',
    M360_MGMT_STAGE_ESTIMATE => 'تأیید برآورد',
    M360_MGMT_STAGE_PARTS => 'گیت قطعه',
    M360_MGMT_STAGE_FINANCE => 'گیت مالی',
    M360_MGMT_STAGE_WORK => 'اجرای کار',
    M360_MGMT_STAGE_QC => 'کنترل کیفیت',
    M360_MGMT_STAGE_DELIVERY_READY => 'آماده تحویل',
    M360_MGMT_STAGE_FINAL_INVOICE => 'فاکتور نهایی',
    M360_MGMT_STAGE_SETTLEMENT => 'تسویه',
    M360_MGMT_STAGE_CUSTOMER_DELIVERY => 'تحویل مشتری',
    M360_MGMT_STAGE_CLOSED => 'بسته‌شده',
];

/** @var array<string, string> */
const M360_MGMT_PERIOD_LABELS_FA = [
    'today' => 'امروز',
    '7d' => '۷ روز اخیر',
    '30d' => '۳۰ روز اخیر',
    'all' => 'همه',
];

function m360_mgmt_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_mgmt_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_mgmt_view_exists($conn, string $viewName): bool
{
    if (!is_resource($conn)) {
        return false;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = ?",
        [$viewName]
    );
    return (int)($rows[0]['c'] ?? 0) > 0;
}

/**
 * @return array{from:?string,to:?string,sql_from:string,params:list<mixed>}
 */
function m360_mgmt_period_window(string $period): array
{
    $period = strtolower(trim($period));
    if ($period === 'all') {
        return ['from' => null, 'to' => null, 'sql_from' => '', 'params' => []];
    }
    $hours = match ($period) {
        '7d' => 24 * 7,
        '30d' => 24 * 30,
        default => 24,
    };
    return [
        'from' => gmdate('Y-m-d H:i:s', time() - ($hours * 3600)),
        'to' => null,
        'sql_from' => ' AND created_at >= ?',
        'params' => [gmdate('Y-m-d H:i:s', time() - ($hours * 3600))],
    ];
}

function m360_mgmt_scalar($conn, string $sql, array $params = []): float
{
    if (!is_resource($conn)) {
        return 0.0;
    }
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return 0.0;
    }
    $row = $rows[0];
    foreach ($row as $v) {
        return (float)$v;
    }
    return 0.0;
}

function m360_mgmt_safe_div(float $num, float $den): float
{
    return $den > 0.0 ? ($num / $den) : 0.0;
}

function m360_mgmt_age_hours(?string $dt): float
{
    if ($dt === null || trim($dt) === '') {
        return 0.0;
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return 0.0;
    }
    return max(0.0, (time() - $ts) / 3600.0);
}

function m360_mgmt_is_closed(array $row): bool
{
    return strtoupper(trim((string)($row['jobcard_status'] ?? ''))) === 'CLOSED'
        || !empty($row['jobcard_closed_at']);
}

function m360_mgmt_is_delivery_ready(array $row): bool
{
    if (m360_mgmt_is_closed($row)) {
        return false;
    }
    $qc = strtoupper(trim((string)($row['qc_status'] ?? '')));
    if ($qc === 'DELIVERY_READY') {
        return true;
    }
    if (strtoupper(trim((string)($row['delivery_readiness_status'] ?? ''))) === 'READY') {
        return true;
    }
    return strtoupper(trim((string)($row['jobcard_status'] ?? ''))) === 'DELIVERY_READY';
}

/**
 * @return array{stage:string,label_fa:string}
 */
function m360_mgmt_resolve_stage(array $row): array
{
    if (m360_mgmt_is_closed($row)) {
        return ['stage' => M360_MGMT_STAGE_CLOSED, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_CLOSED]];
    }

    $cd = strtoupper(trim((string)($row['customer_delivery_status'] ?? '')));
    if ($cd === 'VEHICLE_RELEASED' || !empty($row['vehicle_released_at'])) {
        return ['stage' => M360_MGMT_STAGE_CLOSED, 'label_fa' => 'خروج خودرو / در انتظار بستن'];
    }
    if ($cd === 'DELIVERY_SIGNED') {
        return ['stage' => M360_MGMT_STAGE_CUSTOMER_DELIVERY, 'label_fa' => 'امضای تحویل ثبت‌شده'];
    }

    $settle = strtoupper(trim((string)($row['settlement_status'] ?? '')));
    $fi = strtoupper(trim((string)($row['final_invoice_status'] ?? $row['invoice_status'] ?? '')));
    if ($fi === 'FINALIZED' && !in_array($settle, ['SETTLED', 'MANAGER_RELEASE_APPROVED'], true)) {
        return ['stage' => M360_MGMT_STAGE_SETTLEMENT, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_SETTLEMENT]];
    }
    if (in_array($fi, ['DRAFT', 'CALCULATED', 'INTERNAL_REVIEW', 'FINALIZED', 'CUSTOMER_NOTIFIED'], true) || !empty($row['current_final_invoice_id'])) {
        return ['stage' => M360_MGMT_STAGE_FINAL_INVOICE, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_FINAL_INVOICE]];
    }
    if (m360_mgmt_is_delivery_ready($row)) {
        return ['stage' => M360_MGMT_STAGE_DELIVERY_READY, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_DELIVERY_READY]];
    }

    $qc = strtoupper(trim((string)($row['qc_status'] ?? '')));
    if (in_array($qc, ['QC_IN_PROGRESS', 'QC_FAILED', 'REWORK_REQUIRED', 'QC_PASSED', 'READY_FOR_QC'], true)
        || strtoupper(trim((string)($row['work_execution_status'] ?? ''))) === 'READY_FOR_QC') {
        return ['stage' => M360_MGMT_STAGE_QC, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_QC]];
    }

    $wx = strtoupper(trim((string)($row['work_execution_status'] ?? '')));
    if ($wx !== '' && !in_array($wx, ['APPROVED_FOR_WORK'], true)) {
        return ['stage' => M360_MGMT_STAGE_WORK, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_WORK]];
    }

    $parts = strtoupper(trim((string)($row['parts_gate_status'] ?? '')));
    if (str_contains($parts, 'PENDING') || $parts === 'PARTS_PENDING') {
        return ['stage' => M360_MGMT_STAGE_PARTS, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_PARTS]];
    }
    $fin = strtoupper(trim((string)($row['finance_gate_status'] ?? '')));
    if (str_contains($fin, 'PENDING') || $fin === 'FINANCE_PENDING') {
        return ['stage' => M360_MGMT_STAGE_FINANCE, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_FINANCE]];
    }

    $est = strtoupper(trim((string)($row['estimate_status'] ?? '')));
    if (in_array($est, ['DRAFT', 'SENT_TO_CUSTOMER', 'WAITING_FOR_APPROVAL', 'ESTIMATE_SENT', 'INTERNAL_REVIEW'], true)
        || strtoupper(trim((string)($row['technical_status'] ?? ''))) === 'WAITING_FOR_APPROVAL') {
        return ['stage' => M360_MGMT_STAGE_ESTIMATE, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_ESTIMATE]];
    }
    if ($est === 'APPROVED_FOR_WORK' && ($wx === '' || $wx === 'APPROVED_FOR_WORK')) {
        return ['stage' => M360_MGMT_STAGE_WORK, 'label_fa' => 'تأییدشده — آماده اجرا'];
    }

    $tech = strtoupper(trim((string)($row['technical_status'] ?? '')));
    if ($tech !== '' && !in_array($tech, ['TECHNICAL_DONE', 'READY_FOR_ESTIMATE'], true)) {
        return ['stage' => M360_MGMT_STAGE_TECHNICAL, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_TECHNICAL]];
    }

    $contract = strtoupper(trim((string)($row['contract_status'] ?? $row['intake_contract_status'] ?? '')));
    if ($contract !== '' && !in_array($contract, ['SIGNED', 'COMPLETED', 'ACTIVE'], true)) {
        return ['stage' => M360_MGMT_STAGE_CONTRACT, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_CONTRACT]];
    }

    $js = strtoupper(trim((string)($row['jobcard_status'] ?? '')));
    if ($js === '' || in_array($js, ['RECEIVED', 'OPEN', 'INTAKE'], true)) {
        return ['stage' => M360_MGMT_STAGE_RECEPTION, 'label_fa' => M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_RECEPTION]];
    }

    return ['stage' => M360_MGMT_STAGE_RECEPTION, 'label_fa' => $js !== '' ? $js : M360_MGMT_STAGE_LABELS_FA[M360_MGMT_STAGE_RECEPTION]];
}

/**
 * @return list<string>
 */
function m360_mgmt_risk_flags(array $row): array
{
    $flags = [];
    if (m360_mgmt_is_delivery_ready($row)) {
        $rem = (float)($row['remaining_amount'] ?? $row['settlement_remaining_amount'] ?? 0);
        $settle = strtoupper(trim((string)($row['settlement_status'] ?? '')));
        if ($rem > 0 && !in_array($settle, ['SETTLED', 'MANAGER_RELEASE_APPROVED'], true)) {
            $flags[] = 'DELIVERY_READY_UNPAID';
        }
    }
    if (strtoupper(trim((string)($row['qc_status'] ?? ''))) === 'QC_FAILED') {
        $flags[] = 'QC_FAILED';
    }
    if (in_array(strtoupper(trim((string)($row['qc_status'] ?? ''))), ['REWORK_REQUIRED', 'REWORK'], true)) {
        $flags[] = 'REWORK_REQUIRED';
    }
    if ((float)($row['variance_amount'] ?? 0) !== 0.0 && strtoupper(trim((string)($row['variance_status'] ?? ''))) !== 'OK') {
        $flags[] = 'INVOICE_VARIANCE';
    }
    if (!empty($row['manager_release_approved']) || strtoupper(trim((string)($row['settlement_status'] ?? ''))) === 'MANAGER_RELEASE_APPROVED') {
        $flags[] = 'MANAGER_RELEASE';
    }
    if ((strtoupper(trim((string)($row['customer_delivery_status'] ?? ''))) === 'VEHICLE_RELEASED' || !empty($row['vehicle_released_at']))
        && !m360_mgmt_is_closed($row)) {
        $flags[] = 'RELEASED_NOT_CLOSED';
    }
    foreach (m360_mgmt_status_conflicts($row) as $c) {
        $flags[] = 'STATUS_CONFLICT:' . $c;
    }
    return array_values(array_unique($flags));
}

/**
 * @return list<string>
 */
function m360_mgmt_status_conflicts(array $row): array
{
    $conflicts = [];
    if (m360_mgmt_is_closed($row) && empty($row['vehicle_released_at']) && strtoupper(trim((string)($row['customer_delivery_status'] ?? ''))) !== 'VEHICLE_RELEASED') {
        $conflicts[] = 'CLOSED_WITHOUT_RELEASE';
    }
    if (strtoupper(trim((string)($row['customer_delivery_status'] ?? ''))) === 'VEHICLE_RELEASED' && strtoupper(trim((string)($row['customer_delivery_status'] ?? ''))) !== 'DELIVERY_SIGNED'
        && empty($row['customer_delivery_signed_at'])) {
        // released without signed - check separately
    }
    if (!empty($row['vehicle_released_at']) && empty($row['customer_delivery_signed_at'])
        && strtoupper(trim((string)($row['customer_delivery_status'] ?? ''))) !== 'DELIVERY_SIGNED') {
        $conflicts[] = 'RELEASED_WITHOUT_SIGNATURE';
    }
    if (m360_mgmt_is_delivery_ready($row) && !in_array(strtoupper(trim((string)($row['qc_status'] ?? ''))), ['QC_PASSED', 'DELIVERY_READY'], true)) {
        $conflicts[] = 'DELIVERY_READY_QC_MISMATCH';
    }
    $settle = strtoupper(trim((string)($row['settlement_status'] ?? '')));
    $rem = (float)($row['remaining_amount'] ?? $row['settlement_remaining_amount'] ?? 0);
    if ($settle === 'SETTLED' && $rem > 0.01) {
        $conflicts[] = 'SETTLED_WITH_BALANCE';
    }
    return $conflicts;
}

function m360_mgmt_stage_href(int $jobcardId, string $stage): string
{
    return match ($stage) {
        M360_MGMT_STAGE_ONLINE => 'erp-reception-online-requests.php',
        M360_MGMT_STAGE_CONTRACT => 'erp-intake-contracts.php',
        M360_MGMT_STAGE_RECEPTION => 'erp-reception-jobcard-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_TECHNICAL => 'erp-technical-jobcard-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_ESTIMATE, M360_MGMT_STAGE_PARTS, M360_MGMT_STAGE_FINANCE => 'erp-estimate-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_WORK => 'erp-work-execution-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_QC => 'erp-qc-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_DELIVERY_READY, M360_MGMT_STAGE_FINAL_INVOICE => 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_SETTLEMENT => 'erp-settlement-detail.php?jobcard_id=' . $jobcardId,
        M360_MGMT_STAGE_CUSTOMER_DELIVERY => 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId,
        default => 'erp-jobcard-timeline.php?jobcard_id=' . $jobcardId,
    };
}

/**
 * @return list<array<string, mixed>>
 */
function m360_mgmt_fetch_pipeline($conn, int $limit = 400): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return [];
    }
    $limit = max(1, min(500, $limit));

    if (m360_mgmt_view_exists($conn, M360_MGMT_VIEW_PIPELINE)) {
        $rows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP ' . $limit . ' * FROM dbo.' . M360_MGMT_VIEW_PIPELINE . ' ORDER BY jobcard_id DESC'
        );
    } else {
        $fiJoin = customer_core_table_exists($conn, 'erp_final_invoices')
            ? 'LEFT JOIN dbo.erp_final_invoices fi ON fi.final_invoice_id = j.current_final_invoice_id'
            : '';
        $fiCols = customer_core_table_exists($conn, 'erp_final_invoices')
            ? ', fi.invoice_status, fi.total_amount AS invoice_total, fi.variance_amount, fi.variance_status'
            : '';
        $rows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP ' . $limit . ' j.*, c.full_name AS customer_name, c.primary_mobile AS mobile, v.plate_number AS plate_no' . $fiCols . '
             FROM dbo.erp_jobcards j
             LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
             LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
             ' . $fiJoin . '
             ORDER BY j.jobcard_id DESC'
        );
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = m360_mgmt_enrich_pipeline_row($row);
    }
    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function m360_mgmt_enrich_pipeline_row(array $row): array
{
    $last = (string)($row['last_activity_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? '');
    $age = m360_mgmt_age_hours($last !== '' ? $last : (string)($row['created_at'] ?? ''));
    $closed = m360_mgmt_is_closed($row);
    $stage = m360_mgmt_resolve_stage($row);
    if (empty($row['current_stage'])) {
        $row['current_stage'] = $stage['stage'];
        $row['current_stage_label_fa'] = $stage['label_fa'];
    }
    $row['age_hours'] = round($age, 1);
    $row['is_overdue_24'] = !$closed && $age >= 24;
    $row['is_overdue_48'] = !$closed && $age >= 48;
    $row['is_overdue_72'] = !$closed && $age >= 72;
    $flags = m360_mgmt_risk_flags($row);
    $row['risk_flags'] = $flags;
    $row['risk_flags_text'] = implode(', ', $flags);
    $row['stage_href'] = m360_mgmt_stage_href((int)($row['jobcard_id'] ?? 0), (string)$row['current_stage']);
    $row['timeline_href'] = 'erp-jobcard-timeline.php?jobcard_id=' . (int)($row['jobcard_id'] ?? 0);
    return $row;
}

/**
 * @return array<string, mixed>
 */
function m360_mgmt_dashboard_cards($conn, string $period = 'today'): array
{
    $pipeline = m360_mgmt_fetch_pipeline($conn, 500);
    $open = 0;
    $closed = 0;
    $waitingApproval = 0;
    $approvedWork = 0;
    $readyQc = 0;
    $deliveryReady = 0;
    $settlementPending = 0;
    $overdue24 = 0;
    $overdue48 = 0;
    $overdue72 = 0;
    $rework = 0;
    $invoiceTotal = 0.0;
    $paidTotal = 0.0;
    $remainingTotal = 0.0;

    foreach ($pipeline as $row) {
        if (m360_mgmt_is_closed($row)) {
            $closed++;
            continue;
        }
        $open++;
        $stage = (string)($row['current_stage'] ?? '');
        if ($stage === M360_MGMT_STAGE_ESTIMATE) {
            $waitingApproval++;
        }
        if (strtoupper(trim((string)($row['estimate_status'] ?? ''))) === 'APPROVED_FOR_WORK') {
            $approvedWork++;
        }
        if (strtoupper(trim((string)($row['work_execution_status'] ?? ''))) === 'READY_FOR_QC'
            || in_array(strtoupper(trim((string)($row['qc_status'] ?? ''))), ['READY_FOR_QC', 'QC_IN_PROGRESS'], true)) {
            $readyQc++;
        }
        if (m360_mgmt_is_delivery_ready($row)) {
            $deliveryReady++;
        }
        $settle = strtoupper(trim((string)($row['settlement_status'] ?? '')));
        $fi = strtoupper(trim((string)($row['final_invoice_status'] ?? $row['invoice_status'] ?? '')));
        if ($fi === 'FINALIZED' && !in_array($settle, ['SETTLED', 'MANAGER_RELEASE_APPROVED'], true)) {
            $settlementPending++;
        }
        if (!empty($row['is_overdue_24'])) {
            $overdue24++;
        }
        if (!empty($row['is_overdue_48'])) {
            $overdue48++;
        }
        if (!empty($row['is_overdue_72'])) {
            $overdue72++;
        }
        if (in_array(strtoupper(trim((string)($row['qc_status'] ?? ''))), ['REWORK_REQUIRED', 'QC_FAILED'], true)) {
            $rework++;
        }
        $invoiceTotal += (float)($row['final_invoice_amount'] ?? $row['invoice_total'] ?? $row['total_amount'] ?? 0);
        $paidTotal += (float)($row['settlement_amount_paid'] ?? $row['total_paid_amount'] ?? 0);
        $remainingTotal += (float)($row['settlement_remaining_amount'] ?? $row['remaining_amount'] ?? 0);
    }

    return [
        'period' => $period,
        'open_jobcards' => $open,
        'closed_jobcards' => $closed,
        'waiting_approval' => $waitingApproval,
        'approved_for_work' => $approvedWork,
        'ready_for_qc' => $readyQc,
        'delivery_ready' => $deliveryReady,
        'settlement_pending' => $settlementPending,
        'overdue_24h' => $overdue24,
        'overdue_48h' => $overdue48,
        'overdue_72h' => $overdue72,
        'rework_required' => $rework,
        'final_invoice_total' => round($invoiceTotal, 2),
        'paid_amount' => round($paidTotal, 2),
        'remaining_amount' => round($remainingTotal, 2),
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_mgmt_kpi_intake($conn, array $window): array
{
    $table = 'erp_customer_online_requests';
    if (!is_resource($conn) || !customer_core_table_exists($conn, $table)) {
        return ['today' => 0, 'converted' => 0, 'not_converted' => 0, 'conversion_rate' => 0.0];
    }
    $w = $window['sql_from'] ?? '';
    $p = $window['params'] ?? [];
    $total = (int)m360_mgmt_scalar($conn, 'SELECT COUNT(*) AS c FROM dbo.' . $table . ' WHERE 1=1' . $w, $p);
    $converted = (int)m360_mgmt_scalar(
        $conn,
        "SELECT COUNT(*) AS c FROM dbo." . $table . " WHERE request_status = N'CONVERTED_TO_JOBCARD'" . $w,
        $p
    );
    return [
        'today' => $total,
        'converted' => $converted,
        'not_converted' => max(0, $total - $converted),
        'conversion_rate' => round(m360_mgmt_safe_div((float)$converted, (float)$total) * 100, 1),
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_mgmt_kpi_full($conn, string $period = 'today'): array
{
    $window = m360_mgmt_period_window($period);
    $pipeline = m360_mgmt_fetch_pipeline($conn, 500);
    $cards = m360_mgmt_dashboard_cards($conn, $period);

    $qcFailed = 0;
    $reworkRate = 0.0;
    $qcTotal = 0;
    foreach ($pipeline as $row) {
        $qc = strtoupper(trim((string)($row['qc_status'] ?? '')));
        if ($qc !== '') {
            $qcTotal++;
        }
        if ($qc === 'QC_FAILED') {
            $qcFailed++;
        }
    }
    $reworkCount = (int)($cards['rework_required'] ?? 0);
    $reworkRate = m360_mgmt_safe_div((float)$reworkCount, (float)max(1, $qcTotal)) * 100;

    return [
        'period' => $period,
        'cards' => $cards,
        'intake' => m360_mgmt_kpi_intake($conn, $window),
        'qc_failed' => $qcFailed,
        'rework_rate' => round($reworkRate, 1),
        'pipeline_count' => count($pipeline),
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_mgmt_high_risk_rows($conn, int $limit = 30): array
{
    $rows = m360_mgmt_fetch_pipeline($conn, 500);
    $risky = [];
    foreach ($rows as $row) {
        if (($row['risk_flags'] ?? []) !== [] || !empty($row['is_overdue_72'])) {
            $risky[] = $row;
        }
    }
    usort($risky, static fn(array $a, array $b): int => ((float)($b['age_hours'] ?? 0) <=> (float)($a['age_hours'] ?? 0)));
    return array_slice($risky, 0, max(1, min(100, $limit)));
}

function m360_mgmt_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function m360_mgmt_kpi_health_status(array $cards): string
{
    if (($cards['overdue_72h'] ?? 0) > 0
        || ($cards['delivery_ready_unpaid'] ?? 0) > 0
        || ($cards['released_with_balance'] ?? 0) > 0
        || ($cards['qc_failed'] ?? 0) > 0
        || ($cards['status_conflict'] ?? 0) > 0) {
        return 'CRITICAL';
    }
    if (($cards['overdue_24h'] ?? 0) > 0 || ($cards['settlement_pending'] ?? 0) > 0) {
        return 'WARNING';
    }
    return 'OK';
}

/**
 * @return list<array<string, mixed>>
 */
function m360_mgmt_operational_kpi_rows($conn): array
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-financial-control-helper.php';
    $today = m360_mgmt_dashboard_cards($conn, 'today');
    $d7 = m360_mgmt_dashboard_cards($conn, '7d');
    $d30 = m360_mgmt_dashboard_cards($conn, '30d');
    $fin = m360_financial_control_summary($conn);

    $today['delivery_ready_unpaid'] = (int)($fin['delivery_ready_unpaid_count'] ?? 0);
    $today['released_with_balance'] = (int)($fin['released_with_balance_count'] ?? 0);
    $today['qc_failed'] = 0;
    $today['status_conflict'] = 0;
    foreach (m360_mgmt_fetch_pipeline($conn, 500) as $row) {
        if (in_array('QC_FAILED', $row['risk_flags'] ?? [], true)) {
            $today['qc_failed']++;
        }
        if (m360_mgmt_status_conflicts($row) !== []) {
            $today['status_conflict']++;
        }
    }

    $defs = [
        ['key' => 'open_jobcards', 'label' => 'پرونده‌های باز', 'hint' => 'JobCardهایی که هنوز بسته نشده‌اند'],
        ['key' => 'closed_jobcards', 'label' => 'پرونده‌های بسته', 'hint' => 'JobCard با وضعیت CLOSED'],
        ['key' => 'waiting_approval', 'label' => 'منتظر تأیید مشتری', 'hint' => 'مرحله برآورد / تأیید'],
        ['key' => 'delivery_ready', 'label' => 'آماده تحویل', 'hint' => 'پس از QC و آمادگی تحویل'],
        ['key' => 'settlement_pending', 'label' => 'در انتظار تسویه', 'hint' => 'فاکتور نهایی — تسویه نشده'],
        ['key' => 'overdue_72h', 'label' => 'معوق بیش از ۷۲ ساعت', 'hint' => 'بدون فعالیت / باز ماندن طولانی'],
        ['key' => 'delivery_ready_unpaid', 'label' => 'آماده تحویل — تسویه نشده', 'hint' => 'ریسک تحویل بدون تسویه'],
        ['key' => 'released_with_balance', 'label' => 'خروج با مانده', 'hint' => 'خودرو تحویل شده ولی مانده مالی'],
        ['key' => 'qc_failed', 'label' => 'QC ناموفق', 'hint' => 'نیاز به rework یا بازبینی'],
        ['key' => 'status_conflict', 'label' => 'ناسازگاری وضعیت', 'hint' => 'تضاد بین وضعیت‌های مراحل'],
    ];

    $rows = [];
    foreach ($defs as $def) {
        $key = (string)$def['key'];
        $row = [
            'key' => $key,
            'label_fa' => (string)$def['label'],
            'hint_fa' => (string)$def['hint'],
            'today' => (int)($today[$key] ?? 0),
            'days_7' => (int)($d7[$key] ?? 0),
            'days_30' => (int)($d30[$key] ?? 0),
        ];
        $row['status'] = match ($key) {
            'overdue_72h' => ($row['today'] > 0 ? 'CRITICAL' : 'OK'),
            'delivery_ready_unpaid', 'released_with_balance', 'qc_failed', 'status_conflict' => ($row['today'] > 0 ? 'CRITICAL' : 'OK'),
            'settlement_pending', 'overdue_24h' => ($row['today'] > 0 ? 'WARNING' : 'OK'),
            default => m360_mgmt_kpi_health_status(['overdue_24h' => $row['today'], 'settlement_pending' => $key === 'settlement_pending' ? $row['today'] : 0]),
        };
        $rows[] = $row;
    }
    return $rows;
}

function m360_mgmt_nav_links(): array
{
    return [
        ['href' => 'erp-management-dashboard.php', 'label' => 'داشبورد مدیریت'],
        ['href' => 'erp-owner-control-center.php', 'label' => 'مرکز کنترل مالک'],
        ['href' => 'erp-operational-kpi.php', 'label' => 'KPI عملیاتی'],
        ['href' => 'erp-bottleneck-monitor.php', 'label' => 'گلوگاه‌ها'],
        ['href' => 'erp-financial-control-summary.php', 'label' => 'کنترل مالی'],
    ];
}
