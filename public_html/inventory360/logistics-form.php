<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){ inv360_csrf_require(); $res=inv360_logistics_create($conn,$_POST,$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; if($ok){header('Location: logistics.php');exit;} }
inv360_layout_start('فرم لجستیک','logistics.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?>
<label>حامل<input name="carrier"></label>
<label>وسیله<input name="vehicle"></label>
<label>راننده<input name="driver_name"></label>
<label>بارنامه<input name="waybill"></label>
<label>مسیر<input name="route_text"></label>
<label>مبدأ<input name="origin" required></label>
<label>مقصد<input name="destination" required></label>
<label>وزن<input type="number" step="0.001" name="weight" value="0"></label>
<label>حجم<input type="number" step="0.001" name="volume" value="0"></label>
<label>تعداد بسته<input type="number" name="package_count" value="1"></label>
<label>هزینه حمل<input type="number" step="0.01" name="freight_cost" value="0"></label>
<label>تاریخ برنامه‌ریزی<input type="date" name="planned_date"></label>
<label>کد رهگیری<input name="tracking_code"></label>
<label>یادداشت تحویل<textarea name="delivery_proof_note"></textarea></label>
<button type="submit">ثبت</button></form>
<?php inv360_layout_end();