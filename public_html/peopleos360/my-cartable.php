<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';
require_once __DIR__ . '/includes/p360-hr-occ-med-manager.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
$user = p360hr_current_core_user_row();
$uid = (int)($user['user_id'] ?? $user['USER_ID'] ?? 0);

p360hr_layout_start('میز کار من', 'خلاصه پرونده، درخواست‌ها و خدمات پرسنلی');
if ($emp === null) {
    echo '<div class="m360-alert m360-alert-err">پرونده پرسنلی به حساب شما متصل نشده است.</div>';
    p360hr_layout_end();
    exit;
}

$eid = (int)($emp['employee_id'] ?? $emp['EMPLOYEE_ID'] ?? 0);
$code = (string)($emp['employee_code'] ?? $emp['EMPLOYEE_CODE'] ?? '');
$fn = p360hr_employee_full_name([
    'first_name' => (string)($emp['first_name'] ?? $emp['FIRST_NAME'] ?? ''),
    'last_name' => (string)($emp['last_name'] ?? $emp['LAST_NAME'] ?? ''),
    'display_name_override' => (string)($emp['display_name_override'] ?? $emp['DISPLAY_NAME_OVERRIDE'] ?? ''),
]);
$fp = p360hr_fingerprint_code($eid);
$life = (string)($emp['lifecycle_state'] ?? $emp['LIFECYCLE_STATE'] ?? '');
$unit = (string)($emp['unit_name'] ?? $emp['UNIT_NAME'] ?? '');
$job = (string)($emp['job_title'] ?? $emp['JOB_TITLE'] ?? '');
$hy = (string)($emp['hire_year_jalali'] ?? $emp['HIRE_YEAR_JALALI'] ?? '');
$uname = (string)($user['username'] ?? $user['USERNAME'] ?? '');
$acctStatus = p360hr_must_change_password($user) ? 'نیازمند تغییر رمز' : 'فعال';

$isManager = $uid > 0 && p360hr_user_is_direct_manager_of_anyone($uid);

// Compact counters from existing data only (soft-fail to 0)
$pendingReq = 0;
$activeContracts = 0;
$newAlerts = 0;
$lastSlipLabel = '—';
$conn = p360hr_odbc();
$st = @odbc_prepare($conn, "SELECT COUNT(*) AS c FROM dbo.p360_employee_requests WHERE employee_id=? AND request_status IN (N'SUBMITTED', N'PENDING', N'IN_REVIEW')");
if ($st && @odbc_execute($st, [$eid]) && ($r = odbc_fetch_array($st))) {
    $pendingReq = (int)($r['c'] ?? $r['C'] ?? 0);
}
$st = @odbc_prepare($conn, 'SELECT COUNT(*) AS c FROM dbo.p360_hr_contracts WHERE employee_id=? AND is_locked=1');
if ($st && @odbc_execute($st, [$eid]) && ($r = odbc_fetch_array($st))) {
    $activeContracts = (int)($r['c'] ?? $r['C'] ?? 0);
}
$st = @odbc_prepare($conn, "SELECT TOP 1 period_id, slip_status FROM dbo.p360_payroll_slips WHERE employee_id=? AND slip_status IN (N'VISIBLE_TO_EMPLOYEE', N'APPROVED', N'LOCKED', N'PAID', N'calculated', N'CALCULATED') ORDER BY id DESC");
if ($st && @odbc_execute($st, [$eid]) && ($r = odbc_fetch_array($st))) {
    $lastSlipLabel = trim((string)($r['period_id'] ?? $r['PERIOD_ID'] ?? ''));
    if ($lastSlipLabel === '') {
        $lastSlipLabel = 'ثبت‌شده';
    }
}

$mgrAlerts = [];
$mgrPendingRec = 0;
if ($isManager) {
    $msg = null;
    $ok = false;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['mgr_action'] ?? '') === 'recommend') {
        $token = (string)($_POST['erp_csrf_token'] ?? '');
        if (!erp_csrf_validate_token('p360hr_mgr_rec', $token)) {
            $msg = 'توکن امنیتی نامعتبر است.';
        } else {
            $res = p360hr_manager_recommend((int)($_POST['reminder_id'] ?? 0), $uid, (string)($_POST['recommendation_code'] ?? ''), (string)($_POST['recommendation_note'] ?? ''));
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
        }
    }
    $mgrAlerts = p360hr_manager_cartable_reminders($uid);
    $newAlerts = count($mgrAlerts);
    foreach ($mgrAlerts as $a) {
        $rec = strtoupper((string)($a['recommendation_code'] ?? ''));
        if ($rec === '' || $rec === 'NO_RECOMMENDATION') {
            $mgrPendingRec++;
        }
    }
}

