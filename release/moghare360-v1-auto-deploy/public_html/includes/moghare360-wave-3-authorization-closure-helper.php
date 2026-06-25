<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 3 Authorization Closure Helper (Wave 3D)
 *
 * Read-only operational closure summary · no DB writes.
 * Internal controlled authorization — NOT final legal e-signature.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_WAVE3_CLOSURE_AUTH_TABLE = 'erp_jobcard_authorizations';
const MOGHARE360_WAVE3_CLOSURE_HISTORY_TABLE = 'erp_jobcard_authorization_history';

const MOGHARE360_WAVE3_CLOSURE_STATUS_READY = 'READY';
const MOGHARE360_WAVE3_CLOSURE_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_WAVE3_CLOSURE_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_WAVE3_CLOSURE_STATUS_ERROR = 'ERROR';

const MOGHARE360_WAVE3_CLOSURE_WORKFLOW_EVENT = 'AUTHORIZATION_STATUS_CHANGED';

/**
 * @return array{ok: bool, connection: mixed, error: string}
 */
function moghare360_wave_3_closure_db_context(): array
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
function moghare360_wave_3_closure_fetch_group_counts($connection, string $column): array
{
    $allowed = ['authorization_status', 'authorization_type', 'authorization_method'];

    if (!in_array($column, $allowed, true)) {
        return [];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT ' . $column . ' AS grp, COUNT(*) AS cnt FROM dbo.erp_jobcard_authorizations GROUP BY ' . $column . ' ORDER BY cnt DESC',
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
function moghare360_wave_3_closure_fetch_status_counts(): array
{
    $ctx = moghare360_wave_3_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'counts' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_AUTH_TABLE)) {
        return ['ok' => false, 'counts' => [], 'error' => 'جدول erp_jobcard_authorizations یافت نشد.'];
    }

    return [
        'ok' => true,
        'counts' => moghare360_wave_3_closure_fetch_group_counts($connection, 'authorization_status'),
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_wave_3_closure_fetch_type_counts(): array
{
    $ctx = moghare360_wave_3_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'counts' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_AUTH_TABLE)) {
        return ['ok' => false, 'counts' => [], 'error' => 'جدول erp_jobcard_authorizations یافت نشد.'];
    }

    return [
        'ok' => true,
        'counts' => moghare360_wave_3_closure_fetch_group_counts($connection, 'authorization_type'),
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, counts: array<string, int>, error: string}
 */
function moghare360_wave_3_closure_fetch_method_counts(): array
{
    $ctx = moghare360_wave_3_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'counts' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_AUTH_TABLE)) {
        return ['ok' => false, 'counts' => [], 'error' => 'جدول erp_jobcard_authorizations یافت نشد.'];
    }

    return [
        'ok' => true,
        'counts' => moghare360_wave_3_closure_fetch_group_counts($connection, 'authorization_method'),
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, summary: array<string, mixed>, error: string}
 */
