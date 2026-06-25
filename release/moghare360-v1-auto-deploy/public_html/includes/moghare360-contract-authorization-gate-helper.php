<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Contract Authorization Gate Helper (Wave 3C)
 *
 * Read-only authorization requirement evaluation · no DB writes.
 * Internal controlled authorization — NOT final legal e-signature.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_CONTRACT_AUTH_GATE_AUTH_TABLE = 'erp_jobcard_authorizations';
const MOGHARE360_CONTRACT_AUTH_GATE_HISTORY_TABLE = 'erp_jobcard_authorization_history';
const MOGHARE360_CONTRACT_AUTH_GATE_MEDIA_TABLE = 'erp_jobcard_media';

const MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY = 'READY';
const MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR = 'ERROR';

const MOGHARE360_CONTRACT_AUTH_GATE_INTERNAL_NOTICE =
    'This is internal controlled authorization readiness evaluation, not final legal e-signature.';

/**
 * @return list<array{key: string, type: string, label: string, rule: string}>
 */
function moghare360_contract_authorization_gate_required_rules(): array
{
    return [
        [
            'key' => 'acceptance_contract_approved',
            'type' => 'acceptance_contract',
            'label' => 'قرارداد پذیرش — تأیید شده',
            'rule' => 'authorization_type=acceptance_contract AND authorization_status=approved',
        ],
        [
            'key' => 'repair_permission_approved',
            'type' => 'repair_permission',
            'label' => 'اجازه تعمیر — تأیید شده',
            'rule' => 'authorization_type=repair_permission AND authorization_status=approved',
        ],
        [
            'key' => 'diagnostic_authorization_or_evidence',
            'type' => 'diagnostic_authorization',
            'label' => 'مجوز تشخیص تأیید شده یا فایل تشخیصی (WAVE 2)',
            'rule' => 'diagnostic_authorization approved OR diagnostic media evidence exists',
        ],
        [
            'key' => 'delivery_approval_approved',
            'type' => 'delivery_approval',
            'label' => 'تأیید تحویل — تأیید شده',
            'rule' => 'authorization_type=delivery_approval AND authorization_status=approved',
        ],
    ];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_contract_authorization_gate_fetch_records(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return ['ok' => false, 'rows' => [], 'error' => 'شناسه کارت کار نامعتبر است.'];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'rows' => [], 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_GATE_AUTH_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_authorizations یافت نشد.'];
    }

    $where = 'WHERE jobcard_id = ?';
    $params = [$jobcardId];

    if (customer_core_column_exists($connection, MOGHARE360_CONTRACT_AUTH_GATE_AUTH_TABLE, 'is_deleted')) {
        $where .= ' AND is_deleted = 0';
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT authorization_id, jobcard_id, authorization_type, authorization_status, authorization_method,
                customer_name, customer_mobile, authorization_note,
                CONVERT(VARCHAR(30), created_at, 120) AS created_at,
                CONVERT(VARCHAR(30), updated_at, 120) AS updated_at
         FROM dbo.erp_jobcard_authorizations
         ' . $where . '
         ORDER BY created_at DESC, authorization_id DESC',
        $params
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

function moghare360_contract_authorization_gate_fetch_history_count(int $jobcardId): int
{
    if ($jobcardId < 1) {
        return 0;
    }

    $connection = customer_core_db();

    if ($connection === false || !customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_GATE_HISTORY_TABLE)) {
        return 0;
    }

    return (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcard_authorization_history WHERE jobcard_id = ?',
        [$jobcardId]
    ) ?? 0);
}

function moghare360_contract_authorization_gate_fetch_diagnostic_evidence_count(int $jobcardId): int
{
    if ($jobcardId < 1) {
        return 0;
    }

    $connection = customer_core_db();

    if ($connection === false || !customer_core_table_exists($connection, MOGHARE360_CONTRACT_AUTH_GATE_MEDIA_TABLE)) {
        return 0;
    }

    return (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_media
         WHERE jobcard_id = ? AND media_type = 'diagnostic'",
        [$jobcardId]
    ) ?? 0);
}

