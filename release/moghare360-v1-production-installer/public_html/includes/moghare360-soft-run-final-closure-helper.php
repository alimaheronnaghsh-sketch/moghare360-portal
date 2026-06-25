<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Final Closure Helper (Wave 6D)
 *
 * Read-only final closure summary · no DB writes.
 * Internal Soft Run pilot readiness signoff — NOT final vehicle delivery.
 */

$controlRoomPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';
$scenarioPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
$testPackPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-operator-test-pack-helper.php';

if (is_file($controlRoomPath)) {
    require_once $controlRoomPath;
}
if (is_file($scenarioPath)) {
    require_once $scenarioPath;
}
if (is_file($testPackPath)) {
    require_once $testPackPath;
}

const MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY = 'PILOT_READY_FOR_CONTROLLED_EXECUTION';
const MOGHARE360_SOFT_RUN_FINAL_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_SOFT_RUN_FINAL_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_FINAL_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_FINAL_STATUS_ERROR = 'ERROR';

function moghare360_soft_run_final_closure_public_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_final_closure_fetch_control_room_status(): array
{
    if (!function_exists('moghare360_soft_run_control_room_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper اتاق کنترل WAVE 6A در دسترس نیست.',
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
        'errors' => (array)($evaluation['errors'] ?? []),
        'helper_available' => true,
    ];
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_final_closure_fetch_scenario_board_status(): array
{
    if (!function_exists('moghare360_soft_run_scenario_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper برد سناریو WAVE 6B در دسترس نیست.',
            'errors' => ['scenario_helper_missing'],
            'helper_available' => false,
        ];
    }

    $evaluation = moghare360_soft_run_scenario_evaluate();

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR),
        'label' => function_exists('moghare360_soft_run_scenario_status_label')
            ? moghare360_soft_run_scenario_status_label((string)($evaluation['status'] ?? ''))
            : (string)($evaluation['status'] ?? ''),
        'message' => (string)($evaluation['message'] ?? ''),
        'errors' => (array)($evaluation['errors'] ?? []),
        'helper_available' => true,
    ];
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_final_closure_fetch_operator_test_pack_status(): array
{
    if (!function_exists('moghare360_soft_run_operator_test_pack_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper بسته تست اپراتوری WAVE 6C در دسترس نیست.',
            'errors' => ['test_pack_helper_missing'],
            'helper_available' => false,
        ];
    }

    $evaluation = moghare360_soft_run_operator_test_pack_evaluate();

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR),
        'label' => function_exists('moghare360_soft_run_operator_test_pack_status_label')
            ? moghare360_soft_run_operator_test_pack_status_label((string)($evaluation['status'] ?? ''))
            : (string)($evaluation['status'] ?? ''),
        'message' => (string)($evaluation['message'] ?? ''),
        'errors' => (array)($evaluation['errors'] ?? []),
        'helper_available' => true,
    ];
}

/**
 * @return list<array{path: string, label_fa: string, critical: bool}>
 */
function moghare360_soft_run_final_closure_required_pages(): array
{
    return [
        ['path' => 'erp-soft-run-control-room.php', 'label_fa' => 'اتاق کنترل Soft Run (WAVE 6A)', 'critical' => true],
        ['path' => 'erp-soft-run-scenario-board.php', 'label_fa' => 'برد سناریوهای اجرای آزمایشی (WAVE 6B)', 'critical' => true],
        ['path' => 'erp-soft-run-operator-test-pack.php', 'label_fa' => 'بسته تست اپراتوری (WAVE 6C)', 'critical' => true],
        ['path' => 'erp-jobcard-command-workbench.php', 'label_fa' => 'میز فرمان کارت کار', 'critical' => true],
        ['path' => 'erp-jobcard-command-center.php', 'label_fa' => 'مرکز فرمان یکپارچه', 'critical' => true],
        ['path' => 'erp-unified-operational-closure-dashboard.php', 'label_fa' => 'بستن عملیاتی WAVE 5', 'critical' => true],
        ['path' => 'erp-delivery-control-closure-dashboard.php', 'label_fa' => 'بستن کنترل تحویل WAVE 4', 'critical' => true],
        ['path' => 'erp-authorization-closure-dashboard.php', 'label_fa' => 'بستن مجوز WAVE 3', 'critical' => true],
        ['path' => 'erp-media-evidence-closure-dashboard.php', 'label_fa' => 'بستن مدارک WAVE 2', 'critical' => true],
    ];
}

/**
 * @return array{ok: bool, total: int, present: int, missing: int, pages: list<array<string, mixed>>}
 */
