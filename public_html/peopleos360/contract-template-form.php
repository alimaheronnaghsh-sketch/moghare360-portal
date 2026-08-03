<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null; $ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_template_save($conn, $_POST, $uid, isset($_POST['id']) ? (int)$_POST['id'] : null);
    $ok = !empty($r['ok']); $msg = $r['message'] ?? '';
}
p360_layout_start('فرم قالب قرارداد', 'contract-templates.php');
p360_flash($msg, $ok);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>کد</label><input name="template_code" required><label>عنوان</label><input name="template_title" required><label>متن (paste)</label><textarea name="template_body" rows="10" required>قرارداد {{employee_name}} — حقوق {{salary}}</textarea><label>id برای ویرایش</label><input name="id"><button>ذخیره</button></form>';
p360_layout_end();