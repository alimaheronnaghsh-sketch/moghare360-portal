<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP UI Shell Render Helper
 *
 * Mission 32 — UI shell only. No auth change. No DB write. No permission model change.
 */

/**
 * @return list<string>
 */
function moghare360_shell_allowed_role_modes(): array
{
    return ['owner', 'service', 'reception', 'finance', 'qc'];
}

function moghare360_shell_normalize_role_mode(string $roleMode): string
{
    $roleMode = strtolower(trim($roleMode));

    return in_array($roleMode, moghare360_shell_allowed_role_modes(), true) ? $roleMode : 'owner';
}

function moghare360_shell_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * @return array<string, array<string, mixed>>
 */
function moghare360_get_shell_menu(string $roleMode = 'owner'): array
{
    $roleMode = moghare360_shell_normalize_role_mode($roleMode);

    $modules = [
        'dashboard' => [
            'label' => 'داشبورد',
            'label_en' => 'Dashboard',
            'icon' => 'DB',
            'section' => 'core',
            'href' => 'erp-app-shell-demo.php?role=' . rawurlencode($roleMode) . '&module=dashboard',
            'roles' => ['owner', 'service', 'reception', 'finance', 'qc'],
        ],
        'customers' => [
            'label' => 'مشتریان',
            'label_en' => 'Customers',
            'icon' => 'CU',
            'section' => 'core',
            'href' => '#customers-demo',
            'roles' => ['owner', 'reception'],
        ],
        'vehicles' => [
            'label' => 'خودروها',
            'label_en' => 'Vehicles',
            'icon' => 'VH',
            'section' => 'core',
            'href' => '#vehicles-demo',
            'roles' => ['owner', 'service', 'reception'],
        ],
        'jobcards' => [
            'label' => 'کارت‌های کار',
            'label_en' => 'JobCards',
            'icon' => 'JC',
            'section' => 'operations',
            'href' => 'erp-jobcard-part-readonly-list.php',
            'roles' => ['owner', 'service', 'reception', 'qc'],
        ],
        'service_operations' => [
            'label' => 'عملیات سرویس',
            'label_en' => 'Service Operations',
            'icon' => 'SO',
            'section' => 'operations',
            'href' => 'erp-service-operation-readonly-list.php',
            'roles' => ['owner', 'service'],
        ],
        'parts_inventory' => [
            'label' => 'قطعات / انبار',
            'label_en' => 'Parts / Inventory',
            'icon' => 'PI',
            'section' => 'operations',
            'href' => 'erp-stock-readonly-list.php',
            'roles' => ['owner', 'service'],
        ],
        'purchase_requests' => [
            'label' => 'درخواست خرید',
            'label_en' => 'Purchase Requests',
            'icon' => 'PR',
            'section' => 'operations',
            'href' => 'erp-purchase-request-readonly-list.php',
            'roles' => ['owner', 'service'],
        ],
        'payments' => [
            'label' => 'پرداخت‌ها',
            'label_en' => 'Payments',
            'icon' => 'PY',
            'section' => 'finance',
            'href' => 'erp-payment-readonly-list.php',
            'roles' => ['owner', 'reception', 'finance'],
        ],
        'qc' => [
            'label' => 'کنترل کیفیت',
            'label_en' => 'QC',
            'icon' => 'QC',
            'section' => 'delivery',
            'href' => 'erp-qc-check.php',
            'roles' => ['owner', 'qc'],
        ],
        'delivery' => [
            'label' => 'تحویل',
            'label_en' => 'Delivery',
            'icon' => 'DL',
            'section' => 'delivery',
            'href' => 'erp-delivery-control.php',
            'roles' => ['owner', 'reception', 'qc'],
        ],
        'soft_run_gate' => [
            'label' => 'دروازه Soft Run',
            'label_en' => 'Soft Run Gate',
            'icon' => 'SR',
            'section' => 'delivery',
            'href' => 'erp-soft-run-readiness.php',
            'roles' => ['owner', 'service', 'reception', 'finance', 'qc'],
        ],
    ];

    $visible = [];

    foreach ($modules as $key => $module) {
        $roles = $module['roles'] ?? [];

        if (in_array($roleMode, $roles, true)) {
            $visible[$key] = $module;
        }
    }

    return $visible;
}

/**
 * @return array<string, string>
 */
function moghare360_shell_section_labels(): array
{
    return [
        'core' => 'هسته عملیات',
        'operations' => 'عملیات تعمیرگاه',
        'finance' => 'مالی',
        'delivery' => 'کیفیت و تحویل',
    ];
}

function moghare360_shell_role_label(string $roleMode): string
{
    $map = [
        'owner' => 'مالک سیستم',
        'service' => 'سرویس / تعمیرکار',
        'reception' => 'پذیرش',
        'finance' => 'مالی',
        'qc' => 'کنترل کیفیت',
    ];

    return $map[moghare360_shell_normalize_role_mode($roleMode)] ?? 'مالک سیستم';
}

function moghare360_shell_asset_base(): string
{
    return 'assets/moghare360-ui';
}

/**
 * @param array<string, array<string, mixed>> $menu
 */
