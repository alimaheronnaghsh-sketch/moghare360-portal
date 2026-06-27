<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Review Helper (Wave 8C)
 *
 * Read-only findings review & corrective action monitoring · no DB writes.
 * NOT final vehicle delivery. NOT delivery completion.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_SOFT_RUN_FINDING_REVIEW_FINDINGS_TABLE = 'erp_soft_run_findings';
const MOGHARE360_SOFT_RUN_FINDING_REVIEW_HISTORY_TABLE = 'erp_soft_run_finding_history';

const MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_READY = 'FINDINGS_REVIEW_READY';
const MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED = 'ACTION_REQUIRED';
const MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR = 'ERROR';

const MOGHARE360_SOFT_RUN_FINDING_REVIEW_BLOCK_MESSAGE =
    'پایه داده ثبت یافته‌های Soft Run برای بازبینی در دسترس نیست.';

/**
 * @return array{ok: bool, connection: mixed, error: string, findings_table_ok: bool, history_table_ok: bool}
 */
function moghare360_soft_run_finding_review_db_context(): array
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

    $findingsOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_REVIEW_FINDINGS_TABLE);
    $historyOk = customer_core_table_exists($connection, MOGHARE360_SOFT_RUN_FINDING_REVIEW_HISTORY_TABLE);

    if (!$findingsOk || !$historyOk) {
        return [
            'ok' => false,
            'connection' => $connection,
            'error' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_BLOCK_MESSAGE,
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

/**
 * @return array<string, string>
 */
function moghare360_soft_run_finding_review_status_labels(): array
{
    return [
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_READY => 'آماده بازبینی یافته‌ها',
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED => 'نیازمند اقدام',
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR => 'خطا',
        'OPEN' => 'باز',
        'UNDER_REVIEW' => 'در حال بازبینی',
        'ACTION_REQUIRED' => 'نیازمند اقدام',
        'RESOLVED' => 'رفع شده',
        'CLOSED' => 'بسته شده',
        'CANCELLED' => 'لغو شده',
        'LOW' => 'کم',
        'MEDIUM' => 'متوسط',
        'HIGH' => 'بالا',
        'CRITICAL' => 'بحرانی',
        'NOT_STARTED' => 'شروع نشده',
        'IN_PROGRESS' => 'در حال انجام',
        'DONE' => 'انجام شده',
        'NOT_REQUIRED' => 'نیاز نیست',
        'BLOCKED' => 'مسدود',
        'ISSUE' => 'مسئله',
        'BUG' => 'باگ',
        'OBSERVATION' => 'مشاهده',
        'RISK' => 'ریسک',
        'PROCESS_GAP' => 'شکاف فرآیند',
        'TRAINING_NEED' => 'نیاز آموزشی',
    ];
}

function moghare360_soft_run_finding_review_status_label(string $status): string
{
    $key = strtoupper(trim($status));

    return moghare360_soft_run_finding_review_status_labels()[$key] ?? 'نامشخص';
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_review_finding_status_keys(): array
{
    return ['OPEN', 'UNDER_REVIEW', 'ACTION_REQUIRED', 'RESOLVED', 'CLOSED', 'CANCELLED'];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_review_severity_keys(): array
{
    return ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
}

/**
 * @return list<string>
 */
function moghare360_soft_run_finding_review_corrective_status_keys(): array
{
    return ['NOT_STARTED', 'IN_PROGRESS', 'DONE', 'NOT_REQUIRED', 'BLOCKED'];
}

/**
 * @param list<string> $keys
 * @return array<string, int>
 */
function moghare360_soft_run_finding_review_zero_counts(array $keys): array
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
function moghare360_soft_run_finding_review_fetch_status_counts(): array
{
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'counts' => moghare360_soft_run_finding_review_zero_counts(
                moghare360_soft_run_finding_review_finding_status_keys()
            ),
            'error' => (string)$ctx['error'],
        ];
    }

    $counts = moghare360_soft_run_finding_review_zero_counts(
        moghare360_soft_run_finding_review_finding_status_keys()
    );

    $rows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT finding_status AS grp, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_findings
         GROUP BY finding_status',
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
function moghare360_soft_run_finding_review_fetch_severity_counts(): array
{
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'counts' => moghare360_soft_run_finding_review_zero_counts(
                moghare360_soft_run_finding_review_severity_keys()
            ),
            'error' => (string)$ctx['error'],
        ];
    }

    $counts = moghare360_soft_run_finding_review_zero_counts(
        moghare360_soft_run_finding_review_severity_keys()
    );

    $rows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT severity_level AS grp, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_findings
         GROUP BY severity_level',
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
function moghare360_soft_run_finding_review_fetch_corrective_status_counts(): array
{
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'counts' => moghare360_soft_run_finding_review_zero_counts(
                moghare360_soft_run_finding_review_corrective_status_keys()
            ),
            'error' => (string)$ctx['error'],
        ];
    }

    $counts = moghare360_soft_run_finding_review_zero_counts(
        moghare360_soft_run_finding_review_corrective_status_keys()
    );

    $rows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT corrective_action_status AS grp, COUNT(*) AS cnt
         FROM dbo.erp_soft_run_findings
         GROUP BY corrective_action_status',
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
function moghare360_soft_run_finding_review_fetch_recent_findings(int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'records' => [], 'error' => (string)$ctx['error']];
    }

    $records = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT TOP (' . $limit . ')
            finding_id,
            finding_code,
            execution_id,
            jobcard_id,
            finding_type,
            severity_level,
            finding_status,
            corrective_action_status,
            finding_title,
            created_at,
            updated_at
         FROM dbo.erp_soft_run_findings
         ORDER BY finding_id DESC',
        []
    );

    return ['ok' => true, 'records' => $records, 'error' => ''];
}

