<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$items=inv360_items_list($conn,100); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $doc=inv360_create_document($conn,['prefix'=>'CRT','doc_type'=>'customer_return_receipt','target_warehouse_id'=>((int)$_POST['warehouse_id'])?:null,'target_location_id'=>((int)$_POST['location_id'])?:null,'reason'=>$_POST['reason']??'برگشت مشتری'],$uid);
  inv360_add_document_line($conn,(int)$doc['document_id'],(int)$_POST['part_id'],(float)$_POST['qty']);
  $p=inv360_post_document($conn,(int)$doc['document_id'],$uid); $ok=!empty($p['ok']); $msg=$ok?'برگشت مشتری ثبت شد.':(string)$p['message'];
}
inv360_layout_start('برگشت از مشتری','customer-returns.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>انبار<select name="warehouse_id"><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label>دلیل<input name="reason"></label>
<button type="submit">ثبت برگشت</button></form>
<p class="hint">زیرساخت آماده؛ اتصال به ماژول مشتری ERP در فاز بعد.</p>
<?php inv360_layout_end();