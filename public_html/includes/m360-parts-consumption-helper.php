<?php
declare(strict_types=1);

/**
 * MOGHARE360 P5 — Approved estimate parts consumption (no full inventory module).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-estimate-helper.php';

const M360_PART_USAGE_TABLE = 'erp_jobcard_part_usage';

/**
 * @return list<array<string, mixed>>
 */
function m360_parts_approved_items($conn, int $jobcardId): array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return [];
    }
    $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
    if ($est === null) {
        return [];
    }
    $estStatus = strtoupper((string)($est['estimate_status'] ?? ''));
    if (!in_array($estStatus, [M360_EST_STATUS_APPROVED, M360_EST_STATUS_APPROVED_WORK, M360_EST_STATUS_PARTS_CLEARED, M360_EST_STATUS_FIN_CLEARED, M360_EST_STATUS_PARTS_PENDING, M360_EST_STATUS_FIN_PENDING], true)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        "SELECT * FROM dbo.erp_estimate_items WHERE estimate_id = ? AND item_type = N'PART' AND item_status <> N'REMOVED' ORDER BY estimate_item_id",
        [(int)$est['estimate_id']]
    );
}

function m360_parts_consumed_qty($conn, int $jobcardId, int $partId, int $estimateItemId = 0): float
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_PART_USAGE_TABLE)) {
        return 0.0;
    }
    $sql = "SELECT ISNULL(SUM(quantity), 0) AS s FROM dbo." . M360_PART_USAGE_TABLE . "
            WHERE jobcard_id = ? AND part_id = ? AND is_active = 1 AND usage_status = N'USED'";
    $params = [$jobcardId, $partId];
    if ($estimateItemId > 0 && customer_core_column_exists($conn, M360_PART_USAGE_TABLE, 'estimate_item_id')) {
        $sql .= ' AND estimate_item_id = ?';
        $params[] = $estimateItemId;
    }
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    return (float)($rows[0]['s'] ?? 0);
}

function m360_parts_stock_available($conn, int $partId): ?float
{
    if (!is_resource($conn) || $partId < 1) {
        return null;
    }
    if (customer_core_table_exists($conn, 'erp_stock_balances')) {
        $rows = customer_core_fetch_rows(
            $conn,
            'SELECT ISNULL(SUM(available_qty), 0) AS s FROM dbo.erp_stock_balances WHERE part_id = ?',
            [$partId]
        );
        return (float)($rows[0]['s'] ?? 0);
    }
    return null;
}

function m360_parts_default_location_id($conn): int
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_stock_locations')) {
        return 1;
    }
    $id = (int)(customer_core_scalar(
        $conn,
        "SELECT TOP 1 stock_location_id FROM dbo.erp_stock_locations WHERE location_code = N'MAIN' ORDER BY stock_location_id"
    ) ?? 0);
    if ($id < 1) {
        $id = (int)(customer_core_scalar($conn, 'SELECT TOP 1 stock_location_id FROM dbo.erp_stock_locations ORDER BY stock_location_id') ?? 1);
    }
    return max(1, $id);
}

function m360_parts_resolve_part_id($conn, array $estimateItem): int
{
    $partId = (int)($estimateItem['part_id'] ?? 0);
    if ($partId > 0) {
        return $partId;
    }
    if (!is_resource($conn) || !customer_core_table_exists($conn, 'erp_parts')) {
        return 0;
    }
    $title = trim((string)($estimateItem['item_title'] ?? ''));
    if ($title === '') {
        return 0;
    }
    return (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 part_id FROM dbo.erp_parts WHERE part_name = ? OR part_code = ? ORDER BY part_id',
        [$title, $title]
    ) ?? 0);
}

/**
 * @return array{ok:bool,message:string,consumed:bool}
 */
