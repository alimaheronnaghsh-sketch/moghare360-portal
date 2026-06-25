<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Control Room Helper (Wave 6A)
 *
 * Read-only internal Soft Run control summary · no DB writes.
 * Aggregates WAVE 2–5 closure layers — NOT final vehicle delivery.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

$wave2ClosurePath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';
$wave3ClosurePath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';
$wave4ClosurePath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-wave-4-delivery-control-closure-helper.php';
$wave5ClosurePath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-wave-5-unified-closure-helper.php';

if (is_file($wave2ClosurePath)) {
    require_once $wave2ClosurePath;
}
if (is_file($wave3ClosurePath)) {
    require_once $wave3ClosurePath;
}
if (is_file($wave4ClosurePath)) {
    require_once $wave4ClosurePath;
}
if (is_file($wave5ClosurePath)) {
    require_once $wave5ClosurePath;
}

const MOGHARE360_SOFT_RUN_STATUS_READY = 'SOFT_RUN_READY';
const MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_SOFT_RUN_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_STATUS_ERROR = 'ERROR';

/**
 * @return array{ok: bool, wave: string, status: string, label: string, message: string, checks: array<string, bool>, summary: array<string, mixed>, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_control_room_normalize_closure(string $wave, ?array $closure, bool $helperAvailable): array
{
    if (!$helperAvailable || $closure === null) {
        return [
            'ok' => false,
            'wave' => $wave,
            'status' => 'ERROR',
            'label' => 'خطا',
            'message' => 'Helper بستن ' . $wave . ' در دسترس نیست.',
            'checks' => [],
            'summary' => [],
            'errors' => ['helper_missing'],
            'helper_available' => false,
        ];
    }

    $status = strtoupper(trim((string)($closure['status'] ?? 'ERROR')));

    return [
        'ok' => (bool)($closure['ok'] ?? false),
        'wave' => $wave,
        'status' => $status,
        'label' => moghare360_soft_run_control_room_wave_label($wave, $status),
        'message' => (string)($closure['message'] ?? ''),
        'checks' => (array)($closure['checks'] ?? []),
        'summary' => (array)($closure['summary'] ?? []),
        'errors' => (array)($closure['errors'] ?? []),
        'helper_available' => true,
    ];
}

function moghare360_soft_run_control_room_wave_label(string $wave, string $status): string
{
    $status = strtoupper(trim($status));

    return match ($wave) {
        'WAVE_2' => function_exists('moghare360_wave_2_closure_status_label')
            ? moghare360_wave_2_closure_status_label($status)
            : $status,
        'WAVE_3' => function_exists('moghare360_wave_3_closure_status_label')
            ? moghare360_wave_3_closure_status_label($status)
            : $status,
        'WAVE_4' => function_exists('moghare360_wave_4_closure_status_label')
            ? moghare360_wave_4_closure_status_label($status)
            : $status,
        'WAVE_5' => function_exists('moghare360_wave_5_closure_status_label')
            ? moghare360_wave_5_closure_status_label($status)
            : $status,
        default => $status,
    };
}

/**
 * @return array{ok: bool, wave: string, status: string, label: string, message: string, checks: array<string, bool>, summary: array<string, mixed>, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_control_room_fetch_wave_2_status(): array
{
    if (!function_exists('moghare360_wave_2_closure_status')) {
        return moghare360_soft_run_control_room_normalize_closure('WAVE_2', null, false);
    }

    return moghare360_soft_run_control_room_normalize_closure(
        'WAVE_2',
        moghare360_wave_2_closure_status(),
        true
    );
}

/**
 * @return array{ok: bool, wave: string, status: string, label: string, message: string, checks: array<string, bool>, summary: array<string, mixed>, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_control_room_fetch_wave_3_status(): array
{
    if (!function_exists('moghare360_wave_3_closure_status')) {
        return moghare360_soft_run_control_room_normalize_closure('WAVE_3', null, false);
    }

    return moghare360_soft_run_control_room_normalize_closure(
        'WAVE_3',
        moghare360_wave_3_closure_status(),
        true
    );
}

/**
 * @return array{ok: bool, wave: string, status: string, label: string, message: string, checks: array<string, bool>, summary: array<string, mixed>, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_control_room_fetch_wave_4_status(): array
{
    if (!function_exists('moghare360_wave_4_closure_status')) {
        return moghare360_soft_run_control_room_normalize_closure('WAVE_4', null, false);
    }

    return moghare360_soft_run_control_room_normalize_closure(
        'WAVE_4',
        moghare360_wave_4_closure_status(),
        true
    );
}

/**
 * @return array{ok: bool, wave: string, status: string, label: string, message: string, checks: array<string, bool>, summary: array<string, mixed>, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_control_room_fetch_wave_5_status(): array
{
    if (!function_exists('moghare360_wave_5_closure_status')) {
        return moghare360_soft_run_control_room_normalize_closure('WAVE_5', null, false);
    }

    return moghare360_soft_run_control_room_normalize_closure(
        'WAVE_5',
        moghare360_wave_5_closure_status(),
        true
    );
}

/**
 * @return array{ok: bool, total_jobcards: int, wave_2_media: int, wave_3_authorizations: int, wave_4_clearances: int, wave_5_listed: int, closure_helpers: array<string, bool>, error: string}
 */