echo '<section class="p360hr-section"><div class="p360hr-section-card">';
echo '<h2 class="p360hr-section-title">خلاصه پرونده پرسنلی</h2>';
echo '<div class="p360hr-info-grid">';
$cells = [
    'نام و نام خانوادگی' => $fn,
    'کد پرسنلی رسمی' => $code,
    'عنوان شغل' => $job !== '' ? $job : '—',
    'واحد' => $unit !== '' ? $unit : '—',
    'وضعیت همکاری' => $life !== '' ? $life : '—',
    'سال استخدام' => $hy !== '' ? $hy : '—',
    'کد دستگاه انگشت‌زن' => $fp,
    'وضعیت حساب کاربری' => $acctStatus . ($uname !== '' ? ' (' . $uname . ')' : ''),
];
foreach ($cells as $lbl => $val) {
    echo '<div class="p360hr-info-item"><span class="lbl">' . p360hr_h($lbl) . '</span><span class="val">' . p360hr_h($val) . '</span></div>';
}
echo '</div></div></section>';

echo '<section class="p360hr-section">';
echo '<h2 class="p360hr-section-title">نمای وضعیت</h2>';
echo '<div class="p360hr-kpi-grid">';
echo '<div class="p360hr-kpi"><span class="lbl">درخواست‌های در انتظار</span><span class="num">' . p360hr_h((string)$pendingReq) . '</span></div>';
echo '<div class="p360hr-kpi"><span class="lbl">قراردادهای فعال</span><span class="num">' . p360hr_h((string)$activeContracts) . '</span></div>';
echo '<div class="p360hr-kpi"><span class="lbl">هشدارهای جدید</span><span class="num">' . p360hr_h((string)$newAlerts) . '</span></div>';
echo '<div class="p360hr-kpi"><span class="lbl">آخرین فیش حقوقی</span><span class="num" style="font-size:1.05rem">' . p360hr_h($lastSlipLabel) . '</span></div>';
echo '</div></section>';

echo '<section class="p360hr-section">';
echo '<h2 class="p360hr-section-title">خدمات پرسنلی من</h2>';
echo '<div class="p360hr-action-grid">';
$actions = [
    ['my-dossier.php', 'پر', 'پرونده پرسنلی من', 'مشاهده اطلاعات هویتی، سازمانی و طب کار'],
    ['my-requests.php', 'در', 'درخواست‌های پرسنلی من', 'ثبت و پیگیری درخواست‌های پرسنلی'],
    ['my-attendance.php', 'حض', 'حضور و غیاب من', 'مشاهده کارکرد و تردد ثبت‌شده'],
    ['my-payslips.php', 'فی', 'فیش‌های حقوقی من', 'مشاهده فیش‌های قابل‌نمایش'],
    ['my-contracts.php', 'قر', 'قراردادهای من', 'مشاهده و پذیرش قراردادهای پرسنلی'],
    ['my-benefits.php', 'مز', 'عیدی، سنوات و مزایای من', 'خلاصه عیدی، سنوات و مزایای قراردادی'],
    ['my-password.php', 'رم', 'تغییر رمز عبور', 'به‌روزرسانی امن رمز ورود'],
];
foreach ($actions as $a) {
    echo '<a class="p360hr-action-card" href="' . p360hr_h($a[0]) . '">';
    echo '<span class="p360hr-action-icon" aria-hidden="true">' . p360hr_h($a[1]) . '</span>';
    echo '<h3>' . p360hr_h($a[2]) . '</h3>';
    echo '<p>' . p360hr_h($a[3]) . '</p>';
    echo '</a>';
}
echo '</div></section>';