/**
 * @return array{
 *   ok: bool,
 *   total_findings: int,
 *   findings_with_history: int,
 *   total_history_rows: int,
 *   coverage_percent: float|null,
 *   error: string
 * }
 */
function moghare360_soft_run_finding_review_fetch_history_coverage(): array
{
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'total_findings' => 0,
            'findings_with_history' => 0,
            'total_history_rows' => 0,
            'coverage_percent' => null,
            'error' => (string)$ctx['error'],
        ];
    }

    $totalFindings = (int)(customer_core_scalar(
        $ctx['connection'],
        'SELECT COUNT(*) FROM dbo.erp_soft_run_findings',
        []
    ) ?? 0);

    $totalHistoryRows = (int)(customer_core_scalar(
        $ctx['connection'],
        'SELECT COUNT(*) FROM dbo.erp_soft_run_finding_history',
        []
    ) ?? 0);

    $findingsWithHistory = (int)(customer_core_scalar(
        $ctx['connection'],
        'SELECT COUNT(DISTINCT finding_id) FROM dbo.erp_soft_run_finding_history',
        []
    ) ?? 0);

    $coveragePercent = null;
    if ($totalFindings > 0) {
        $coveragePercent = round(($findingsWithHistory / $totalFindings) * 100, 1);
    }

    return [
        'ok' => true,
        'total_findings' => $totalFindings,
        'findings_with_history' => $findingsWithHistory,
        'total_history_rows' => $totalHistoryRows,
        'coverage_percent' => $coveragePercent,
        'error' => '',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   total_findings: int,
 *   total_history_rows: int,
 *   latest_finding_id: int|null,
 *   latest_finding_code: string|null,
 *   latest_finding_status: string|null,
 *   latest_corrective_action_status: string|null,
 *   latest_severity_level: string|null,
 *   finding_status_counts: array<string, int>,
 *   severity_level_counts: array<string, int>,
 *   corrective_action_status_counts: array<string, int>,
 *   open_count: int,
 *   action_required_count: int,
 *   resolved_count: int,
 *   closed_count: int,
 *   high_count: int,
 *   critical_count: int,
 *   blocked_corrective_count: int,
 *   history_coverage_count: int,
 *   history_coverage_percent: float|null,
 *   latest_created_at: string|null,
 *   latest_updated_at: string|null,
 *   error: string
 * }
 */
