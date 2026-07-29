<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
$partPref=(int)($_GET['part_id']??0);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $action=$_POST['action']??'create';
  if($action==='create'){
    $res=inv360_create_document($conn,[
      'prefix'=>'STK','doc_type'=>$_POST['doc_type'],
      'source_warehouse_id'=>((int)$_POST['source_warehouse_id'])?:null,
      'source_location_id'=>((int)$_POST['source_location_id'])?:null,
      'target_warehouse_id'=>((int)$_POST['target_warehouse_id'])?:null,
      'target_location_id'=>((int)$_POST['target_location_id'])?:null,
      'reason'=>$_POST['reason']??null,'notes'=>$_POST['notes']??null,
    ],$uid);
    $ok=!empty($res['ok']); $msg=(string)$res['message'];
    if($ok){
      inv360_add_document_line($conn,(int)$res['document_id'],(int)$_POST['part_id'],(float)$_POST['qty'], (float)($_POST['unit_cost']??0) ?: null);
      if(!empty($_POST['post_now'])){
        $p=inv360_post_document($conn,(int)$res['document_id'],$uid);
        $ok=!empty($p['ok']); $msg=(string)$p['message'];
      }
      if($ok){ header('Location: stock-document-view.php?id='.(int)$res['document_id']); exit; }
    }
  }
}
$wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn); $items=inv360_items_list($conn,200);
inv360_layout_start('ثبت سند انبار','stock-documents.php');
inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?><input type="hidden" name="action" value="create">
<label>نوع سند<select name="doc_type">
<option value="purchase_receipt">رسید خرید</option>
<option value="opening_receipt">رسید اول دوره</option>
<option value="transfer_receipt">رسید انتقالی</option>
<option value="return_from_consumption">برگشت از مصرف</option>
<option value="customer_return_receipt">برگشت از مشتری</option>
<option value="consumption_issue">حواله مصرف</option>
<option value="sales_issue">حواله فروش</option>
<option value="scrap_issue">حواله ضایعات</option>
<option value="consignment_issue">حواله امانی</option>
<option value="direct_delivery">تحویل مستقیم</option>
<option value="supplier_return_issue">مرجوعی به تأمین‌کننده</option>
<option value="transfer">انتقال بین انبار</option>
<option value="bin_move">جابه‌جایی قفسه</option>
<option value="reserve">رزرو کالا</option>
<option value="release_reserve">آزادسازی رزرو</option>
<option value="adjustment_increase">تعدیل افزایش</option>
<option value="adjustment_decrease">تعدیل کاهش</option>
</select></label>
<label>کالا<select name="part_id" required><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>" <?= $partPref===(int)$it['PartID']?'selected':'' ?>><?= inv360_h($it['ItemName'].' / '.($it['TechnicalCode']??'')) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" min="0.001" name="qty" required></label>
<label>بهای واحد<input type="number" step="0.0001" name="unit_cost" value="0"></label>
<label>انبار مبدأ<select name="source_warehouse_id"><option value="0">—</option><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان مبدأ<select name="source_location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label>انبار مقصد<select name="target_warehouse_id"><option value="0">—</option><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان مقصد<select name="target_location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<label style="grid-column:1/-1">دلیل (برای تعدیل الزامی)<input name="reason"></label>
<label><input type="checkbox" name="post_now" value="1" checked> ثبت قطعی فوری</label>
<button type="submit">ثبت سند</button>
</form>
<?php inv360_layout_end();