/**
 * @param list<array<string, string>> $authorizationRows
 * @return array{ok: bool, status: string, jobcard_id: int, required: list<string>, approved: list<string>, pending: list<string>, missing: list<string>, rejected: list<string>, cancelled: list<string>, authorization_count: int, history_count: int, diagnostic_evidence_count: int, message: string, errors: list<string>}
 */
function moghare360_contract_authorization_gate_evaluate(int $jobcardId, array $authorizationRows): array
{
    $ruleDefs = moghare360_contract_authorization_gate_required_rules();
    $requiredKeys = array_map(static fn(array $item): string => (string)$item['key'], $ruleDefs);
    $historyCount = moghare360_contract_authorization_gate_fetch_history_count($jobcardId);
    $diagnosticEvidenceCount = moghare360_contract_authorization_gate_fetch_diagnostic_evidence_count($jobcardId);
    $authorizationCount = count($authorizationRows);

    $approved = [];
    $pending = [];
    $missing = [];
    $rejected = [];
    $cancelled = [];

    $byType = [];
    foreach ($authorizationRows as $row) {
        $type = strtolower(trim((string)($row['authorization_type'] ?? '')));
        if ($type === '') {
            continue;
        }
        $byType[$type][] = strtolower(trim((string)($row['authorization_status'] ?? '')));
    }

    $typeState = static function (array $byTypeMap, string $authType): string {
        $statuses = $byTypeMap[$authType] ?? [];

        if ($statuses === []) {
            return 'missing';
        }

        if (in_array('approved', $statuses, true)) {
            return 'approved';
        }

        if (in_array('rejected', $statuses, true)) {
            return 'rejected';
        }

        if (in_array('cancelled', $statuses, true)) {
            return 'cancelled';
        }

        return 'pending';
    };

    foreach ($ruleDefs as $rule) {
        $key = (string)$rule['key'];
        $type = (string)$rule['type'];

        if ($key === 'diagnostic_authorization_or_evidence') {
            $diagState = $typeState($byType, 'diagnostic_authorization');

            if ($diagState === 'approved' || $diagnosticEvidenceCount > 0) {
                $approved[] = $key;
            } elseif ($diagState === 'rejected') {
                $rejected[] = $key;
            } elseif ($diagState === 'cancelled') {
                $cancelled[] = $key;
            } elseif ($diagState === 'pending') {
                $pending[] = $key;
            } else {
                $missing[] = $key;
            }

            continue;
        }

        $state = $typeState($byType, $type);

        match ($state) {
            'approved' => $approved[] = $key,
            'pending' => $pending[] = $key,
            'rejected' => $rejected[] = $key,
            'cancelled' => $cancelled[] = $key,
            default => $missing[] = $key,
        };
    }

    if ($authorizationCount === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY,
            'jobcard_id' => $jobcardId,
            'required' => $requiredKeys,
            'approved' => [],
            'pending' => [],
            'missing' => $requiredKeys,
            'rejected' => [],
            'cancelled' => [],
            'authorization_count' => 0,
            'history_count' => $historyCount,
            'diagnostic_evidence_count' => $diagnosticEvidenceCount,
            'message' => 'هیچ رکورد مجوز/قراردادی برای این کارت کار ثبت نشده است.',
            'errors' => [],
        ];
    }

    if ($rejected !== [] || $cancelled !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED,
            'jobcard_id' => $jobcardId,
            'required' => $requiredKeys,
            'approved' => $approved,
            'pending' => $pending,
            'missing' => $missing,
            'rejected' => $rejected,
            'cancelled' => $cancelled,
            'authorization_count' => $authorizationCount,
            'history_count' => $historyCount,
            'diagnostic_evidence_count' => $diagnosticEvidenceCount,
            'message' => 'گیت مجوز مسدود — مجوز رد/لغو شده یا تأیید بحرانی موجود نیست.',
            'errors' => [],
        ];
    }

    $criticalMissing = in_array('acceptance_contract_approved', $missing, true)
        || in_array('repair_permission_approved', $missing, true);

    if ($criticalMissing) {
        return [
            'ok' => true,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED,
            'jobcard_id' => $jobcardId,
            'required' => $requiredKeys,
            'approved' => $approved,
            'pending' => $pending,
            'missing' => $missing,
            'rejected' => $rejected,
            'cancelled' => $cancelled,
            'authorization_count' => $authorizationCount,
            'history_count' => $historyCount,
            'diagnostic_evidence_count' => $diagnosticEvidenceCount,
            'message' => 'گیت مجوز مسدود — تأیید بحرانی (پذیرش/تعمیر) موجود نیست.',
            'errors' => [],
        ];
    }

    if ($missing === [] && $pending === []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY,
            'jobcard_id' => $jobcardId,
            'required' => $requiredKeys,
            'approved' => $approved,
            'pending' => $pending,
            'missing' => $missing,
            'rejected' => $rejected,
            'cancelled' => $cancelled,
            'authorization_count' => $authorizationCount,
            'history_count' => $historyCount,
            'diagnostic_evidence_count' => $diagnosticEvidenceCount,
            'message' => 'همه مجوزهای الزامی تأیید شده‌اند — آماده از نظر مجوز داخلی.',
            'errors' => [],
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL,
        'jobcard_id' => $jobcardId,
        'required' => $requiredKeys,
        'approved' => $approved,
        'pending' => $pending,
        'missing' => $missing,
        'rejected' => $rejected,
        'cancelled' => $cancelled,
        'authorization_count' => $authorizationCount,
        'history_count' => $historyCount,
        'diagnostic_evidence_count' => $diagnosticEvidenceCount,
        'message' => 'برخی مجوزهای الزامی هنوز تأیید نشده‌اند.',
        'errors' => [],
    ];
}

