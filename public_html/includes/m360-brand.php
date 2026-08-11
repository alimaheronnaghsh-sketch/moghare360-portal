<?php
declare(strict_types=1);

/**
 * MOGHARE360 — Canonical customer-facing brand (MAHIN 360°)
 *
 * Technical prefixes (m360_*, p360_*, moghare360_ERP, ir.moghare360.app) stay unchanged.
 * All customer-visible product branding should use these helpers.
 */

if (!function_exists('m360_brand_name_fa')) {
    function m360_brand_name_fa(): string
    {
        return 'ماهین 360°';
    }

    function m360_brand_name_en(): string
    {
        return 'MAHIN 360°';
    }

    function m360_brand_short_name(): string
    {
        return 'MAHIN360';
    }

    function m360_brand_tagline_fa(): string
    {
        return 'سامانه خدمات خودرو';
    }

    function m360_brand_logo_path(): string
    {
        return 'assets/brand/mahin360-logo.png';
    }

    function m360_brand_logo_compact_path(): string
    {
        return 'assets/brand/mahin360-logo-compact.png';
    }

    function m360_brand_favicon_32_path(): string
    {
        return 'assets/icons/favicon-32.png';
    }

    function m360_brand_favicon_48_path(): string
    {
        return 'assets/icons/favicon-48.png';
    }

    function m360_brand_apple_touch_icon_path(): string
    {
        return 'assets/icons/apple-touch-icon.png';
    }

    function m360_brand_pwa_192_path(): string
    {
        return 'assets/icons/icon-192.png';
    }

    function m360_brand_pwa_512_path(): string
    {
        return 'assets/icons/icon-512.png';
    }

    /** Browser / page title helper. */
    function m360_brand_title(string $pageFa = ''): string
    {
        $pageFa = trim($pageFa);
        if ($pageFa === '') {
            return m360_brand_name_fa() . ' — ' . m360_brand_name_en();
        }

        return $pageFa . ' | ' . m360_brand_name_fa();
    }

    function m360_brand_h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Emit standard favicon / apple / manifest head links.
     */
    function m360_brand_render_head_links(): void
    {
        echo '<link rel="icon" type="image/png" sizes="32x32" href="' . m360_brand_h(m360_brand_favicon_32_path()) . '">';
        echo '<link rel="icon" type="image/png" sizes="48x48" href="' . m360_brand_h(m360_brand_favicon_48_path()) . '">';
        echo '<link rel="apple-touch-icon" href="' . m360_brand_h(m360_brand_apple_touch_icon_path()) . '">';
        echo '<meta name="application-name" content="' . m360_brand_h(m360_brand_short_name()) . '">';
        echo '<meta name="apple-mobile-web-app-title" content="' . m360_brand_h(m360_brand_short_name()) . '">';
    }
}
