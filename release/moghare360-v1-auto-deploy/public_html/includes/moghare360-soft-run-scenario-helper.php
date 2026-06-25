<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Scenario Helper (Wave 6B)
 *
 * Read-only pilot scenario checklist · no DB writes.
 * Internal Soft Run pilot planning — NOT final vehicle delivery.
 */

$controlRoomPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
if (is_file($controlRoomPath)) {
    require_once $controlRoomPath;
}

const MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY = 'PILOT_READY';
const MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR = 'ERROR';

/**
 * @return list<array{key: string, title: string, title_fa: string, related_page: string, wave: string}>
 */
function moghare360_soft_run_scenario_required_scenarios(): array
{
    return [
        [
            'key' => 'customer_intake',
            'title' => 'Customer intake scenario',
            'title_fa' => 'سناریو پذیرش مشتری',
            'related_page' => 'erp-customer-create-v2.php',
            'wave' => 'WAVE_1',
        ],
        [
            'key' => 'vehicle_binding',
            'title' => 'Vehicle binding scenario',
            'title_fa' => 'سناریو اتصال خودرو',
            'related_page' => 'erp-vehicle-create-v2.php',
            'wave' => 'WAVE_1',
        ],
        [
            'key' => 'jobcard_creation',
            'title' => 'JobCard creation scenario',
            'title_fa' => 'سناریو ایجاد کارت کار',
            'related_page' => 'erp-jobcard-create-v2.php',
            'wave' => 'WAVE_1',
        ],
        [
            'key' => 'camera_evidence',
            'title' => 'Camera-only evidence scenario',
            'title_fa' => 'سناریو مدارک دوربین',
            'related_page' => 'erp-media-evidence-closure-dashboard.php',
            'wave' => 'WAVE_2',
        ],
        [
            'key' => 'diagnostic_binding',
            'title' => 'Diagnostic file binding scenario',
            'title_fa' => 'سناریو اتصال فایل تشخیصی',
            'related_page' => 'erp-media-evidence-closure-dashboard.php',
            'wave' => 'WAVE_2',
        ],
        [
            'key' => 'authorization_creation',
            'title' => 'Authorization creation scenario',
            'title_fa' => 'سناریو ایجاد مجوز',
            'related_page' => 'erp-jobcard-contract-authorization.php',
            'wave' => 'WAVE_3',
        ],
        [
            'key' => 'authorization_workflow',
            'title' => 'Authorization workflow scenario',
            'title_fa' => 'سناریو گردش کار مجوز',
            'related_page' => 'erp-jobcard-contract-authorization-workflow.php',
            'wave' => 'WAVE_3',
        ],
        [
            'key' => 'authorization_gate',
            'title' => 'Authorization gate scenario',
            'title_fa' => 'سناریو گیت مجوز',
            'related_page' => 'erp-jobcard-authorization-gate.php',
            'wave' => 'WAVE_3',
        ],
        [
            'key' => 'final_readiness',
            'title' => 'Final readiness scenario',
            'title_fa' => 'سناریو آمادگی نهایی',
            'related_page' => 'erp-jobcard-final-readiness.php',
            'wave' => 'WAVE_4',
        ],
        [
            'key' => 'delivery_eligibility',
            'title' => 'Delivery eligibility scenario',
            'title_fa' => 'سناریو صلاحیت تحویل',
            'related_page' => 'erp-jobcard-delivery-eligibility.php',
            'wave' => 'WAVE_4',
        ],
        [
            'key' => 'delivery_clearance',
            'title' => 'Delivery clearance scenario',
            'title_fa' => 'سناریو Clearance تحویل',
            'related_page' => 'erp-jobcard-delivery-clearance.php',
            'wave' => 'WAVE_4',
        ],
        [
            'key' => 'unified_command_center',
            'title' => 'Unified command center scenario',
            'title_fa' => 'سناریو مرکز فرمان یکپارچه',
            'related_page' => 'erp-jobcard-command-center.php',
            'wave' => 'WAVE_5',
        ],
        [
            'key' => 'operator_workbench',
            'title' => 'Operator workbench scenario',
            'title_fa' => 'سناریو میز فرمان اپراتور',
            'related_page' => 'erp-jobcard-command-workbench.php',
            'wave' => 'WAVE_5',
        ],
        [
            'key' => 'soft_run_control_room',
            'title' => 'Soft Run control room scenario',
            'title_fa' => 'سناریو اتاق کنترل Soft Run',
            'related_page' => 'erp-soft-run-control-room.php',
            'wave' => 'WAVE_6A',
        ],
    ];
}

/**
 * @return list<array{path: string, label_fa: string, critical: bool}>
 */
