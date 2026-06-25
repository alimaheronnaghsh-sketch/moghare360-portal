<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 6 CRM Follow-up Detail
 */

require_once __DIR__ . '/includes/erp-crm-helper.php';

$scheduleId = crm_get_int('followup_schedule_id');
$connection = false;
$errorMessage = '';
$schedule = null;
$customer = null;
$operation = null;
$records = [];
$surveys = [];
$upsells = [];
$flash = crm_flash(crm_get_string('ok'));

if ($scheduleId === null) {
    crm_error('جزئیات پیگیری', 'شناسه پیگیری (followup_schedule_id) الزامی است.');
}

try {
    $connection = crm_db();
    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }
    crm_require_auth($connection, 'crm.followup.view');

    $schedule = crm_get_schedule($connection, $scheduleId);
    if ($schedule === null) {
        crm_error('جزئیات پیگیری', 'پیگیری یافت نشد.');
    }

    $customerId = ctype_digit((string)($schedule['customer_id'] ?? '')) ? (int)$schedule['customer_id'] : null;
    $intakeId = ctype_digit((string)($schedule['intake_id'] ?? '')) ? (int)$schedule['intake_id'] : null;
    $operationCaseId = ctype_digit((string)($schedule['operation_case_id'] ?? '')) ? (int)$schedule['operation_case_id'] : null;

    $customer = crm_get_customer_preview($connection, $customerId, $intakeId);
    $operation = crm_get_operation_preview($connection, $operationCaseId);

    $records = crm_fetch_rows($connection, 'SELECT * FROM dbo.erp_crm_followup_records WHERE followup_schedule_id=? ORDER BY followup_record_id DESC', [$scheduleId]);

    if (crm_table_exists($connection, 'erp_customer_satisfaction_surveys')) {
        $surveys = crm_fetch_rows($connection, 'SELECT satisfaction_id, overall_score, created_at FROM dbo.erp_customer_satisfaction_surveys WHERE followup_schedule_id=? ORDER BY satisfaction_id DESC', [$scheduleId]);
    }

    if (crm_table_exists($connection, 'erp_upsell_opportunities') && $customerId !== null) {
        $upsells = crm_fetch_rows($connection, 'SELECT TOP 10 upsell_id, opportunity_code, opportunity_title, opportunity_status FROM dbo.erp_upsell_opportunities WHERE customer_id=? ORDER BY upsell_id DESC', [$customerId]);
    }
} catch (Throwable) {
    $errorMessage = 'جزئیات پیگیری قابل بارگذاری نیست.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

crm_render_head('جزئیات پیگیری');

echo '<div class="p6crm-hero"><h1>جزئیات پیگیری</h1><p>' . crm_h($schedule['followup_code'] ?? '') . '</p></div>';

if ($flash !== '') {
    echo '<div class="p1cc-card p1cc-success"><p>' . crm_h($flash) . '</p></div>';
}
if ($errorMessage !== '') {
    crm_error('جزئیات پیگیری', $errorMessage);
}

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">برنامه پیگیری</h2>';
echo '<p>وضعیت: <span class="p1cc-badge ' . crm_badge_class($schedule['followup_status'] ?? '') . '">' . crm_h($schedule['followup_status'] ?? '') . '</span></p>';
echo '<p>دلیل: ' . crm_h($schedule['followup_reason'] ?? '') . ' · زمان: ' . crm_h($schedule['scheduled_at'] ?? '') . '</p>';
echo '<p>اولویت: ' . crm_h($schedule['priority_level'] ?? '') . ' · مسئول: ' . crm_h($schedule['assigned_to_text'] ?? '—') . '</p>';
if (($schedule['source_note'] ?? '') !== '') {
    echo '<p class="p1cc-hint">' . crm_h($schedule['source_note']) . '</p>';
}
echo '</div>';

if ($customer !== null) {
    echo '<div class="p1cc-card"><h2 class="p6crm-section-title">مشتری</h2>';
    echo '<p>' . crm_h($customer['full_name'] ?? '—') . ' · ' . crm_h($customer['mobile'] ?? '') . '</p></div>';
}
if ($operation !== null) {
    echo '<div class="p1cc-card"><h2 class="p6crm-section-title">پرونده عملیات</h2>';
    echo '<p>' . crm_h($operation['operation_code'] ?? '') . ' — ' . crm_h($operation['current_stage'] ?? '') . '</p></div>';
}

echo '<div class="p1cc-card"><h2 class="p6crm-section-title">ثبت نتیجه تماس</h2>';
echo '<form method="post" action="submit-crm-followup.php">';
echo '<input type="hidden" name="followup_schedule_id" value="' . (int)$scheduleId . '">';
echo erp_csrf_input('crm_followup_record');
echo '<div class="p1cc-form-grid">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">کانال *</label><select class="p1cc-select" name="contact_channel" required>';
foreach (['PHONE' => 'تلفن', 'WHATSAPP_PLACEHOLDER' => 'واتساپ (placeholder)', 'IN_PERSON' => 'حضوری', 'SMS_PLACEHOLDER' => 'SMS (placeholder)', 'OTHER' => 'سایر'] as $v => $l) {
    echo '<option value="' . $v . '">' . $l . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">نتیجه *</label><select class="p1cc-select" name="contact_result" required>';
foreach (['ANSWERED','NO_ANSWER','CALLBACK_REQUESTED','COMPLAINT','SATISFIED','UNSATISFIED','NEEDS_MANAGER','COMPLETED'] as $r) {
    echo '<option value="' . $r . '">' . $r . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">احساس مشتری</label><select class="p1cc-select" name="customer_sentiment">';
echo '<option value="">—</option>';
foreach (['POSITIVE','NEUTRAL','NEGATIVE','ANGRY','VIP_ATTENTION'] as $s) {
    echo '<option value="' . $s . '">' . $s . '</option>';
}
echo '</select></div>';
echo '<div class="p1cc-form-group"><label class="p1cc-label">پیگیری بعدی</label><input class="p1cc-input m360-ltr" type="datetime-local" name="next_followup_at"></div>';
echo '<div class="p1cc-form-group full"><label class="p1cc-label">یادداشت</label><textarea class="p1cc-textarea" name="followup_note" maxlength="2000"></textarea></div>';
echo '</div><button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت تماس</button></form>';
echo '<p style="margin-top:1rem"><a class="p1cc-btn" href="erp-customer-satisfaction.php?followup_schedule_id=' . (int)$scheduleId . '&customer_id=' . crm_h($schedule['customer_id'] ?? '') . '&operation_case_id=' . crm_h($schedule['operation_case_id'] ?? '') . '">فرم رضایت‌سنجی</a></p>';
echo '</div>';

if ($records !== []) {
    echo '<div class="p1cc-card"><h2 class="p6crm-section-title">سوابق تماس</h2><table class="p1cc-table"><thead><tr><th>کانال</th><th>نتیجه</th><th>احساس</th><th>تاریخ</th></tr></thead><tbody>';
    foreach ($records as $rec) {
        echo '<tr><td>' . crm_h($rec['contact_channel'] ?? '') . '</td><td>' . crm_h($rec['contact_result'] ?? '') . '</td><td>' . crm_h($rec['customer_sentiment'] ?? '—') . '</td><td>' . crm_h($rec['created_at'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

if ($surveys !== []) {
    echo '<div class="p1cc-card"><h2 class="p6crm-section-title">رضایت‌سنجی‌ها</h2><table class="p1cc-table"><thead><tr><th>امتیاز کلی</th><th>تاریخ</th></tr></thead><tbody>';
    foreach ($surveys as $s) {
        echo '<tr><td class="m360-num">' . crm_h($s['overall_score'] ?? '') . '</td><td>' . crm_h($s['created_at'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

if ($upsells !== []) {
    echo '<div class="p1cc-card"><h2 class="p6crm-section-title">فرصت‌های فروش مرتبط</h2><table class="p1cc-table"><thead><tr><th>کد</th><th>عنوان</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($upsells as $u) {
        echo '<tr><td class="m360-ltr">' . crm_h($u['opportunity_code'] ?? '') . '</td><td>' . crm_h($u['opportunity_title'] ?? '') . '</td><td>' . crm_h($u['opportunity_status'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

echo '<p><a href="erp-crm-followup-board.php">بازگشت به تابلو</a></p>';
crm_render_foot();
