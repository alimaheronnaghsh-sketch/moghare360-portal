<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Final Closure Helper (Wave 8D)
 *
 * Read-only WAVE 8 final closure summary · no DB writes.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

$reviewHelperPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-review-helper.php';
if (is_file($reviewHelperPath)) {
    require_once $reviewHelperPath;
}

const MOGHARE360_SOFT_RUN_FINDING_FINAL_FINDINGS_TABLE = 'erp_soft_run_findings';
const MOGHARE360_SOFT_RUN_FINDING_FINAL_HISTORY_TABLE = 'erp_soft_run_finding_history';

const MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_READY = 'WAVE_8_READY_FOR_CORRECTIVE_ACTION_REVIEW';
const MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ACTION_REQUIRED = 'ACTION_REQUIRED';
const MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR = 'ERROR';

const MOGHARE360_SOFT_RUN_FINDING_FINAL_BLOCK_MESSAGE =
    'پایه داده یا وابستگی‌های بستن نهایی WAVE 8 در دسترس نیست.';

/**
 * @return array{ok: bool, connection: mixed, error: string, findings_table_ok: bool, history_table_ok: bool}
 */
function moghare360_soft_run_finding_final_closure_db_context(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'connection' => false,
            'error' => 'اتصال به پایگاه داده برقرار نشد.',
            'findings_table_ok' => false,
            'history_table_ok' => false,
        ];
    }

    $findingsOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_FINAL_FINDINGS_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_FINAL_HISTORY_TABLE);

    if (!$findingsOk || !$historyOk) {
        return [
            'ok' => false,
            'connection' => $connection,
            'error' => MOGHARE360_SOFT_RUN_FINDING_FINAL_BLOCK_MESSAGE,
            'findings_table_ok' => $findingsOk,
            'history_table_ok' => $historyOk,
        ];
    }

    return [
        'ok' => true,
        'connection' => $connection,
        'error' => '',
        'findings_table_ok' => true,
        'history_table_ok' => true,
    ];
}

function moghare360_soft_run_finding_final_closure_public_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, helper_available: bool, review: array<string, mixed>}
 */
function moghare360_soft_run_finding_final_closure_fetch_review_status(): array
{
    if (!function_exists('moghare360_soft_run_finding_review_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR),
            'message' => 'Helper بازبینی WAVE 8C در دسترس نیست.',
            'helper_available' => false,
            'review' => [],
        ];
    }

    $review = moghare360_soft_run_finding_review_evaluate();

    return [
        'ok' => (bool)($review['ok'] ?? false),
        'status' => (string)($review['status'] ?? MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR),
        'label' => function_exists('moghare360_soft_run_finding_review_status_label')
            ? moghare360_soft_run_finding_review_status_label((string)($review['status'] ?? ''))
            : (string)($review['status'] ?? ''),
        'message' => (string)($review['message'] ?? ''),
        'helper_available' => true,
        'review' => $review,
    ];
}

/**
 * @return array<string, mixed>
 */
function moghare360_soft_run_finding_final_closure_empty_finding_summary(string $error = ''): array
{
    return [
        'ok' => false,
        'total_findings' => 0,
        'total_history_rows' => 0,
        'latest_finding_id' => null,
        'latest_finding_code' => null,
        'latest_finding_status' => null,
        'latest_corrective_action_status' => null,
        'latest_severity_level' => null,
        'history_coverage_count' => 0,
        'history_coverage_percent' => null,
        'open_count' => 0,
        'under_review_count' => 0,
        'action_required_count' => 0,
        'resolved_count' => 0,
        'closed_count' => 0,
        'cancelled_count' => 0,
        'finding_status_counts' => [],
        'severity_level_counts' => [],
        'corrective_action_status_counts' => [],
        'high_unresolved_count' => 0,
        'critical_unresolved_count' => 0,
        'latest_created_at' => null,
        'latest_updated_at' => null,
        'error' => $error,
    ];
}

/**
 * @return array<string, mixed>
 */