function moghare360_soft_run_scenario_required_pages(): array
{
    return [
        ['path' => 'erp-soft-run-control-room.php', 'label_fa' => 'اتاق کنترل Soft Run', 'critical' => true],
        ['path' => 'erp-jobcard-command-workbench.php', 'label_fa' => 'میز فرمان کارت کار', 'critical' => true],
        ['path' => 'erp-jobcard-command-center.php', 'label_fa' => 'مرکز فرمان یکپارچه', 'critical' => true],
        ['path' => 'erp-unified-operational-closure-dashboard.php', 'label_fa' => 'بستن عملیاتی WAVE 5', 'critical' => true],
        ['path' => 'erp-delivery-control-closure-dashboard.php', 'label_fa' => 'بستن کنترل تحویل WAVE 4', 'critical' => true],
        ['path' => 'erp-authorization-closure-dashboard.php', 'label_fa' => 'بستن مجوز WAVE 3', 'critical' => true],
        ['path' => 'erp-media-evidence-closure-dashboard.php', 'label_fa' => 'بستن مدارک WAVE 2', 'critical' => true],
        ['path' => 'erp-jobcard-final-readiness.php', 'label_fa' => 'آمادگی نهایی', 'critical' => true],
        ['path' => 'erp-jobcard-delivery-eligibility.php', 'label_fa' => 'صلاحیت تحویل', 'critical' => true],
        ['path' => 'erp-jobcard-delivery-clearance.php', 'label_fa' => 'ثبت Clearance', 'critical' => true],
        ['path' => 'erp-jobcard-delivery-clearance-preview.php', 'label_fa' => 'پیش‌نمایش Clearance', 'critical' => true],
        ['path' => 'erp-jobcard-authorization-gate.php', 'label_fa' => 'گیت مجوز', 'critical' => true],
        ['path' => 'erp-jobcard-contract-authorization.php', 'label_fa' => 'ثبت مجوز قرارداد', 'critical' => true],
        ['path' => 'erp-jobcard-contract-authorization-preview.php', 'label_fa' => 'پیش‌نمایش مجوز', 'critical' => true],
        ['path' => 'erp-jobcard-contract-authorization-workflow.php', 'label_fa' => 'گردش کار مجوز', 'critical' => true],
    ];
}

function moghare360_soft_run_scenario_public_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, runtime_summary: array<string, mixed>, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_scenario_fetch_control_room_status(): array
{
    if (!function_exists('moghare360_soft_run_control_room_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper اتاق کنترل Soft Run در دسترس نیست.',
            'runtime_summary' => [],
            'errors' => ['control_room_helper_missing'],
            'helper_available' => false,
        ];
    }

    $evaluation = moghare360_soft_run_control_room_evaluate();

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_STATUS_ERROR),
        'label' => function_exists('moghare360_soft_run_control_room_status_label')
            ? moghare360_soft_run_control_room_status_label((string)($evaluation['status'] ?? ''))
            : (string)($evaluation['status'] ?? ''),
        'message' => (string)($evaluation['message'] ?? ''),
        'runtime_summary' => (array)($evaluation['runtime_summary'] ?? []),
        'errors' => (array)($evaluation['errors'] ?? []),
        'helper_available' => true,
    ];
}

/**
 * @return array{ok: bool, total: int, present: int, missing: int, pages: list<array<string, mixed>>}
 */
