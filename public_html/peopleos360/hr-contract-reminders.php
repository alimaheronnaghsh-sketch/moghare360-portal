<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-identity-date.php';
require_once __DIR__ . '/includes/p360-hr-jalali-ui.php';
require_once __DIR__ . '/includes/p360-hr-occ-med-manager.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    p360hr_forbidden_page();
}

$msg = null;
$ok = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_reminder', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $actor = erp_auth_current_user_id() ?? 0;
        $action = (string)($_POST['action'] ?? '');
        $cid = (int)($_POST['contract_id'] ?? 0);
        $rid = (int)($_POST['reminder_id'] ?? 0);
        if ($action === 'renew') {
            $res = p360hr_renew_from_contract($cid, $actor);
            $ok = !empty($res['ok']);
            $msg = (string)$res['message'];
            if ($ok) {
                header('Location: hr-contract-register.php?contract_id=' . (int)$res['contract_id'] . '&tab=type');
                exit;
            }
        } elseif ($action === 'seen') {
            p360hr_exec("UPDATE dbo.p360_hr_contract_reminders SET status=N'SEEN', seen_at=SYSUTCDATETIME() WHERE reminder_id=? AND status=N'NEW'", [$rid]);
            $ok = true;
            $msg = 'هشدار به‌عنوان دیده‌شده ثبت شد.';
        } elseif ($action === 'exit') {
            header('Location: hr-exit-form.php?contract_id=' . $cid . '&reminder_id=' . $rid);
            exit;
        }
    }
}

$rows = p360hr_reminders_for_dashboard();
$csrf = erp_csrf_create_token('p360hr_reminder');

p360hr_layout_start('هشدارهای تمدید قرارداد', 'قرارداد پرسنلی در آستانه اتمام است');
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}

if ($rows === []) {
    echo '<div class="p360hr-empty">هشدار فعالی وجود ندارد.</div>';
}

foreach ($rows as $r) {
    $emp = ['first_name' => $r['first_name'] ?? '', 'last_name' => $r['last_name'] ?? '', 'display_name_override' => $r['display_name_override'] ?? ''];
    $mgrRes = p360hr_resolve_direct_manager((int)$r['employee_id']);
    $days = (int)($r['days_remaining'] ?? 0);
    $badgeDays = $days < 0 ? 'p360hr-badge-danger' : ($days <= 7 ? 'p360hr-badge-danger' : 'p360hr-badge-warn');
    $st = strtoupper((string)($r['status'] ?? ''));
    $badgeSt = $st === 'OVERDUE' ? 'p360hr-badge-danger' : ($st === 'ACTION_REQUIRED' ? 'p360hr-badge-warn' : 'p360hr-badge-info');

    echo '<article class="p360hr-reminder">';
    echo '<div class="p360hr-reminder-head"><div class="who">';
    echo '<strong>' . p360hr_h(p360hr_employee_full_name($emp)) . '</strong>';
    echo '<span>کد پرسنلی: ' . p360hr_h((string)$r['employee_code']) . '</span>';
    echo '</div><div class="p360hr-reminder-badges">';
    echo '<span class="p360hr-badge ' . $badgeSt . '">' . p360hr_h(p360hr_reminder_status_fa((string)$r['status'])) . '</span>';
    echo '<span class="p360hr-badge ' . $badgeDays . '">' . p360hr_h((string)$days) . ' روز باقی‌مانده</span>';
    echo '</div></div>';

    echo '<div class="p360hr-reminder-body">';
    echo '<div class="field"><span class="lbl">عنوان شغل</span><span class="val">' . p360hr_h((string)($r['job_title'] ?? '')) . '</span></div>';
    if ($mgrRes['ok']) {
        $mgrEmp = p360hr_employee_by_id((int)$mgrRes['manager_employee_id']);
        echo '<div class="field"><span class="lbl">مدیر مستقیم</span><span class="val">' . p360hr_h($mgrEmp ? p360hr_employee_full_name($mgrEmp) : '—') . '</span></div>';
    } else {
        echo '<div class="field"><span class="lbl">مدیر مستقیم</span><span class="val" style="color:#fde68a">' . p360hr_h((string)$mgrRes['message']) . '</span></div>';
    }
    echo '<div class="field"><span class="lbl">نوع قرارداد</span><span class="val">' . p360hr_h((string)($r['contract_type'] ?? '')) . '</span></div>';
    echo '<div class="field"><span class="lbl">تاریخ شروع</span><span class="val">' . p360hr_h(p360hr_date_jalali((string)($r['start_date'] ?? ''))) . '</span></div>';
    echo '<div class="field"><span class="lbl">تاریخ پایان</span><span class="val">' . p360hr_h(p360hr_date_jalali((string)$r['contract_end_date'])) . '</span></div>';
    echo '<div class="field"><span class="lbl">وضعیت قرارداد</span><span class="val">' . p360hr_h(p360hr_stage_fa((string)($r['stage_code'] ?? ''))) . '</span></div>';
    echo '</div>';

    echo '<form method="post" class="p360hr-reminder-foot">';
    echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
    echo '<input type="hidden" name="contract_id" value="' . (int)$r['contract_id'] . '">';
    echo '<input type="hidden" name="reminder_id" value="' . (int)$r['reminder_id'] . '">';
    echo '<a class="m360-btn m360-btn-secondary" href="hr-contract-register.php?contract_id=' . (int)$r['contract_id'] . '&personnel_code=' . rawurlencode((string)$r['employee_code']) . '">مشاهده قرارداد</a>';
    echo '<button class="m360-btn m360-btn-primary" name="action" value="renew" type="submit">تمدید قرارداد</button>';
    echo '<button class="m360-btn p360hr-btn-exit" name="action" value="exit" type="submit">عدم تمدید و ثبت خروج</button>';
    echo '<button class="m360-btn m360-btn-secondary" name="action" value="seen" type="submit">ثبت تصمیم بعدی / دیده‌شدن</button>';
    echo '</form></article>';
}

p360hr_layout_end();
