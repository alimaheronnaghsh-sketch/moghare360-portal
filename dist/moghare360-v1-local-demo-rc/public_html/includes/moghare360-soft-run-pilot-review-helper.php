<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Review Helper (Wave 7C)
 *
 * Read-only pilot execution review & closure summary · no DB writes.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_SOFT_RUN_PILOT_REVIEW_EXECUTIONS_TABLE = 'erp_soft_run_pilot_executions';
const MOGHARE360_SOFT_RUN_PILOT_REVIEW_HISTORY_TABLE = 'erp_soft_run_pilot_execution_history';

const MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_READY = 'PILOT_REVIEW_READY';
const MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR = 'ERROR';

const MOGHARE360_SOFT_RUN_PILOT_REVIEW_BLOCK_MESSAGE =
    'پایه داده لاگ اجرای پایلوت Soft Run برای بازبینی در دسترس نیست.';

/**
 * @return array{ok: bool, connection: mixed, error: string, executions_table_ok: bool, history_table_ok: bool}
 */
function moghare360_soft_run_pilot_review_db_context(): array
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

    $executionsOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_REVIEW_EXECUTIONS_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_PILOT_REVIEW_HISTORY_TABLE);

    if (!$executionsOk || !$historyOk) {
        return [
            'ok' => false,
            'connection' => $connection,
            'error' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_BLOCK_MESSAGE,
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

/**
 * @return array<string, string>
 */
function moghare360_soft_run_pilot_review_status_labels(): array
{
    return [
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_READY => 'آماده بازبینی پایلوت',
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR => 'خطا',
        'DRAFT' => 'پیش‌نویس',
        'STARTED' => 'شروع شده',
        'OBSERVED' => 'مشاهده شده',
        'PASSED' => 'موفق',
        'FAILED' => 'ناموفق',
        'BLOCKED' => 'مسدود',
        'CANCELLED' => 'لغو شده',
        'PENDING_REVIEW' => 'در انتظار بازبینی',
        'VISIBLE' => 'قابل مشاهده',
        'MISSING' => 'مفقود',
        'NOT_REQUIRED' => 'نیاز نیست',
        'NOT_EVALUATED' => 'ارزیابی نشده',
        'PASS' => 'قبول',
        'FAIL' => 'رد',
        'NEEDS_REVIEW' => 'نیازمند بازبینی',
    ];
}

function moghare360_soft_run_pilot_review_status_label(string $status): string
{
    $key = strtoupper(trim($status));

    return moghare360_soft_run_pilot_review_status_labels()[$key] ?? 'نامشخص';
}

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_review_execution_status_keys(): array
{
    return ['DRAFT', 'STARTED', 'OBSERVED', 'PASSED', 'FAILED', 'BLOCKED', 'CANCELLED'];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_review_result_status_keys(): array
{
    return ['NOT_EVALUATED', 'PASS', 'FAIL', 'BLOCKED', 'NEEDS_REVIEW'];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_review_evidence_status_keys(): array
{
    return ['PENDING_REVIEW', 'VISIBLE', 'MISSING', 'NOT_REQUIRED'];
}

/**
 * @param list<string> $keys
 * @return array<string, int>
 */
function moghare360_soft_run_pilot_review_zero_counts(array $keys): array
{
    $counts = [];
    foreach ($keys as $key) {
        $counts[$key] = 0;
    }

    return $counts;
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_soft_run_pilot_review_fetch_execution_status_counts(): array
{
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'counts' => moghare360_soft_run_pilot_review_zero_counts(
                moghare360_soft_run_pilot_review_execution_status_keys()
            ),
            'error' => (string)$ctx['error'],
        ];
    }

    $counts = moghare360_soft_run_pilot_review_zero_counts(
        moghare360_soft_run_pilot_review_execution_status_keys()
    );

    $rows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT execution_status AS grp, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_pilot_executions
         GROUP BY execution_status',
        []
    );

    foreach ($rows as $row) {
        $key = strtoupper(trim((string)($row['grp'] ?? '')));
        if (array_key_exists($key, $counts)) {
            $counts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    return ['ok' => true, 'counts' => $counts, 'error' => ''];
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_soft_run_pilot_review_fetch_result_status_counts(): array
{
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'counts' => moghare360_soft_run_pilot_review_zero_counts(
                moghare360_soft_run_pilot_review_result_status_keys()
            ),
            'error' => (string)$ctx['error'],
        ];
    }

    $counts = moghare360_soft_run_pilot_review_zero_counts(
        moghare360_soft_run_pilot_review_result_status_keys()
    );

    $rows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT result_status AS grp, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_pilot_executions
         GROUP BY result_status',
        []
    );

    foreach ($rows as $row) {
        $key = strtoupper(trim((string)($row['grp'] ?? '')));
        if (array_key_exists($key, $counts)) {
            $counts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    return ['ok' => true, 'counts' => $counts, 'error' => ''];
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_soft_run_pilot_review_fetch_evidence_status_counts(): array
{
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'counts' => moghare360_soft_run_pilot_review_zero_counts(
                moghare360_soft_run_pilot_review_evidence_status_keys()
            ),
            'error' => (string)$ctx['error'],
        ];
    }

    $counts = moghare360_soft_run_pilot_review_zero_counts(
        moghare360_soft_run_pilot_review_evidence_status_keys()
    );

    $rows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT evidence_status AS grp, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_pilot_executions
         GROUP BY evidence_status',
        []
    );

    foreach ($rows as $row) {
        $key = strtoupper(trim((string)($row['grp'] ?? '')));
        if (array_key_exists($key, $counts)) {
            $counts[$key] = (int)($row['cnt'] ?? 0);
        }
    }

    return ['ok' => true, 'counts' => $counts, 'error' => ''];
}

/**
 * @return array{ok: bool, records: list<array<string, string>>, error: string}
 */
function moghare360_soft_run_pilot_review_fetch_recent_executions(int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'records' => [], 'error' => (string)$ctx['error']];
    }

    $records = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT TOP (' . $limit . ')
            execution_id,
            execution_code,
            jobcard_id,
            scenario_key,
            scenario_title,
            execution_status,
            evidence_status,
            result_status,
            created_at,
            updated_at
         FROM dbo.erp_soft_run_pilot_executions
         ORDER BY execution_id DESC',
        []
    );

    return ['ok' => true, 'records' => $records, 'error' => ''];
}

