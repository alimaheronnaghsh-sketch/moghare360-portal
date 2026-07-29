<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false; $calc=null;
$items=inv360_items_list($conn,100);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $res=inv360_landed_cost_calculate($conn,$_POST,$uid); $ok=!empty($res['ok']); $msg=(string)$res['message']; $calc=$res['result']??null;
}
$rows=inv360_landed_cost_list($conn);
inv360_layout_start('بهای تمام‌شده وارداتی','landed-cost.php'); inv360_flash_render($msg,$ok);
?>
<form method="post" class="inv-form"><?= inv360_csrf_field() ?>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد<input type="number" step="0.001" name="qty" value="1" required></label>
<label>قیمت خرید<input type="number" step="0.01" name="purchase_price" required></label>
<label>حمل خارجی<input type="number" step="0.01" name="foreign_freight" value="0"></label>
<label>بیمه<input type="number" step="0.01" name="insurance" value="0"></label>
<label>کارمزد بانک<input type="number" step="0.01" name="bank_fee" value="0"></label>
<label>بازرسی<input type="number" step="0.01" name="inspection" value="0"></label>
<label>گمرک<input type="number" step="0.01" name="customs" value="0"></label>
<label>عوارض<input type="number" step="0.01" name="duties" value="0"></label>
<label>انبارداری<input type="number" step="0.01" name="warehousing" value="0"></label>
<label>ترخیص<input type="number" step="0.01" name="clearance" value="0"></label>
<label>حمل داخلی<input type="number" step="0.01" name="inland_freight" value="0"></label>
<label>کارگزار<input type="number" step="0.01" name="broker_fee" value="0"></label>
<label>سایر<input type="number" step="0.01" name="other_direct" value="0"></label>
<label>روش تخصیص<select name="allocation_method"><option value="by_value">بر اساس ارزش</option><option value="by_weight">وزن</option><option value="by_volume">حجم</option><option value="by_quantity">تعداد</option><option value="manual_pct">درصد دستی</option></select></label>
<label>وزن<input type="number" step="0.001" name="weight" value="1"></label>
<label>حجم<input type="number" step="0.001" name="volume" value="1"></label>
<label>درصد دستی<input type="number" step="0.01" name="manual_pct" value="100"></label>
<button type="submit">محاسبه و ذخیره</button></form>
<?php if($calc): ?><div class="panel"><p>بهای تمام‌شده کل: <strong><?= inv360_h((string)$calc['total_landed']) ?></strong></p><p>بهای واحد: <strong><?= inv360_h((string)$calc['unit_landed']) ?></strong></p></div><?php endif; ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>شناسه</th><th>کالا</th><th>کل</th><th>واحد</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['LandedCostID'] ?></td><td><?= inv360_h((string)($r['ItemName']??'')) ?></td><td><?= inv360_h((string)$r['TotalLanded']) ?></td><td><?= inv360_h((string)$r['UnitLanded']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();