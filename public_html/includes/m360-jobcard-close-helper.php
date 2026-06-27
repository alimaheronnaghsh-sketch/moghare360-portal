<?php
declare(strict_types=1);

/**
 * MOGHARE360 P7 — Vehicle release and jobcard close (no accounting voucher).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

foreach (['m360-settlement-helper.php', 'm360-customer-delivery-helper.php'] as $m360P7Helper) {
    $helperPath = __DIR__ . DIRECTORY_SEPARATOR . $m360P7Helper;
    if (is_file($helperPath)) {
        require_once $helperPath;
    }
}

if (!defined('M360_DEL_CONTROLS_TABLE')) {
    define('M360_DEL_CONTROLS_TABLE', 'erp_delivery_controls');
}
const M360_DEL_EVENTS = 'erp_delivery_events';
const M360_JC_HISTORY = 'erp_jobcard_change_history';

const M360_JC_FINALIZED = 'FINALIZED';
const M360_JC_DELIVERY_SIGNED = 'DELIVERY_SIGNED';
const M360_JC_VEHICLE_RELEASED = 'VEHICLE_RELEASED';
const M360_JC_QC_PASSED = 'QC_PASSED';
const M360_JC_DELIVERY_READY = 'DELIVERY_READY';
const M360_JC_DEL_READY = 'READY';
const M360_JC_DEL_RELEASED = 'RELEASED';
const M360_JC_CLOSED = 'CLOSED';

function m360_jc_close_write_history($conn, int $jobcardId, string $type, ?string $prev, ?string $new, string $summary, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_JC_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_JC_HISTORY . ' (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $type, $prev, $new, $summary, $userId]
    );
}

function m360_jc_close_write_event($conn, int $jobcardId, string $name, ?string $note, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_DEL_EVENTS)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_DEL_EVENTS . ' (jobcard_id, event_name, event_note, created_by_user_id) VALUES (?, ?, ?, ?)',
        [$jobcardId, $name, $note, $userId]
    );
}

/**
 * @return bool
 */
function m360_jc_close_invoice_finalized($conn, int $jobcardId, array $jobcardRow): bool
{
    $invStatus = strtoupper(trim((string)($jobcardRow['final_invoice_status'] ?? '')));
    if ($invStatus === M360_JC_FINALIZED) {
        return true;
    }

    if (!customer_core_table_exists($conn, 'erp_final_invoices')) {
        return false;
    }

    $invoiceId = (int)($jobcardRow['current_final_invoice_id'] ?? 0);
    if ($invoiceId > 0) {
        $rows = customer_core_fetch_rows(
            $conn,
            'SELECT TOP 1 invoice_status FROM dbo.erp_final_invoices WHERE final_invoice_id = ?',
            [$invoiceId]
        );
        if ($rows !== [] && strtoupper(trim((string)($rows[0]['invoice_status'] ?? ''))) === M360_JC_FINALIZED) {
            return true;
        }
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 invoice_status FROM dbo.erp_final_invoices WHERE jobcard_id = ? ORDER BY final_invoice_id DESC',
        [$jobcardId]
    );

    return $rows !== [] && strtoupper(trim((string)($rows[0]['invoice_status'] ?? ''))) === M360_JC_FINALIZED;
}

/**
 * @param array<string, mixed> $jobcardRow
 * @return array{ok:bool,message:string}
 */
