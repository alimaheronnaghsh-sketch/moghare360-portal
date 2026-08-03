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
<div class="m360-page-header" style="border-bottom:none;margin-bottom:0.5rem;">
<p class="m360-page-subtitle">سامانه مستقل انبار، خرید و لجستیک</p>
<div class="m360-page-badges">
<span class="m360-badge m360-badge-ok">مستقل</span>
<span class="m360-badge m360-badge-info">پایگاه داده مشترک moghare360_ERP</span>
<span class="m360-badge">بدون اتصال عملیاتی</span>
</div>
</div>

<div class="m360-kpi-grid">
  <div class="m360-kpi-card"><span>تعداد کالا</span><strong><?= (int)$parts ?></strong></div>
  <div class="m360-kpi-card"><span>انبارها</span><strong><?= (int)$wh ?></strong></div>
  <div class="m360-kpi-card"><span>اسناد باز</span><strong><?= (int)$openDocs ?></strong></div>
  <div class="m360-kpi-card"><span>موجودی قرنطینه</span><strong><?= inv360_h((string)$qua) ?></strong></div>
  <div class="m360-kpi-card"><span>زیر نقطه سفارش</span><strong><?= (int)$low ?></strong></div>
</div>

<h2 style="font-size:1.1rem;margin-bottom:1rem;">دسترسی سریع ماژول‌ها</h2>
<div class="m360-module-grid">
<a class="m360-module-card" href="items.php"><h3>کالا و جستجو</h3><p>تعریف، جستجو و مدیریت کالا</p></a>
<a class="m360-module-card" href="warehouses.php"><h3>انبار و Bin</h3><p>انبارها، مکان‌ها و ساختار فیزیکی</p></a>
<a class="m360-module-card" href="stock-documents.php"><h3>اسناد انبار</h3><p>رسید، حواله، انتقال، تعدیل</p></a>
<a class="m360-module-card" href="stock-balances.php"><h3>موجودی و رزرو</h3><p>موجودی، رزرو و انبارگردانی</p></a>
<a class="m360-module-card" href="suppliers.php"><h3>خرید و تأمین‌کننده</h3><p>تأمین‌کنندگان و درخواست خرید</p></a>
<a class="m360-module-card" href="rfq.php"><h3>RFQ و سفارش خرید</h3><p>استعلام، مقایسه و سفارش</p></a>
<a class="m360-module-card" href="goods-receipts.php"><h3>دریافت و QC</h3><p>دریافت کالا، کنترل کیفیت، قرنطینه</p></a>
<a class="m360-module-card" href="costing.php"><h3>بهای تمام‌شده</h3><p>هزینه فرود آمده و محاسبه بها</p></a>
<a class="m360-module-card" href="logistics.php"><h3>لجستیک</h3><p>حمل و نقل و پیگیری محموله</p></a>
<a class="m360-module-card" href="tools-assets.php"><h3>ابزار و اموال</h3><p>تحویل، بازگشت و پیگیری</p></a>
<a class="m360-module-card" href="reports.php"><h3>گزارش‌ها و Audit</h3><p>گزارش‌ها و لاگ فعالیت</p></a>
</div>

<h2 style="font-size:1.1rem;margin:1.5rem 0 0.75rem;">مسیر تست پیشنهادی مالک</h2>
<?php
$sections = [
    'کالا و انبار' => [
        1 => ['item-form.php', 'تعریف کالا'],
        2 => ['item-search.php', 'جستجوی فنی'],
        3 => ['item-search.php', 'جستجوی کارگاه'],
        4 => ['warehouses.php', 'تعریف انبار'],
        5 => ['locations.php', 'تعریف Bin'],
    ],
    'عملیات موجودی' => [
        6 => ['stock-document-form.php', 'رسید خرید'],
        7 => ['stock-balances.php', 'مشاهده موجودی'],
        8 => ['reservations.php', 'رزرو'],
        9 => ['stock-document-form.php', 'حواله مصرف'],
        10 => ['stock-document-form.php', 'تست منفی موجودی'],
        11 => ['stock-document-form.php', 'انتقال'],
        12 => ['stock-document-form.php', 'تعدیل'],
        13 => ['stock-count-form.php', 'انبارگردانی'],
    ],
    'خرید و تأمین' => [
        14 => ['supplier-form.php', 'تأمین‌کننده'],
        15 => ['purchase-request-form.php', 'درخواست خرید'],
        16 => ['rfq-form.php', 'RFQ'],
        17 => ['rfq.php', 'مقایسه'],
        18 => ['purchase-order-form.php', 'سفارش خرید'],
        19 => ['goods-receipt-form.php', 'دریافت کالا'],
    ],
    'کیفیت و لجستیک' => [
        20 => ['quality-control.php', 'QC'],
        21 => ['quarantine.php', 'قرنطینه'],
        22 => ['supplier-returns.php', 'مرجوعی'],
        23 => ['landed-cost.php', 'بها'],
        24 => ['logistics.php', 'لجستیک'],
    ],
    'ابزار، گزارش و Audit' => [
        25 => ['tools-assets.php', 'ابزار صدور'],
        26 => ['tools-assets.php', 'ابزار بازگشت'],
        27 => ['reports.php', 'گزارش‌ها'],
        28 => ['audit-log.php', 'Audit'],
    ],
];
foreach ($sections as $secTitle => $steps): ?>
<div class="m360-step-section">
<h3><?= inv360_h($secTitle) ?></h3>
<div class="m360-step-grid">
<?php foreach ($steps as $n => $s): ?>
<a class="m360-step-pill" href="<?= inv360_h($s[0]) ?>"><?= (int)$n ?>. <?= inv360_h($s[1]) ?></a>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>
<?php inv360_layout_end(); ?>
