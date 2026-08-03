<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'دسترسی مجاز نیست.';
    exit;
}

$conn = p360hr_odbc();
$msg = null;
$ok = false;
$focusId = (int)($_GET['employee_id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_admin_reset', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $targetUser = (int)($_POST['target_user_id'] ?? 0);
        $targetEmp = (int)($_POST['target_employee_id'] ?? 0);
        $reason = (string)($_POST['reason_fa'] ?? '');
        $actor = erp_auth_current_user_id() ?? 0;
        $res = p360hr_admin_reset_password($actor, $targetUser, $targetEmp > 0 ? $targetEmp : null, $reason);
        $ok = !empty($res['ok']);
        $msg = (string)($res['message'] ?? '');
        $focusId = $targetEmp;
    }
}

p360hr_layout_start('مدیریت پرسنل');
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}

echo '<p><a class="m360-btn" href="hr-personnel-form.php">فرم اطلاعات پرسنلی</a> ';
echo '<a class="m360-btn" href="hr-contract-register.php">ثبت قرارداد پرسنلی</a> ';
echo '<a class="m360-btn" href="hr-employer-profile.php">پروفایل کارفرما</a></p>';

echo '<table class="m360-table"><thead><tr><th>کد</th><th>نام</th><th>واحد</th><th>کاربر</th><th>عملیات</th></tr></thead><tbody>';
$rs = @odbc_exec($conn, 'SELECT TOP 200 e.employee_id, e.employee_code, e.first_name, e.last_name, e.unit_name, e.core_user_id, u.username, u.must_change_password
FROM dbo.p360_employees e
LEFT JOIN dbo.core_users u ON u.user_id = e.core_user_id
WHERE e.hire_year_jalali IS NOT NULL
ORDER BY e.employee_id');
if ($rs) {
    while ($r = odbc_fetch_array($rs)) {
        $eid = (int)($r['employee_id'] ?? 0);
        $uid = (int)($r['core_user_id'] ?? 0);
        $pcode = (string)($r['employee_code'] ?? '');
        echo '<tr><td>' . p360hr_h($pcode) . '</td>';
        echo '<td>' . p360hr_h(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) . '</td>';
        echo '<td>' . p360hr_h((string)($r['unit_name'] ?? '')) . '</td>';
        echo '<td>' . p360hr_h((string)($r['username'] ?? '')) . ( ((int)($r['must_change_password'] ?? 0) === 1) ? ' (نیاز به تغییر رمز)' : '') . '</td><td>';
        echo '<a href="hr-personnel-form.php?personnel_code=' . rawurlencode($pcode) . '">پرونده</a> | ';
        echo '<a href="hr-contract-register.php?personnel_code=' . rawurlencode($pcode) . '">قرارداد</a> | ';
        if ($uid > 0) {
            $csrf = erp_csrf_create_token('p360hr_admin_reset');
            echo '<form method="post" style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">';
            echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
            echo '<input type="hidden" name="target_user_id" value="' . $uid . '">';
            echo '<input type="hidden" name="target_employee_id" value="' . $eid . '">';
            echo '<input name="reason_fa" placeholder="دلیل فارسی بازنشانی" required style="min-width:180px">';
            echo '<button class="m360-btn" type="submit">بازنشانی رمز عبور</button></form>';
        }
        echo '</td></tr>';
    }
}
echo '</tbody></table>';
p360hr_layout_end();
