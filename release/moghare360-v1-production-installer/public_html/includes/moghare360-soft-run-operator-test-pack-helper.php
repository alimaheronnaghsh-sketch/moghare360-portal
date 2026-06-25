<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Operator Test Pack Helper (Wave 6C)
 *
 * Read-only operator test pack · no DB writes.
 * Internal Soft Run operator test planning — NOT final vehicle delivery.
 */

$scenarioPath = __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';
if (is_file($scenarioPath)) {
    require_once $scenarioPath;
}

const MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY = 'TEST_PACK_READY';
const MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED = 'REVIEW_REQUIRED';
const MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED = 'BLOCKED';
const MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY = 'EMPTY';
const MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR = 'ERROR';

/**
 * @return list<array{step: int, key: string, title: string, title_fa: string, related_page: string, manual: bool}>
 */
function moghare360_soft_run_operator_test_pack_steps(): array
{
    return [
        ['step' => 1, 'key' => 'open_control_room', 'title' => 'Open Soft Run Control Room', 'title_fa' => 'باز کردن اتاق کنترل Soft Run', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => false],
        ['step' => 2, 'key' => 'open_scenario_board', 'title' => 'Open Soft Run Scenario Board', 'title_fa' => 'باز کردن برد سناریوهای اجرای آزمایشی', 'related_page' => 'erp-soft-run-scenario-board.php', 'manual' => false],
        ['step' => 3, 'key' => 'open_workbench', 'title' => 'Open JobCard Command Workbench', 'title_fa' => 'باز کردن میز فرمان کارت کار', 'related_page' => 'erp-jobcard-command-workbench.php', 'manual' => false],
        ['step' => 4, 'key' => 'open_command_center', 'title' => 'Open JobCard Command Center for sample JobCard', 'title_fa' => 'باز کردن مرکز فرمان برای کارت کار نمونه', 'related_page' => 'erp-jobcard-command-center.php', 'manual' => false],
        ['step' => 5, 'key' => 'confirm_jobcard_context', 'title' => 'Confirm JobCard context is visible', 'title_fa' => 'تأیید نمایش زمینه کارت کار', 'related_page' => 'erp-jobcard-command-center.php', 'manual' => true],
        ['step' => 6, 'key' => 'confirm_evidence_status', 'title' => 'Confirm evidence status is visible', 'title_fa' => 'تأیید نمایش وضعیت مدارک', 'related_page' => 'erp-jobcard-command-center.php', 'manual' => true],
        ['step' => 7, 'key' => 'confirm_authorization_status', 'title' => 'Confirm authorization status is visible', 'title_fa' => 'تأیید نمایش وضعیت مجوز', 'related_page' => 'erp-jobcard-command-center.php', 'manual' => true],
        ['step' => 8, 'key' => 'confirm_final_readiness', 'title' => 'Confirm final readiness status is visible', 'title_fa' => 'تأیید نمایش وضعیت آمادگی نهایی', 'related_page' => 'erp-jobcard-final-readiness.php', 'manual' => true],
        ['step' => 9, 'key' => 'confirm_delivery_eligibility', 'title' => 'Confirm delivery eligibility status is visible', 'title_fa' => 'تأیید نمایش وضعیت صلاحیت تحویل', 'related_page' => 'erp-jobcard-delivery-eligibility.php', 'manual' => true],
        ['step' => 10, 'key' => 'confirm_delivery_clearance', 'title' => 'Confirm delivery clearance status is visible', 'title_fa' => 'تأیید نمایش وضعیت Clearance تحویل', 'related_page' => 'erp-jobcard-delivery-clearance-preview.php', 'manual' => true],
        ['step' => 11, 'key' => 'confirm_wave2_closure', 'title' => 'Confirm WAVE 2 closure dashboard is visible', 'title_fa' => 'تأیید نمایش داشبورد بستن WAVE 2', 'related_page' => 'erp-media-evidence-closure-dashboard.php', 'manual' => true],
        ['step' => 12, 'key' => 'confirm_wave3_closure', 'title' => 'Confirm WAVE 3 closure dashboard is visible', 'title_fa' => 'تأیید نمایش داشبورد بستن WAVE 3', 'related_page' => 'erp-authorization-closure-dashboard.php', 'manual' => true],
        ['step' => 13, 'key' => 'confirm_wave4_closure', 'title' => 'Confirm WAVE 4 closure dashboard is visible', 'title_fa' => 'تأیید نمایش داشبورد بستن WAVE 4', 'related_page' => 'erp-delivery-control-closure-dashboard.php', 'manual' => true],
        ['step' => 14, 'key' => 'confirm_wave5_closure', 'title' => 'Confirm WAVE 5 closure dashboard is visible', 'title_fa' => 'تأیید نمایش داشبورد بستن WAVE 5', 'related_page' => 'erp-unified-operational-closure-dashboard.php', 'manual' => true],
        ['step' => 15, 'key' => 'confirm_no_post_form', 'title' => 'Confirm no POST form exists in Soft Run pages', 'title_fa' => 'تأیید عدم وجود فرم POST در صفحات Soft Run', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => true],
        ['step' => 16, 'key' => 'confirm_no_final_delivery', 'title' => 'Confirm no final delivery action exists', 'title_fa' => 'تأیید عدم وجود اقدام تحویل نهایی', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => true],
        ['step' => 17, 'key' => 'confirm_no_delivery_completion', 'title' => 'Confirm no delivery completion action exists', 'title_fa' => 'تأیید عدم وجود اقدام تکمیل تحویل', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => true],
        ['step' => 18, 'key' => 'confirm_no_public_portal', 'title' => 'Confirm public portal is not activated', 'title_fa' => 'تأیید غیرفعال بودن پورتال عمومی', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => true],
        ['step' => 19, 'key' => 'confirm_no_payment', 'title' => 'Confirm payment/accounting is not activated', 'title_fa' => 'تأیید غیرفعال بودن پرداخت/حسابداری', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => true],
        ['step' => 20, 'key' => 'confirm_no_production_login', 'title' => 'Confirm production login is not activated', 'title_fa' => 'تأیید غیرفعال بودن ورود تولید', 'related_page' => 'erp-soft-run-control-room.php', 'manual' => true],
    ];
}

