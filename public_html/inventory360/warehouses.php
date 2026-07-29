<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
$rows=inv360_warehouses_list($conn);
inv360_layout_start('انبارها','warehouses.php');
inv360_flash_render($msg,$ok);
?>
<p><a class="btn" href="warehouse-form.php">انبار جدید</a></p>
<div class="table-scroll"><table class="data-table"><thead><tr><th>شناسه</th><th>کد</th><th>نام</th><th>نوع</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['WarehouseID'] ?></td><td><?= inv360_h($r['WarehouseCode']) ?></td><td><?= inv360_h($r['WarehouseName']) ?></td><td><?= inv360_h(inv360_status_fa((string)($r['WarehouseType']??'main'))) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();