if ($isManager) {
    echo '<section class="p360hr-section" id="mgr-alerts">';
    echo '<h2 class="p360hr-section-title">هشدارها و اقدامات مدیریتی</h2>';
    echo '<div class="p360hr-mgr-summary">';
    echo '<a href="#mgr-alerts-list"><span class="lbl">هشدار قرارداد در آستانه اتمام</span><span class="num">' . (int)$newAlerts . '</span></a>';
    echo '<a href="#mgr-alerts-list"><span class="lbl">نیازمند توصیه مدیر</span><span class="num">' . (int)$mgrPendingRec . '</span></a>';
    echo '<a href="#mgr-alerts-list"><span class="lbl">مشاهده هشدارهای مدیریتی</span><span class="num" style="font-size:1rem">رفتن به فهرست</span></a>';
    echo '</div>';

    if (isset($msg) && $msg !== null) {
        echo '<div class="m360-alert ' . (!empty($ok) ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
    }
    $csrf = erp_csrf_create_token('p360hr_mgr_rec');
    echo '<div id="mgr-alerts-list">';
    if ($mgrAlerts === []) {
        echo '<div class="p360hr-empty">هشدار فعالی برای پرسنل زیرمجموعه شما نیست.</div>';
    }
    foreach ($mgrAlerts as $a) {
        $person = ['first_name' => $a['first_name'] ?? '', 'last_name' => $a['last_name'] ?? '', 'display_name_override' => $a['display_name_override'] ?? ''];
        $days = (int)($a['days_remaining'] ?? 0);
        $badgeDays = $days <= 7 ? 'p360hr-badge-danger' : 'p360hr-badge-warn';
        echo '<article class="p360hr-reminder">';
        echo '<div class="p360hr-reminder-head"><div class="who">';
        echo '<strong>قرارداد پرسنلی در آستانه اتمام است</strong>';
        echo '<span>' . p360hr_h(p360hr_employee_full_name($person)) . ' — ' . p360hr_h((string)$a['employee_code']) . '</span>';
        echo '</div><div class="p360hr-reminder-badges">';
        echo '<span class="p360hr-badge p360hr-badge-info">' . p360hr_h(p360hr_reminder_status_fa((string)$a['status'])) . '</span>';
        echo '<span class="p360hr-badge ' . $badgeDays . '">' . p360hr_h((string)$days) . ' روز باقی‌مانده</span>';
        echo '</div></div>';
        echo '<div class="p360hr-reminder-body">';
        echo '<div class="field"><span class="lbl">عنوان شغل</span><span class="val">' . p360hr_h((string)($a['contract_job_title'] ?? $a['job_title'] ?? '')) . '</span></div>';
        echo '<div class="field"><span class="lbl">تاریخ پایان قرارداد</span><span class="val">' . p360hr_h(p360hr_date_jalali((string)$a['contract_end_date'])) . '</span></div>';
        echo '<div class="field"><span class="lbl">وضعیت توصیه</span><span class="val">' . p360hr_h(p360hr_recommendation_fa((string)($a['recommendation_code'] ?? 'NO_RECOMMENDATION'))) . '</span></div>';
        echo '</div>';
        echo '<div class="p360hr-reminder-foot">';
        echo '<a class="m360-btn m360-btn-secondary" href="hr-manager-contract-view.php?contract_id=' . (int)$a['contract_id'] . '&reminder_id=' . (int)$a['reminder_id'] . '">مشاهده قرارداد</a>';
        echo '</div>';
        echo '<form method="post" class="p360hr-section-card" style="margin:0;border:0;border-radius:0;box-shadow:none">';
        echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
        echo '<input type="hidden" name="mgr_action" value="recommend">';
        echo '<input type="hidden" name="reminder_id" value="' . (int)$a['reminder_id'] . '">';
        echo '<div class="p360hr-form-grid">';
        echo '<label>ثبت نظر / توصیه مدیر<select name="recommendation_code">';
        foreach (['RENEW_RECOMMENDED' => 'توصیه به تمدید', 'NON_RENEW_RECOMMENDED' => 'توصیه به عدم تمدید', 'REVIEW_REQUIRED' => 'نیازمند بررسی', 'NO_RECOMMENDATION' => 'بدون توصیه'] as $k => $fa) {
            $sel = ((string)($a['recommendation_code'] ?? '') === $k) ? ' selected' : '';
            echo '<option value="' . $k . '"' . $sel . '>' . $fa . '</option>';
        }
        echo '</select></label>';
        echo '<label class="span-2">توضیح<textarea name="recommendation_note" rows="2">' . p360hr_h((string)($a['recommendation_note'] ?? '')) . '</textarea></label>';
        echo '</div>';
        echo '<p class="p360hr-warn">مدیر نمی‌تواند تمدید را نهایی کند، همکاری را خاتمه دهد، مزد یا تاریخ قرارداد را تغییر دهد.</p>';
        echo '<div class="p360hr-form-actions"><button class="m360-btn m360-btn-primary" type="submit">ثبت نظر مدیر</button></div>';
        echo '</form></article>';
    }
    echo '</div></section>';
}

p360hr_layout_end();
