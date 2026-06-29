<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.8-B-A — Shared operational navigation shell + read-only responsibility strip.
 * UI/display only — no workflow or permission changes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_OPS_SHELL_STAFF_HOME = 'erp-staff-home.php';
const M360_OPS_SHELL_PRODUCT_HOME = 'erp-product-home.php';
const M360_OPS_SHELL_ROUTE_MAP = 'erp-route-map.php';
const M360_OPS_SHELL_TIMELINE = 'erp-jobcard-timeline.php';

const M360_OPS_SHELL_MISSING_FA = 'ثبت نشده';
const M360_OPS_SHELL_UNKNOWN_FA = 'نامشخص';

/** @var array<string, string> */
const M360_OPS_SHELL_SECTIONS_FA = [
    'reception_jobcards' => 'JobCardهای پذیرش',
    'intake_contracts' => 'قراردادهای پذیرش',
    'technical_board' => 'برد عملیات فنی',
    'work_board' => 'برد اجرای کار',
    'qc_board' => 'برد QC',
    'estimate_board' => 'برد برآورد',
    'invoice_board' => 'برد فاکتور نهایی',
    'reception_detail' => 'جزئیات پذیرش JobCard',
    'technical_detail' => 'جزئیات فنی JobCard',
    'work_detail' => 'جزئیات اجرای کار',
    'estimate_detail' => 'جزئیات برآورد',
    'invoice_detail' => 'جزئیات فاکتور',
    'qc_detail' => 'جزئیات QC',
    'settlement_detail' => 'جزئیات تسویه',
    'timeline' => 'تایم‌لاین JobCard',
];

/** @var array<string, string> */
const M360_OPS_SHELL_BOARD_BACK = [
    'reception_jobcards' => 'erp-reception-jobcards.php',
    'intake_contracts' => 'erp-intake-contracts.php',
    'technical_board' => 'erp-technical-board.php',
    'work_board' => 'erp-work-execution-board.php',
    'qc_board' => 'erp-qc-board.php',
    'estimate_board' => 'erp-estimate-board.php',
    'invoice_board' => 'erp-final-invoice-board.php',
];

function m360_operational_shell_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_operational_shell_css_href(): string
{
    return 'assets/css/m360-operational-shell.css';
}

/**
 * @param mixed $conn
 */
