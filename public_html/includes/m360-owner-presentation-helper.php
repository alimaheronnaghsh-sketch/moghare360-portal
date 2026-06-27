<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11 — Owner presentation lock (read-only demo flow + V1 boundaries).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-lock-helper.php';

/**
 * @return list<array{order:int,title_fa:string,url:string,note_fa:string}>
 */
function m360_owner_presentation_flow(): array
{
    return [
        ['order' => 1, 'title_fa' => 'Product Home', 'url' => 'erp-product-home.php', 'note_fa' => 'ورود محصولی و نمای کلی V1'],
        ['order' => 2, 'title_fa' => 'Soft Run Control Center', 'url' => 'erp-soft-run-control-center.php', 'note_fa' => 'آمادگی بهره‌برداری آزمایشی'],
        ['order' => 3, 'title_fa' => 'Demo Flow Map', 'url' => 'erp-demo-flow-map.php', 'note_fa' => 'نقشه مسیر P1–P8'],
        ['order' => 4, 'title_fa' => 'End-to-End Demo Scenario', 'url' => 'erp-end-to-end-demo-scenario.php', 'note_fa' => 'ردیابی مراحل با evidence'],
        ['order' => 5, 'title_fa' => 'Management Dashboard', 'url' => 'erp-management-dashboard.php', 'note_fa' => 'KPI مدیریتی read-only'],
        ['order' => 6, 'title_fa' => 'Bottleneck Monitor', 'url' => 'erp-bottleneck-monitor.php', 'note_fa' => 'گلوگاه‌های عملیاتی'],
        ['order' => 7, 'title_fa' => 'Financial Control Summary', 'url' => 'erp-financial-control-summary.php', 'note_fa' => 'کنترل مالی عملیاتی — بدون حسابداری رسمی'],
        ['order' => 8, 'title_fa' => 'JobCard Timeline', 'url' => 'erp-jobcard-timeline.php?jobcard_id=0', 'note_fa' => 'مسیر audit از Intake تا Close'],
        ['order' => 9, 'title_fa' => 'Final Invoice / Delivery', 'url' => 'erp-final-invoice-board.php', 'note_fa' => 'فاکتور نهایی، تسویه، تحویل'],
        ['order' => 10, 'title_fa' => 'RC Release Readiness', 'url' => 'erp-release-readiness.php', 'note_fa' => 'آمادگی Release Candidate'],
    ];
}

/**
 * @return list<string>
 */
function m360_owner_presentation_strengths(): array
{
    return [
        'چرخه کامل P1 تا P7 از درخواست تا بستن JobCard',
        'Gateهای P1.5 تا P7 بدون bypass',
        'داشبورد مدیریتی P8 read-only',
        'Soft Run و Demo Scenario P9',
        'Navigation و Route Registry P10',
        'RC Final Lock و Package امن P11',
    ];
}

/**
 * @return list<string>
 */
function m360_owner_v1_exclusions(): array
{
    return [
        'حسابداری رسمی / سند حسابداری',
        'دفترکل (Ledger)',
        'درگاه پرداخت آنلاین',
        'اتصال بانکی',
        'مالیات رسمی / سامانه مودیان',
        'SaaS / Multi-company / Tenant',
        'HR / CRM / Marketing کامل',
        'خرید و انبار کامل',
        'Production deploy از این RC',
    ];
}

/**
 * @return list<array{key:string,title_fa:string}>
 */
function m360_owner_signoff_checklist(): array
{
    return [
        ['key' => 'workflow_demo', 'title_fa' => 'مسیر عملیاتی P1–P7 قابل نمایش است'],
        ['key' => 'management_kpi', 'title_fa' => 'KPI و Timeline مدیریتی نمایش داده شد'],
        ['key' => 'scope_understood', 'title_fa' => 'محدودیت‌های V1 توضیح داده شد'],
        ['key' => 'no_false_promise', 'title_fa' => 'قول حسابداری/درگاه/بانک/مالیات داده نشد'],
        ['key' => 'demo_data_only', 'title_fa' => 'فقط داده DEMO/M360-DEMO استفاده شد'],
        ['key' => 'security_lock', 'title_fa' => 'Security scope lock مرور شد'],
        ['key' => 'rc_lock', 'title_fa' => 'RC Final Lock تأیید شد'],
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_owner_presentation_lock_report(): array
{
    $lock = m360_release_lock_status();
    return [
        'flow' => m360_owner_presentation_flow(),
        'strengths' => m360_owner_presentation_strengths(),
        'exclusions' => m360_owner_v1_exclusions(),
        'signoff_checklist' => m360_owner_signoff_checklist(),
        'rc_status' => $lock['rc_status'],
        'read_only' => true,
    ];
}
