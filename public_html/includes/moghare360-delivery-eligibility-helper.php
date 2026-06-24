<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Delivery Eligibility Helper (Wave 4B)
 *
 * Read-only delivery eligibility based on WAVE 4A final readiness.
 * Does not perform final delivery · does not create delivery records · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';

const MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE = 'ELIGIBLE';
const MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE = 'NOT_ELIGIBLE';
const MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR = 'ERROR';

const MOGHARE360_DELIVERY_ELIGIBILITY_INTERNAL_NOTICE =
    'This is read-only delivery eligibility review — not legal e-signature. No delivery action on this page.';

/**
 * @return array<string, mixed>
 */
function moghare360_delivery_eligibility_fetch_final_readiness(int $jobcardId): array
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
 * @return list<array{key: string, label: string, rule: string}>
 */
function moghare360_delivery_eligibility_rules(): array
{
    return [
        [
            'key' => 'final_readiness_ready',
            'label' => 'آمادگی نهایی — READY',
            'rule' => 'final_readiness.status = READY',
        ],
        [
            'key' => 'evidence_gate_complete',
            'label' => 'گیت مدارک — COMPLETE',
            'rule' => 'evidence_gate.status = COMPLETE',
        ],
        [
            'key' => 'authorization_gate_ready',
            'label' => 'گیت مجوز — READY',
            'rule' => 'authorization_gate.status = READY',
        ],
        [
            'key' => 'no_blocking_items',
            'label' => 'بدون آیتم مسدودکننده بحرانی',
            'rule' => 'blocked_items is empty AND no rejected/cancelled authorization rules',
        ],
        [
            'key' => 'no_delivery_record',
            'label' => 'بدون ایجاد رکورد تحویل',
            'rule' => 'read-only eligibility layer — no delivery INSERT',
        ],
    ];
}

function moghare360_delivery_eligibility_evidence_is_ready(string $status): bool
{
    return strtoupper(trim($status)) === MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE;
}

function moghare360_delivery_eligibility_authorization_is_ready(string $status): bool
{
    return strtoupper(trim($status)) === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY;
}

/**
 * @return array{ok: bool, status: string, jobcard_id: int, final_readiness: array<string, mixed>, rules: list<array{key: string, label: string, rule: string}>, eligible_items: list<string>, review_items: list<string>, blocking_items: list<string>, recommended_action: string, message: string, errors: list<string>}
 */
