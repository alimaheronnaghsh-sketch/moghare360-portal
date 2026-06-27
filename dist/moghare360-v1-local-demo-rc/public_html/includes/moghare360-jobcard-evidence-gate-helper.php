<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Evidence Gate Helper (Wave 2D)
 *
 * Read-only evidence completeness evaluation · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_JOBCARD_EVIDENCE_MEDIA_TABLE = 'erp_jobcard_media';
const MOGHARE360_JOBCARD_EVIDENCE_HISTORY_TABLE = 'erp_jobcard_media_history';

const MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE = 'COMPLETE';
const MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR = 'ERROR';

/**
 * @return list<array{key: string, label: string, rule: string}>
 */
function moghare360_jobcard_evidence_allowed_required_items(): array
{
    return [
        [
            'key' => 'input_front_or_other',
            'label' => 'ورودی — نمای جلو یا سایر',
            'rule' => 'media_stage=input AND media_type IN (front, other)',
        ],
        [
            'key' => 'input_odometer_or_dashboard',
            'label' => 'ورودی — کیلومتر یا داشبورد',
            'rule' => 'media_stage=input AND media_type IN (odometer, dashboard)',
        ],
        [
            'key' => 'output_front_or_other',
            'label' => 'خروجی — نمای جلو یا سایر',
            'rule' => 'media_stage=output AND media_type IN (front, other)',
        ],
        [
            'key' => 'diagnostic_any',
            'label' => 'حداقل یک فایل تشخیصی',
            'rule' => 'media_type=diagnostic AND media_stage IN (diagnostic_initial, diagnostic_secondary, diagnostic_final)',
        ],
    ];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_jobcard_evidence_fetch_media(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return ['ok' => false, 'rows' => [], 'error' => 'شناسه کارت کار نامعتبر است.'];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'rows' => [], 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_EVIDENCE_MEDIA_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_media یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT media_id, jobcard_id, media_stage, media_type, relative_path, mime_type, file_size, created_at, notes
         FROM dbo.erp_jobcard_media
         WHERE jobcard_id = ?
         ORDER BY media_id DESC',
        [$jobcardId]
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

function moghare360_jobcard_evidence_fetch_history_count(int $jobcardId): int
{
    if ($jobcardId < 1) {
        return 0;
    }

    $connection = customer_core_db();

    if ($connection === false || !customer_core_table_exists($connection, MOGHARE360_JOBCARD_EVIDENCE_HISTORY_TABLE)) {
        return 0;
    }

    return (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcard_media_history WHERE jobcard_id = ?',
        [$jobcardId]
    ) ?? 0);
}

/**
 * @param list<array<string, string>> $mediaRows
 * @return array{ok: bool, status: string, jobcard_id: int, required: list<string>, found: list<string>, missing: list<string>, media_count: int, diagnostic_count: int, history_count: int, message: string, errors: list<string>}
 */
function moghare360_jobcard_evidence_evaluate(int $jobcardId, array $mediaRows, int $historyCount = 0): array
{
    $requiredDefs = moghare360_jobcard_evidence_allowed_required_items();
    $requiredKeys = array_map(static fn(array $item): string => (string)$item['key'], $requiredDefs);
    $found = [];
    $missing = [];

    $normalized = [];
    foreach ($mediaRows as $row) {
        $normalized[] = [
            'media_stage' => strtolower(trim((string)($row['media_stage'] ?? ''))),
            'media_type' => strtolower(trim((string)($row['media_type'] ?? ''))),
        ];
    }

    $diagnosticCount = 0;
    foreach ($normalized as $row) {
        if ($row['media_type'] === 'diagnostic') {
            $diagnosticCount++;
        }
    }

    $hasInputFrontOrOther = false;
    $hasInputOdometerOrDashboard = false;
    $hasOutputFrontOrOther = false;
    $hasDiagnostic = false;

    foreach ($normalized as $row) {
        $stage = $row['media_stage'];
        $type = $row['media_type'];

        if ($stage === 'input' && in_array($type, ['front', 'other'], true)) {
            $hasInputFrontOrOther = true;
        }

        if ($stage === 'input' && in_array($type, ['odometer', 'dashboard'], true)) {
            $hasInputOdometerOrDashboard = true;
        }

        if ($stage === 'output' && in_array($type, ['front', 'other'], true)) {
            $hasOutputFrontOrOther = true;
        }

        if (
            $type === 'diagnostic'
            && in_array($stage, ['diagnostic_initial', 'diagnostic_secondary', 'diagnostic_final'], true)
        ) {
            $hasDiagnostic = true;
        }
    }

    $ruleMap = [
        'input_front_or_other' => $hasInputFrontOrOther,
        'input_odometer_or_dashboard' => $hasInputOdometerOrDashboard,
        'output_front_or_other' => $hasOutputFrontOrOther,
        'diagnostic_any' => $hasDiagnostic,
    ];

    foreach ($requiredKeys as $key) {
        if (($ruleMap[$key] ?? false) === true) {
            $found[] = $key;
        } else {
            $missing[] = $key;
        }
    }

    $mediaCount = count($mediaRows);

    if ($mediaCount === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY,
            'jobcard_id' => $jobcardId,
            'required' => $requiredKeys,
            'found' => [],
            'missing' => $requiredKeys,
            'media_count' => 0,
            'diagnostic_count' => 0,
            'history_count' => $historyCount,
            'message' => 'هیچ رسانه‌ای برای این کارت کار ثبت نشده است.',
            'errors' => [],
        ];
    }

    if ($missing === []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE,
            'jobcard_id' => $jobcardId,
            'required' => $requiredKeys,
            'found' => $found,
            'missing' => [],
            'media_count' => $mediaCount,
            'diagnostic_count' => $diagnosticCount,
            'history_count' => $historyCount,
            'message' => 'حداقل مدارک موردنیاز تکمیل است.',
            'errors' => [],
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL,
        'jobcard_id' => $jobcardId,
        'required' => $requiredKeys,
        'found' => $found,
        'missing' => $missing,
        'media_count' => $mediaCount,
        'diagnostic_count' => $diagnosticCount,
        'history_count' => $historyCount,
        'message' => 'برخی مدارک الزامی هنوز تکمیل نشده‌اند.',
        'errors' => [],
    ];
}

