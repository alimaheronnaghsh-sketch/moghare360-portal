<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login();
$conn=inv360_db(); $user=inv360_current_user(); $uid=(int)$user['user_id']; $msg=''; $ok=false;
$rows=inv360_stock_balances_list($conn);
inv360_layout_start('موجودی','stock-balances.php');
inv360_flash_render($msg,$ok);
?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>کالا</th><th>کد کارگاه</th><th>فیزیکی</th><th>رزرو</th><th>قرنطینه</th><th>مسدود</th><th>آزاد</th></tr></thead><tbody>
<?php foreach($rows as $r): $avail=inv360_available_qty($r); ?>
<tr><td><?= inv360_h((string)($r['ItemName']??$r['PartID'])) ?></td><td><?= inv360_h((string)($r['WorkshopCode']??'')) ?></td>
<td><?= inv360_h((string)$r['PhysicalQty']) ?></td><td><?= inv360_h((string)$r['ReservedQty']) ?></td><td><?= inv360_h((string)$r['QuarantineQty']) ?></td><td><?= inv360_h((string)$r['BlockedQty']) ?></td><td><?= inv360_h((string)$avail) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php inv360_layout_end();