<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Command Workbench Helper (Wave 5B)
 *
 * Read-only operator workbench · JobCard list and navigation.
 * Does not perform final delivery · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-unified-jobcard-command-helper.php';

const MOGHARE360_JOBCARD_COMMAND_WORKBENCH_TABLE = 'erp_jobcards';

const MOGHARE360_JOBCARD_COMMAND_WORKBENCH_INTERNAL_NOTICE =
    'This is read-only operator navigation — not final vehicle delivery. No delivery action on this page.';

/**
 * @return list<string>
 */
function moghare360_jobcard_command_workbench_list_columns(): array
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
function moghare360_jobcard_command_workbench_resolve_select_columns($connection): array
{
    $columns = [];
    foreach (moghare360_jobcard_command_workbench_list_columns() as $column) {
        if (customer_core_column_exists($connection, MOGHARE360_JOBCARD_COMMAND_WORKBENCH_TABLE, $column)) {
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
function moghare360_jobcard_command_workbench_fetch_jobcards(int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'jobcards' => [],
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
            'errors' => ['db_connection_failed'],
        ];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_COMMAND_WORKBENCH_TABLE)) {
        return [
            'ok' => false,
            'jobcards' => [],
            'message' => 'جدول erp_jobcards یافت نشد.',
            'errors' => ['jobcard_table_not_found'],
        ];
    }

    $selectColumns = moghare360_jobcard_command_workbench_resolve_select_columns($connection);
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
        $unifiedStatusLabel = '';

        if ($jobcardId > 0 && function_exists('moghare360_unified_jobcard_command_evaluate')) {
            $eval = moghare360_unified_jobcard_command_evaluate($jobcardId);
            $unifiedStatus = (string)($eval['status'] ?? '');
            $unifiedStatusLabel = moghare360_jobcard_command_workbench_status_label($unifiedStatus);
        }

        $jobcards[] = array_merge($row, [
            'unified_status' => $unifiedStatus,
            'unified_status_label' => $unifiedStatusLabel,
        ]);
    }

    return [
        'ok' => true,
        'jobcards' => $jobcards,
        'message' => '',
        'errors' => [],
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_jobcard_command_workbench_build_links(int $jobcardId): array
{
    $id = (string)max(1, $jobcardId);

    return [
        'command_center' => 'erp-jobcard-command-center.php?jobcard_id=' . $id,
        'final_readiness' => 'erp-jobcard-final-readiness.php?jobcard_id=' . $id,
        'delivery_eligibility' => 'erp-jobcard-delivery-eligibility.php?jobcard_id=' . $id,
        'clearance_preview' => 'erp-jobcard-delivery-clearance-preview.php?jobcard_id=' . $id,
        'evidence_review' => 'erp-jobcard-evidence-review.php?jobcard_id=' . $id,
        'authorization_gate' => 'erp-jobcard-authorization-gate.php?jobcard_id=' . $id,
    ];
}

/**
 * @return array{ok: bool, snapshot: array<string, mixed>, message: string, errors: list<string>}
 */
function moghare360_jobcard_command_workbench_fetch_jobcard_snapshot(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'snapshot' => [],
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'snapshot' => [],
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
            'errors' => ['db_connection_failed'],
        ];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_COMMAND_WORKBENCH_TABLE)) {
        return [
            'ok' => false,
            'snapshot' => [],
            'message' => 'جدول erp_jobcards یافت نشد.',
            'errors' => ['jobcard_table_not_found'],
        ];
    }

    $selectColumns = moghare360_jobcard_command_workbench_resolve_select_columns($connection);

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP 1 ' . implode(', ', $selectColumns) . '
         FROM dbo.erp_jobcards
         WHERE jobcard_id = ?',
        [$jobcardId]
    );

    if ($rows === []) {
        return [
            'ok' => false,
            'snapshot' => [],
            'message' => 'کارت کار در erp_jobcards یافت نشد.',
            'errors' => ['jobcard_not_found'],
        ];
    }

    $row = $rows[0];
    $unifiedStatus = '';
    $unifiedStatusLabel = '';
    $unifiedMessage = '';

    if (function_exists('moghare360_unified_jobcard_command_evaluate')) {
        $eval = moghare360_unified_jobcard_command_evaluate($jobcardId);
        $unifiedStatus = (string)($eval['status'] ?? '');
        $unifiedStatusLabel = moghare360_jobcard_command_workbench_status_label($unifiedStatus);
        $unifiedMessage = (string)($eval['message'] ?? '');
    }

    $snapshot = [
        'jobcard_id' => (string)($row['jobcard_id'] ?? ''),
        'jobcard_number' => (string)($row['jobcard_number'] ?? ''),
        'customer_id' => (string)($row['customer_id'] ?? ''),
        'vehicle_id' => (string)($row['vehicle_id'] ?? ''),
        'jobcard_status' => (string)($row['jobcard_status'] ?? ''),
        'lifecycle_state' => (string)($row['lifecycle_state'] ?? ''),
        'priority_level' => (string)($row['priority_level'] ?? ''),
        'reception_at' => (string)($row['reception_at'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'unified_status' => $unifiedStatus,
        'unified_status_label' => $unifiedStatusLabel,
        'unified_message' => $unifiedMessage,
        'links' => moghare360_jobcard_command_workbench_build_links($jobcardId),
    ];

    return [
        'ok' => true,
        'snapshot' => $snapshot,
        'message' => '',
        'errors' => [],
    ];
}

/**
 * @param list<array<string, mixed>> $jobcards
 * @return array{total: int, by_jobcard_status: array<string, int>, by_lifecycle_state: array<string, int>, by_unified_status: array<string, int>}
 */
function moghare360_jobcard_command_workbench_status_summary(array $jobcards): array
{
    $byJobcardStatus = [];
    $byLifecycleState = [];
    $byUnifiedStatus = [];

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

        $unifiedKey = trim((string)($row['unified_status'] ?? ''));
        if ($unifiedKey === '') {
            $unifiedKey = '(unavailable)';
        }
        $byUnifiedStatus[$unifiedKey] = ($byUnifiedStatus[$unifiedKey] ?? 0) + 1;
    }

    return [
        'total' => count($jobcards),
        'by_jobcard_status' => $byJobcardStatus,
        'by_lifecycle_state' => $byLifecycleState,
        'by_unified_status' => $byUnifiedStatus,
    ];
}

function moghare360_jobcard_command_workbench_status_label(string $status): string
{
    if (function_exists('moghare360_unified_jobcard_command_status_label')) {
        $unifiedLabel = moghare360_unified_jobcard_command_status_label($status);
        if ($unifiedLabel !== 'نامشخص') {
            return $unifiedLabel;
        }
    }

    return match (strtoupper(trim($status))) {
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY => 'آماده عملیاتی',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED => 'نیازمند اقدام',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY => 'خالی',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