function m360_vehicle_release_validate($conn, int $jobcardId, array $jobcardRow): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }

    if (trim((string)($jobcardRow['vehicle_released_at'] ?? '')) !== '') {
        return ['ok' => false, 'message' => 'خودرو قبلاً تحویل داده شده است.'];
    }

    if (!m360_jc_close_invoice_finalized($conn, $jobcardId, $jobcardRow)) {
        return ['ok' => false, 'message' => 'فاکتور نهایی هنوز نهایی نشده است.'];
    }

    $settlement = function_exists('m360_settlement_fetch_active') ? m360_settlement_fetch_active($conn, $jobcardId) : null;
    if ($settlement === null) {
        return ['ok' => false, 'message' => 'رکورد تسویه یافت نشد.'];
    }
    $settleGate = m360_settlement_can_release($conn, $jobcardId, $settlement);
    if (!$settleGate['ok']) {
        return $settleGate;
    }

    $deliveryStatus = strtoupper(trim((string)($jobcardRow['customer_delivery_status'] ?? '')));
    if ($deliveryStatus !== M360_JC_DELIVERY_SIGNED) {
        return ['ok' => false, 'message' => 'امضای تحویل مشتری ثبت نشده است.'];
    }

    $qcStatus = strtoupper(trim((string)($jobcardRow['qc_status'] ?? '')));
    if (!in_array($qcStatus, [M360_JC_QC_PASSED, M360_JC_DELIVERY_READY], true)) {
        return ['ok' => false, 'message' => 'QC باید Pass یا آماده تحویل باشد.'];
    }

    $readiness = strtoupper(trim((string)($jobcardRow['delivery_readiness_status'] ?? '')));
    if ($readiness !== M360_JC_DEL_READY) {
        return ['ok' => false, 'message' => 'آمادگی تحویل (READY) تأیید نشده است.'];
    }

    return ['ok' => true, 'message' => ''];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_vehicle_release($conn, int $jobcardId, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $userId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }
    $jobcardRow = $rows[0];

    $validation = m360_vehicle_release_validate($conn, $jobcardId, $jobcardRow);
    if (!$validation['ok']) {
        return $validation;
    }

    $prevDelivery = strtoupper(trim((string)($jobcardRow['customer_delivery_status'] ?? '')));

    if (customer_core_table_exists($conn, M360_DEL_CONTROLS_TABLE)) {
        customer_core_execute(
            $conn,
            "UPDATE dbo." . M360_DEL_CONTROLS_TABLE . " SET delivery_status = N'RELEASED', released_at = SYSUTCDATETIME()
             WHERE jobcard_id = ? AND is_active = 1 AND delivery_status = N'READY'",
            [$jobcardId]
        );
    }

    $sets = ['vehicle_released_at = SYSUTCDATETIME()', "customer_delivery_status = N'VEHICLE_RELEASED'"];
    $params = [];
    if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $jobcardId;
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?',
        $params
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت تحویل خودرو ناموفق بود.'];
    }

    m360_jc_close_write_history(
        $conn,
        $jobcardId,
        'JOBCARD_VEHICLE_RELEASED',
        $prevDelivery,
        M360_JC_VEHICLE_RELEASED,
        'P7 vehicle released to customer',
        $userId
    );
    m360_jc_close_write_event($conn, $jobcardId, 'VEHICLE_RELEASED_TO_CUSTOMER', null, $userId);

    return ['ok' => true, 'message' => 'خودرو با موفقیت تحویل مشتری شد.'];
}

/**
 * @param array<string, mixed> $jobcardRow
 * @return array{ok:bool,message:string}
 */
function m360_jobcard_close_validate($conn, int $jobcardId, array $jobcardRow): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }

    if (trim((string)($jobcardRow['vehicle_released_at'] ?? '')) === '') {
        return ['ok' => false, 'message' => 'ابتدا خودرو باید تحویل مشتری شود.'];
    }

    $jobcardStatus = strtoupper(trim((string)($jobcardRow['jobcard_status'] ?? '')));
    if ($jobcardStatus === M360_JC_CLOSED) {
        return ['ok' => false, 'message' => 'کارت کار قبلاً بسته شده است.'];
    }

    return ['ok' => true, 'message' => ''];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_jobcard_close($conn, int $jobcardId, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $userId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }
    $jobcardRow = $rows[0];

    $validation = m360_jobcard_close_validate($conn, $jobcardId, $jobcardRow);
    if (!$validation['ok']) {
        return $validation;
    }

    $prevStatus = strtoupper(trim((string)($jobcardRow['jobcard_status'] ?? '')));

    $sets = [
        "jobcard_status = N'CLOSED'",
        'jobcard_closed_at = SYSUTCDATETIME()',
        'closed_by_user_id = ?',
    ];
    $params = [$userId];
    if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $jobcardId;

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?',
        $params
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'بستن کارت کار ناموفق بود.'];
    }

    m360_jc_close_write_history(
        $conn,
        $jobcardId,
        'JOBCARD_CLOSED',
        $prevStatus !== '' ? $prevStatus : null,
        M360_JC_CLOSED,
        'P7 jobcard closed',
        $userId
    );
    m360_jc_close_write_event($conn, $jobcardId, 'JOBCARD_CLOSED', null, $userId);

    return ['ok' => true, 'message' => 'کارت کار بسته شد.'];
}
