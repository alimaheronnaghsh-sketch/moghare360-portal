<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Unified JobCard Command Helper (Wave 5A)
 *
 * Read-only unified operational command center across WAVE 2–4 layers.
 * Does not perform final delivery · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-timeline-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';

const MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY = 'OPERATION_READY';
const MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED = 'ACTION_REQUIRED';
const MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR = 'ERROR';

const MOGHARE360_UNIFIED_JOBCARD_COMMAND_INTERNAL_NOTICE =
    'This is read-only operational command review — not final vehicle delivery. No delivery action on this page.';

const MOGHARE360_UNIFIED_JOBCARD_COMMAND_JOBCARD_TABLE = 'erp_jobcards';

/**
 * @return list<string>
 */
function moghare360_unified_jobcard_command_jobcard_columns(): array
{
    return [
        'jobcard_id',
        'jobcard_number',
        'customer_id',
        'vehicle_id',
        'relation_id',
        'reception_user_id',
        'assigned_team_id',
        'jobcard_status',
        'reception_at',
        'promised_at',
        'intake_mileage',
        'fuel_level',
        'customer_complaint',
        'requested_services',
        'initial_vehicle_condition',
        'internal_notes',
        'priority_level',
        'lifecycle_state',
        'created_at',
        'updated_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];
}

/**
 * @return array{ok: bool, jobcard: array<string, string>|null, message: string, errors: list<string>}
 */
function moghare360_unified_jobcard_command_fetch_jobcard(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'jobcard' => null,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'jobcard' => null,
            'message' => 'اتصال به پایگاه داده برقرار نشد.',
            'errors' => ['db_connection_failed'],
        ];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_UNIFIED_JOBCARD_COMMAND_JOBCARD_TABLE)) {
        return [
            'ok' => false,
            'jobcard' => null,
            'message' => 'جدول erp_jobcards یافت نشد.',
            'errors' => ['jobcard_table_not_found'],
        ];
    }

    $selectColumns = [];
    foreach (moghare360_unified_jobcard_command_jobcard_columns() as $column) {
        if (customer_core_column_exists($connection, MOGHARE360_UNIFIED_JOBCARD_COMMAND_JOBCARD_TABLE, $column)) {
            $selectColumns[] = $column;
        }
    }

    if ($selectColumns === [] || !in_array('jobcard_id', $selectColumns, true)) {
        $selectColumns = ['jobcard_id'];
    }

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
            'jobcard' => null,
            'message' => 'کارت کار در erp_jobcards یافت نشد.',
            'errors' => ['jobcard_not_found'],
        ];
    }

    return [
        'ok' => true,
        'jobcard' => $rows[0],
        'message' => '',
        'errors' => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function moghare360_unified_jobcard_command_fetch_evidence(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    $review = moghare360_jobcard_evidence_review($jobcardId);
    $timeline = moghare360_jobcard_evidence_timeline_review($jobcardId);

    $review['timeline_event_count'] = count((array)($timeline['timeline'] ?? []));
    $review['timeline_warnings'] = (array)($timeline['warnings'] ?? []);

    return $review;
}

/**
 * @return array<string, mixed>
 */
function moghare360_unified_jobcard_command_fetch_authorization(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    return moghare360_contract_authorization_gate_review($jobcardId);
}

/**
 * @return array<string, mixed>
 */
function moghare360_unified_jobcard_command_fetch_final_readiness(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    return moghare360_jobcard_final_readiness_evaluate($jobcardId);
}

/**
 * @return array<string, mixed>
 */
function moghare360_unified_jobcard_command_fetch_delivery_eligibility(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    return moghare360_delivery_eligibility_evaluate($jobcardId);
}

/**
 * @return array<string, mixed>
 */
function moghare360_unified_jobcard_command_fetch_delivery_clearance(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'schema_status' => MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED,
            'records' => [],
            'latest' => null,
            'cleared_count' => 0,
            'not_cleared_count' => 0,
            'has_cleared' => false,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    $list = moghare360_delivery_clearance_list_by_jobcard($jobcardId);
    $records = (array)($list['records'] ?? []);
    $clearedCount = 0;
    $notClearedCount = 0;
    $latest = $records[0] ?? null;

    foreach ($records as $row) {
        $status = (string)($row['clearance_status'] ?? '');
        if ($status === 'cleared') {
            $clearedCount++;
        }
        if ($status === 'not_cleared') {
            $notClearedCount++;
        }
    }

    return [
        'ok' => (bool)($list['ok'] ?? false),
        'schema_status' => (string)($list['schema_status'] ?? MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED),
        'records' => $records,
        'record_count' => count($records),
        'latest' => $latest,
        'cleared_count' => $clearedCount,
        'not_cleared_count' => $notClearedCount,
        'has_cleared' => $clearedCount > 0,
        'message' => (string)($list['message'] ?? ''),
        'errors' => (array)($list['errors'] ?? []),
    ];
}

function moghare360_unified_jobcard_command_has_blocking_clearance(array $clearance): bool
{
    foreach ((array)($clearance['records'] ?? []) as $row) {
        $status = (string)($row['clearance_status'] ?? '');
        $decision = (string)($row['clearance_decision'] ?? '');
        if ($status === 'not_cleared' && $decision === 'not_cleared_missing_requirements') {
            return true;
        }
    }

    return false;
}

/**
 * @return array{ok: bool, status: string, jobcard_id: int, jobcard: array<string, mixed>, evidence: array<string, mixed>, authorization: array<string, mixed>, final_readiness: array<string, mixed>, delivery_eligibility: array<string, mixed>, delivery_clearance: array<string, mixed>, ready_items: list<string>, action_items: list<string>, blocked_items: list<string>, missing_items: list<string>, message: string, errors: list<string>}
 */
function moghare360_unified_jobcard_command_evaluate(int $jobcardId): array
{
    $emptyResult = static function (int $id, string $message, array $errors = []): array {
        return [
            'ok' => false,
            'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR,
            'jobcard_id' => $id,
            'jobcard' => [],
            'evidence' => [],
            'authorization' => [],
            'final_readiness' => [],
            'delivery_eligibility' => [],
            'delivery_clearance' => [],
            'ready_items' => [],
            'action_items' => [],
            'blocked_items' => [],
            'missing_items' => [],
            'message' => $message,
            'errors' => $errors,
        ];
    };

    if ($jobcardId < 1) {
        return $emptyResult(
            $jobcardId,
            'شناسه کارت کار نامعتبر است.',
            ['شناسه کارت کار باید عدد مثبت باشد.']
        );
    }

    $jobcardFetch = moghare360_unified_jobcard_command_fetch_jobcard($jobcardId);
    $evidence = moghare360_unified_jobcard_command_fetch_evidence($jobcardId);
    $authorization = moghare360_unified_jobcard_command_fetch_authorization($jobcardId);
    $finalReadiness = moghare360_unified_jobcard_command_fetch_final_readiness($jobcardId);
    $deliveryEligibility = moghare360_unified_jobcard_command_fetch_delivery_eligibility($jobcardId);
    $deliveryClearance = moghare360_unified_jobcard_command_fetch_delivery_clearance($jobcardId);

    $jobcard = (array)($jobcardFetch['jobcard'] ?? []);
    $evidenceStatus = strtoupper(trim((string)($evidence['status'] ?? '')));
    $authStatus = strtoupper(trim((string)($authorization['status'] ?? '')));
    $finalStatus = strtoupper(trim((string)($finalReadiness['status'] ?? '')));
    $eligibilityStatus = strtoupper(trim((string)($deliveryEligibility['status'] ?? '')));

    $readyItems = [];
    $actionItems = [];
    $blockedItems = array_values(array_unique(array_merge(
        (array)($finalReadiness['blocked_items'] ?? []),
        (array)($deliveryEligibility['blocking_items'] ?? [])
    )));
    $missingItems = array_values(array_unique(array_merge(
        (array)($finalReadiness['missing_items'] ?? []),
        (array)($evidence['missing'] ?? []),
        (array)($authorization['missing'] ?? [])
    )));

    if (!$jobcardFetch['ok'] && in_array('jobcard_not_found', (array)($jobcardFetch['errors'] ?? []), true)) {
        return $emptyResult($jobcardId, 'کارت کار یافت نشد.', (array)($jobcardFetch['errors'] ?? []));
    }

    $readErrors = [];
    if ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR && !($evidence['ok'] ?? false)) {
        $readErrors[] = 'evidence_read_failure';
    }
    if ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR && !($authorization['ok'] ?? false)) {
        $readErrors[] = 'authorization_read_failure';
    }
    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR && !($finalReadiness['ok'] ?? false)) {
        $readErrors[] = 'final_readiness_read_failure';
    }
    if ($eligibilityStatus === MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR && !($deliveryEligibility['ok'] ?? false)) {
        $readErrors[] = 'delivery_eligibility_read_failure';
    }

    if ($readErrors !== []) {
        return [
            'ok' => false,
            'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'jobcard' => $jobcard,
            'evidence' => $evidence,
            'authorization' => $authorization,
            'final_readiness' => $finalReadiness,
            'delivery_eligibility' => $deliveryEligibility,
            'delivery_clearance' => $deliveryClearance,
            'ready_items' => [],
            'action_items' => [],
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'خواندن وضعیت عملیاتی یکپارچه ناموفق بود.',
            'errors' => $readErrors,
        ];
    }

    $hasOperationalData = $evidenceStatus !== MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY
        || $authStatus !== MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY
        || $finalStatus !== MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY
        || $eligibilityStatus !== MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY
        || (int)($deliveryClearance['record_count'] ?? 0) > 0
        || $jobcard !== [];

    if (!$hasOperationalData) {
        return [
            'ok' => true,
            'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY,
            'jobcard_id' => $jobcardId,
            'jobcard' => $jobcard,
            'evidence' => $evidence,
            'authorization' => $authorization,
            'final_readiness' => $finalReadiness,
            'delivery_eligibility' => $deliveryEligibility,
            'delivery_clearance' => $deliveryClearance,
            'ready_items' => [],
            'action_items' => [],
            'blocked_items' => [],
            'missing_items' => [],
            'message' => 'داده عملیاتی معنادار برای این کارت کار وجود ندارد.',
            'errors' => [],
        ];
    }

    $isBlocked = $finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED
        || $eligibilityStatus === MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE
        || $authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED
        || $evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR
        || moghare360_unified_jobcard_command_has_blocking_clearance($deliveryClearance);

    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED) {
        $blockedItems[] = 'final_readiness_blocked';
    }
    if ($eligibilityStatus === MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE) {
        $blockedItems[] = 'delivery_eligibility_not_eligible';
    }
    if ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED) {
        $blockedItems[] = 'authorization_gate_blocked';
    }
    if ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR) {
        $blockedItems[] = 'evidence_gate_blocked';
    }
    if (moghare360_unified_jobcard_command_has_blocking_clearance($deliveryClearance)) {
        $blockedItems[] = 'clearance_not_cleared_missing_requirements';
    }

    $blockedItems = array_values(array_unique($blockedItems));

    if ($isBlocked) {
        return [
            'ok' => true,
            'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED,
            'jobcard_id' => $jobcardId,
            'jobcard' => $jobcard,
            'evidence' => $evidence,
            'authorization' => $authorization,
            'final_readiness' => $finalReadiness,
            'delivery_eligibility' => $deliveryEligibility,
            'delivery_clearance' => $deliveryClearance,
            'ready_items' => [],
            'action_items' => (array)($finalReadiness['partial_items'] ?? []),
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'عملیات مسدود — گیت‌های زیرمجموعه یا Clearance وضعیت بحرانی دارند.',
            'errors' => [],
        ];
    }

    $hasClearedClearance = ($deliveryClearance['has_cleared'] ?? false) === true;
    $operationReady = $finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY
        && $eligibilityStatus === MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE
        && $hasClearedClearance;

    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY) {
        $readyItems[] = 'final_readiness_ready';
    }
    if ($eligibilityStatus === MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE) {
        $readyItems[] = 'delivery_eligibility_eligible';
    }
    if ($hasClearedClearance) {
        $readyItems[] = 'delivery_clearance_cleared';
    }
    if ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE) {
        $readyItems[] = 'evidence_complete';
    }
    if ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY) {
        $readyItems[] = 'authorization_gate_ready';
    }

    if ($operationReady) {
        return [
            'ok' => true,
            'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY,
            'jobcard_id' => $jobcardId,
            'jobcard' => $jobcard,
            'evidence' => $evidence,
            'authorization' => $authorization,
            'final_readiness' => $finalReadiness,
            'delivery_eligibility' => $deliveryEligibility,
            'delivery_clearance' => $deliveryClearance,
            'ready_items' => array_values(array_unique($readyItems)),
            'action_items' => [],
            'blocked_items' => [],
            'missing_items' => $missingItems,
            'message' => 'وضعیت عملیاتی یکپارچه آماده است — تحویل نهایی هنوز در این لایه انجام نمی‌شود.',
            'errors' => [],
        ];
    }

    foreach ((array)($finalReadiness['partial_items'] ?? []) as $item) {
        $actionItems[] = (string)$item;
    }
    foreach ((array)($deliveryEligibility['review_items'] ?? []) as $item) {
        $actionItems[] = (string)$item;
    }
    if (!$hasClearedClearance && (int)($deliveryClearance['record_count'] ?? 0) === 0) {
        $actionItems[] = 'delivery_clearance_not_recorded';
    } elseif (!$hasClearedClearance) {
        $actionItems[] = 'delivery_clearance_not_cleared_yet';
    }
    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL) {
        $actionItems[] = 'final_readiness_partial';
    }
    if ($eligibilityStatus === MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED) {
        $actionItems[] = 'delivery_eligibility_review_required';
    }
    if ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL) {
        $actionItems[] = 'evidence_partial';
    }
    if ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL) {
        $actionItems[] = 'authorization_gate_partial';
    }

    $actionItems = array_values(array_unique($actionItems));

    return [
        'ok' => true,
        'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED,
        'jobcard_id' => $jobcardId,
        'jobcard' => $jobcard,
        'evidence' => $evidence,
        'authorization' => $authorization,
        'final_readiness' => $finalReadiness,
        'delivery_eligibility' => $deliveryEligibility,
        'delivery_clearance' => $deliveryClearance,
        'ready_items' => array_values(array_unique($readyItems)),
        'action_items' => $actionItems,
        'blocked_items' => [],
        'missing_items' => $missingItems,
        'message' => 'اقدام عملیاتی لازم است — برخی الزامات هنوز تکمیل نشده‌اند.',
        'errors' => [],
    ];
}