function moghare360_shell_render_navigation(string $activeModule, array $menu): void
{
    $sections = moghare360_shell_section_labels();
    $grouped = [];

    foreach ($menu as $key => $item) {
        $section = (string)($item['section'] ?? 'core');
        $grouped[$section][$key] = $item;
    }

    echo '<nav class="m360-shell-nav" aria-label="منوی اصلی">';

    foreach ($sections as $sectionKey => $sectionLabel) {
        if (!isset($grouped[$sectionKey])) {
            continue;
        }

        echo '<div class="m360-shell-nav-section">';
        echo '<p class="m360-shell-nav-section-title">' . moghare360_shell_h($sectionLabel) . '</p>';
        echo '<ul class="m360-shell-nav-list">';

        foreach ($grouped[$sectionKey] as $moduleKey => $item) {
            $isActive = $moduleKey === $activeModule;
            $classes = 'm360-shell-nav-link' . ($isActive ? ' is-active' : '');

            echo '<li class="m360-shell-nav-item">';
            echo '<a class="' . moghare360_shell_h($classes) . '" href="' . moghare360_shell_h((string)$item['href']) . '" data-module="' . moghare360_shell_h($moduleKey) . '">';
            echo '<span class="m360-shell-nav-icon">' . moghare360_shell_h((string)$item['icon']) . '</span>';
            echo '<span class="m360-shell-nav-label">' . moghare360_shell_h((string)$item['label']) . '</span>';
            echo '</a></li>';
        }

        echo '</ul></div>';
    }

    echo '</nav>';
}

function moghare360_render_shell_start(string $pageTitle, string $activeModule, string $roleMode = 'owner'): void
{
    $roleMode = moghare360_shell_normalize_role_mode($roleMode);
    $menu = moghare360_get_shell_menu($roleMode);
    $assetBase = moghare360_shell_asset_base();
    $roleLabel = moghare360_shell_role_label($roleMode);

    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="fa" dir="rtl">' . "\n";
    echo '<head>' . "\n";
    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    echo '<title>' . moghare360_shell_h($pageTitle) . ' — MOGHARE360 ERP</title>' . "\n";
    echo '<link rel="stylesheet" href="' . moghare360_shell_h($assetBase . '/moghare360-design-tokens.css') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . moghare360_shell_h($assetBase . '/moghare360-rtl.css') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . moghare360_shell_h($assetBase . '/moghare360-layout.css') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . moghare360_shell_h($assetBase . '/moghare360-components.css') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . moghare360_shell_h($assetBase . '/moghare360-shell.css') . '">' . "\n";
    echo '</head>' . "\n";
    echo '<body class="m360-rtl">' . "\n";
    echo '<div class="m360-shell-demo-banner">APP SHELL DEMO — UI ONLY — NO DB — NO AUTH CHANGE — ROLE PLACEHOLDER: ' . moghare360_shell_h(strtoupper($roleMode)) . '</div>' . "\n";
    echo '<div class="m360-shell-overlay" data-m360-shell-overlay aria-hidden="true"></div>' . "\n";
    echo '<div class="m360-shell-app" data-m360-shell>' . "\n";

    echo '<aside class="m360-shell-sidebar" aria-label="نوار کناری">';
    echo '<div class="m360-shell-sidebar-brand">';
    echo '<div class="m360-shell-sidebar-logo">M3</div>';
    echo '<div><div class="m360-shell-sidebar-title">مغاره ۳۶۰</div><div class="m360-shell-sidebar-subtitle">Soft Run ERP</div></div>';
    echo '</div>';

    moghare360_shell_render_navigation($activeModule, $menu);

    echo '<div class="m360-shell-sidebar-footer">Mission 32 Shell — ' . moghare360_shell_h($roleLabel) . '</div>';
    echo '</aside>';

    echo '<header class="m360-shell-topbar">';
    echo '<div class="m360-shell-topbar-start">';
    echo '<button type="button" class="m360-shell-topbar-toggle is-desktop-collapse" data-m360-shell-toggle aria-label="باز و بسته کردن منو">☰</button>';
    echo '<div class="m360-shell-topbar-title-wrap">';
    echo '<h1 class="m360-shell-topbar-title">' . moghare360_shell_h($pageTitle) . '</h1>';
    echo '<p class="m360-shell-topbar-breadcrumb">MOGHARE360 / Soft Run / ' . moghare360_shell_h($roleLabel) . '</p>';
    echo '</div></div>';
    echo '<div class="m360-shell-topbar-end">';
    echo '<span class="m360-shell-status-pill is-soft-run">Soft Run Prototype</span>';
    echo '<span class="m360-shell-status-pill is-prototype">Local Only</span>';
    echo '<span class="m360-shell-user-chip"><span class="m360-shell-user-avatar">U</span><span>user_id 10001 placeholder</span></span>';
    echo '</div></header>';

    echo '<main class="m360-shell-content"><div class="m360-content-container">';
}

function moghare360_render_shell_end(): void
{
    $assetBase = moghare360_shell_asset_base();

    echo '</div></main>' . "\n";
    echo '</div>' . "\n";
    echo '<script src="' . moghare360_shell_h($assetBase . '/moghare360-shell.js') . '" defer></script>' . "\n";
    echo '</body></html>' . "\n";
}
