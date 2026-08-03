<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
if ($emp === null) {
    p360hr_layout_start('درخواست‌های پرسنلی');
    echo '<p>پرونده متصل نیست.</p>';
    p360hr_layout_end();
    exit;
}
$eid = (int)($emp['employee_id'] ?? 0);
$conn = p360hr_odbc();
$msg = null;
$ok = false;

$types = [];
$ts = @odbc_exec($conn, 'SELECT request_type_code, title_fa FROM dbo.p360_hr_request_types WHERE is_active=1 ORDER BY sort_order');
if ($ts) {
    while ($r = odbc_fetch_array($ts)) {
        $types[] = $r;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!function_exists('erp_csrf_validate_token') || !erp_csrf_validate_token('p360hr_request', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $type = trim((string)($_POST['request_type_code'] ?? ''));
        $body = trim((string)($_POST['request_body'] ?? ''));
        if ($type === '' || $body === '') {
            $msg = 'نوع درخواست و توضیحات الزامی است.';
        } else {
            $reqNo = 'HR-' . gmdate('YmdHis') . '-' . $eid;
            $ins = @odbc_prepare($conn, 'INSERT INTO dbo.p360_employee_requests (employee_id, request_type, request_body, request_status, request_type_code, request_no, submitted_at) VALUES (?, ?, ?, N\'SUBMITTED\', ?, ?, SYSUTCDATETIME())');
            if ($ins && @odbc_execute($ins, [$eid, $type, $body, $type, $reqNo])) {
                $ok = true;
                $msg = 'درخواست ثبت شد.';
            } else {
                $msg = 'ثبت درخواست ناموفق بود.';
            }
        }
    }
}

p360hr_layout_start('درخواست‌های پرسنلی');
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}
$csrf = erp_csrf_create_token('p360hr_request');
echo '<form class="m360-card m360-form" method="post" style="max-width:560px">';
echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
echo '<label>نوع درخواست</label><select name="request_type_code" required>';
foreach ($types as $t) {
    $code = (string)($t['request_type_code'] ?? $t['REQUEST_TYPE_CODE'] ?? '');
    $title = (string)($t['title_fa'] ?? $t['TITLE_FA'] ?? $code);
    echo '<option value="' . p360hr_h($code) . '">' . p360hr_h($title) . '</option>';
}
echo '</select>';
echo '<label>توضیحات</label><textarea name="request_body" rows="5" required></textarea>';
echo '<button class="m360-btn" type="submit">ثبت درخواست</button></form>';

echo '<h2>درخواست‌های من</h2>';
echo '<table class="m360-table"><thead><tr><th>شماره</th><th>نوع</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>';
$rs = @odbc_prepare($conn, 'SELECT TOP 50 request_no, request_type_code, request_status, created_at FROM dbo.p360_employee_requests WHERE employee_id=? ORDER BY id DESC');
if ($rs && @odbc_execute($rs, [$eid])) {
    $any = false;
    while ($r = odbc_fetch_array($rs)) {
        $any = true;
        echo '<tr><td>' . p360hr_h((string)($r['request_no'] ?? '')) . '</td><td>' . p360hr_h((string)($r['request_type_code'] ?? '')) . '</td><td>' . p360hr_h((string)($r['request_status'] ?? '')) . '</td><td>' . p360hr_h((string)($r['created_at'] ?? '')) . '</td></tr>';
    }
    if (!$any) {
        echo '<tr><td colspan="4" class="m360-empty-state">موردی نیست.</td></tr>';
    }
}
echo '</tbody></table>';
p360hr_layout_end();
