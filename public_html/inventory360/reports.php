<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$invValue=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(b.physical_qty * ISNULL(p.standard_cost, ISNULL(p.last_purchase_price,0))),0) FROM dbo.inv360_stock_balances b LEFT JOIN dbo.inv360_items p ON p.item_id=b.item_id',[])??0);
$belowReorder=inv360_rows($conn,'SELECT p.item_id AS PartID, p.item_name_fa AS ItemName, p.reorder_point AS ReorderPoint, ISNULL(SUM(b.physical_qty - b.reserved_qty - b.quarantine_qty - b.blocked_qty),0) AS Avail FROM dbo.inv360_items p LEFT JOIN dbo.inv360_stock_balances b ON b.item_id=p.item_id WHERE ISNULL(p.is_deleted,0)=0 GROUP BY p.item_id,p.item_name_fa,p.reorder_point HAVING ISNULL(p.reorder_point,0)>0 AND ISNULL(SUM(b.physical_qty - b.reserved_qty - b.quarantine_qty - b.blocked_qty),0)<=ISNULL(p.reorder_point,0)',[]);
$overMax=inv360_rows($conn,'SELECT p.item_id AS PartID, p.item_name_fa AS ItemName, p.max_stock AS MaxStock, ISNULL(SUM(b.physical_qty),0) AS Phys FROM dbo.inv360_items p LEFT JOIN dbo.inv360_stock_balances b ON b.item_id=p.item_id WHERE ISNULL(p.is_deleted,0)=0 GROUP BY p.item_id,p.item_name_fa,p.max_stock HAVING ISNULL(p.max_stock,0)>0 AND ISNULL(SUM(b.physical_qty),0)>ISNULL(p.max_stock,0)',[]);
$quarantine=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(quarantine_qty),0) FROM dbo.inv360_stock_balances',[])??0);
$pendingDocs=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_stock_documents WHERE doc_status IN (N\'draft\',N\'submitted\',N\'approved\')',[])??0);
$openPR=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_purchase_requests WHERE pr_status NOT IN (N\'closed\',N\'cancelled\')',[])??0);
$openPO=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_purchase_orders WHERE po_status NOT IN (N\'closed\',N\'cancelled\')',[])??0);
$abc=inv360_rows($conn,'SELECT TOP 20 p.item_name_fa AS ItemName, ISNULL(SUM(b.physical_qty),0) AS Qty, ISNULL(p.standard_cost,ISNULL(p.last_purchase_price,0)) AS UnitCost, ISNULL(SUM(b.physical_qty),0)*ISNULL(p.standard_cost,ISNULL(p.last_purchase_price,0)) AS Val FROM dbo.inv360_items p LEFT JOIN dbo.inv360_stock_balances b ON b.item_id=p.item_id WHERE ISNULL(p.is_deleted,0)=0 GROUP BY p.item_name_fa,p.standard_cost,p.last_purchase_price ORDER BY Val DESC',[]);
$suggest=inv360_rows($conn,'SELECT p.item_id AS PartID,p.item_name_fa AS ItemName,p.workshop_code AS WorkshopCode,p.technical_code AS TechnicalCode,p.reorder_point AS ReorderPoint,ISNULL(SUM(b.physical_qty - b.reserved_qty - b.quarantine_qty - b.blocked_qty),0) AS Avail FROM dbo.inv360_items p LEFT JOIN dbo.inv360_stock_balances b ON b.item_id=p.item_id WHERE ISNULL(p.is_deleted,0)=0 GROUP BY p.item_id,p.item_name_fa,p.workshop_code,p.technical_code,p.reorder_point HAVING ISNULL(p.reorder_point,0)>0 AND ISNULL(SUM(b.physical_qty - b.reserved_qty - b.quarantine_qty - b.blocked_qty),0)<=ISNULL(p.reorder_point,0)',[]);
inv360_layout_start('گزارش‌ها','reports.php');
?>
<div class="cards">
  <div class="card"><h3>ارزش موجودی</h3><p><?= inv360_money_fa($invValue) ?></p></div>
  <div class="card"><h3>زیر نقطه سفارش</h3><p><?= count($belowReorder) ?></p></div>
  <div class="card"><h3>بیش از حداکثر</h3><p><?= count($overMax) ?></p></div>
  <div class="card"><h3>قرنطینه</h3><p><?= inv360_h((string)$quarantine) ?></p></div>
  <div class="card"><h3>اسناد معلق</h3><p><?= $pendingDocs ?></p></div>
  <div class="card"><h3>PR باز</h3><p><?= $openPR ?></p></div>
  <div class="card"><h3>PO باز</h3><p><?= $openPO ?></p></div>
</div>
<h2>پیشنهاد خرید پایه</h2>
<div class="table-scroll"><table class="m360-table"><thead><tr><th>کالا</th><th>کد کارگاه</th><th>آزاد</th><th>نقطه سفارش</th></tr></thead><tbody>
<?php foreach($suggest as $r): ?><tr><td><?= inv360_h($r['ItemName']) ?></td><td><?= inv360_h((string)$r['WorkshopCode']) ?></td><td><?= inv360_h((string)$r['Avail']) ?></td><td><?= inv360_h((string)$r['ReorderPoint']) ?></td></tr><?php endforeach; if(!$suggest) echo '<tr><td colspan="4">موردی نیست</td></tr>'; ?>
</tbody></table></div>
<h2>ABC بر اساس ارزش موجودی</h2>
<div class="table-scroll"><table class="m360-table"><thead><tr><th>کالا</th><th>موجودی</th><th>بهای واحد</th><th>ارزش</th></tr></thead><tbody>
<?php foreach($abc as $r): ?><tr><td><?= inv360_h($r['ItemName']) ?></td><td><?= inv360_h((string)$r['Qty']) ?></td><td><?= inv360_h((string)$r['UnitCost']) ?></td><td><?= inv360_h((string)$r['Val']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p class="hint">XYZ / Days on Hand / ایمنی موجودی: زیرساخت آماده؛ اجرای کامل در فاز بعد (نیازمند تاریخچه مصرف پایدار).</p>
<?php inv360_layout_end();