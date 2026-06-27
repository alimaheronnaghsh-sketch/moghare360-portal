<?php
declare(strict_types=1);

/**
 * MOGHARE360 P9 — Demo scenario stage map (read-only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-soft-run-helper.php';

/**
 * @return list<array<string, mixed>>
 */
function m360_demo_scenario_stages(): array
{
    return [
        ['stage_code' => 'ONLINE_REQUEST', 'phase' => 'P1', 'label_fa' => 'درخواست آنلاین', 'source_table' => 'erp_customer_online_requests', 'page' => 'erp-reception-online-requests.php'],
        ['stage_code' => 'CONTRACT', 'phase' => 'P1.5', 'label_fa' => 'قرارداد / OTP / امضا', 'source_table' => 'erp_intake_contracts', 'page' => 'erp-intake-contracts.php'],
        ['stage_code' => 'RECEPTION', 'phase' => 'P2', 'label_fa' => 'پذیرش', 'source_table' => 'erp_jobcards', 'page' => 'erp-reception-jobcard-detail.php'],
        ['stage_code' => 'TECHNICAL', 'phase' => 'P3', 'label_fa' => 'تشخیص فنی', 'source_table' => 'erp_service_operations', 'page' => 'erp-technical-jobcard-detail.php'],
        ['stage_code' => 'ESTIMATE', 'phase' => 'P4', 'label_fa' => 'برآورد', 'source_table' => 'erp_estimates', 'page' => 'erp-estimate-detail.php'],
        ['stage_code' => 'CUSTOMER_APPROVAL', 'phase' => 'P4', 'label_fa' => 'تأیید مشتری', 'source_table' => 'erp_estimate_approvals', 'page' => 'erp-estimate-detail.php'],
        ['stage_code' => 'PARTS_GATE', 'phase' => 'P4', 'label_fa' => 'گیت قطعه', 'source_table' => 'erp_estimates', 'page' => 'erp-estimate-detail.php'],
        ['stage_code' => 'FINANCE_GATE', 'phase' => 'P4', 'label_fa' => 'گیت مالی', 'source_table' => 'erp_estimates', 'page' => 'erp-estimate-detail.php'],
        ['stage_code' => 'WORK_EXECUTION', 'phase' => 'P5', 'label_fa' => 'اجرای کار', 'source_table' => 'erp_work_execution_events', 'page' => 'erp-work-execution-detail.php'],
        ['stage_code' => 'PARTS_CONSUMPTION', 'phase' => 'P5', 'label_fa' => 'مصرف قطعه', 'source_table' => 'erp_jobcard_part_usage', 'page' => 'erp-work-execution-detail.php'],
        ['stage_code' => 'TECHNICAL_COMPLETION', 'phase' => 'P5', 'label_fa' => 'اتمام فنی', 'source_table' => 'erp_jobcards', 'page' => 'erp-work-execution-detail.php'],
        ['stage_code' => 'QC', 'phase' => 'P6', 'label_fa' => 'کنترل کیفیت', 'source_table' => 'erp_qc_checks', 'page' => 'erp-qc-detail.php'],
        ['stage_code' => 'DELIVERY_READINESS', 'phase' => 'P6', 'label_fa' => 'آمادگی تحویل', 'source_table' => 'erp_delivery_controls', 'page' => 'erp-qc-detail.php'],
        ['stage_code' => 'FINAL_INVOICE', 'phase' => 'P7', 'label_fa' => 'فاکتور نهایی', 'source_table' => 'erp_final_invoices', 'page' => 'erp-final-invoice-detail.php'],
        ['stage_code' => 'SETTLEMENT', 'phase' => 'P7', 'label_fa' => 'تسویه', 'source_table' => 'erp_settlement_controls', 'page' => 'erp-settlement-detail.php'],
        ['stage_code' => 'CUSTOMER_DELIVERY', 'phase' => 'P7', 'label_fa' => 'تحویل مشتری OTP/امضا', 'source_table' => 'erp_customer_delivery_confirmations', 'page' => 'erp-final-invoice-detail.php'],
        ['stage_code' => 'VEHICLE_RELEASE', 'phase' => 'P7', 'label_fa' => 'خروج خودرو', 'source_table' => 'erp_delivery_controls', 'page' => 'erp-settlement-detail.php'],
        ['stage_code' => 'JOBCARD_CLOSED', 'phase' => 'P7', 'label_fa' => 'بستن JobCard', 'source_table' => 'erp_jobcards', 'page' => 'erp-jobcard-timeline.php'],
        ['stage_code' => 'MANAGEMENT_DASHBOARD', 'phase' => 'P8', 'label_fa' => 'بازتاب داشبورد مدیریت', 'source_table' => 'vw_m360_owner_jobcard_pipeline', 'page' => 'erp-management-dashboard.php'],
    ];
}

function m360_demo_stage_page_link(array $stageDef, int $jobcardId): string
{
    $page = (string)($stageDef['page'] ?? '#');
    if ($jobcardId > 0 && str_contains($page, 'detail.php')) {
        return $page . '?jobcard_id=' . $jobcardId;
    }
    if ($stageDef['stage_code'] === 'JOBCARD_CLOSED' && $jobcardId > 0) {
        return 'erp-jobcard-timeline.php?jobcard_id=' . $jobcardId;
    }
    return $page;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_demo_scenario_status($conn, int $jobcardId = 0): array
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-e2e-validation-helper.php';
    if ($jobcardId < 1 && is_resource($conn)) {
        $jc = m360_soft_run_find_demo_jobcard($conn);
        $jobcardId = (int)($jc['jobcard_id'] ?? 0);
    }
    $validated = ($jobcardId > 0 && is_resource($conn)) ? m360_e2e_validate_jobcard($conn, $jobcardId) : [];
    $byCode = [];
    foreach ($validated as $row) {
        $byCode[(string)$row['stage_code']] = $row;
    }

    $out = [];
    foreach (m360_demo_scenario_stages() as $def) {
        $code = (string)$def['stage_code'];
        $ev = $byCode[$code] ?? [
            'stage_status' => M360_SOFT_RUN_STATUS_NOT_RUN,
            'evidence_table' => (string)$def['source_table'],
            'evidence_id' => 0,
            'message_fa' => 'داده demo یافت نشد یا مرحله اجرا نشده است.',
            'gate_result' => M360_SOFT_RUN_STATUS_NOT_RUN,
            'audit_result' => M360_SOFT_RUN_STATUS_NOT_RUN,
            'risk_level' => 'LOW',
        ];
        $out[] = array_merge($def, [
            'stage_status' => (string)($ev['stage_status'] ?? M360_SOFT_RUN_STATUS_NOT_RUN),
            'evidence_table' => (string)($ev['evidence_table'] ?? $def['source_table']),
            'evidence_id' => (int)($ev['evidence_id'] ?? 0),
            'message_fa' => (string)($ev['message_fa'] ?? ''),
            'gate_result' => (string)($ev['gate_result'] ?? M360_SOFT_RUN_STATUS_NOT_RUN),
            'audit_result' => (string)($ev['audit_result'] ?? M360_SOFT_RUN_STATUS_NOT_RUN),
            'page_link' => m360_demo_stage_page_link($def, $jobcardId),
            'warning' => $jobcardId < 1 ? 'JobCard نمایشی یافت نشد.' : '',
        ]);
    }
    return $out;
}
