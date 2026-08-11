<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-auth.php';
require_once __DIR__ . '/p360-money.php';
require_once __DIR__ . '/p360-date.php';

function p360_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Top-level HR navigation (H0). Child operational routes remain reachable
 * from domain pages; they are not listed in the shell sidebar.
 *
 * @return array<string,string> href => label
 */
function p360_nav_items(): array
{
    return [
        'dashboard.php' => 'داشبورد',
        'employees.php' => 'پرسنل',
        'contract-templates.php' => 'قرارداد و جایگاه سازمانی',
        'attendance-records.php' => 'زمان کاری و حضور و غیاب',
        'leave-requests.php' => 'مرخصی، اضافه‌کاری و انضباط',
        'payroll-slips.php' => 'حقوق و مزایا',
        'self-service.php' => 'کارتابل پرسنل',
        'reports.php' => 'گزارش‌ها و شاخص‌ها',
        'company-setup.php' => 'تنظیمات مدیریتی',
    ];
}

/**
 * Map current script to the matching top-level nav href for selected state.
 */
function p360_nav_active_href(string $active, string $current): string
{
    $key = $active !== '' ? $active : $current;
    $key = basename($key);

    $map = [
        'dashboard.php' => 'dashboard.php',
        'index.php' => 'dashboard.php',
        'employees.php' => 'employees.php',
        'employee-form.php' => 'employees.php',
        'employee-view.php' => 'employees.php',
        'employee-search.php' => 'employees.php',
        'employee-family.php' => 'employees.php',
        'employee-education.php' => 'employees.php',
        'employee-work-history.php' => 'employees.php',
        'employee-position-history.php' => 'employees.php',
        'employee-salary-history.php' => 'employees.php',
        'contract-templates.php' => 'contract-templates.php',
        'contract-template-form.php' => 'contract-templates.php',
        'contract-template-view.php' => 'contract-templates.php',
        'contract-types.php' => 'contract-templates.php',
        'contract-generate.php' => 'contract-templates.php',
        'positions.php' => 'contract-templates.php',
        'employment-types.php' => 'contract-templates.php',
        'attendance-records.php' => 'attendance-records.php',
        'attendance-devices.php' => 'attendance-records.php',
        'attendance-raw.php' => 'attendance-records.php',
        'timesheets.php' => 'attendance-records.php',
        'labor-calendar.php' => 'attendance-records.php',
        'leave-requests.php' => 'leave-requests.php',
        'overtime-requests.php' => 'leave-requests.php',
        'mission-requests.php' => 'leave-requests.php',
        'disciplinary.php' => 'leave-requests.php',
        'approvals.php' => 'leave-requests.php',
        'inbox.php' => 'leave-requests.php',
        'payroll-slips.php' => 'payroll-slips.php',
        'payroll-periods.php' => 'payroll-slips.php',
        'payroll-run.php' => 'payroll-slips.php',
        'loans.php' => 'payroll-slips.php',
        'loan-form.php' => 'payroll-slips.php',
        'self-service.php' => 'self-service.php',
        'reports.php' => 'reports.php',
        'audit-log.php' => 'reports.php',
        'company-setup.php' => 'company-setup.php',
        'companies.php' => 'company-setup.php',
        'branches.php' => 'company-setup.php',
        'departments.php' => 'company-setup.php',
        'settings.php' => 'company-setup.php',
        'legal-rule-center.php' => 'company-setup.php',
        'legal-rule-form.php' => 'company-setup.php',
        'legal-text-upload.php' => 'company-setup.php',
    ];

    if (isset($map[$key])) {
        return $map[$key];
    }
    if (isset(p360_nav_items()[$key])) {
        return $key;
    }
    return $active !== '' ? basename($active) : $current;
}

function p360_browser_title(string $title): string
{
    $title = trim($title);
    if ($title === '' || $title === 'منابع انسانی' || $title === 'داشبورد') {
        return 'منابع انسانی | ماهین 360°';
    }
    return $title . ' | منابع انسانی';
}

function p360_layout_start(string $title, string $active = ''): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $user = p360_current_user();
    $name = p360_h((string)($user['display_name'] ?? ''));
    $current = basename((string)($_SERVER['PHP_SELF'] ?? ''));
    $activeHref = p360_nav_active_href($active, $current);
    $pageTitle = p360_browser_title($title);

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . p360_h($pageTitle) . '</title>';
    echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">';
    echo '</head><body>';
    echo '<div class="m360-app-shell">';
    echo '<aside class="m360-sidebar" aria-label="منوی منابع انسانی">';
    echo '<div class="m360-sidebar-brand"><h2>منابع انسانی</h2><small>ماهین 360°</small></div>';
    foreach (p360_nav_items() as $href => $label) {
        $cls = ($activeHref === $href) ? ' active' : '';
        $aria = ($activeHref === $href) ? ' aria-current="page"' : '';
        echo '<a class="m360-sidebar-link' . $cls . '" href="' . p360_h($href) . '"' . $aria . '>' . p360_h($label) . '</a>';
    }
    echo '<div class="m360-sidebar-foot"><span>' . $name . '</span><a href="logout.php">خروج</a></div>';
    echo '</aside>';
    echo '<main class="m360-main">';
    echo '<div class="m360-page-header"><h1 class="m360-page-title">' . p360_h($title) . '</h1></div>';
}

function p360_layout_end(): void
{
    echo '</main></div></body></html>';
}

function p360_flash(?string $msg, bool $ok = true): void
{
    if ($msg) {
        echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360_h($msg) . '</div>';
    }
}

function p360_table(array $rows, array $cols): void
{
    echo '<table class="m360-table"><thead><tr>';
    foreach ($cols as $c) {
        echo '<th>' . p360_h($c) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach (array_keys($cols) as $k) {
            echo '<td>' . p360_h((string)($r[$k] ?? '')) . '</td>';
        }
        echo '</tr>';
    }
    if ($rows === []) {
        echo '<tr><td colspan="' . count($cols) . '" class="m360-empty-state">موردی نیست.</td></tr>';
    }
    echo '</tbody></table>';
}
