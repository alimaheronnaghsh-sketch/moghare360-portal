<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_logistics_list($conn); inv360_layout_start('لجستیک','logistics.php');
echo '<p><a class="btn" href="logistics-form.php">درخواست حمل جدید</a></p><div class="table-scroll"><table class="data-table"><thead><tr><th>کد</th><th>مسیر</th><th>وضعیت</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td>'.inv360_h($r['RequestNo']).'</td><td>'.inv360_h((string)$r['OriginText']).' → '.inv360_h((string)$r['DestinationText']).'</td><td>'.inv360_h(inv360_status_fa($r['LogStatus'])).'</td></tr>';
echo '</tbody></table></div>'; inv360_layout_end();