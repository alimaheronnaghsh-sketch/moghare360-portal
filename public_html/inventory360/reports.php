<?php
require_once __DIR__.'/includes/inv360-bootstrap.php'; inv360_require_login(); $conn=inv360_db();
$invValue=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(b.PhysicalQty * ISNULL(p.StandardCost, ISNULL(p.LastPurchasePrice,0))),0) FROM dbo.Inv360StockBalances b LEFT JOIN dbo.Parts p ON p.PartID=b.PartID',[])??0);
$belowReorder=inv360_rows($conn,'SELECT p.PartID, p.ItemName, p.ReorderPoint, ISNULL(SUM(b.PhysicalQty - b.ReservedQty - b.QuarantineQty - b.BlockedQty),0) AS Avail FROM dbo.Parts p LEFT JOIN dbo.Inv360StockBalances b ON b.PartID=p.PartID WHERE ISNULL(p.IsDeleted,0)=0 GROUP BY p.PartID,p.ItemName,p.ReorderPoint HAVING ISNULL(p.ReorderPoint,0)>0 AND ISNULL(SUM(b.PhysicalQty - b.ReservedQty - b.QuarantineQty - b.BlockedQty),0)<=ISNULL(p.ReorderPoint,0)',[]);
$overMax=inv360_rows($conn,'SELECT p.PartID, p.ItemName, p.MaxStock, ISNULL(SUM(b.PhysicalQty),0) AS Phys FROM dbo.Parts p LEFT JOIN dbo.Inv360StockBalances b ON b.PartID=p.PartID WHERE ISNULL(p.IsDeleted,0)=0 GROUP BY p.PartID,p.ItemName,p.MaxStock HAVING ISNULL(p.MaxStock,0)>0 AND ISNULL(SUM(b.PhysicalQty),0)>ISNULL(p.MaxStock,0)',[]);
$quarantine=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(QuarantineQty),0) FROM dbo.Inv360StockBalances',[])??0);
$pendingDocs=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.Inv360StockDocuments WHERE DocStatus IN (N\'draft\',N\'submitted\',N\'approved\')',[])??0);
$openPR=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.Inv360PurchaseRequests WHERE PRStatus NOT IN (N\'closed\',N\'cancelled\')',[])??0);
$openPO=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.Inv360PurchaseOrders WHERE POStatus NOT IN (N\'closed\',N\'cancelled\')',[])??0);
$abc=inv360_rows($conn,'SELECT TOP 20 p.ItemName, ISNULL(SUM(b.PhysicalQty),0) AS Qty, ISNULL(p.StandardCost,ISNULL(p.LastPurchasePrice,0)) AS UnitCost, ISNULL(SUM(b.PhysicalQty),0)*ISNULL(p.StandardCost,ISNULL(p.LastPurchasePrice,0)) AS Val FROM dbo.Parts p LEFT JOIN dbo.Inv360StockBalances b ON b.PartID=p.PartID WHERE ISNULL(p.IsDeleted,0)=0 GROUP BY p.ItemName,p.StandardCost,p.LastPurchasePrice ORDER BY Val DESC',[]);
$suggest=inv360_rows($conn,'SELECT p.PartID,p.ItemName,p.WorkshopCode,p.TechnicalCode,p.ReorderPoint,ISNULL(SUM(b.PhysicalQty - b.ReservedQty - b.QuarantineQty - b.BlockedQty),0) AS Avail FROM dbo.Parts p LEFT JOIN dbo.Inv360StockBalances b ON b.PartID=p.PartID WHERE ISNULL(p.IsDeleted,0)=0 GROUP BY p.PartID,p.ItemName,p.WorkshopCode,p.TechnicalCode,p.ReorderPoint HAVING ISNULL(p.ReorderPoint,0)>0 AND ISNULL(SUM(b.PhysicalQty - b.ReservedQty - b.QuarantineQty - b.BlockedQty),0)<=ISNULL(p.ReorderPoint,0)',[]);
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
<div class="table-scroll"><table class="data-table"><thead><tr><th>کالا</th><th>کد کارگاه</th><th>آزاد</th><th>نقطه سفارش</th></tr></thead><tbody>
<?php foreach($suggest as $r): ?><tr><td><?= inv360_h($r['ItemName']) ?></td><td><?= inv360_h((string)$r['WorkshopCode']) ?></td><td><?= inv360_h((string)$r['Avail']) ?></td><td><?= inv360_h((string)$r['ReorderPoint']) ?></td></tr><?php endforeach; if(!$suggest) echo '<tr><td colspan="4">موردی نیست</td></tr>'; ?>
</tbody></table></div>
<h2>ABC بر اساس ارزش موجودی</h2>
<div class="table-scroll"><table class="data-table"><thead><tr><th>کالا</th><th>موجودی</th><th>بهای واحد</th><th>ارزش</th></tr></thead><tbody>
<?php foreach($abc as $r): ?><tr><td><?= inv360_h($r['ItemName']) ?></td><td><?= inv360_h((string)$r['Qty']) ?></td><td><?= inv360_h((string)$r['UnitCost']) ?></td><td><?= inv360_h((string)$r['Val']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p class="hint">XYZ / Days on Hand / ایمنی موجودی: زیرساخت آماده؛ اجرای کامل در فاز بعد (نیازمند تاریخچه مصرف پایدار).</p>
<?php inv360_layout_end();