<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Soft Run Readiness Helper (Wave 9A)
 *
 * Read-only executive aggregation of WAVE 6/7/8 final closure · no DB writes.
 * NOT final vehicle delivery. NOT Go/No-Go approval. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

$wave6HelperPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';
if (is_file($wave6HelperPath)) {
    require_once $wave6HelperPath;
}

$wave7HelperPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';
if (is_file($wave7HelperPath)) {
    require_once $wave7HelperPath;
}

$wave8HelperPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-final-closure-helper.php';
if (is_file($wave8HelperPath)) {
    require_once $wave8HelperPath;
}

const MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY = 'EXECUTIVE_REVIEW_READY';
const MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW = 'GO_REVIEW_REQUIRED';
const MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR = 'ERROR';

function moghare360_executive_soft_run_readiness_public_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, helper_available: bool, evaluation: array<string, mixed>}
 */
function moghare360_executive_soft_run_readiness_fetch_wave6_status(): array
{
    if (!function_exists('moghare360_soft_run_final_closure_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper بستن نهایی WAVE 6 در دسترس نیست.',
            'helper_available' => false,
            'evaluation' => [],
        ];
    }

    $evaluation = moghare360_soft_run_final_closure_evaluate();
    $status = (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_FINAL_STATUS_ERROR);

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => $status,
        'label' => function_exists('moghare360_soft_run_final_closure_status_label')
            ? moghare360_soft_run_final_closure_status_label($status)
            : $status,
        'message' => (string)($evaluation['message'] ?? ''),
        'helper_available' => true,
        'evaluation' => $evaluation,
    ];
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, helper_available: bool, evaluation: array<string, mixed>}
 */
function moghare360_executive_soft_run_readiness_fetch_wave7_status(): array
{
    if (!function_exists('moghare360_soft_run_pilot_final_closure_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper بستن نهایی WAVE 7 در دسترس نیست.',
            'helper_available' => false,
            'evaluation' => [],
        ];
    }

    $evaluation = moghare360_soft_run_pilot_final_closure_evaluate();
    $status = (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR);

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => $status,
        'label' => function_exists('moghare360_soft_run_pilot_final_closure_status_label')
            ? moghare360_soft_run_pilot_final_closure_status_label($status)
            : $status,
        'message' => (string)($evaluation['message'] ?? ''),
        'helper_available' => true,
        'evaluation' => $evaluation,
    ];
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, helper_available: bool, evaluation: array<string, mixed>}
 */
function moghare360_executive_soft_run_readiness_fetch_wave8_status(): array
{
    if (!function_exists('moghare360_soft_run_finding_final_closure_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper بستن نهایی WAVE 8 در دسترس نیست.',
            'helper_available' => false,
            'evaluation' => [],
        ];
    }

    $evaluation = moghare360_soft_run_finding_final_closure_evaluate();
    $status = (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR);

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => $status,
        'label' => function_exists('moghare360_soft_run_finding_final_closure_status_label')
            ? moghare360_soft_run_finding_final_closure_status_label($status)
            : $status,
        'message' => (string)($evaluation['message'] ?? ''),
        'helper_available' => true,
        'evaluation' => $evaluation,
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   total_pilot_executions: int,
 *   total_findings: int,
 *   total_finding_history_rows: int,
 *   history_coverage_percent: float|null,
 *   open_finding_count: int,
 *   under_review_finding_count: int,
 *   action_required_finding_count: int,
 *   resolved_finding_count: int,
 *   closed_finding_count: int,
 *   corrective_in_progress_count: int,
 *   corrective_blocked_count: int,
 *   high_unresolved_count: int,
 *   critical_unresolved_count: int,
 *   error: string
 * }
 */