function moghare360_soft_run_control_room_fetch_runtime_summary(): array
{
    $wave2 = moghare360_soft_run_control_room_fetch_wave_2_status();
    $wave3 = moghare360_soft_run_control_room_fetch_wave_3_status();
    $wave4 = moghare360_soft_run_control_room_fetch_wave_4_status();
    $wave5 = moghare360_soft_run_control_room_fetch_wave_5_status();

    $totalJobcards = (int)($wave5['summary']['total_in_db'] ?? 0);

    if ($totalJobcards === 0) {
        $connection = customer_core_db();
        if ($connection !== false && customer_core_table_exists($connection, 'erp_jobcards')) {
            $totalJobcards = (int)(customer_core_scalar(
                $connection,
                'SELECT COUNT(*) FROM dbo.erp_jobcards',
                []
            ) ?? 0);
        }
    }

    return [
        'ok' => true,
        'total_jobcards' => $totalJobcards,
        'wave_2_media' => (int)($wave2['summary']['total_media'] ?? 0),
        'wave_3_authorizations' => (int)($wave3['summary']['total_authorizations'] ?? 0),
        'wave_4_clearances' => (int)($wave4['summary']['total_clearances'] ?? 0),
        'wave_5_listed' => (int)($wave5['summary']['total_listed'] ?? 0),
        'closure_helpers' => [
            'wave_2' => $wave2['helper_available'],
            'wave_3' => $wave3['helper_available'],
            'wave_4' => $wave4['helper_available'],
            'wave_5' => $wave5['helper_available'],
        ],
        'error' => '',
    ];
}

/**
 * @param array{ok: bool, wave: string, status: string, label: string, message: string, checks: array<string, bool>, summary: array<string, mixed>, errors: list<string>, helper_available: bool} $waveStatus
 */