/**
 * @return array{ok: bool, status: string, jobcard_id: int, required: list<string>, approved: list<string>, pending: list<string>, missing: list<string>, rejected: list<string>, cancelled: list<string>, authorization_count: int, history_count: int, diagnostic_evidence_count: int, message: string, errors: list<string>, authorization_rows: list<array<string, string>>}
 */
function moghare360_contract_authorization_gate_review(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'required' => array_map(
                static fn(array $item): string => (string)$item['key'],
                moghare360_contract_authorization_gate_required_rules()
            ),
            'approved' => [],
            'pending' => [],
            'missing' => [],
            'rejected' => [],
            'cancelled' => [],
            'authorization_count' => 0,
            'history_count' => 0,
            'diagnostic_evidence_count' => 0,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
            'authorization_rows' => [],
        ];
    }

    $fetch = moghare360_contract_authorization_gate_fetch_records($jobcardId);

    if (!$fetch['ok']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'required' => array_map(
                static fn(array $item): string => (string)$item['key'],
                moghare360_contract_authorization_gate_required_rules()
            ),
            'approved' => [],
            'pending' => [],
            'missing' => [],
            'rejected' => [],
            'cancelled' => [],
            'authorization_count' => 0,
            'history_count' => 0,
            'diagnostic_evidence_count' => 0,
            'message' => 'خواندن مجوزهای کارت کار ناموفق بود.',
            'errors' => [$fetch['error']],
            'authorization_rows' => [],
        ];
    }

    $evaluation = moghare360_contract_authorization_gate_evaluate($jobcardId, $fetch['rows']);

    return array_merge($evaluation, ['authorization_rows' => $fetch['rows']]);
}

function moghare360_contract_authorization_gate_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY => 'آماده',
        MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY => 'خالی',
        MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return array<string, string>
 */
function moghare360_contract_authorization_gate_rule_labels(): array
{
    $labels = [];

    foreach (moghare360_contract_authorization_gate_required_rules() as $rule) {
        $labels[(string)$rule['key']] = (string)$rule['label'];
    }

    return $labels;
}
