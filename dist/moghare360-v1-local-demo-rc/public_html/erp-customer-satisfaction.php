<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 Customer Satisfaction Form
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

$followupScheduleId = crm_get_int('followup_schedule_id');
$customerId = crm_get_int('customer_id');
$operationCaseId = crm_get_int('operation_case_id');
$connection = false;
$errorMessage = '';

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    crm_require_auth($connection, 'crm.satisfaction.write');
} catch (Throwable) {
    $errorMessage = 'فرم رضایت‌سنجی قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

crm_render_head('رضایت‌سنجی مشتری');

echo '<div class="p6crm-internal-note">فرم داخلی پرسنل — این صفحه Customer Portal نیست و هیچ پیام خارجی ارسال نمی‌کند.</div>';
echo '<div class="p6crm-hero"><h1>رضایت‌سنجی مشتری</h1><p>امتیاز ۱ تا ۱۰ — foundation داخلی</p></div>';

if ($errorMessage !== '') {
    crm_error('رضایت‌سنجی', $errorMessage);
}

echo '<form class="p1cc-card" method="post" action="submit-customer-satisfaction.php">';
echo erp_csrf_input('crm_satisfaction');
if ($followupScheduleId !== null) {
    echo '<input type="hidden" name="followup_schedule_id" value="' . $followupScheduleId . '">';
}
if ($customerId !== null) {
    echo '<input type="hidden" name="customer_id" value="' . $customerId . '">';
}
if ($operationCaseId !== null) {
    echo '<input type="hidden" name="operation_case_id" value="' . $operationCaseId . '">';
}
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">امتیاز کلی * (۱–۱۰)</label><input class="p1cc-input m360-ltr" type="number" min="1" max="10" name="overall_score" required></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کیفیت سرویس</label><input class="p1cc-input m360-ltr" type="number" min="1" max="10" name="service_quality_score"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">تحویل</label><input class="p1cc-input m360-ltr" type="number" min="1" max="10" name="delivery_score"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">قیمت</label><input class="p1cc-input m360-ltr" type="number" min="1" max="10" name="price_score"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">رفتار پرسنل</label><input class="p1cc-input m360-ltr" type="number" min="1" max="10" name="staff_behavior_score"></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">احتمال بازگشت</label><input class="p1cc-input m360-ltr" type="number" min="1" max="10" name="comeback_probability_score"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">شکایت</label><textarea class="p1cc-textarea" name="complaint_text" maxlength="2000"></textarea></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">نکته مثبت</label><textarea class="p1cc-textarea" name="positive_note" maxlength="2000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت رضایت‌سنجی</button></form>';

crm_render_foot();
