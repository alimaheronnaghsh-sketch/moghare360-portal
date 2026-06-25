<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 5 Unified Operational Closure Helper (Wave 5C)
 *
 * Read-only operational closure summary · no DB writes.
 * Internal unified command review — NOT final vehicle delivery.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-unified-jobcard-command-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-command-workbench-helper.php';

const MOGHARE360_WAVE5_CLOSURE_JOBCARD_TABLE = 'erp_jobcards';

const MOGHARE360_WAVE5_CLOSURE_STATUS_READY = 'READY';
const MOGHARE360_WAVE5_CLOSURE_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_WAVE5_CLOSURE_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_WAVE5_CLOSURE_STATUS_ERROR = 'ERROR';

/**
 * @return array{ok: bool, connection: mixed, error: string}
 */
function moghare360_wave_5_closure_db_context(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'connection' => false, 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    return ['ok' => true, 'connection' => $connection, 'error' => ''];
}

/**
 * @return list<string>
 */
function moghare360_wave_5_closure_list_columns(): array
{
    return [
        'jobcard_id',
        'jobcard_number',
        'customer_id',
        'vehicle_id',
        'jobcard_status',
        'lifecycle_state',
        'priority_level',
        'reception_at',
        'created_at',
        'updated_at',
    ];
}

/**
 * @return list<string>
 */
function moghare360_wave_5_closure_resolve_select_columns($connection): array
{
    $columns = [];
    foreach (moghare360_wave_5_closure_list_columns() as $column) {
        if (customer_core_column_exists($connection, MOGHARE360_WAVE5_CLOSURE_JOBCARD_TABLE, $column)) {
            $columns[] = $column;
        }
    }

    if ($columns === [] || !in_array('jobcard_id', $columns, true)) {
        return ['jobcard_id'];
    }

    return $columns;
}

/**
 * @return array{ok: bool, jobcards: list<array<string, mixed>>, message: string, errors: list<string>}
 */
function moghare360_wave_5_closure_fetch_jobcards(int $limit = 25): array
{
    if (function_exists('moghare360_jobcard_command_workbench_fetch_jobcards')) {
        return moghare360_jobcard_command_workbench_fetch_jobcards($limit);
    }

    $limit = max(1, min(100, $limit));
    $ctx = moghare360_wave_5_closure_db_context();

    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'jobcards' => [],
            'message' => $ctx['error'],
            'errors' => ['db_connection_failed'],
        ];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE5_CLOSURE_JOBCARD_TABLE)) {
        return [
            'ok' => false,
            'jobcards' => [],
            'message' => 'جدول erp_jobcards یافت نشد.',
            'errors' => ['jobcard_table_not_found'],
        ];
    }

    $selectColumns = moghare360_wave_5_closure_resolve_select_columns($connection);
    $orderBy = in_array('created_at', $selectColumns, true)
        ? 'created_at DESC, jobcard_id DESC'
        : 'jobcard_id DESC';

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' ' . implode(', ', $selectColumns) . '
         FROM dbo.erp_jobcards
         ORDER BY ' . $orderBy,
        []
    );

    $jobcards = [];
    foreach ($rows as $row) {
        $jobcardId = (int)($row['jobcard_id'] ?? 0);
        $unifiedStatus = '';
        if ($jobcardId > 0 && function_exists('moghare360_unified_jobcard_command_evaluate')) {
            $eval = moghare360_unified_jobcard_command_evaluate($jobcardId);
            $unifiedStatus = (string)($eval['status'] ?? '');
        }
        $jobcards[] = array_merge($row, ['unified_status' => $unifiedStatus]);
    }

    return [
        'ok' => true,
        'jobcards' => $jobcards,
        'message' => '',
        'errors' => [],
    ];
}

/**
 * @param list<array<string, mixed>> $jobcards
 * @return array{
 *   by_jobcard_status: array<string, int>,
 *   by_lifecycle_state: array<string, int>,
 *   by_unified_status: array<string, int>
 * }
 */
