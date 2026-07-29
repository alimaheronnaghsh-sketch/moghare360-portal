<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_assets_list($conn); inv360_layout_start('ابزار و اموال','tools-assets.php');
echo '<p><a class="btn" href="tool-issue-return.php">تحویل / برگشت ابزار</a></p><div class="table-scroll"><table class="data-table"><thead><tr><th>کد</th><th>نام</th><th>سریال</th><th>وضعیت</th><th>تحویل‌گیرنده</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td>'.inv360_h($r['AssetCode']).'</td><td>'.inv360_h($r['AssetName']).'</td><td>'.inv360_h((string)$r['SerialNo']).'</td><td>'.inv360_h(inv360_status_fa($r['HealthStatus'])).'</td><td>'.inv360_h((string)$r['AssignedPerson']).'</td></tr>';
echo '</tbody></table></div>';
echo '<form method="post" action="tool-issue-return.php" class="inv-form" style="margin-top:1rem"><input type="hidden" name="mode" value="create">'.inv360_csrf_field();
echo '<label>کد ابزار<input name="asset_code" required></label><label>نام<input name="asset_name" required></label><label>سریال<input name="serial_no"></label><button type="submit">ثبت ابزار جدید</button></form>';
inv360_layout_end();