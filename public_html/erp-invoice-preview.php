<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 Invoice Preview (internal, non-official)
 */

require_once __DIR__ . '/includes/erp-pricing-engine.php';

$connection = false;
$errorMessage = '';
$costHeaderId = pricing_get_int('cost_header_id');
$header = null;
$lines = [];
$customer = null;
$operationCase = null;
$previews = [];
$flash = pricing_flash(pricing_get_string('ok'));

try {
    $connection = pricing_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && pricing_post_string('form_action') === 'create_preview') {
        erp_csrf_require_valid('finance_invoice_preview', $_POST['erp_csrf_token'] ?? null);
        pricing_require_auth($connection, 'finance.invoice.preview');

        $hid = pricing_post_int('cost_header_id');
        if ($hid === null) {
            throw new RuntimeException('شناسه سربرگ هزینه الزامی است.');
        }

        $header = pricing_get_cost_header($connection, $hid);
        if ($header === null) {
            throw new RuntimeException('سربرگ هزینه یافت نشد.');
        }

        if (!pricing_table_exists($connection, 'erp_invoice_previews')) {
            throw new RuntimeException('جدول erp_invoice_previews یافت نشد.');
        }

        $previewCode = pricing_generate_invoice_preview_code();
        $ok = pricing_execute(
            $connection,
            'INSERT INTO dbo.erp_invoice_previews (cost_header_id, preview_code, preview_status, service_total, labour_total, parts_total, discount_total, payable_total, paid_total, remaining_total, preview_note, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $hid, $previewCode, 'READY',
                (float)($header['service_total'] ?? 0), (float)($header['labour_total'] ?? 0), (float)($header['parts_total'] ?? 0),
                (float)($header['discount_total'] ?? 0), (float)($header['payable_total'] ?? 0), (float)($header['paid_total'] ?? 0),
                (float)($header['remaining_total'] ?? 0), pricing_post_string('preview_note') ?: 'پیش‌نمایش داخلی', pricing_safe_current_user(),
            ]
        );
        if ($ok === false) {
            throw new RuntimeException('ایجاد پیش‌نمایش انجام نشد.');
        }

        $previewId = pricing_scope_identity($connection);
        pricing_insert_history($connection, 'INVOICE_PREVIEW', $previewId, 'CREATE', 'ایجاد پیش‌نمایش فاکتور داخلی', null, $previewCode);
        pricing_safe_redirect('erp-invoice-preview.php?cost_header_id=' . $hid . '&ok=preview_ok');
    }

    pricing_require_auth($connection, 'finance.invoice.preview');

    if ($costHeaderId !== null) {
        $header = pricing_get_cost_header($connection, $costHeaderId);
        if ($header !== null) {
            $lines = pricing_fetch_rows($connection, 'SELECT line_type, line_title, qty, unit_price, discount_amount, line_total FROM dbo.erp_jobcard_cost_lines WHERE cost_header_id=? ORDER BY cost_line_id', [$costHeaderId]);
            $customer = pricing_get_customer_preview($connection, ctype_digit((string)($header['customer_id'] ?? '')) ? (int)$header['customer_id'] : null);
            if (ctype_digit((string)($header['operation_case_id'] ?? ''))) {
                $operationCase = pricing_get_operation_case($connection, (int)$header['operation_case_id']);
            }
            if (pricing_table_exists($connection, 'erp_invoice_previews')) {
                $previews = pricing_fetch_rows($connection, 'SELECT invoice_preview_id, preview_code, preview_status, created_at FROM dbo.erp_invoice_previews WHERE cost_header_id=? ORDER BY invoice_preview_id DESC', [$costHeaderId]);
            }
        }
    }
} catch (Throwable) {
    $errorMessage = 'پیش‌نمایش فاکتور قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

pricing_render_head('پیش‌نمایش فاکتور');

echo '<div class="p5fs-invoice-warning">این پیش‌نمایش داخلی است و فاکتور رسمی مالیاتی نیست.</div>';
echo '<div class="p5fs-hero"><h1>پیش‌نمایش فاکتور</h1><p>نمایش داخلی — بدون شماره فاکتور رسمی و بدون مالیات</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . pricing_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    pricing_error('پیش‌نمایش فاکتور', $errorMessage);
}

