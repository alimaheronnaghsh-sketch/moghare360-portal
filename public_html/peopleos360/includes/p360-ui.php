<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-auth.php';
require_once __DIR__ . '/p360-money.php';
require_once __DIR__ . '/p360-date.php';
function p360_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function p360_nav_items(): array {
    return [
        'dashboard.php' => 'داشبورد',
        'company-setup.php' => 'راه‌اندازی شرکت',
        'companies.php' => 'شرکت‌ها',
        'branches.php' => 'شعب',
        'departments.php' => 'واحدها',
        'positions.php' => 'سمت‌ها',
        'legal-rule-center.php' => 'مرکز قوانین',
        'contract-templates.php' => 'قالب قرارداد',
        'employment-types.php' => 'نوع همکاری',
        'contract-types.php' => 'نوع قرارداد',
        'labor-calendar.php' => 'تقویم کار',
        'employees.php' => 'پرسنل',
        'employee-search.php' => 'جستجوی پرسنل',
        'recruitment.php' => 'جذب',
        'attendance-devices.php' => 'دستگاه حضور',
        'attendance-records.php' => 'کارکرد',
        'timesheets.php' => 'تایم‌شیت',
        'payroll-periods.php' => 'دوره حقوق',
        'payroll-slips.php' => 'فیش حقوق',
        'self-service.php' => 'خودخدمت',
        'leave-requests.php' => 'مرخصی',
        'loans.php' => 'وام',
        'performance-reviews.php' => 'عملکرد',
        'training.php' => 'آموزش',
        'equipment.php' => 'تجهیزات',
        'inbox.php' => 'صندوق ورودی',
        'approvals.php' => 'تأییدها',
        'reports.php' => 'گزارش',
        'audit-log.php' => 'Audit',
        'settings.php' => 'تنظیمات',
        'exit-cases.php' => 'خروج',
        'settlement.php' => 'تسویه',
        'announcements.php' => 'اطلاعیه',
    ];
}
function p360_layout_start(string $title, string $active = ''): void {
    $user = p360_current_user();
    $name = p360_h((string)($user['display_name'] ?? ''));
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . p360_h($title) . ' | PeopleOS360</title><link rel="stylesheet" href="assets/peopleos360.css"></head><body><div class="p360-shell">';
    echo '<aside class="p360-nav"><div class="brand">PeopleOS360</div><nav>';
    foreach (p360_nav_items() as $href => $label) {
        $cls = ($active === $href) ? ' class="active"' : '';
        echo '<a href="' . p360_h($href) . '"' . $cls . '>' . p360_h($label) . '</a>';
    }
    echo '</nav><div class="nav-foot"><div>' . $name . '</div><a href="logout.php">خروج</a></div></aside>';
    echo '<main class="p360-main"><header class="p360-top"><h1>' . p360_h($title) . '</h1></header><div class="p360-content">';
}
function p360_layout_end(): void { echo '</div></main></div></body></html>'; }
function p360_flash(?string $msg, bool $ok = true): void {
    if ($msg) echo '<div class="notice ' . ($ok ? 'ok' : 'err') . '">' . p360_h($msg) . '</div>';
}
function p360_table(array $rows, array $cols): void {
    echo '<table class="data"><thead><tr>';
    foreach ($cols as $c) echo '<th>' . p360_h($c) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach (array_keys($cols) as $k) echo '<td>' . p360_h((string)($r[$k] ?? '')) . '</td>';
        echo '</tr>';
    }
    if ($rows === []) echo '<tr><td colspan="' . count($cols) . '">موردی نیست.</td></tr>';
    echo '</tbody></table>';
}