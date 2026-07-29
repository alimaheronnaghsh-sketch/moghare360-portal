<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $id=(int)($_GET['id']??0); $row=inv360_items_get($conn,$id);
inv360_layout_start('مشاهده کالا','items.php');
if(!$row){ echo '<div class="m360-alert m360-alert-err">کالا یافت نشد.</div>'; inv360_layout_end(); exit; }
$bal=inv360_balance_get($conn,$id,null,null);
?>
<p><a href="item-search.php">بازگشت به جستجو</a> | <a href="stock-document-form.php?part_id=<?= $id ?>">عملیات انبار</a></p>
<table class="m360-table">
<tr><th>کد کارگاه</th><td><?= inv360_h((string)($row['WorkshopCode']??'')) ?></td></tr>
<tr><th>کد فنی</th><td><?= inv360_h((string)($row['TechnicalCode']??'')) ?></td></tr>
<tr><th>نام فارسی</th><td><?= inv360_h((string)$row['ItemName']) ?></td></tr>
<tr><th>نام انگلیسی</th><td><?= inv360_h((string)($row['ItemNameEn']??'')) ?></td></tr>
<tr><th>برند</th><td><?= inv360_h((string)($row['ManufacturerBrand']??'')) ?></td></tr>
<tr><th>OEM / Part</th><td><?= inv360_h((string)(($row['OEMCode']??'').' / '.($row['PartNumber']??''))) ?></td></tr>
<tr><th>موجودی فیزیکی</th><td><?= inv360_h((string)($bal['PhysicalQty']??0)) ?></td></tr>
<tr><th>موجودی آزاد</th><td><?= inv360_h((string)inv360_available_qty($bal)) ?></td></tr>
<tr><th>رزرو / قرنطینه</th><td><?= inv360_h((string)(($bal['ReservedQty']??0).' / '.($bal['QuarantineQty']??0))) ?></td></tr>
</table>
<?php inv360_layout_end();