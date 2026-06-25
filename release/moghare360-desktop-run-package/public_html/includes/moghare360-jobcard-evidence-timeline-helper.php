<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Evidence Timeline Helper (Wave 2E)
 *
 * Read-only media + history timeline · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_JOBCARD_TIMELINE_MEDIA_TABLE = 'erp_jobcard_media';
const MOGHARE360_JOBCARD_TIMELINE_HISTORY_TABLE = 'erp_jobcard_media_history';

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_jobcard_evidence_timeline_fetch_media(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return ['ok' => false, 'rows' => [], 'error' => 'شناسه کارت کار نامعتبر است.'];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'rows' => [], 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_TIMELINE_MEDIA_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_media یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT media_id, jobcard_id, media_stage, media_type, relative_path, mime_type,
                source, capture_method, metadata_status, created_at, notes
         FROM dbo.erp_jobcard_media
         WHERE jobcard_id = ?
         ORDER BY created_at DESC, media_id DESC',
        [$jobcardId]
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_jobcard_evidence_timeline_fetch_history(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return ['ok' => false, 'rows' => [], 'error' => 'شناسه کارت کار نامعتبر است.'];
    }

    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'rows' => [], 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_TIMELINE_HISTORY_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_media_history یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT history_id, media_id, jobcard_id, event_code, event_title, event_notes,
                old_status, new_status, event_at, event_by
         FROM dbo.erp_jobcard_media_history
         WHERE jobcard_id = ?
         ORDER BY event_at DESC, history_id DESC',
        [$jobcardId]
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

function moghare360_jobcard_evidence_timeline_stage_label(string $stage): string
{
    return match (strtolower(trim($stage))) {
        'input' => 'ورودی',
        'output' => 'خروجی',
        'diagnostic_initial' => 'تشخیص اولیه',
        'diagnostic_secondary' => 'تشخیص میانی',
        'diagnostic_final' => 'تشخیص نهایی',
        default => $stage !== '' ? $stage : '—',
    };
}

function moghare360_jobcard_evidence_timeline_event_label(string $eventCode): string
{
    return match (strtoupper(trim($eventCode))) {
        'DIAGNOSTIC_FILE_REGISTERED' => 'ثبت فایل تشخیصی',
        'MEDIA_REGISTERED' => 'ثبت رسانه',
        'MEDIA_CAPTURED' => 'ثبت تصویر',
        default => $eventCode !== '' ? $eventCode : '—',
    };
}

/**
 * @param list<array<string, string>> $mediaRows
 * @param list<array<string, string>> $historyRows
 * @return list<array<string, mixed>>
 */
function moghare360_jobcard_evidence_timeline_build(array $mediaRows, array $historyRows): array
{
    $events = [];
    $mediaIds = [];

    foreach ($mediaRows as $row) {
        $mediaId = trim((string)($row['media_id'] ?? ''));
        if ($mediaId !== '') {
            $mediaIds[$mediaId] = true;
        }

        $relativePath = moghare360_jobcard_evidence_timeline_safe_relative_path(
            (string)($row['relative_path'] ?? '')
        );

        $events[] = [
            'kind' => 'media',
            'sort_at' => (string)($row['created_at'] ?? ''),
            'media_id' => $mediaId,
            'jobcard_id' => (string)($row['jobcard_id'] ?? ''),
            'media_stage' => (string)($row['media_stage'] ?? ''),
            'media_type' => (string)($row['media_type'] ?? ''),
            'mime_type' => (string)($row['mime_type'] ?? ''),
            'relative_path' => $relativePath,
            'source' => (string)($row['source'] ?? ''),
            'capture_method' => (string)($row['capture_method'] ?? ''),
            'metadata_status' => (string)($row['metadata_status'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'event_code' => 'MEDIA_REGISTERED',
            'event_title' => 'Media metadata registered',
            'event_at' => (string)($row['created_at'] ?? ''),
            'stage_label' => moghare360_jobcard_evidence_timeline_stage_label((string)($row['media_stage'] ?? '')),
            'event_label' => moghare360_jobcard_evidence_timeline_event_label('MEDIA_REGISTERED'),
        ];
    }

    foreach ($historyRows as $row) {
        $eventCode = trim((string)($row['event_code'] ?? ''));
        $mediaId = trim((string)($row['media_id'] ?? ''));

        $events[] = [
            'kind' => 'history',
            'sort_at' => (string)($row['event_at'] ?? ''),
            'history_id' => (string)($row['history_id'] ?? ''),
            'media_id' => $mediaId,
            'jobcard_id' => (string)($row['jobcard_id'] ?? ''),
            'media_stage' => '',
            'media_type' => '',
            'mime_type' => '',
            'relative_path' => '',
            'source' => '',
            'capture_method' => '',
            'metadata_status' => '',
            'created_at' => '',
            'event_code' => $eventCode,
            'event_title' => (string)($row['event_title'] ?? ''),
            'event_notes' => (string)($row['event_notes'] ?? ''),
            'event_at' => (string)($row['event_at'] ?? ''),
            'event_by' => (string)($row['event_by'] ?? ''),
            'stage_label' => '',
            'event_label' => moghare360_jobcard_evidence_timeline_event_label($eventCode),
        ];
    }

    usort($events, static function (array $a, array $b): int {
        $aTime = strtotime((string)($a['sort_at'] ?? '')) ?: 0;
        $bTime = strtotime((string)($b['sort_at'] ?? '')) ?: 0;

        if ($aTime === $bTime) {
            return strcmp((string)($b['media_id'] ?? $b['history_id'] ?? ''), (string)($a['media_id'] ?? $a['history_id'] ?? ''));
        }

        return $bTime <=> $aTime;
    });

    return $events;
}

function moghare360_jobcard_evidence_timeline_safe_relative_path(string $relativePath): string
{
    $relativePath = trim(str_replace('\\', '/', $relativePath));

    if ($relativePath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $relativePath) === 1) {
        return '';
    }

    if (str_contains($relativePath, '..')) {
        return '';
    }

    if (!str_starts_with($relativePath, 'storage/')) {
        return '';
    }

    return $relativePath;
}

/**
 * @param list<array<string, string>> $mediaRows
 * @param list<array<string, string>> $historyRows
 * @return list<string>
 */
function moghare360_jobcard_evidence_timeline_build_warnings(array $mediaRows, array $historyRows): array
{
    $warnings = [];
    $mediaIds = [];

    foreach ($mediaRows as $row) {
        $mediaId = trim((string)($row['media_id'] ?? ''));
        if ($mediaId !== '') {
            $mediaIds[$mediaId] = true;
        }
    }

    foreach ($historyRows as $row) {
        $mediaId = trim((string)($row['media_id'] ?? ''));
        $historyId = trim((string)($row['history_id'] ?? ''));

        if ($mediaId !== '' && !isset($mediaIds[$mediaId])) {
            $warnings[] = 'رکورد تاریخچه #' . $historyId . ' به media_id=' . $mediaId . ' اشاره دارد اما رسانه متناظر یافت نشد.';
        }

        if ($mediaId === '') {
            $warnings[] = 'رکورد تاریخچه #' . $historyId . ' (' . trim((string)($row['event_code'] ?? '')) . ') بدون media_id ثبت شده است.';
        }
    }

    if ($historyRows !== [] && $mediaRows === []) {
        $warnings[] = 'تاریخچه رسانه وجود دارد اما رکورد متادیتای رسانه برای این کارت کار یافت نشد.';
    }

    return array_values(array_unique($warnings));
}

/**
 * @return array{ok: bool, jobcard_id: int, media_count: int, history_count: int, events: list<array<string, mixed>>, warnings: list<string>, errors: list<string>}
 */
function moghare360_jobcard_evidence_timeline_review(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'ok' => false,
            'jobcard_id' => $jobcardId,
            'media_count' => 0,
            'history_count' => 0,
            'events' => [],
            'warnings' => [],
            'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
        ];
    }

    $mediaFetch = moghare360_jobcard_evidence_timeline_fetch_media($jobcardId);
    $errors = [];

    if (!$mediaFetch['ok']) {
        $errors[] = $mediaFetch['error'];
    }

    $historyFetch = moghare360_jobcard_evidence_timeline_fetch_history($jobcardId);

    if (!$historyFetch['ok']) {
        $errors[] = $historyFetch['error'];
    }

    if ($errors !== []) {
        return [
            'ok' => false,
            'jobcard_id' => $jobcardId,
            'media_count' => 0,
            'history_count' => 0,
            'events' => [],
            'warnings' => [],
            'errors' => $errors,
        ];
    }

    $mediaRows = $mediaFetch['rows'];
    $historyRows = $historyFetch['rows'];
    $warnings = moghare360_jobcard_evidence_timeline_build_warnings($mediaRows, $historyRows);
    $events = moghare360_jobcard_evidence_timeline_build($mediaRows, $historyRows);

    return [
        'ok' => true,
        'jobcard_id' => $jobcardId,
        'media_count' => count($mediaRows),
        'history_count' => count($historyRows),
        'events' => $events,
        'warnings' => $warnings,
        'errors' => [],
    ];
}
