<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2 Media & Evidence Closure Helper (Wave 2F)
 *
 * Read-only operational closure summary · no DB writes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_WAVE2_CLOSURE_MEDIA_TABLE = 'erp_jobcard_media';
const MOGHARE360_WAVE2_CLOSURE_HISTORY_TABLE = 'erp_jobcard_media_history';

const MOGHARE360_WAVE2_CLOSURE_STATUS_READY = 'READY';
const MOGHARE360_WAVE2_CLOSURE_STATUS_PARTIAL = 'PARTIAL';
const MOGHARE360_WAVE2_CLOSURE_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_WAVE2_CLOSURE_STATUS_ERROR = 'ERROR';

/**
 * @return array{ok: bool, connection: mixed, error: string}
 */
function moghare360_wave_2_closure_db_context(): array
{
    $connection = customer_core_db();

    if ($connection === false) {
        return ['ok' => false, 'connection' => false, 'error' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    return ['ok' => true, 'connection' => $connection, 'error' => ''];
}

/**
 * @return array{ok: bool, summary: array<string, mixed>, error: string}
 */
function moghare360_wave_2_closure_fetch_summary(): array
{
    $ctx = moghare360_wave_2_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'summary' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE2_CLOSURE_MEDIA_TABLE)) {
        return ['ok' => false, 'summary' => [], 'error' => 'جدول erp_jobcard_media یافت نشد.'];
    }

    $historyTableOk = customer_core_table_exists($connection, MOGHARE360_WAVE2_CLOSURE_HISTORY_TABLE);

    $totalMedia = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcard_media',
        []
    ) ?? 0);

    $totalCamera = (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_media WHERE media_type <> 'diagnostic'",
        []
    ) ?? 0);

    $totalDiagnostic = (int)(customer_core_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.erp_jobcard_media WHERE media_type = 'diagnostic'",
        []
    ) ?? 0);

    $totalHistory = 0;
    if ($historyTableOk) {
        $totalHistory = (int)(customer_core_scalar(
            $connection,
            'SELECT COUNT(*) FROM dbo.erp_jobcard_media_history',
            []
        ) ?? 0);
    }

    $byStage = moghare360_wave_2_closure_fetch_group_counts($connection, 'media_stage');
    $byType = moghare360_wave_2_closure_fetch_group_counts($connection, 'media_type');
    $byMime = moghare360_wave_2_closure_fetch_group_counts($connection, 'mime_type');

    $latestMedia = customer_core_scalar(
        $connection,
        'SELECT TOP 1 CONVERT(VARCHAR(30), created_at, 120) FROM dbo.erp_jobcard_media ORDER BY created_at DESC',
        []
    );

    $latestHistory = null;
    if ($historyTableOk) {
        $latestHistory = customer_core_scalar(
            $connection,
            'SELECT TOP 1 CONVERT(VARCHAR(30), event_at, 120) FROM dbo.erp_jobcard_media_history ORDER BY event_at DESC',
            []
        );
    }

    return [
        'ok' => true,
        'summary' => [
            'total_media' => $totalMedia,
            'total_camera_photo' => $totalCamera,
            'total_diagnostic' => $totalDiagnostic,
            'total_history' => $totalHistory,
            'by_media_stage' => $byStage,
            'by_media_type' => $byType,
            'by_mime_type' => $byMime,
            'latest_media_created_at' => $latestMedia ?? '',
            'latest_history_event_at' => $latestHistory ?? '',
            'media_table_ok' => true,
            'history_table_ok' => $historyTableOk,
        ],
        'error' => '',
    ];
}

/**
 * @return array<string, int>
 */