/**
 * @return list<array{key: string, title: string, title_fa: string, manual: bool}>
 */
function moghare360_soft_run_operator_test_pack_expected_evidence(): array
{
    return [
        ['key' => 'control_room_status', 'title' => 'Control Room status visible', 'title_fa' => 'وضعیت اتاق کنترل قابل مشاهده', 'manual' => true],
        ['key' => 'scenario_board_status', 'title' => 'Scenario Board status visible', 'title_fa' => 'وضعیت برد سناریو قابل مشاهده', 'manual' => true],
        ['key' => 'workbench_jobcard_list', 'title' => 'Workbench JobCard list visible', 'title_fa' => 'لیست کارت کار در میز فرمان قابل مشاهده', 'manual' => true],
        ['key' => 'jobcard_number_visible', 'title' => 'jobcard_number visible', 'title_fa' => 'شماره کارت کار (jobcard_number) قابل مشاهده', 'manual' => true],
        ['key' => 'command_center_unified_status', 'title' => 'Command Center unified status visible', 'title_fa' => 'وضعیت یکپارچه مرکز فرمان قابل مشاهده', 'manual' => true],
        ['key' => 'evidence_panel', 'title' => 'Evidence panel visible', 'title_fa' => 'پنل مدارک قابل مشاهده', 'manual' => true],
        ['key' => 'authorization_panel', 'title' => 'Authorization panel visible', 'title_fa' => 'پنل مجوز قابل مشاهده', 'manual' => true],
        ['key' => 'final_readiness_panel', 'title' => 'Final readiness panel visible', 'title_fa' => 'پنل آمادگی نهایی قابل مشاهده', 'manual' => true],
        ['key' => 'delivery_eligibility_panel', 'title' => 'Delivery eligibility panel visible', 'title_fa' => 'پنل صلاحیت تحویل قابل مشاهده', 'manual' => true],
        ['key' => 'delivery_clearance_panel', 'title' => 'Delivery clearance panel visible', 'title_fa' => 'پنل Clearance تحویل قابل مشاهده', 'manual' => true],
        ['key' => 'closure_dashboards_reachable', 'title' => 'Closure dashboards reachable', 'title_fa' => 'داشبوردهای بستن قابل دسترسی', 'manual' => false],
        ['key' => 'no_post_form', 'title' => 'No POST form', 'title_fa' => 'بدون فرم POST', 'manual' => true],
        ['key' => 'no_final_delivery', 'title' => 'No final delivery action', 'title_fa' => 'بدون اقدام تحویل نهایی', 'manual' => true],
        ['key' => 'no_delivery_completion', 'title' => 'No delivery completion', 'title_fa' => 'بدون تکمیل تحویل', 'manual' => true],
        ['key' => 'no_public_portal', 'title' => 'No public portal activation', 'title_fa' => 'بدون فعال‌سازی پورتال عمومی', 'manual' => true],
        ['key' => 'no_payment_accounting', 'title' => 'No payment/accounting activation', 'title_fa' => 'بدون فعال‌سازی پرداخت/حسابداری', 'manual' => true],
        ['key' => 'no_production_login', 'title' => 'No production login activation', 'title_fa' => 'بدون فعال‌سازی ورود تولید', 'manual' => true],
    ];
}

