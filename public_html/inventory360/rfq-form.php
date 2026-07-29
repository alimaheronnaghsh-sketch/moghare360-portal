<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$sup=inv360_suppliers_list($conn); $items=inv360_items_list($conn,100);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){ inv360_csrf_require(); $res=inv360_rfq_create($conn,$_POST,$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; if($ok){header('Location: rfq.php');exit;} }
inv360_layout_start('فرم RFQ','rfq.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?>
<label>تأمین‌کننده<select name="supplier_id"><?php foreach($sup as $s): ?><option value="<?= (int)$s['SupplierID'] ?>"><?= inv360_h($s['SupplierName']) ?></option><?php endforeach; ?></select></label>
<label>کالا<select name="part_id"><option value="0">—</option><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>قلم متنی<input name="item_text"></label>
<label>قیمت واحد<input type="number" step="0.01" name="unit_price" required></label>
<label>تخفیف<input type="number" step="0.01" name="discount" value="0"></label>
<label>مالیات<input type="number" step="0.01" name="tax" value="0"></label>
<label>حمل<input type="number" step="0.01" name="freight" value="0"></label>
<label>زمان تحویل (روز)<input type="number" name="delivery_days" value="7"></label>
<label>شرایط پرداخت<input name="payment_terms"></label>
<label>درجه کیفیت (1-10)<input type="number" step="0.1" name="quality_grade" value="8"></label>
<label>گارانتی<input name="warranty"></label>
<label>ارز<input name="currency" value="IRR"></label>
<label>پاسخ‌گویی (1-10)<input type="number" step="0.1" name="responsiveness" value="8"></label>
<label>نرخ مغایرت %<input type="number" step="0.1" name="mismatch_rate" value="2"></label>
<label>نرخ برگشت %<input type="number" step="0.1" name="return_rate" value="1"></label>
<label>معتبر تا<input type="date" name="valid_until"></label>
<button type="submit">ثبت RFQ</button></form>
<?php inv360_layout_end();