function moghare360_soft_run_scenario_page_status(): array
{
    $publicRoot = moghare360_soft_run_scenario_public_root();
    $requiredPages = moghare360_soft_run_scenario_required_pages();
    $pages = [];
    $present = 0;
    $missing = 0;

    foreach ($requiredPages as $pageDef) {
        $path = (string)($pageDef['path'] ?? '');
        $fullPath = $publicRoot . DIRECTORY_SEPARATOR . $path;
        $exists = $path !== '' && is_file($fullPath);

        if ($exists) {
            $present++;
        } else {
            $missing++;
        }

        $pages[] = [
            'path' => $path,
            'label_fa' => (string)($pageDef['label_fa'] ?? $path),
            'critical' => (bool)($pageDef['critical'] ?? true),
            'exists' => $exists,
            'status' => $exists ? 'PRESENT' : 'MISSING',
        ];
    }

    return [
        'ok' => $missing === 0,
        'total' => count($requiredPages),
        'present' => $present,
        'missing' => $missing,
        'pages' => $pages,
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   control_room: array<string, mixed>,
 *   scenarios: list<array<string, mixed>>,
 *   pages: array<string, mixed>,
 *   ready_items: list<string>,
 *   review_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   message: string,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_scenario_evaluate(): array
{
    $scenarios = moghare360_soft_run_scenario_required_scenarios();
    $controlRoom = moghare360_soft_run_scenario_fetch_control_room_status();
    $pageStatus = moghare360_soft_run_scenario_page_status();
    $publicRoot = moghare360_soft_run_scenario_public_root();

    $readyItems = [];
    $reviewItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    if ($scenarios === []) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY,
            'control_room' => $controlRoom,
            'scenarios' => [],
            'pages' => $pageStatus,
            'ready_items' => [],
            'review_items' => [],
            'blocked_items' => [],
            'missing_items' => ['فهرست سناریوهای آزمایشی تعریف نشده است'],
            'message' => 'برد اجرای آزمایشی — خالی (سناریو تعریف نشده).',
            'errors' => ['no_scenarios_defined'],
        ];
    }

    $enrichedScenarios = [];
    foreach ($scenarios as $scenario) {
        $relatedPage = (string)($scenario['related_page'] ?? '');
        $pageExists = $relatedPage !== '' && is_file($publicRoot . DIRECTORY_SEPARATOR . $relatedPage);

        $scenarioStatus = 'READY';
        if (!$pageExists) {
            $scenarioStatus = 'MISSING';
            $missingItems[] = (string)($scenario['title_fa'] ?? $scenario['key']) . ' — صفحه ' . $relatedPage . ' یافت نشد';
        } else {
            $readyItems[] = (string)($scenario['title_fa'] ?? $scenario['key']) . ' — آماده بازبینی';
        }

        $enrichedScenarios[] = array_merge($scenario, [
            'page_exists' => $pageExists,
            'status' => $scenarioStatus,
        ]);
    }

    if (!$controlRoom['helper_available']) {
        $blockedItems[] = 'اتاق کنترل Soft Run — helper موجود نیست';
        $errors[] = 'control_room_helper_missing';

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR,
            'control_room' => $controlRoom,
            'scenarios' => $enrichedScenarios,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'برد اجرای آزمایشی — خطا (وابستگی اتاق کنترل نامعتبر).',
            'errors' => $errors,
        ];
    }

    $controlRoomStatus = strtoupper(trim((string)$controlRoom['status']));

    if ($controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_BLOCKED || $controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_ERROR) {
        $blockedItems[] = 'اتاق کنترل Soft Run — ' . (string)$controlRoom['message'];

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED,
            'control_room' => $controlRoom,
            'scenarios' => $enrichedScenarios,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'برد اجرای آزمایشی — مسدود (اتاق کنترل Soft Run BLOCKED/ERROR).',
            'errors' => $errors,
        ];
    }

    foreach ($pageStatus['pages'] as $pageRow) {
        if (!($pageRow['exists'] ?? false) && ($pageRow['critical'] ?? false)) {
            $blockedItems[] = 'صفحه بحرانی مفقود: ' . (string)($pageRow['path'] ?? '');
            $missingItems[] = (string)($pageRow['label_fa'] ?? '') . ' — ' . (string)($pageRow['path'] ?? '');
        }
    }

    if ($blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED,
            'control_room' => $controlRoom,
            'scenarios' => $enrichedScenarios,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'برد اجرای آزمایشی — مسدود (صفحه بحرانی مفقود).',
            'errors' => $errors,
        ];
    }

    if ((int)($pageStatus['present'] ?? 0) === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY,
            'control_room' => $controlRoom,
            'scenarios' => $enrichedScenarios,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'برد اجرای آزمایشی — خالی (صفحه زمان اجرا یافت نشد).',
            'errors' => $errors,
        ];
    }

    $allScenariosOk = true;
    foreach ($enrichedScenarios as $scenario) {
        if (($scenario['status'] ?? '') === 'MISSING') {
            $allScenariosOk = false;
            break;
        }
    }

    $softRunReady = $controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_READY;
    $allPagesPresent = (int)($pageStatus['missing'] ?? 0) === 0;

    if ($softRunReady && $allPagesPresent && $allScenariosOk && $reviewItems === []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY,
            'control_room' => $controlRoom,
            'scenarios' => $enrichedScenarios,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'برد اجرای آزمایشی — آماده (Soft Run READY، سناریوها و صفحات زمان اجرا موجود).',
            'errors' => $errors,
        ];
    }

    if ($controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED) {
        $reviewItems[] = 'اتاق کنترل Soft Run — ' . (string)$controlRoom['message'];
    }

    if (!$allScenariosOk) {
        $reviewItems[] = 'برخی سناریوها صفحه مرتبط ندارند — بازبینی اپراتور';
    }

    if (!$allPagesPresent && $blockedItems === []) {
        $reviewItems[] = 'برخی صفحات غیربحرانی نیازمند بازبینی هستند';
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED,
        'control_room' => $controlRoom,
        'scenarios' => $enrichedScenarios,
        'pages' => $pageStatus,
        'ready_items' => $readyItems,
        'review_items' => $reviewItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'message' => 'برد اجرای آزمایشی — نیازمند بازبینی (یک یا چند مورد برای اپراتور).',
        'errors' => $errors,
    ];
}

function moghare360_soft_run_scenario_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY => 'آماده اجرای آزمایشی',
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
