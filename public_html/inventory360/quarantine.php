<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db(); $uid=(int)inv360_current_user()['user_id']; $msg=''; $ok=false;
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
  inv360_csrf_require();
  $res=inv360_qc_release_quarantine($conn,(int)$_POST['part_id'],(float)$_POST['qty'],((int)$_POST['warehouse_id'])?:null,((int)$_POST['location_id'])?:null,$uid);
  $ok=!empty($res['ok']); $msg=(string)$res['message'];
}
$rows=inv360_rows($conn,'SELECT b.*, p.ItemName, p.WorkshopCode, p.TechnicalCode FROM dbo.Inv360StockBalances b LEFT JOIN dbo.Parts p ON p.PartID=b.PartID WHERE b.QuarantineQty>0 ORDER BY b.BalanceID DESC',[]);
$items=inv360_items_list($conn,100); $wh=inv360_warehouses_list($conn); $loc=inv360_locations_list($conn);
inv360_layout_start('قرنطینه','quarantine.php'); inv360_flash_render($msg,$ok);
?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>کالا</th><th>کد کارگاه</th><th>کد فنی</th><th>قرنطینه</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= inv360_h((string)($r['ItemName']??'')) ?></td><td><?= inv360_h((string)($r['WorkshopCode']??'')) ?></td><td><?= inv360_h((string)($r['TechnicalCode']??'')) ?></td><td><?= inv360_h((string)$r['QuarantineQty']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<form method="post" class="inv-form" style="margin-top:1rem"><?= inv360_csrf_field() ?>
<label>کالا<select name="part_id"><?php foreach($items as $it): ?><option value="<?= (int)$it['PartID'] ?>"><?= inv360_h($it['ItemName']) ?></option><?php endforeach; ?></select></label>
<label>تعداد آزادسازی<input type="number" step="0.001" name="qty" required></label>
<label>انبار<select name="warehouse_id"><option value="0">—</option><?php foreach($wh as $w): ?><option value="<?= (int)$w['WarehouseID'] ?>"><?= inv360_h($w['WarehouseName']) ?></option><?php endforeach; ?></select></label>
<label>مکان<select name="location_id"><option value="0">—</option><?php foreach($loc as $l): ?><option value="<?= (int)$l['LocationID'] ?>"><?= inv360_h($l['LocationCode']) ?></option><?php endforeach; ?></select></label>
<button type="submit">آزادسازی از قرنطینه</button></form>
<?php inv360_layout_end();