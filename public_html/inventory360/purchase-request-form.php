<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
$items=inv360_items_list($conn,100);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){ inv360_csrf_require(); $partId=(int)$_POST['part_id']; $stock=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(physical_qty),0) FROM dbo.inv360_stock_balances WHERE item_id=?',[$partId])??0); $res=inv360_pr_create($conn,array_merge($_POST,['current_stock'=>$stock]),$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; if($ok){header('Location: purchase-requests.php');exit;} }
inv360_layout_start('فرم درخواست خرید','purchase-requests.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?>
<label>درخواست‌کننده<input name="requester" required></label>
<label>واحد<input name="department"></label>
<label>تاریخ نیاز<input type="date" name="needed_date"></label>
<label>فوریت<select name="urgency"><option value="normal">عادی</option><option value="high">بالا</option><option value="emergency">اضطراری</option></select></label>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>شرح<input name="item_text"></label>
<label>تعداد<input type="number" step="0.001" name="qty" required></label>
<label>دلیل<textarea name="reason" required></textarea></label>
<label>تأمین‌کنندگان پیشنهادی<input name="suggested_suppliers"></label>
<button type="submit">ثبت</button></form>
<?php inv360_layout_end();