function moghare360_soft_run_finding_final_closure_fetch_finding_summary(): array
{
    if (!function_exists('moghare360_soft_run_finding_review_fetch_summary')) {
        return moghare360_soft_run_finding_final_closure_empty_finding_summary(
            'Helper خلاصه یافته در دسترس نیست.'
        );
    }

    $summary = moghare360_soft_run_finding_review_fetch_summary();

    if (!($summary['ok'] ?? false)) {
        return moghare360_soft_run_finding_final_closure_empty_finding_summary(
            (string)($summary['error'] ?? '')
        );
    }

    $findingStatusCounts = (array)($summary['finding_status_counts'] ?? []);
    $severityCounts = (array)($summary['severity_level_counts'] ?? []);
    $correctiveCounts = (array)($summary['corrective_action_status_counts'] ?? []);

    $highUnresolved = 0;
    $criticalUnresolved = 0;

    $ctx = moghare360_soft_run_finding_final_closure_db_context();
    if ($ctx['ok']) {
        $highUnresolved = (int)(customer_core_scalar(
            $ctx['connection'],
            'SELECT COUNT(*) FROM dbo.erp_soft_run_findings
             WHERE severity_level = ?
               AND finding_status NOT IN (?, ?)',
            ['HIGH', 'CLOSED', 'CANCELLED']
        ) ?? 0);

        $criticalUnresolved = (int)(customer_core_scalar(
            $ctx['connection'],
            'SELECT COUNT(*) FROM dbo.erp_soft_run_findings
             WHERE severity_level = ?
               AND finding_status NOT IN (?, ?)',
            ['CRITICAL', 'CLOSED', 'CANCELLED']
        ) ?? 0);
    }

    return [
        'ok' => true,
        'total_findings' => (int)($summary['total_findings'] ?? 0),
        'total_history_rows' => (int)($summary['total_history_rows'] ?? 0),
        'latest_finding_id' => $summary['latest_finding_id'] ?? null,
        'latest_finding_code' => $summary['latest_finding_code'] ?? null,
        'latest_finding_status' => $summary['latest_finding_status'] ?? null,
        'latest_corrective_action_status' => $summary['latest_corrective_action_status'] ?? null,
        'latest_severity_level' => $summary['latest_severity_level'] ?? null,
        'history_coverage_count' => (int)($summary['history_coverage_count'] ?? 0),
        'history_coverage_percent' => $summary['history_coverage_percent'] ?? null,
        'open_count' => (int)($summary['open_count'] ?? 0),
        'under_review_count' => (int)($findingStatusCounts['UNDER_REVIEW'] ?? 0),
        'action_required_count' => (int)($summary['action_required_count'] ?? 0),
        'resolved_count' => (int)($summary['resolved_count'] ?? 0),
        'closed_count' => (int)($summary['closed_count'] ?? 0),
        'cancelled_count' => (int)($findingStatusCounts['CANCELLED'] ?? 0),
        'finding_status_counts' => $findingStatusCounts,
        'severity_level_counts' => $severityCounts,
        'corrective_action_status_counts' => $correctiveCounts,
        'high_unresolved_count' => $highUnresolved,
        'critical_unresolved_count' => $criticalUnresolved,
        'latest_created_at' => $summary['latest_created_at'] ?? null,
        'latest_updated_at' => $summary['latest_updated_at'] ?? null,
        'error' => '',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   corrective_not_started_count: int,
 *   corrective_in_progress_count: int,
 *   corrective_done_count: int,
 *   corrective_not_required_count: int,
 *   corrective_blocked_count: int,
 *   write_boundary_note: string,
 *   error: string
 * }
 */
function moghare360_soft_run_finding_final_closure_fetch_corrective_summary(): array
{
    $findingSummary = moghare360_soft_run_finding_final_closure_fetch_finding_summary();
    $correctiveCounts = (array)($findingSummary['corrective_action_status_counts'] ?? []);

    if (!($findingSummary['ok'] ?? false)) {
        return [
            'ok' => false,
            'corrective_not_started_count' => 0,
            'corrective_in_progress_count' => 0,
            'corrective_done_count' => 0,
            'corrective_not_required_count' => 0,
            'corrective_blocked_count' => 0,
            'write_boundary_note' => 'Corrective summary unavailable.',
            'error' => (string)($findingSummary['error'] ?? ''),
        ];
    }

    return [
        'ok' => true,
        'corrective_not_started_count' => (int)($correctiveCounts['NOT_STARTED'] ?? 0),
        'corrective_in_progress_count' => (int)($correctiveCounts['IN_PROGRESS'] ?? 0),
        'corrective_done_count' => (int)($correctiveCounts['DONE'] ?? 0),
        'corrective_not_required_count' => (int)($correctiveCounts['NOT_REQUIRED'] ?? 0),
        'corrective_blocked_count' => (int)($correctiveCounts['BLOCKED'] ?? 0),
        'write_boundary_note' => 'Read-only WAVE 8D — writes only via WAVE 8A create and WAVE 8B workflow to Soft Run finding tables.',
        'error' => '',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   total_findings: int,
 *   total_history_rows: int,
 *   findings_with_history: int,
 *   coverage_percent: float|null,
 *   write_boundary_note: string,
 *   error: string
 * }
 */
function moghare360_soft_run_finding_final_closure_fetch_history_summary(): array
{
    if (function_exists('moghare360_soft_run_finding_review_fetch_history_coverage')) {
        $coverage = moghare360_soft_run_finding_review_fetch_history_coverage();

        return [
            'ok' => (bool)($coverage['ok'] ?? false),
            'total_findings' => (int)($coverage['total_findings'] ?? 0),
            'total_history_rows' => (int)($coverage['total_history_rows'] ?? 0),
            'findings_with_history' => (int)($coverage['findings_with_history'] ?? 0),
            'coverage_percent' => $coverage['coverage_percent'] ?? null,
            'write_boundary_note' => 'Read-only WAVE 8D — writes only via WAVE 8A create and WAVE 8B workflow to Soft Run finding tables.',
            'error' => (string)($coverage['error'] ?? ''),
        ];
    }

    return [
        'ok' => false,
        'total_findings' => 0,
        'total_history_rows' => 0,
        'findings_with_history' => 0,
        'coverage_percent' => null,
        'write_boundary_note' => 'History summary unavailable.',
        'error' => 'Helper پوشش تاریخچه در دسترس نیست.',
    ];
}

/**
 * @return list<array{path: string, label_fa: string, critical: bool}>
 */
function moghare360_soft_run_finding_final_closure_required_pages(): array
{
    return [
        ['path' => 'erp-soft-run-finding-create.php', 'label_fa' => 'ثبت یافته (WAVE 8A)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-board.php', 'label_fa' => 'برد یافته‌ها (WAVE 8A)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-detail.php', 'label_fa' => 'جزئیات یافته (WAVE 8A)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-workflow.php', 'label_fa' => 'گردش کار یافته (WAVE 8B)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-review-dashboard.php', 'label_fa' => 'داشبورد بازبینی یافته‌ها (WAVE 8C)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-final-closure-dashboard.php', 'label_fa' => 'داشبورد نهایی بستن WAVE 8 (WAVE 8D)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-final-closure-dashboard.php', 'label_fa' => 'داشبورد نهایی بستن اجرای پایلوت WAVE 7D', 'critical' => false],
        ['path' => 'erp-soft-run-pilot-review-dashboard.php', 'label_fa' => 'داشبورد بازبینی اجرای پایلوت WAVE 7C', 'critical' => false],
    ];
}

/**
 * @return array{ok: bool, total: int, present: int, missing: int, pages: list<array<string, mixed>>}
 */
function moghare360_soft_run_finding_final_closure_page_status(): array
{
    $publicRoot = moghare360_soft_run_finding_final_closure_public_root();
    $requiredPages = moghare360_soft_run_finding_final_closure_required_pages();
    $pages = [];
    $present = 0;
    $missing = 0;

    foreach ($requiredPages as $pageDef) {
        $path = (string)($pageDef['path'] ?? '');
        $exists = $path !== '' && is_file($publicRoot . DIRECTORY_SEPARATOR . $path);

        if ($exists) {
            $present++;
        } else {
            $missing++;
        }

        $pages[] = [
            'path' => $path,
            'label_fa' => (string)($pageDef['label_fa'] ?? $path),
            'critical' => (bool)($pageDef['critical'] ?? true),
            'exists' => $exists,
            'status' => $exists ? 'PRESENT' : 'MISSING',
        ];
    }

    $criticalMissing = 0;
    foreach ($pages as $pageRow) {
        if (!($pageRow['exists'] ?? false) && ($pageRow['critical'] ?? false)) {
            $criticalMissing++;
        }
    }

    return [
        'ok' => $criticalMissing === 0,
        'total' => count($requiredPages),
        'present' => $present,
        'missing' => $missing,
        'pages' => $pages,
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_final_closure_default_signoff_notes(): array
{
    return [
        'این داشبورد فقط بستن نهایی داخلی WAVE 8 (ثبت یافته‌ها و اقدام اصلاحی Soft Run) است — بدون نوشتن پایگاه داده.',
        'رکوردهای یافته فقط از طریق WAVE 8A create و WAVE 8B workflow به‌روزرسانی می‌شوند.',
        'مرز نوشتن تأیید شد: فقط dbo.erp_soft_run_findings و dbo.erp_soft_run_finding_history (WAVE 8A/8B).',
        'تحویل نهایی خودرو انجام نمی‌شود و رکورد تکمیل تحویل ایجاد نمی‌شود.',
        'پورتال عمومی، پرداخت، حسابداری رسمی، SaaS و ورود تولید فعال نیست.',
        'امضای قانونی نهایی فعال نشده است.',
        'قوانین WAVE 1 تا 8C بدون تغییر باقی مانده‌اند.',
        'Cursor تصمیم گام بعدی نقشه راه را اتخاذ نکرده است.',
    ];
}

function moghare360_soft_run_finding_final_closure_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_READY => 'آماده بازبینی اقدام اصلاحی (WAVE 8)',
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ACTION_REQUIRED => 'نیازمند اقدام',
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   label: string,
 *   message: string,
 *   review_status: array<string, mixed>,
 *   finding_summary: array<string, mixed>,
 *   corrective_summary: array<string, mixed>,
 *   history_summary: array<string, mixed>,
 *   pages: array<string, mixed>,
 *   ready_items: list<string>,
 *   action_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   signoff_notes: list<string>,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_finding_final_closure_evaluate(): array
{
    $signoffNotes = moghare360_soft_run_finding_final_closure_default_signoff_notes();
    $readyItems = [];
    $actionItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    $reviewStatus = moghare360_soft_run_finding_final_closure_fetch_review_status();
    $findingSummary = moghare360_soft_run_finding_final_closure_fetch_finding_summary();
    $correctiveSummary = moghare360_soft_run_finding_final_closure_fetch_corrective_summary();
    $historySummary = moghare360_soft_run_finding_final_closure_fetch_history_summary();
    $pageStatus = moghare360_soft_run_finding_final_closure_page_status();

    if (!$reviewStatus['helper_available']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR),
            'message' => (string)$reviewStatus['message'],
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => ['WAVE 8C — helper بازبینی یافته موجود نیست'],
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => ['review_helper_missing'],
        ];
    }

    $ctx = moghare360_soft_run_finding_final_closure_db_context();
    $reviewLayerStatus = strtoupper(trim((string)$reviewStatus['status']));

    if (!$ctx['ok']) {
        $blockedItems[] = 'جداول یافته: ' . (string)$ctx['error'];
        if (!($ctx['findings_table_ok'] ?? false)) {
            $missingItems[] = 'dbo.erp_soft_run_findings';
        }
        if (!($ctx['history_table_ok'] ?? false)) {
            $missingItems[] = 'dbo.erp_soft_run_finding_history';
        }

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED),
            'message' => MOGHARE360_SOFT_RUN_FINDING_FINAL_BLOCK_MESSAGE,
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    if (in_array($reviewLayerStatus, [
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR,
    ], true)) {
        $blockedItems[] = 'WAVE 8C — ' . (string)$reviewStatus['message'];

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 8 — مسدود (بازبینی WAVE 8C مسدود یا خطا).',
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    $criticalUnresolved = (int)($findingSummary['critical_unresolved_count'] ?? 0);
    if ($criticalUnresolved > 0) {
        $blockedItems[] = $criticalUnresolved . ' یافته CRITICAL حل‌نشده موجود است.';

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 8 — مسدود (یافته CRITICAL حل‌نشده).',
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    foreach ($pageStatus['pages'] as $pageRow) {
        if (!($pageRow['exists'] ?? false) && ($pageRow['critical'] ?? false)) {
            $blockedItems[] = 'صفحه بحرانی مفقود: ' . (string)($pageRow['path'] ?? '');
            $missingItems[] = (string)($pageRow['label_fa'] ?? '') . ' — ' . (string)($pageRow['path'] ?? '');
        } elseif ($pageRow['exists'] ?? false) {
            $readyItems[] = 'صفحه موجود: ' . (string)($pageRow['path'] ?? '');
        }
    }

    if ($blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 8 — مسدود (صفحه بحرانی مفقود).',
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    $totalFindings = (int)($findingSummary['total_findings'] ?? 0);
    $totalHistoryRows = (int)($findingSummary['total_history_rows'] ?? 0);

    if ($totalFindings < 1 || $reviewLayerStatus === MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_EMPTY,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_EMPTY),
            'message' => 'بستن نهایی WAVE 8 — خالی (هنوز رکورد یافته ثبت نشده).',
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    if ($totalHistoryRows < 1) {
        $blockedItems[] = 'تاریخچه یافته یافت نشد.';
    }

    $openCount = (int)($findingSummary['open_count'] ?? 0);
    $underReviewCount = (int)($findingSummary['under_review_count'] ?? 0);
    $actionRequiredStatusCount = (int)($findingSummary['action_required_count'] ?? 0);
    $highUnresolved = (int)($findingSummary['high_unresolved_count'] ?? 0);
    $correctiveNotStarted = (int)($correctiveSummary['corrective_not_started_count'] ?? 0);
    $correctiveInProgress = (int)($correctiveSummary['corrective_in_progress_count'] ?? 0);
    $correctiveBlocked = (int)($correctiveSummary['corrective_blocked_count'] ?? 0);

    $needsAction = false;

    if ($openCount > 0 || $underReviewCount > 0 || $actionRequiredStatusCount > 0) {
        $needsAction = true;
        $actionItems[] = 'یافته‌های باز/در بازبینی/نیازمند اقدام: '
            . ($openCount + $underReviewCount + $actionRequiredStatusCount);
    }

    if ($correctiveNotStarted > 0 || $correctiveInProgress > 0 || $correctiveBlocked > 0) {
        $needsAction = true;
        $actionItems[] = 'اقدام اصلاحی فعال: NOT_STARTED=' . $correctiveNotStarted
            . ', IN_PROGRESS=' . $correctiveInProgress
            . ', BLOCKED=' . $correctiveBlocked;
    }

    if ($highUnresolved > 0) {
        $needsAction = true;
        $actionItems[] = $highUnresolved . ' یافته HIGH حل‌نشده';
    }

    if ($reviewLayerStatus === MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED) {
        $needsAction = true;
        foreach ((array)($reviewStatus['review']['review_items'] ?? []) as $item) {
            $actionItems[] = (string)$item;
        }
    }

    if ($needsAction) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ACTION_REQUIRED,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ACTION_REQUIRED),
            'message' => 'بستن نهایی WAVE 8 — نیازمند اقدام (یافته‌ها یا اقدامات اصلاحی فعال).',
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    if ($totalHistoryRows < 1) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 8 — مسدود (تاریخچه یافته تأیید نشد).',
            'review_status' => $reviewStatus,
            'finding_summary' => $findingSummary,
            'corrective_summary' => $correctiveSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'action_items' => $actionItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    $readyItems[] = 'WAVE 8A — ثبت کنترل‌شده یافته فعال';
    $readyItems[] = 'WAVE 8B — گردش کار کنترل‌شده با تاریخچه فعال';
    $readyItems[] = 'WAVE 8C — بازبینی یافته‌ها: ' . (string)$reviewStatus['status'];
    $readyItems[] = 'پوشش تاریخچه: ' . (string)($findingSummary['history_coverage_count'] ?? 0)
        . ' / ' . $totalFindings
        . ($findingSummary['history_coverage_percent'] !== null
            ? ' (' . (string)$findingSummary['history_coverage_percent'] . '%)'
            : '');
    $readyItems[] = (string)($historySummary['write_boundary_note'] ?? 'Write boundary confirmed (read-only).');
    $readyItems[] = 'لایه بستن نهایی WAVE 8 — بدون نوشتن پایگاه داده از این داشبورد';

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_READY,
        'label' => moghare360_soft_run_finding_final_closure_status_label(MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_READY),
        'message' => 'بستن نهایی WAVE 8 — آماده بازبینی اقدام اصلاحی (ثبت، گردش کار، تاریخچه و بازبینی تأیید شد).',
        'review_status' => $reviewStatus,
        'finding_summary' => $findingSummary,
        'corrective_summary' => $correctiveSummary,
        'history_summary' => $historySummary,
        'pages' => $pageStatus,
        'ready_items' => $readyItems,
        'action_items' => $actionItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'signoff_notes' => $signoffNotes,
        'errors' => $errors,
    ];
}
