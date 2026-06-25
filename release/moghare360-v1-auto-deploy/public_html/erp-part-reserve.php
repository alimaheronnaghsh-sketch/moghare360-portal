<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Part Reservation Form
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$items = [];
$selectedItemId = inventory_get_int('inventory_item_id');
$availableToReserve = null;
$ruleDecisionId = inventory_get_int('rule_decision_id');
$operationCaseId = inventory_get_int('operation_case_id');
$flash = inventory_get_string('ok') !== '' ? inventory_flash('reserve_ok') : '';

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.reserve.create');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $items = inventory_fetch_rows(
            $connection,
            'SELECT inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items WHERE is_active = 1 ORDER BY item_name'
        );
    }

    if ($selectedItemId !== null) {
        $availableToReserve = inventory_calculate_available_to_reserve($connection, $selectedItemId);
    }
} catch (Throwable) {
    $errorMessage = 'صفحه رزرو قطعه قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_render_head('رزرو قطعه');

echo '<div class="p4ip-hero"><h1>رزرو قطعه</h1><p>رزرو کنترل‌شده از موجودی انبار</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . inventory_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    inventory_error('رزرو قطعه', $errorMessage);
}

if ($availableToReserve !== null) {
    echo '<div class="p1cc-card"><p>مقدار قابل رزرو برای قلم انتخاب‌شده: <strong class="m360-ltr">' . inventory_h((string)$availableToReserve) . '</strong></p></div>';
}

echo '<form class="p1cc-card" method="post" action="submit-part-reserve.php">';
echo erp_csrf_input('inventory_part_reserve');
echo '<div class="p1cc-form-grid">';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="inventory_item_id">قلم انبار *</label><select class="p1cc-select" id="inventory_item_id" name="inventory_item_id" required onchange="location.href=\'erp-part-reserve.php?inventory_item_id=\'+this.value">';
echo '<option value="">انتخاب کنید</option>';
foreach ($items as $item) {
    $id = (int)($item['inventory_item_id'] ?? 0);
    $sel = $selectedItemId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . inventory_h(($item['item_code'] ?? '') . ' — ' . ($item['item_name'] ?? '')) . '</option>';
}
echo '</select></div>';

echo '<div class="p1cc-form-group"><label class="p1cc-label" for="requested_qty">تعداد درخواستی *</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0.01" id="requested_qty" name="requested_qty" value="1" required></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="operation_case_id">پرونده عملیات</label><input class="p1cc-input m360-ltr" id="operation_case_id" name="operation_case_id" value="' . inventory_h($operationCaseId !== null ? (string)$operationCaseId : '') . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="service_step_id">مرحله سرویس</label><input class="p1cc-input m360-ltr" id="service_step_id" name="service_step_id" value=""></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="rule_decision_id">تصمیم قانون</label><input class="p1cc-input m360-ltr" id="rule_decision_id" name="rule_decision_id" value="' . inventory_h($ruleDecisionId !== null ? (string)$ruleDecisionId : '') . '"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="reservation_reason">دلیل رزرو</label><textarea class="p1cc-textarea" id="reservation_reason" name="reservation_reason" maxlength="1000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت رزرو</button></form>';

inventory_render_foot();
