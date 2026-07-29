<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){ inv360_csrf_require(); $res=inv360_supplier_create($conn,$_POST,$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; if($ok){header('Location: suppliers.php');exit;} }
inv360_layout_start('ثبت تأمین‌کننده','suppliers.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>کد<input name="supplier_code" required></label>
<label>نام<input name="supplier_name" required></label>
<label>مخاطب<input name="contact_name"></label>
<label>تلفن<input name="contact_phone"></label>
<label>شرایط پرداخت<input name="payment_terms"></label>
<button type="submit">ذخیره</button></form>
<?php inv360_layout_end();