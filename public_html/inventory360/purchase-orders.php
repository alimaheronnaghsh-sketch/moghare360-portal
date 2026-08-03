<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_po_list($conn); inv360_layout_start('سفارش خرید','purchase-orders.php');
echo '<p><a class="btn" href="purchase-order-form.php">PO جدید</a></p><div class="table-scroll"><table class="m360-table"><thead><tr><th>شماره</th><th>تأمین‌کننده</th><th>وضعیت</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td><a href="purchase-order-form.php?id='.(int)$r['PurchaseOrderID'].'">'.inv360_h($r['PONo']).'</a></td><td>'.inv360_h((string)($r['SupplierName']??'')).'</td><td>'.inv360_h(inv360_status_fa($r['POStatus'])).'</td></tr>';
echo '</tbody></table></div>'; inv360_layout_end();