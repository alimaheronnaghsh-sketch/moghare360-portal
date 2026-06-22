<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Submit Purchase Request
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    inventory_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('inventory_purchase_create', $_POST['erp_csrf_token'] ?? null);

$partName = inventory_post_string('requested_part_name');
$partCode = inventory_post_string('requested_part_code');
$requestedQty = inventory_post_float('requested_qty');
$inventoryItemId = inventory_post_int('inventory_item_id');
$supplierId = inventory_post_int('supplier_id');
$operationCaseId = inventory_post_int('operation_case_id');
$serviceStepId = inventory_post_int('service_step_id');
$ruleDecisionId = inventory_post_int('rule_decision_id');
$urgency = inventory_post_string('urgency_level') ?: 'NORMAL';
$source = inventory_post_string('purchase_source') ?: 'LOCAL';
$estimatedCost = inventory_post_float('estimated_cost');
$internalNote = inventory_post_string('internal_note');

$allowedUrgency = ['LOW', 'NORMAL', 'HIGH', 'URGENT'];
$allowedSource = ['LOCAL', 'IMPORT', 'CUSTOMER_PROVIDED', 'UNKNOWN'];

if ($partName === '' || $requestedQty === null || $requestedQty <= 0) {
    inventory_error('خطای اعتبارسنجی', 'نام قطعه و تعداد الزامی است.');
}
if (!in_array($urgency, $allowedUrgency, true) || !in_array($source, $allowedSource, true)) {
    inventory_error('خطای اعتبارسنجی', 'مقادیر فوریت یا منبع نامعتبر است.');
}

$connection = false;

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.purchase.create');

    $purchaseTable = inventory_purchase_table($connection);
    if ($purchaseTable === null) {
        throw new RuntimeException('جدول درخواست خرید یافت نشد. ابتدا SQL فاز ۴ را اجرا کنید.');
    }

    $requestCode = inventory_generate_purchase_request_code();

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت درخواست خرید انجام نشد.');
    }

    $ok = inventory_execute(
        $connection,
        'INSERT INTO dbo.' . $purchaseTable . ' (request_code, inventory_item_id, operation_case_id, service_step_id, rule_decision_id, supplier_id, requested_part_name, requested_part_code, requested_qty, urgency_level, purchase_source, request_status, estimated_cost, internal_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$requestCode, $inventoryItemId, $operationCaseId, $serviceStepId, $ruleDecisionId, $supplierId, $partName, $partCode ?: null, $requestedQty, $urgency, $source, 'REQUESTED', $estimatedCost, $internalNote ?: null, inventory_safe_current_user()]
    );
    if ($ok === false) {
        throw new RuntimeException('ثبت درخواست خرید انجام نشد.');
    }

    $purchaseRequestId = inventory_scope_identity($connection);

    inventory_create_stock_movement($connection, [
        'inventory_item_id' => $inventoryItemId,
        'purchase_request_id' => $purchaseRequestId,
        'operation_case_id' => $operationCaseId,
        'movement_type' => 'PURCHASE_REQUEST',
        'movement_qty' => $requestedQty,
        'movement_note' => 'درخواست خرید ' . $requestCode,
    ]);

    inventory_insert_history($connection, 'PURCHASE_REQUEST', $purchaseRequestId, 'CREATE', 'ثبت درخواست خرید', null, $requestCode);

    if (!@odbc_commit($connection)) {
        throw new RuntimeException('ثبت درخواست خرید انجام نشد.');
    }
    @odbc_autocommit($connection, true);
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    inventory_error('خطا', 'ثبت درخواست خرید انجام نشد.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_safe_redirect('erp-supplier-board.php?ok=purchase');
