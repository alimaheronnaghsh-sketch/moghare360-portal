<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Final Readiness Helper (Wave 4A)
 *
 * Read-only final readiness by combining WAVE 2 evidence + WAVE 3 authorization gates.
 * Does not perform final delivery · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';

const MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY = 'READY';
const MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR = 'ERROR';

const MOGHARE360_JOBCARD_FINAL_READINESS_INTERNAL_NOTICE =
    'This is read-only final readiness evaluation — not legal e-signature. No delivery action on this page.';

const MOGHARE360_JOBCARD_FINAL_READINESS_JOBCARD_TABLE = 'erp_jobcards';

/**
 * @return array{ok: bool, jobcard: array<string, string>|null, message: string, errors: list<string>}
 */
function moghare360_jobcard_final_readiness_fetch_jobcard(int $jobcardId): array
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

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_FINAL_READINESS_JOBCARD_TABLE)) {
        return [
            'ok' => true,
            'jobcard' => ['jobcard_id' => (string)$jobcardId],
            'message' => 'مرجع erp_jobcards برای شناسه تأیید نشد — فقط شناسه استفاده می‌شود.',
            'errors' => [],
        ];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP 1 jobcard_id, customer_id, vehicle_id, jobcard_status, jobcard_code
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
function moghare360_jobcard_final_readiness_fetch_evidence_gate(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['invalid_jobcard_id'],
        ];
    }

    return moghare360_jobcard_evidence_review($jobcardId);
}

/**
 * @return array<string, mixed>
 */
function moghare360_jobcard_final_readiness_fetch_authorization_gate(int $jobcardId): array
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

function moghare360_jobcard_final_readiness_evidence_is_ready(string $status): bool
{
    return strtoupper(trim($status)) === MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE;
}

function moghare360_jobcard_final_readiness_authorization_is_ready(string $status): bool
{
    return strtoupper(trim($status)) === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY;
}

/**
 * @return array{ok: bool, status: string, jobcard_id: int, jobcard: array<string, string>, evidence_gate: array<string, mixed>, authorization_gate: array<string, mixed>, ready_items: list<string>, partial_items: list<string>, blocked_items: list<string>, missing_items: list<string>, message: string, errors: list<string>}
 */
