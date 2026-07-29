<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    p360_exec($conn, 'INSERT INTO dbo.p360_manpower_requests (request_no, headcount, reason_text, created_by) VALUES (?,?,?,?)', [$_POST['request_no']??('MR-'.time()), (int)($_POST['headcount']??1), $_POST['reason_text']??null, $uid]);
}
$rows = p360_manpower_list($conn);
p360_layout_start('درخواست نیرo', 'recruitment.php');
p360_table($rows, ['request_no'=>'شماره','headcount'=>'تعداد','request_status'=>'وضعیت']);
p360_layout_end();