function moghare360_soft_run_final_closure_page_status(): array
{
    $publicRoot = moghare360_soft_run_final_closure_public_root();
    $requiredPages = moghare360_soft_run_final_closure_required_pages();
    $pages = [];
    $present = 0;
    $missing = 0;

    foreach ($requiredPages as $pageDef) {
        $path = (string)($pageDef['path'] ?? '');
        $exists = $path !== '' && is_file($publicRoot . DIRECTORY_SEPARATOR . $path);

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
 * @return list<string>
 */
function moghare360_soft_run_final_closure_default_signoff_notes(): array
{
    return [
        'این داشبورد فقط بستن داخلی Soft Run و آمادگی پایلوت است — بدون نوشتن پایگاه داده.',
        'تحویل نهایی خودرو انجام نمی‌شود و رکورد تکمیل تحویل ایجاد نمی‌شود.',
        'پورتال عمومی، پورتال مشتری، پرداخت، حسابداری رسمی و SaaS فعال نیست.',
        'امضای قانونی نهایی و ورود تولید (production login) فعال نشده است.',
        'قوانین WAVE 2 تا 6C بدون تغییر باقی مانده‌اند.',
        'Cursor تصمیم گام بعدی نقشه راه را اتخاذ نکرده است.',
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   control_room: array<string, mixed>,
 *   scenario_board: array<string, mixed>,
 *   operator_test_pack: array<string, mixed>,
 *   pages: array<string, mixed>,
 *   ready_items: list<string>,
 *   review_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   signoff_notes: list<string>,
 *   message: string,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_final_closure_evaluate(): array
{
    $controlRoom = moghare360_soft_run_final_closure_fetch_control_room_status();
    $scenarioBoard = moghare360_soft_run_final_closure_fetch_scenario_board_status();
    $operatorTestPack = moghare360_soft_run_final_closure_fetch_operator_test_pack_status();
    $pageStatus = moghare360_soft_run_final_closure_page_status();
    $signoffNotes = moghare360_soft_run_final_closure_default_signoff_notes();

    $readyItems = [];
    $reviewItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    $helpersAvailable = $controlRoom['helper_available']
        && $scenarioBoard['helper_available']
        && $operatorTestPack['helper_available'];

    if (!$helpersAvailable) {
        if (!$controlRoom['helper_available']) {
            $blockedItems[] = 'WAVE 6A — helper اتاق کنترل موجود نیست';
            $errors[] = 'control_room_helper_missing';
        }
        if (!$scenarioBoard['helper_available']) {
            $blockedItems[] = 'WAVE 6B — helper برد سناریو موجود نیست';
            $errors[] = 'scenario_helper_missing';
        }
        if (!$operatorTestPack['helper_available']) {
            $blockedItems[] = 'WAVE 6C — helper بسته تست اپراتوری موجود نیست';
            $errors[] = 'test_pack_helper_missing';
        }

        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_FINAL_STATUS_ERROR,
            'control_room' => $controlRoom,
            'scenario_board' => $scenarioBoard,
            'operator_test_pack' => $operatorTestPack,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'message' => 'بستن نهایی Soft Run — خطا (وابستگی‌های WAVE 6A/6B/6C نامعتبر).',
            'errors' => $errors,
        ];
    }

    $controlRoomStatus = strtoupper(trim((string)$controlRoom['status']));
    $scenarioStatus = strtoupper(trim((string)$scenarioBoard['status']));
    $testPackStatus = strtoupper(trim((string)$operatorTestPack['status']));

    foreach ([
        'WAVE 6A' => [$controlRoomStatus, $controlRoom],
        'WAVE 6B' => [$scenarioStatus, $scenarioBoard],
        'WAVE 6C' => [$testPackStatus, $operatorTestPack],
    ] as $waveLabel => [$status, $layer]) {
        if ($status === 'ERROR' || ($layer['ok'] ?? false) === false && in_array($status, ['ERROR'], true)) {
            $blockedItems[] = $waveLabel . ' — ' . (string)($layer['message'] ?? 'خطا');
        } elseif ($status === 'BLOCKED') {
            $blockedItems[] = $waveLabel . ' — ' . (string)($layer['message'] ?? 'مسدود');
        } elseif ($status === MOGHARE360_SOFT_RUN_STATUS_READY
            || $status === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY
            || $status === MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY
            || $status === MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY
        ) {
            $readyItems[] = $waveLabel . ' — ' . (string)($layer['message'] ?? $status);
        } elseif ($status === 'REVIEW_REQUIRED' || $status === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED
            || $status === MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED
        ) {
            $reviewItems[] = $waveLabel . ' — ' . (string)($layer['message'] ?? 'نیازمند بازبینی');
        } elseif ($status === 'EMPTY') {
            $missingItems[] = $waveLabel . ' — ' . (string)($layer['message'] ?? 'خالی');
        }
    }

    foreach ($pageStatus['pages'] as $pageRow) {
        if (!($pageRow['exists'] ?? false) && ($pageRow['critical'] ?? false)) {
            $blockedItems[] = 'صفحه بحرانی مفقود: ' . (string)($pageRow['path'] ?? '');
            $missingItems[] = (string)($pageRow['label_fa'] ?? '') . ' — ' . (string)($pageRow['path'] ?? '');
        } elseif ($pageRow['exists'] ?? false) {
            $readyItems[] = 'صفحه موجود: ' . (string)($pageRow['path'] ?? '');
        }
    }

    $anyBlockedLayer = in_array($controlRoomStatus, [MOGHARE360_SOFT_RUN_STATUS_BLOCKED, MOGHARE360_SOFT_RUN_STATUS_ERROR], true)
        || in_array($scenarioStatus, [MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED, MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR], true)
        || in_array($testPackStatus, [MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED, MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR], true);

    if ($anyBlockedLayer || $blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINAL_STATUS_BLOCKED,
            'control_room' => $controlRoom,
            'scenario_board' => $scenarioBoard,
            'operator_test_pack' => $operatorTestPack,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'message' => 'بستن نهایی Soft Run — مسدود (یک یا چند لایه WAVE 6 مسدود یا صفحه بحرانی مفقود).',
            'errors' => $errors,
        ];
    }

    $allEmpty = $controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_EMPTY
        && $scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY
        && $testPackStatus === MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY;

    if ($allEmpty || (int)($pageStatus['present'] ?? 0) === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINAL_STATUS_EMPTY,
            'control_room' => $controlRoom,
            'scenario_board' => $scenarioBoard,
            'operator_test_pack' => $operatorTestPack,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'message' => 'بستن نهایی Soft Run — خالی (لایه عملیاتی معنادار وجود ندارد).',
            'errors' => $errors,
        ];
    }

    $pilotReady = $controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_READY
        && $scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY
        && $testPackStatus === MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY
        && (int)($pageStatus['missing'] ?? 0) === 0;

    if ($pilotReady) {
        $readyItems[] = 'لایه بستن نهایی — بدون نیاز به نوشتن پایگاه داده';
        $readyItems[] = 'آمادگی پایلوت برای اجرای کنترل‌شده داخلی تأیید شد (خواندن فقط)';

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY,
            'control_room' => $controlRoom,
            'scenario_board' => $scenarioBoard,
            'operator_test_pack' => $operatorTestPack,
            'pages' => $pageStatus,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'signoff_notes' => $signoffNotes,
            'message' => 'بستن نهایی Soft Run — آماده اجرای کنترل‌شده پایلوت (WAVE 6A/6B/6C READY).',
            'errors' => $errors,
        ];
    }

    if ($reviewItems === []) {
        if ($controlRoomStatus === MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED) {
            $reviewItems[] = 'WAVE 6A — ' . (string)$controlRoom['message'];
        }
        if ($scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED) {
            $reviewItems[] = 'WAVE 6B — ' . (string)$scenarioBoard['message'];
        }
        if ($testPackStatus === MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED) {
            $reviewItems[] = 'WAVE 6C — ' . (string)$operatorTestPack['message'];
        }
    }

    $reviewItems[] = 'بازبینی مدیر/اپراتور برای تأیید نهایی پایلوت توصیه می‌شود';

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_FINAL_STATUS_REVIEW_REQUIRED,
        'control_room' => $controlRoom,
        'scenario_board' => $scenarioBoard,
        'operator_test_pack' => $operatorTestPack,
        'pages' => $pageStatus,
        'ready_items' => $readyItems,
        'review_items' => $reviewItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'signoff_notes' => $signoffNotes,
        'message' => 'بستن نهایی Soft Run — نیازمند بازبینی (یک یا چند جزء آمادگی کامل نیست).',
        'errors' => $errors,
    ];
}

function moghare360_soft_run_final_closure_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY => 'آماده اجرای کنترل‌شده پایلوت',
        MOGHARE360_SOFT_RUN_FINAL_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_SOFT_RUN_FINAL_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_FINAL_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_FINAL_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