/**
 * @return list<array{path: string, label_fa: string, critical: bool}>
 */
function moghare360_soft_run_operator_test_pack_required_pages(): array
{
    return [
        ['path' => 'erp-soft-run-control-room.php', 'label_fa' => 'اتاق کنترل Soft Run', 'critical' => true],
        ['path' => 'erp-soft-run-scenario-board.php', 'label_fa' => 'برد سناریوهای اجرای آزمایشی', 'critical' => true],
        ['path' => 'erp-jobcard-command-workbench.php', 'label_fa' => 'میز فرمان کارت کار', 'critical' => true],
        ['path' => 'erp-jobcard-command-center.php', 'label_fa' => 'مرکز فرمان یکپارچه', 'critical' => true],
        ['path' => 'erp-unified-operational-closure-dashboard.php', 'label_fa' => 'بستن عملیاتی WAVE 5', 'critical' => true],
        ['path' => 'erp-delivery-control-closure-dashboard.php', 'label_fa' => 'بستن کنترل تحویل WAVE 4', 'critical' => true],
        ['path' => 'erp-authorization-closure-dashboard.php', 'label_fa' => 'بستن مجوز WAVE 3', 'critical' => true],
        ['path' => 'erp-media-evidence-closure-dashboard.php', 'label_fa' => 'بستن مدارک WAVE 2', 'critical' => true],
        ['path' => 'erp-jobcard-final-readiness.php', 'label_fa' => 'آمادگی نهایی', 'critical' => true],
        ['path' => 'erp-jobcard-delivery-eligibility.php', 'label_fa' => 'صلاحیت تحویل', 'critical' => true],
        ['path' => 'erp-jobcard-delivery-clearance-preview.php', 'label_fa' => 'پیش‌نمایش Clearance', 'critical' => true],
    ];
}

function moghare360_soft_run_operator_test_pack_public_root(): string
{
    return dirname(__DIR__);
}

/**
 * @return array{ok: bool, status: string, label: string, message: string, control_room_status: string, errors: list<string>, helper_available: bool}
 */
function moghare360_soft_run_operator_test_pack_fetch_scenario_status(): array
{
    if (!function_exists('moghare360_soft_run_scenario_evaluate')) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR,
            'label' => 'خطا',
            'message' => 'Helper برد سناریو در دسترس نیست.',
            'control_room_status' => '',
            'errors' => ['scenario_helper_missing'],
            'helper_available' => false,
        ];
    }

    $evaluation = moghare360_soft_run_scenario_evaluate();
    $controlRoom = (array)($evaluation['control_room'] ?? []);

    return [
        'ok' => (bool)($evaluation['ok'] ?? false),
        'status' => (string)($evaluation['status'] ?? MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR),
        'label' => function_exists('moghare360_soft_run_scenario_status_label')
            ? moghare360_soft_run_scenario_status_label((string)($evaluation['status'] ?? ''))
            : (string)($evaluation['status'] ?? ''),
        'message' => (string)($evaluation['message'] ?? ''),
        'control_room_status' => (string)($controlRoom['status'] ?? ''),
        'errors' => (array)($evaluation['errors'] ?? []),
        'helper_available' => true,
    ];
}

