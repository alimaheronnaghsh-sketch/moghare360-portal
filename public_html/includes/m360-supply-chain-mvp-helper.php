<?php
declare(strict_types=1);

/**
 * MOGHARE360 Prompt 3 — supply-chain MVP helper (PO / logistics / tools).
 * Uses existing parts/stock/purchase foundations; adds controlled MVP operations.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-auth-context.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_SC_PO_TABLE = 'erp_purchase_orders';
const M360_SC_PO_LINES_TABLE = 'erp_purchase_order_lines';
const M360_SC_LOGISTICS_TABLE = 'erp_logistics_requests';
const M360_SC_TOOLS_TABLE = 'erp_tools_assets';
const M360_SC_TOOL_ISSUE_TABLE = 'erp_tool_issue_events';
const M360_SC_AUDIT_TABLE = 'erp_supply_chain_audit_events';

function m360_sc_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_sc_require_staff(): void
{
    erp_auth_context_start();
    $userId = (int)(erp_auth_current_user_id() ?? erp_auth_context_session_user_id() ?? 0);
    if ($userId < 1) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_sc_actor_user_id(): int
{
    return (int)(erp_auth_current_user_id() ?? erp_auth_context_session_user_id() ?? 0);
}

function m360_sc_audit($conn, string $entityType, int $entityId, string $eventName, ?string $note, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_SC_AUDIT_TABLE) || $entityId < 1) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SC_AUDIT_TABLE . ' (entity_type, entity_id, event_name, event_note, created_by_user_id)
         VALUES (?, ?, ?, ?, ?)',
        [$entityType, $entityId, $eventName, $note, $userId > 0 ? $userId : null]
    );
}

/** @return array{ok:bool,message:string,purchase_order_id:?int} */
function m360_sc_create_purchase_order($conn, int $supplierId, string $notes, int $userId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_SC_PO_TABLE)) {
        return ['ok' => false, 'message' => 'جدول سفارش خرید آماده نیست.', 'purchase_order_id' => null];
    }
    $poNumber = 'PO-' . gmdate('YmdHis') . '-' . random_int(1000, 9999);
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SC_PO_TABLE . '
            (po_number, supplier_id, po_status, ordered_at, notes, created_by_user_id)
         VALUES (?, ?, N\'DRAFT\', SYSUTCDATETIME(), ?, ?)',
        [$poNumber, $supplierId > 0 ? $supplierId : null, $notes !== '' ? $notes : null, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ایجاد سفارش خرید ناموفق بود.', 'purchase_order_id' => null];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 purchase_order_id FROM dbo.' . M360_SC_PO_TABLE . ' WHERE po_number = ? ORDER BY purchase_order_id DESC',
        [$poNumber]
    ) ?? 0);
    m360_sc_audit($conn, 'PURCHASE_ORDER', $id, 'PO_CREATED', $poNumber, $userId);
    return ['ok' => true, 'message' => 'سفارش خرید ایجاد شد.', 'purchase_order_id' => $id > 0 ? $id : null];
}

/** @return array{ok:bool,message:string} */
function m360_sc_add_po_line($conn, int $poId, string $itemName, float $qty, ?int $partId, ?float $unitCost, int $userId): array
{
    if (!customer_core_table_exists($conn, M360_SC_PO_LINES_TABLE) || $poId < 1 || $qty <= 0 || trim($itemName) === '') {
        return ['ok' => false, 'message' => 'قلم سفارش معتبر نیست.'];
    }
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SC_PO_LINES_TABLE . '
            (purchase_order_id, part_id, item_name, ordered_qty, received_qty, unit_cost)
         VALUES (?, ?, ?, ?, 0, ?)',
        [$poId, $partId > 0 ? $partId : null, mb_substr(trim($itemName), 0, 200), $qty, $unitCost]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت قلم سفارش ناموفق بود.'];
    }
    m360_sc_audit($conn, 'PURCHASE_ORDER', $poId, 'PO_LINE_ADDED', $itemName, $userId);
    return ['ok' => true, 'message' => 'قلم سفارش ثبت شد.'];
}

