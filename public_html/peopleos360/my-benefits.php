<?php
declare(strict_types=1);

/**
 * Read-only employee view of contractual Eid/severance/benefits (no mutation).
 */

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';
require_once __DIR__ . '/includes/p360-hr-contract-engine.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
p360hr_layout_start('عیدی، سنوات و مزایا', 'خلاصه مزایای قراردادی قابل‌مشاهده برای شما');
if ($emp === null) {
    echo '<div class="m360-alert m360-alert-err">پرونده پرسنلی به حساب شما متصل نشده است.</div>';
    p360hr_layout_end();
    exit;
}
$eid = (int)($emp['employee_id'] ?? 0);
$rows = p360hr_rows(
    "SELECT contract_id, personnel_code, contract_type, contract_job_title, stage_code, is_locked,
            eid_payment_method, severance_payment_method, start_date, end_date
     FROM dbo.p360_hr_contracts
     WHERE employee_id=? AND is_locked=1
     ORDER BY contract_id DESC",
    [$eid]
);

echo '<section class="p360hr-section"><div class="p360hr-section-card">';
echo '<p class="p360hr-sub" style="margin-bottom:1rem">این صفحه فقط‌خواندنی است و مقادیر ساختگی ایجاد نمی‌کند.</p>';
if ($rows === []) {
    echo '<div class="p360hr-empty">قرارداد نهایی‌شده‌ای برای نمایش مزایا یافت نشد.</div>';
} else {
    echo '<div class="p360hr-readonly-grid">';
    foreach ($rows as $c) {
        echo '<div class="p360hr-readonly">';
        echo '<span class="lbl">قرارداد #' . p360hr_h((string)$c['contract_id']) . ' — ' . p360hr_h((string)($c['contract_job_title'] ?? '')) . '</span>';
        echo '<span class="val">نوع: ' . p360hr_h((string)($c['contract_type'] ?? '')) . '</span>';
        echo '<span class="val">عیدی: ' . p360hr_h((string)($c['eid_payment_method'] ?? '—')) . '</span>';
        echo '<span class="val">سنوات: ' . p360hr_h((string)($c['severance_payment_method'] ?? '—')) . '</span>';
        echo '<span class="val">اعتبار: ' . p360hr_h(p360hr_date_jalali((string)($c['start_date'] ?? ''))) . ' تا ' . p360hr_h(p360hr_date_jalali((string)($c['end_date'] ?? ''))) . '</span>';
        echo '</div>';
    }
    echo '</div>';
}
echo '</div></section>';
echo '<p><a class="m360-btn m360-btn-secondary" href="my-cartable.php">بازگشت به میز کار</a> ';
echo '<a class="m360-btn m360-btn-secondary" href="my-contracts.php">قراردادهای من</a></p>';
p360hr_layout_end();