function moghare360_wave_5_closure_fetch_status_counts(array $jobcards): array
{
    $byJobcardStatus = [];
    $byLifecycleState = [];
    $byUnifiedStatus = [
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY => 0,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED => 0,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED => 0,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY => 0,
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR => 0,
    ];

    foreach ($jobcards as $row) {
        $statusKey = trim((string)($row['jobcard_status'] ?? ''));
        if ($statusKey === '') {
            $statusKey = '(empty)';
        }
        $byJobcardStatus[$statusKey] = ($byJobcardStatus[$statusKey] ?? 0) + 1;

        $lifecycleKey = trim((string)($row['lifecycle_state'] ?? ''));
        if ($lifecycleKey === '') {
            $lifecycleKey = '(empty)';
        }
        $byLifecycleState[$lifecycleKey] = ($byLifecycleState[$lifecycleKey] ?? 0) + 1;

        $unifiedKey = strtoupper(trim((string)($row['unified_status'] ?? '')));
        if ($unifiedKey === '') {
            $unifiedKey = '(unavailable)';
        }
        if (array_key_exists($unifiedKey, $byUnifiedStatus)) {
            $byUnifiedStatus[$unifiedKey]++;
        } else {
            $byUnifiedStatus[$unifiedKey] = ($byUnifiedStatus[$unifiedKey] ?? 0) + 1;
        }
    }

    return [
        'by_jobcard_status' => $byJobcardStatus,
        'by_lifecycle_state' => $byLifecycleState,
        'by_unified_status' => $byUnifiedStatus,
    ];
}

/**
 * @return array{ok: bool, jobcards: list<array<string, mixed>>, message: string, errors: list<string>}
 */
function moghare360_wave_5_closure_fetch_recent_jobcards(int $limit = 10): array
{
    $result = moghare360_wave_5_closure_fetch_jobcards($limit);

    return [
        'ok' => (bool)($result['ok'] ?? false),
        'jobcards' => (array)($result['jobcards'] ?? []),
        'message' => (string)($result['message'] ?? ''),
        'errors' => (array)($result['errors'] ?? []),
    ];
}

/**
 * @return array{ok: bool, jobcard_id: int, unified_status: string, unified_status_label: string, message: string, errors: list<string>}
 */
function moghare360_wave_5_closure_fetch_sample_command_status(int $jobcardId = 1): array
{
    $jobcardId = max(1, $jobcardId);

    if (!function_exists('moghare360_unified_jobcard_command_evaluate')) {
        return [
            'ok' => false,
            'jobcard_id' => $jobcardId,
            'unified_status' => '',
            'unified_status_label' => '',
            'message' => 'Helper مرکز فرمان یکپارچه در دسترس نیست.',
            'errors' => ['command_helper_missing'],
        ];
    }

    $eval = moghare360_unified_jobcard_command_evaluate($jobcardId);

    return [
        'ok' => (bool)($eval['ok'] ?? false),
        'jobcard_id' => $jobcardId,
        'unified_status' => (string)($eval['status'] ?? ''),
        'unified_status_label' => moghare360_wave_5_closure_status_label((string)($eval['status'] ?? '')),
        'message' => (string)($eval['message'] ?? ''),
        'errors' => (array)($eval['errors'] ?? []),
    ];
}

/**
 * @return array{ok: bool, summary: array<string, mixed>, error: string}
 */
function moghare360_wave_5_closure_build_summary(): array
{
    $ctx = moghare360_wave_5_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'summary' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE5_CLOSURE_JOBCARD_TABLE)) {
        return ['ok' => false, 'summary' => [], 'error' => 'جدول erp_jobcards یافت نشد.'];
    }

    $listResult = moghare360_wave_5_closure_fetch_jobcards(25);
    $jobcards = (array)($listResult['jobcards'] ?? []);
    $statusCounts = moghare360_wave_5_closure_fetch_status_counts($jobcards);

    $totalInDb = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcards',
        []
    ) ?? 0);

    $latestCreated = null;
    $latestUpdated = null;
    if (customer_core_column_exists($connection, MOGHARE360_WAVE5_CLOSURE_JOBCARD_TABLE, 'created_at')) {
        $latestCreated = customer_core_scalar(
            $connection,
            'SELECT TOP 1 CONVERT(VARCHAR(30), created_at, 120) FROM dbo.erp_jobcards ORDER BY created_at DESC',
            []
        );
    }
    if (customer_core_column_exists($connection, MOGHARE360_WAVE5_CLOSURE_JOBCARD_TABLE, 'updated_at')) {
        $latestUpdated = customer_core_scalar(
            $connection,
            'SELECT TOP 1 CONVERT(VARCHAR(30), updated_at, 120) FROM dbo.erp_jobcards ORDER BY updated_at DESC',
            []
        );
    }

    $sample = moghare360_wave_5_closure_fetch_sample_command_status(1);

    return [
        'ok' => true,
        'summary' => [
            'total_listed' => count($jobcards),
            'total_in_db' => $totalInDb,
            'by_jobcard_status' => $statusCounts['by_jobcard_status'],
            'by_lifecycle_state' => $statusCounts['by_lifecycle_state'],
            'by_unified_status' => $statusCounts['by_unified_status'],
            'latest_jobcard_created_at' => $latestCreated ?? '',
            'latest_jobcard_updated_at' => $latestUpdated ?? '',
            'sample_jobcard_id' => $sample['jobcard_id'] ?? 1,
            'sample_unified_status' => $sample['unified_status'] ?? '',
            'sample_unified_status_label' => $sample['unified_status_label'] ?? '',
            'sample_unified_message' => $sample['message'] ?? '',
            'jobcard_table_ok' => true,
            'command_center_helper_ok' => function_exists('moghare360_unified_jobcard_command_evaluate'),
            'workbench_helper_ok' => function_exists('moghare360_jobcard_command_workbench_fetch_jobcards'),
        ],
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, status: string, message: string, checks: array<string, bool>, errors: list<string>, summary: array<string, mixed>}
 */
