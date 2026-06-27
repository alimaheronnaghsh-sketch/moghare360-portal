<?php
declare(strict_types=1);

/**
 * MOGHARE360 P10 — Product navigation registry (read-only route catalog P1–P10).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_NAV_RC_VERSION = 'MOGHARE360-V1-RC';

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function m360_nav_route(
    string $key,
    string $phase,
    string $titleFa,
    string $titleEn,
    string $url,
    string $category,
    string $accessType,
    string $method = 'GET',
    array $flags = [],
    string $notes = ''
): array {
    return [
        'route_key' => $key,
        'phase_code' => $phase,
        'title_fa' => $titleFa,
        'title_en' => $titleEn,
        'url' => $url,
        'category' => $category,
        'access_type' => $accessType,
        'expected_method' => strtoupper($method),
        'is_demo_entry' => !empty($flags['demo']),
        'is_owner_entry' => !empty($flags['owner']),
        'is_staff_entry' => !empty($flags['staff']),
        'is_customer_entry' => !empty($flags['customer']),
        'is_api' => !empty($flags['api']),
        'notes' => $notes,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_nav_registry(): array
{
    static $registry = null;
    if ($registry !== null) {
        return $registry;
    }

    $s = static fn(bool $v = true): array => ['staff' => $v];
    $c = static fn(bool $v = true): array => ['customer' => $v];
    $a = static fn(bool $v = true): array => ['api' => $v];
    $d = static fn(bool $v = true): array => ['demo' => $v, 'staff' => $v];
    $o = static fn(bool $v = true): array => ['owner' => $v, 'staff' => $v];

    $registry = [
        // P1 — Public / Intake
        m360_nav_route('p1_customer_request', 'P1', 'درخواست آنلاین مشتری', 'Customer Online Request', 'customer-request.php', 'Public / Intake', 'public', 'GET', $c()),
        m360_nav_route('p1_reception_requests', 'P1', 'داشبورد درخواست‌های آنلاین', 'Reception Online Requests', 'erp-reception-online-requests.php', 'Public / Intake', 'staff', 'GET', $s()),
        m360_nav_route('p1_reception_request_detail', 'P1', 'جزئیات درخواست آنلاین', 'Online Request Detail', 'erp-reception-online-request-detail.php', 'Public / Intake', 'staff', 'GET', $s()),
        m360_nav_route('p1_reception_request_accept', 'P1', 'پذیرش درخواست آنلاین', 'Accept Online Request', 'erp-reception-online-request-accept.php', 'Public / Intake', 'staff', 'POST', $s(), 'POST action — workflow gate'),
        m360_nav_route('p1_api_customer_request', 'P1', 'API درخواست مشتری', 'Customer Request API', 'api/customer/request.php', 'API', 'public', 'POST', $a()),

        // P1.5 — Contract
        m360_nav_route('p15_contract_template', 'P1.5', 'پیش‌نمایش قالب قرارداد', 'Contract Template Preview', 'contract-template-intake.php', 'Contract', 'staff', 'GET', $s()),
        m360_nav_route('p15_customer_contract', 'P1.5', 'بررسی قرارداد مشتری', 'Customer Contract Review', 'customer-intake-contract.php', 'Contract', 'customer', 'GET', $c()),
        m360_nav_route('p15_customer_contract_sign', 'P1.5', 'امضای قرارداد مشتری', 'Customer Contract Sign', 'customer-intake-contract-sign.php', 'Contract', 'customer', 'GET', $c()),
        m360_nav_route('p15_intake_contracts', 'P1.5', 'برد قراردادهای پذیرش', 'Intake Contracts Board', 'erp-intake-contracts.php', 'Contract', 'staff', 'GET', $s()),
        m360_nav_route('p15_intake_contract_detail', 'P1.5', 'جزئیات قرارداد پذیرش', 'Intake Contract Detail', 'erp-intake-contract-detail.php', 'Contract', 'staff', 'GET', $s()),
        m360_nav_route('p15_intake_contract_generate', 'P1.5', 'تولید قرارداد', 'Generate Contract', 'erp-intake-contract-generate.php', 'Contract', 'staff', 'POST', $s()),
        m360_nav_route('p15_intake_contract_send', 'P1.5', 'ارسال قرارداد', 'Send Contract', 'erp-intake-contract-send.php', 'Contract', 'staff', 'POST', $s()),
        m360_nav_route('p15_api_contract_otp', 'P1.5', 'API OTP قرارداد', 'Contract OTP API', 'api/customer/contract-send-otp.php', 'API', 'customer', 'POST', $a()),
        m360_nav_route('p15_api_contract_sign', 'P1.5', 'API امضای قرارداد', 'Contract Sign API', 'api/customer/contract-sign.php', 'API', 'customer', 'POST', $a()),

        // P2 — Reception
        m360_nav_route('p2_reception_jobcards', 'P2', 'برد کارت کار پذیرش', 'Reception JobCards', 'erp-reception-jobcards.php', 'Reception', 'staff', 'GET', $s()),
        m360_nav_route('p2_reception_jobcard_detail', 'P2', 'جزئیات کارت کار پذیرش', 'Reception JobCard Detail', 'erp-reception-jobcard-detail.php', 'Reception', 'staff', 'GET', $s()),
        m360_nav_route('p2_reception_jobcard_action', 'P2', 'عملیات کارت کار پذیرش', 'Reception JobCard Action', 'erp-reception-jobcard-action.php', 'Reception', 'staff', 'POST', $s()),

        // P3 — Technical
        m360_nav_route('p3_technical_board', 'P3', 'برد عملیات فنی', 'Technical Board', 'erp-technical-board.php', 'Technical', 'staff', 'GET', $s()),
        m360_nav_route('p3_technical_detail', 'P3', 'جزئیات فنی JobCard', 'Technical JobCard Detail', 'erp-technical-jobcard-detail.php', 'Technical', 'staff', 'GET', $s()),
        m360_nav_route('p3_technical_action', 'P3', 'عملیات فنی JobCard', 'Technical JobCard Action', 'erp-technical-jobcard-action.php', 'Technical', 'staff', 'POST', $s()),

        // P4 — Estimate
        m360_nav_route('p4_estimate_board', 'P4', 'برد برآورد', 'Estimate Board', 'erp-estimate-board.php', 'Estimate', 'staff', 'GET', $s()),
        m360_nav_route('p4_estimate_detail', 'P4', 'جزئیات برآورد', 'Estimate Detail', 'erp-estimate-detail.php', 'Estimate', 'staff', 'GET', $s()),
        m360_nav_route('p4_estimate_action', 'P4', 'عملیات برآورد', 'Estimate Action', 'erp-estimate-action.php', 'Estimate', 'staff', 'POST', $s()),
        m360_nav_route('p4_customer_estimate', 'P4', 'تأیید برآورد مشتری', 'Customer Estimate Approval', 'customer-estimate-approval.php', 'Estimate', 'customer', 'GET', $c()),
        m360_nav_route('p4_customer_estimate_sign', 'P4', 'امضای تأیید برآورد', 'Customer Estimate Sign', 'customer-estimate-approval-sign.php', 'Estimate', 'customer', 'GET', $c()),
        m360_nav_route('p4_api_estimate_otp', 'P4', 'API OTP برآورد', 'Estimate OTP API', 'api/customer/estimate-send-otp.php', 'API', 'customer', 'POST', $a()),
        m360_nav_route('p4_api_estimate_approve', 'P4', 'API تأیید برآورد', 'Estimate Approve API', 'api/customer/estimate-approve.php', 'API', 'customer', 'POST', $a()),

        // P5 — Work Execution
        m360_nav_route('p5_work_board', 'P5', 'برد اجرای کار', 'Work Execution Board', 'erp-work-execution-board.php', 'Work Execution', 'staff', 'GET', $s()),
        m360_nav_route('p5_work_detail', 'P5', 'جزئیات اجرای کار', 'Work Execution Detail', 'erp-work-execution-detail.php', 'Work Execution', 'staff', 'GET', $s()),
        m360_nav_route('p5_work_action', 'P5', 'عملیات اجرای کار', 'Work Execution Action', 'erp-work-execution-action.php', 'Work Execution', 'staff', 'POST', $s()),

        // P6 — QC
        m360_nav_route('p6_qc_board', 'P6', 'برد QC', 'QC Board', 'erp-qc-board.php', 'QC', 'staff', 'GET', $s()),
        m360_nav_route('p6_qc_detail', 'P6', 'جزئیات QC', 'QC Detail', 'erp-qc-detail.php', 'QC', 'staff', 'GET', $s()),
        m360_nav_route('p6_qc_action', 'P6', 'عملیات QC', 'QC Action', 'erp-qc-action.php', 'QC', 'staff', 'POST', $s()),

        // P7 — Final Invoice / Delivery
        m360_nav_route('p7_final_invoice_board', 'P7', 'برد فاکتور نهایی', 'Final Invoice Board', 'erp-final-invoice-board.php', 'Final Invoice / Delivery', 'staff', 'GET', $s()),
        m360_nav_route('p7_final_invoice_detail', 'P7', 'جزئیات فاکتور نهایی', 'Final Invoice Detail', 'erp-final-invoice-detail.php', 'Final Invoice / Delivery', 'staff', 'GET', $s()),
        m360_nav_route('p7_final_invoice_action', 'P7', 'عملیات فاکتور نهایی', 'Final Invoice Action', 'erp-final-invoice-action.php', 'Final Invoice / Delivery', 'staff', 'POST', $s()),
        m360_nav_route('p7_settlement_detail', 'P7', 'جزئیات تسویه', 'Settlement Detail', 'erp-settlement-detail.php', 'Final Invoice / Delivery', 'staff', 'GET', $s()),
        m360_nav_route('p7_settlement_action', 'P7', 'عملیات تسویه', 'Settlement Action', 'erp-settlement-action.php', 'Final Invoice / Delivery', 'staff', 'POST', $s()),
        m360_nav_route('p7_customer_delivery_review', 'P7', 'بررسی تحویل مشتری', 'Customer Delivery Review', 'customer-delivery-review.php', 'Final Invoice / Delivery', 'customer', 'GET', $c()),
        m360_nav_route('p7_customer_delivery_sign', 'P7', 'امضای تحویل مشتری', 'Customer Delivery Sign', 'customer-delivery-sign.php', 'Final Invoice / Delivery', 'customer', 'GET', $c()),
        m360_nav_route('p7_api_delivery_otp', 'P7', 'API OTP تحویل', 'Delivery OTP API', 'api/customer/delivery-send-otp.php', 'API', 'customer', 'POST', $a()),
        m360_nav_route('p7_api_delivery_confirm', 'P7', 'API تأیید تحویل', 'Delivery Confirm API', 'api/customer/delivery-confirm.php', 'API', 'customer', 'POST', $a()),

        // P8 — Management
        m360_nav_route('p8_management_dashboard', 'P8', 'داشبورد مدیریت', 'Management Dashboard', 'erp-management-dashboard.php', 'Management', 'staff', 'GET', $o()),
        m360_nav_route('p8_owner_control', 'P8', 'مرکز کنترل مالک', 'Owner Control Center', 'erp-owner-control-center.php', 'Management', 'staff', 'GET', $o()),
        m360_nav_route('p8_operational_kpi', 'P8', 'KPI عملیاتی', 'Operational KPI', 'erp-operational-kpi.php', 'Management', 'staff', 'GET', $o()),
        m360_nav_route('p8_jobcard_timeline', 'P8', 'Timeline JobCard', 'JobCard Timeline', 'erp-jobcard-timeline.php', 'Management', 'staff', 'GET', $o()),
        m360_nav_route('p8_bottleneck', 'P8', 'مانیتور گلوگاه', 'Bottleneck Monitor', 'erp-bottleneck-monitor.php', 'Management', 'staff', 'GET', $o()),
        m360_nav_route('p8_financial_summary', 'P8', 'خلاصه کنترل مالی', 'Financial Control Summary', 'erp-financial-control-summary.php', 'Management', 'staff', 'GET', $o()),
        m360_nav_route('p8_api_kpi', 'P8', 'API خلاصه KPI', 'KPI Summary API', 'api/management/kpi-summary.php', 'API', 'staff', 'GET', array_merge($a(), $o())),
        m360_nav_route('p8_api_bottleneck', 'P8', 'API گلوگاه', 'Bottleneck API', 'api/management/bottleneck-summary.php', 'API', 'staff', 'GET', array_merge($a(), $o())),
        m360_nav_route('p8_api_timeline', 'P8', 'API Timeline', 'Timeline API', 'api/management/jobcard-timeline.php', 'API', 'staff', 'GET', array_merge($a(), $o())),

        // P9 — Soft Run / Demo
        m360_nav_route('p9_soft_run_center', 'P9', 'کنترل‌سنتر Soft Run', 'Soft Run Control Center', 'erp-soft-run-control-center.php', 'Soft Run / Demo', 'staff', 'GET', $d()),
        m360_nav_route('p9_demo_scenario', 'P9', 'سناریوی End-to-End Demo', 'E2E Demo Scenario', 'erp-end-to-end-demo-scenario.php', 'Soft Run / Demo', 'staff', 'GET', $d()),
        m360_nav_route('p9_soft_run_checklist', 'P9', 'چک‌لیست Soft Run', 'Soft Run Checklist', 'erp-soft-run-checklist.php', 'Soft Run / Demo', 'staff', 'POST', $d(), 'POST فقط soft_run_checklist'),
        m360_nav_route('p9_demo_flow_map', 'P9', 'نقشه Demo Flow', 'Demo Flow Map', 'erp-demo-flow-map.php', 'Soft Run / Demo', 'staff', 'GET', $d()),
        m360_nav_route('p9_demo_readiness', 'P9', 'گزارش Demo Readiness', 'Demo Readiness Report', 'erp-demo-readiness-report.php', 'Soft Run / Demo', 'staff', 'GET', $d()),
        m360_nav_route('p9_api_demo_status', 'P9', 'API وضعیت Demo', 'Demo Scenario API', 'api/soft-run/demo-scenario-status.php', 'API', 'staff', 'GET', array_merge($a(), $d())),
        m360_nav_route('p9_api_readiness', 'P9', 'API Readiness', 'Readiness Summary API', 'api/soft-run/readiness-summary.php', 'API', 'staff', 'GET', array_merge($a(), $d())),

        // P10 — Release / RC
        m360_nav_route('p10_product_home', 'P10', 'خانه محصول MOGHARE360', 'Product Home', 'erp-product-home.php', 'Release / RC', 'staff', 'GET', ['staff' => true, 'owner' => true, 'demo' => true]),
        m360_nav_route('p10_demo_package_rc', 'P10', 'Demo Package RC', 'Demo Package RC', 'erp-demo-package-rc.php', 'Release / RC', 'staff', 'GET', $d()),
        m360_nav_route('p10_release_readiness', 'P10', 'آمادگی Release', 'Release Readiness', 'erp-release-readiness.php', 'Release / RC', 'staff', 'GET', $o()),
        m360_nav_route('p10_route_map', 'P10', 'نقشه Route', 'Route Map', 'erp-route-map.php', 'Release / RC', 'staff', 'GET', $s()),
        m360_nav_route('p10_link_audit', 'P10', 'ممیزی لینک', 'Link Audit', 'erp-link-audit.php', 'Release / RC', 'staff', 'GET', $s()),
    ];

    return $registry;
}

/**
 * @return array<string, array<string, mixed>>
 */
