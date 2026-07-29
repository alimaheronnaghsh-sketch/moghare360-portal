<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
if(($_SERVER["REQUEST_METHOD"]??"")==="POST"){inv360_csrf_require();$res=inv360_location_create($conn,(int)$_POST["warehouse_id"],trim((string)$_POST["bin"]),trim((string)$_POST["name"]),$uid);$ok=!empty($res["ok"]);$msg=(string)$res["message"];}
$wh=inv360_warehouses_list($conn); $rows=inv360_locations_list($conn);
inv360_layout_start('مکان‌ها / Bin','locations.php');
inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>انبار<select name="warehouse_id" required><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>کد Bin<input name="bin" required></label>
<label>نام<input name="name"></label>
<button type="submit">ثبت Bin</button></form>
<div class="table-scroll"><table class="data-table"><thead><tr><th>شناسه</th><th>انبار</th><th>کد</th><th>Bin</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['LocationID'] ?></td><td><?= (int)$r['WarehouseID'] ?></td><td><?= inv360_h($r['LocationCode']) ?></td><td><?= inv360_h((string)($r['BinCode']??'')) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();