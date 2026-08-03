<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

function work360_nav_items(array $user): array
{
    $role = strtoupper((string)($user['role_code'] ?? 'STAFF'));
    $items = [
        'dashboard.php' => 'داشبورد',
        'my-tasks.php' => 'کارهای من',
        'task-create.php' => 'ثبت کار',
    ];
    if (in_array($role, ['OWNER', 'MANAGER', 'SUPERVISOR'], true)) {
        $items['supervisor-board.php'] = 'برد سرپرست';
    }
    if (in_array($role, ['OWNER', 'MANAGER'], true)) {
        $items['manager-report.php'] = 'گزارش مدیریت';
        $items['daily-performance.php'] = 'عملکرد روزانه';
        $items['reports.php'] = 'گزارش‌ها';
        $items['users.php'] = 'کاربران';
        $items['departments.php'] = 'واحدها';
    } elseif ($role === 'SUPERVISOR') {
        $items['daily-performance.php'] = 'عملکرد روزانه';
    } else {
        $items['daily-performance.php'] = 'خلاصه روزانه من';
    }
    return $items;
}

function work360_layout_start(string $title, string $active = ''): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $user = work360_current_user();
    $name = work360_h((string)($user['full_name'] ?? ''));
    $role = work360_h(work360_role_fa((string)($user['role_code'] ?? '')));
    $current = basename((string)($_SERVER['PHP_SELF'] ?? ''));
    $crumb = 'داشبورد';
    if ($current === 'my-tasks.php') { $crumb = 'کارهای من'; }
    elseif ($current === 'supervisor-board.php') { $crumb = 'برد سرپرست'; }
    elseif ($current === 'manager-report.php') { $crumb = 'گزارش مدیریت'; }
    elseif ($current === 'daily-performance.php') { $crumb = 'عملکرد روزانه'; }
    elseif ($current === 'task-create.php') { $crumb = 'ثبت کار'; }
    elseif ($current === 'users.php' || $current === 'user-form.php') { $crumb = 'کاربران'; }
    elseif ($current === 'departments.php') { $crumb = 'واحدها'; }
    elseif ($current === 'reports.php') { $crumb = 'گزارش‌ها'; }
    elseif (str_starts_with($current, 'task-')) { $crumb = 'کار'; }
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . work360_h($title) . ' | Work360</title>';
    echo '<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">';
    echo '<link rel="stylesheet" href="assets/work360.css">';
    echo '</head><body><div class="m360-app-shell">';
    echo '<aside class="m360-sidebar"><div class="m360-sidebar-brand"><h2>Work360</h2><small>مرکز کار و پیگیری روزانه</small></div>';
    foreach (work360_nav_items($user ?? []) as $href => $label) {
        $cls = ($active === $href || $current === $href) ? ' active' : '';
        echo '<a class="m360-sidebar-link' . $cls . '" href="' . work360_h($href) . '">' . work360_h($label) . '</a>';
    }
    echo '<div class="m360-sidebar-foot"><span>' . $name . ' · ' . $role . '</span><a href="logout.php">خروج</a></div>';
    echo '</aside><main class="m360-main">';
    echo '<div class="w360-breadcrumb">پرسنل / Work360 / ' . work360_h($crumb) . ' · <a href="../personnel.html">بازگشت به صفحه پرسنل</a></div>';
    echo '<div class="m360-page-header"><h1 class="m360-page-title">' . work360_h($title) . '</h1></div>';
}

function work360_layout_end(): void
{
    echo '</main></div></body></html>';
}

function work360_flash(?string $msg, bool $ok = true): void
{
    if ($msg === null || $msg === '') {
        return;
    }
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . work360_h($msg) . '</div>';
}

function work360_gauge_svg(float $value, string $label, string $tone = 'ok'): string
{
    $v = max(0, min(100, $value));
    $r = 42;
    $c = 2 * M_PI * $r;
    $dash = ($v / 100) * $c;
    $offset = $c - $dash;
    $color = $tone === 'warn' ? '#d4af37' : ($tone === 'bad' ? '#ef4444' : '#22c55e');
    return '<div class="w360-gauge">'
        . '<svg viewBox="0 0 120 120" aria-hidden="true">'
        . '<circle class="w360-gauge-track" cx="60" cy="60" r="' . $r . '"></circle>'
        . '<circle class="w360-gauge-value" cx="60" cy="60" r="' . $r . '" stroke="' . $color . '" stroke-dasharray="' . $c . '" stroke-dashoffset="' . $offset . '"></circle>'
        . '</svg>'
        . '<div class="w360-gauge-center"><strong>' . work360_h((string)$v) . '%</strong><span>' . work360_h($label) . '</span></div>'
        . '</div>';
}
