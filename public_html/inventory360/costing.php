<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$method=$_GET['method']??'weighted_average';
$rows=inv360_costing_overview($conn,$method);
inv360_layout_start('بهای تمام‌شده','costing.php');
?>
<form method="get" class="inv-form" style="margin-bottom:1rem">
<label>روش ارزش‌گذاری<select name="method" onchange="this.form.submit()">
<option value="weighted_average" <?= $method==='weighted_average'?'selected':'' ?>>میانگین موزون</option>
<option value="last_purchase_price" <?= $method==='last_purchase_price'?'selected':'' ?>>آخرین خرید</option>
<option value="standard_cost" <?= $method==='standard_cost'?'selected':'' ?>>بهای استاندارد</option>
<option value="replacement_cost" <?= $method==='replacement_cost'?'selected':'' ?>>بهای جایگزینی</option>
<option value="fifo" <?= $method==='fifo'?'selected':'' ?>>FIFO (جای‌نگهدار)</option>
<option value="contract_price" <?= $method==='contract_price'?'selected':'' ?>>قیمت قرارداد (جای‌نگهدار)</option>
</select></label>
</form>
<?php if($method==='fifo'||$method==='contract_price'): ?><p class="hint">زیرساخت آماده؛ اجرای کامل در فاز بعد</p><?php endif; ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>کالا</th><th>موجودی</th><th>بهای واحد</th><th>ارزش</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= inv360_h((string)($r['ItemName']??'')) ?></td><td><?= inv360_h((string)($r['Qty']??0)) ?></td><td><?= inv360_h((string)($r['UnitCost']??0)) ?></td><td><?= inv360_h((string)($r['Value']??0)) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p><a class="btn" href="landed-cost.php">محاسبه بهای تمام‌شده وارداتی</a></p>
<?php inv360_layout_end();