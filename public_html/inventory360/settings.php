<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  foreach(['company_name','default_currency','valuation_method','stock_negative_block'] as $k){
    if(isset($_POST[$k])) inv360_setting_set($conn,$k,(string)$_POST[$k],$uid);
  }
  $ok=true; $msg='تنظیمات ذخیره شد.';
}
$get=function($k,$d='') use($conn){ return (string)(inv360_setting_get($conn,$k)??$d); };
inv360_layout_start('تنظیمات','settings.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form"><?= inv360_csrf_field() ?>
<label>نام شرکت<input name="company_name" value="<?= inv360_h($get('company_name','MAHIN360')) ?>"></label>
<label>ارز پیش‌فرض<input name="default_currency" value="<?= inv360_h($get('default_currency','IRR')) ?>"></label>
<label>روش ارزش‌گذاری<select name="valuation_method">
<?php $vm=$get('valuation_method','weighted_average'); foreach(['weighted_average','last_purchase_price','standard_cost','replacement_cost'] as $m): ?>
<option value="<?= $m ?>" <?= $vm===$m?'selected':'' ?>><?= $m ?></option>
<?php endforeach; ?>
</select></label>
<label>ممنوعیت موجودی منفی<select name="stock_negative_block"><option value="1" <?= $get('stock_negative_block','1')==='1'?'selected':'' ?>>فعال</option><option value="0" <?= $get('stock_negative_block','1')==='0'?'selected':'' ?>>غیرفعال</option></select></label>
<button type="submit">ذخیره</button></form>
<p class="hint">این تنظیمات فقط Inventory360 standalone هستند و به ERP متصل نیستند.</p>
<?php inv360_layout_end();