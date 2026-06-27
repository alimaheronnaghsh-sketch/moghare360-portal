<?php
declare(strict_types=1);

/**
 * MOGHARE360 P9 — End-to-end gate validation (read-only evidence; no bypass).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-soft-run-helper.php';

/**
 * @return list<array<string, mixed>>
 */
function m360_e2e_validate_jobcard($conn, int $jobcardId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return [];
    }

    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE jobcard_id = ?', [$jobcardId]);
    $jc = $rows[0] ?? null;
    if ($jc === null) {
        return [];
    }

    $stages = [];
    $stages[] = m360_e2e_check_online_request($conn, $jobcardId);
    $stages[] = m360_e2e_check_contract($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_simple($conn, 'RECEPTION', 'erp_jobcards', $jobcardId, $jobcardId > 0, 'پذیرش JobCard ثبت شده است.');
    $stages[] = m360_e2e_check_technical($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_estimate($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_customer_approval($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_parts_gate($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_finance_gate($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_work_execution($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_parts_consumption($conn, $jobcardId);
    $stages[] = m360_e2e_check_technical_completion($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_qc($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_delivery_readiness($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_final_invoice($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_settlement($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_customer_delivery($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_vehicle_release($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_closed($conn, $jobcardId, $jc);
    $stages[] = m360_e2e_check_management($conn, $jobcardId);

    return $stages;
}

/**
 * @return array<string, mixed>
 */
function m360_e2e_result(
    string $stageCode,
    bool $ok,
    string $table,
    int $evidenceId,
    string $messageFa,
    string $gateStatus = M360_SOFT_RUN_STATUS_PASS,
    string $risk = 'LOW'
): array {
    return [
        'stage_code' => $stageCode,
        'stage_status' => $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING,
        'evidence_table' => $table,
        'evidence_id' => $evidenceId,
        'message_fa' => $messageFa,
        'gate_result' => $gateStatus,
        'audit_result' => $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING,
        'risk_level' => $risk,
    ];
}

function m360_e2e_check_online_request($conn, int $jobcardId): array
{
    if (!customer_core_table_exists($conn, 'erp_customer_online_requests')) {
        return m360_e2e_result('ONLINE_REQUEST', false, 'erp_customer_online_requests', 0, 'جدول درخواست آنلاین موجود نیست.', M360_SOFT_RUN_STATUS_NOT_RUN);
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 online_request_id, request_status FROM dbo.erp_customer_online_requests WHERE converted_jobcard_id = ? ORDER BY online_request_id DESC',
        [$jobcardId]
    );
    $row = $rows[0] ?? null;
    if ($row === null) {
        return m360_e2e_result('ONLINE_REQUEST', false, 'erp_customer_online_requests', 0, 'درخواست آنلاین مرتبط یافت نشد (demo ممکن است از پذیرش مستقیم باشد).', M360_SOFT_RUN_STATUS_WARNING, 'MEDIUM');
    }
    return m360_e2e_result('ONLINE_REQUEST', true, 'erp_customer_online_requests', (int)$row['online_request_id'], 'درخواست آنلاین به JobCard متصل است.');
}

function m360_e2e_check_contract($conn, int $jobcardId, array $jc): array
{
    if (!customer_core_table_exists($conn, 'erp_intake_contracts')) {
        return m360_e2e_result('CONTRACT', false, 'erp_intake_contracts', 0, 'جدول قرارداد موجود نیست.', M360_SOFT_RUN_STATUS_NOT_RUN);
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 contract_id, contract_status FROM dbo.erp_intake_contracts WHERE jobcard_id = ? ORDER BY contract_id DESC',
        [$jobcardId]
    );
    $row = $rows[0] ?? null;
    if ($row === null) {
        return m360_e2e_result('CONTRACT', false, 'erp_intake_contracts', 0, 'قرارداد پذیرش یافت نشد.', M360_SOFT_RUN_STATUS_BLOCKED, 'HIGH');
    }
    $signed = in_array(strtoupper((string)($row['contract_status'] ?? '')), ['SIGNED', 'COMPLETED', 'ACTIVE'], true);
    $gate = $signed ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
    if (is_file(__DIR__ . '/m360-intake-contract-helper.php')) {
        require_once __DIR__ . '/m360-intake-contract-helper.php';
        if (function_exists('m360_intake_contract_find_active_for_jobcard')) {
            $c = m360_intake_contract_find_active_for_jobcard($conn, $jobcardId);
            if ($c !== null && function_exists('m360_intake_contract_is_signed')) {
                $signed = m360_intake_contract_is_signed($c);
                $gate = $signed ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_BLOCKED;
            }
        }
    }
    return m360_e2e_result('CONTRACT', $signed, 'erp_intake_contracts', (int)$row['contract_id'], $signed ? 'قرارداد امضا/فعال است.' : 'گیت P1.5 — قرارداد امضا نشده.', $gate, $signed ? 'LOW' : 'HIGH');
}

function m360_e2e_check_simple($conn, string $code, string $table, int $id, bool $ok, string $msg): array
{
    return m360_e2e_result($code, $ok, $table, $id, $msg);
}

function m360_e2e_check_technical($conn, int $jobcardId, array $jc): array
{
    $tech = strtoupper(trim((string)($jc['technical_status'] ?? '')));
    $id = 0;
    if (customer_core_table_exists($conn, 'erp_service_operations')) {
        $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 service_operation_id FROM dbo.erp_service_operations WHERE jobcard_id = ? ORDER BY service_operation_id DESC', [$jobcardId]);
        $id = (int)($rows[0]['service_operation_id'] ?? 0);
    }
    $ok = $tech !== '' || $id > 0;
    $gate = M360_SOFT_RUN_STATUS_PASS;
    if (is_file(__DIR__ . '/m360-technical-operation-helper.php')) {
        require_once __DIR__ . '/m360-technical-operation-helper.php';
        if (function_exists('m360_technical_assert_gates')) {
            $g = m360_technical_assert_gates($conn, $jobcardId, $jc);
            $gate = !empty($g['ok']) ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
        }
    }
    return m360_e2e_result('TECHNICAL', $ok, 'erp_service_operations', $id, $ok ? 'مسیر فنی ثبت شده است.' : 'عملیات فنی یافت نشد.', $gate);
}

function m360_e2e_check_estimate($conn, int $jobcardId, array $jc): array
{
    if (!customer_core_table_exists($conn, 'erp_estimates')) {
        return m360_e2e_result('ESTIMATE', false, 'erp_estimates', 0, 'جدول برآورد موجود نیست.', M360_SOFT_RUN_STATUS_NOT_RUN);
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 estimate_id, estimate_status FROM dbo.erp_estimates WHERE jobcard_id = ? AND estimate_status <> N\'CANCELLED\' ORDER BY estimate_id DESC', [$jobcardId]);
    $row = $rows[0] ?? null;
    return m360_e2e_result('ESTIMATE', $row !== null, 'erp_estimates', (int)($row['estimate_id'] ?? 0), $row !== null ? 'برآورد ثبت شده است.' : 'برآورد یافت نشد.');
}

function m360_e2e_check_customer_approval($conn, int $jobcardId, array $jc): array
{
    $estStatus = strtoupper(trim((string)($jc['estimate_status'] ?? '')));
    $ok = in_array($estStatus, ['APPROVED_FOR_WORK', 'ESTIMATE_APPROVED', 'APPROVED'], true);
    $id = 0;
    if (customer_core_table_exists($conn, 'erp_estimate_approvals')) {
        $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 approval_id FROM dbo.erp_estimate_approvals WHERE jobcard_id = ? ORDER BY approval_id DESC', [$jobcardId]);
        $id = (int)($rows[0]['approval_id'] ?? 0);
        $ok = $ok || $id > 0;
    }
    return m360_e2e_result('CUSTOMER_APPROVAL', $ok, 'erp_estimate_approvals', $id, $ok ? 'تأیید مشتری/برآورد ثبت شده.' : 'تأیید مشتری کامل نیست.', $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING, 'MEDIUM');
}

function m360_e2e_check_parts_gate($conn, int $jobcardId, array $jc): array
{
    $parts = strtoupper(trim((string)($jc['parts_gate_status'] ?? '')));
    $ok = $parts === '' || str_contains($parts, 'CLEARED') || str_contains($parts, 'NOT_REQUIRED') || $parts === 'PARTS_CLEARED';
    return m360_e2e_result('PARTS_GATE', $ok, 'erp_estimates', (int)($jc['current_estimate_id'] ?? 0), $ok ? 'گیت قطعه عبور کرده یا لازم نبوده.' : 'گیت قطعه pending است.', $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING);
}

function m360_e2e_check_finance_gate($conn, int $jobcardId, array $jc): array
{
    $fin = strtoupper(trim((string)($jc['finance_gate_status'] ?? '')));
    $ok = $fin === '' || str_contains($fin, 'CLEARED') || str_contains($fin, 'NOT_REQUIRED') || $fin === 'FINANCE_CLEARED';
    return m360_e2e_result('FINANCE_GATE', $ok, 'erp_estimates', (int)($jc['current_estimate_id'] ?? 0), $ok ? 'گیت مالی عبور کرده یا لازم نبوده.' : 'گیت مالی pending است.', $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING);
}

function m360_e2e_check_work_execution($conn, int $jobcardId, array $jc): array
{
    $wx = strtoupper(trim((string)($jc['work_execution_status'] ?? '')));
    $id = 0;
    if (customer_core_table_exists($conn, 'erp_work_execution_events')) {
        $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 event_id FROM dbo.erp_work_execution_events WHERE jobcard_id = ? ORDER BY event_id DESC', [$jobcardId]);
        $id = (int)($rows[0]['event_id'] ?? 0);
    }
    $ok = $wx !== '' || $id > 0;
    $gate = M360_SOFT_RUN_STATUS_PASS;
    if (is_file(__DIR__ . '/m360-work-execution-helper.php')) {
        require_once __DIR__ . '/m360-work-execution-helper.php';
        if (function_exists('m360_work_assert_gates')) {
            $g = m360_work_assert_gates($conn, $jobcardId, $jc);
            $gate = !empty($g['ok']) ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
        }
    }
    return m360_e2e_result('WORK_EXECUTION', $ok, 'erp_work_execution_events', $id, $ok ? 'اجرای کار ثبت شده.' : 'اجرای کار یافت نشد.', $gate);
}

function m360_e2e_check_parts_consumption($conn, int $jobcardId): array
{
    if (!customer_core_table_exists($conn, 'erp_jobcard_part_usage')) {
        return m360_e2e_result('PARTS_CONSUMPTION', false, 'erp_jobcard_part_usage', 0, 'جدول مصرف قطعه موجود نیست.', M360_SOFT_RUN_STATUS_NOT_RUN);
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 part_usage_id FROM dbo.erp_jobcard_part_usage WHERE jobcard_id = ? ORDER BY part_usage_id DESC', [$jobcardId]);
    $id = (int)($rows[0]['part_usage_id'] ?? 0);
    return m360_e2e_result('PARTS_CONSUMPTION', $id > 0, 'erp_jobcard_part_usage', $id, $id > 0 ? 'مصرف قطعه ثبت شده.' : 'مصرف قطعه demo ثبت نشده (ممکن است optional).', M360_SOFT_RUN_STATUS_WARNING, 'LOW');
}

function m360_e2e_check_technical_completion($conn, int $jobcardId, array $jc): array
{
    $wx = strtoupper(trim((string)($jc['work_execution_status'] ?? '')));
    $ok = in_array($wx, ['READY_FOR_QC', 'COMPLETED', 'TECHNICAL_COMPLETED'], true) || !empty($jc['ready_for_qc_at']);
    return m360_e2e_result('TECHNICAL_COMPLETION', $ok, 'erp_jobcards', $jobcardId, $ok ? 'اتمام فنی / آماده QC.' : 'هنوز READY_FOR_QC نشده.', $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING);
}

function m360_e2e_check_qc($conn, int $jobcardId, array $jc): array
{
    $qc = strtoupper(trim((string)($jc['qc_status'] ?? '')));
    $id = 0;
    if (customer_core_table_exists($conn, 'erp_qc_checks')) {
        $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 qc_check_id, qc_status FROM dbo.erp_qc_checks WHERE jobcard_id = ? ORDER BY qc_check_id DESC', [$jobcardId]);
        $id = (int)($rows[0]['qc_check_id'] ?? 0);
    }
    $ok = in_array($qc, ['QC_PASSED', 'DELIVERY_READY'], true);
    $gate = M360_SOFT_RUN_STATUS_PASS;
    if (is_file(__DIR__ . '/m360-qc-helper.php')) {
        require_once __DIR__ . '/m360-qc-helper.php';
        if (function_exists('m360_qc_assert_gates')) {
            $g = m360_qc_assert_gates($conn, $jobcardId, $jc);
            $gate = !empty($g['ok']) ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING;
        }
    }
    return m360_e2e_result('QC', $ok, 'erp_qc_checks', $id, $ok ? 'QC passed.' : 'QC کامل نیست.', $gate, $ok ? 'LOW' : 'MEDIUM');
}

function m360_e2e_check_delivery_readiness($conn, int $jobcardId, array $jc): array
{
    $ready = strtoupper(trim((string)($jc['delivery_readiness_status'] ?? ''))) === 'READY'
        || strtoupper(trim((string)($jc['qc_status'] ?? ''))) === 'DELIVERY_READY';
    return m360_e2e_result('DELIVERY_READINESS', $ready, 'erp_delivery_controls', $jobcardId, $ready ? 'آمادگی تحویل ثبت شده.' : 'آمادگی تحویل کامل نیست.');
}

function m360_e2e_check_final_invoice($conn, int $jobcardId, array $jc): array
{
    $fi = strtoupper(trim((string)($jc['final_invoice_status'] ?? '')));
    $id = (int)($jc['current_final_invoice_id'] ?? 0);
    if ($id < 1 && customer_core_table_exists($conn, 'erp_final_invoices')) {
        $rows = customer_core_fetch_rows($conn, "SELECT TOP 1 final_invoice_id, invoice_status FROM dbo.erp_final_invoices WHERE jobcard_id = ? AND invoice_status = N'FINALIZED' ORDER BY final_invoice_id DESC", [$jobcardId]);
        $id = (int)($rows[0]['final_invoice_id'] ?? 0);
        $fi = strtoupper((string)($rows[0]['invoice_status'] ?? $fi));
    }
    $ok = $fi === 'FINALIZED' || $id > 0;
    return m360_e2e_result('FINAL_INVOICE', $ok, 'erp_final_invoices', $id, $ok ? 'فاکتور نهایی finalized.' : 'فاکتور نهایی finalized نشده.');
}

function m360_e2e_check_settlement($conn, int $jobcardId, array $jc): array
{
    $st = strtoupper(trim((string)($jc['settlement_status'] ?? '')));
    $ok = in_array($st, ['SETTLED', 'MANAGER_RELEASE_APPROVED'], true);
    $id = 0;
    if (customer_core_table_exists($conn, 'erp_settlement_controls')) {
        $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 settlement_id, settlement_status FROM dbo.erp_settlement_controls WHERE jobcard_id = ? ORDER BY settlement_id DESC', [$jobcardId]);
        $id = (int)($rows[0]['settlement_id'] ?? 0);
        if (!$ok) {
            $ok = in_array(strtoupper((string)($rows[0]['settlement_status'] ?? '')), ['SETTLED', 'MANAGER_RELEASE_APPROVED'], true);
        }
    }
    return m360_e2e_result('SETTLEMENT', $ok, 'erp_settlement_controls', $id, $ok ? 'تسویه معتبر.' : 'تسویه کامل نیست.', $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING);
}

function m360_e2e_check_customer_delivery($conn, int $jobcardId, array $jc): array
{
    $cd = strtoupper(trim((string)($jc['customer_delivery_status'] ?? '')));
    $id = 0;
    if (customer_core_table_exists($conn, 'erp_customer_delivery_confirmations')) {
        $rows = customer_core_fetch_rows($conn, "SELECT TOP 1 delivery_confirmation_id FROM dbo.erp_customer_delivery_confirmations WHERE jobcard_id = ? AND confirmation_status IN (N'DELIVERY_SIGNED', N'SIGNED', N'CONFIRMED') ORDER BY delivery_confirmation_id DESC", [$jobcardId]);
        $id = (int)($rows[0]['delivery_confirmation_id'] ?? 0);
    }
    $ok = $cd === 'DELIVERY_SIGNED' || $id > 0 || !empty($jc['customer_delivery_signed_at']);
    return m360_e2e_result('CUSTOMER_DELIVERY', $ok, 'erp_customer_delivery_confirmations', $id, $ok ? 'تحویل مشتری امضا/OTP ثبت شده.' : 'امضای تحویل مشتری ثبت نشده.');
}

function m360_e2e_check_vehicle_release($conn, int $jobcardId, array $jc): array
{
    $ok = strtoupper(trim((string)($jc['customer_delivery_status'] ?? ''))) === 'VEHICLE_RELEASED' || !empty($jc['vehicle_released_at']);
    return m360_e2e_result('VEHICLE_RELEASE', $ok, 'erp_delivery_controls', $jobcardId, $ok ? 'خروج خودرو ثبت شده.' : 'خروج خودرو ثبت نشده.');
}

function m360_e2e_check_closed($conn, int $jobcardId, array $jc): array
{
    $ok = strtoupper(trim((string)($jc['jobcard_status'] ?? ''))) === 'CLOSED' || !empty($jc['jobcard_closed_at']);
    return m360_e2e_result('JOBCARD_CLOSED', $ok, 'erp_jobcards', $jobcardId, $ok ? 'JobCard بسته شده.' : 'JobCard هنوز CLOSED نشده.');
}

function m360_e2e_check_management($conn, int $jobcardId): array
{
    require_once __DIR__ . '/m360-management-kpi-helper.php';
    $viewsOk = is_resource($conn) && m360_mgmt_view_exists($conn, M360_MGMT_VIEW_PIPELINE);
    $timelineOk = is_file(dirname(__DIR__) . '/erp-jobcard-timeline.php');
    $ok = $viewsOk && $timelineOk;
    return m360_e2e_result('MANAGEMENT_DASHBOARD', $ok, 'vw_m360_owner_jobcard_pipeline', $jobcardId, $ok ? 'P8 dashboard/timeline در دسترس است.' : 'View یا timeline P8 در دسترس نیست.', $ok ? M360_SOFT_RUN_STATUS_PASS : M360_SOFT_RUN_STATUS_WARNING);
}