/** @return array{ok:bool,message:string} */
function m360_sc_receive_po_line($conn, int $poLineId, float $recvQty, int $userId): array
{
    if (!customer_core_table_exists($conn, M360_SC_PO_LINES_TABLE) || $poLineId < 1 || $recvQty <= 0) {
        return ['ok' => false, 'message' => 'دریافت قلم نامعتبر است.'];
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 po_line_id, purchase_order_id, ordered_qty, received_qty FROM dbo.' . M360_SC_PO_LINES_TABLE . ' WHERE po_line_id = ?',
        [$poLineId]
    );
    if ($rows === []) {
        return ['ok' => false, 'message' => 'قلم یافت نشد.'];
    }
    $line = $rows[0];
    $ordered = (float)$line['ordered_qty'];
    $already = (float)$line['received_qty'];
    $open = $ordered - $already;
    if ($recvQty > $open + 0.0001) {
        return ['ok' => false, 'message' => 'مقدار دریافت از مانده باز بیشتر است.'];
    }
    $newRecv = $already + $recvQty;
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SC_PO_LINES_TABLE . ' SET received_qty = ? WHERE po_line_id = ?',
        [$newRecv, $poLineId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'به‌روزرسانی دریافت ناموفق بود.'];
    }
    $poId = (int)$line['purchase_order_id'];
    $openLines = (int)(customer_core_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.' . M360_SC_PO_LINES_TABLE . ' WHERE purchase_order_id = ? AND received_qty < ordered_qty',
        [$poId]
    ) ?? 0);
    $status = $openLines > 0 ? 'PARTIAL_RECEIVED' : 'RECEIVED';
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SC_PO_TABLE . ' SET po_status = ?, updated_at = SYSUTCDATETIME() WHERE purchase_order_id = ?',
        [$status, $poId]
    );
    m360_sc_audit($conn, 'PURCHASE_ORDER', $poId, 'PO_LINE_RECEIVED', 'line=' . $poLineId . ';qty=' . $recvQty, $userId);
    return ['ok' => true, 'message' => 'دریافت ثبت شد. مانده باز: ' . max(0, $ordered - $newRecv)];
}

/** @return array{ok:bool,message:string,logistics_request_id:?int} */
function m360_sc_create_logistics_request($conn, string $origin, string $destination, ?int $jobcardId, int $userId): array
{
    if (!customer_core_table_exists($conn, M360_SC_LOGISTICS_TABLE)) {
        return ['ok' => false, 'message' => 'جدول لجستیک آماده نیست.', 'logistics_request_id' => null];
    }
    $code = 'LG-' . gmdate('YmdHis') . '-' . random_int(100, 999);
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SC_LOGISTICS_TABLE . '
            (request_code, jobcard_id, origin_text, destination_text, logistics_status, planned_at, created_by_user_id)
         VALUES (?, ?, ?, ?, N\'PLANNED\', SYSUTCDATETIME(), ?)',
        [$code, $jobcardId > 0 ? $jobcardId : null, $origin, $destination, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ایجاد درخواست لجستیک ناموفق بود.', 'logistics_request_id' => null];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 logistics_request_id FROM dbo.' . M360_SC_LOGISTICS_TABLE . ' WHERE request_code = ? ORDER BY logistics_request_id DESC',
        [$code]
    ) ?? 0);
    m360_sc_audit($conn, 'LOGISTICS', $id, 'LOGISTICS_CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'درخواست لجستیک ایجاد شد.', 'logistics_request_id' => $id > 0 ? $id : null];
}

/** @return array{ok:bool,message:string,tool_asset_id:?int} */
function m360_sc_create_tool($conn, string $code, string $name, string $type, int $userId): array
{
    if (!customer_core_table_exists($conn, M360_SC_TOOLS_TABLE) || trim($code) === '' || trim($name) === '') {
        return ['ok' => false, 'message' => 'اطلاعات ابزار معتبر نیست.', 'tool_asset_id' => null];
    }
    $type = strtoupper(trim($type));
    if (!in_array($type, ['TOOL', 'ASSET', 'EQUIPMENT'], true)) {
        $type = 'TOOL';
    }
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SC_TOOLS_TABLE . '
            (tool_code, tool_name, tool_type, created_by_user_id)
         VALUES (?, ?, ?, ?)',
        [mb_substr(trim($code), 0, 40), mb_substr(trim($name), 0, 200), $type, $userId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت ابزار/دارایی ناموفق بود.', 'tool_asset_id' => null];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 tool_asset_id FROM dbo.' . M360_SC_TOOLS_TABLE . ' WHERE tool_code = ? ORDER BY tool_asset_id DESC',
        [mb_substr(trim($code), 0, 40)]
    ) ?? 0);
    m360_sc_audit($conn, 'TOOL_ASSET', $id, 'TOOL_CREATED', $code, $userId);
    return ['ok' => true, 'message' => 'ابزار/دارایی ثبت شد.', 'tool_asset_id' => $id > 0 ? $id : null];
}

