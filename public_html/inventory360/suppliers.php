<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$rows=inv360_suppliers_list($conn); inv360_layout_start('تأمین‌کنندگان','suppliers.php');
echo '<p><a class="btn" href="supplier-form.php">تأمین‌کننده جدید</a></p><div class="table-scroll"><table class="data-table"><thead><tr><th>کد</th><th>نام</th><th>وضعیت</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td>'.inv360_h($r['SupplierCode']).'</td><td>'.inv360_h($r['SupplierName']).'</td><td>'.inv360_h(inv360_status_fa($r['SupplierStatus'])).'</td></tr>';
echo '</tbody></table></div>'; inv360_layout_end();