function moghare360_soft_run_finding_review_fetch_summary(): array
{
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'total_findings' => 0,
            'total_history_rows' => 0,
            'latest_finding_id' => null,
            'latest_finding_code' => null,
            'latest_finding_status' => null,
            'latest_corrective_action_status' => null,
            'latest_severity_level' => null,
            'finding_status_counts' => moghare360_soft_run_finding_review_zero_counts(
                moghare360_soft_run_finding_review_finding_status_keys()
            ),
            'severity_level_counts' => moghare360_soft_run_finding_review_zero_counts(
                moghare360_soft_run_finding_review_severity_keys()
            ),
            'corrective_action_status_counts' => moghare360_soft_run_finding_review_zero_counts(
                moghare360_soft_run_finding_review_corrective_status_keys()
            ),
            'open_count' => 0,
            'action_required_count' => 0,
            'resolved_count' => 0,
            'closed_count' => 0,
            'high_count' => 0,
            'critical_count' => 0,
            'blocked_corrective_count' => 0,
            'history_coverage_count' => 0,
            'history_coverage_percent' => null,
            'latest_created_at' => null,
            'latest_updated_at' => null,
            'error' => (string)$ctx['error'],
        ];
    }

    $statusCounts = moghare360_soft_run_finding_review_fetch_status_counts();
    $severityCounts = moghare360_soft_run_finding_review_fetch_severity_counts();
    $correctiveCounts = moghare360_soft_run_finding_review_fetch_corrective_status_counts();
    $coverage = moghare360_soft_run_finding_review_fetch_history_coverage();

    $findingStatusCounts = (array)($statusCounts['counts'] ?? []);
    $severityLevelCounts = (array)($severityCounts['counts'] ?? []);
    $correctiveStatusCounts = (array)($correctiveCounts['counts'] ?? []);

    $latestRows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT TOP (1)
            finding_id,
            finding_code,
            finding_status,
            corrective_action_status,
            severity_level,
            created_at,
            updated_at
         FROM dbo.erp_soft_run_findings
         ORDER BY finding_id DESC',
        []
    );

    $latest = $latestRows[0] ?? [];

    return [
        'ok' => true,
        'total_findings' => (int)($coverage['total_findings'] ?? 0),
        'total_history_rows' => (int)($coverage['total_history_rows'] ?? 0),
        'latest_finding_id' => isset($latest['finding_id']) && (int)$latest['finding_id'] > 0
            ? (int)$latest['finding_id']
            : null,
        'latest_finding_code' => trim((string)($latest['finding_code'] ?? '')) !== ''
            ? (string)$latest['finding_code']
            : null,
        'latest_finding_status' => trim((string)($latest['finding_status'] ?? '')) !== ''
            ? strtoupper(trim((string)$latest['finding_status']))
            : null,
        'latest_corrective_action_status' => trim((string)($latest['corrective_action_status'] ?? '')) !== ''
            ? strtoupper(trim((string)$latest['corrective_action_status']))
            : null,
        'latest_severity_level' => trim((string)($latest['severity_level'] ?? '')) !== ''
            ? strtoupper(trim((string)$latest['severity_level']))
            : null,
        'finding_status_counts' => $findingStatusCounts,
        'severity_level_counts' => $severityLevelCounts,
        'corrective_action_status_counts' => $correctiveStatusCounts,
        'open_count' => (int)($findingStatusCounts['OPEN'] ?? 0),
        'action_required_count' => (int)($findingStatusCounts['ACTION_REQUIRED'] ?? 0),
        'resolved_count' => (int)($findingStatusCounts['RESOLVED'] ?? 0),
        'closed_count' => (int)($findingStatusCounts['CLOSED'] ?? 0),
        'high_count' => (int)($severityLevelCounts['HIGH'] ?? 0),
        'critical_count' => (int)($severityLevelCounts['CRITICAL'] ?? 0),
        'blocked_corrective_count' => (int)($correctiveStatusCounts['BLOCKED'] ?? 0),
        'history_coverage_count' => (int)($coverage['findings_with_history'] ?? 0),
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
function moghare360_soft_run_finding_review_default_operational_notes(): array
{
    return [
        'این داشبورد فقط بازبینی و پایش اقدام اصلاحی یافته‌های Soft Run است — بدون نوشتن پایگاه داده.',
        'رکوردهای یافته از طریق create/workflow WAVE 8A/8B به‌روزرسانی می‌شوند — این صفحه فقط می‌خواند.',
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
 *   recent_findings: list<array<string, string>>,
 *   history_coverage: array<string, mixed>,
 *   review_items: list<string>,
 *   operational_notes: list<string>,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_finding_review_evaluate(): array
{
    $errors = [];
    $reviewItems = [];
    $ctx = moghare360_soft_run_finding_review_db_context();

    if (!$ctx['ok']) {
        if ($ctx['connection'] === false) {
            return [
                'ok' => false,
                'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR,
                'label' => moghare360_soft_run_finding_review_status_label(
                    MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR
                ),
                'message' => (string)$ctx['error'],
                'summary' => [],
                'recent_findings' => [],
                'history_coverage' => [],
                'review_items' => ['اتصال پایگاه داده برقرار نشد.'],
                'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
                'errors' => [(string)$ctx['error']],
            ];
        }

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_review_status_label(
                MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED
            ),
            'message' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_BLOCK_MESSAGE,
            'summary' => [],
            'recent_findings' => [],
            'history_coverage' => [],
            'review_items' => [
                'findings table: ' . (($ctx['findings_table_ok'] ?? false) ? 'OK' : 'MISSING'),
                'history table: ' . (($ctx['history_table_ok'] ?? false) ? 'OK' : 'MISSING'),
            ],
            'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
            'errors' => [MOGHARE360_SOFT_RUN_FINDING_REVIEW_BLOCK_MESSAGE],
        ];
    }

    $summary = moghare360_soft_run_finding_review_fetch_summary();
    $recent = moghare360_soft_run_finding_review_fetch_recent_findings(25);
    $coverage = moghare360_soft_run_finding_review_fetch_history_coverage();

    if (!($summary['ok'] ?? false) || !($recent['ok'] ?? false) || !($coverage['ok'] ?? false)) {
        $readError = (string)($summary['error'] ?? $recent['error'] ?? $coverage['error'] ?? 'خطای خواندن بازبینی یافته‌ها.');

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR,
            'label' => moghare360_soft_run_finding_review_status_label(
                MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ERROR
            ),
            'message' => $readError,
            'summary' => $summary,
            'recent_findings' => [],
            'history_coverage' => $coverage,
            'review_items' => ['خطا در خواندن داده‌های بازبینی یافته‌ها.'],
            'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
            'errors' => [$readError],
        ];
    }

    $totalFindings = (int)($summary['total_findings'] ?? 0);
    $totalHistoryRows = (int)($summary['total_history_rows'] ?? 0);

    if ($totalFindings < 1) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY,
            'label' => moghare360_soft_run_finding_review_status_label(
                MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY
            ),
            'message' => 'جداول یافته موجود است اما هنوز رکورد یافته‌ای ثبت نشده است.',
            'summary' => $summary,
            'recent_findings' => [],
            'history_coverage' => $coverage,
            'review_items' => ['هیچ رکورد یافته‌ای یافت نشد.'],
            'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
            'errors' => [],
        ];
    }

    if ($totalHistoryRows < 1) {
        $errors[] = 'هیچ ردیف تاریخچه یافته یافت نشد.';
        $reviewItems[] = 'پوشش تاریخچه: ۰ ردیف';

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED,
            'label' => moghare360_soft_run_finding_review_status_label(
                MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED
            ),
            'message' => 'رکورد یافته موجود است اما تاریخچه تأیید نشد.',
            'summary' => $summary,
            'recent_findings' => (array)($recent['records'] ?? []),
            'history_coverage' => $coverage,
            'review_items' => $reviewItems,
            'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
            'errors' => $errors,
        ];
    }

    $actionRequired = false;
    $actionItems = [];

    $statusActionRows = customer_core_fetch_rows(
        $ctx['connection'],
        'SELECT finding_id, finding_status, corrective_action_status, severity_level
         FROM dbo.erp_soft_run_findings
         WHERE finding_status IN (?, ?, ?)
            OR corrective_action_status IN (?, ?, ?)
            OR severity_level IN (?, ?)',
        [
            'OPEN', 'UNDER_REVIEW', 'ACTION_REQUIRED',
            'NOT_STARTED', 'IN_PROGRESS', 'BLOCKED',
            'HIGH', 'CRITICAL',
        ]
    );

    foreach ($statusActionRows as $row) {
        $findingId = (string)($row['finding_id'] ?? '');
        $findingStatus = strtoupper(trim((string)($row['finding_status'] ?? '')));
        $correctiveStatus = strtoupper(trim((string)($row['corrective_action_status'] ?? '')));
        $severity = strtoupper(trim((string)($row['severity_level'] ?? '')));

        $reasons = [];
        if (in_array($findingStatus, ['OPEN', 'UNDER_REVIEW', 'ACTION_REQUIRED'], true)) {
            $reasons[] = 'finding=' . $findingStatus;
        }
        if (in_array($correctiveStatus, ['NOT_STARTED', 'IN_PROGRESS', 'BLOCKED'], true)) {
            $reasons[] = 'corrective=' . $correctiveStatus;
        }
        if (in_array($severity, ['HIGH', 'CRITICAL'], true)) {
            $reasons[] = 'severity=' . $severity;
        }

        if ($reasons !== []) {
            $actionRequired = true;
            $actionItems[] = 'یافته #' . $findingId . ' — ' . implode(', ', $reasons);
        }
    }

    if ($actionRequired) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED,
            'label' => moghare360_soft_run_finding_review_status_label(
                MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED
            ),
            'message' => 'رکوردهای یافته موجود است — ' . count($actionItems) . ' مورد نیازمند اقدام یا پایش فعال است.',
            'summary' => $summary,
            'recent_findings' => (array)($recent['records'] ?? []),
            'history_coverage' => $coverage,
            'review_items' => array_merge($reviewItems, $actionItems),
            'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
            'errors' => $errors,
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_READY,
        'label' => moghare360_soft_run_finding_review_status_label(
            MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_READY
        ),
        'message' => 'بازبینی یافته‌های Soft Run — آماده (رکوردها و تاریخچه موجود).',
        'summary' => $summary,
        'recent_findings' => (array)($recent['records'] ?? []),
        'history_coverage' => $coverage,
        'review_items' => [
            'کل یافته‌ها: ' . $totalFindings,
            'کل تاریخچه: ' . $totalHistoryRows,
            'پوشش تاریخچه: ' . (string)($summary['history_coverage_count'] ?? 0)
                . ' / ' . $totalFindings
                . ($summary['history_coverage_percent'] !== null
                    ? ' (' . (string)$summary['history_coverage_percent'] . '%)'
                    : ''),
            'باز: ' . (string)($summary['open_count'] ?? 0),
            'نیازمند اقدام: ' . (string)($summary['action_required_count'] ?? 0),
            'رفع شده: ' . (string)($summary['resolved_count'] ?? 0),
            'بسته شده: ' . (string)($summary['closed_count'] ?? 0),
        ],
        'operational_notes' => moghare360_soft_run_finding_review_default_operational_notes(),
        'errors' => [],
    ];
}
