<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Stock Board (read-only)
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$rows = [];
$filterName = inventory_get_string('item_name');

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    inventory_require_auth($connection, 'inventory.stock.view');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $sql = 'SELECT i.inventory_item_id, i.item_code, i.item_name, i.min_stock_qty,
                       ISNULL(SUM(b.available_qty),0) AS available_qty,
                       ISNULL(SUM(b.reserved_qty),0) AS reserved_qty,
                       ISNULL(SUM(b.pending_receive_qty),0) AS pending_receive_qty
                FROM dbo.erp_inventory_items i
                LEFT JOIN dbo.erp_stock_balances b ON b.inventory_item_id = i.inventory_item_id
                WHERE i.is_active = 1';
        $params = [];
        if ($filterName !== '') {
            $sql .= ' AND i.item_name LIKE ?';
            $params[] = '%' . $filterName . '%';
        }
        $sql .= ' GROUP BY i.inventory_item_id, i.item_code, i.item_name, i.min_stock_qty ORDER BY i.item_name';
        $rows = inventory_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'تابلو موجودی انبار قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_render_head('تابلو موجودی انبار', true);

echo '<div class="p4ip-hero"><h1>تابلو موجودی انبار</h1><p>بررسی موجودی، رزرو و در انتظار دریافت</p></div>';

if ($errorMessage !== '') {
    inventory_error('تابلو موجودی', $errorMessage);
}

echo '<div class="p1cc-card"><form method="get" class="p4ip-form-inline">';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="item_name">فیلتر نام</label><input class="p1cc-input" id="item_name" name="item_name" value="' . inventory_h($filterName) . '"></div>';
echo '<button class="p1cc-btn" type="submit">اعمال فیلتر</button>';
echo '</form></div>';

echo '<div class="p1cc-card"><div class="p1cc-nav-grid" style="margin-bottom:1rem">';
echo '<a class="p1cc-nav-card" href="erp-part-reserve.php"><span class="p1cc-nav-title">رزرو قطعه</span></a>';
echo '<a class="p1cc-nav-card" href="erp-purchase-request-create.php"><span class="p1cc-nav-title">درخواست خرید</span></a>';
echo '</div>';

if ($rows === []) {
    echo '<p class="p1cc-hint">داده‌ای برای نمایش وجود ندارد.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr>';
    echo '<th>کد</th><th>نام</th><th>موجود</th><th>رزرو</th><th>در انتظار دریافت</th><th>قابل رزرو</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $av = (float)($row['available_qty'] ?? '0');
        $rs = (float)($row['reserved_qty'] ?? '0');
        $pend = (float)($row['pending_receive_qty'] ?? '0');
        $free = max(0, $av - $rs);
        $badge = inventory_stock_badge((string)$av, (string)$rs, (string)$pend, $row['min_stock_qty'] ?? '0');
        echo '<tr>';
        echo '<td class="m360-ltr">' . inventory_h($row['item_code'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['item_name'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . inventory_h((string)$av) . '</td>';
        echo '<td class="m360-ltr">' . inventory_h((string)$rs) . '</td>';
        echo '<td class="m360-ltr">' . inventory_h((string)$pend) . '</td>';
        echo '<td class="m360-ltr">' . inventory_h((string)$free) . '</td>';
        echo '<td><span class="p1cc-badge ' . inventory_badge_class($badge) . '">' . inventory_h($badge) . '</span></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

inventory_render_foot();
