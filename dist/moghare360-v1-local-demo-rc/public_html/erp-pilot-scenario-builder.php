<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

pilot_session_start();
$csrfAction = 'pilot_scenario_create';
$csrfInput = pilot_csrf_input($csrfAction);

$c = false;
$scenarios = [];
$dbWarning = '';
$flash = '';
$csrfReason = '';
if (isset($_GET['ok'])) {
    $flash = pilot_flash((string)$_GET['ok']);
}
if (isset($_GET['dup']) && $_GET['dup'] === '1') {
    $flash = pilot_flash('scenario_dup');
}
if (isset($_GET['err']) && $_GET['err'] === 'csrf') {
    $flash = 'اعتبارسنجی امنیتی ناموفق بود. فرم تازه‌سازی شد؛ لطفاً دوباره ثبت کنید.';
    $csrfReason = pilot_csrf_safe_reason_label($_GET['reason'] ?? '');
}

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('اتصال برقرار نشد.');
    }
    pilot_require_auth($c, 'pilot.view');
    $scenarios = pilot_get_scenarios($c, 10);
} catch (Throwable) {
    $dbWarning = 'لیست سناریوها قابل بارگذاری نیست؛ ثبت سناریوی جدید همچنان از همین فرم امکان‌پذیر است.';
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

pilot_render_head('ساخت سناریوی Pilot');
echo '<div class="p12pl-hero"><h1>ساخت سناریوی Pilot</h1><p>داده Pilot برای اجرای آزمایشی داخلی است — جداول Pilot فقط.</p></div>';
echo '<div class="p12pl-warning-box">داده Pilot برای اجرای آزمایشی داخلی است. هیچ جدول اصلی Phase 1–10 تغییر نمی‌کند.</div>';

if ($dbWarning !== '') {
    echo '<div class="p12pl-flash-warn">' . pilot_h($dbWarning) . '</div>';
}

if ($flash !== '') {
    $cls = str_contains($flash, 'هشدار') || str_contains($flash, 'ناموفق') ? 'p12pl-flash-warn' : 'p12pl-flash-ok';
    echo '<div class="' . $cls . '">' . pilot_h($flash);
    if ($csrfReason !== '') {
        echo '<br><small>کد خطا: ' . pilot_h($csrfReason) . '</small>';
    }
    echo '</div>';
}

echo '<div class="p1cc-card"><form method="post" action="submit-pilot-scenario.php" class="p12pl-form-grid">';
echo $csrfInput;
echo '<div class="p12pl-form-row"><label>نام مشتری *</label><input type="text" name="customer_name" required maxlength="250"></div>';
echo '<div class="p12pl-form-row"><label>موبایل</label><input type="text" name="mobile" maxlength="50" placeholder="09xxxxxxxxx"></div>';
echo '<div class="p12pl-form-row"><label>پلاک</label><input type="text" name="vehicle_plate" maxlength="100"></div>';
echo '<div class="p12pl-form-row"><label>برند / مدل</label><input type="text" name="vehicle_brand_model" maxlength="250"></div>';
echo '<div class="p12pl-form-row"><label>نوع قرارداد</label><select name="contract_type"><option value="">—</option><option value="STANDARD">استاندارد</option><option value="VIP">VIP</option><option value="WARRANTY">گارانتی</option></select></div>';
echo '<div class="p12pl-form-row"><label>حالت مجوز</label><select name="authorization_mode"><option value="">—</option><option value="OWNER_PRESENT">حضور مالک</option><option value="DELEGATE">نماینده</option><option value="PHONE_AUTH">تأیید تلفنی</option></select></div>';
echo '<div class="p12pl-form-row"><label>شرح خدمت JobCard</label><textarea name="jobcard_service_description" maxlength="1500"></textarea></div>';
echo '<div class="p12pl-form-row"><label>نیاز قطعه</label><select name="part_required"><option value="0">خیر</option><option value="1">بله</option></select></div>';
echo '<div class="p12pl-form-row"><label>مبلغ پیش‌نمایش مالی</label><input type="number" name="payment_preview_amount" min="0" step="1000" value="0"></div>';
echo '<div class="p12pl-form-row"><label>پیگیری CRM</label><select name="crm_followup_expected"><option value="0">خیر</option><option value="1">بله</option></select></div>';
echo '<div class="p12pl-form-row"><label>نمونه حضور HR</label><select name="hr_attendance_sample"><option value="0">خیر</option><option value="1">بله</option></select></div>';
echo '<p><button type="submit" class="p1cc-btn p1cc-btn-primary">ثبت سناریوی Pilot</button></p>';
echo '</form></div>';

if ($scenarios !== []) {
    echo '<div class="p1cc-card"><h2 class="p12pl-section-title">آخرین سناریوها</h2><table class="p1cc-table"><thead><tr>';
    echo '<th>کد</th><th>مشتری</th><th>پلاک</th><th>وضعیت</th><th>مشاهده</th></tr></thead><tbody>';
    foreach ($scenarios as $sc) {
        echo '<tr><td class="m360-num">' . pilot_h($sc['scenario_code'] ?? '') . '</td>';
        echo '<td>' . pilot_h($sc['customer_name'] ?? '') . '</td>';
        echo '<td>' . pilot_h($sc['vehicle_plate'] ?? '') . '</td>';
        echo '<td><span class="p12pl-badge ' . pilot_badge_class((string)($sc['scenario_status'] ?? '')) . '">' . pilot_h($sc['scenario_status'] ?? '') . '</span></td>';
        echo '<td><a href="erp-pilot-flow-viewer.php?scenario_id=' . pilot_h($sc['scenario_id'] ?? '') . '">Flow</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

pilot_render_foot();