/**
 * @return array{
 *   ok: bool,
 *   total_executions: int,
 *   executions_with_history: int,
 *   total_history_rows: int,
 *   coverage_percent: float|null,
 *   error: string
 * }
 */
function moghare360_soft_run_pilot_review_fetch_history_coverage(): array
{
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'total_executions' => 0,
            'executions_with_history' => 0,
            'total_history_rows' => 0,
            'coverage_percent' => null,
            'error' => (string)$ctx['error'],
        ];
    }

    $totalExecutions = (int)(customer_core_scalar(
        $ctx['connection'],
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_executions',
        []
    ) ?? 0);

    $totalHistoryRows = (int)(customer_core_scalar(
        $ctx['connection'],
        'SELECT COUNT(*) FROM dbo.erp_soft_run_pilot_execution_history',
        []
    ) ?? 0);

    $executionsWithHistory = (int)(customer_core_scalar(
        $ctx['connection'],
        'SELECT COUNT(DISTINCT execution_id) FROM dbo.erp_soft_run_pilot_execution_history',
        []
    ) ?? 0);

    $coveragePercent = null;
    if ($totalExecutions > 0) {
        $coveragePercent = round(($executionsWithHistory / $totalExecutions) * 100, 1);
    }

    return [
        'ok' => true,
        'total_executions' => $totalExecutions,
        'executions_with_history' => $executionsWithHistory,
        'total_history_rows' => $totalHistoryRows,
        'coverage_percent' => $coveragePercent,
        'error' => '',
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
 *   execution_status_counts: array<string, int>,
 *   result_status_counts: array<string, int>,
 *   evidence_status_counts: array<string, int>,
 *   history_coverage_count: int,
 *   history_coverage_percent: float|null,
 *   latest_created_at: string|null,
 *   latest_updated_at: string|null,
 *   error: string
 * }
 */
