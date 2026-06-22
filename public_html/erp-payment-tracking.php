<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 5 Payment Tracking
 */

require_once __DIR__ . '/includes/erp-pricing-engine.php';

$connection = false;
$errorMessage = '';
$headers = [];
$filterStatus = pricing_get_string('payment_status');
$prefillHeaderId = pricing_get_int('cost_header_id');
$flash = pricing_flash(pricing_get_string('ok'));

try {
    $connection = pricing_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    pricing_require_auth($connection, 'finance.payment.view');

    if (pricing_table_exists($connection, 'erp_jobcard_cost_headers')) {
        $sql = "SELECT TOP 50 cost_header_id, cost_code, operation_case_id, jobcard_id, customer_id, payable_total, paid_total, remaining_total, payment_status FROM dbo.erp_jobcard_cost_headers WHERE calculation_status <> 'CANCELLED'";
        $params = [];
        if ($filterStatus !== '') {
            $sql .= ' AND payment_status = ?';
            $params[] = $filterStatus;
        }
        $sql .= ' ORDER BY cost_header_id DESC';
        $headers = pricing_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'پیگیری پرداخت قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

pricing_render_head('پیگیری پرداخت');

echo '<div class="p5fs-hero"><h1>پیگیری پرداخت</h1><p>ثبت و پیگیری وضعیت بدهی و پرداخت</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . pricing_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    pricing_error('پیگیری پرداخت', $errorMessage);
}

echo '<div class="p1cc-card"><form method="get" class="p1cc-form-grid" style="align-items:end">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت پرداخت</label><select class="p1cc-select" name="payment_status"><option value="">همه</option>';
foreach (['UNPAID', 'PARTIAL_PAID', 'PAID', 'OVERPAID'] as $st) {
    $sel = $filterStatus === $st ? ' selected' : '';
    echo '<option value="' . $st . '"' . $sel . '>' . pricing_h(pricing_payment_status_label($st)) . '</option>';
}
echo '</select></div><button class="p1cc-btn" type="submit">فیلتر</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">ثبت پرداخت جدید</h2>';
echo '<form method="post" action="submit-payment-record.php">';
echo erp_csrf_input('finance_payment_record');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">سربرگ هزینه *</label><input class="p1cc-input m360-ltr" name="cost_header_id" required value="' . pricing_h($prefillHeaderId !== null ? (string)$prefillHeaderId : '') . '"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مبلغ *</label><input class="p1cc-input m360-ltr" type="number" step="0.01" min="0.01" name="payment_amount" required></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">روش پرداخت</label><select class="p1cc-select" name="payment_method">';
foreach (['CASH' => 'نقد', 'CARD' => 'کارت', 'BANK_TRANSFER' => 'انتقال بانکی', 'POS_PLACEHOLDER' => 'POS (placeholder)', 'CREDIT' => 'اعتبار', 'OTHER' => 'سایر'] as $v => $l) {
    echo '<option value="' . $v . '">' . $l . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">پرونده عملیات</label><input class="p1cc-input m360-ltr" name="operation_case_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">JobCard</label><input class="p1cc-input m360-ltr" name="jobcard_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مشتری</label><input class="p1cc-input m360-ltr" name="customer_id"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">مرجع</label><input class="p1cc-input" name="payment_reference" maxlength="200"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="payment_note" maxlength="1500"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت پرداخت</button></form></div>';

echo '<div class="p1cc-card"><h2 class="p5fs-section-title">سربرگ‌های هزینه</h2>';
if ($headers === []) {
    echo '<p class="p1cc-hint">سربرگ هزینه‌ای یافت نشد. <a href="erp-jobcard-cost-preview.php">ایجاد سربرگ</a></p>';
} else {
    echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>پرونده</th><th>قابل پرداخت</th><th>پرداخت‌شده</th><th>مانده</th><th>وضعیت</th><th></th></tr></thead><tbody>';
    foreach ($headers as $h) {
        $hid = (int)($h['cost_header_id'] ?? 0);
        echo '<tr>';
        echo '<td class="m360-ltr">' . pricing_h($h['cost_code'] ?? '') . '</td>';
        echo '<td>' . pricing_h($h['operation_case_id'] !== '' ? $h['operation_case_id'] : '—') . '</td>';
        echo '<td class="m360-ltr">' . pricing_h(pricing_format_amount($h['payable_total'] ?? '0')) . '</td>';
        echo '<td class="m360-ltr">' . pricing_h(pricing_format_amount($h['paid_total'] ?? '0')) . '</td>';
        echo '<td class="m360-ltr">' . pricing_h(pricing_format_amount($h['remaining_total'] ?? '0')) . '</td>';
        echo '<td><span class="p1cc-badge ' . pricing_badge_class($h['payment_status'] ?? '') . '">' . pricing_h(pricing_payment_status_label($h['payment_status'] ?? '')) . '</span></td>';
        echo '<td><a href="erp-jobcard-cost-preview.php?cost_header_id=' . $hid . '">جزئیات</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

pricing_render_foot();
