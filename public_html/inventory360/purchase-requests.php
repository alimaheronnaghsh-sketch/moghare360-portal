<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_pr_list($conn); inv360_layout_start('درخواست خرید','purchase-requests.php');
echo '<p><a class="btn" href="purchase-request-form.php">درخواست جدید</a></p><div class="table-scroll"><table class="m360-table"><thead><tr><th>شماره</th><th>قلم</th><th>تعداد</th><th>وضعیت</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td>'.inv360_h($r['PRNo']).'</td><td>'.inv360_h((string)($r['ItemText']??'')).'</td><td>'.inv360_h((string)$r['Qty']).'</td><td>'.inv360_h(inv360_status_fa($r['PRStatus'])).'</td></tr>';
echo '</tbody></table></div>'; inv360_layout_end();