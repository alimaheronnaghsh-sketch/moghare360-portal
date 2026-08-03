<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-contract-engine.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
if ($emp === null && !p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'پرونده متصل نیست.';
    exit;
}

$contractId = (int)($_GET['contract_id'] ?? 0);
$msg = null;
$ok = false;
$devOtp = null;

if ($contractId > 0) {
    $c = p360hr_contract_get($contractId);
    if ($c === null) {
        http_response_code(404);
        echo 'قرارداد یافت نشد.';
        exit;
    }
    p360hr_assert_own_employee((int)$c['employee_id']);
} else {
    $c = null;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $c) {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_my_contract', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $actor = erp_auth_current_user_id() ?? 0;
        if ($action === 'accept') {
            $res = p360hr_accept_contract($contractId, $actor, (string)($_SERVER['REMOTE_ADDR'] ?? ''), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        } elseif ($action === 'issue_otp') {
            $res = p360hr_otp_issue($contractId);
            if (!empty($res['otp_dev'])) {
                $devOtp = (string)$res['otp_dev'];
            }
        } elseif ($action === 'verify_otp') {
            $res = p360hr_otp_verify($contractId, (string)($_POST['otp_code'] ?? ''));
        } elseif ($action === 'sign') {
            $res = p360hr_save_signature_png($contractId, 'employee', (string)($_POST['signature_data'] ?? ''));
        } else {
            $res = ['ok' => false, 'message' => 'عملیات نامعتبر.'];
        }
        $ok = !empty($res['ok']);
        $msg = (string)($res['message'] ?? '');
        $c = p360hr_contract_get($contractId);
    }
}

p360hr_layout_start('قراردادهای من');

if ($contractId < 1) {
    $eid = (int)($emp['employee_id'] ?? 0);
    $rows = p360hr_rows('SELECT contract_id, personnel_code, contract_type, stage_code, contract_version, is_locked, start_date, end_date FROM dbo.p360_hr_contracts WHERE employee_id=? ORDER BY contract_id DESC', [$eid]);
    echo '<table class="m360-table"><thead><tr><th>شناسه</th><th>نوع</th><th>مرحله</th><th>نسخه</th><th>عملیات</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr><td>' . (int)$r['contract_id'] . '</td><td>' . p360hr_h(p360hr_contract_type_fa((string)$r['contract_type'])) . '</td>';
        echo '<td>' . p360hr_h(p360hr_stage_fa((string)$r['stage_code'])) . '</td><td>' . (int)$r['contract_version'] . '</td>';
        echo '<td><a href="my-contracts.php?contract_id=' . (int)$r['contract_id'] . '">مشاهده</a>';
        if ((int)$r['is_locked'] === 1) {
            echo ' | <a href="hr-contract-pdf.php?contract_id=' . (int)$r['contract_id'] . '">PDF</a>';
        }
        echo '</td></tr>';
    }
    if ($rows === []) {
        echo '<tr><td colspan="5">قراردادی ثبت نشده است.</td></tr>';
    }
    echo '</tbody></table>';
    p360hr_layout_end();
    exit;
}

if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}
if ($devOtp !== null) {
    echo '<div class="m360-alert">کد UAT محلی: ' . p360hr_h($devOtp) . '</div>';
}

$csrf = erp_csrf_create_token('p360hr_my_contract');
echo '<div class="p360hr-meta">';
echo '<div><b>مرحله</b>' . p360hr_h(p360hr_stage_fa((string)$c['stage_code'])) . '</div>';
echo '<div><b>نسخه</b>' . (int)$c['contract_version'] . '</div>';
echo '<div><b>نوع</b>' . p360hr_h(p360hr_contract_type_fa((string)$c['contract_type'])) . '</div>';
echo '</div>';
echo '<section class="m360-card"><pre style="white-space:pre-wrap">' . p360hr_h(p360hr_load_rendered_body($c)) . '</pre></section>';

if ((int)($c['is_locked'] ?? 0) !== 1 && in_array(strtoupper((string)$c['stage_code']), ['PRESENTED', 'ACCEPTED', 'OTP_VERIFIED', 'EMPLOYEE_SIGNED', 'ADMIN_REVIEW'], true)) {
    echo '<section class="m360-card"><p>' . p360hr_h(P360HR_ACCEPT_TEXT) . '</p>';
    echo '<form method="post"><input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '"><input type="hidden" name="action" value="accept">';
    echo '<label><input type="checkbox" required> می‌پذیرم</label> <button class="m360-btn" type="submit">پذیرش</button></form>';
    echo '<form method="post" style="display:inline"><input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '"><input type="hidden" name="action" value="issue_otp">';
    echo '<button class="m360-btn" type="submit">دریافت OTP</button></form>';
    echo '<form method="post" style="margin-top:.5rem;display:flex;gap:.4rem"><input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '"><input type="hidden" name="action" value="verify_otp">';
    echo '<input name="otp_code" pattern="\\d{6}" maxlength="6" placeholder="کد ۶ رقمی" required><button class="m360-btn" type="submit">تأیید OTP</button></form>';
    echo '<canvas id="sig" width="420" height="160" style="border:1px solid #333;touch-action:none;background:#fff;max-width:100%"></canvas>';
    echo '<form method="post" id="sf"><input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '"><input type="hidden" name="action" value="sign"><input type="hidden" name="signature_data" id="sd">';
    echo '<button type="button" onclick="document.getElementById(\'sig\').getContext(\'2d\').clearRect(0,0,420,160)">پاک کردن</button> ';
    echo '<button class="m360-btn" type="submit">ثبت امضا</button></form>';
    echo '<script>(function(){var c=document.getElementById("sig"),x=c.getContext("2d"),d=false;function p(e){var r=c.getBoundingClientRect(),t=e.touches?e.touches[0]:e;return{x:(t.clientX-r.left)*c.width/r.width,y:(t.clientY-r.top)*c.height/r.height};}function s(e){d=true;var a=p(e);x.beginPath();x.moveTo(a.x,a.y);e.preventDefault();}function m(e){if(!d)return;var a=p(e);x.lineTo(a.x,a.y);x.stroke();e.preventDefault();}c.onmousedown=s;c.onmousemove=m;window.onmouseup=function(){d=false;};c.ontouchstart=s;c.ontouchmove=m;c.ontouchend=function(){d=false;};document.getElementById("sf").onsubmit=function(){document.getElementById("sd").value=c.toDataURL("image/png");};})();</script>';
    echo '</section>';
}

if ((int)($c['is_locked'] ?? 0) === 1) {
    echo '<p><a class="m360-btn" href="hr-contract-pdf.php?contract_id=' . $contractId . '">دانلود قرارداد نهایی</a></p>';
}

p360hr_layout_end();