function moghare360_executive_soft_run_readiness_fetch_findings_snapshot(): array
{
    $empty = [
        'ok' => false,
        'total_pilot_executions' => 0,
        'total_findings' => 0,
        'total_finding_history_rows' => 0,
        'history_coverage_percent' => null,
        'open_finding_count' => 0,
        'under_review_finding_count' => 0,
        'action_required_finding_count' => 0,
        'resolved_finding_count' => 0,
        'closed_finding_count' => 0,
        'corrective_in_progress_count' => 0,
        'corrective_blocked_count' => 0,
        'high_unresolved_count' => 0,
        'critical_unresolved_count' => 0,
        'error' => '',
    ];

    $totalPilotExecutions = 0;
    if (function_exists('moghare360_soft_run_pilot_final_closure_fetch_execution_summary')) {
        $pilotSummary = moghare360_soft_run_pilot_final_closure_fetch_execution_summary();
        if ($pilotSummary['ok'] ?? false) {
            $totalPilotExecutions = (int)($pilotSummary['total_executions'] ?? 0);
        }
    }

    if (function_exists('moghare360_soft_run_finding_final_closure_fetch_finding_summary')) {
        $findingSummary = moghare360_soft_run_finding_final_closure_fetch_finding_summary();
        $correctiveSummary = function_exists('moghare360_soft_run_finding_final_closure_fetch_corrective_summary')
            ? moghare360_soft_run_finding_final_closure_fetch_corrective_summary()
            : [];

        if ($findingSummary['ok'] ?? false) {
            return [
                'ok' => true,
                'total_pilot_executions' => $totalPilotExecutions,
                'total_findings' => (int)($findingSummary['total_findings'] ?? 0),
                'total_finding_history_rows' => (int)($findingSummary['total_history_rows'] ?? 0),
                'history_coverage_percent' => $findingSummary['history_coverage_percent'] ?? null,
                'open_finding_count' => (int)($findingSummary['open_count'] ?? 0),
                'under_review_finding_count' => (int)($findingSummary['under_review_count'] ?? 0),
                'action_required_finding_count' => (int)($findingSummary['action_required_count'] ?? 0),
                'resolved_finding_count' => (int)($findingSummary['resolved_count'] ?? 0),
                'closed_finding_count' => (int)($findingSummary['closed_count'] ?? 0),
                'corrective_in_progress_count' => (int)($correctiveSummary['corrective_in_progress_count'] ?? 0),
                'corrective_blocked_count' => (int)($correctiveSummary['corrective_blocked_count'] ?? 0),
                'high_unresolved_count' => (int)($findingSummary['high_unresolved_count'] ?? 0),
                'critical_unresolved_count' => (int)($findingSummary['critical_unresolved_count'] ?? 0),
                'error' => '',
            ];
        }

        $empty['total_pilot_executions'] = $totalPilotExecutions;
        $empty['error'] = (string)($findingSummary['error'] ?? 'خلاصه یافته در دسترس نیست.');

        return $empty;
    }

    $connection = customer_core_db();
    if ($connection === false) {
        $empty['error'] = 'اتصال به پایگاه داده برقرار نشد.';

        return $empty;
    }

    if (customer_core_table_exists($connection, 'erp_soft_run_pilot_executions')) {
        $totalPilotExecutions = (int)(customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions',
            []
        ) ?? 0);
    }

    $totalFindings = 0;
    if (customer_core_table_exists($connection, 'erp_soft_run_findings')) {
        $totalFindings = (int)(customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_soft_run_findings',
            []
        ) ?? 0);
    }

    $empty['ok'] = true;
    $empty['total_pilot_executions'] = $totalPilotExecutions;
    $empty['total_findings'] = $totalFindings;

    return $empty;
}

/**
 * @return list<array{path: string, label_fa: string, critical: bool}>
 */
function moghare360_executive_soft_run_readiness_required_pages(): array
{
    return [
        ['path' => 'erp-soft-run-control-room.php', 'label_fa' => 'اتاق کنترل Soft Run (WAVE 6A)', 'critical' => true],
        ['path' => 'erp-soft-run-final-closure-dashboard.php', 'label_fa' => 'بستن نهایی WAVE 6 (WAVE 6D)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-final-closure-dashboard.php', 'label_fa' => 'بستن نهایی WAVE 7 (WAVE 7D)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-review-dashboard.php', 'label_fa' => 'بازبینی پایلوت WAVE 7 (WAVE 7C)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-final-closure-dashboard.php', 'label_fa' => 'بستن نهایی WAVE 8 (WAVE 8D)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-review-dashboard.php', 'label_fa' => 'بازبینی یافته‌ها WAVE 8 (WAVE 8C)', 'critical' => true],
        ['path' => 'erp-soft-run-finding-board.php', 'label_fa' => 'برد یافته‌ها WAVE 8 (WAVE 8A)', 'critical' => false],
        ['path' => 'erp-executive-soft-run-readiness-dashboard.php', 'label_fa' => 'داشبورد آمادگی مدیریتی WAVE 9A', 'critical' => true],
    ];
}

/**
 * @return array{ok: bool, total: int, present: int, missing: int, pages: list<array<string, mixed>>}
 */
