<?php
declare(strict_types=1);

/**
 * MOGHARE360 P8 — JobCard management timeline (read-only aggregation).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';

/** @var array<string, string> */
const M360_TIMELINE_MILESTONES_FA = [
    'ONLINE_REQUEST' => 'درخواست آنلاین',
    'CONTRACT' => 'قرارداد پذیرش',
    'RECEPTION' => 'پذیرش',
    'TECHNICAL' => 'عملیات فنی',
    'ESTIMATE' => 'برآورد',
    'CUSTOMER_APPROVAL' => 'تأیید مشتری',
    'PARTS_GATE' => 'گیت قطعه',
    'FINANCE_GATE' => 'گیت مالی',
    'WORK_EXECUTION' => 'اجرای کار',
    'PARTS_CONSUMPTION' => 'مصرف قطعه',
    'TECHNICAL_COMPLETION' => 'اتمام فنی',
    'QC' => 'کنترل کیفیت',
    'DELIVERY_READINESS' => 'آمادگی تحویل',
    'FINAL_INVOICE' => 'فاکتور نهایی',
    'SETTLEMENT' => 'تسویه',
    'CUSTOMER_DELIVERY' => 'تحویل مشتری',
    'VEHICLE_RELEASE' => 'خروج خودرو',
    'CLOSED' => 'بستن پرونده',
];

/**
 * @return array{jobcard:?array<string,mixed>,events:list<array<string,mixed>>}
 */
function m360_timeline_build($conn, int $jobcardId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, 'erp_jobcards')) {
        return ['jobcard' => null, 'events' => []];
    }

    $jcRows = customer_core_fetch_rows($conn, 'SELECT TOP 1 j.*, c.full_name AS customer_name, c.primary_mobile AS mobile, v.plate_number, v.brand, v.model FROM dbo.erp_jobcards j LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id WHERE j.jobcard_id = ?', [$jobcardId]);
    $jobcard = $jcRows[0] ?? null;
    if ($jobcard === null) {
        return ['jobcard' => null, 'events' => []];
    }

    $events = [];
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_customer_online_request_history', 'online_request_id', 'event_name', 'created_at', $jobcardId, 'request'));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_intake_contract_events', 'contract_id', 'event_name', 'created_at', $jobcardId, 'contract'));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_jobcard_change_history', 'jobcard_id', 'change_type', 'created_at', $jobcardId, 'jobcard', true));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_service_operation_change_history', 'jobcard_id', 'change_type', 'created_at', $jobcardId, 'service', true));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_estimate_events', 'jobcard_id', 'event_name', 'created_at', $jobcardId, 'estimate', true));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_work_execution_events', 'jobcard_id', 'event_name', 'created_at', $jobcardId, 'work', true));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_qc_events', 'jobcard_id', 'event_name', 'created_at', $jobcardId, 'qc', true));
    $events = array_merge($events, m360_timeline_from_table($conn, 'erp_delivery_events', 'jobcard_id', 'event_name', 'created_at', $jobcardId, 'delivery', true));

    usort($events, static function (array $a, array $b): int {
        return strcmp((string)($a['occurred_at'] ?? ''), (string)($b['occurred_at'] ?? ''));
    });

    return ['jobcard' => m360_mgmt_enrich_pipeline_row($jobcard), 'events' => $events];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_timeline_from_table(
    $conn,
    string $table,
    string $idColumn,
    string $eventColumn,
    string $timeColumn,
    int $jobcardId,
    string $source,
    bool $filterByJobcard = false
): array {
    if (!is_resource($conn) || !customer_core_table_exists($conn, $table)) {
        return [];
    }

    if ($filterByJobcard && customer_core_column_exists($conn, $table, 'jobcard_id')) {
        $sql = 'SELECT * FROM dbo.' . $table . ' WHERE jobcard_id = ? ORDER BY ' . $timeColumn . ' ASC';
        $params = [$jobcardId];
    } elseif ($table === 'erp_customer_online_request_history') {
        if (!customer_core_table_exists($conn, 'erp_customer_online_requests')) {
            return [];
        }
        $sql = 'SELECT h.* FROM dbo.' . $table . ' h INNER JOIN dbo.erp_customer_online_requests r ON r.online_request_id = h.online_request_id WHERE r.converted_jobcard_id = ? ORDER BY h.' . $timeColumn . ' ASC';
        $params = [$jobcardId];
    } elseif ($table === 'erp_intake_contract_events') {
        if (!customer_core_table_exists($conn, 'erp_intake_contracts')) {
            return [];
        }
        $sql = 'SELECT e.* FROM dbo.' . $table . ' e INNER JOIN dbo.erp_intake_contracts c ON c.contract_id = e.contract_id WHERE c.jobcard_id = ? ORDER BY e.' . $timeColumn . ' ASC';
        $params = [$jobcardId];
    } else {
        return [];
    }

    $rows = customer_core_fetch_rows($conn, $sql, $params);
    $out = [];
    foreach ($rows as $row) {
        $name = (string)($row[$eventColumn] ?? $row['event_name'] ?? $row['change_type'] ?? 'EVENT');
        $out[] = [
            'source' => $source,
            'event_name' => $name,
            'event_label_fa' => m360_timeline_label_fa($name),
            'event_note' => (string)($row['event_note'] ?? $row['change_note'] ?? $row['new_value'] ?? ''),
            'occurred_at' => (string)($row[$timeColumn] ?? $row['created_at'] ?? ''),
        ];
    }
    return $out;
}

function m360_timeline_label_fa(string $eventName): string
{
    $map = [
        'JOBCARD_CREATED' => 'ایجاد کارت کار',
        'JOBCARD_CLOSED' => 'بستن پرونده',
        'JOBCARD_VEHICLE_RELEASED' => 'خروج خودرو',
        'JOBCARD_DELIVERY_SIGNED' => 'امضای تحویل',
        'FINAL_INVOICE_FINALIZED' => 'نهایی‌سازی فاکتور',
        'QC_PASSED' => 'QC تأیید',
        'QC_FAILED' => 'QC رد',
        'DELIVERY_READY' => 'آمادگی تحویل',
        'WORK_EXECUTION_COMPLETED' => 'اتمام اجرای کار',
        'ESTIMATE_APPROVED' => 'تأیید برآورد',
        'ONLINE_REQUEST_CONVERTED_TO_JOBCARD' => 'تبدیل درخواست به کارت کار',
    ];
    return $map[strtoupper(trim($eventName))] ?? $eventName;
}
