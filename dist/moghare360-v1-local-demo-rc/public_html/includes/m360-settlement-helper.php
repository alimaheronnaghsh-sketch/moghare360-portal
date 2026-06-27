<?php
declare(strict_types=1);

/**
 * MOGHARE360 P7 — Settlement control (read/recalculate; no payment gateway or erp_payments INSERT).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_SETTLE_TABLE = 'erp_settlement_controls';

const M360_SETTLE_PAYMENT_PENDING = 'PAYMENT_PENDING';
const M360_SETTLE_PARTIAL = 'PARTIAL_SETTLED';
const M360_SETTLE_SETTLED = 'SETTLED';
const M360_SETTLE_MANAGER_RELEASE = 'MANAGER_RELEASE_APPROVED';
const M360_SETTLE_BLOCKED = 'BLOCKED';

const M360_SETTLE_DEL_EVENTS = 'erp_delivery_events';

/**
 * @return array<string, mixed>|null
 */
function m360_settlement_fetch_active($conn, $jobcardId): ?array
{
    $jobcardId = (int)$jobcardId;
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, M360_SETTLE_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_SETTLE_TABLE . ' WHERE jobcard_id = ? ORDER BY settlement_id DESC',
        [$jobcardId]
    );
    return $rows[0] ?? null;
}

function m360_settlement_total_paid($conn, $jobcardId): float
{
    $jobcardId = (int)$jobcardId;
    if (!is_resource($conn) || $jobcardId < 1 || !customer_core_table_exists($conn, 'erp_payments')) {
        return 0.0;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT ISNULL(SUM(payment_amount), 0) AS s FROM dbo.erp_payments WHERE jobcard_id = ? AND is_active = 1 AND payment_status IN (N'RECEIVED', N'CONFIRMED', N'PARTIAL')",
        [$jobcardId]
    );
    return (float)($rows[0]['s'] ?? 0);
}

function m360_settlement_resolve_status(float $totalDue, float $totalPaid, string $currentStatus, bool $managerApproved): string
{
    $currentStatus = strtoupper(trim($currentStatus));
    if ($currentStatus === M360_SETTLE_BLOCKED) {
        return M360_SETTLE_BLOCKED;
    }
    if ($totalDue > 0 && $totalPaid >= $totalDue) {
        return M360_SETTLE_SETTLED;
    }
    if ($managerApproved || $currentStatus === M360_SETTLE_MANAGER_RELEASE) {
        return M360_SETTLE_MANAGER_RELEASE;
    }
    if ($totalPaid > 0) {
        return M360_SETTLE_PARTIAL;
    }
    return M360_SETTLE_PAYMENT_PENDING;
}

function m360_settlement_update_jobcard($conn, int $jobcardId, string $status, float $paid, float $remaining): void
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return;
    }
    $sets = [
        'settlement_status = ?',
        'settlement_amount_paid = ?',
        'settlement_remaining_amount = ?',
    ];
    $params = [$status, $paid, max(0.0, $remaining)];
    if (customer_core_column_exists($conn, 'erp_jobcards', 'updated_at')) {
        $sets[] = 'updated_at = SYSUTCDATETIME()';
    }
    $params[] = $jobcardId;
    customer_core_execute(
        $conn,
        'UPDATE dbo.erp_jobcards SET ' . implode(', ', $sets) . ' WHERE jobcard_id = ?',
        $params
    );
}

function m360_settlement_record_event($conn, int $jobcardId, int $settlementId, string $eventName, ?string $note, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_SETTLE_DEL_EVENTS)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SETTLE_DEL_EVENTS . ' (jobcard_id, settlement_id, event_name, event_note, event_ip, event_user_agent, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $jobcardId,
            $settlementId > 0 ? $settlementId : null,
            $eventName,
            $note,
            customer_core_client_ip(),
            customer_core_user_agent(),
            $userId > 0 ? $userId : null,
        ]
    );
}

/**
 * @return array{ok:bool,message:string,settlement_id:int}
 */