function moghare360_executive_soft_run_readiness_page_status(): array
{
    $publicRoot = moghare360_executive_soft_run_readiness_public_root();
    $requiredPages = moghare360_executive_soft_run_readiness_required_pages();
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
function moghare360_executive_soft_run_readiness_default_decision_notes(): array
{
    return [
        'این داشبورد فقط لایه آمادگی مدیریتی Soft Run است — بدون نوشتن پایگاه داده.',
        'وضعیت WAVE 6، WAVE 7 و WAVE 8 از داشبوردهای بستن نهایی موجود خوانده می‌شود.',
        'این داشبورد تأیید تحویل نهایی خودرو نیست و رکورد تکمیل تحویل ایجاد نمی‌کند.',
        'پورتال عمومی، پرداخت، حسابداری رسمی، SaaS و ورود تولید فعال نیست.',
        'امضای قانونی نهایی فعال نشده است.',
        'رفتار WAVE 6/WAVE 7/WAVE 8 بدون تغییر باقی مانده است.',
        'Cursor تصمیم گام بعدی نقشه راه را اتخاذ نکرده است.',
    ];
}

function moghare360_executive_soft_run_readiness_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY => 'آماده بازبینی مدیریتی',
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW => 'نیازمند بازبینی Go/No-Go',
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY => 'خالی',
        MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   label: string,
 *   message: string,
 *   summary: array<string, mixed>,
 *   wave6: array<string, mixed>,
 *   wave7: array<string, mixed>,
 *   wave8: array<string, mixed>,
 *   snapshot: array<string, mixed>,
 *   pages: array<string, mixed>,
 *   ready_items: list<string>,
 *   review_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   decision_notes: list<string>,
 *   go_interpretation: string,
 *   errors: list<string>
 * }
 */
