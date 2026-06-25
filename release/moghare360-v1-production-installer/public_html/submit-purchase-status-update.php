<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Submit Purchase Status Update
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    inventory_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('inventory_purchase_status', $_POST['erp_csrf_token'] ?? null);

$purchaseRequestId = inventory_post_int('purchase_request_id');
$newStatus = inventory_post_string('new_status');

$allowed = ['REQUESTED', 'SUPPLIER_PENDING', 'ORDERED', 'PENDING_RECEIVE', 'RECEIVED', 'CANCELLED'];

if ($purchaseRequestId === null || $newStatus === '' || !in_array($newStatus, $allowed, true)) {
    inventory_error('خطای اعتبارسنجی', 'شناسه درخواست یا وضعیت جدید نامعتبر است.');
}

$connection = false;

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.purchase.update');

    $purchaseTable = inventory_purchase_table($connection);
    if ($purchaseTable === null) {
        throw new RuntimeException('جدول درخواست خرید یافت نشد.');
    }

    $rows = inventory_fetch_rows(
        $connection,
        'SELECT TOP 1 purchase_request_id, request_code, inventory_item_id, requested_qty, request_status FROM dbo.' . $purchaseTable . ' WHERE purchase_request_id = ?',
        [$purchaseRequestId]
    );
    if ($rows === []) {
        throw new RuntimeException('درخواست خرید یافت نشد.');
    }

    $current = $rows[0];
    $oldStatus = (string)($current['request_status'] ?? '');
    $itemId = ctype_digit((string)($current['inventory_item_id'] ?? '')) ? (int)$current['inventory_item_id'] : null;
    $qty = (float)($current['requested_qty'] ?? '0');

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('به‌روزرسانی وضعیت انجام نشد.');
    }

    $ok = inventory_execute(
        $connection,
        'UPDATE dbo.' . $purchaseTable . ' SET request_status = ?, updated_at = SYSUTCDATETIME(), updated_by = ? WHERE purchase_request_id = ?',
        [$newStatus, inventory_safe_current_user(), $purchaseRequestId]
    );
    if ($ok === false) {
        throw new RuntimeException('به‌روزرسانی وضعیت انجام نشد.');
    }

    if ($newStatus === 'PENDING_RECEIVE' && $itemId !== null && $qty > 0 && inventory_table_exists($connection, 'erp_stock_balances')) {
        $balanceId = inventory_ensure_balance_row($connection, $itemId, null);
        if ($balanceId !== null) {
            inventory_execute(
                $connection,
                'UPDATE dbo.erp_stock_balances SET pending_receive_qty = pending_receive_qty + ?, updated_at = SYSUTCDATETIME(), updated_by = ? WHERE stock_balance_id = ?',
                [$qty, inventory_safe_current_user(), $balanceId]
            );
        }
        inventory_create_stock_movement($connection, [
            'inventory_item_id' => $itemId,
            'purchase_request_id' => $purchaseRequestId,
            'movement_type' => 'PENDING_RECEIVE',
            'movement_qty' => $qty,
            'movement_note' => 'در انتظار دریافت — ' . ($current['request_code'] ?? ''),
        ]);
    }

    if ($newStatus === 'RECEIVED' && $itemId !== null && $qty > 0 && inventory_table_exists($connection, 'erp_stock_balances')) {
        $balanceId = inventory_ensure_balance_row($connection, $itemId, null);
        if ($balanceId !== null) {
            inventory_execute(
                $connection,
                'UPDATE dbo.erp_stock_balances SET available_qty = available_qty + ?, pending_receive_qty = CASE WHEN pending_receive_qty >= ? THEN pending_receive_qty - ? ELSE 0 END, updated_at = SYSUTCDATETIME(), updated_by = ? WHERE stock_balance_id = ?',
                [$qty, $qty, $qty, inventory_safe_current_user(), $balanceId]
            );
        }
        inventory_create_stock_movement($connection, [
            'inventory_item_id' => $itemId,
            'purchase_request_id' => $purchaseRequestId,
            'movement_type' => 'RECEIVE',
            'movement_qty' => $qty,
            'movement_note' => 'دریافت قطعه — ' . ($current['request_code'] ?? ''),
        ]);
    }

    inventory_insert_history($connection, 'PURCHASE_REQUEST', $purchaseRequestId, 'STATUS_UPDATE', 'تغییر وضعیت درخواست خرید', $oldStatus, $newStatus);

    if (!@odbc_commit($connection)) {
        throw new RuntimeException('به‌روزرسانی وضعیت انجام نشد.');
    }
    @odbc_autocommit($connection, true);
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    inventory_error('خطا', 'به‌روزرسانی وضعیت درخواست خرید انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_safe_redirect('erp-supplier-board.php?ok=status');
