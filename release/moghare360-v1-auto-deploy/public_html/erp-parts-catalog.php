<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Parts Catalog
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$items = [];
$legacyParts = [];
$flash = inventory_get_string('ok') !== '' ? inventory_flash('item_ok') : '';

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        erp_csrf_require_valid('inventory_catalog_create', $_POST['erp_csrf_token'] ?? null);
        inventory_require_auth($connection, 'inventory.catalog.write');

        if (!inventory_table_exists($connection, 'erp_inventory_items')) {
            throw new RuntimeException('جدول erp_inventory_items یافت نشد. ابتدا SQL فاز ۴ را اجرا کنید.');
        }

        $itemName = inventory_post_string('item_name');
        $itemCode = inventory_post_string('item_code');
        $itemCategory = inventory_post_string('item_category');
        $brand = inventory_post_string('brand');
        $compatibleVehicle = inventory_post_string('compatible_vehicle');
        $unitName = inventory_post_string('unit_name') ?: 'عدد';
        $minStock = inventory_post_float('min_stock_qty') ?? 0.0;
        $notes = inventory_post_string('notes');

        if ($itemName === '') {
            throw new RuntimeException('نام قلم الزامی است.');
        }
        if ($itemCode === '') {
            $itemCode = inventory_generate_item_code();
        }

        if (!@odbc_autocommit($connection, false)) {
            throw new RuntimeException('ثبت قلم انبار انجام نشد.');
        }

        $ok = inventory_execute(
            $connection,
            'INSERT INTO dbo.erp_inventory_items (item_code, item_name, item_category, brand, compatible_vehicle, unit_name, min_stock_qty, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
            [$itemCode, $itemName, $itemCategory ?: null, $brand ?: null, $compatibleVehicle ?: null, $unitName, $minStock, $notes ?: null, inventory_safe_current_user()]
        );
        if ($ok === false) {
            throw new RuntimeException('ثبت قلم انبار انجام نشد.');
        }

        $itemId = inventory_scope_identity($connection);
        if ($itemId !== null && inventory_table_exists($connection, 'erp_stock_balances')) {
            inventory_ensure_balance_row($connection, $itemId, null);
        }

        inventory_insert_history($connection, 'INVENTORY_ITEM', $itemId, 'CREATE', 'ثبت قلم جدید در کاتالوگ', null, $itemCode);

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت قلم انبار انجام نشد.');
        }
        @odbc_autocommit($connection, true);
        inventory_safe_redirect('erp-parts-catalog.php?ok=1');
    }

    inventory_require_auth($connection, 'inventory.catalog.view');

    if (inventory_table_exists($connection, 'erp_inventory_items')) {
        $items = inventory_fetch_rows(
            $connection,
            'SELECT TOP 100 inventory_item_id, item_code, item_name, item_category, brand, unit_name, min_stock_qty, is_active, created_at
             FROM dbo.erp_inventory_items ORDER BY inventory_item_id DESC'
        );
    }

    if (inventory_table_exists($connection, 'erp_parts')) {
        $legacyParts = inventory_fetch_rows(
            $connection,
            'SELECT TOP 50 part_id, part_code, part_name, brand, unit_name, is_active
             FROM dbo.erp_parts ORDER BY part_id DESC'
        );
    }
} catch (Throwable $e) {
    if ($connection !== false && @odbc_autocommit($connection) === false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }
    $errorMessage = 'صفحه کاتالوگ قطعات قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_render_head('کاتالوگ قطعات');

echo '<div class="p4ip-hero"><h1>کاتالوگ قطعات</h1><p>مدیریت اقلام انبار — Phase 4</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . inventory_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    inventory_error('کاتالوگ قطعات', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p4ip-section-title">افزودن قلم جدید</h2>';
echo '<form method="post" action="erp-parts-catalog.php">';
echo erp_csrf_input('inventory_catalog_create');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="item_name">نام قلم *</label><input class="p1cc-input" id="item_name" name="item_name" required maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="item_code">کد قلم</label><input class="p1cc-input m360-ltr" id="item_code" name="item_code" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="item_category">دسته</label><input class="p1cc-input" id="item_category" name="item_category" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="brand">برند</label><input class="p1cc-input" id="brand" name="brand" maxlength="100"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="compatible_vehicle">خودرو سازگار</label><input class="p1cc-input" id="compatible_vehicle" name="compatible_vehicle" maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="unit_name">واحد</label><input class="p1cc-input" id="unit_name" name="unit_name" value="عدد" maxlength="50"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="min_stock_qty">حداقل موجودی</label><input class="p1cc-input m360-ltr" type="number" step="0.01" id="min_stock_qty" name="min_stock_qty" value="0"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="notes">یادداشت</label><textarea class="p1cc-textarea" id="notes" name="notes" maxlength="1000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت قلم</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p4ip-section-title">اقلام انبار (Phase 4)</h2>';
if ($items === []) {
    echo '<p class="p1cc-hint">هنوز قلمی ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>نام</th><th>دسته</th><th>برند</th><th>واحد</th><th>حداقل</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($items as $row) {
        echo '<tr>';
        echo '<td class="m360-ltr">' . inventory_h($row['item_code'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['item_name'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['item_category'] ?? '—') . '</td>';
        echo '<td>' . inventory_h($row['brand'] ?? '—') . '</td>';
        echo '<td>' . inventory_h($row['unit_name'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . inventory_h($row['min_stock_qty'] ?? '0') . '</td>';
        echo '<td><span class="p1cc-badge ' . (($row['is_active'] ?? '1') === '1' ? 'p1cc-badge-active' : 'p1cc-badge-draft') . '">' . (($row['is_active'] ?? '1') === '1' ? 'فعال' : 'غیرفعال') . '</span></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

if ($legacyParts !== []) {
    echo '<div class="p1cc-card"><h2 class="p4ip-section-title">قطعات Legacy (فقط خواندنی)</h2>';
    echo '<p class="p1cc-hint">جدول dbo.erp_parts — بدون تغییر</p>';
    echo '<table class="p1cc-table"><thead><tr><th>شناسه</th><th>کد</th><th>نام</th><th>برند</th><th>واحد</th></tr></thead><tbody>';
    foreach ($legacyParts as $row) {
        echo '<tr>';
        echo '<td>' . inventory_h($row['part_id'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . inventory_h($row['part_code'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['part_name'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['brand'] ?? '—') . '</td>';
        echo '<td>' . inventory_h($row['unit_name'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

inventory_render_foot();
