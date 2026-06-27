<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

try {
    $c = commercial_db();
    if ($c !== false) { cs_require_auth($c, 'commercial.demo.view'); @odbc_close($c); }
} catch (Throwable) { cs_error('Sales Showcase', 'دسترسی به Sales Showcase ممکن نیست.'); }

cs_render_head('MOGHARE360 Sales Showcase');
echo '<div class="p10cs-hero"><h1>MOGHARE360 Sales Showcase</h1><p>فروش اولیه — بدون ادعای SaaS production</p></div>';

$sections = [
    'Problem' => 'تعمیرگاه‌ها با پراکندگی JobCard، انبار، مالی و CRM در اکسل و سیستم‌های جداگانه درگیرند.',
    'Solution' => 'MOGHARE360 یک Repair Shop Operating System یکپارچه از پذیرش تا تحویل، CRM و گزارش مدیریتی است.',
    'Modules' => 'Customer Core · Operation Engine · Rule Engine · Inventory · Finance Preview · CRM · HR · Management Reporting',
    'Business Value' => 'کاهش گم‌شدن پرونده، شفافیت مالی preview، پیگیری مشتری، آمادگی مدیریتی.',
    'Why MOGHARE360' => 'ساخته‌شده برای تعمیرگاه ایرانی — RTL — Soft Run تست‌شده — مرزهای امن معماری حفظ‌شده.',
    'Demo Flow' => 'Soft Run Home → Business Command → Operation → Finance Preview → CRM → Management Dashboard → Commercial Demo',
    'Commercial Readiness' => 'بسته‌های محصول، license preview، checklist و گزارش نهایی — بدون billing واقعی.',
];

foreach ($sections as $title => $body) {
    echo '<div class="p1cc-card"><h2 class="p10cs-section-title">' . commercial_h($title) . '</h2><p>' . commercial_h($body) . '</p></div>';
}

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">ماژول‌ها</h2><div class="p1cc-nav-grid">';
$mods = [
    ['erp-customer-core-dashboard.php', 'Customer Core'], ['erp-operation-control-center.php', 'Operation'],
    ['erp-rule-decision-board.php', 'Rule Engine'], ['erp-stock-board.php', 'Inventory'],
    ['erp-finance-control-center.php', 'Finance Preview'], ['erp-crm-followup-board.php', 'CRM'],
    ['erp-hr-dashboard.php', 'HR'], ['erp-management-dashboard.php', 'Management Reporting'],
];
foreach ($mods as [$url, $title]) {
    if (commercial_page_exists($url)) {
        echo '<a class="p1cc-nav-card" href="' . commercial_h($url) . '"><span class="p1cc-nav-title">' . commercial_h($title) . '</span></a>';
    }
}
echo '</div></div>';

cs_render_foot();
