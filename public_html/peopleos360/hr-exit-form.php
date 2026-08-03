<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-identity-date.php';
require_once __DIR__ . '/includes/p360-hr-jalali-ui.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'دسترسی مجاز نیست.';
    exit;
}

$contractId = (int)($_GET['contract_id'] ?? $_POST['contract_id'] ?? 0);
$reminderId = (int)($_GET['reminder_id'] ?? $_POST['reminder_id'] ?? 0);
$c = p360hr_contract_get($contractId);
if ($c === null) {
    echo 'قرارداد یافت نشد.';
    exit;
}
$emp = p360hr_employee_by_id((int)$c['employee_id']);
$msg = null;
$ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_exit', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $actor = erp_auth_current_user_id() ?? 0;
        if (($_POST['action'] ?? '') === 'confirm_exit') {
            $res = p360hr_confirm_exit_case((int)($_POST['exit_id'] ?? 0), $actor);
        } else {
            $last = p360hr_parse_posted_jalali_sql('last_work_date', true);
            if (!$last['ok']) {
                $res = ['ok' => false, 'message' => (string)$last['message']];
            } else {
                $data = $_POST;
                $data['last_work_date'] = $last['ymd'];
                $data['reminder_id'] = $reminderId;
                $res = p360hr_open_exit_case($contractId, $actor, $data);
            }
        }
        $ok = !empty($res['ok']);
        $msg = (string)($res['message'] ?? '');
    }
}

$csrf = erp_csrf_create_token('p360hr_exit');
p360hr_layout_start('عدم تمدید و ثبت خروج');
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}
echo '<p class="p360hr-warn">باز کردن این فرم به‌تنهایی وضعیت همکاری را تغییر نمی‌دهد. تأیید نهایی جداگانه و حسابرسی‌شده لازم است.</p>';
echo '<p>پرسنل: <b>' . p360hr_h(p360hr_employee_full_name($emp ?? [])) . '</b> / قرارداد #' . $contractId . '</p>';
echo '<form method="post" class="m360-card">';
echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
echo '<input type="hidden" name="contract_id" value="' . $contractId . '">';
echo '<input type="hidden" name="reminder_id" value="' . $reminderId . '">';
echo '<div class="p360hr-meta">';
echo '<label>نوع خروج<br><input name="exit_type" required placeholder="عدم تمدید / استعفا / ..."></label>';
echo '<label>علت<br><input name="reason_fa" required></label>';
echo '<label>وضعیت تسویه<br><input name="settlement_status"></label>';
echo '<label>وضعیت ضمانت<br><input name="guarantee_status"></label>';
echo '<label>وضعیت قرارداد<br><input name="contract_status" value="ENDING"></label>';
echo '<label>وضعیت حساب کاربری<br><input name="account_status" value="PENDING"></label>';
echo '<label><input type="checkbox" name="assets_returned" value="1"> تحویل اموال</label>';
echo '</div>';
p360hr_jalali_date_field('last_work_date', null, 'تاریخ آخرین روز همکاری', ['required' => true, 'allow_clear' => false]);
echo '<label>توضیحات<br><textarea name="notes_fa" rows="3" style="width:100%"></textarea></label>';
echo '<p><button class="m360-btn" type="submit">ثبت پیش‌نویس خروج</button></p>';
echo '</form>';

$exits = p360hr_rows('SELECT TOP 5 * FROM dbo.p360_hr_exit_cases WHERE contract_id=? ORDER BY exit_id DESC', [$contractId]);
foreach ($exits as $ex) {
    echo '<section class="m360-card"><p>پیش‌نویس خروج #' . (int)$ex['exit_id'] . ' — تأیید: ' . (((int)$ex['confirmed'] === 1) ? 'بله' : 'خیر') . '</p>';
    if ((int)$ex['confirmed'] !== 1) {
        echo '<form method="post"><input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
        echo '<input type="hidden" name="contract_id" value="' . $contractId . '">';
        echo '<input type="hidden" name="exit_id" value="' . (int)$ex['exit_id'] . '">';
        echo '<input type="hidden" name="action" value="confirm_exit">';
        echo '<button class="m360-btn" type="submit">تأیید نهایی خروج (کنترل‌شده)</button></form>';
    }
    echo '</section>';
}
p360hr_layout_end();
