<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$items=inv360_items_list($conn,100); $sup=inv360_suppliers_list($conn); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $partId=(int)$_POST['part_id']; $qty=(float)$_POST['qty']; $reason=trim((string)$_POST['reason']);
  if($reason===''){ $msg='دلیل مرجوعی الزامی است.'; }
  else {
    $doc=inv360_create_document($conn,['prefix'=>'SRT','doc_type'=>'supplier_return','source_warehouse_id'=>((int)$_POST['warehouse_id'])?:null,'source_location_id'=>((int)$_POST['location_id'])?:null,'reason'=>$reason,'reference_type'=>'supplier','reference_no'=>$_POST['supplier_id']??''],$uid);
    inv360_add_document_line($conn,(int)$doc['document_id'],$partId,$qty);
    $p=inv360_post_document($conn,(int)$doc['document_id'],$uid);
    if(!empty($p['ok'])){
      inv360_exec($conn,'INSERT INTO dbo.Inv360SupplierReturns (ReturnNo,SupplierID,PartID,Qty,ReasonText,ReturnStatus,CreatedByUserID) VALUES (?,?,?,?,?,N\'posted\',?)',['SR-'.gmdate('YmdHis'),((int)$_POST['supplier_id'])?:null,$partId,$qty,$reason,$uid]);
      $ok=true; $msg='مرجوعی به تأمین‌کننده ثبت شد.';
    } else { $msg=(string)$p['message']; }
  }
}
$rows=inv360_rows($conn,'SELECT TOP 50 r.*, p.ItemName, s.SupplierName FROM dbo.Inv360SupplierReturns r LEFT JOIN dbo.Parts p ON p.PartID=r.PartID LEFT JOIN dbo.Inv360Suppliers s ON s.SupplierID=r.SupplierID ORDER BY r.SupplierReturnID DESC',[]);
inv360_layout_start('مرجوعی به تأمین‌کننده','supplier-returns.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>تأمین‌کننده<select name="supplier_id"><?php foreach($sup as $s): ?><option value="<?= (int)$s['SupplierID'] ?>"><?= inv360_h($s['SupplierName']) ?></option><?php endforeach; ?></select></label>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>انبار<select name="warehouse_id"><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label>دلیل<textarea name="reason" required></textarea></label>
<button type="submit">ثبت مرجوعی</button></form>
<div class="table-scroll"><table class="data-table"><thead><tr><th>شناسه</th><th>تأمین‌کننده</th><th>کالا</th><th>تعداد</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['SupplierReturnID'] ?></td><td><?= inv360_h((string)($r['SupplierName']??'')) ?></td><td><?= inv360_h((string)($r['ItemName']??'')) ?></td><td><?= inv360_h((string)$r['Qty']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();