<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Final Closure Helper (Wave 7D)
 *
 * Read-only WAVE 7 final closure summary · no DB writes.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

$reviewHelperPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-review-helper.php';
if (is_file($reviewHelperPath)) {
    require_once $reviewHelperPath;
}

const MOGHARE360_SOFT_RUN_PILOT_FINAL_EXECUTIONS_TABLE = 'erp_soft_run_pilot_executions';
const MOGHARE360_SOFT_RUN_PILOT_FINAL_HISTORY_TABLE = 'erp_soft_run_pilot_execution_history';

const MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY = 'WAVE_7_READY_FOR_SOFT_RUN_REVIEW';
const MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR = 'ERROR';

const MOGHARE360_SOFT_RUN_PILOT_FINAL_BLOCK_MESSAGE =
    'پایه داده یا وابستگی‌های بستن نهایی WAVE 7 در دسترس نیست.';

/**
 * @return array{ok: bool, connection: mixed, error: string, executions_table_ok: bool, history_table_ok: bool}
 */
function moghare360_soft_run_pilot_final_closure_db_context(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'connection' => false,
            'error' => 'اتصال به پایگاه داده برقرار نشد.',
            'executions_table_ok' => false,
            'history_table_ok' => false,
        ];
    }

    $executionsOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_FINAL_EXECUTIONS_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_FINAL_HISTORY_TABLE);

    if (!$executionsOk || !$historyOk) {
        return [
            'ok' => false,
            'connection' => $connection,
            'error' => MOGHARE360_SOFT_RUN_PILOT_FINAL_BLOCK_MESSAGE,
            'executions_table_ok' => $executionsOk,
            'history_table_ok' => $historyOk,
        ];
    }

    return [
        'ok' => true,
        'connection' => $connection,
        'error' => '',
        'executions_table_ok' => true,
        'history_table_ok' => true,
    ];
}

function moghare360_soft_run_pilot_final_closure_public_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, helper_available: bool, review: array<string, mixed>}
 */
