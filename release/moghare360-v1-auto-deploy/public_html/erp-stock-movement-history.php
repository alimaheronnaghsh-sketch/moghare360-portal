<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Stock Movement History (read-only)
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$rows = [];
$items = [];
$movementTable = null;

$filterItemId = inventory_get_int('inventory_item_id');
$filterType = inventory_get_string('movement_type');
$filterCaseId = inventory_get_int('operation_case_id');
$filterPurchaseId = inventory_get_int('purchase_request_id');

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.movement.view');

    $movementTable = inventory_movements_table($connection);

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $items = inventory_fetch_rows($connection, 'SELECT inventory_item_id, item_code, item_name FROM dbo.erp_inventory_items ORDER BY item_name');
    }

    if ($movementTable !== null) {
        $sql = 'SELECT TOP 100 stock_movement_id, inventory_item_id, stock_location_id, reservation_id, purchase_request_id, operation_case_id, movement_type, movement_qty, movement_status, movement_note, created_at, created_by FROM dbo.' . $movementTable . ' WHERE 1=1';
        $params = [];
        if ($filterItemId !== null) {
            $sql .= ' AND inventory_item_id = ?';
            $params[] = $filterItemId;
        }
        if ($filterType !== '') {
            $sql .= ' AND movement_type = ?';
            $params[] = $filterType;
        }
        if ($filterCaseId !== null) {
            $sql .= ' AND operation_case_id = ?';
            $params[] = $filterCaseId;
        }
        if ($filterPurchaseId !== null) {
            $sql .= ' AND purchase_request_id = ?';
            $params[] = $filterPurchaseId;
        }
        $sql .= ' ORDER BY stock_movement_id DESC';
        $rows = inventory_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'تاریخچه جابجایی انبار قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_render_head('تاریخچه جابجایی انبار', true);

echo '<div class="p4ip-hero"><h1>تاریخچه جابجایی انبار</h1><p>ثبت read-only حرکت‌های موجودی</p></div>';

if ($errorMessage !== '') {
    inventory_error('تاریخچه جابجایی', $errorMessage);
}

echo '<div class="p1cc-card"><form method="get" class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="inventory_item_id">قلم انبار</label><select class="p1cc-select" id="inventory_item_id" name="inventory_item_id"><option value="">همه</option>';
foreach ($items as $item) {
    $id = (int)($item['inventory_item_id'] ?? 0);
    $sel = $filterItemId === $id ? ' selected' : '';
    echo '<option value="' . $id . '"' . $sel . '>' . inventory_h(($item['item_code'] ?? '') . ' — ' . ($item['item_name'] ?? '')) . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="movement_type">نوع حرکت</label><select class="p1cc-select" id="movement_type" name="movement_type"><option value="">همه</option>';
foreach (['INITIAL_BALANCE', 'RESERVATION', 'USAGE', 'RELEASE', 'PURCHASE_REQUEST', 'PENDING_RECEIVE', 'RECEIVE', 'ADJUSTMENT'] as $mt) {
    $sel = $filterType === $mt ? ' selected' : '';
    echo '<option value="' . $mt . '"' . $sel . '>' . $mt . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="operation_case_id">پرونده</label><input class="p1cc-input m360-ltr" id="operation_case_id" name="operation_case_id" value="' . inventory_h($filterCaseId !== null ? (string)$filterCaseId : '') . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="purchase_request_id">درخواست خرید</label><input class="p1cc-input m360-ltr" id="purchase_request_id" name="purchase_request_id" value="' . inventory_h($filterPurchaseId !== null ? (string)$filterPurchaseId : '') . '"></div>';
echo '<div class="p1cc-form-group"><button class="p1cc-btn" type="submit">فیلتر</button></div>';
echo '</form></div>';

echo '<div class="p1cc-card">';
if ($movementTable === null) {
    echo '<p class="p1cc-hint">جدول حرکت انبار Phase 4 یافت نشد.</p>';
} elseif ($rows === []) {
    echo '<p class="p1cc-hint">رکوردی یافت نشد.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>شناسه</th><th>قلم</th><th>نوع</th><th>تعداد</th><th>پرونده</th><th>خرید</th><th>یادداشت</th><th>تاریخ</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        echo '<td>' . inventory_h($row['stock_movement_id'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['inventory_item_id'] ?? '—') . '</td>';
        echo '<td><span class="p1cc-badge p1cc-badge-draft">' . inventory_h($row['movement_type'] ?? '') . '</span></td>';
        echo '<td class="m360-ltr">' . inventory_h($row['movement_qty'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['operation_case_id'] !== '' ? $row['operation_case_id'] : '—') . '</td>';
        echo '<td>' . inventory_h($row['purchase_request_id'] !== '' ? $row['purchase_request_id'] : '—') . '</td>';
        echo '<td>' . inventory_h($row['movement_note'] ?? '—') . '</td>';
        echo '<td>' . inventory_h($row['created_at'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

inventory_render_foot();
