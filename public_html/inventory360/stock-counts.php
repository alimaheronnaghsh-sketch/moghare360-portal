<?php
require_once __DIR__.'/includes/inv360-bootstrap.php';
inv360_require_login(); $conn=inv360_db();
$rows=inv360_rows($conn,'SELECT TOP 50 c.count_id AS InventoryCountID, c.counted_qty AS CountedQuantity, c.count_status AS WorkflowStatus, p.item_name_fa AS ItemName FROM dbo.inv360_stock_counts c LEFT JOIN dbo.inv360_items p ON p.item_id=c.item_id ORDER BY c.count_id DESC',[]);
inv360_layout_start('انبارگردانی','stock-counts.php');
echo '<p><a class="btn" href="stock-count-form.php">شمارش جدید</a></p><div class="table-scroll"><table class="m360-table"><thead><tr><th>شناسه</th><th>کالا</th><th>شمارش</th><th>وضعیت</th></tr></thead><tbody>';
foreach($rows as $r) echo '<tr><td>'.(int)$r['InventoryCountID'].'</td><td>'.inv360_h((string)($r['ItemName']??'')).'</td><td>'.inv360_h((string)$r['CountedQuantity']).'</td><td>'.inv360_h((string)$r['WorkflowStatus']).'</td></tr>';
echo '</tbody></table></div>'; inv360_layout_end();