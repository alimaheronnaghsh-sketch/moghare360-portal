<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $partId=(int)$_POST['part_id']; $counted=(float)$_POST['counted_qty']; $reason=trim((string)$_POST['reason']);
  if($reason===''){ $msg='دلیل انبارگردانی الزامی است.'; }
  else {
    $system=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(physical_qty),0) FROM dbo.inv360_stock_balances WHERE item_id=?',[$partId])??0);
    $variance=$counted-$system;
    inv360_exec($conn,'INSERT INTO dbo.inv360_stock_counts (item_id, warehouse_id, location_id, system_qty, counted_qty, variance_qty, reason_text, count_status, created_by) VALUES (?,?,?,?,?,?,?,N\'approved\',?)',
      [$partId,((int)$_POST['warehouse_id'])?:null,((int)$_POST['location_id'])?:null,$system,$counted,$variance,$reason.' | system='.$system.' | variance='.$variance,$uid]);
    if(abs($variance)>0.0001){
      $type=$variance>0?'adjustment_increase':'adjustment_decrease';
      $doc=inv360_create_document($conn,['prefix'=>'CNT','doc_type'=>$type,'target_warehouse_id'=>((int)$_POST['warehouse_id'])?:null,'target_location_id'=>((int)$_POST['location_id'])?:null,'source_warehouse_id'=>((int)$_POST['warehouse_id'])?:null,'source_location_id'=>((int)$_POST['location_id'])?:null,'reason'=>$reason],$uid);
      inv360_add_document_line($conn,(int)$doc['document_id'],$partId,abs($variance));
      $p=inv360_post_document($conn,(int)$doc['document_id'],$uid); $ok=!empty($p['ok']); $msg=$ok?('انبارگردانی و تعدیل ثبت شد. مغایرت: '.$variance):(string)$p['message'];
    } else { $ok=true; $msg='انبارگردانی بدون مغایرت ثبت شد.'; }
  }
}
$items=inv360_items_list($conn,100); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn);
inv360_layout_start('فرم انبارگردانی','stock-counts.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>مقدار شمارش‌شده<input type="number" step="0.001" name="counted_qty" required></label>
<label>انبار<select name="warehouse_id"><option value="0">—</option><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label style="grid-column:1/-1">دلیل الزامی<input name="reason" required></label>
<button type="submit">ثبت شمارش و تعدیل</button></form>
<?php inv360_layout_end();