function moghare360_wave_3_closure_fetch_summary(): array
{
    $ctx = moghare360_wave_3_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'summary' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_AUTH_TABLE)) {
        return ['ok' => false, 'summary' => [], 'error' => 'جدول erp_jobcard_authorizations یافت نشد.'];
    }

    $historyTableOk = customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_HISTORY_TABLE);

    $totalAuth = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcard_authorizations',
        []
    ) ?? 0);

    $totalHistory = 0;
    $workflowHistoryCount = 0;

    if ($historyTableOk) {
        $totalHistory = (int)(customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_authorization_history',
            []
        ) ?? 0);

        $workflowHistoryCount = (int)(customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_authorization_history WHERE event_code = ?',
            [MOGHARE360_WAVE3_CLOSURE_WORKFLOW_EVENT]
        ) ?? 0);
    }

    $byStatus = moghare360_wave_3_closure_fetch_group_counts($connection, 'authorization_status');
    $byType = moghare360_wave_3_closure_fetch_group_counts($connection, 'authorization_type');
    $byMethod = moghare360_wave_3_closure_fetch_group_counts($connection, 'authorization_method');

    $approvedCount = (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_authorizations WHERE authorization_status = 'approved'",
        []
    ) ?? 0);

    $pendingCount = (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_authorizations WHERE authorization_status IN ('draft', 'pending_customer_approval')",
        []
    ) ?? 0);

    $rejectedCount = (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_authorizations WHERE authorization_status = 'rejected'",
        []
    ) ?? 0);

    $cancelledCount = (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_authorizations WHERE authorization_status = 'cancelled'",
        []
    ) ?? 0);

    $latestCreated = customer_core_scalar(
        $connection,
        'SELECT TOP 1 CONVERT(VARCHAR(30), created_at, 120) FROM dbo.erp_jobcard_authorizations ORDER BY created_at DESC',
        []
    );

    $latestUpdated = customer_core_scalar(
        $connection,
        'SELECT TOP 1 CONVERT(VARCHAR(30), updated_at, 120) FROM dbo.erp_jobcard_authorizations ORDER BY updated_at DESC',
        []
    );

    $latestHistory = null;
    if ($historyTableOk) {
        $latestHistory = customer_core_scalar(
            $connection,
            'SELECT TOP 1 CONVERT(VARCHAR(30), event_at, 120) FROM dbo.erp_jobcard_authorization_history ORDER BY event_at DESC',
            []
        );
    }

    return [
        'ok' => true,
        'summary' => [
            'total_authorizations' => $totalAuth,
            'total_history' => $totalHistory,
            'workflow_history_count' => $workflowHistoryCount,
            'by_authorization_status' => $byStatus,
            'by_authorization_type' => $byType,
            'by_authorization_method' => $byMethod,
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'cancelled_count' => $cancelledCount,
            'latest_authorization_created_at' => $latestCreated ?? '',
            'latest_authorization_updated_at' => $latestUpdated ?? '',
            'latest_history_event_at' => $latestHistory ?? '',
            'authorization_table_ok' => true,
            'history_table_ok' => $historyTableOk,
        ],
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_wave_3_closure_fetch_recent_authorizations(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $ctx = moghare360_wave_3_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'rows' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_AUTH_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_authorizations یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' authorization_id, jobcard_id, authorization_type, authorization_status,
                authorization_method, customer_name, created_at
         FROM dbo.erp_jobcard_authorizations
         ORDER BY created_at DESC, authorization_id DESC',
        []
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_wave_3_closure_fetch_recent_history(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $ctx = moghare360_wave_3_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'rows' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE3_CLOSURE_HISTORY_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_authorization_history یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' history_id, authorization_id, jobcard_id, event_code, event_title,
                old_status, new_status, event_at
         FROM dbo.erp_jobcard_authorization_history
         ORDER BY event_at DESC, history_id DESC',
        []
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

/**
 * @return array{ok: bool, status: string, message: string, checks: array<string, bool>, errors: list<string>, summary: array<string, mixed>}
 */
function moghare360_wave_3_closure_status(): array
{
    $summaryResult = moghare360_wave_3_closure_fetch_summary();

    if (!$summaryResult['ok']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_WAVE3_CLOSURE_STATUS_ERROR,
            'message' => 'خواندن خلاصه WAVE 3 ناموفق بود.',
            'checks' => [
                'authorization_table_readable' => false,
                'authorization_history_table_readable' => false,
                'authorization_records_exist' => false,
                'history_records_exist' => false,
                'workflow_history_exists' => false,
                'no_db_write_required' => true,
            ],
            'errors' => [$summaryResult['error']],
            'summary' => [],
        ];
    }

    $summary = $summaryResult['summary'];
    $checks = [
        'authorization_table_readable' => ($summary['authorization_table_ok'] ?? false) === true,
        'authorization_history_table_readable' => ($summary['history_table_ok'] ?? false) === true,
        'authorization_records_exist' => (int)($summary['total_authorizations'] ?? 0) > 0,
        'history_records_exist' => (int)($summary['total_history'] ?? 0) > 0,
        'workflow_history_exists' => (int)($summary['workflow_history_count'] ?? 0) > 0,
        'no_db_write_required' => true,
    ];

    $totalAuth = (int)($summary['total_authorizations'] ?? 0);
    $totalHistory = (int)($summary['total_history'] ?? 0);

    if ($totalAuth === 0 && $totalHistory === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE3_CLOSURE_STATUS_EMPTY,
            'message' => 'هیچ رکورد مجوز یا تاریخچه‌ای ثبت نشده است.',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    $ready = $checks['authorization_table_readable']
        && $checks['authorization_history_table_readable']
        && $checks['authorization_records_exist']
        && $checks['history_records_exist']
        && $checks['workflow_history_exists'];

    if ($ready) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE3_CLOSURE_STATUS_READY,
            'message' => 'بستن عملیاتی WAVE 3 — آماده (مجوز، گردش کار و تاریخچه فعال).',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_WAVE3_CLOSURE_STATUS_PARTIAL,
        'message' => 'بستن عملیاتی WAVE 3 — ناقص (برخی اجزای مجوز/تاریخچه هنوز کامل نیست).',
        'checks' => $checks,
        'errors' => [],
        'summary' => $summary,
    ];
}

function moghare360_wave_3_closure_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_WAVE3_CLOSURE_STATUS_READY => 'آماده',
        MOGHARE360_WAVE3_CLOSURE_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_WAVE3_CLOSURE_STATUS_EMPTY => 'خالی',
        MOGHARE360_WAVE3_CLOSURE_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
