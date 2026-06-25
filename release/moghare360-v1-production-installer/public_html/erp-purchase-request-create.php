<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Purchase Request Create Form
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$items = [];
$suppliers = [];
$ruleDecisionId = inventory_get_int('rule_decision_id');
$operationCaseId = inventory_get_int('operation_case_id');
$prefill = [
    'inventory_item_id' => inventory_get_int('inventory_item_id'),
    'requested_part_name' => '',
    'requested_part_code' => '',
    'requested_qty' => '1',
    'operation_case_id' => $operationCaseId,
    'service_step_id' => inventory_get_int('service_step_id'),
    'rule_decision_id' => $ruleDecisionId,
];

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.purchase.create');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $items = inventory_fetch_rows($connection, 'SELECT inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items WHERE is_active = 1 ORDER BY item_name');
    }
    if (inventory_table_exists($connection, 'erp_suppliers')) {
        $suppliers = inventory_fetch_rows($connection, 'SELECT supplier_id, supplier_code, supplier_name FROM dbo.erp_suppliers WHERE is_active = 1 ORDER BY supplier_name');
    }

    if ($ruleDecisionId !== null && inventory_table_exists($connection, 'erp_inventory_rule_requests')) {
        $ruleRows = inventory_fetch_rows(
            $connection,
            'SELECT TOP 1 operation_case_id, service_step_id, part_code, part_name, requested_qty
             FROM dbo.erp_inventory_rule_requests WHERE rule_decision_id = ? ORDER BY inventory_rule_request_id DESC',
            [$ruleDecisionId]
        );
        if ($ruleRows !== []) {
            $r = $ruleRows[0];
            $prefill['operation_case_id'] = $prefill['operation_case_id'] ?? (inventory_get_int('operation_case_id') ?? (ctype_digit((string)($r['operation_case_id'] ?? '')) ? (int)$r['operation_case_id'] : null));
            $prefill['service_step_id'] = $prefill['service_step_id'] ?? (ctype_digit((string)($r['service_step_id'] ?? '')) ? (int)$r['service_step_id'] : null);
            $prefill['requested_part_name'] = (string)($r['part_name'] ?? '');
            $prefill['requested_part_code'] = (string)($r['part_code'] ?? '');
            $prefill['requested_qty'] = (string)($r['requested_qty'] ?? '1');
        }
    }

    if ($prefill['inventory_item_id'] !== null) {
        $item = inventory_get_item($connection, $prefill['inventory_item_id']);
        if ($item !== null) {
            if ($prefill['requested_part_name'] === '') {
                $prefill['requested_part_name'] = (string)($item['item_name'] ?? '');
            }
            if ($prefill['requested_part_code'] === '') {
                $prefill['requested_part_code'] = (string)($item['item_code'] ?? '');
            }
        }
    }
} catch (Throwable) {
    $errorMessage = 'صفحه درخواست خرید قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_render_head('درخواست خرید');

echo '<div class="p4ip-hero"><h1>درخواست خرید</h1><p>ثبت کنترل‌شده نیاز به تامین قطعه</p></div>';

if ($errorMessage !== '') {
    inventory_error('درخواست خرید', $errorMessage);
}

if ($ruleDecisionId !== null) {
    echo '<div class="p1cc-card"><p class="p1cc-hint">پیش‌فرض از تصمیم قانون #' . inventory_h((string)$ruleDecisionId) . '</p></div>';
}

echo '<form class="p1cc-card" method="post" action="submit-purchase-request.php">';
echo erp_csrf_input('inventory_purchase_create');
echo '<div class="p1cc-form-grid">';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="inventory_item_id">قلم انبار</label><select class="p1cc-select" id="inventory_item_id" name="inventory_item_id">';
echo '<option value="">—</option>';
foreach ($items as $item) {
    $id = (int)($item['inventory_item_id'] ?? 0);
    $sel = $prefill['inventory_item_id'] === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . inventory_h(($item['item_code'] ?? '') . ' — ' . ($item['item_name'] ?? '')) . '</option>';
}
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="supplier_id">تامین‌کننده</label><select class="p1cc-select" id="supplier_id" name="supplier_id">';
echo '<option value="">—</option>';
foreach ($suppliers as $sup) {
    $id = (int)($sup['supplier_id'] ?? 0);
    echo '<option value="' . $id . '">' . inventory_h(($sup['supplier_code'] ?? '') . ' — ' . ($sup['supplier_name'] ?? '')) . '</option>';
}
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="requested_part_name">نام قطعه *</label><input class="p1cc-input" id="requested_part_name" name="requested_part_name" required maxlength="300" value="' . inventory_h($prefill['requested_part_name']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="requested_part_code">کد قطعه</label><input class="p1cc-input m360-ltr" id="requested_part_code" name="requested_part_code" maxlength="100" value="' . inventory_h($prefill['requested_part_code']) . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="requested_qty">تعداد *</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0.01" id="requested_qty" name="requested_qty" required value="' . inventory_h($prefill['requested_qty']) . '"></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="urgency_level">فوریت</label><select class="p1cc-select" id="urgency_level" name="urgency_level">';
foreach (['LOW' => 'کم', 'NORMAL' => 'عادی', 'HIGH' => 'بالا', 'URGENT' => 'فوری'] as $v => $l) {
    echo '<option value="' . $v . '"' . ($v === 'NORMAL' ? ' selected' : '') . '>' . $l . '</option>';
}
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="purchase_source">منبع تامین</label><select class="p1cc-select" id="purchase_source" name="purchase_source">';
foreach (['LOCAL' => 'محلی', 'IMPORT' => 'وارداتی', 'CUSTOMER_PROVIDED' => 'تامین مشتری', 'UNKNOWN' => 'نامشخص'] as $v => $l) {
    echo '<option value="' . $v . '"' . ($v === 'LOCAL' ? ' selected' : '') . '>' . $l . '</option>';
}
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="estimated_cost">هزینه تخمینی</label><input class="p1cc-input m360-ltr" type="number" step="0.01" id="estimated_cost" name="estimated_cost"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="operation_case_id">پرونده عملیات</label><input class="p1cc-input m360-ltr" id="operation_case_id" name="operation_case_id" value="' . inventory_h($prefill['operation_case_id'] !== null ? (string)$prefill['operation_case_id'] : '') . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="service_step_id">مرحله سرویس</label><input class="p1cc-input m360-ltr" id="service_step_id" name="service_step_id" value="' . inventory_h($prefill['service_step_id'] !== null ? (string)$prefill['service_step_id'] : '') . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="rule_decision_id">تصمیم قانون</label><input class="p1cc-input m360-ltr" id="rule_decision_id" name="rule_decision_id" value="' . inventory_h($prefill['rule_decision_id'] !== null ? (string)$prefill['rule_decision_id'] : '') . '"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="internal_note">یادداشت داخلی</label><textarea class="p1cc-textarea" id="internal_note" name="internal_note" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت درخواست خرید</button></form>';

inventory_render_foot();