if ($costHeaderId === null) {
    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">جستجوی سربرگ هزینه</h2>';
    echo '<form method="get"><div class="p1cc-form-group"><label class="p1cc-label">شناسه سربرگ هزینه</label><input class="p1cc-input m360-ltr" name="cost_header_id" required></div>';
    echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">نمایش</button></form></div>';
} elseif ($header === null) {
    echo '<div class="p1cc-card p1cc-error"><p>سربرگ هزینه یافت نشد.</p></div>';
} else {
    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">اطلاعات پرونده</h2>';
    echo '<p>کد هزینه: <strong class="m360-ltr">' . pricing_h($header['cost_code'] ?? '') . '</strong></p>';
    if ($operationCase !== null) {
        echo '<p>پرونده عملیات: ' . pricing_h($operationCase['operation_code'] ?? '') . ' — ' . pricing_h($operationCase['current_stage'] ?? '') . '</p>';
    }
    if ($customer !== null) {
        echo '<p>مشتری: ' . pricing_h($customer['full_name'] ?? '—') . ' — ' . pricing_h($customer['mobile'] ?? '') . '</p>';
    }
    echo '<p>وضعیت پرداخت: <span class="p1cc-badge ' . pricing_badge_class($header['payment_status'] ?? '') . '">' . pricing_h(pricing_payment_status_label($header['payment_status'] ?? '')) . '</span></p>';
    echo '</div>';

    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">ردیف‌ها</h2>';
    if ($lines === []) {
        echo '<p class="p1cc-hint">ردیفی ثبت نشده است.</p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr><th>نوع</th><th>عنوان</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead><tbody>';
        foreach ($lines as $ln) {
            echo '<tr><td>' . pricing_h($ln['line_type'] ?? '') . '</td><td>' . pricing_h($ln['line_title'] ?? '') . '</td><td class="m360-ltr">' . pricing_h($ln['qty'] ?? '') . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($ln['unit_price'] ?? '0')) . '</td><td class="m360-ltr">' . pricing_h(pricing_format_amount($ln['line_total'] ?? '0')) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '<div class="p5fs-totals">';
    foreach (['service_total' => 'خدمات', 'labour_total' => 'اجرت', 'parts_total' => 'قطعات', 'discount_total' => 'تخفیف', 'payable_total' => 'قابل پرداخت', 'paid_total' => 'پرداخت‌شده', 'remaining_total' => 'مانده'] as $k => $l) {
        echo '<div class="p5fs-total-item"><span>' . $l . '</span><strong class="m360-num">' . pricing_h(pricing_format_amount($header[$k] ?? '0')) . '</strong></div>';
    }
    echo '</div></div>';

    echo '<div class="p1cc-card"><h2 class="p5fs-section-title">ایجاد پیش‌نمایش داخلی</h2>';
    echo '<form method="post"><input type="hidden" name="form_action" value="create_preview"><input type="hidden" name="cost_header_id" value="' . (int)$costHeaderId . '">';
    echo erp_csrf_input('finance_invoice_preview');
    echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت پیش‌نمایش</label><textarea class="p1cc-textarea" name="preview_note" maxlength="1500">پیش‌نمایش داخلی — غیر رسمی</textarea></div>';
    echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ایجاد پیش‌نمایش</button></form></div>';

    if ($previews !== []) {
        echo '<div class="p1cc-card"><h2 class="p5fs-section-title">پیش‌نمایش‌های ثبت‌شده</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>';
        foreach ($previews as $pv) {
            echo '<tr><td class="m360-ltr">' . pricing_h($pv['preview_code'] ?? '') . '</td><td>' . pricing_h($pv['preview_status'] ?? '') . '</td><td>' . pricing_h($pv['created_at'] ?? '') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

pricing_render_foot();
