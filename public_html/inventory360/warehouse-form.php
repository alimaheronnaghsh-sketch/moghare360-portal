<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
if(($_SERVER["REQUEST_METHOD"]??"")==="POST"){inv360_csrf_require();$res=inv360_warehouse_create($conn,trim((string)$_POST["code"]),trim((string)$_POST["name"]),trim((string)$_POST["type"]),$uid);$ok=!empty($res["ok"]);$msg=(string)$res["message"]; if($ok){header("Location: warehouses.php");exit;}}
inv360_layout_start('ثبت انبار','warehouses.php');
inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>کد انبار<input name="code" required></label>
<label>نام انبار<input name="name" required></label>
<label>نوع<select name="type"><option value="main">اصلی</option><option value="parts">قطعات</option><option value="tools">ابزار</option><option value="quarantine">قرنطینه</option><option value="returns">مرجوعی</option><option value="scrap">ضایعات</option><option value="consignment">امانی</option><option value="project">پروژه</option><option value="mobile">سیار</option><option value="in_transit">در مسیر</option></select></label>
<button type="submit">ذخیره</button></form>
<?php inv360_layout_end();