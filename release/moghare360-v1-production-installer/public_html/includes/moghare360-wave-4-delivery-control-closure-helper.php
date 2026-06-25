<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 4 Delivery Control Closure Helper (Wave 4D)
 *
 * Read-only operational closure summary · no DB writes.
 * Internal delivery control review — NOT final vehicle delivery.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';

const MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE = 'erp_jobcard_delivery_clearances';
const MOGHARE360_WAVE4_CLOSURE_HISTORY_TABLE = 'erp_jobcard_delivery_clearance_history';

const MOGHARE360_WAVE4_CLOSURE_STATUS_READY = 'READY';
const MOGHARE360_WAVE4_CLOSURE_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_WAVE4_CLOSURE_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_WAVE4_CLOSURE_STATUS_ERROR = 'ERROR';

/**
 * @return array{ok: bool, connection: mixed, error: string}
 */
function moghare360_wave_4_closure_db_context(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'connection' => false, 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    return ['ok' => true, 'connection' => $connection, 'error' => ''];
}

/**
 * @return array<string, int>
 */
function moghare360_wave_4_closure_fetch_group_counts($connection, string $column): array
{
    $allowed = ['clearance_status', 'clearance_decision'];

    if (!in_array($column, $allowed, true)) {
        return [];
    }

    $whereClause = '';
    if (customer_core_column_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE, 'is_deleted')) {
        $whereClause = ' WHERE is_deleted = 0';
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT ' . $column . ' AS grp, COUNT(*) AS cnt FROM dbo.erp_jobcard_delivery_clearances'
        . $whereClause
        . ' GROUP BY ' . $column . ' ORDER BY cnt DESC',
        []
    );

    $counts = [];
    foreach ($rows as $row) {
        $key = trim((string)($row['grp'] ?? ''));
        if ($key === '') {
            $key = '(empty)';
        }
        $counts[$key] = (int)($row['cnt'] ?? 0);
    }

    return $counts;
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_wave_4_closure_fetch_clearance_status_counts(): array
{
    $ctx = moghare360_wave_4_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'counts' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE)) {
        return ['ok' => false, 'counts' => [], 'error' => 'جدول erp_jobcard_delivery_clearances یافت نشد.'];
    }

    return [
        'ok' => true,
        'counts' => moghare360_wave_4_closure_fetch_group_counts($connection, 'clearance_status'),
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_wave_4_closure_fetch_clearance_decision_counts(): array
{
    $ctx = moghare360_wave_4_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'counts' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE)) {
        return ['ok' => false, 'counts' => [], 'error' => 'جدول erp_jobcard_delivery_clearances یافت نشد.'];
    }

    return [
        'ok' => true,
        'counts' => moghare360_wave_4_closure_fetch_group_counts($connection, 'clearance_decision'),
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, summary: array<string, mixed>, error: string}
 */