function m360_settlement_recalculate($conn, int $jobcardId, int $finalInvoiceId, float $totalDue, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $finalInvoiceId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.', 'settlement_id' => 0];
    }
    if (!customer_core_table_exists($conn, M360_SETTLE_TABLE)) {
        return ['ok' => false, 'message' => 'جدول کنترل تسویه یافت نشد.', 'settlement_id' => 0];
    }
    if ($totalDue < 0) {
        return ['ok' => false, 'message' => 'مبلغ قابل پرداخت نامعتبر است.', 'settlement_id' => 0];
    }

    $totalPaid = m360_settlement_total_paid($conn, $jobcardId);
    $remaining = max(0.0, $totalDue - $totalPaid);
    $existing = m360_settlement_fetch_active($conn, $jobcardId);
    $currentStatus = (string)($existing['settlement_status'] ?? M360_SETTLE_PAYMENT_PENDING);
    $managerApproved = (bool)($existing['manager_release_approved'] ?? false);
    $newStatus = m360_settlement_resolve_status($totalDue, $totalPaid, $currentStatus, $managerApproved);
    $settledSql = $newStatus === M360_SETTLE_SETTLED ? 'SYSUTCDATETIME()' : 'NULL';

    if ($existing !== null && (int)($existing['final_invoice_id'] ?? 0) === $finalInvoiceId) {
        $settlementId = (int)$existing['settlement_id'];
        $ok = customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_SETTLE_TABLE . ' SET final_invoice_id = ?, settlement_status = ?, total_due_amount = ?, total_paid_amount = ?, remaining_amount = ?, settled_at = ' . $settledSql . ' WHERE settlement_id = ? AND jobcard_id = ?',
            [$finalInvoiceId, $newStatus, $totalDue, $totalPaid, $remaining, $settlementId, $jobcardId]
        );
        if ($ok === false) {
            return ['ok' => false, 'message' => 'به‌روزرسانی تسویه ناموفق بود.', 'settlement_id' => 0];
        }
    } else {
        $ok = customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_SETTLE_TABLE . ' (jobcard_id, final_invoice_id, settlement_status, total_due_amount, total_paid_amount, remaining_amount, manager_release_approved, settled_at, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, 0, ' . $settledSql . ', ?)',
            [$jobcardId, $finalInvoiceId, $newStatus, $totalDue, $totalPaid, $remaining, $userId > 0 ? $userId : null]
        );
        if ($ok === false) {
            return ['ok' => false, 'message' => 'ایجاد رکورد تسویه ناموفق بود.', 'settlement_id' => 0];
        }
        $settlementId = (int)(customer_core_scalar(
            $conn,
            'SELECT TOP 1 settlement_id FROM dbo.' . M360_SETTLE_TABLE . ' WHERE jobcard_id = ? ORDER BY settlement_id DESC',
            [$jobcardId]
        ) ?? 0);
        if ($settlementId < 1) {
            return ['ok' => false, 'message' => 'شناسه تسویه یافت نشد.', 'settlement_id' => 0];
        }
    }

    m360_settlement_update_jobcard($conn, $jobcardId, $newStatus, $totalPaid, $remaining);
    m360_settlement_record_event($conn, $jobcardId, $settlementId, 'SETTLEMENT_RECALCULATED', null, $userId);

    return ['ok' => true, 'message' => 'تسویه مجدداً محاسبه شد.', 'settlement_id' => $settlementId];
}

/**
 * @param array<string, mixed> $settlementRow
 * @return array{ok:bool,message:string}
 */