/** @return array{ok:bool,message:string} */
function m360_sc_issue_tool($conn, int $toolId, int $toUserId, int $byUserId): array
{
    if (!customer_core_table_exists($conn, M360_SC_TOOL_ISSUE_TABLE) || $toolId < 1 || $toUserId < 1) {
        return ['ok' => false, 'message' => 'صدور ابزار نامعتبر است.'];
    }
    $open = (int)(customer_core_scalar(
        $conn,
        "SELECT COUNT(*) FROM dbo." . M360_SC_TOOL_ISSUE_TABLE . " WHERE tool_asset_id = ? AND issue_status = N'ISSUED'",
        [$toolId]
    ) ?? 0);
    if ($open > 0) {
        return ['ok' => false, 'message' => 'این ابزار هم‌اکنون صادر شده و برگشت نشده است.'];
    }
    $ok = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_SC_TOOL_ISSUE_TABLE . '
            (tool_asset_id, issued_to_user_id, issued_by_user_id, issue_status)
         VALUES (?, ?, ?, N\'ISSUED\')',
        [$toolId, $toUserId, $byUserId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ثبت صدور ابزار ناموفق بود.'];
    }
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SC_TOOLS_TABLE . ' SET assigned_user_id = ?, updated_at = SYSUTCDATETIME() WHERE tool_asset_id = ?',
        [$toUserId, $toolId]
    );
    m360_sc_audit($conn, 'TOOL_ASSET', $toolId, 'TOOL_ISSUED', 'to=' . $toUserId, $byUserId);
    return ['ok' => true, 'message' => 'ابزار صادر شد.'];
}

/** @return array{ok:bool,message:string} */
function m360_sc_return_tool($conn, int $toolId, string $condition, int $userId): array
{
    if (!customer_core_table_exists($conn, M360_SC_TOOL_ISSUE_TABLE) || $toolId < 1) {
        return ['ok' => false, 'message' => 'برگشت ابزار نامعتبر است.'];
    }
    $condition = strtoupper(trim($condition));
    if (!in_array($condition, ['GOOD', 'NEEDS_SERVICE', 'DAMAGED', 'MISSING'], true)) {
        $condition = 'GOOD';
    }
    $ok = customer_core_execute(
        $conn,
        "UPDATE dbo." . M360_SC_TOOL_ISSUE_TABLE . "
         SET issue_status = N'RETURNED', returned_at = SYSUTCDATETIME(), condition_on_return = ?
         WHERE tool_asset_id = ? AND issue_status = N'ISSUED'",
        [$condition, $toolId]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'برگشت ابزار ناموفق بود.'];
    }
    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_SC_TOOLS_TABLE . ' SET assigned_user_id = NULL, condition_status = ?, updated_at = SYSUTCDATETIME() WHERE tool_asset_id = ?',
        [$condition === 'MISSING' ? 'MISSING' : $condition, $toolId]
    );
    m360_sc_audit($conn, 'TOOL_ASSET', $toolId, 'TOOL_RETURNED', $condition, $userId);
    return ['ok' => true, 'message' => 'ابزار برگشت داده شد.'];
}

/** @return list<array<string,mixed>> */
function m360_sc_list_purchase_orders($conn, int $limit = 50): array
{
    if (!customer_core_table_exists($conn, M360_SC_PO_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . max(1, min(200, $limit)) . ' * FROM dbo.' . M360_SC_PO_TABLE . ' WHERE is_active = 1 ORDER BY purchase_order_id DESC',
        []
    );
}

/** @return list<array<string,mixed>> */
function m360_sc_list_logistics($conn, int $limit = 50): array
{
    if (!customer_core_table_exists($conn, M360_SC_LOGISTICS_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . max(1, min(200, $limit)) . ' * FROM dbo.' . M360_SC_LOGISTICS_TABLE . ' WHERE is_active = 1 ORDER BY logistics_request_id DESC',
        []
    );
}

/** @return list<array<string,mixed>> */
function m360_sc_list_tools($conn, int $limit = 50): array
{
    if (!customer_core_table_exists($conn, M360_SC_TOOLS_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . max(1, min(200, $limit)) . ' * FROM dbo.' . M360_SC_TOOLS_TABLE . ' WHERE is_active = 1 ORDER BY tool_asset_id DESC',
        []
    );
}