function moghare360_soft_run_pilot_final_closure_fetch_review_status(): array
{
    if (!function_exists('moghare360_soft_run_pilot_review_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR),
            'message' => 'Helper بازبینی WAVE 7C در دسترس نیست.',
            'helper_available' => false,
            'review' => [],
        ];
    }

    $review = moghare360_soft_run_pilot_review_evaluate();

    return [
        'ok' => (bool)($review['ok'] ?? false),
        'status' => (string)($review['status'] ?? MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR),
        'label' => function_exists('moghare360_soft_run_pilot_review_status_label')
            ? moghare360_soft_run_pilot_review_status_label((string)($review['status'] ?? ''))
            : (string)($review['status'] ?? ''),
        'message' => (string)($review['message'] ?? ''),
        'helper_available' => true,
        'review' => $review,
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   total_executions: int,
 *   total_history_rows: int,
 *   latest_execution_id: int|null,
 *   latest_execution_code: string|null,
 *   latest_execution_status: string|null,
 *   latest_result_status: string|null,
 *   latest_evidence_status: string|null,
 *   history_coverage_count: int,
 *   history_coverage_percent: float|null,
 *   latest_created_at: string|null,
 *   latest_updated_at: string|null,
 *   execution_status_counts: array<string, int>,
 *   result_status_counts: array<string, int>,
 *   evidence_status_counts: array<string, int>,
 *   error: string
 * }
 */
function moghare360_soft_run_pilot_final_closure_fetch_execution_summary(): array
{
    if (function_exists('moghare360_soft_run_pilot_review_fetch_summary')) {
        $summary = moghare360_soft_run_pilot_review_fetch_summary();

        return [
            'ok' => (bool)($summary['ok'] ?? false),
            'total_executions' => (int)($summary['total_executions'] ?? 0),
            'total_history_rows' => (int)($summary['total_history_rows'] ?? 0),
            'latest_execution_id' => $summary['latest_execution_id'] ?? null,
            'latest_execution_code' => $summary['latest_execution_code'] ?? null,
            'latest_execution_status' => $summary['latest_execution_status'] ?? null,
            'latest_result_status' => $summary['latest_result_status'] ?? null,
            'latest_evidence_status' => $summary['latest_evidence_status'] ?? null,
            'history_coverage_count' => (int)($summary['history_coverage_count'] ?? 0),
            'history_coverage_percent' => $summary['history_coverage_percent'] ?? null,
            'latest_created_at' => $summary['latest_created_at'] ?? null,
            'latest_updated_at' => $summary['latest_updated_at'] ?? null,
            'execution_status_counts' => (array)($summary['execution_status_counts'] ?? []),
            'result_status_counts' => (array)($summary['result_status_counts'] ?? []),
            'evidence_status_counts' => (array)($summary['evidence_status_counts'] ?? []),
            'error' => (string)($summary['error'] ?? ''),
        ];
    }

    return [
        'ok' => false,
        'total_executions' => 0,
        'total_history_rows' => 0,
        'latest_execution_id' => null,
        'latest_execution_code' => null,
        'latest_execution_status' => null,
        'latest_result_status' => null,
        'latest_evidence_status' => null,
        'history_coverage_count' => 0,
        'history_coverage_percent' => null,
        'latest_created_at' => null,
        'latest_updated_at' => null,
        'execution_status_counts' => [],
        'result_status_counts' => [],
        'evidence_status_counts' => [],
        'error' => 'Helper خلاصه اجرا در دسترس نیست.',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   workflow_terminal_count: int,
 *   passed_count: int,
 *   failed_count: int,
 *   blocked_count: int,
 *   review_required_count: int,
 *   error: string
 * }
 */
function moghare360_soft_run_pilot_final_closure_fetch_workflow_summary(): array
{
    $ctx = moghare360_soft_run_pilot_final_closure_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'workflow_terminal_count' => 0,
            'passed_count' => 0,
            'failed_count' => 0,
            'blocked_count' => 0,
            'review_required_count' => 0,
            'error' => (string)$ctx['error'],
        ];
    }

    $connection = $ctx['connection'];

    $terminalCount = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions
         WHERE execution_status IN (?, ?, ?)',
        ['PASSED', 'FAILED', 'CANCELLED']
    ) ?? 0);

    $passedCount = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions WHERE execution_status = ?',
        ['PASSED']
    ) ?? 0);

    $failedCount = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions
         WHERE execution_status = ? OR result_status = ?',
        ['FAILED', 'FAIL']
    ) ?? 0);

    $blockedCount = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions
         WHERE execution_status = ? OR result_status = ?',
        ['BLOCKED', 'BLOCKED']
    ) ?? 0);

    $reviewRequiredCount = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions
         WHERE execution_status IN (?, ?)
            OR result_status = ?
            OR evidence_status = ?',
        ['FAILED', 'BLOCKED', 'NEEDS_REVIEW', 'MISSING']
    ) ?? 0);

    return [
        'ok' => true,
        'workflow_terminal_count' => $terminalCount,
        'passed_count' => $passedCount,
        'failed_count' => $failedCount,
        'blocked_count' => $blockedCount,
        'review_required_count' => $reviewRequiredCount,
        'error' => '',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   total_executions: int,
 *   total_history_rows: int,
 *   executions_with_history: int,
 *   coverage_percent: float|null,
 *   write_boundary_note: string,
 *   error: string
 * }
 */
function moghare360_soft_run_pilot_final_closure_fetch_history_summary(): array
{
    if (function_exists('moghare360_soft_run_pilot_review_fetch_history_coverage')) {
        $coverage = moghare360_soft_run_pilot_review_fetch_history_coverage();

        return [
            'ok' => (bool)($coverage['ok'] ?? false),
            'total_executions' => (int)($coverage['total_executions'] ?? 0),
            'total_history_rows' => (int)($coverage['total_history_rows'] ?? 0),
            'executions_with_history' => (int)($coverage['executions_with_history'] ?? 0),
            'coverage_percent' => $coverage['coverage_percent'] ?? null,
            'write_boundary_note' => 'Read-only WAVE 7D — writes only via WAVE 7A create and WAVE 7B workflow to pilot execution tables.',
            'error' => (string)($coverage['error'] ?? ''),
        ];
    }

    return [
        'ok' => false,
        'total_executions' => 0,
        'total_history_rows' => 0,
        'executions_with_history' => 0,
        'coverage_percent' => null,
        'write_boundary_note' => 'History summary unavailable.',
        'error' => 'Helper پوشش تاریخچه در دسترس نیست.',
    ];
}