function moghare360_wave_5_closure_status(): array
{
    $summaryResult = moghare360_wave_5_closure_build_summary();

    if (!$summaryResult['ok']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_WAVE5_CLOSURE_STATUS_ERROR,
            'message' => 'خواندن خلاصه WAVE 5 ناموفق بود.',
            'checks' => [
                'jobcard_table_readable' => false,
                'jobcards_exist' => false,
                'command_center_helper_exists' => function_exists('moghare360_unified_jobcard_command_evaluate'),
                'workbench_helper_exists' => function_exists('moghare360_jobcard_command_workbench_fetch_jobcards'),
                'sample_command_not_error' => false,
                'no_db_write_required' => true,
            ],
            'errors' => [$summaryResult['error']],
            'summary' => [],
        ];
    }

    $summary = $summaryResult['summary'];
    $sampleStatus = strtoupper(trim((string)($summary['sample_unified_status'] ?? '')));
    $sampleNotError = $sampleStatus !== '' && $sampleStatus !== MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR;

    $checks = [
        'jobcard_table_readable' => ($summary['jobcard_table_ok'] ?? false) === true,
        'jobcards_exist' => (int)($summary['total_in_db'] ?? 0) > 0,
        'command_center_helper_exists' => ($summary['command_center_helper_ok'] ?? false) === true,
        'workbench_helper_exists' => ($summary['workbench_helper_ok'] ?? false) === true,
        'sample_command_not_error' => $sampleNotError,
        'no_db_write_required' => true,
    ];

    $totalInDb = (int)($summary['total_in_db'] ?? 0);

    if ($totalInDb === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE5_CLOSURE_STATUS_EMPTY,
            'message' => 'هیچ کارت کاری در erp_jobcards ثبت نشده است.',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    $ready = $checks['jobcard_table_readable']
        && $checks['jobcards_exist']
        && $checks['command_center_helper_exists']
        && $checks['workbench_helper_exists']
        && $checks['sample_command_not_error'];

    if ($ready) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE5_CLOSURE_STATUS_READY,
            'message' => 'بستن عملیاتی WAVE 5 — آماده (مرکز فرمان یکپارچه و میز فرمان فعال).',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_WAVE5_CLOSURE_STATUS_PARTIAL,
        'message' => 'بستن عملیاتی WAVE 5 — ناقص (برخی اجزای پوشش فرمان یکپارچه هنوز کامل نیست).',
        'checks' => $checks,
        'errors' => [],
        'summary' => $summary,
    ];
}

function moghare360_wave_5_closure_status_label(string $status): string
{
    if (function_exists('moghare360_unified_jobcard_command_status_label')) {
        $unified = moghare360_unified_jobcard_command_status_label($status);
        if ($unified !== 'نامشخص') {
            return $unified;
        }
    }

    return match (strtoupper(trim($status))) {
        MOGHARE360_WAVE5_CLOSURE_STATUS_READY => 'آماده',
        MOGHARE360_WAVE5_CLOSURE_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_WAVE5_CLOSURE_STATUS_EMPTY => 'خالی',
        MOGHARE360_WAVE5_CLOSURE_STATUS_ERROR => 'خطا',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY => 'آماده عملیاتی',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED => 'نیازمند اقدام',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY => 'خالی',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
