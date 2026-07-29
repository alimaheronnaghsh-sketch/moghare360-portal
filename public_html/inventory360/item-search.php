<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db();
$q=trim((string)($_GET['q']??''));
$rows=$q!==''?inv360_search_items($conn,$q,80):[];
inv360_layout_start('جستجوی کالا','item-search.php');
?>
<form method="get" class="m360-form">
<label style="grid-column:1/-1">جستجو با کد فنی / کد کارگاه / نام قطعه / OEM<input name="q" value="<?= inv360_h($q) ?>" placeholder="مثال: فیلتر یا BMW-TECH-01" autofocus></label>
<button type="submit">جستجو</button>
</form>
<?php if($q!=='' && !$rows): ?><div class="m360-alert m360-alert-err">نتیجه‌ای یافت نشد.</div><?php endif; ?>
<div class="table-scroll"><table class="m360-table"><thead><tr>
<th>کد کارگاه</th><th>کد فنی</th><th>نام قطعه</th><th>برند</th><th>موجودی کل</th><th>آزاد</th><th>رزرو</th><th>وضعیت</th><th></th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?= inv360_h((string)($r['WorkshopCode']??$r['InternalCode']??'')) ?></td>
<td><?= inv360_h((string)($r['TechnicalCode']??'')) ?></td>
<td><?= inv360_h((string)($r['ItemName']??'')) ?></td>
<td><?= inv360_h((string)($r['ManufacturerBrand']??'')) ?></td>
<td><?= inv360_h((string)($r['PhysicalQty']??0)) ?></td>
<td><?= inv360_h((string)($r['AvailableQty']??0)) ?></td>
<td><?= inv360_h((string)($r['ReservedQty']??0)) ?></td>
<td><?= inv360_h(inv360_status_fa((string)($r['ItemStatus']??'active'))) ?></td>
<td><a href="item-view.php?id=<?= (int)$r['PartID'] ?>">مشاهده</a> | <a href="stock-document-form.php?part_id=<?= (int)$r['PartID'] ?>">عملیات انبار</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php inv360_layout_end();