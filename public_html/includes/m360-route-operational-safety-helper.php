<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.8-C — Route Map operational safety classification (UI-only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';

const M360_ROUTE_OPS_VIEW_OPERATIONAL = 'operational';
const M360_ROUTE_OPS_VIEW_TECHNICAL = 'technical';

const M360_ROUTE_OPS_CLASS_OPERATIONAL = 'operational';
const M360_ROUTE_OPS_CLASS_GUIDED = 'guided';
const M360_ROUTE_OPS_CLASS_ACTION = 'action';
const M360_ROUTE_OPS_CLASS_API = 'api';
const M360_ROUTE_OPS_CLASS_CUSTOMER = 'customer';
const M360_ROUTE_OPS_CLASS_DIAGNOSTIC = 'diagnostic';
const M360_ROUTE_OPS_CLASS_RUNTIME_HOLD = 'runtime_hold';

/** @var array<string, string> */
const M360_ROUTE_OPS_BADGE_FA = [
    M360_ROUTE_OPS_CLASS_OPERATIONAL => 'قابل ورود',
    M360_ROUTE_OPS_CLASS_GUIDED => 'راهنمای مسیر',
    M360_ROUTE_OPS_CLASS_ACTION => 'عملیات داخلی',
    M360_ROUTE_OPS_CLASS_API => 'API سیستم',
    M360_ROUTE_OPS_CLASS_CUSTOMER => 'مسیر مشتری',
    M360_ROUTE_OPS_CLASS_DIAGNOSTIC => 'تشخیصی / مدیریتی',
    M360_ROUTE_OPS_CLASS_RUNTIME_HOLD => 'نیازمند بررسی عملیاتی',
];

/** @var array<string, string> */
const M360_ROUTE_OPS_REASON_FA = [
    M360_ROUTE_OPS_CLASS_GUIDED => 'این صفحه از مسیر تابلو، فهرست یا پرونده مربوط باز می‌شود.',
    M360_ROUTE_OPS_CLASS_ACTION => 'ورود مستقیم مجاز نیست؛ این مسیر فقط از فرم یا پرونده مربوط فراخوانی می‌شود.',
    M360_ROUTE_OPS_CLASS_API => 'فقط برای مصرف سیستم / درخواست برنامه‌ای',
    M360_ROUTE_OPS_CLASS_CUSTOMER => 'مسیر مشتری — خارج از میز کار عملیاتی پرسنل',
    M360_ROUTE_OPS_CLASS_RUNTIME_HOLD => 'فایل وجود دارد، اما مسیر برای عملیات روزانه آماده یا قابل اتکا نیست.',
    M360_ROUTE_OPS_CLASS_DIAGNOSTIC => 'ابزار تشخیص، انتشار یا دمو — نه کار روزانه خط تولید',
    M360_ROUTE_OPS_CLASS_OPERATIONAL => 'ورود مستقیم از میز کار یا One-Day Run',
];

/** @var list<string> */
const M360_ROUTE_OPS_RUNTIME_NOT_READY_URLS = [
    'erp-jobcard-part-use.php',
    'erp-payment-tracking.php',
];

function m360_route_ops_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_route_ops_normalize_url(string $url): string
{
    return ltrim(str_replace('\\', '/', trim($url)), '/');
}

function m360_route_ops_is_action_endpoint(string $url): bool
{
    $url = m360_route_ops_normalize_url($url);
    if ($url === '') {
        return true;
    }
    if (str_ends_with($url, '-action.php')) {
        return true;
    }

    return in_array($url, [
        'erp-reception-online-request-accept.php',
        'erp-reception-jobcard-action.php',
        'erp-technical-jobcard-action.php',
        'erp-work-execution-action.php',
        'erp-qc-action.php',
        'erp-final-invoice-action.php',
        'erp-settlement-action.php',
        'erp-estimate-action.php',
        'erp-intake-contract-generate.php',
        'erp-intake-contract-send.php',
    ], true);
}

function m360_route_ops_is_guided_detail(string $url): bool
{
    $url = m360_route_ops_normalize_url($url);

    return str_ends_with($url, '-detail.php') || $url === 'erp-jobcard-timeline.php';
}

function m360_route_ops_is_customer_path(string $url): bool
{
    $url = m360_route_ops_normalize_url($url);

    return str_starts_with($url, 'customer-') || $url === 'customer-request.php';
}

function m360_route_ops_is_diagnostic_route(array $route): bool
{
    $url = m360_route_ops_normalize_url((string)($route['url'] ?? ''));
    $category = (string)($route['category'] ?? '');

    if (!empty($route['is_demo_entry'])) {
        return true;
    }
    if (!empty($route['is_owner_entry']) && str_starts_with((string)($route['phase_code'] ?? ''), 'P8')) {
        return true;
    }
    if (str_contains($category, 'Release / RC') || str_contains($category, 'Soft Run / Demo')) {
        return true;
    }

    return in_array($url, [
        'erp-route-map.php',
        'erp-link-audit.php',
        'erp-release-readiness.php',
        'erp-demo-package-rc.php',
        'erp-product-home.php',
    ], true);
}

function m360_route_ops_is_runtime_not_ready(string $url): bool
{
    return in_array(m360_route_ops_normalize_url($url), M360_ROUTE_OPS_RUNTIME_NOT_READY_URLS, true);
}

/**
 * @param array<string, mixed> $route
 * @return array<string, mixed>
 */
function m360_route_ops_classify(array $route): array
{
    $url = m360_route_ops_normalize_url((string)($route['url'] ?? ''));
    $method = strtoupper((string)($route['expected_method'] ?? 'GET'));
    $fileExists = !empty($route['file_exists']);

    if (m360_route_ops_is_runtime_not_ready($url)) {
        $class = M360_ROUTE_OPS_CLASS_RUNTIME_HOLD;
    } elseif (!empty($route['is_api']) || str_starts_with($url, 'api/')) {
        $class = M360_ROUTE_OPS_CLASS_API;
    } elseif ($method === 'POST' || m360_route_ops_is_action_endpoint($url)) {
        $class = M360_ROUTE_OPS_CLASS_ACTION;
    } elseif (!empty($route['is_customer_entry']) || m360_route_ops_is_customer_path($url)) {
        $class = M360_ROUTE_OPS_CLASS_CUSTOMER;
    } elseif (m360_route_ops_is_guided_detail($url)) {
        $class = M360_ROUTE_OPS_CLASS_GUIDED;
    } elseif (m360_route_ops_is_diagnostic_route($route)) {
        $class = M360_ROUTE_OPS_CLASS_DIAGNOSTIC;
    } else {
        $class = M360_ROUTE_OPS_CLASS_OPERATIONAL;
    }

    $badgeFa = M360_ROUTE_OPS_BADGE_FA[$class] ?? $class;
    $reasonFa = M360_ROUTE_OPS_REASON_FA[$class] ?? '';
    $fileStatusFa = $fileExists ? 'فایل موجود' : 'فایل ناموجود';

    return array_merge($route, [
        'ops_class' => $class,
        'ops_badge_fa' => $badgeFa,
        'ops_reason_fa' => $reasonFa,
        'ops_file_status_fa' => $fileStatusFa,
        'ops_link_operational' => m360_route_ops_link_operational($class, $fileExists),
        'ops_link_technical' => m360_route_ops_link_technical($class, $fileExists),
        'ops_link_behavior_fa' => m360_route_ops_link_behavior_fa($class, M360_ROUTE_OPS_VIEW_OPERATIONAL, $fileExists),
    ]);
}

function m360_route_ops_link_operational(string $class, bool $fileExists): bool
{
    if (!$fileExists) {
        return false;
    }

    return in_array($class, [M360_ROUTE_OPS_CLASS_OPERATIONAL, M360_ROUTE_OPS_CLASS_DIAGNOSTIC], true);
}

function m360_route_ops_link_technical(string $class, bool $fileExists): bool
{
    if (!$fileExists) {
        return false;
    }

    return in_array($class, [
        M360_ROUTE_OPS_CLASS_OPERATIONAL,
        M360_ROUTE_OPS_CLASS_DIAGNOSTIC,
        M360_ROUTE_OPS_CLASS_CUSTOMER,
    ], true);
}

function m360_route_ops_link_behavior_fa(string $class, string $view, bool $fileExists): string
{
    if (!$fileExists) {
        return 'غیرفعال';
    }

    $clickable = $view === M360_ROUTE_OPS_VIEW_TECHNICAL
        ? m360_route_ops_link_technical($class, $fileExists)
        : m360_route_ops_link_operational($class, $fileExists);

    if ($clickable) {
        return $view === M360_ROUTE_OPS_VIEW_TECHNICAL && !in_array($class, [M360_ROUTE_OPS_CLASS_OPERATIONAL, M360_ROUTE_OPS_CLASS_DIAGNOSTIC], true)
            ? 'فقط فنی'
            : 'فعال';
    }

    return $view === M360_ROUTE_OPS_VIEW_TECHNICAL && in_array($class, [M360_ROUTE_OPS_CLASS_GUIDED, M360_ROUTE_OPS_CLASS_ACTION, M360_ROUTE_OPS_CLASS_API, M360_ROUTE_OPS_CLASS_RUNTIME_HOLD], true)
        ? 'فقط فنی'
        : 'غیرفعال';
}

function m360_route_ops_normalize_view(string $view): string
{
    $view = strtolower(trim($view));

    return $view === M360_ROUTE_OPS_VIEW_TECHNICAL ? M360_ROUTE_OPS_VIEW_TECHNICAL : M360_ROUTE_OPS_VIEW_OPERATIONAL;
}

/**
 * @return list<array<string, mixed>>
 */
function m360_route_ops_enrich_audit_rows(): array
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-route-audit-helper.php';

    $rows = [];
    foreach (m360_route_audit_rows() as $route) {
        $rows[] = m360_route_ops_classify($route);
    }

    return $rows;
}