function m360_parts_consume_approved($conn, int $jobcardId, int $estimateItemId, float $quantity, int $userId, ?int $serviceOperationId = null): array
{
    if (!is_resource($conn) || $jobcardId < 1 || $estimateItemId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات مصرف نامعتبر است.', 'consumed' => false];
    }

    $items = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 * FROM dbo.erp_estimate_items WHERE estimate_item_id = ? AND jobcard_id = ? AND item_type = N'PART'",
        [$estimateItemId, $jobcardId]
    );
    if ($items === []) {
        return ['ok' => false, 'message' => 'قطعه در برآورد تأیید‌شده یافت نشد.', 'consumed' => false];
    }
    $item = $items[0];
    $approvedQty = (float)($item['quantity'] ?? 1);
    if ($quantity <= 0) {
        $quantity = $approvedQty;
    }
    if ($quantity > $approvedQty) {
        return ['ok' => false, 'message' => 'مقدار مصرف بیش از مقدار تأیید‌شده در برآورد است.', 'consumed' => false];
    }

    $partId = m360_parts_resolve_part_id($conn, $item);
    if ($partId < 1) {
        return ['ok' => false, 'message' => 'شناسه قطعه قابل تشخیص نیست.', 'consumed' => false];
    }

    $already = m360_parts_consumed_qty($conn, $jobcardId, $partId, $estimateItemId);
    if ($already >= $approvedQty) {
        return ['ok' => true, 'message' => 'این قطعه قبلاً مصرف شده است.', 'consumed' => false];
    }
    if ($already + $quantity > $approvedQty) {
        return ['ok' => false, 'message' => 'مجموع مصرف از سقف برآورد بیشتر می‌شود.', 'consumed' => false];
    }

    $stock = m360_parts_stock_available($conn, $partId);
    if ($stock !== null && $stock < $quantity) {
        return ['ok' => false, 'message' => 'INSUFFICIENT_STOCK', 'consumed' => false];
    }

    if (!customer_core_table_exists($conn, M360_PART_USAGE_TABLE)) {
        return ['ok' => false, 'message' => 'جدول مصرف قطعه یافت نشد.', 'consumed' => false];
    }

    $locationId = m360_parts_default_location_id($conn);
    $cols = ['jobcard_id', 'service_operation_id', 'part_id', 'stock_location_id', 'quantity', 'usage_status', 'created_by_user_id', 'is_active'];
    $vals = [$jobcardId, $serviceOperationId, $partId, $locationId, $quantity, 'USED', $userId, 1];
    if (customer_core_column_exists($conn, M360_PART_USAGE_TABLE, 'estimate_item_id')) {
        $cols[] = 'estimate_item_id';
        $vals[] = $estimateItemId;
    }

    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_PART_USAGE_TABLE . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
        $vals
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت مصرف قطعه ناموفق بود.', 'consumed' => false];
    }

    if ($stock !== null && customer_core_table_exists($conn, 'erp_stock_balances')) {
        customer_core_execute(
            $conn,
            'UPDATE dbo.erp_stock_balances SET available_qty = available_qty - ?, updated_at = SYSUTCDATETIME() WHERE part_id = ? AND available_qty >= ?',
            [$quantity, $partId, $quantity]
        );
    }

    return ['ok' => true, 'message' => 'مصرف قطعه ثبت شد.', 'consumed' => true];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_parts_list_consumed($conn, int $jobcardId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_PART_USAGE_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT part_usage_id, jobcard_id, part_id, quantity, usage_status, created_at, service_operation_id
         FROM dbo.' . M360_PART_USAGE_TABLE . ' WHERE jobcard_id = ? AND is_active = 1 ORDER BY part_usage_id DESC',
        [$jobcardId]
    );
}

function m360_parts_consumption_complete($conn, int $jobcardId): bool
{
    $approved = m360_parts_approved_items($conn, $jobcardId);
    if ($approved === []) {
        return true;
    }
    foreach ($approved as $item) {
        $partId = m360_parts_resolve_part_id($conn, $item);
        $need = (float)($item['quantity'] ?? 1);
        $got = m360_parts_consumed_qty($conn, $jobcardId, $partId, (int)($item['estimate_item_id'] ?? 0));
        if ($got < $need) {
            return false;
        }
    }
    return true;
}
