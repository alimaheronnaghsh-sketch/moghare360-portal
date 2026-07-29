<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $res=inv360_item_save($conn, $_POST, (int)$user['user_id'], ((int)($_POST['part_id']??0))?:null);
  $ok=!empty($res['ok']); $msg=(string)$res['message'];
  if($ok && !empty($res['part_id'])){ header('Location: item-view.php?id='.(int)$res['part_id']); exit; }
}
inv360_layout_start('ثبت / ویرایش کالا','items.php');
inv360_flash_render($msg,$ok);
?>
<form method="post" class="m360-form">
<?= inv360_csrf_field() ?>
<label>کد کارگاه<input name="workshop_code" required></label>
<label>کد فنی<input name="technical_code" required></label>
<label>کد کالا<input name="item_code"></label>
<label>نام فارسی<input name="item_name_fa" required></label>
<label>نام انگلیسی<input name="item_name_en"></label>
<label>نام متداول<input name="common_name"></label>
<label>برند<input name="brand"></label>
<label>سازنده<input name="manufacturer"></label>
<label>کشور<input name="country"></label>
<label>Part Number<input name="part_number"></label>
<label>OEM<input name="oem_code"></label>
<label>کدهای جایگزین<input name="alternative_codes"></label>
<label>بارکد<input name="barcode"></label>
<label>نوع کالا<select name="item_type"><option value="spare_part">قطعه یدکی</option><option value="consumable">مصرفی</option><option value="tool">ابزار</option><option value="asset">دارایی</option><option value="raw_material">مواد اولیه</option><option value="finished_good">آماده</option></select></label>
<label>زیرگروه<input name="subcategory"></label>
<label>خانواده<input name="family"></label>
<label>حداقل موجودی<input name="min_stock" type="number" step="0.001" value="0"></label>
<label>حداکثر موجودی<input name="max_stock" type="number" step="0.001"></label>
<label>نقطه سفارش<input name="reorder_point" type="number" step="0.001" value="0"></label>
<label>وضعیت<select name="item_status"><option value="active">فعال</option><option value="inactive">غیرفعال</option><option value="stopped_purchase">توقف خرید</option><option value="blocked_sale">توقف فروش</option><option value="obsolete">منسوخ</option></select></label>
<label style="grid-column:1/-1">توضیح<textarea name="description" rows="2"></textarea></label>
<button type="submit">ذخیره کالا</button>
</form>
<?php inv360_layout_end();