<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 JobCard Cost Preview
 */

require_once __DIR__ . '/includes/erp-pricing-engine.php';

$connection = false;
$errorMessage = '';
$costHeaderId = pricing_get_int('cost_header_id');
$header = null;
$lines = [];
$payments = [];
$flash = pricing_flash(pricing_get_string('ok'));

try {
    $connection = pricing_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = pricing_post_string('form_action');
        pricing_require_auth($connection, 'finance.cost.write');

        if ($action === 'create_header') {
            erp_csrf_require_valid('finance_cost_header', $_POST['erp_csrf_token'] ?? null);
            $id = pricing_get_or_create_cost_header($connection, [
                'operation_case_id' => pricing_post_int('operation_case_id'),
                'jobcard_id' => pricing_post_int('jobcard_id'),
                'customer_id' => pricing_post_int('customer_id'),
                'vehicle_binding_id' => pricing_post_int('vehicle_binding_id'),
                'preview_note' => pricing_post_string('preview_note') ?: null,
            ]);
            if ($id === null) {
                throw new RuntimeException('ایجاد سربرگ هزینه انجام نشد.');
            }
            pricing_safe_redirect('erp-jobcard-cost-preview.php?cost_header_id=' . $id . '&ok=cost_ok');
        }

        if ($action === 'add_line') {
            erp_csrf_require_valid('finance_cost_line', $_POST['erp_csrf_token'] ?? null);
            $hid = pricing_post_int('cost_header_id');
            $lineType = pricing_post_string('line_type') ?: 'SERVICE';
            $title = pricing_post_string('line_title');
            $qty = pricing_post_float('qty') ?? 1.0;
            $unitPrice = pricing_post_float('unit_price') ?? 0.0;
            $discount = pricing_post_float('discount_amount') ?? 0.0;
            if ($hid === null || $title === '') {
                throw new RuntimeException('سربرگ و عنوان ردیف الزامی است.');
            }
            $lineTotal = pricing_calculate_line_total($qty, $unitPrice, $discount);
            if (!@odbc_autocommit($connection, false)) {
                throw new RuntimeException('ثبت ردیف انجام نشد.');
            }
            pricing_execute($connection, 'INSERT INTO dbo.erp_jobcard_cost_lines (cost_header_id, operation_case_id, service_step_id, inventory_item_id, line_type, line_title, qty, unit_price, discount_amount, line_total, line_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [
                $hid, pricing_post_int('operation_case_id'), pricing_post_int('service_step_id'), pricing_post_int('inventory_item_id'),
                $lineType, $title, $qty, $unitPrice, $discount, $lineTotal, pricing_post_string('line_note') ?: null, pricing_safe_current_user(),
            ]);
            pricing_recalculate_cost_header($connection, $hid);
            @odbc_commit($connection);
            @odbc_autocommit($connection, true);
            pricing_safe_redirect('erp-jobcard-cost-preview.php?cost_header_id=' . $hid . '&ok=line_ok');
        }

        if ($action === 'recalculate') {
            erp_csrf_require_valid('finance_cost_recalc', $_POST['erp_csrf_token'] ?? null);
            $hid = pricing_post_int('cost_header_id');
            if ($hid === null || !pricing_recalculate_cost_header($connection, $hid)) {
                throw new RuntimeException('محاسبه مجدد انجام نشد.');
            }
            pricing_safe_redirect('erp-jobcard-cost-preview.php?cost_header_id=' . $hid . '&ok=recalc_ok');
        }
    }

    pricing_require_auth($connection, 'finance.cost.view');

    if ($costHeaderId !== null) {
        $header = pricing_get_cost_header($connection, $costHeaderId);
        if ($header !== null) {
            $lines = pricing_fetch_rows($connection, 'SELECT * FROM dbo.erp_jobcard_cost_lines WHERE cost_header_id=? ORDER BY cost_line_id', [$costHeaderId]);
            if (pricing_table_exists($connection, 'erp_payment_records')) {
                $payments = pricing_fetch_rows($connection, "SELECT payment_record_id, payment_code, payment_method, payment_amount, payment_status, paid_at FROM dbo.erp_payment_records WHERE cost_header_id=? ORDER BY payment_record_id DESC", [$costHeaderId]);
            }
        }
    }
} catch (Throwable) {
    $errorMessage = 'صفحه هزینه JobCard قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

pricing_render_head('هزینه JobCard');

echo '<div class="p5fs-hero"><h1>هزینه JobCard</h1><p>موتور محاسبه هزینه — بدون فاکتور رسمی</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . pricing_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    pricing_error('هزینه JobCard', $errorMessage);
}