/**
 * @return list<array{path: string, label_fa: string, critical: bool}>
 */
function moghare360_soft_run_pilot_final_closure_required_pages(): array
{
    return [
        ['path' => 'erp-soft-run-pilot-execution-create.php', 'label_fa' => 'ثبت اجرای پایلوت (WAVE 7A)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-execution-board.php', 'label_fa' => 'برد اجرای پایلوت (WAVE 7A)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-execution-detail.php', 'label_fa' => 'جزئیات اجرای پایلوت (WAVE 7A)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-execution-workflow.php', 'label_fa' => 'گردش کار اجرای پایلوت (WAVE 7B)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-review-dashboard.php', 'label_fa' => 'داشبورد بازبینی پایلوت (WAVE 7C)', 'critical' => true],
        ['path' => 'erp-soft-run-pilot-final-closure-dashboard.php', 'label_fa' => 'داشبورد نهایی بستن WAVE 7 (WAVE 7D)', 'critical' => true],
        ['path' => 'erp-soft-run-final-closure-dashboard.php', 'label_fa' => 'داشبورد نهایی آمادگی پایلوت WAVE 6D', 'critical' => false],
    ];
}

/**
 * @return array{ok: bool, total: int, present: int, missing: int, pages: list<array<string, mixed>>}
 */
