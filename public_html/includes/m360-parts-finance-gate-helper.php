<?php
declare(strict_types=1);

/**
 * MOGHARE360 P4 — Parts and finance gate helpers (read/check only, no full inventory/payment).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_GATE_PARTS_PENDING = 'PENDING';
const M360_GATE_PARTS_CLEARED = 'CLEARED';
const M360_GATE_PARTS_NOT_REQUIRED = 'NOT_REQUIRED';

const M360_GATE_FINANCE_PENDING = 'PENDING';
const M360_GATE_FINANCE_CLEARED = 'CLEARED';
const M360_GATE_FINANCE_NOT_REQUIRED = 'NOT_REQUIRED';

const M360_FINANCE_ADVANCE_MIN_PERCENT = 0.5;
const M360_FINANCE_ROUND_UNIT = 10000000;

function m360_finance_calculate_advance(float $totalAmount): float
{
    if ($totalAmount <= 0) {
        return 0.0;
    }
    $raw = $totalAmount * M360_FINANCE_ADVANCE_MIN_PERCENT;
    $unit = (float)M360_FINANCE_ROUND_UNIT;
    return ceil($raw / $unit) * $unit;
}

/**
 * @return array{parts_required:bool,parts_gate_status:string,message:string}
 */
function m360_parts_gate_evaluate($conn, int $estimateId): array
{
    $default = ['parts_required' => false, 'parts_gate_status' => M360_GATE_PARTS_NOT_REQUIRED, 'message' => ''];
    if (!is_resource($conn) || $estimateId < 1 || !customer_core_table_exists($conn, 'erp_estimate_items')) {
        return $default;
    }

    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT COUNT(*) AS c FROM dbo.erp_estimate_items WHERE estimate_id = ? AND item_type = N'PART' AND item_status <> N'REMOVED'",
        [$estimateId]
    );
    $partCount = (int)($rows[0]['c'] ?? 0);
    if ($partCount < 1) {
        return $default;
    }

    $jobcardId = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 jobcard_id FROM dbo.erp_estimates WHERE estimate_id = ?',
        [$estimateId]
    ) ?? 0);

    if ($jobcardId > 0 && customer_core_table_exists($conn, 'erp_jobcard_part_usage')) {
        $usage = customer_core_fetch_rows(
            $conn,
            "SELECT COUNT(*) AS c FROM dbo.erp_jobcard_part_usage WHERE jobcard_id = ? AND is_active = 1 AND usage_status IN (N'RESERVED', N'USED', N'CONFIRMED')",
            [$jobcardId]
        );
        if ((int)($usage[0]['c'] ?? 0) >= $partCount) {
            return [
                'parts_required' => true,
                'parts_gate_status' => M360_GATE_PARTS_CLEARED,
                'message' => 'قطعات تأیید یا رزرو شده‌اند.',
            ];
        }
    }

    if ($jobcardId > 0 && customer_core_table_exists($conn, 'erp_purchase_requests')) {
        $pr = customer_core_fetch_rows(
            $conn,
            "SELECT COUNT(*) AS c FROM dbo.erp_purchase_requests WHERE jobcard_id = ? AND is_active = 1 AND request_status IN (N'APPROVED', N'ORDERED', N'RECEIVED')",
            [$jobcardId]
        );
        if ((int)($pr[0]['c'] ?? 0) > 0) {
            return [
                'parts_required' => true,
                'parts_gate_status' => M360_GATE_PARTS_PENDING,
                'message' => 'درخواست خرید قطعه در جریان است.',
            ];
        }
    }

    return [
        'parts_required' => true,
        'parts_gate_status' => M360_GATE_PARTS_PENDING,
        'message' => 'نیاز قطعه — آماده‌سازی در انتظار است.',
    ];
}

/**
 * @return array{finance_required:bool,finance_gate_status:string,advance_required:float,message:string}
 */