/**
 * @return array{ok: bool, total: int, present: int, missing: int, pages: list<array<string, mixed>>}
 */
function moghare360_soft_run_operator_test_pack_page_inventory(): array
{
    $publicRoot = moghare360_soft_run_operator_test_pack_public_root();
    $requiredPages = moghare360_soft_run_operator_test_pack_required_pages();
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
 * @param list<string> $softRunPagePaths
 */
function moghare360_soft_run_operator_test_pack_scan_soft_run_pages(array $softRunPagePaths): array
{
    $publicRoot = moghare360_soft_run_operator_test_pack_public_root();
    $hasPost = false;
    $hasFinalDelivery = false;
    $hasDeliveryCompletion = false;
    $hasPublicPortalClaim = false;
    $hasPaymentActive = false;
    $hasProductionLoginActive = false;

    foreach ($softRunPagePaths as $relativePath) {
        $fullPath = $publicRoot . DIRECTORY_SEPARATOR . $relativePath;
        if (!is_file($fullPath)) {
            continue;
        }
        $content = (string)file_get_contents($fullPath);
        if (preg_match('/method\s*=\s*["\']post["\']/i', $content)) {
            $hasPost = true;
        }
        if (preg_match('/submit-.*delivery/i', $content)) {
            $hasFinalDelivery = true;
        }
        if (preg_match('/vehicle_delivered|delivery_completion/i', $content)) {
            $hasDeliveryCompletion = true;
        }
        if (preg_match('/public\s+portal\s+active|پورتال\s+عمومی\s+فعال/i', $content)) {
            $hasPublicPortalClaim = true;
        }
        if (preg_match('/payment\s+gateway\s+active|حسابداری\s+رسمی\s+فعال/i', $content)) {
            $hasPaymentActive = true;
        }
        if (preg_match('/production\s+login\s+active|ورود\s+تولید\s+فعال/i', $content)) {
            $hasProductionLoginActive = true;
        }
    }

    return [
        'no_post_form' => !$hasPost,
        'no_final_delivery' => !$hasFinalDelivery,
        'no_delivery_completion' => !$hasDeliveryCompletion,
        'no_public_portal' => !$hasPublicPortalClaim,
        'no_payment_accounting' => !$hasPaymentActive,
        'no_production_login' => !$hasProductionLoginActive,
    ];
}

/**
 * @return array{
 *   ok: bool,
 *   status: string,
 *   scenario_board: array<string, mixed>,
 *   steps: list<array<string, mixed>>,
 *   expected_evidence: list<array<string, mixed>>,
 *   pages: array<string, mixed>,
 *   ready_items: list<string>,
 *   review_items: list<string>,
 *   blocked_items: list<string>,
 *   missing_items: list<string>,
 *   message: string,
 *   errors: list<string>
 * }
 */
function moghare360_soft_run_operator_test_pack_evaluate(): array
{
    $steps = moghare360_soft_run_operator_test_pack_steps();
    $expectedEvidence = moghare360_soft_run_operator_test_pack_expected_evidence();
    $scenarioBoard = moghare360_soft_run_operator_test_pack_fetch_scenario_status();
    $pageInventory = moghare360_soft_run_operator_test_pack_page_inventory();
    $publicRoot = moghare360_soft_run_operator_test_pack_public_root();

    $readyItems = [];
    $reviewItems = [];
    $blockedItems = [];
    $missingItems = [];
    $errors = [];

    if ($steps === [] || $expectedEvidence === []) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY,
            'scenario_board' => $scenarioBoard,
            'steps' => [],
            'expected_evidence' => [],
            'pages' => $pageInventory,
            'ready_items' => [],
            'review_items' => [],
            'blocked_items' => [],
            'missing_items' => ['فهرست گام‌های تست یا شواهد مورد انتظار تعریف نشده است'],
            'message' => 'بسته تست اپراتوری — خالی (تعریف گام‌ها یا شواهد موجود نیست).',
            'errors' => ['empty_test_pack'],
        ];
    }

    if (!$scenarioBoard['helper_available']) {
        return [
            'ok' => false,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR,
            'scenario_board' => $scenarioBoard,
            'steps' => $steps,
            'expected_evidence' => $expectedEvidence,
            'pages' => $pageInventory,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => ['Helper برد سناریو WAVE 6B موجود نیست'],
            'missing_items' => $missingItems,
            'message' => 'بسته تست اپراتوری — خطا (وابستگی WAVE 6B نامعتبر).',
            'errors' => ['scenario_helper_missing'],
        ];
    }

    $enrichedSteps = [];
    foreach ($steps as $step) {
        $relatedPage = (string)($step['related_page'] ?? '');
        $pageExists = $relatedPage !== '' && is_file($publicRoot . DIRECTORY_SEPARATOR . $relatedPage);
        $isManual = (bool)($step['manual'] ?? false);

        $stepStatus = 'READY';
        if (!$pageExists) {
            $stepStatus = 'MISSING';
            $missingItems[] = 'گام ' . (string)($step['step'] ?? '') . ' — صفحه ' . $relatedPage . ' یافت نشد';
        } elseif ($isManual) {
            $stepStatus = 'MANUAL';
            $reviewItems[] = 'گام ' . (string)($step['step'] ?? '') . ' — ' . (string)($step['title_fa'] ?? '') . ' (تأیید دستی اپراتور)';
        } else {
            $readyItems[] = 'گام ' . (string)($step['step'] ?? '') . ' — ' . (string)($step['title_fa'] ?? '') . ' (صفحه موجود)';
        }

        $enrichedSteps[] = array_merge($step, [
            'page_exists' => $pageExists,
            'status' => $stepStatus,
        ]);
    }

    $enrichedEvidence = [];
    foreach ($expectedEvidence as $evidence) {
        $isManual = (bool)($evidence['manual'] ?? true);
        $evidenceStatus = $isManual ? 'MANUAL' : 'AUTO';

        if ($isManual) {
            $reviewItems[] = 'شاهد: ' . (string)($evidence['title_fa'] ?? '') . ' (تأیید دستی اپراتور)';
        } else {
            $readyItems[] = 'شاهد: ' . (string)($evidence['title_fa'] ?? '') . ' (بررسی خودکار صفحات بستن)';
        }

        $enrichedEvidence[] = array_merge($evidence, ['status' => $evidenceStatus]);
    }

    $scenarioStatus = strtoupper(trim((string)$scenarioBoard['status']));

    if ($scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED || $scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_ERROR) {
        $blockedItems[] = 'برد سناریو WAVE 6B — ' . (string)$scenarioBoard['message'];

        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED,
            'scenario_board' => $scenarioBoard,
            'steps' => $enrichedSteps,
            'expected_evidence' => $enrichedEvidence,
            'pages' => $pageInventory,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'بسته تست اپراتوری — مسدود (وضعیت برد سناریو BLOCKED/ERROR).',
            'errors' => $errors,
        ];
    }

    foreach ($pageInventory['pages'] as $pageRow) {
        if (!($pageRow['exists'] ?? false) && ($pageRow['critical'] ?? false)) {
            $blockedItems[] = 'صفحه بحرانی مفقود: ' . (string)($pageRow['path'] ?? '');
            $missingItems[] = (string)($pageRow['label_fa'] ?? '') . ' — ' . (string)($pageRow['path'] ?? '');
        }
    }

    if ($blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED,
            'scenario_board' => $scenarioBoard,
            'steps' => $enrichedSteps,
            'expected_evidence' => $enrichedEvidence,
            'pages' => $pageInventory,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'بسته تست اپراتوری — مسدود (صفحه زمان اجرای بحرانی مفقود).',
            'errors' => $errors,
        ];
    }

    if ((int)($pageInventory['present'] ?? 0) === 0) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY,
            'scenario_board' => $scenarioBoard,
            'steps' => $enrichedSteps,
            'expected_evidence' => $enrichedEvidence,
            'pages' => $pageInventory,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'بسته تست اپراتوری — خالی (صفحه زمان اجرا یافت نشد).',
            'errors' => $errors,
        ];
    }

    $softRunPages = [
        'erp-soft-run-control-room.php',
        'erp-soft-run-scenario-board.php',
        'erp-soft-run-operator-test-pack.php',
    ];
    $scan = moghare360_soft_run_operator_test_pack_scan_soft_run_pages($softRunPages);

    if ($scan['no_post_form']) {
        $readyItems[] = 'بررسی خودکار: بدون فرم POST در صفحات Soft Run';
    } else {
        $blockedItems[] = 'فرم POST در صفحات Soft Run یافت شد';
    }
    if ($scan['no_final_delivery']) {
        $readyItems[] = 'بررسی خودکار: بدون اقدام تحویل نهایی';
    } else {
        $blockedItems[] = 'اقدام تحویل نهایی در صفحات Soft Run یافت شد';
    }
    if ($scan['no_delivery_completion']) {
        $readyItems[] = 'بررسی خودکار: بدون تکمیل تحویل';
    } else {
        $blockedItems[] = 'اقدام تکمیل تحویل در صفحات Soft Run یافت شد';
    }

    if ($blockedItems !== []) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED,
            'scenario_board' => $scenarioBoard,
            'steps' => $enrichedSteps,
            'expected_evidence' => $enrichedEvidence,
            'pages' => $pageInventory,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'بسته تست اپراتوری — مسدود (بررسی خودکار مرز محصول ناموفق).',
            'errors' => $errors,
        ];
    }

    $allStepsPagesOk = true;
    foreach ($enrichedSteps as $step) {
        if (($step['status'] ?? '') === 'MISSING') {
            $allStepsPagesOk = false;
            break;
        }
    }

    $pilotReady = $scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY;
    $allPagesPresent = (int)($pageInventory['missing'] ?? 0) === 0;

    if ($pilotReady && $allPagesPresent && $allStepsPagesOk) {
        return [
            'ok' => true,
            'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY,
            'scenario_board' => $scenarioBoard,
            'steps' => $enrichedSteps,
            'expected_evidence' => $enrichedEvidence,
            'pages' => $pageInventory,
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'blocked_items' => $blockedItems,
            'missing_items' => $missingItems,
            'message' => 'بسته تست اپراتوری — آماده (برد سناریو PILOT_READY، صفحات و گام‌ها موجود؛ شواهد دستی برای اپراتور).',
            'errors' => $errors,
        ];
    }

    if ($scenarioStatus === MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED) {
        $reviewItems[] = 'برد سناریو WAVE 6B — ' . (string)$scenarioBoard['message'];
    }

    if (!$allStepsPagesOk) {
        $reviewItems[] = 'برخی گام‌های تست صفحه مرتبط ندارند — بازبینی اپراتور';
    }

    return [
        'ok' => true,
        'status' => MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED,
        'scenario_board' => $scenarioBoard,
        'steps' => $enrichedSteps,
        'expected_evidence' => $enrichedEvidence,
        'pages' => $pageInventory,
        'ready_items' => $readyItems,
        'review_items' => $reviewItems,
        'blocked_items' => $blockedItems,
        'missing_items' => $missingItems,
        'message' => 'بسته تست اپراتوری — نیازمند بازبینی (تأیید دستی شواهد توسط اپراتور).',
        'errors' => $errors,
    ];
}

function moghare360_soft_run_operator_test_pack_status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY => 'آماده بسته تست',
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED => 'نیازمند بازبینی',
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED => 'مسدود',
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY => 'خالی',
        MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_ERROR => 'خطا',
        default => 'نامشخص',
    };
}
