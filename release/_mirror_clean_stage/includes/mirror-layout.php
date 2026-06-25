<?php
declare(strict_types=1);

/**
 * MOGHARE360 Mirror — shared layout and config loader.
 */

function mirror_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return array<string, mixed> */
function mirror_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $example = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'mirror-config.example.php';
    $local = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'mirror-config.php';

    if (is_file($local)) {
        $loaded = require $local;
        $config = is_array($loaded) ? $loaded : [];
        return $config;
    }

    $config = is_file($example) ? (require $example) : [];
    if (!is_array($config)) {
        $config = [];
    }

    return $config;
}

function mirror_brand_name(): string
{
    return (string)(mirror_config()['BRAND_NAME'] ?? 'مقاره موتورز');
}

function mirror_logo_path(): string
{
    $jpg = 'assets/brand/moghareh-motors-logo.jpg';
    if (is_file(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $jpg))) {
        return $jpg;
    }
    return '';
}

function mirror_sms_otp_enabled(): bool
{
    $cfg = mirror_config();
    return !empty($cfg['SMS_OTP_ENABLED']) && !empty($cfg['SMS_GATEWAY_CONFIGURED']);
}

function mirror_render_head(string $title, string $activeNav = ''): void
{
    $brand = mirror_brand_name();
    $logo = mirror_logo_path();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<meta name="theme-color" content="#22c55e">';
    echo '<link rel="manifest" href="manifest.webmanifest">';
    echo '<title>' . mirror_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/css/mirror.css">';
    echo '</head><body><div class="m360-wrap">';

    echo '<header class="m360-header"><div class="m360-brand">';
    if ($logo !== '') {
        echo '<img src="' . mirror_h($logo) . '" alt="' . mirror_h($brand) . '">';
    }
    echo '<div><h1>' . mirror_h($brand) . '</h1>';
    echo '<p class="m360-tagline">پورتال یکپارچه خدمات خودرو</p></div></div>';
    echo '<nav class="m360-nav">';
    $links = [
        'index' => ['index.php', 'خانه'],
        'customer' => ['customer-request.php', 'مشتری'],
        'staff' => ['staff-login.php', 'پرسنل'],
    ];
    foreach ($links as $key => [$href, $label]) {
        $cls = ($activeNav === $key) ? ' class="active"' : '';
        echo '<a href="' . mirror_h($href) . '"' . $cls . '>' . mirror_h($label) . '</a>';
    }
    echo '</nav></header>';
}

function mirror_render_foot(): void
{
    echo '<footer class="m360-footer">';
    echo '© ' . mirror_h(mirror_brand_name()) . ' — تمامی حقوق محفوظ است.';
    echo '<div class="m360-install-hint">برای نصب اپلیکیشن: از منوی مرورگر «افزودن به صفحه اصلی» را انتخاب کنید.</div>';
    echo '</footer></div>';
    echo '<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("service-worker.js").catch(function(){});}</script>';
    echo '</body></html>';
}