function moghare360_executive_soft_run_readiness_evaluate(): array
{
    $decisionNotes = moghare360_executive_soft_run_readiness_default_decision_notes();
    $readyItems = [];
    $reviewItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    $wave6 = moghare360_executive_soft_run_readiness_fetch_wave6_status();
    $wave7 = moghare360_executive_soft_run_readiness_fetch_wave7_status();
    $wave8 = moghare360_executive_soft_run_readiness_fetch_wave8_status();
    $snapshot = moghare360_executive_soft_run_readiness_fetch_findings_snapshot();
    $pageStatus = moghare360_executive_soft_run_readiness_page_status();

    $wave6Status = strtoupper(trim((string)$wave6['status']));
    $wave7Status = strtoupper(trim((string)$wave7['status']));
    $wave8Status = strtoupper(trim((string)$wave8['status']));

    $buildSummary = static function (string $executiveStatus) use (
        $wave6Status,
        $wave7Status,
        $wave8Status,
        $snapshot,
        $pageStatus
    ): array {
        return [
            'wave6_status' => $wave6Status,
            'wave7_status' => $wave7Status,
            'wave8_status' => $wave8Status,
            'executive_readiness_status' => $executiveStatus,
            'total_pilot_executions' => (int)($snapshot['total_pilot_executions'] ?? 0),
            'total_findings' => (int)($snapshot['total_findings'] ?? 0),
            'total_finding_history_rows' => (int)($snapshot['total_finding_history_rows'] ?? 0),
            'history_coverage_percent' => $snapshot['history_coverage_percent'] ?? null,
            'open_finding_count' => (int)($snapshot['open_finding_count'] ?? 0),
            'under_review_finding_count' => (int)($snapshot['under_review_finding_count'] ?? 0),
            'action_required_finding_count' => (int)($snapshot['action_required_finding_count'] ?? 0),
            'resolved_finding_count' => (int)($snapshot['resolved_finding_count'] ?? 0),
            'closed_finding_count' => (int)($snapshot['closed_finding_count'] ?? 0),
            'corrective_in_progress_count' => (int)($snapshot['corrective_in_progress_count'] ?? 0),
            'corrective_blocked_count' => (int)($snapshot['corrective_blocked_count'] ?? 0),
            'high_unresolved_count' => (int)($snapshot['high_unresolved_count'] ?? 0),
            'critical_unresolved_count' => (int)($snapshot['critical_unresolved_count'] ?? 0),
            'required_pages_present_count' => (int)($pageStatus['present'] ?? 0),
            'required_pages_total_count' => (int)($pageStatus['total'] ?? 0),
            'final_management_note' => 'Read-only executive layer — no delivery approval from this dashboard.',
        ];
    };

    if (!$wave6['helper_available'] || !$wave7['helper_available'] || !$wave8['helper_available']) {
        if (!$wave6['helper_available']) {
            $blockedItems[] = 'WAVE 6 — helper بستن نهایی موجود نیست';
            $errors[] = 'wave6_helper_missing';
        }
        if (!$wave7['helper_available']) {
            $blockedItems[] = 'WAVE 7 — helper بستن نهایی موجود نیست';
            $errors[] = 'wave7_helper_missing';
        }
        if (!$wave8['helper_available']) {
            $blockedItems[] = 'WAVE 8 — helper بستن نهایی موجود نیست';
            $errors[] = 'wave8_helper_missing';
        }

        return [
            'ok' => false,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR,
            'label' => moghare360_executive_soft_run_readiness_status_label(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR),
            'message' => 'آمادگی مدیریتی Soft Run — خطا (وابستگی WAVE 6/7/8 نامعتبر).',
            'summary' => $buildSummary(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_ERROR),
            'wave6' => $wave6,
            'wave7' => $wave7,
            'wave8' => $wave8,
            'snapshot' => $snapshot,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'decision_notes' => $decisionNotes,
            'go_interpretation' => 'BLOCKED — وابستگی‌های بستن نهایی در دسترس نیست.',
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

    $criticalUnresolved = (int)($snapshot['critical_unresolved_count'] ?? 0);
    if ($criticalUnresolved > 0) {
        $blockedItems[] = $criticalUnresolved . ' یافته CRITICAL حل‌نشده موجود است.';
    }

    $wave7BlockedOrError = in_array($wave7Status, [
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR,
    ], true);

    $wave8BlockedOrError = in_array($wave8Status, [
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ERROR,
    ], true);

    if ($wave7BlockedOrError) {
        $blockedItems[] = 'WAVE 7 — ' . (string)$wave7['message'];
    }

    if ($wave8BlockedOrError) {
        $blockedItems[] = 'WAVE 8 — ' . (string)$wave8['message'];
    }

    if ($blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED,
            'label' => moghare360_executive_soft_run_readiness_status_label(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED),
            'message' => 'آمادگی مدیریتی Soft Run — مسدود (صفحه بحرانی، یافته CRITICAL، یا WAVE 7/8 مسدود).',
            'summary' => $buildSummary(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED),
            'wave6' => $wave6,
            'wave7' => $wave7,
            'wave8' => $wave8,
            'snapshot' => $snapshot,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'decision_notes' => $decisionNotes,
            'go_interpretation' => 'NO-GO — مسدود (رفع موانع بحرانی قبل از بازبینی مدیریتی).',
            'errors' => $errors,
        ];
    }

    $totalPilot = (int)($snapshot['total_pilot_executions'] ?? 0);
    $totalFindings = (int)($snapshot['total_findings'] ?? 0);

    if ($totalPilot < 1 && $totalFindings < 1
        && $wave7Status === MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY
        && $wave8Status === MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_EMPTY
    ) {
        return [
            'ok' => true,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY,
            'label' => moghare360_executive_soft_run_readiness_status_label(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY),
            'message' => 'آمادگی مدیریتی Soft Run — خالی (هنوز رکورد پایلوت یا یافته ثبت نشده).',
            'summary' => $buildSummary(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY),
            'wave6' => $wave6,
            'wave7' => $wave7,
            'wave8' => $wave8,
            'snapshot' => $snapshot,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'decision_notes' => $decisionNotes,
            'go_interpretation' => 'EMPTY — داده عملیاتی Soft Run برای بازبینی مدیریتی موجود نیست.',
            'errors' => $errors,
        ];
    }

    $openCount = (int)($snapshot['open_finding_count'] ?? 0);
    $underReviewCount = (int)($snapshot['under_review_finding_count'] ?? 0);
    $actionRequiredCount = (int)($snapshot['action_required_finding_count'] ?? 0);
    $highUnresolved = (int)($snapshot['high_unresolved_count'] ?? 0);
    $correctiveInProgress = (int)($snapshot['corrective_in_progress_count'] ?? 0);
    $correctiveBlocked = (int)($snapshot['corrective_blocked_count'] ?? 0);

    $correctiveNotStarted = 0;
    if (function_exists('moghare360_soft_run_finding_final_closure_fetch_corrective_summary')) {
        $correctiveSummary = moghare360_soft_run_finding_final_closure_fetch_corrective_summary();
        $correctiveNotStarted = (int)($correctiveSummary['corrective_not_started_count'] ?? 0);
    }

    $needsGoReview = false;

    if ($wave8Status === MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ACTION_REQUIRED) {
        $needsGoReview = true;
        $reviewItems[] = 'WAVE 8 — ' . (string)$wave8['message'];
    }

    if ($openCount > 0 || $underReviewCount > 0 || $actionRequiredCount > 0) {
        $needsGoReview = true;
        $reviewItems[] = 'یافته‌های فعال: OPEN=' . $openCount
            . ', UNDER_REVIEW=' . $underReviewCount
            . ', ACTION_REQUIRED=' . $actionRequiredCount;
    }

    if ($correctiveNotStarted > 0 || $correctiveInProgress > 0 || $correctiveBlocked > 0) {
        $needsGoReview = true;
        $reviewItems[] = 'اقدام اصلاحی فعال: NOT_STARTED=' . $correctiveNotStarted
            . ', IN_PROGRESS=' . $correctiveInProgress
            . ', BLOCKED=' . $correctiveBlocked;
    }

    if ($highUnresolved > 0) {
        $needsGoReview = true;
        $reviewItems[] = $highUnresolved . ' یافته HIGH حل‌نشده';
    }

    if ($wave7Status === MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED) {
        $needsGoReview = true;
        $reviewItems[] = 'WAVE 7 — ' . (string)$wave7['message'];
    }

    if ($wave6Status === MOGHARE360_SOFT_RUN_FINAL_STATUS_REVIEW_REQUIRED) {
        $needsGoReview = true;
        $reviewItems[] = 'WAVE 6 — ' . (string)$wave6['message'];
    }

    if ($needsGoReview) {
        foreach ((array)($wave8['evaluation']['action_items'] ?? []) as $item) {
            $reviewItems[] = (string)$item;
        }

        return [
            'ok' => true,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW,
            'label' => moghare360_executive_soft_run_readiness_status_label(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW),
            'message' => 'آمادگی مدیریتی Soft Run — نیازمند بازبینی Go/No-Go (یافته‌ها یا اقدامات اصلاحی فعال).',
            'summary' => $buildSummary(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW),
            'wave6' => $wave6,
            'wave7' => $wave7,
            'wave8' => $wave8,
            'snapshot' => $snapshot,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'decision_notes' => $decisionNotes,
            'go_interpretation' => 'REVIEW — بازبینی مدیریتی قبل از تصمیم Go توصیه می‌شود (یافته/اقدام اصلاحی فعال).',
            'errors' => $errors,
        ];
    }

    $wave6Ready = $wave6Status === MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY;
    $wave7Ready = $wave7Status === MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY;
    $wave8Ready = $wave8Status === MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_READY;

    $readyItems[] = 'WAVE 6 — ' . (string)$wave6['status'];
    $readyItems[] = 'WAVE 7 — ' . (string)$wave7['status'];
    $readyItems[] = 'WAVE 8 — ' . (string)$wave8['status'];
    $readyItems[] = 'صفحات زمان اجرا: ' . (int)($pageStatus['present'] ?? 0) . ' / ' . (int)($pageStatus['total'] ?? 0);
    $readyItems[] = 'پوشش تاریخچه یافته: '
        . (string)($snapshot['history_coverage_percent'] ?? '—') . '%';
    $readyItems[] = 'لایه آمادگی مدیریتی — بدون نوشتن پایگاه داده';

    $executiveReady = $wave6Ready && $wave7Ready && $wave8Ready;

    if ($executiveReady) {
        return [
            'ok' => true,
            'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY,
            'label' => moghare360_executive_soft_run_readiness_status_label(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY),
            'message' => 'آمادگی مدیریتی Soft Run — آماده بازبینی مدیریتی (WAVE 6/7/8 تأیید شد).',
            'summary' => $buildSummary(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY),
            'wave6' => $wave6,
            'wave7' => $wave7,
            'wave8' => $wave8,
            'snapshot' => $snapshot,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'decision_notes' => $decisionNotes,
            'go_interpretation' => 'GO REVIEW READY — لایه‌های WAVE 6/7/8 آماده بازبینی مدیریتی (بدون تأیید تحویل نهایی).',
            'errors' => $errors,
        ];
    }

    $reviewItems[] = 'WAVE 6=' . $wave6Status . ', WAVE 7=' . $wave7Status . ', WAVE 8=' . $wave8Status;

    return [
        'ok' => true,
        'status' => MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW,
        'label' => moghare360_executive_soft_run_readiness_status_label(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW),
        'message' => 'آمادگی مدیریتی Soft Run — نیازمند بازبینی Go/No-Go (یک یا چند لایه آماده کامل نیست).',
        'summary' => $buildSummary(MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW),
        'wave6' => $wave6,
        'wave7' => $wave7,
        'wave8' => $wave8,
        'snapshot' => $snapshot,
        'pages' => $pageStatus,
        'ready_items' => $readyItems,
        'review_items' => $reviewItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'decision_notes' => $decisionNotes,
        'go_interpretation' => 'REVIEW — بازبینی مدیریتی قبل از تصمیم Go توصیه می‌شود.',
        'errors' => $errors,
    ];
}