function moghare360_soft_run_pilot_final_closure_page_status(): array
{
    $publicRoot = moghare360_soft_run_pilot_final_closure_public_root();
    $requiredPages = moghare360_soft_run_pilot_final_closure_required_pages();
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
function moghare360_soft_run_pilot_final_closure_default_signoff_notes(): array
{
    return [
        'این داشبورد فقط بستن نهایی داخلی WAVE 7 (لاگ اجرای پایلوت Soft Run) است — بدون نوشتن پایگاه داده.',
        'رکوردهای اجرای پایلوت فقط از طریق WAVE 7A create و WAVE 7B workflow به‌روزرسانی می‌شوند.',
        'مرز نوشتن تأیید شد: فقط dbo.erp_soft_run_pilot_executions و dbo.erp_soft_run_pilot_execution_history (WAVE 7A/7B).',
        'تحویل نهایی خودرو انجام نمی‌شود و رکورد تکمیل تحویل ایجاد نمی‌شود.',
        'پورتال عمومی، پرداخت، حسابداری رسمی، SaaS و ورود تولید فعال نیست.',
        'امضای قانونی نهایی فعال نشده است.',
        'قوانین WAVE 1 تا 7C بدون تغییر باقی مانده‌اند.',
        'Cursor تصمیم گام بعدی نقشه راه را اتخاذ نکرده است.',
    ];
}

function moghare360_soft_run_pilot_final_closure_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY => 'آماده بازبینی Soft Run (WAVE 7)',
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR => 'خطا',
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
 *   execution_summary: array<string, mixed>,
 *   workflow_summary: array<string, mixed>,
 *   history_summary: array<string, mixed>,
 *   pages: array<string, mixed>,
 *   ready_items: list<string>,
 *   review_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   signoff_notes: list<string>,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_pilot_final_closure_evaluate(): array
{
    $signoffNotes = moghare360_soft_run_pilot_final_closure_default_signoff_notes();
    $readyItems = [];
    $reviewItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    $reviewStatus = moghare360_soft_run_pilot_final_closure_fetch_review_status();
    $executionSummary = moghare360_soft_run_pilot_final_closure_fetch_execution_summary();
    $workflowSummary = moghare360_soft_run_pilot_final_closure_fetch_workflow_summary();
    $historySummary = moghare360_soft_run_pilot_final_closure_fetch_history_summary();
    $pageStatus = moghare360_soft_run_pilot_final_closure_page_status();

    if (!$reviewStatus['helper_available']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_ERROR),
            'message' => (string)$reviewStatus['message'],
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => ['WAVE 7C — helper بازبینی پایلوت موجود نیست'],
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => ['review_helper_missing'],
        ];
    }

    $ctx = moghare360_soft_run_pilot_final_closure_db_context();
    $reviewLayerStatus = strtoupper(trim((string)$reviewStatus['status']));

    if (!$ctx['ok']) {
        $blockedItems[] = 'جداول پایلوت: ' . (string)$ctx['error'];
        if (!($ctx['executions_table_ok'] ?? false)) {
            $missingItems[] = 'dbo.erp_soft_run_pilot_executions';
        }
        if (!($ctx['history_table_ok'] ?? false)) {
            $missingItems[] = 'dbo.erp_soft_run_pilot_execution_history';
        }

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED),
            'message' => MOGHARE360_SOFT_RUN_PILOT_FINAL_BLOCK_MESSAGE,
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    if (in_array($reviewLayerStatus, [
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED,
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR,
    ], true)) {
        $blockedItems[] = 'WAVE 7C — ' . (string)$reviewStatus['message'];

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 7 — مسدود (بازبینی WAVE 7C مسدود یا خطا).',
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
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
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 7 — مسدود (صفحه بحرانی مفقود).',
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    $totalExecutions = (int)($executionSummary['total_executions'] ?? 0);
    $totalHistoryRows = (int)($executionSummary['total_history_rows'] ?? 0);

    if ($totalExecutions < 1 || $reviewLayerStatus === MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_EMPTY) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY),
            'message' => 'بستن نهایی WAVE 7 — خالی (هنوز رکورد اجرای پایلوت ثبت نشده).',
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    if ($totalHistoryRows < 1) {
        $blockedItems[] = 'تاریخچه اجرای پایلوت یافت نشد.';
    }

    $reviewRequiredCount = (int)($workflowSummary['review_required_count'] ?? 0);
    if ($reviewRequiredCount > 0) {
        $reviewItems[] = $reviewRequiredCount . ' اجرا نیازمند بازبینی (FAILED/BLOCKED/NEEDS_REVIEW/MISSING evidence).';
    }

    if ($reviewLayerStatus === MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_REVIEW_REQUIRED || $reviewRequiredCount > 0) {
        foreach ((array)($reviewStatus['review']['review_items'] ?? []) as $item) {
            $reviewItems[] = (string)$item;
        }

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED),
            'message' => 'بستن نهایی WAVE 7 — نیازمند بازبینی (اجراهای نیازمند توجه موجود).',
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    if ($totalHistoryRows < 1) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED,
            'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED),
            'message' => 'بستن نهایی WAVE 7 — مسدود (تاریخچه اجرا تأیید نشد).',
            'review_status' => $reviewStatus,
            'execution_summary' => $executionSummary,
            'workflow_summary' => $workflowSummary,
            'history_summary' => $historySummary,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'errors' => $errors,
        ];
    }

    $readyItems[] = 'WAVE 7A — ثبت کنترل‌شده اجرای پایلوت فعال';
    $readyItems[] = 'WAVE 7B — گردش کار کنترل‌شده با تاریخچه فعال';
    $readyItems[] = 'WAVE 7C — بازبینی پایلوت: ' . (string)$reviewStatus['status'];
    $readyItems[] = 'پوشش تاریخچه: ' . (string)($executionSummary['history_coverage_count'] ?? 0)
        . ' / ' . $totalExecutions
        . ($executionSummary['history_coverage_percent'] !== null
            ? ' (' . (string)$executionSummary['history_coverage_percent'] . '%)'
            : '');
    $readyItems[] = (string)($historySummary['write_boundary_note'] ?? 'Write boundary confirmed (read-only).');
    $readyItems[] = 'لایه بستن نهایی WAVE 7 — بدون نوشتن پایگاه داده از این داشبورد';

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY,
        'label' => moghare360_soft_run_pilot_final_closure_status_label(MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY),
        'message' => 'بستن نهایی WAVE 7 — آماده بازبینی Soft Run (لاگ اجرای پایلوت، گردش کار، تاریخچه و بازبینی تأیید شد).',
        'review_status' => $reviewStatus,
        'execution_summary' => $executionSummary,
        'workflow_summary' => $workflowSummary,
        'history_summary' => $historySummary,
        'pages' => $pageStatus,
        'ready_items' => $readyItems,
        'review_items' => $reviewItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'signoff_notes' => $signoffNotes,
        'errors' => $errors,
    ];
}