function moghare360_soft_run_control_room_wave_2_acceptable(array $waveStatus): bool
{
    $status = strtoupper(trim((string)($waveStatus['status'] ?? '')));

    if ($status === 'READY') {
        return true;
    }

    if ($status === 'PARTIAL') {
        $totalMedia = (int)($waveStatus['summary']['total_media'] ?? 0);
        $totalHistory = (int)($waveStatus['summary']['total_history'] ?? 0);

        return $totalMedia > 0 || $totalHistory > 0;
    }

    return false;
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   wave_2: array<string, mixed>,
 *   wave_3: array<string, mixed>,
 *   wave_4: array<string, mixed>,
 *   wave_5: array<string, mixed>,
 *   runtime_summary: array<string, mixed>,
 *   ready_items: list<string>,
 *   review_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   message: string,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_control_room_evaluate(): array
{
    $wave2 = moghare360_soft_run_control_room_fetch_wave_2_status();
    $wave3 = moghare360_soft_run_control_room_fetch_wave_3_status();
    $wave4 = moghare360_soft_run_control_room_fetch_wave_4_status();
    $wave5 = moghare360_soft_run_control_room_fetch_wave_5_status();
    $runtimeSummary = moghare360_soft_run_control_room_fetch_runtime_summary();

    $waves = [
        'WAVE_2' => $wave2,
        'WAVE_3' => $wave3,
        'WAVE_4' => $wave4,
        'WAVE_5' => $wave5,
    ];

    $readyItems = [];
    $reviewItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    foreach ($waves as $waveKey => $waveStatus) {
        if (!$waveStatus['helper_available']) {
            $missingItems[] = $waveKey . ' — helper بستن موجود نیست';
            $blockedItems[] = $waveKey . ' — helper ناموجود';
            $errors[] = $waveKey . '_helper_missing';
            continue;
        }

        $status = strtoupper(trim((string)$waveStatus['status']));

        if ($status === 'ERROR' || !$waveStatus['ok']) {
            $blockedItems[] = $waveKey . ' — ' . (string)$waveStatus['message'];
            if ($waveStatus['errors'] !== []) {
                foreach ($waveStatus['errors'] as $err) {
                    $errors[] = $waveKey . ': ' . (string)$err;
                }
            }
            continue;
        }

        if ($status === 'READY') {
            $readyItems[] = $waveKey . ' — ' . (string)$waveStatus['message'];
            continue;
        }

        if ($waveKey === 'WAVE_2' && moghare360_soft_run_control_room_wave_2_acceptable($waveStatus)) {
            $readyItems[] = $waveKey . ' — پوشش مدارک قابل قبول (PARTIAL با داده)';
            continue;
        }

        if ($status === 'PARTIAL') {
            $reviewItems[] = $waveKey . ' — ' . (string)$waveStatus['message'];
            continue;
        }

        if ($status === 'EMPTY') {
            $missingItems[] = $waveKey . ' — ' . (string)$waveStatus['message'];
            continue;
        }

        $reviewItems[] = $waveKey . ' — وضعیت: ' . $status;
    }

    $anyHelperMissing = in_array(false, $runtimeSummary['closure_helpers'] ?? [], true);
    if ($anyHelperMissing) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_STATUS_ERROR,
            'wave_2' => $wave2,
            'wave_3' => $wave3,
            'wave_4' => $wave4,
            'wave_5' => $wave5,
            'runtime_summary' => $runtimeSummary,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'Soft Run — خطا در وابستگی‌های بستن عملیاتی.',
            'errors' => $errors,
        ];
    }

    if ($blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_STATUS_BLOCKED,
            'wave_2' => $wave2,
            'wave_3' => $wave3,
            'wave_4' => $wave4,
            'wave_5' => $wave5,
            'runtime_summary' => $runtimeSummary,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'Soft Run — مسدود (یک یا چند لایه کنترل خطا یا BLOCKED دارد).',
            'errors' => $errors,
        ];
    }

    $totalJobcards = (int)($runtimeSummary['total_jobcards'] ?? 0);
    $hasMeaningfulData = $totalJobcards > 0
        || (int)($runtimeSummary['wave_2_media'] ?? 0) > 0
        || (int)($runtimeSummary['wave_3_authorizations'] ?? 0) > 0
        || (int)($runtimeSummary['wave_4_clearances'] ?? 0) > 0;

    if (!$hasMeaningfulData && $missingItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_STATUS_EMPTY,
            'wave_2' => $wave2,
            'wave_3' => $wave3,
            'wave_4' => $wave4,
            'wave_5' => $wave5,
            'runtime_summary' => $runtimeSummary,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'Soft Run — خالی (داده عملیاتی معناداری وجود ندارد).',
            'errors' => $errors,
        ];
    }

    $wave2Ok = moghare360_soft_run_control_room_wave_2_acceptable($wave2);
    $wave3Ready = strtoupper(trim((string)$wave3['status'])) === 'READY';
    $wave4Ready = strtoupper(trim((string)$wave4['status'])) === 'READY';
    $wave5Ready = strtoupper(trim((string)$wave5['status'])) === 'READY';

    if ($wave2Ok && $wave3Ready && $wave4Ready && $wave5Ready && $totalJobcards > 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_STATUS_READY,
            'wave_2' => $wave2,
            'wave_3' => $wave3,
            'wave_4' => $wave4,
            'wave_5' => $wave5,
            'runtime_summary' => $runtimeSummary,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'Soft Run — آماده (تمام لایه‌های بستن عملیاتی WAVE 2–5 و حداقل یک کارت کار).',
            'errors' => $errors,
        ];
    }

    if ($reviewItems === [] && $missingItems !== []) {
        foreach ($missingItems as $item) {
            $reviewItems[] = $item;
        }
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED,
        'wave_2' => $wave2,
        'wave_3' => $wave3,
        'wave_4' => $wave4,
        'wave_5' => $wave5,
        'runtime_summary' => $runtimeSummary,
        'ready_items' => $readyItems,
        'review_items' => $reviewItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'message' => 'Soft Run — نیازمند بازبینی (یک یا چند موج PARTIAL یا ناقص است).',
        'errors' => $errors,
    ];
}

function moghare360_soft_run_control_room_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_SOFT_RUN_STATUS_READY => 'آماده Soft Run',
        MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_SOFT_RUN_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