/**
 * @return array{ok: bool, status: string, jobcard_id: int, required: list<string>, found: list<string>, missing: list<string>, media_count: int, diagnostic_count: int, history_count: int, message: string, errors: list<string>, media_rows: list<array<string, string>>}
 */
function moghare360_jobcard_evidence_review(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'required' => [],
            'found' => [],
            'missing' => [],
            'media_count' => 0,
            'diagnostic_count' => 0,
            'history_count' => 0,
            'message' => 'شناسه کارت کار نامعتبر است.',
            'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
            'media_rows' => [],
        ];
    }

    $mediaFetch = moghare360_jobcard_evidence_fetch_media($jobcardId);

    if (!$mediaFetch['ok']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR,
            'jobcard_id' => $jobcardId,
            'required' => array_map(
                static fn(array $item): string => (string)$item['key'],
                moghare360_jobcard_evidence_allowed_required_items()
            ),
            'found' => [],
            'missing' => [],
            'media_count' => 0,
            'diagnostic_count' => 0,
            'history_count' => 0,
            'message' => 'خواندن متادیتای رسانه ناموفق بود.',
            'errors' => [$mediaFetch['error']],
            'media_rows' => [],
        ];
    }

    $historyCount = moghare360_jobcard_evidence_fetch_history_count($jobcardId);
    $evaluation = moghare360_jobcard_evidence_evaluate($jobcardId, $mediaFetch['rows'], $historyCount);

    return array_merge($evaluation, ['media_rows' => $mediaFetch['rows']]);
}

function moghare360_jobcard_evidence_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE => 'تکمیل',
        MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY => 'خالی',
        MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return array<string, string>
 */
function moghare360_jobcard_evidence_required_labels(): array
{
    $labels = [];

    foreach (moghare360_jobcard_evidence_allowed_required_items() as $item) {
        $labels[(string)$item['key']] = (string)$item['label'];
    }

    return $labels;
}
