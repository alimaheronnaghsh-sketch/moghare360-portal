<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db(); $uid = (int)(p360_current_user()['user_id'] ?? 0);
$msg = null; $ok = true; $cats = p360_legal_categories($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_legal_rule_save($conn, $_POST, $uid);
    $ok = !empty($r['ok']); $msg = $r['message'] ?? '';
}
p360_layout_start('فرم قانون', 'legal-rule-center.php');
echo p360_legal_disclaimer_html(); p360_flash($msg, $ok);
echo '<form class="card" method="post">'.p360_csrf_field();
echo '<label>دسته</label><select name="category_id"><option value="">—</option>';
foreach ($cats as $c) echo '<option value="'.(int)$c['id'].'">'.p360_h($c['category_name']).'</option>';
echo '</select><label>کد</label><input name="rule_code" required><label>عنوان</label><input name="rule_title" required><label>متن</label><textarea name="rule_text" rows="4"></textarea><label>JSON مقدار</label><textarea name="rule_value_json" rows="2"></textarea><label>effective_from</label><input name="effective_from" placeholder="YYYY-MM-DD"><button>ذخیره</button></form>';
p360_layout_end();