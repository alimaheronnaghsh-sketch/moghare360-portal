<?php
declare(strict_types=1);

/**
 * Read-only contract summary for direct managers (no finalize / wage / exit).
 */

require_once __DIR__ . '/includes/p360-hr-occ-med-manager.php';

p360hr_require_password_changed_for_cartable();
$user = p360hr_current_core_user_row();
$uid = (int)($user['user_id'] ?? $user['USER_ID'] ?? 0);
$contractId = (int)($_GET['contract_id'] ?? 0);
$reminderId = (int)($_GET['reminder_id'] ?? 0);

if ($uid < 1 || $contractId < 1) {
    p360hr_forbidden_page();
}

$allowed = false;
if (p360hr_can_manage_personnel()) {
    $allowed = true;
} elseif ($reminderId > 0) {
    $rec = p360hr_manager_assert_recipient_access($reminderId, $uid);
    if ($rec !== null && (int)$rec['contract_id'] === $contractId) {
        $allowed = true;
    }
} else {
    $c = p360hr_contract_get($contractId);
    if ($c !== null) {
        $mgr = p360hr_resolve_direct_manager((int)$c['employee_id']);
        if ($mgr['ok'] && (int)$mgr['manager_user_id'] === $uid) {
            $allowed = true;
        }
    }
}

if (!$allowed) {
    p360hr_forbidden_page();
}

$c = p360hr_contract_get($contractId);
if ($c === null) {
    http_response_code(404);
    p360hr_layout_start('قرارداد یافت نشد');
    echo '<div class="p360hr-empty">قرارداد یافت نشد.</div>';
    echo '<p><a class="m360-btn m360-btn-secondary" href="my-cartable.php">بازگشت به میز کار من</a></p>';
    p360hr_layout_end();
    exit;
}
$emp = p360hr_employee_by_id((int)$c['employee_id']);

p360hr_layout_start('خلاصه قرارداد (مدیر مستقیم)', 'نمای فقط‌خواندنی — بدون امکان نهایی‌سازی');
echo '<div class="p360hr-warn">این نمای فقط‌خواندنی است. نهایی‌سازی تمدید، خاتمه همکاری، تغییر مزد و تاریخ‌ها فقط توسط منابع انسانی/مالک انجام می‌شود.</div>';
echo '<section class="p360hr-section"><div class="p360hr-section-card">';
echo '<div class="p360hr-readonly-grid">';
$fields = [
    'نام پرسنل' => $emp ? p360hr_employee_full_name($emp) : '',
    'کد پرسنلی' => (string)($emp['employee_code'] ?? ''),
    'عنوان شغل قرارداد' => (string)($c['contract_job_title'] ?? $c['job_title'] ?? ''),
    'نوع قرارداد' => (string)($c['contract_type'] ?? ''),
    'تاریخ شروع' => p360hr_date_jalali((string)($c['start_date'] ?? '')),
    'تاریخ پایان' => p360hr_date_jalali((string)($c['end_date'] ?? '')),
    'مرحله' => p360hr_stage_fa((string)($c['stage_code'] ?? '')),
];
foreach ($fields as $lbl => $val) {
    echo '<div class="p360hr-readonly"><span class="lbl">' . p360hr_h($lbl) . '</span><span class="val">' . p360hr_h($val) . '</span></div>';
}
echo '</div></div></section>';
echo '<div class="p360hr-form-actions">';
echo '<a class="m360-btn m360-btn-primary" href="my-cartable.php#mgr-alerts">بازگشت به میز کار من</a>';
echo '</div>';
p360hr_layout_end();