function m360_finance_gate_evaluate($conn, int $estimateId, ?float $totalAmount = null, ?float $advanceRequired = null): array
{
    if (!is_resource($conn) || $estimateId < 1) {
        return [
            'finance_required' => true,
            'finance_gate_status' => M360_GATE_FINANCE_PENDING,
            'advance_required' => 0.0,
            'message' => 'اتصال نامعتبر',
        ];
    }

    if ($totalAmount === null || $advanceRequired === null) {
        $row = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 total_amount, advance_required_amount, finance_required, finance_gate_status, jobcard_id FROM dbo.erp_estimates WHERE estimate_id = ?',
            [$estimateId]
        );
        if ($row === []) {
            return [
                'finance_required' => true,
                'finance_gate_status' => M360_GATE_FINANCE_PENDING,
                'advance_required' => 0.0,
                'message' => 'برآورد یافت نشد',
            ];
        }
        $totalAmount = (float)($row[0]['total_amount'] ?? 0);
        $advanceRequired = (float)($row[0]['advance_required_amount'] ?? m360_finance_calculate_advance($totalAmount));
        if (!(bool)($row[0]['finance_required'] ?? true)) {
            return [
                'finance_required' => false,
                'finance_gate_status' => M360_GATE_FINANCE_NOT_REQUIRED,
                'advance_required' => 0.0,
                'message' => 'علی‌الحساب لازم نیست.',
            ];
        }
        $jobcardId = (int)($row[0]['jobcard_id'] ?? 0);
    } else {
        $jobcardId = (int)(customer_core_scalar(
            $conn,
            'SELECT TOP 1 jobcard_id FROM dbo.erp_estimates WHERE estimate_id = ?',
            [$estimateId]
        ) ?? 0);
    }

    if ($totalAmount <= 0) {
        return [
            'finance_required' => true,
            'finance_gate_status' => M360_GATE_FINANCE_PENDING,
            'advance_required' => 0.0,
            'message' => 'مبلغ برآورد صفر است — مجوز مالی لازم است.',
        ];
    }

    if ($advanceRequired <= 0) {
        return [
            'finance_required' => false,
            'finance_gate_status' => M360_GATE_FINANCE_NOT_REQUIRED,
            'advance_required' => 0.0,
            'message' => 'علی‌الحساب لازم نیست.',
        ];
    }

    if ($jobcardId > 0 && customer_core_table_exists($conn, 'erp_payments')) {
        $paid = customer_core_fetch_rows(
            $conn,
            "SELECT ISNULL(SUM(payment_amount), 0) AS s FROM dbo.erp_payments WHERE jobcard_id = ? AND is_active = 1 AND payment_status IN (N'RECEIVED', N'CONFIRMED', N'PARTIAL')",
            [$jobcardId]
        );
        $sum = (float)($paid[0]['s'] ?? 0);
        if ($sum >= $advanceRequired) {
            return [
                'finance_required' => true,
                'finance_gate_status' => M360_GATE_FINANCE_CLEARED,
                'advance_required' => $advanceRequired,
                'message' => 'مجوز مالی تأیید شده است.',
            ];
        }
    }

    return [
        'finance_required' => true,
        'finance_gate_status' => M360_GATE_FINANCE_PENDING,
        'advance_required' => $advanceRequired,
        'message' => 'علی‌الحساب دریافت نشده است.',
    ];
}

/**
 * @return array{ok:bool,parts_gate_status:string,finance_gate_status:string}
 */
function m360_gates_can_approve_for_work($conn, array $estimateRow): array
{
    $estimateId = (int)($estimateRow['estimate_id'] ?? 0);
    $estStatus = strtoupper((string)($estimateRow['estimate_status'] ?? ''));
    if ($estStatus !== 'CUSTOMER_APPROVED') {
        return ['ok' => false, 'parts_gate_status' => '', 'finance_gate_status' => ''];
    }

    $partsStatus = strtoupper((string)($estimateRow['parts_gate_status'] ?? ''));
    $financeStatus = strtoupper((string)($estimateRow['finance_gate_status'] ?? ''));

    if (!in_array($partsStatus, [M360_GATE_PARTS_CLEARED, M360_GATE_PARTS_NOT_REQUIRED], true)) {
        return ['ok' => false, 'parts_gate_status' => $partsStatus, 'finance_gate_status' => $financeStatus];
    }
    if (!in_array($financeStatus, [M360_GATE_FINANCE_CLEARED, M360_GATE_FINANCE_NOT_REQUIRED], true)) {
        return ['ok' => false, 'parts_gate_status' => $partsStatus, 'finance_gate_status' => $financeStatus];
    }

    return ['ok' => true, 'parts_gate_status' => $partsStatus, 'finance_gate_status' => $financeStatus];
}
