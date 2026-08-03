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

function mirror_public_asset_version(): string
{
    $css = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mirror.css';
    if (is_file($css)) {
        return (string)filemtime($css);
    }

    return 'fix-f-v2';
}

function mirror_render_head(string $title, string $activeNav = ''): void
{
    $logo = mirror_logo_path();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">';
    echo '<meta http-equiv="Pragma" content="no-cache">';
    echo '<meta http-equiv="Expires" content="0">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<meta name="theme-color" content="#22c55e">';
    echo '<link rel="manifest" href="manifest.webmanifest">';
    echo '<title>' . mirror_h($title) . '</title>';
    $assetV = mirror_public_asset_version();
    $tokensPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'moghare360-ui' . DIRECTORY_SEPARATOR . 'moghare360-design-tokens.css';
    $tokensV = is_file($tokensPath) ? (string)filemtime($tokensPath) : $assetV;
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css?v=' . mirror_h($tokensV) . '">';
    echo '<link rel="stylesheet" href="assets/css/mirror.css?v=' . mirror_h($assetV) . '">';
    echo '<link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css?v=' . mirror_h($assetV) . '">';
    echo '</head><body class="m360-public-shell"><div class="m360-wrap">';

    echo '<header class="m360-public-header">';
    echo '<div class="m360-public-header__inner">';
    echo '<a href="./" class="m360-public-brand">';
    if ($logo !== '') {
        echo '<img class="m360-public-brand__logo m360-public-logo" src="' . mirror_h($logo) . '" alt="MOGHAREH360">';
    }
    echo '<span class="m360-public-brand__text">';
    echo '<span class="m360-public-brand__title m360-brand-latin" lang="en" dir="ltr">MOGHAREH360</span>';
    echo '<span class="m360-public-brand__tagline">' . mirror_h(mirror_brand_tagline()) . '</span>';
    echo '</span></a>';
    echo '<nav class="m360-public-nav" aria-label="منوی اصلی">';
    $links = [
        'index' => ['./', 'خانه'],
        'customer' => ['customer-request.php', 'مشتری'],
        'staff' => ['staff-login.php', 'پرسنل'],
    ];
    foreach ($links as $key => [$href, $label]) {
        $cls = 'm360-public-nav__link' . ($activeNav === $key ? ' is-active' : '');
        echo '<a class="' . $cls . '" href="' . mirror_h($href) . '">' . mirror_h($label) . '</a>';
    }
    echo '</nav></div></header>';
    echo '<main class="m360-public-main">';
}

function mirror_render_foot(): void
{
    echo '</main>';
    echo '<footer class="m360-footer">';
    echo '© <span class="m360-brand-latin" lang="en" dir="ltr">MOGHAREH360</span> — تمام حقوق محفوظ است.';
    echo '<div class="m360-install-hint">برای نصب اپلیکیشن، از منوی مرورگر «افزودن به صفحه اصلی» را انتخاب کنید.</div>';
    echo '</footer></div>';
    echo '<script src="assets/js/m360-pwa.js?v=20260803" defer></script>';
    echo '<script>(function(){window.addEventListener("pageshow",function(e){if(e.persisted){window.location.reload();}});})();</script>';
    echo '</body></html>';
}

/** @return list<string> */
function m360_hall_return_tabs(): array
{
    return ['overview', 'assignments', 'requests', 'external', 'timeline', 'quality'];
}

/**
 * Parse allowlisted Hall JobCard return context from GET (navigation only).
 *
 * @return array{ok:bool,jobcard_id:int,tab:string,href:string,label:string}
 */
function m360_hall_parse_return_context(): array
{
    $jobcardId = (int)($_GET['return_jobcard_id'] ?? 0);
    $tab = strtolower(trim((string)($_GET['return_tab'] ?? '')));
    if ($jobcardId < 1 || !in_array($tab, m360_hall_return_tabs(), true)) {
        return [
            'ok' => false,
            'jobcard_id' => 0,
            'tab' => '',
            'href' => 'erp-operations-home.php',
            'label' => 'بازگشت به عملیات تعمیرگاه',
        ];
    }

    return [
        'ok' => true,
        'jobcard_id' => $jobcardId,
        'tab' => $tab,
        'href' => 'erp-hall-jobcard-detail.php?jobcard_id=' . $jobcardId . '&tab=' . rawurlencode($tab),
        'label' => 'بازگشت به جزئیات پرونده',
    ];
}

/**
 * Append controlled return context to an existing relative URL.
 */
function m360_hall_append_return_context(string $url, int $jobcardId, string $tab): string
{
    $tab = strtolower(trim($tab));
    if ($jobcardId < 1 || !in_array($tab, m360_hall_return_tabs(), true)) {
        return $url;
    }
    $sep = str_contains($url, '?') ? '&' : '?';

    return $url . $sep . 'return_jobcard_id=' . $jobcardId . '&return_tab=' . rawurlencode($tab);
}

/**
 * Compact return bar for Hall child operational pages.
 */
function m360_hall_render_return_nav(string $sectionFa = ''): void
{
    $ctx = m360_hall_parse_return_context();
    echo '<nav class="m360-hall-return-nav" aria-label="بازگشت" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.55rem;margin:0 0 .85rem;padding:.55rem .7rem;border:1px solid rgba(34,197,94,.22);border-radius:12px;background:rgba(15,23,42,.45);color:#e5e7eb;">';
    if ($sectionFa !== '') {
        echo '<span style="font-size:.82rem;color:#9ca3af;">عملیات تعمیرگاه › جزئیات پرونده › ' . mirror_h($sectionFa) . '</span>';
    } else {
        echo '<span style="font-size:.82rem;color:#9ca3af;">ناوبری بازگشت</span>';
    }
    echo '<a class="m360-btn" href="' . mirror_h($ctx['href']) . '">' . mirror_h($ctx['label']) . '</a>';
    echo '</nav>';
}