function moghare360_wave_2_closure_fetch_group_counts($connection, string $column): array
{
    $allowed = ['media_stage', 'media_type', 'mime_type'];

    if (!in_array($column, $allowed, true)) {
        return [];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT ' . $column . ' AS grp, COUNT(*) AS cnt FROM dbo.erp_jobcard_media GROUP BY ' . $column . ' ORDER BY cnt DESC',
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
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_wave_2_closure_fetch_recent_media(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $ctx = moghare360_wave_2_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'rows' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE2_CLOSURE_MEDIA_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_media یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' media_id, jobcard_id, media_stage, media_type, mime_type, relative_path, created_at
         FROM dbo.erp_jobcard_media
         ORDER BY created_at DESC, media_id DESC',
        []
    );

    foreach ($rows as &$row) {
        $row['relative_path'] = moghare360_wave_2_closure_safe_relative_path((string)($row['relative_path'] ?? ''));
    }
    unset($row);

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

/**
 * @return array{ok: bool, rows: list<array<string, string>>, error: string}
 */
function moghare360_wave_2_closure_fetch_recent_history(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $ctx = moghare360_wave_2_closure_db_context();

    if (!$ctx['ok']) {
        return ['ok' => false, 'rows' => [], 'error' => $ctx['error']];
    }

    $connection = $ctx['connection'];

    if (!customer_core_table_exists($connection, MOGHARE360_WAVE2_CLOSURE_HISTORY_TABLE)) {
        return ['ok' => false, 'rows' => [], 'error' => 'جدول erp_jobcard_media_history یافت نشد.'];
    }

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT TOP ' . $limit . ' history_id, media_id, jobcard_id, event_code, event_title, event_at
         FROM dbo.erp_jobcard_media_history
         ORDER BY event_at DESC, history_id DESC',
        []
    );

    return ['ok' => true, 'rows' => $rows, 'error' => ''];
}

function moghare360_wave_2_closure_safe_relative_path(string $relativePath): string
{
    $relativePath = trim(str_replace('\\', '/', $relativePath));

    if ($relativePath === '' || preg_match('#^https?://#i', $relativePath) === 1 || str_contains($relativePath, '..')) {
        return '';
    }

    if (!str_starts_with($relativePath, 'storage/')) {
        return '';
    }

    return $relativePath;
}

/**
 * @return array{ok: bool, status: string, message: string, checks: array<string, bool>, errors: list<string>, summary: array<string, mixed>}
 */
function moghare360_wave_2_closure_status(): array
{
    $summaryResult = moghare360_wave_2_closure_fetch_summary();

    if (!$summaryResult['ok']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_WAVE2_CLOSURE_STATUS_ERROR,
            'message' => 'خواندن خلاصه WAVE 2 ناموفق بود.',
            'checks' => [
                'media_table_readable' => false,
                'history_table_readable' => false,
                'camera_evidence_exists' => false,
                'diagnostic_records_exist' => false,
                'history_records_exist' => false,
                'no_db_write_required' => true,
            ],
            'errors' => [$summaryResult['error']],
            'summary' => [],
        ];
    }

    $summary = $summaryResult['summary'];
    $checks = [
        'media_table_readable' => ($summary['media_table_ok'] ?? false) === true,
        'history_table_readable' => ($summary['history_table_ok'] ?? false) === true,
        'camera_evidence_exists' => (int)($summary['total_camera_photo'] ?? 0) > 0,
        'diagnostic_records_exist' => (int)($summary['total_diagnostic'] ?? 0) > 0,
        'history_records_exist' => (int)($summary['total_history'] ?? 0) > 0,
        'no_db_write_required' => true,
    ];

    $totalMedia = (int)($summary['total_media'] ?? 0);
    $totalHistory = (int)($summary['total_history'] ?? 0);

    if ($totalMedia === 0 && $totalHistory === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE2_CLOSURE_STATUS_EMPTY,
            'message' => 'هیچ رکورد رسانه یا تاریخچه‌ای ثبت نشده است.',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    $ready = $checks['media_table_readable']
        && $checks['history_table_readable']
        && $checks['camera_evidence_exists']
        && $checks['diagnostic_records_exist']
        && $checks['history_records_exist'];

    if ($ready) {
        return [
            'ok' => true,
            'status' => MOGHARE360_WAVE2_CLOSURE_STATUS_READY,
            'message' => 'بستن عملیاتی WAVE 2 — آماده (مدارک دوربین، تشخیصی و تاریخچه فعال).',
            'checks' => $checks,
            'errors' => [],
            'summary' => $summary,
        ];
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_WAVE2_CLOSURE_STATUS_PARTIAL,
        'message' => 'بستن عملیاتی WAVE 2 — ناقص (برخی اجزا هنوز داده ندارند).',
        'checks' => $checks,
        'errors' => [],
        'summary' => $summary,
    ];
}

function moghare360_wave_2_closure_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_WAVE2_CLOSURE_STATUS_READY => 'آماده',
        MOGHARE360_WAVE2_CLOSURE_STATUS_PARTIAL => 'ناقص',
        MOGHARE360_WAVE2_CLOSURE_STATUS_EMPTY => 'خالی',
        MOGHARE360_WAVE2_CLOSURE_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}

/**
 * @return list<array{label: string, href: string}>
 */
function moghare360_wave_2_closure_navigation_links(): array
{
    return [
        ['label' => 'ثبت تصویر دوربین', 'href' => 'erp-jobcard-camera-capture.php'],
        ['label' => 'پیش‌نمایش رسانه', 'href' => 'erp-jobcard-media-preview.php?jobcard_id=1'],
        ['label' => 'ثبت فایل تشخیصی', 'href' => 'erp-jobcard-diagnostic-file.php'],
        ['label' => 'پیش‌نمایش تشخیصی', 'href' => 'erp-jobcard-diagnostic-preview.php?jobcard_id=1'],
        ['label' => 'بازبینی تکمیل مدارک', 'href' => 'erp-jobcard-evidence-review.php?jobcard_id=1'],
        ['label' => 'خط زمانی مدارک', 'href' => 'erp-jobcard-evidence-timeline.php?jobcard_id=1'],
    ];
}
