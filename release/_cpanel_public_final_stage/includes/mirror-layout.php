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
    return 'MOGHAREH360';
}

function mirror_brand_tagline(): string
{
    return 'سامانه خدمات خودرو';
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
    echo '</head><body class="m360-public-shell"><div class="m360-wrap">';

    echo '<header class="m360-public-header">';
    echo '<div class="m360-public-header__inner">';
    echo '<a href="index.php" class="m360-public-brand">';
    if ($logo !== '') {
        echo '<img class="m360-public-brand__logo m360-public-logo" src="' . mirror_h($logo) . '" alt="MOGHAREH360">';
    }
    echo '<span class="m360-public-brand__text">';
    echo '<span class="m360-public-brand__title m360-brand-latin" lang="en" dir="ltr">MOGHAREH360</span>';
    echo '<span class="m360-public-brand__tagline">' . mirror_h(mirror_brand_tagline()) . '</span>';
    echo '</span></a>';
    echo '<nav class="m360-public-nav" aria-label="منوی اصلی">';
    $links = [
        'index' => ['index.php', 'خانه'],
        'customer' => ['customer-request.php', 'مشتری'],
        'staff' => ['staff-login.php', 'پرسنل'],
    ];
    foreach ($links as $key => [$href, $label]) {
        $cls = 'm360-public-nav__link' . ($activeNav === $key ? ' is-active' : '');
        echo '<a class="' . $cls . '" href="' . mirror_h($href) . '">' . mirror_h($label) . '</a>';
    }
    echo '<a class="m360-public-nav__link m360-public-nav__link--mgmt" href="owner-login.php">ورود مدیریتی</a>';
    echo '</nav></div></header>';
    echo '<main class="m360-public-main">';
}

function mirror_render_foot(): void
{
    echo '</main>';
    echo '<footer class="m360-footer">';
    echo '© <span class="m360-brand-latin" lang="en" dir="ltr">MOGHAREH360</span> — تمامی حقوق محفوظ است.';
    echo '<div class="m360-install-hint">برای نصب اپلیکیشن: از منوی مرورگر «افزودن به صفحه اصلی» را انتخاب کنید.</div>';
    echo '</footer></div>';
    echo '<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("service-worker.js").catch(function(){});}</script>';
    echo '</body></html>';
}