function moghare360_wave_4_closure_fetch_summary(): array
{
    $ctx = moghare360_wave_4_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'summary' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE)) {
        return ['ok' => false, 'summary' => [], 'error' => 'جدول erp_jobcard_delivery_clearances یافت نشد.'];
    }

    $historyTableOk = customer_core_table_exists($connection, MOGHARE360_WAVE4_CLOSURE_HISTORY_TABLE);

    $whereClause = '';
    if (customer_core_column_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE, 'is_deleted')) {
        $whereClause = ' WHERE is_deleted = 0';
    }

    $totalClearances = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcard_delivery_clearances' . $whereClause,
        []
    ) ?? 0);

    $totalHistory = 0;
    if ($historyTableOk) {
        $totalHistory = (int)(customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_delivery_clearance_history',
            []
        ) ?? 0);
    }

    $byStatus = moghare360_wave_4_closure_fetch_group_counts($connection, 'clearance_status');
    $byDecision = moghare360_wave_4_closure_fetch_group_counts($connection, 'clearance_decision');

    $latestCreated = customer_core_scalar(
        $connection,
        'SELECT TOP 1 CONVERT(VARCHAR(30), created_at, 120) FROM dbo.erp_jobcard_delivery_clearances'
        . $whereClause
        . ' ORDER BY created_at DESC',
        []
    );

    $latestUpdated = customer_core_scalar(
        $connection,
        'SELECT TOP 1 CONVERT(VARCHAR(30), updated_at, 120) FROM dbo.erp_jobcard_delivery_clearances'
        . $whereClause
        . ' ORDER BY updated_at DESC',
        []
    );

    $latestHistory = null;
    if ($historyTableOk) {
        $latestHistory = customer_core_scalar(
            $connection,
            'SELECT TOP 1 CONVERT(VARCHAR(30), event_at, 120) FROM dbo.erp_jobcard_delivery_clearance_history ORDER BY event_at DESC',
            []
        );
    }

    $sample = moghare360_wave_4_closure_fetch_sample_jobcard_status(1);

    return [
        'ok' => true,
        'summary' => [
            'total_clearances' => $totalClearances,
            'total_history' => $totalHistory,
            'by_clearance_status' => $byStatus,
            'by_clearance_decision' => $byDecision,
            'cleared_count' => (int)($byStatus['cleared'] ?? 0),
            'not_cleared_count' => (int)($byStatus['not_cleared'] ?? 0),
            'cancelled_count' => (int)($byStatus['cancelled'] ?? 0),
            'latest_clearance_created_at' => $latestCreated ?? '',
            'latest_clearance_updated_at' => $latestUpdated ?? '',
            'latest_clearance_history_event_at' => $latestHistory ?? '',
            'clearance_table_ok' => true,
            'history_table_ok' => $historyTableOk,
            'sample_jobcard_id' => $sample['jobcard_id'] ?? 1,
            'sample_final_readiness_status' => $sample['final_readiness_status'] ?? '',
            'sample_delivery_eligibility_status' => $sample['delivery_eligibility_status'] ?? '',
        ],
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_wave_4_closure_fetch_recent_clearances(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $ctx = moghare360_wave_4_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'rows' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_delivery_clearances یافت نشد.'];
    }

    $whereClause = '';
    if (customer_core_column_exists($connection, MOGHARE360_WAVE4_CLOSURE_CLEARANCE_TABLE, 'is_deleted')) {
        $whereClause = ' WHERE is_deleted = 0';
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' clearance_id, jobcard_id, clearance_status, clearance_decision,
                reviewer_name, clearance_note, created_at, updated_at
         FROM dbo.erp_jobcard_delivery_clearances'
        . $whereClause
        . ' ORDER BY created_at DESC, clearance_id DESC',
        []
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_wave_4_closure_fetch_recent_history(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $ctx = moghare360_wave_4_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'rows' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE4_CLOSURE_HISTORY_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_delivery_clearance_history یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' history_id, clearance_id, jobcard_id, event_code, event_title,
                new_status, clearance_decision, event_at
         FROM dbo.erp_jobcard_delivery_clearance_history
         ORDER BY event_at DESC, history_id DESC',
        []
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

/**
 * @return array{ok: bool, jobcard_id: int, final_readiness_status: string, delivery_eligibility_status: string, error: string}
 */
function moghare360_wave_4_closure_fetch_sample_jobcard_status(int $jobcardId = 1): array
{
    $jobcardId = max(1, $jobcardId);

    if (!function_exists('moghare360_jobcard_final_readiness_evaluate')
        || !function_exists('moghare360_delivery_eligibility_evaluate')) {
        return [
            'ok' => false,
            'jobcard_id' => $jobcardId,
            'final_readiness_status' => '',
            'delivery_eligibility_status' => '',
            'error' => 'Helperهای آمادگی نهایی یا صلاحیت تحویل در دسترس نیست.',
        ];
    }

    $finalReadiness = moghare360_jobcard_final_readiness_evaluate($jobcardId);
    $eligibility = moghare360_delivery_eligibility_evaluate($jobcardId);

    return [
        'ok' => true,
        'jobcard_id' => $jobcardId,
        'final_readiness_status' => (string)($finalReadiness['status'] ?? ''),
        'delivery_eligibility_status' => (string)($eligibility['status'] ?? ''),
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, status: string, message: string, checks: array<string, bool>, errors: list<string>, summary: array<string, mixed>}
 */
function moghare360_wave_4_closure_status(): array
{
    $summaryResult = moghare360_wave_4_closure_fetch_summary();

    $finalReadinessHelperOk = function_exists('moghare360_jobcard_final_readiness_evaluate');
    $eligibilityHelperOk = function_exists('moghare360_delivery_eligibility_evaluate');

    if (!$summaryResult['ok']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_WAVE4_CLOSURE_STATUS_ERROR,
            'message' => 'خواندن خلاصه WAVE 4 ناموفق بود.',
            'checks' => [
                'clearance_table_readable' => false,
                'clearance_history_table_readable' => false,
                'clearance_records_exist' => false,
                'history_records_exist' => false,
                'final_readiness_helper_exists' => $finalReadinessHelperOk,
                'delivery_eligibility_helper_exists' => $eligibilityHelperOk,
                'no_db_write_required' => true,
            ],
            'errors' => [$summaryResult['error']],
            'summary' => [],
        ];
    }

    $summary = $summaryResult['summary'];
    $checks = [
        'clearance_table_readable' => ($summary['clearance_table_ok'] ?? false) === true,
        'clearance_history_table_readable' => ($summary['history_table_ok'] ?? false) === true,
        'clearance_records_exist' => (int)($summary['total_clearances'] ?? 0) > 0,
        'history_records_exist' => (int)($summary['total_history'] ?? 0) > 0,
        'final_readiness_helper_exists' => $finalReadinessHelperOk,
        'delivery_eligibility_helper_exists' => $eligibilityHelperOk,
        'no_db_write_required' => true,
    ];

    $totalClearances = (int)($summary['total_clearances'] ?? 0);
    $totalHistory = (int)($summary['total_history'] ?? 0);

    if ($totalClearances === 0 && $totalHistory === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE4_CLOSURE_STATUS_EMPTY,
            'message' => 'هیچ رکورد Clearance تحویل یا تاریخچه‌ای ثبت نشده است.',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    $ready = $checks['clearance_table_readable']
        && $checks['clearance_history_table_readable']
        && $checks['clearance_records_exist']
        && $checks['history_records_exist']
        && $checks['final_readiness_helper_exists']
        && $checks['delivery_eligibility_helper_exists'];

    if ($ready) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE4_CLOSURE_STATUS_READY,
            'message' => 'بستن عملیاتی WAVE 4 — آماده (Clearance تحویل، تاریخچه و گیت‌های زیرمجموعه فعال).',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_WAVE4_CLOSURE_STATUS_PARTIAL,
        'message' => 'بستن عملیاتی WAVE 4 — ناقص (برخی اجزای کنترل تحویل هنوز کامل نیست).',
        'checks' => $checks,
        'errors' => [],
        'summary' => $summary,
    ];
}

function moghare360_wave_4_closure_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_WAVE4_CLOSURE_STATUS_READY => 'آماده',
        MOGHARE360_WAVE4_CLOSURE_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_WAVE4_CLOSURE_STATUS_EMPTY => 'خالی',
        MOGHARE360_WAVE4_CLOSURE_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
