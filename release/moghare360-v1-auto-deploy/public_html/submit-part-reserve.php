<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Submit Part Reservation
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    inventory_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('inventory_part_reserve', $_POST['erp_csrf_token'] ?? null);

$itemId = inventory_post_int('inventory_item_id');
$requestedQty = inventory_post_float('requested_qty');
$operationCaseId = inventory_post_int('operation_case_id');
$serviceStepId = inventory_post_int('service_step_id');
$ruleDecisionId = inventory_post_int('rule_decision_id');
$reason = inventory_post_string('reservation_reason');

if ($itemId === null || $requestedQty === null || $requestedQty <= 0) {
    inventory_error('خطای اعتبارسنجی', 'قلم انبار و تعداد درخواستی الزامی است.');
}

$connection = false;

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.reserve.create');

    if (!inventory_table_exists($connection, 'erp_part_reservations')) {
        throw new RuntimeException('جدول erp_part_reservations یافت نشد.');
    }

    $item = inventory_get_item($connection, $itemId);
    if ($item === null) {
        throw new RuntimeException('قلم انبار یافت نشد.');
    }

    $available = inventory_calculate_available_to_reserve($connection, $itemId);
    $reservedQty = 0.0;
    $status = 'PENDING';

    if ($available >= $requestedQty) {
        $reservedQty = $requestedQty;
        $status = 'RESERVED';
    } elseif ($available > 0) {
        $reservedQty = $available;
        $status = 'PARTIALLY_RESERVED';
    }

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت رزرو انجام نشد.');
    }

    $ok = inventory_execute(
        $connection,
        'INSERT INTO dbo.erp_part_reservations (inventory_item_id, operation_case_id, service_step_id, rule_decision_id, requested_qty, reserved_qty, reservation_status, reservation_reason, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
        [$itemId, $operationCaseId, $serviceStepId, $ruleDecisionId, $requestedQty, $reservedQty, $status, $reason ?: null, inventory_safe_current_user()]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت رزرو انجام نشد.');
    }

    $reservationId = inventory_scope_identity($connection);

    if ($reservedQty > 0 && inventory_table_exists($connection, 'erp_stock_balances')) {
        $balanceId = inventory_ensure_balance_row($connection, $itemId, null);
        if ($balanceId !== null) {
            inventory_execute(
                $connection,
                'UPDATE dbo.erp_stock_balances SET reserved_qty = reserved_qty + ?, updated_at = SYSUTCDATETIME(), updated_by = ? WHERE stock_balance_id = ?',
                [$reservedQty, inventory_safe_current_user(), $balanceId]
            );
        }
    }

    inventory_create_stock_movement($connection, [
        'inventory_item_id' => $itemId,
        'reservation_id' => $reservationId,
        'operation_case_id' => $operationCaseId,
        'movement_type' => 'RESERVATION',
        'movement_qty' => $reservedQty > 0 ? $reservedQty : $requestedQty,
        'movement_note' => 'رزرو قطعه — وضعیت ' . $status,
    ]);

    inventory_insert_history($connection, 'RESERVATION', $reservationId, 'CREATE', 'ثبت رزرو قطعه', null, $status);

    if (!@odbc_commit($connection)) {
        throw new RuntimeException('ثبت رزرو انجام نشد.');
    }
    @odbc_autocommit($connection, true);
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    inventory_error('خطا', 'ثبت رزرو قطعه انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_safe_redirect('erp-part-reserve.php?ok=1');
