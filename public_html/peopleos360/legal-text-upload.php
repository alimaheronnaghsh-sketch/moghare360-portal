<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $text = (string)($_POST['rule_text'] ?? '');
    $r = p360_legal_rule_save($conn, ['rule_code'=>'UPLOAD-'.time(),'rule_title'=>(string)($_POST['rule_title']??'متن بارگذاری'),'rule_text'=>$text,'category_id'=>$_POST['category_id']??null,'effective_from'=>gmdate('Y-m-d')], $uid);
    $msg = $r['message'] ?? 'ثبت شد.';
}
p360_layout_start('بارگذاری متن حقوقی', 'legal-rule-center.php');
echo p360_legal_disclaimer_html(); if ($msg) p360_flash($msg, true);
echo '<form class="card" method="post">'.p360_csrf_field().'<label>عنوان</label><input name="rule_title"><label>متن</label><textarea name="rule_text" rows="8" required></textarea><button>بارگذاری</button></form>';
p360_layout_end();