function m360_settlement_can_release($conn, $jobcardId, array $settlementRow): array
{
    $jobcardId = (int)$jobcardId;
    if (!is_resource($conn) || $jobcardId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }
    if ((int)($settlementRow['jobcard_id'] ?? 0) !== $jobcardId) {
        return ['ok' => false, 'message' => 'رکورد تسویه با JobCard مطابقت ندارد.'];
    }

    $status = strtoupper(trim((string)($settlementRow['settlement_status'] ?? '')));
    if ($status === M360_SETTLE_BLOCKED) {
        return ['ok' => false, 'message' => 'تحویل به دلیل مسدودسازی تسویه مجاز نیست.'];
    }
    if (in_array($status, [M360_SETTLE_SETTLED, M360_SETTLE_MANAGER_RELEASE], true)) {
        return ['ok' => true, 'message' => ''];
    }
    if ((bool)($settlementRow['manager_release_approved'] ?? false)) {
        return ['ok' => true, 'message' => ''];
    }

    return ['ok' => false, 'message' => 'تسویه کامل یا مجوز مدیریتی برای تحویل لازم است.'];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_settlement_manager_release($conn, $jobcardId, int $settlementId, string $reason, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $settlementId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل مجوز مدیریتی الزامی است.'];
    }
    if (!customer_core_table_exists($conn, M360_SETTLE_TABLE)) {
        return ['ok' => false, 'message' => 'جدول کنترل تسویه یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_SETTLE_TABLE . ' WHERE settlement_id = ? AND jobcard_id = ?',
        [$settlementId, $jobcardId]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'رکورد تسویه یافت نشد.'];
    }
    $row = $rows[0];
    if (strtoupper((string)($row['settlement_status'] ?? '')) === M360_SETTLE_BLOCKED) {
        return ['ok' => false, 'message' => 'تسویه مسدود است — مجوز مدیریتی مجاز نیست.'];
    }
    if (strtoupper((string)($row['settlement_status'] ?? '')) === M360_SETTLE_SETTLED) {
        return ['ok' => true, 'message' => 'تسویه قبلاً کامل شده است.'];
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SETTLE_TABLE . ' SET settlement_status = ?, manager_release_approved = 1, manager_release_reason = ? WHERE settlement_id = ? AND jobcard_id = ?',
        [M360_SETTLE_MANAGER_RELEASE, $reason, $settlementId, $jobcardId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت مجوز مدیریتی ناموفق بود.'];
    }

    $paid = (float)($row['total_paid_amount'] ?? m360_settlement_total_paid($conn, $jobcardId));
    $remaining = (float)($row['remaining_amount'] ?? 0);
    m360_settlement_update_jobcard($conn, $jobcardId, M360_SETTLE_MANAGER_RELEASE, $paid, $remaining);
    m360_settlement_record_event($conn, $jobcardId, $settlementId, 'SETTLEMENT_MANAGER_RELEASE_APPROVED', $reason, $userId);

    return ['ok' => true, 'message' => 'مجوز خروج با مانده بدهی ثبت شد.'];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_settlement_mark_settled($conn, $jobcardId, int $settlementId, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $settlementId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }
    if (!customer_core_table_exists($conn, M360_SETTLE_TABLE)) {
        return ['ok' => false, 'message' => 'جدول کنترل تسویه یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_SETTLE_TABLE . ' WHERE settlement_id = ? AND jobcard_id = ?',
        [$settlementId, $jobcardId]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'رکورد تسویه یافت نشد.'];
    }
    $row = $rows[0];
    if (strtoupper((string)($row['settlement_status'] ?? '')) === M360_SETTLE_SETTLED) {
        return ['ok' => true, 'message' => 'تسویه قبلاً کامل ثبت شده است.'];
    }
    if (strtoupper((string)($row['settlement_status'] ?? '')) === M360_SETTLE_BLOCKED) {
        return ['ok' => false, 'message' => 'تسویه مسدود است.'];
    }

    $totalDue = (float)($row['total_due_amount'] ?? 0);
    $totalPaid = m360_settlement_total_paid($conn, $jobcardId);
    $remaining = max(0.0, $totalDue - $totalPaid);

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SETTLE_TABLE . ' SET settlement_status = ?, total_paid_amount = ?, remaining_amount = ?, settled_at = SYSUTCDATETIME() WHERE settlement_id = ? AND jobcard_id = ?',
        [M360_SETTLE_SETTLED, $totalPaid, $remaining, $settlementId, $jobcardId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت تسویه کامل ناموفق بود.'];
    }

    m360_settlement_update_jobcard($conn, $jobcardId, M360_SETTLE_SETTLED, $totalPaid, $remaining);
    m360_settlement_record_event($conn, $jobcardId, $settlementId, 'SETTLEMENT_SETTLED', null, $userId);

    return ['ok' => true, 'message' => 'تسویه کامل ثبت شد.'];
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_settlement_block_delivery($conn, $jobcardId, int $settlementId, string $note, int $userId): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $settlementId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات نامعتبر است.'];
    }
    if (!customer_core_table_exists($conn, M360_SETTLE_TABLE)) {
        return ['ok' => false, 'message' => 'جدول کنترل تسویه یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 settlement_id FROM dbo.' . M360_SETTLE_TABLE . ' WHERE settlement_id = ? AND jobcard_id = ?',
        [$settlementId, $jobcardId]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'رکورد تسویه یافت نشد.'];
    }

    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SETTLE_TABLE . ' SET settlement_status = ?, manager_release_approved = 0 WHERE settlement_id = ? AND jobcard_id = ?',
        [M360_SETTLE_BLOCKED, $settlementId, $jobcardId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'مسدودسازی تحویل ناموفق بود.'];
    }

    $active = m360_settlement_fetch_active($conn, $jobcardId);
    $paid = (float)($active['total_paid_amount'] ?? 0);
    $remaining = (float)($active['remaining_amount'] ?? 0);
    m360_settlement_update_jobcard($conn, $jobcardId, M360_SETTLE_BLOCKED, $paid, $remaining);
    m360_settlement_record_event($conn, $jobcardId, $settlementId, 'SETTLEMENT_DELIVERY_BLOCKED', trim($note) !== '' ? trim($note) : null, $userId);

    return ['ok' => true, 'message' => 'تحویل به دلیل تسویه مسدود شد.'];
}