function moghare360_jobcard_final_readiness_evaluate(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'jobcard' => [],
            'evidence_gate' => [],
            'authorization_gate' => [],
            'ready_items' => [],
            'partial_items' => [],
            'blocked_items' => [],
            'missing_items' => [],
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
        ];
    }

    $jobcardResult = moghare360_jobcard_final_readiness_fetch_jobcard($jobcardId);
    $evidenceGate = moghare360_jobcard_final_readiness_fetch_evidence_gate($jobcardId);
    $authorizationGate = moghare360_jobcard_final_readiness_fetch_authorization_gate($jobcardId);

    $evidenceStatus = strtoupper(trim((string)($evidenceGate['status'] ?? '')));
    $authStatus = strtoupper(trim((string)($authorizationGate['status'] ?? '')));

    $readyItems = [];
    $partialItems = [];
    $blockedItems = [];
    $missingItems = [];

    if (moghare360_jobcard_final_readiness_evidence_is_ready($evidenceStatus)) {
        $readyItems[] = 'wave2_evidence_gate';
    } elseif ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL) {
        $partialItems[] = 'wave2_evidence_gate';
        foreach (($evidenceGate['missing'] ?? []) as $key) {
            $missingItems[] = 'evidence:' . (string)$key;
        }
    } elseif ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY) {
        $missingItems[] = 'wave2_evidence_gate';
        foreach (($evidenceGate['missing'] ?? []) as $key) {
            $missingItems[] = 'evidence:' . (string)$key;
        }
    } elseif ($evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR) {
        $blockedItems[] = 'wave2_evidence_gate';
    }

    if (moghare360_jobcard_final_readiness_authorization_is_ready($authStatus)) {
        $readyItems[] = 'wave3_authorization_gate';
    } elseif ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL) {
        $partialItems[] = 'wave3_authorization_gate';
        foreach (($authorizationGate['missing'] ?? []) as $key) {
            $missingItems[] = 'authorization:' . (string)$key;
        }
        foreach (($authorizationGate['pending'] ?? []) as $key) {
            $partialItems[] = 'authorization_pending:' . (string)$key;
        }
    } elseif ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED) {
        $blockedItems[] = 'wave3_authorization_gate';
        foreach (($authorizationGate['rejected'] ?? []) as $key) {
            $blockedItems[] = 'authorization_rejected:' . (string)$key;
        }
        foreach (($authorizationGate['cancelled'] ?? []) as $key) {
            $blockedItems[] = 'authorization_cancelled:' . (string)$key;
        }
        foreach (($authorizationGate['missing'] ?? []) as $key) {
            $missingItems[] = 'authorization:' . (string)$key;
        }
    } elseif ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY) {
        $missingItems[] = 'wave3_authorization_gate';
    } elseif ($authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR) {
        $blockedItems[] = 'wave3_authorization_gate';
    }

    $errors = [];
    if (!($jobcardResult['ok'] ?? false)) {
        $errors = array_merge($errors, $jobcardResult['errors'] ?? []);
    }
    if (!($evidenceGate['ok'] ?? false)) {
        $errors = array_merge($errors, $evidenceGate['errors'] ?? []);
    }
    if (!($authorizationGate['ok'] ?? false)) {
        $errors = array_merge($errors, $authorizationGate['errors'] ?? []);
    }

    if ($errors !== [] && $evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR
        && $authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'jobcard' => (array)($jobcardResult['jobcard'] ?? []),
            'evidence_gate' => $evidenceGate,
            'authorization_gate' => $authorizationGate,
            'ready_items' => [],
            'partial_items' => [],
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'خواندن گیت‌های آمادگی ناموفق بود.',
            'errors' => $errors,
        ];
    }

    $mediaCount = (int)($evidenceGate['media_count'] ?? 0);
    $authCount = (int)($authorizationGate['authorization_count'] ?? 0);

    if ($mediaCount === 0 && $authCount === 0
        && $evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY
        && $authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY) {
        return [
            'ok' => true,
            'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY,
            'jobcard_id' => $jobcardId,
            'jobcard' => (array)($jobcardResult['jobcard'] ?? ['jobcard_id' => (string)$jobcardId]),
            'evidence_gate' => $evidenceGate,
            'authorization_gate' => $authorizationGate,
            'ready_items' => [],
            'partial_items' => [],
            'blocked_items' => [],
            'missing_items' => array_values(array_unique($missingItems)),
            'message' => 'داده معنادار مدارک و مجوز برای این کارت کار وجود ندارد.',
            'errors' => [],
        ];
    }

    if ($blockedItems !== [] || $evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR
        || $authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED) {
        return [
            'ok' => true,
            'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED,
            'jobcard_id' => $jobcardId,
            'jobcard' => (array)($jobcardResult['jobcard'] ?? ['jobcard_id' => (string)$jobcardId]),
            'evidence_gate' => $evidenceGate,
            'authorization_gate' => $authorizationGate,
            'ready_items' => $readyItems,
            'partial_items' => $partialItems,
            'blocked_items' => array_values(array_unique($blockedItems)),
            'missing_items' => array_values(array_unique($missingItems)),
            'message' => 'آمادگی نهایی مسدود — گیت مدارک یا مجوز وضعیت بحرانی دارد.',
            'errors' => [],
        ];
    }

    if (moghare360_jobcard_final_readiness_evidence_is_ready($evidenceStatus)
        && moghare360_jobcard_final_readiness_authorization_is_ready($authStatus)) {
        return [
            'ok' => true,
            'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY,
            'jobcard_id' => $jobcardId,
            'jobcard' => (array)($jobcardResult['jobcard'] ?? ['jobcard_id' => (string)$jobcardId]),
            'evidence_gate' => $evidenceGate,
            'authorization_gate' => $authorizationGate,
            'ready_items' => $readyItems,
            'partial_items' => [],
            'blocked_items' => [],
            'missing_items' => [],
            'message' => 'آمادگی نهایی داخلی — گیت مدارک (COMPLETE) و گیت مجوز (READY) تأیید شد.',
            'errors' => [],
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL,
        'jobcard_id' => $jobcardId,
        'jobcard' => (array)($jobcardResult['jobcard'] ?? ['jobcard_id' => (string)$jobcardId]),
        'evidence_gate' => $evidenceGate,
        'authorization_gate' => $authorizationGate,
        'ready_items' => $readyItems,
        'partial_items' => array_values(array_unique($partialItems)),
        'blocked_items' => [],
        'missing_items' => array_values(array_unique($missingItems)),
        'message' => 'آمادگی نهایی ناقص — برخی الزامات مدارک یا مجوز هنوز تکمیل نشده‌اند.',
        'errors' => [],
    ];
}

function moghare360_jobcard_final_readiness_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY => 'آماده',
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY => 'خالی',
        MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return array<string, string>
 */
function moghare360_jobcard_final_readiness_item_labels(): array
{
    return [
        'wave2_evidence_gate' => 'گیت مدارک WAVE 2',
        'wave3_authorization_gate' => 'گیت مجوز WAVE 3',
    ];
}