function m360_operational_shell_resolve_user_name($conn, mixed $userId): string
{
    $id = (int)$userId;
    if ($id < 1) {
        return M360_OPS_SHELL_MISSING_FA;
    }
    if ($conn === false || !is_resource($conn)) {
        return M360_OPS_SHELL_UNKNOWN_FA;
    }
    if (!customer_core_table_exists($conn, 'core_users')) {
        return M360_OPS_SHELL_UNKNOWN_FA;
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 full_name, username FROM dbo.core_users WHERE user_id = ?',
        [$id]
    );
    $row = $rows[0] ?? null;
    if ($row === null) {
        return M360_OPS_SHELL_UNKNOWN_FA;
    }

    $name = trim((string)($row['full_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    $username = trim((string)($row['username'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    return M360_OPS_SHELL_UNKNOWN_FA;
}

function m360_operational_shell_status_label(string $domain, string $status): string
{
    $status = strtoupper(trim($status));
    if ($status === '') {
        return M360_OPS_SHELL_MISSING_FA;
    }

    return match ($domain) {
        'reception', 'jobcard' => m360_operational_shell_load_label('m360_jobcard_workflow_status_label', $status),
        'technical' => m360_operational_shell_load_label('m360_technician_workflow_status_label', $status),
        'work' => m360_operational_shell_load_label('m360_work_status_label', $status),
        'estimate' => m360_operational_shell_load_label('m360_estimate_status_label', $status),
        'invoice' => m360_operational_shell_load_label('m360_fi_status_label', $status),
        'qc' => m360_operational_shell_load_label('m360_qc_status_label', $status),
        'settlement' => m360_operational_shell_settlement_label($status),
        default => $status,
    };
}

function m360_operational_shell_load_label(string $fn, string $status): string
{
    static $loaded = [];
    $map = [
        'm360_jobcard_workflow_status_label' => 'm360-jobcard-workflow-helper.php',
        'm360_technician_workflow_status_label' => 'm360-technician-workflow-helper.php',
        'm360_work_status_label' => 'm360-work-execution-helper.php',
        'm360_estimate_status_label' => 'm360-estimate-helper.php',
        'm360_fi_status_label' => 'm360-final-invoice-helper.php',
        'm360_qc_status_label' => 'm360-qc-helper.php',
    ];
    if (!isset($loaded[$fn]) && isset($map[$fn])) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . $map[$fn];
        $loaded[$fn] = true;
    }
    if (function_exists($fn)) {
        return (string)$fn($status);
    }

    return $status;
}

function m360_operational_shell_settlement_label(string $status): string
{
    $labels = [
        'PAYMENT_PENDING' => 'در انتظار پرداخت',
        'PARTIAL' => 'تسویه جزئی',
        'SETTLED' => 'تسویه کامل',
        'MANAGER_RELEASE' => 'مجوز مدیریتی',
        'BLOCKED' => 'مسدود',
    ];

    return $labels[strtoupper(trim($status))] ?? $status;
}

/**
 * @param list<string> $allowedActions
 */
function m360_operational_shell_next_action_label(string $domain, string $status, array $allowedActions = [], string $gateMessage = ''): string
{
    if ($gateMessage !== '') {
        return 'منتظر بررسی — ' . $gateMessage;
    }

    if ($allowedActions !== []) {
        $first = (string)$allowedActions[0];
        $actionLabels = m360_operational_shell_action_labels($domain);

        return $actionLabels[$first] ?? 'اقدام مجاز: ' . $first;
    }

    $status = strtoupper(trim($status));

    return match ($domain) {
        'reception', 'jobcard' => match ($status) {
            'CLOSED' => 'بسته‌شده',
            'READY_FOR_TECHNICAL' => 'آماده فنی',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        'technical' => match ($status) {
            'WAITING_APPROVAL' => 'نیازمند تأیید',
            'REWORK_REQUIRED' => 'نیازمند بازکاری',
            'READY_FOR_QC' => 'در انتظار QC',
            'COMPLETED' => 'تکمیل فنی',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        'work' => match ($status) {
            'READY_FOR_QC' => 'آماده QC',
            'IN_PROGRESS' => 'در حال انجام',
            'COMPLETED' => 'تکمیل اجرا',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        'estimate' => match ($status) {
            'DRAFT' => 'نیازمند تکمیل برآورد',
            'SENT_TO_CUSTOMER' => 'منتظر تأیید مشتری',
            'CUSTOMER_APPROVED' => 'آماده انجام',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        'invoice' => match ($status) {
            'FINALIZED' => 'آماده تسویه',
            'DRAFT' => 'نیازمند محاسبه',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        'qc' => match ($status) {
            'REWORK_REQUIRED' => 'نیازمند بازکاری',
            'PASSED' => 'آماده تحویل',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        'settlement' => match ($status) {
            'SETTLED' => 'آماده تحویل',
            'PAYMENT_PENDING' => 'در انتظار پرداخت',
            default => M360_OPS_SHELL_UNKNOWN_FA,
        },
        default => M360_OPS_SHELL_UNKNOWN_FA,
    };
}

/**
 * @return array<string, string>
 */
function m360_operational_shell_action_labels(string $domain): array
{
    return match ($domain) {
        'reception', 'jobcard' => [
            'mark_vehicle_arrived' => 'ثبت ورود خودرو',
            'continue_to_technical' => 'آماده فنی',
            'hold' => 'معلق',
        ],
        'technical' => [
            'assign_technician' => 'اختصاص تکنسین',
            'start_diagnosis' => 'شروع عیب‌یابی',
            'ready_for_qc' => 'آماده QC',
        ],
        'work' => [
            'start_work' => 'آماده انجام — شروع کار',
            'ready_for_qc' => 'در انتظار QC',
            'complete_technical_work' => 'تکمیل فنی',
        ],
        'estimate' => [
            'send_to_customer' => 'نیازمند تأیید مشتری',
            'approve_for_work' => 'آماده انجام',
        ],
        'invoice' => [
            'finalize' => 'آماده تسویه',
            'calculate' => 'نیازمند محاسبه',
        ],
        'qc' => [
            'start_qc' => 'شروع QC',
            'pass_qc' => 'تأیید QC',
            'require_rework' => 'نیازمند بازکاری',
        ],
        'settlement' => [
            'mark_settled' => 'آماده تحویل',
            'close_jobcard' => 'بستن پرونده',
        ],
        default => [],
    };
}

/**
 * @param array<string, mixed> $context
 */
function m360_operational_shell_board_context(string $sectionKey): array
{
    return [
        'page_kind' => 'board',
        'section_key' => $sectionKey,
        'section_title_fa' => M360_OPS_SHELL_SECTIONS_FA[$sectionKey] ?? $sectionKey,
        'back_href' => M360_OPS_SHELL_STAFF_HOME,
        'back_label_fa' => 'بازگشت',
        'show_route_map' => true,
        'breadcrumb' => [
            ['label_fa' => 'میز کار', 'href' => M360_OPS_SHELL_STAFF_HOME],
            ['label_fa' => M360_OPS_SHELL_SECTIONS_FA[$sectionKey] ?? $sectionKey, 'href' => ''],
        ],
    ];
}

/**
 * @param array<string, mixed> $context
 */
function m360_operational_shell_detail_context(string $sectionKey, string $backHref, int $recordId = 0): array
{
    return [
        'page_kind' => 'detail',
        'section_key' => $sectionKey,
        'section_title_fa' => M360_OPS_SHELL_SECTIONS_FA[$sectionKey] ?? $sectionKey,
        'back_href' => $backHref,
        'back_label_fa' => 'بازگشت',
        'show_route_map' => true,
        'record_id' => $recordId,
        'breadcrumb' => [
            ['label_fa' => 'میز کار', 'href' => M360_OPS_SHELL_STAFF_HOME],
            ['label_fa' => m360_operational_shell_parent_section_label($sectionKey), 'href' => $backHref],
            ['label_fa' => M360_OPS_SHELL_SECTIONS_FA[$sectionKey] ?? $sectionKey, 'href' => ''],
        ],
    ];
}

function m360_operational_shell_parent_section_label(string $detailSectionKey): string
{
    return match ($detailSectionKey) {
        'reception_detail' => M360_OPS_SHELL_SECTIONS_FA['reception_jobcards'],
        'technical_detail' => M360_OPS_SHELL_SECTIONS_FA['technical_board'],
        'work_detail' => M360_OPS_SHELL_SECTIONS_FA['work_board'],
        'estimate_detail' => M360_OPS_SHELL_SECTIONS_FA['estimate_board'],
        'invoice_detail' => M360_OPS_SHELL_SECTIONS_FA['invoice_board'],
        'qc_detail' => M360_OPS_SHELL_SECTIONS_FA['qc_board'],
        'settlement_detail' => M360_OPS_SHELL_SECTIONS_FA['invoice_board'],
        'timeline' => M360_OPS_SHELL_SECTIONS_FA['technical_board'],
        default => 'مسیر جاری',
    };
}

/**
 * @param array<string, mixed> $context
 */
function m360_operational_shell_render_top_nav(array $context): void
{
    $backHref = (string)($context['back_href'] ?? M360_OPS_SHELL_STAFF_HOME);
    $backLabel = (string)($context['back_label_fa'] ?? 'بازگشت');
    $sectionTitle = (string)($context['section_title_fa'] ?? '');

    echo '<nav class="m360-ops-topnav" aria-label="ناوبری عملیاتی">';
    echo '<div class="m360-ops-topnav-links">';
    echo '<a class="m360-ops-nav-link m360-ops-nav-back" href="' . m360_operational_shell_h($backHref) . '">← ' . m360_operational_shell_h($backLabel) . '</a>';
    echo '<a class="m360-ops-nav-link" href="' . m360_operational_shell_h(M360_OPS_SHELL_STAFF_HOME) . '">میز کار من</a>';
    echo '<a class="m360-ops-nav-link" href="' . m360_operational_shell_h(M360_OPS_SHELL_PRODUCT_HOME) . '">صفحه اصلی محصول</a>';
    if (!empty($context['show_route_map'])) {
        echo '<a class="m360-ops-nav-link m360-ops-nav-secondary" href="' . m360_operational_shell_h(M360_OPS_SHELL_ROUTE_MAP) . '">نقشه مسیرها</a>';
    }
    echo '</div>';
    if ($sectionTitle !== '') {
        echo '<div class="m360-ops-topnav-title"><span class="m360-ops-topnav-kicker">مسیر جاری</span><h2 class="m360-ops-topnav-heading">' . m360_operational_shell_h($sectionTitle) . '</h2></div>';
    }
    echo '</nav>';
}

/**
 * @param list<array{label_fa:string,href?:string}> $items
 */
function m360_operational_shell_render_breadcrumb(array $items): void
{
    if ($items === []) {
        return;
    }

    echo '<ol class="m360-ops-breadcrumb" aria-label="مسیر">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $label = (string)($item['label_fa'] ?? '');
        $href = (string)($item['href'] ?? '');
        echo '<li class="m360-ops-breadcrumb-item">';
        if ($href !== '' && $i < $last) {
            echo '<a href="' . m360_operational_shell_h($href) . '">' . m360_operational_shell_h($label) . '</a>';
        } else {
            echo '<span aria-current="page">' . m360_operational_shell_h($label) . '</span>';
        }
        echo '</li>';
    }
    echo '</ol>';
}

/**
 * @param array<string, mixed> $context
 */
function m360_operational_shell_render_page_chrome(array $context): void
{
    m360_operational_shell_render_top_nav($context);
    $crumb = $context['breadcrumb'] ?? [];
    if (is_array($crumb)) {
        /** @var list<array{label_fa:string,href?:string}> $crumb */
        m360_operational_shell_render_breadcrumb($crumb);
    }
}

/**
 * @param array<string, mixed> $strip
 */
function m360_operational_shell_render_responsibility_strip(array $strip): void
{
    echo '<section class="m360-ops-strip" aria-label="مسئولیت و وضعیت سند">';

    echo '<div class="m360-ops-strip-head">';
    echo '<h3 class="m360-ops-strip-title">' . m360_operational_shell_h((string)($strip['doc_type_fa'] ?? 'پرونده عملیاتی')) . '</h3>';
    if (!empty($strip['record_label_fa'])) {
        echo '<p class="m360-ops-strip-record">' . m360_operational_shell_h((string)$strip['record_label_fa']) . '</p>';
    }
    if (!empty($strip['status_fa'])) {
        echo '<span class="m360-ops-strip-status">' . m360_operational_shell_h((string)$strip['status_fa']) . '</span>';
    }
    echo '</div>';

    echo '<div class="m360-ops-strip-grid">';
    foreach (m360_operational_shell_strip_fields() as $key => $labelFa) {
        $val = (string)($strip[$key] ?? M360_OPS_SHELL_MISSING_FA);
        if ($val === '') {
            $val = M360_OPS_SHELL_MISSING_FA;
        }
        echo '<div class="m360-ops-strip-field"><span class="m360-ops-strip-lbl">' . m360_operational_shell_h($labelFa) . '</span>';
        echo '<span class="m360-ops-strip-val">' . m360_operational_shell_h($val) . '</span></div>';
    }
    echo '</div>';

    if (!empty($strip['next_action_fa'])) {
        echo '<div class="m360-ops-strip-next"><strong>اقدام بعدی:</strong> ' . m360_operational_shell_h((string)$strip['next_action_fa']) . '</div>';
    }

    $jobcardId = (int)($strip['jobcard_id'] ?? 0);
    if ($jobcardId > 0 && empty($strip['hide_timeline_link'])) {
        $timelineHref = M360_OPS_SHELL_TIMELINE . '?jobcard_id=' . $jobcardId;
        echo '<div class="m360-ops-strip-links"><a href="' . m360_operational_shell_h($timelineHref) . '">تاریخچه / رویدادها</a></div>';
    }

    echo '</section>';
}

/**
 * @return array<string, string>
 */
function m360_operational_shell_strip_fields(): array
{
    return [
        'requester_fa' => 'درخواست‌کننده',
        'creator_fa' => 'ایجادکننده',
        'responsible_fa' => 'مسئول فعلی',
        'assignee_fa' => 'ارجاع‌شده / انجام‌دهنده',
        'approver_fa' => 'تأییدکننده / بازبین',
        'last_changed_fa' => 'آخرین تغییر توسط',
    ];
}

/**
 * @param mixed $conn
 * @param array<string, mixed> $jobcard
 * @param list<string> $allowedActions
 * @return array<string, mixed>
 */
function m360_operational_shell_build_jobcard_strip(
    $conn,
    array $jobcard,
    string $domain,
    string $statusCode,
    array $allowedActions = [],
    string $gateMessage = '',
    string $docTypeFa = 'پرونده JobCard'
): array {
    $jobcardId = (int)($jobcard['jobcard_id'] ?? 0);
    $requester = trim((string)($jobcard['customer_name'] ?? ''));
    if ($requester === '') {
        $requester = M360_OPS_SHELL_MISSING_FA;
    }

    $responsibleId = match ($domain) {
        'reception', 'jobcard' => (int)($jobcard['assigned_reception_user_id'] ?? 0),
        'technical' => (int)($jobcard['assigned_technician_user_id'] ?? 0),
        'qc' => (int)($jobcard['qc_user_id'] ?? 0),
        'work' => (int)($jobcard['assigned_technician_user_id'] ?? 0),
        default => 0,
    };

    $assigneeId = (int)($jobcard['assigned_technician_user_id'] ?? 0);
    if ($domain === 'work' && (int)($jobcard['final_technician_user_id'] ?? 0) > 0) {
        $assigneeId = (int)$jobcard['final_technician_user_id'];
    }

    $approverFa = M360_OPS_SHELL_MISSING_FA;
    if ($domain === 'estimate' && strtoupper((string)($jobcard['estimate_status'] ?? '')) === 'CUSTOMER_APPROVED') {
        $approverFa = 'مشتری (تأیید برآورد)';
    }
    if ($domain === 'settlement' && !empty($jobcard['manager_release_approved'])) {
        $approverFa = 'مدیر (مجوز تحویل)';
    }
    if ($domain === 'qc') {
        $approverFa = m360_operational_shell_resolve_user_name($conn, (int)($jobcard['qc_user_id'] ?? 0));
    }

    $lastChangedId = (int)($jobcard['updated_by_user_id'] ?? 0);
    if ($lastChangedId < 1) {
        $lastChangedId = (int)($jobcard['closed_by_user_id'] ?? 0);
    }

    return [
        'doc_type_fa' => $docTypeFa,
        'record_label_fa' => $jobcardId > 0 ? 'شناسه JobCard: ' . $jobcardId : '',
        'jobcard_id' => $jobcardId,
        'status_fa' => m360_operational_shell_status_label($domain, $statusCode),
        'requester_fa' => $requester,
        'creator_fa' => m360_operational_shell_resolve_user_name($conn, (int)($jobcard['created_by_user_id'] ?? 0)),
        'responsible_fa' => $responsibleId > 0
            ? m360_operational_shell_resolve_user_name($conn, $responsibleId)
            : M360_OPS_SHELL_MISSING_FA,
        'assignee_fa' => $assigneeId > 0
            ? m360_operational_shell_resolve_user_name($conn, $assigneeId)
            : M360_OPS_SHELL_MISSING_FA,
        'approver_fa' => $approverFa,
        'last_changed_fa' => m360_operational_shell_resolve_user_name($conn, $lastChangedId),
        'next_action_fa' => m360_operational_shell_next_action_label($domain, $statusCode, $allowedActions, $gateMessage),
    ];
}

/**
 * Convenience: board page chrome.
 */
function m360_operational_shell_render_board(string $sectionKey): void
{
    $ctx = m360_operational_shell_board_context($sectionKey);
    m360_operational_shell_render_page_chrome($ctx);
}

/**
 * Convenience: detail page chrome + optional strip.
 *
 * @param array<string, mixed>|null $strip
 */
function m360_operational_shell_render_detail(string $sectionKey, string $backHref, int $recordId = 0, ?array $strip = null): void
{
    $ctx = m360_operational_shell_detail_context($sectionKey, $backHref, $recordId);
    m360_operational_shell_render_page_chrome($ctx);
    if ($strip !== null && $strip !== []) {
        m360_operational_shell_render_responsibility_strip($strip);
    }
}
