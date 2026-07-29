<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db();
$rows=inv360_items_list($conn,150);
inv360_layout_start('کالاها','items.php');
echo '<p><a class="btn" href="item-form.php">کالای جدید</a> <a class="btn secondary" href="item-search.php">جستجو</a></p>';
echo '<div class="table-scroll"><table class="m360-table"><thead><tr><th>شناسه</th><th>کد کارگاه</th><th>کد فنی</th><th>نام</th><th>برند</th><th>موجودی</th><th></th></tr></thead><tbody>';
foreach($rows as $r){
  echo '<tr><td>'.(int)$r['PartID'].'</td><td>'.inv360_h((string)($r['WorkshopCode']??'')).'</td><td>'.inv360_h((string)($r['TechnicalCode']??'')).'</td><td>'.inv360_h((string)$r['ItemName']).'</td><td>'.inv360_h((string)($r['ManufacturerBrand']??'')).'</td><td>'.inv360_h((string)($r['Quantity']??0)).'</td><td><a href="item-view.php?id='.(int)$r['PartID'].'">مشاهده</a></td></tr>';
}
echo '</tbody></table></div>';
inv360_layout_end();