/**
 * @param array<string, mixed> $row
 */
function m360_route_ops_render_url_cell(array $row, string $view): void
{
    $url = m360_route_ops_normalize_url((string)($row['url'] ?? ''));
    $title = (string)($row['title_fa'] ?? $url);
    $class = (string)($row['ops_class'] ?? '');
    $fileExists = !empty($row['file_exists']);
    $clickable = $view === M360_ROUTE_OPS_VIEW_TECHNICAL
        ? m360_route_ops_link_technical($class, $fileExists)
        : m360_route_ops_link_operational($class, $fileExists);

    echo '<div class="m360-rmap-title">' . m360_route_ops_h($title) . '</div>';
    if ($clickable) {
        $linkClass = $view === M360_ROUTE_OPS_VIEW_TECHNICAL && $class === M360_ROUTE_OPS_CLASS_CUSTOMER
            ? 'm360-rmap-path m360-rmap-path-tech'
            : 'm360-rmap-path m360-rmap-path-active';
        echo '<a class="' . m360_route_ops_h($linkClass) . '" href="' . m360_route_ops_h($url) . '">' . m360_route_ops_h($url) . '</a>';
    } else {
        echo '<code class="m360-rmap-path m360-rmap-path-disabled">' . m360_route_ops_h($url) . '</code>';
    }
}

/**
 * @return array<string, int>
 */
function m360_route_ops_summary_counts(array $rows): array
{
    $counts = [
        'total' => count($rows),
        'file_exists' => 0,
        'ops_clickable' => 0,
        'unsafe_links_prevented' => 0,
    ];

    foreach ($rows as $row) {
        if (!empty($row['file_exists'])) {
            $counts['file_exists']++;
        }
        if (!empty($row['ops_link_operational'])) {
            $counts['ops_clickable']++;
        }
        $class = (string)($row['ops_class'] ?? '');
        if (!in_array($class, [M360_ROUTE_OPS_CLASS_OPERATIONAL, M360_ROUTE_OPS_CLASS_DIAGNOSTIC], true)) {
            $counts['unsafe_links_prevented']++;
        }
    }

    return $counts;
}
