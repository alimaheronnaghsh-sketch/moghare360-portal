<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-auth.php';
require_once __DIR__ . '/p360-money.php';
require_once __DIR__ . '/p360-date.php';

function p360_h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function p360_nav_sections(): array {
    return [
        'راهبری' => [
            'dashboard.php' => 'داشبورد',
            'inbox.php' => 'صندوق ورودی',
            'approvals.php' => 'تأییدها',
        ],
        'سازمان' => [
            'company-setup.php' => 'راه‌اندازی شرکت',
            'companies.php' => 'شرکت‌ها',
            'branches.php' => 'شعب',
            'departments.php' => 'واحدها',
            'positions.php' => 'سمت‌ها',
        ],
        'پرسنل' => [
            'employees.php' => 'پرسنل',
            'employee-form.php' => 'فرم پرسنل',
            'employee-search.php' => 'جستجوی پرسنل',
        ],
        'قوانین و قراردادها' => [
            'legal-rule-center.php' => 'مرکز قوانین',
            'employment-types.php' => 'نوع همکاری',
            'contract-types.php' => 'نوع قرارداد',
            'contract-templates.php' => 'قالب قرارداد',
            'labor-calendar.php' => 'تقویم کار',
        ],
        'حضور و حقوق' => [
            'attendance-devices.php' => 'دستگاه حضور',
            'attendance-raw.php' => 'لاگ خام',
            'attendance-records.php' => 'کارکرد',
            'timesheets.php' => 'تایم‌شیت',
            'payroll-periods.php' => 'دوره حقوق',
            'payroll-run.php' => 'اجرای حقوق',
            'payroll-slips.php' => 'فیش حقوق',
        ],
        'جذب و استخدام' => [
            'recruitment.php' => 'جذب',
            'manpower-requests.php' => 'درخواست نیرو',
            'vacancies.php' => 'موقعیت‌ها',
            'candidates.php' => 'کاندیدها',
        ],
        'خدمات و رفاه' => [
            'self-service.php' => 'خودخدمت',
            'leave-requests.php' => 'مرخصی',
            'mission-requests.php' => 'مأموریت',
            'overtime-requests.php' => 'اضافه‌کاری',
            'loans.php' => 'وام',
        ],
        'عملکرد و تجهیزات' => [
            'performance-reviews.php' => 'عملکرد',
            'training.php' => 'آموزش',
            'equipment.php' => 'تجهیزات',
            'announcements.php' => 'اطلاعیه',
        ],
        'خروج و تسویه' => [
            'exit-cases.php' => 'خروج',
            'settlement.php' => 'تسویه',
        ],
        'گزارش و Audit' => [
            'reports.php' => 'گزارش‌ها',
            'audit-log.php' => 'Audit',
            'settings.php' => 'تنظیمات',
        ],
    ];
}

function p360_layout_start(string $title, string $active = ''): void {
    $user = p360_current_user();
    $name = p360_h((string)($user['display_name'] ?? ''));
    $current = basename((string)($_SERVER['PHP_SELF'] ?? ''));
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . p360_h($title) . ' | PeopleOS360</title>';
    echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">';
    echo '</head><body>';
    echo '<div class="m360-app-shell">';
    echo '<aside class="m360-sidebar">';
    echo '<div class="m360-sidebar-brand"><h2>PeopleOS360</h2><small>سامانه مستقل اداری و منابع انسانی</small></div>';
    foreach (p360_nav_sections() as $section => $links) {
        echo '<div class="m360-sidebar-section">' . p360_h($section) . '</div>';
        foreach ($links as $href => $label) {
            $cls = ($active === $href || $current === $href) ? ' active' : '';
            echo '<a class="m360-sidebar-link' . $cls . '" href="' . p360_h($href) . '">' . p360_h($label) . '</a>';
        }
    }
    echo '<div class="m360-sidebar-foot"><span>' . $name . '</span><a href="logout.php">خروج</a></div>';
    echo '</aside>';
    echo '<main class="m360-main">';
    echo '<div class="m360-page-header"><h1 class="m360-page-title">' . p360_h($title) . '</h1></div>';
}

function p360_layout_end(): void { echo '</main></div></body></html>'; }

function p360_flash(?string $msg, bool $ok = true): void {
    if ($msg) echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360_h($msg) . '</div>';
}

function p360_table(array $rows, array $cols): void {
    echo '<table class="m360-table"><thead><tr>';
    foreach ($cols as $c) echo '<th>' . p360_h($c) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach (array_keys($cols) as $k) echo '<td>' . p360_h((string)($r[$k] ?? '')) . '</td>';
        echo '</tr>';
    }
    if ($rows === []) echo '<tr><td colspan="' . count($cols) . '" class="m360-empty-state">موردی نیست.</td></tr>';
    echo '</tbody></table>';
}
