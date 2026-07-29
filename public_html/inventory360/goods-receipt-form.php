<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$pos=inv360_po_list($conn); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn); $items=inv360_items_list($conn,100);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $poId=(int)$_POST['po_id']; $partId=(int)$_POST['part_id']; $qty=(float)$_POST['qty']; $whId=(int)$_POST['warehouse_id']; $locId=(int)$_POST['location_id']; $unitCost=(float)$_POST['unit_cost'];
  $res=inv360_gr_create($conn,['receipt_type'=>$_POST['receipt_type']??'from_purchase','po_id'=>$poId?:null,'warehouse_id'=>$whId?:null,'location_id'=>$locId?:null,'notes'=>$_POST['notes']??''],$uid);
  if(!empty($res['ok'])){
    $grId=(int)$res['gr_id'];
    inv360_gr_add_line($conn,$grId,['po_line_id'=>((int)$_POST['po_line_id'])?:null,'part_id'=>$partId,'qty'=>$qty,'unit_cost'=>$unitCost]);
    // post stock document receipt
    $doc=inv360_create_document($conn,['prefix'=>'GR','doc_type'=>'purchase_receipt','target_warehouse_id'=>$whId?:null,'target_location_id'=>$locId?:null,'reference_type'=>'goods_receipt','reference_no'=>$res['gr_no']??'','reason'=>$_POST['notes']??'دریافت'],$uid);
    inv360_add_document_line($conn,(int)$doc['document_id'],$partId,$qty,(float)$unitCost);
    $p=inv360_post_document($conn,(int)$doc['document_id'],$uid);
    if(!empty($p['ok'])){
      if($poId) inv360_po_receive_partial($conn,$poId,$partId,$qty,$uid);
      inv360_exec($conn,'UPDATE dbo.Inv360GoodsReceipts SET GRStatus=N\'posted\' WHERE GoodsReceiptID=?',[$grId]);
      $ok=true; $msg='دریافت ثبت و موجودی افزایش یافت.';
    } else { $msg=(string)$p['message']; }
  } else { $msg=(string)$res['message']; }
}
inv360_layout_start('فرم دریافت کالا','goods-receipts.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>نوع<select name="receipt_type"><option value="from_purchase">از خرید</option><option value="opening">افتتاحیه</option><option value="transfer">انتقالی</option><option value="return">برگشتی</option></select></label>
<label>PO<select name="po_id"><option value="0">—</option><?php foreach($pos as $p): ?><option value="<?= (int)$p['PurchaseOrderID'] ?>"><?= inv360_h($p['PONo']) ?></option><?php endforeach; ?></select></label>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>بهای واحد<input type="number" step="0.01" name="unit_cost" value="0"></label>
<label>انبار<select name="warehouse_id"><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label>یادداشت<input name="notes"></label>
<button type="submit">ثبت دریافت و پست موجودی</button></form>
<?php inv360_layout_end();