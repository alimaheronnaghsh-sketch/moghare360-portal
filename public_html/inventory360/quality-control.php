<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$items=inv360_items_list($conn,100); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $action=$_POST['qc_action']??'accept';
  $res=inv360_qc_create($conn,[
    'part_id'=>(int)$_POST['part_id'],'qty'=>(float)$_POST['qty'],'warehouse_id'=>((int)$_POST['warehouse_id'])?:null,'location_id'=>((int)$_POST['location_id'])?:null,
    'qty_ok'=>!empty($_POST['qty_ok']),'qty_notes'=>$_POST['qty_notes']??'',
    'quality_ok'=>!empty($_POST['quality_ok']),'quality_notes'=>$_POST['quality_notes']??'',
    'doc_ok'=>!empty($_POST['doc_ok']),'doc_notes'=>$_POST['doc_notes']??'',
    'result'=>$action,'notes'=>$_POST['notes']??''
  ],$uid);
  $ok=!empty($res['ok']); $msg=(string)$res['message'];
}
$rows=inv360_qc_list($conn);
inv360_layout_start('کنترل کیفیت','quality-control.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>انبار<select name="warehouse_id"><option value="0">—</option><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label><input type="checkbox" name="qty_ok" value="1" checked> کنترل تعداد</label>
<label>یادداشت تعداد<input name="qty_notes"></label>
<label><input type="checkbox" name="quality_ok" value="1" checked> کنترل کیفیت</label>
<label>یادداشت کیفیت<input name="quality_notes"></label>
<label><input type="checkbox" name="doc_ok" value="1" checked> کنترل اسناد</label>
<label>یادداشت اسناد<input name="doc_notes"></label>
<label>نتیجه<select name="qc_action"><option value="accept">پذیرش</option><option value="reject">رد</option><option value="quarantine">قرنطینه</option></select></label>
<label>یادداشت<textarea name="notes"></textarea></label>
<button type="submit">ثبت QC</button></form>
<div class="table-scroll"><table class="m360-table"><thead><tr><th>شناسه</th><th>کالا</th><th>نتیجه</th><th>تاریخ</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['QCRecordID'] ?></td><td><?= inv360_h((string)($r['ItemName']??'')) ?></td><td><?= inv360_h((string)$r['ResultCode']) ?></td><td><?= inv360_h((string)$r['CreatedAt']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();
