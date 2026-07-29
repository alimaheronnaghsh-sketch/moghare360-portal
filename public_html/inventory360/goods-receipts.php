<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_gr_list($conn); inv360_layout_start('دریافت کالا','goods-receipts.php');
echo '<p><a class="btn" href="goods-receipt-form.php">دریافت جدید</a></p><div class="table-scroll"><table class="m360-table"><thead><tr><th>شماره</th><th>نوع</th><th>وضعیت</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td>'.inv360_h($r['GRNo']).'</td><td>'.inv360_h((string)$r['ReceiptType']).'</td><td>'.inv360_h(inv360_status_fa($r['GRStatus'])).'</td></tr>';
echo '</tbody></table></div>'; inv360_layout_end();