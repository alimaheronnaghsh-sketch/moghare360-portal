<?php
declare(strict_types=1);

/**
 * MOGHARE360 Phase 4 — Central Design System stylesheet loader.
 * Canonical token file: assets/moghare360-ui/moghare360-design-tokens.css
 */

function m360_ds_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_ds_asset_version(string $relativePath): string
{
    $full = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (is_file($full)) {
        return (string)filemtime($full);
    }

    return 'ds-v1';
}

/**
 * Ordered stylesheet stack for operational pages.
 *
 * @return list<string> relative hrefs without query
 */
function m360_ds_stylesheet_stack(string $variant = 'ops'): array
{
    $stack = [
        'assets/moghare360-ui/moghare360-design-tokens.css',
        'assets/moghare360-ui/moghare360-components.css',
        'assets/css/m360-design-system.css',
    ];

    if ($variant === 'ops' || $variant === 'ops+shell') {
        $stack[] = 'assets/css/m360-operational-shell.css';
    }

    if ($variant === 'mirror' || $variant === 'public') {
        $stack[] = 'assets/css/mirror.css';
        $stack[] = 'assets/css/moghare360-v1-luxury-ui.css';
    }

    return $stack;
}

/**
 * Emit <link> tags for the canonical design system.
 */
function m360_ds_render_stylesheets(string $variant = 'ops'): void
{
    foreach (m360_ds_stylesheet_stack($variant) as $href) {
        $v = m360_ds_asset_version($href);
        echo '<link rel="stylesheet" href="' . m360_ds_h($href) . '?v=' . m360_ds_h($v) . '">' . "\n";
    }
}

/**
 * Canonical token path (Owner single source).
 */
function m360_ds_canonical_tokens_href(): string
{
    return 'assets/moghare360-ui/moghare360-design-tokens.css';
}