if ($header === null) {
    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">ایجاد سربرگ هزینه</h2>';
    echo '<form method="post"><input type="hidden" name="form_action" value="create_header">';
    echo erp_csrf_input('finance_cost_header');
    echo '<div class="p1cc-form-grid">';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">پرونده عملیات</label><input class="p1cc-input m360-ltr" name="operation_case_id" value="' . pricing_h(pricing_get_string('operation_case_id')) . '"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">JobCard ID</label><input class="p1cc-input m360-ltr" name="jobcard_id" value="' . pricing_h(pricing_get_string('jobcard_id')) . '"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">مشتری</label><input class="p1cc-input m360-ltr" name="customer_id"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">اتصال خودرو</label><input class="p1cc-input m360-ltr" name="vehicle_binding_id"></div>';
    echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="preview_note" maxlength="1500"></textarea></div>';
    echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ایجاد سربرگ</button></form></div>';
} else {
    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">سربرگ #' . pricing_h((string)$costHeaderId) . ' — ' . pricing_h($header['cost_code'] ?? '') . '</h2>';
    echo '<p class="p1cc-hint">وضعیت: ' . pricing_h($header['calculation_status'] ?? '') . ' · پرداخت: <span class="p1cc-badge ' . pricing_badge_class($header['payment_status'] ?? '') . '">' . pricing_h(pricing_payment_status_label($header['payment_status'] ?? '')) . '</span></p>';
    echo '<div class="p5fs-totals">';
    foreach (['service_total' => 'خدمات', 'labour_total' => 'اجرت', 'parts_total' => 'قطعات', 'discount_total' => 'تخفیف', 'payable_total' => 'قابل پرداخت', 'paid_total' => 'پرداخت‌شده', 'remaining_total' => 'مانده'] as $k => $l) {
        echo '<div class="p5fs-total-item"><span>' . $l . '</span><strong class="m360-num">' . pricing_h(pricing_format_amount($header[$k] ?? '0')) . '</strong></div>';
    }
    echo '</div>';
    echo '<p><a class="p1cc-btn" href="erp-invoice-preview.php?cost_header_id=' . (int)$costHeaderId . '">پیش‌نمایش فاکتور داخلی</a> ';
    echo '<a class="p1cc-btn" href="erp-payment-tracking.php?cost_header_id=' . (int)$costHeaderId . '">ثبت پرداخت</a></p></div>';

    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">ردیف‌های هزینه</h2>';
    if ($lines === []) {
        echo '<p class="p1cc-hint">ردیفی ثبت نشده است.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr><th>نوع</th><th>عنوان</th><th>تعداد</th><th>قیمت</th><th>تخفیف</th><th>جمع</th></tr></thead><tbody>';
        foreach ($lines as $ln) {
            echo '<tr><td>' . pricing_h($ln['line_type'] ?? '') . '</td><td>' . pricing_h($ln['line_title'] ?? '') . '</td><td class="m360-ltr">' . pricing_h($ln['qty'] ?? '') . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($ln['unit_price'] ?? '0')) . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($ln['discount_amount'] ?? '0')) . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($ln['line_total'] ?? '0')) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';

    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">افزودن ردیف</h2><form method="post">';
    echo '<input type="hidden" name="form_action" value="add_line"><input type="hidden" name="cost_header_id" value="' . (int)$costHeaderId . '">';
    echo erp_csrf_input('finance_cost_line');
    echo '<div class="p1cc-form-grid">';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">نوع</label><select class="p1cc-select" name="line_type">';
    foreach (['SERVICE' => 'خدمت', 'LABOUR' => 'اجرت', 'PART' => 'قطعه', 'DISCOUNT' => 'تخفیف', 'MANUAL_ADJUSTMENT' => 'تعدیل'] as $v => $l) {
        echo '<option value="' . $v . '">' . $l . '</option>';
    }
    echo '</select></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">عنوان *</label><input class="p1cc-input" name="line_title" required maxlength="300"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">تعداد</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="qty" value="1"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">قیمت واحد</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="unit_price" value="0"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">تخفیف</label><input class="p1cc-input m360-ltr" type="number" step="0.01" name="discount_amount" value="0"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">مرحله سرویس</label><input class="p1cc-input m360-ltr" name="service_step_id"></div>';
    echo '<div class="p1cc-form-group"><label class="p1cc-label">قلم انبار</label><input class="p1cc-input m360-ltr" name="inventory_item_id"></div>';
    echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="line_note" maxlength="1000"></textarea></div>';
    echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">افزودن ردیف</button></form></div>';

    echo '<div class="p1cc-card"><form method="post" style="display:inline">';
    echo '<input type="hidden" name="form_action" value="recalculate"><input type="hidden" name="cost_header_id" value="' . (int)$costHeaderId . '">';
    echo erp_csrf_input('finance_cost_recalc');
    echo '<button class="p1cc-btn" type="submit">محاسبه مجدد</button></form></div>';

    if ($payments !== []) {
        echo '<div class="p1cc-card"><h2 class="p5fs-section-title">پرداخت‌ها</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>روش</th><th>مبلغ</th><th>تاریخ</th></tr></thead><tbody>';
        foreach ($payments as $p) {
            echo '<tr><td class="m360-ltr">' . pricing_h($p['payment_code'] ?? '') . '</td><td>' . pricing_h($p['payment_method'] ?? '') . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($p['payment_amount'] ?? '0')) . '</td><td>' . pricing_h($p['paid_at'] ?? '') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

pricing_render_foot();
