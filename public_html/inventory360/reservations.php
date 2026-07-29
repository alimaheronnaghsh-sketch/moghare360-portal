<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $act=$_POST['action']??'';
  if($act==='reserve'){
    $doc=inv360_create_document($conn,['prefix'=>'RSV','doc_type'=>'reserve','source_warehouse_id'=>((int)$_POST['warehouse_id'])?:null,'source_location_id'=>((int)$_POST['location_id'])?:null,'reason'=>$_POST['purpose']??'manual'],$uid);
    if(!empty($doc['ok'])){ inv360_add_document_line($conn,(int)$doc['document_id'],(int)$_POST['part_id'],(float)$_POST['qty']); $p=inv360_post_document($conn,(int)$doc['document_id'],$uid); $ok=!empty($p['ok']); $msg=(string)$p['message'];
      if($ok){ inv360_exec($conn,'INSERT INTO dbo.inv360_reservations (item_id,warehouse_id,location_id,qty,purpose_code,purpose_ref,res_status,created_by) VALUES (?,?,?,?,?,?,N\'active\',?)',[(int)$_POST['part_id'],((int)$_POST['warehouse_id'])?:null,((int)$_POST['location_id'])?:null,(float)$_POST['qty'],$_POST['purpose']??'manual',$_POST['purpose_ref']??null,$uid]); }
    } else { $msg=(string)$doc['message']; }
  } elseif($act==='release'){
    $rid=(int)$_POST['reservation_id']; $row=inv360_one($conn,'SELECT TOP 1 reservation_id AS ReservationID, item_id AS PartID, warehouse_id AS WarehouseID, location_id AS LocationID, qty AS Qty, res_status AS ResStatus FROM dbo.inv360_reservations WHERE reservation_id=? AND res_status=N\'active\'',[$rid]);
    if($row){
      $doc=inv360_create_document($conn,['prefix'=>'REL','doc_type'=>'release_reserve','source_warehouse_id'=>$row['WarehouseID'],'source_location_id'=>$row['LocationID'],'reason'=>'release'],$uid);
      inv360_add_document_line($conn,(int)$doc['document_id'],(int)$row['PartID'],(float)$row['Qty']);
      $p=inv360_post_document($conn,(int)$doc['document_id'],$uid); $ok=!empty($p['ok']); $msg=(string)$p['message'];
      if($ok) inv360_exec($conn,'UPDATE dbo.inv360_reservations SET res_status=N\'released\', released_at=SYSUTCDATETIME() WHERE reservation_id=?',[$rid]);
    } else { $msg='رزرو فعال یافت نشد.'; }
  }
}
$items=inv360_items_list($conn,100); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn);
$rows=inv360_rows($conn,'SELECT TOP 50 r.reservation_id AS ReservationID, r.qty AS Qty, r.res_status AS ResStatus, p.item_name_fa AS ItemName FROM dbo.inv360_reservations r LEFT JOIN dbo.inv360_items p ON p.item_id=r.item_id ORDER BY r.reservation_id DESC',[]);
inv360_layout_start('رزروها','reservations.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?><input type="hidden" name="action" value="reserve">
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>انبار<select name="warehouse_id"><option value="0">—</option><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label>هدف<select name="purpose"><option value="manual">دستی</option><option value="future_jobcard">ارجاع آینده JobCard (فقط متن)</option><option value="sales">فروش</option><option value="project">پروژه</option></select></label>
<label>مرجع متنی<input name="purpose_ref" placeholder="بدون اتصال به ERP"></label>
<button type="submit">رزرو</button></form>
<div class="table-scroll"><table class="data-table"><thead><tr><th>شناسه</th><th>کالا</th><th>تعداد</th><th>وضعیت</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['ReservationID'] ?></td><td><?= inv360_h((string)($r['ItemName']??'')) ?></td><td><?= inv360_h((string)$r['Qty']) ?></td><td><?= inv360_h(inv360_status_fa($r['ResStatus'])) ?></td>
<td><?php if($r['ResStatus']==='active'): ?><form method="post" style="display:inline"><?= inv360_csrf_field() ?><input type="hidden" name="action" value="release"><input type="hidden" name="reservation_id" value="<?= (int)$r['ReservationID'] ?>"><button type="submit">آزادسازی</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();