function moghare360_delivery_eligibility_evaluate(int $jobcardId): array
{
    $rules = moghare360_delivery_eligibility_rules();

    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'final_readiness' => [],
            'rules' => $rules,
            'eligible_items' => [],
            'review_items' => [],
            'blocking_items' => [],
            'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR),
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
        ];
    }

    $finalReadiness = moghare360_delivery_eligibility_fetch_final_readiness($jobcardId);
    $finalStatus = strtoupper(trim((string)($finalReadiness['status'] ?? '')));

    $evidenceGate = (array)($finalReadiness['evidence_gate'] ?? []);
    $authGate = (array)($finalReadiness['authorization_gate'] ?? []);
    $evidenceStatus = strtoupper(trim((string)($evidenceGate['status'] ?? '')));
    $authStatus = strtoupper(trim((string)($authGate['status'] ?? '')));

    $eligibleItems = [];
    $reviewItems = [];
    $blockingItems = array_values(array_unique(array_merge(
        (array)($finalReadiness['blocked_items'] ?? []),
        (array)($finalReadiness['missing_items'] ?? [])
    )));

    foreach ((array)($finalReadiness['partial_items'] ?? []) as $item) {
        $reviewItems[] = (string)$item;
    }

    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR || !($finalReadiness['ok'] ?? false)) {
        return [
            'ok' => false,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'final_readiness' => $finalReadiness,
            'rules' => $rules,
            'eligible_items' => [],
            'review_items' => [],
            'blocking_items' => $blockingItems,
            'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR),
            'message' => 'ارزیابی صلاحیت تحویل ناموفق بود.',
            'errors' => (array)($finalReadiness['errors'] ?? ['read_failure']),
        ];
    }

    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY) {
        return [
            'ok' => true,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY,
            'jobcard_id' => $jobcardId,
            'final_readiness' => $finalReadiness,
            'rules' => $rules,
            'eligible_items' => [],
            'review_items' => [],
            'blocking_items' => $blockingItems,
            'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY),
            'message' => 'داده کافی برای ارزیابی صلاحیت تحویل وجود ندارد.',
            'errors' => [],
        ];
    }

    $hasHardBlock = ($finalReadiness['blocked_items'] ?? []) !== []
        || $evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR
        || $authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED
        || $finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED;

    foreach ((array)($authGate['rejected'] ?? []) as $item) {
        $blockingItems[] = 'authorization_rejected:' . (string)$item;
        $hasHardBlock = true;
    }
    foreach ((array)($authGate['cancelled'] ?? []) as $item) {
        $blockingItems[] = 'authorization_cancelled:' . (string)$item;
        $hasHardBlock = true;
    }

    $blockingItems = array_values(array_unique($blockingItems));

    if ($hasHardBlock) {
        return [
            'ok' => true,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE,
            'jobcard_id' => $jobcardId,
            'final_readiness' => $finalReadiness,
            'rules' => $rules,
            'eligible_items' => [],
            'review_items' => $reviewItems,
            'blocking_items' => $blockingItems,
            'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE),
            'message' => 'صلاحیت تحویل وجود ندارد — وضعیت مسدود یا آیتم بحرانی رد/لغو شده است.',
            'errors' => [],
        ];
    }

    $evidenceReady = moghare360_delivery_eligibility_evidence_is_ready($evidenceStatus);
    $authReady = moghare360_delivery_eligibility_authorization_is_ready($authStatus);
    $finalReady = $finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY;

    if ($evidenceReady) {
        $eligibleItems[] = 'evidence_gate_complete';
    }
    if ($authReady) {
        $eligibleItems[] = 'authorization_gate_ready';
    }
    if ($finalReady) {
        $eligibleItems[] = 'final_readiness_ready';
    }
    if ($blockingItems === []) {
        $eligibleItems[] = 'no_blocking_items';
    }
    $eligibleItems[] = 'no_delivery_record';

    if ($finalReady && $evidenceReady && $authReady && $blockingItems === []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE,
            'jobcard_id' => $jobcardId,
            'final_readiness' => $finalReadiness,
            'rules' => $rules,
            'eligible_items' => array_values(array_unique($eligibleItems)),
            'review_items' => [],
            'blocking_items' => [],
            'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE),
            'message' => 'صلاحیت تحویل داخلی تأیید شد — آمادگی نهایی و گیت‌های زیرمجموعه آماده هستند.',
            'errors' => [],
        ];
    }

    if ($finalStatus === MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL
        || $evidenceStatus === MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL
        || $authStatus === MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL) {
        if ($evidenceReady) {
            $reviewItems[] = 'evidence_gate_already_complete';
        }
        if ($authReady) {
            $reviewItems[] = 'authorization_gate_already_ready';
        }

        return [
            'ok' => true,
            'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED,
            'jobcard_id' => $jobcardId,
            'final_readiness' => $finalReadiness,
            'rules' => $rules,
            'eligible_items' => array_values(array_unique($eligibleItems)),
            'review_items' => array_values(array_unique($reviewItems)),
            'blocking_items' => [],
            'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED),
            'message' => 'صلاحیت تحویل نیازمند بازبینی — برخی الزامات هنوز تکمیل نشده‌اند.',
            'errors' => [],
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE,
        'jobcard_id' => $jobcardId,
        'final_readiness' => $finalReadiness,
        'rules' => $rules,
        'eligible_items' => $eligibleItems,
        'review_items' => $reviewItems,
        'blocking_items' => $blockingItems,
        'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE),
        'message' => 'صلاحیت تحویل وجود ندارد.',
        'errors' => [],
    ];
}

function moghare360_delivery_eligibility_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE => 'صلاحیت دارد',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE => 'فاقد صلاحیت',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY => 'خالی',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

function moghare360_delivery_eligibility_recommended_action(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE =>
            'از نظر گیت داخلی صلاحیت دارد — تحویل نهایی هنوز در این لایه انجام نمی‌شود؛ بازبینی اپراتوری توصیه می‌شود.',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED =>
            'بازبینی مدارک و مجوزها را تکمیل کنید — تحویل نهایی انجام نشود.',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE =>
            'تحویل مجاز نیست — ابتدا آیتم‌های مسدودکننده را رفع کنید.',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY =>
            'ابتدا مدارک و مجوزهای کارت کار را ثبت کنید.',
        MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR =>
            'خطای خواندن — شناسه کارت کار یا اتصال DB را بررسی کنید.',
        default => 'اقدام مشخص نیست.',
    };
}

/**
 * @return array<string, string>
 */
function moghare360_delivery_eligibility_rule_labels(): array
{
    $labels = [];
    foreach (moghare360_delivery_eligibility_rules() as $rule) {
        $labels[(string)$rule['key']] = (string)$rule['label'];
    }

    return $labels;
}
