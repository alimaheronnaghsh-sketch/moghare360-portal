<?php
require_once __DIR__ . '/includes/inv360-bootstrap.php';
inv360_require_login();
$conn = inv360_db();
$parts=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_items WHERE ISNULL(is_deleted,0)=0',[])??0);
$wh=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_warehouses WHERE is_active=1',[])??0);
$openDocs=(int)(inv360_scalar($conn,"SELECT COUNT(*) FROM dbo.inv360_stock_documents WHERE doc_status IN (N'draft',N'submitted',N'approved')",[])??0);
$qua=(float)(inv360_scalar($conn,'SELECT ISNULL(SUM(quarantine_qty),0) FROM dbo.inv360_stock_balances',[])??0);
$low=(int)(inv360_scalar($conn,'SELECT COUNT(*) FROM dbo.inv360_items p WHERE ISNULL(p.is_deleted,0)=0 AND ISNULL(p.reorder_point,0)>0 AND ISNULL(p.quantity,0) <= ISNULL(p.reorder_point,0)',[])??0);
inv360_layout_start('داشبورد Inventory360','dashboard.php');
?>
<div class="kpi-grid">
  <div class="kpi"><span>تعداد کالا</span><strong><?= (int)$parts ?></strong></div>
  <div class="kpi"><span>انبارها</span><strong><?= (int)$wh ?></strong></div>
  <div class="kpi"><span>اسناد باز</span><strong><?= (int)$openDocs ?></strong></div>
  <div class="kpi"><span>موجودی قرنطینه</span><strong><?= inv360_h((string)$qua) ?></strong></div>
  <div class="kpi"><span>زیر نقطه سفارش</span><strong><?= (int)$low ?></strong></div>
</div>
<h2>مسیر تست پیشنهادی مالک</h2>
<div class="steps">
<a href="item-form.php">1. تعریف کالا</a>
<a href="item-search.php">2-6. جستجو</a>
<a href="warehouse-form.php">4. انبار</a>
<a href="locations.php">Bin</a>
<a href="stock-document-form.php">5. رسید خرید</a>
<a href="stock-balances.php">6. موجودی</a>
<a href="reservations.php">7-8. رزرو</a>
<a href="stock-document-form.php">9. حواله</a>
<a href="stock-document-form.php">10. منفی موجودی</a>
<a href="stock-document-form.php">11. انتقال</a>
<a href="stock-document-form.php">12. تعدیل</a>
<a href="stock-count-form.php">13. انبارگردانی</a>
<a href="supplier-form.php">14. تأمین‌کننده</a>
<a href="purchase-request-form.php">15. PR</a>
<a href="rfq-form.php">16. RFQ</a>
<a href="rfq.php">17. مقایسه</a>
<a href="purchase-order-form.php">18. PO</a>
<a href="goods-receipt-form.php">19. دریافت</a>
<a href="quality-control.php">20-22. QC/قرنطینه</a>
<a href="landed-cost.php">23. بها</a>
<a href="logistics-form.php">24. لجستیک</a>
<a href="tool-issue-return.php">25-26. ابزار</a>
<a href="reports.php">27. گزارش</a>
<a href="audit-log.php">28. Audit</a>
</div>
<?php inv360_layout_end();