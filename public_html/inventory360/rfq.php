<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_rfq_list($conn); inv360_layout_start('RFQ','rfq.php');
echo '<p><a class="btn" href="rfq-form.php">RFQ جدید</a> <a class="btn secondary" href="rfq.php?compare=1">مقایسه تأمین‌کنندگان</a></p>';
if(!empty($_GET['compare'])){
  $cmp=inv360_rfq_comparison($conn);
  echo '<div class="table-scroll"><table class="m360-table"><thead><tr><th>RFQ</th><th>تأمین‌کننده</th><th>قیمت</th><th>نمره</th></tr></thead><tbody>';
  foreach($cmp as $r) echo '<tr><td>'.inv360_h($r['RFQNo']).'</td><td>'.inv360_h((string)($r['SupplierName']??'')).'</td><td>'.inv360_h((string)$r['UnitPrice']).'</td><td>'.inv360_h((string)$r['Score']).'</td></tr>';
  echo '</tbody></table></div>';
} else {
  echo '<div class="table-scroll"><table class="m360-table"><thead><tr><th>شماره</th><th>قیمت</th><th>نمره</th><th>وضعیت</th></tr></thead><tbody>';
  foreach($rows as $r) echo '<tr><td>'.inv360_h((string)($r['RFQNo']??$r['RfqNo']??'')).'</td><td>'.inv360_h((string)$r['UnitPrice']).'</td><td>'.inv360_h((string)($r['Score']??$r['TotalScore']??'')).'</td><td>'.inv360_h(inv360_status_fa($r['RFQStatus']??'')).'</td></tr>';
  echo '</tbody></table></div>';
}
inv360_layout_end();