function m360_nav_registry_by_key(): array
{
    $map = [];
    foreach (m360_nav_registry() as $route) {
        $map[(string)$route['route_key']] = $route;
    }
    return $map;
}

/**
 * @return list<string>
 */
function m360_nav_phases(): array
{
    return ['P1', 'P1.5', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9', 'P10'];
}

function m360_nav_public_root(): string
{
    return dirname(__DIR__);
}

function m360_nav_resolve_file_path(string $url): string
{
    $url = ltrim(str_replace('\\', '/', $url), '/');
    return m360_nav_public_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $url);
}

function m360_nav_file_exists(string $url): bool
{
    return is_file(m360_nav_resolve_file_path($url));
}

/**
 * @return list<array<string, mixed>>
 */
function m360_nav_demo_entries(): array
{
    return array_values(array_filter(m360_nav_registry(), static fn(array $r): bool => !empty($r['is_demo_entry'])));
}

/**
 * @return list<array<string, mixed>>
 */
function m360_nav_owner_entries(): array
{
    return array_values(array_filter(m360_nav_registry(), static fn(array $r): bool => !empty($r['is_owner_entry'])));
}

function m360_nav_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_nav_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return list<array{href:string,label:string}> */
function m360_nav_rc_links(): array
{
    return [
        ['href' => 'erp-product-home.php', 'label' => 'خانه محصول'],
        ['href' => 'erp-demo-package-rc.php', 'label' => 'Demo Package RC'],
        ['href' => 'erp-release-readiness.php', 'label' => 'Release Readiness'],
        ['href' => 'erp-route-map.php', 'label' => 'Route Map'],
        ['href' => 'erp-link-audit.php', 'label' => 'Link Audit'],
        ['href' => 'erp-soft-run-control-center.php', 'label' => 'Soft Run'],
    ];
}

function m360_nav_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'PASS' => 'pass',
        'WARNING' => 'warn',
        'BLOCKED' => 'block',
        default => 'warn',
    };
}
