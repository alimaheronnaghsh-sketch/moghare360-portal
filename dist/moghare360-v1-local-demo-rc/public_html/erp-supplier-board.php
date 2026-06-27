<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 4 Supplier Board
 */

require_once __DIR__ . '/includes/erp-inventory-purchase-helper.php';

$connection = false;
$errorMessage = '';
$suppliers = [];
$purchaseRequests = [];
$flash = match (inventory_get_string('ok')) {
    'supplier' => inventory_flash('supplier_ok'),
    'purchase' => inventory_flash('purchase_ok'),
    'status' => inventory_flash('status_ok'),
    default => '',
};

try {
    $connection = inventory_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && inventory_post_string('form_action') === 'create_supplier') {
        erp_csrf_require_valid('inventory_supplier_create', $_POST['erp_csrf_token'] ?? null);
        inventory_require_auth($connection, 'inventory.supplier.write');

        if (!inventory_table_exists($connection, 'erp_suppliers')) {
            throw new RuntimeException('جدول erp_suppliers یافت نشد.');
        }

        $supplierName = inventory_post_string('supplier_name');
        $supplierCode = inventory_post_string('supplier_code') ?: inventory_generate_supplier_code();
        $supplierType = inventory_post_string('supplier_type') ?: 'LOCAL';
        $contactName = inventory_post_string('contact_name');
        $mobile = inventory_post_string('mobile');
        $phone = inventory_post_string('phone');
        $address = inventory_post_string('address_text');
        $notes = inventory_post_string('notes');

        if ($supplierName === '') {
            throw new RuntimeException('نام تامین‌کننده الزامی است.');
        }

        $allowedTypes = ['LOCAL', 'IMPORT', 'OEM', 'USED_PARTS', 'CORPORATE'];
        if (!in_array($supplierType, $allowedTypes, true)) {
            $supplierType = 'LOCAL';
        }

        $ok = inventory_execute(
            $connection,
            'INSERT INTO dbo.erp_suppliers (supplier_code, supplier_name, supplier_type, contact_name, mobile, phone, address_text, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
            [$supplierCode, $supplierName, $supplierType, $contactName ?: null, $mobile ?: null, $phone ?: null, $address ?: null, $notes ?: null, inventory_safe_current_user()]
        );
        if ($ok === false) {
            throw new RuntimeException('ثبت تامین‌کننده انجام نشد.');
        }

        $supplierId = inventory_scope_identity($connection);
        inventory_insert_history($connection, 'SUPPLIER', $supplierId, 'CREATE', 'ثبت تامین‌کننده جدید', null, $supplierCode);
        inventory_safe_redirect('erp-supplier-board.php?ok=supplier');
    }

    inventory_require_auth($connection, 'inventory.supplier.view');

    if (inventory_table_exists($connection, 'erp_suppliers')) {
        $suppliers = inventory_fetch_rows(
            $connection,
            'SELECT supplier_id, supplier_code, supplier_name, supplier_type, mobile, phone, is_active FROM dbo.erp_suppliers ORDER BY supplier_id DESC'
        );
    }

    $purchaseTable = inventory_purchase_table($connection);
    if ($purchaseTable !== null) {
        $purchaseRequests = inventory_fetch_rows(
            $connection,
            'SELECT TOP 50 purchase_request_id, request_code, requested_part_name, requested_qty, request_status, supplier_id, created_at
             FROM dbo.' . $purchaseTable . ' ORDER BY purchase_request_id DESC'
        );
    }
} catch (Throwable) {
    $errorMessage = 'تابلو تامین‌کنندگان قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

inventory_render_head('تابلو تامین‌کنندگان');

echo '<div class="p4ip-hero"><h1>تابلو تامین‌کنندگان</h1><p>مدیریت تامین‌کنندگان و درخواست‌های خرید</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . inventory_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    inventory_error('تابلو تامین‌کنندگان', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p4ip-section-title">افزودن تامین‌کننده</h2>';
echo '<form method="post" action="erp-supplier-board.php">';
echo '<input type="hidden" name="form_action" value="create_supplier">';
echo erp_csrf_input('inventory_supplier_create');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="supplier_name">نام *</label><input class="p1cc-input" id="supplier_name" name="supplier_name" required maxlength="300"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="supplier_code">کد</label><input class="p1cc-input m360-ltr" id="supplier_code" name="supplier_code" maxlength="80"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="supplier_type">نوع</label><select class="p1cc-select" id="supplier_type" name="supplier_type">';
foreach (['LOCAL' => 'محلی', 'IMPORT' => 'وارداتی', 'OEM' => 'OEM', 'USED_PARTS' => 'قطعات دست‌دوم', 'CORPORATE' => 'سازمانی'] as $v => $l) {
    echo '<option value="' . $v . '">' . $l . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="contact_name">نام تماس</label><input class="p1cc-input" id="contact_name" name="contact_name" maxlength="200"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="mobile">موبایل</label><input class="p1cc-input m360-ltr" id="mobile" name="mobile" maxlength="50"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label" for="phone">تلفن</label><input class="p1cc-input m360-ltr" id="phone" name="phone" maxlength="50"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="address_text">آدرس</label><input class="p1cc-input" id="address_text" name="address_text" maxlength="1000"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label" for="notes">یادداشت</label><textarea class="p1cc-textarea" id="notes" name="notes" maxlength="1000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت تامین‌کننده</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p4ip-section-title">تامین‌کنندگان</h2>';
if ($suppliers === []) {
    echo '<p class="p1cc-hint">تامین‌کننده‌ای ثبت نشده است.</p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>نام</th><th>نوع</th><th>موبایل</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($suppliers as $row) {
        echo '<tr>';
        echo '<td class="m360-ltr">' . inventory_h($row['supplier_code'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['supplier_name'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['supplier_type'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . inventory_h($row['mobile'] ?? '—') . '</td>';
        echo '<td><span class="p1cc-badge ' . (($row['is_active'] ?? '1') === '1' ? 'p1cc-badge-active' : 'p1cc-badge-draft') . '">' . (($row['is_active'] ?? '1') === '1' ? 'فعال' : 'غیرفعال') . '</span></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

echo '<div class="p1cc-card"><h2 class="p4ip-section-title">درخواست‌های خرید</h2>';
if ($purchaseRequests === []) {
    echo '<p class="p1cc-hint">درخواست خریدی ثبت نشده است. <a href="erp-purchase-request-create.php">ایجاد درخواست</a></p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>قطعه</th><th>تعداد</th><th>وضعیت</th><th>تاریخ</th><th>به‌روزرسانی</th></tr></thead><tbody>';
    foreach ($purchaseRequests as $row) {
        $prId = (int)($row['purchase_request_id'] ?? 0);
        $status = (string)($row['request_status'] ?? '');
        echo '<tr>';
        echo '<td class="m360-ltr">' . inventory_h($row['request_code'] ?? '') . '</td>';
        echo '<td>' . inventory_h($row['requested_part_name'] ?? '') . '</td>';
        echo '<td class="m360-ltr">' . inventory_h($row['requested_qty'] ?? '') . '</td>';
        echo '<td><span class="p1cc-badge p1cc-badge-draft">' . inventory_h($status) . '</span></td>';
        echo '<td>' . inventory_h($row['created_at'] ?? '') . '</td>';
        echo '<td><form method="post" action="submit-purchase-status-update.php" style="display:flex;gap:.35rem;align-items:center">';
        echo erp_csrf_input('inventory_purchase_status');
        echo '<input type="hidden" name="purchase_request_id" value="' . $prId . '">';
        echo '<select class="p1cc-select" name="new_status" style="min-width:140px">';
        foreach (['REQUESTED', 'SUPPLIER_PENDING', 'ORDERED', 'PENDING_RECEIVE', 'RECEIVED', 'CANCELLED'] as $st) {
            if ($st === $status) {
                continue;
            }
            echo '<option value="' . $st . '">' . $st . '</option>';
        }
        echo '</select><button class="p1cc-btn" type="submit">تغییر</button></form></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

inventory_render_foot();