function moghare360_unified_jobcard_command_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY => 'آماده عملیاتی',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED => 'نیازمند اقدام',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY => 'خالی',
        MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return array<string, string>
 */
function moghare360_unified_jobcard_command_item_labels(): array
{
    return [
        'final_readiness_ready' => 'آمادگی نهایی — READY',
        'delivery_eligibility_eligible' => 'صلاحیت تحویل — ELIGIBLE',
        'delivery_clearance_cleared' => 'Clearance تحویل — cleared',
        'evidence_complete' => 'مدارک — COMPLETE',
        'authorization_gate_ready' => 'گیت مجوز — READY',
        'final_readiness_partial' => 'آمادگی نهایی — PARTIAL',
        'delivery_eligibility_review_required' => 'صلاحیت تحویل — REVIEW_REQUIRED',
        'delivery_clearance_not_recorded' => 'رکورد Clearance ثبت نشده',
        'delivery_clearance_not_cleared_yet' => 'Clearance هنوز cleared نشده',
        'evidence_partial' => 'مدارک — PARTIAL',
        'authorization_gate_partial' => 'گیت مجوز — PARTIAL',
        'final_readiness_blocked' => 'آمادگی نهایی — BLOCKED',
        'delivery_eligibility_not_eligible' => 'صلاحیت تحویل — NOT_ELIGIBLE',
        'authorization_gate_blocked' => 'گیت مجوز — BLOCKED',
        'evidence_gate_blocked' => 'گیت مدارک — ERROR/BLOCKED',
        'clearance_not_cleared_missing_requirements' => 'Clearance — not_cleared (الزامات ناقص)',
    ];
}