function moghare360_soft_run_pilot_review_fetch_summary(): array
{
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'total_executions' => 0,
            'total_history_rows' => 0,
            'latest_execution_id' => null,
            'latest_execution_code' => null,
            'latest_execution_status' => null,
            'latest_result_status' => null,
            'latest_evidence_status' => null,
            'execution_status_counts' => moghare360_soft_run_pilot_review_zero_counts(
                moghare360_soft_run_pilot_review_execution_status_keys()
            ),
            'result_status_counts' => moghare360_soft_run_pilot_review_zero_counts(
                moghare360_soft_run_pilot_review_result_status_keys()
            ),
            'evidence_status_counts' => moghare360_soft_run_pilot_review_zero_counts(
                moghare360_soft_run_pilot_review_evidence_status_keys()
            ),
            'history_coverage_count' => 0,
            'history_coverage_percent' => null,
            'latest_created_at' => null,
            'latest_updated_at' => null,
            'error' => (string)$ctx['error'],
        ];
    }

    $executionCounts = moghare360_soft_run_pilot_review_fetch_execution_status_counts();
    $resultCounts = moghare360_soft_run_pilot_review_fetch_result_status_counts();
    $evidenceCounts = moghare360_soft_run_pilot_review_fetch_evidence_status_counts();
    $coverage = moghare360_soft_run_pilot_review_fetch_history_coverage();

    $latestRows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT TOP (1)
            execution_id,
            execution_code,
            execution_status,
            result_status,
            evidence_status,
            created_at,
            updated_at
         FROM dbo.erp_soft_run_pilot_executions
         ORDER BY execution_id DESC',
        []
    );

    $latest = $latestRows[0] ?? [];

    return [
        'ok' => true,
        'total_executions' => (int)($coverage['total_executions'] ?? 0),
        'total_history_rows' => (int)($coverage['total_history_rows'] ?? 0),
        'latest_execution_id' => isset($latest['execution_id']) && (int)$latest['execution_id'] > 0
            ? (int)$latest['execution_id']
            : null,
        'latest_execution_code' => trim((string)($latest['execution_code'] ?? '')) !== ''
            ? (string)$latest['execution_code']
            : null,
        'latest_execution_status' => trim((string)($latest['execution_status'] ?? '')) !== ''
            ? strtoupper(trim((string)$latest['execution_status']))
            : null,
        'latest_result_status' => trim((string)($latest['result_status'] ?? '')) !== ''
            ? strtoupper(trim((string)$latest['result_status']))
            : null,
        'latest_evidence_status' => trim((string)($latest['evidence_status'] ?? '')) !== ''
            ? strtoupper(trim((string)$latest['evidence_status']))
            : null,
        'execution_status_counts' => (array)($executionCounts['counts'] ?? []),
        'result_status_counts' => (array)($resultCounts['counts'] ?? []),
        'evidence_status_counts' => (array)($evidenceCounts['counts'] ?? []),
        'history_coverage_count' => (int)($coverage['executions_with_history'] ?? 0),
        'history_coverage_percent' => $coverage['coverage_percent'] ?? null,
        'latest_created_at' => trim((string)($latest['created_at'] ?? '')) !== ''
            ? (string)$latest['created_at']
            : null,
        'latest_updated_at' => trim((string)($latest['updated_at'] ?? '')) !== ''
            ? (string)$latest['updated_at']
            : null,
        'error' => '',
    ];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_pilot_review_default_operational_notes(): array
{
    return [
        'این داشبورد فقط بازبینی و بستن داخلی اجرای پایلوت Soft Run است — بدون نوشتن پایگاه داده.',
        'رکوردهای اجرای پایلوت از طریق create/workflow WAVE 7A/7B به‌روزرسانی می‌شوند — این صفحه فقط می‌خواند.',
        'تحویل نهایی خودرو انجام نمی‌شود و رکورد تکمیل تحویل ایجاد نمی‌شود.',
        'پورتال عمومی، پرداخت، حسابداری رسمی و ورود تولید فعال نیست.',
        'امضای قانونی نهایی فعال نشده است.',
        'Cursor تصمیم گام بعدی نقشه راه را اتخاذ نکرده است.',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   label: string,
 *   message: string,
 *   summary: array<string, mixed>,
 *   recent_executions: list<array<string, string>>,
 *   history_coverage: array<string, mixed>,
 *   review_items: list<string>,
 *   operational_notes: list<string>,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_pilot_review_evaluate(): array
{
    $errors = [];
    $reviewItems = [];
    $ctx = moghare360_soft_run_pilot_review_db_context();

    if (!$ctx['ok']) {
        if ($ctx['connection'] === false) {
            return [
                'ok' => false,
                'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR,
                'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR),
                'message' => (string)$ctx['error'],
                'summary' => [],
                'recent_executions' => [],
                'history_coverage' => [],
                'review_items' => ['اتصال پایگاه داده برقرار نشد.'],
                'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
                'errors' => [(string)$ctx['error']],
            ];
        }

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED,
            'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED),
            'message' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_BLOCK_MESSAGE,
            'summary' => [],
            'recent_executions' => [],
            'history_coverage' => [],
            'review_items' => [
                'executions table: ' . (($ctx['executions_table_ok'] ?? false) ? 'OK' : 'MISSING'),
                'history table: ' . (($ctx['history_table_ok'] ?? false) ? 'OK' : 'MISSING'),
            ],
            'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
            'errors' => [MOGHARE360_SOFT_RUN_PILOT_REVIEW_BLOCK_MESSAGE],
        ];
    }

    $summary = moghare360_soft_run_pilot_review_fetch_summary();
    $recent = moghare360_soft_run_pilot_review_fetch_recent_executions(25);
    $coverage = moghare360_soft_run_pilot_review_fetch_history_coverage();

    if (!($summary['ok'] ?? false) || !($recent['ok'] ?? false) || !($coverage['ok'] ?? false)) {
        $readError = (string)($summary['error'] ?? $recent['error'] ?? $coverage['error'] ?? 'خطای خواندن بازبینی پایلوت.');

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR,
            'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_ERROR),
            'message' => $readError,
            'summary' => $summary,
            'recent_executions' => [],
            'history_coverage' => $coverage,
            'review_items' => ['خطا در خواندن داده‌های بازبینی پایلوت.'],
            'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
            'errors' => [$readError],
        ];
    }

    $totalExecutions = (int)($summary['total_executions'] ?? 0);
    $totalHistoryRows = (int)($summary['total_history_rows'] ?? 0);

    if ($totalExecutions < 1) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_EMPTY,
            'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_EMPTY),
            'message' => 'جداول پایلوت موجود است اما هنوز رکورد اجرایی ثبت نشده است.',
            'summary' => $summary,
            'recent_executions' => [],
            'history_coverage' => $coverage,
            'review_items' => ['هیچ رکورد اجرای پایلوت یافت نشد.'],
            'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
            'errors' => [],
        ];
    }

    if ($totalHistoryRows < 1) {
        $errors[] = 'هیچ ردیف تاریخچه اجرای پایلوت یافت نشد.';
        $reviewItems[] = 'پوشش تاریخچه: ۰ ردیف';
    }

    $needsReviewCount = 0;

    $reviewRows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT execution_id, execution_status, result_status
         FROM dbo.erp_soft_run_pilot_executions
         WHERE execution_status IN (?, ?) OR result_status = ?',
        ['FAILED', 'BLOCKED', 'NEEDS_REVIEW']
    );

    foreach ($reviewRows as $row) {
        $execStatus = strtoupper(trim((string)($row['execution_status'] ?? '')));
        $resultStatus = strtoupper(trim((string)($row['result_status'] ?? '')));

        if (in_array($execStatus, ['FAILED', 'BLOCKED'], true) || $resultStatus === 'NEEDS_REVIEW') {
            $needsReviewCount++;
            $reviewItems[] = 'اجرای #' . (string)($row['execution_id'] ?? '')
                . ' — execution=' . $execStatus . ', result=' . $resultStatus;
        }
    }

    if ($needsReviewCount > 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_REVIEW_REQUIRED,
            'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_REVIEW_REQUIRED),
            'message' => 'رکوردهای اجرای پایلوت موجود است اما ' . $needsReviewCount . ' مورد نیازمند بازبینی است.',
            'summary' => $summary,
            'recent_executions' => (array)($recent['records'] ?? []),
            'history_coverage' => $coverage,
            'review_items' => $reviewItems,
            'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
            'errors' => $errors,
        ];
    }

    if ($totalHistoryRows < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED,
            'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED),
            'message' => 'رکورد اجرا موجود است اما تاریخچه تأیید نشد.',
            'summary' => $summary,
            'recent_executions' => (array)($recent['records'] ?? []),
            'history_coverage' => $coverage,
            'review_items' => $reviewItems,
            'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
            'errors' => $errors,
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_READY,
        'label' => moghare360_soft_run_pilot_review_status_label(MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_READY),
        'message' => 'بازبینی اجرای پایلوت Soft Run — آماده (رکوردها و تاریخچه موجود).',
        'summary' => $summary,
        'recent_executions' => (array)($recent['records'] ?? []),
        'history_coverage' => $coverage,
        'review_items' => [
            'کل اجراها: ' . $totalExecutions,
            'کل تاریخچه: ' . $totalHistoryRows,
            'پوشش تاریخچه: ' . (string)($summary['history_coverage_count'] ?? 0)
                . ' / ' . $totalExecutions
                . ($summary['history_coverage_percent'] !== null
                    ? ' (' . (string)$summary['history_coverage_percent'] . '%)'
                    : ''),
        ],
        'operational_notes' => moghare360_soft_run_pilot_review_default_operational_notes(),
        'errors' => [],
    ];
}
