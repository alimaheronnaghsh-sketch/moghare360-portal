<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-work-execution-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-staff-home-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-matrix-guard.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-access-enforcement.php';

m360_work_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
m360_ws_require_any(
    ['workshop.work_report.create', 'workshop.diagnosis_report.create', 'workshop.mechanical.view', 'workshop.electrical_options.view'],
    $jobcardId > 0 ? $jobcardId : null
);
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && $_GET['ok'] === '1';
$unitRaw = strtoupper(trim((string)($_GET['unit'] ?? $_GET['team_code'] ?? '')));
$unitContext = m360_fulljob_is_valid_team_code($unitRaw) ? $unitRaw : '';

$conn = customer_core_db();
$jc = null;
$est = null;
$items = [];
$approvedParts = [];
$consumedParts = [];
$serviceOps = [];
$history = [];
$events = [];
$gatesOk = false;
$gateMessage = '';
$allowed = [];
$workStatus = '';
$roleCode = 'UNKNOWN';
$activeTeams = [];
$wxTimeline = ['events' => [], 'missing' => []];

erp_auth_context_start();
if ($conn !== false) {
    $roleCode = m360_staff_home_resolve_role_code(
        $conn,
        (int)(erp_auth_current_user_id() ?? 0),
        (int)($_SESSION['erp_company_id'] ?? 1)
    );
}

if ($conn !== false && $jobcardId > 0) {
    $jc = m360_work_fetch_jobcard($conn, $jobcardId);
    if ($jc !== null) {
        $gate = m360_work_assert_gates($conn, $jobcardId, $jc);
        $gatesOk = $gate['ok'];
        $gateMessage = $gate['message'];
        $workStatus = m360_work_effective_status($jc);
        $allowed = m360_work_allowed_actions($jc, $gatesOk);
        $activeTeams = m360_fulljob_active_team_assignments($conn, $jobcardId);

        // Server-side QC visibility: never render for unauthorized roles / wrong state.
        if (!m360_fulljob_can_render_ready_for_qc($roleCode, $jc)) {
            $allowed = array_values(array_filter(
                $allowed,
                static fn(string $a): bool => $a !== 'ready_for_qc'
            ));
        }

        $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
        if ($est !== null) {
            $items = m360_estimate_list_items($conn, (int)$est['estimate_id']);
        }
        $approvedParts = m360_parts_approved_items($conn, $jobcardId);
        $consumedParts = m360_parts_list_consumed($conn, $jobcardId);
        $serviceOps = m360_technical_list_service_operations($conn, $jobcardId);
        $history = m360_work_list_history($conn, $jobcardId);
        $events = m360_work_list_events($conn, $jobcardId);
        $wxTimeline = m360_fulljob_build_jobcard_timeline($conn, $jobcardId);
    }
}

/**
 * If unit context missing and exactly one active team, use it for display/completion.
 * If multiple and no context, completion requires explicit select.
 */
$resolvedUnit = $unitContext;
if ($resolvedUnit === '' && count($activeTeams) === 1) {
    $candidate = strtoupper(trim((string)($activeTeams[0]['team_code'] ?? '')));
    if (m360_fulljob_is_valid_team_code($candidate)) {
        $resolvedUnit = $candidate;
    }
}

$completionBlockedMulti = ($unitContext === '' && count($activeTeams) > 1);

function m360_wx_form(string $action, int $jobcardId, string $label, string $class = '', string $teamCode = ''): void
{
    echo '<form method="post" action="erp-work-execution-action.php" class="m360-wx-inline-form">';
    echo erp_csrf_input(M360_WORK_CSRF);
    echo '<input type="hidden" name="jobcard_id" value="' . (int)$jobcardId . '">';
    echo '<input type="hidden" name="action" value="' . m360_work_h($action) . '">';
    if ($teamCode !== '' && m360_fulljob_is_valid_team_code($teamCode)) {
        echo '<input type="hidden" name="team_code" value="' . m360_work_h($teamCode) . '">';
    }
    echo '<button type="submit" class="m360-wx-btn ' . m360_work_h($class) . '">' . m360_work_h($label) . '</button>';
    echo '</form>';
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات اجرای کار | ماهین 360°</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-work-execution.css">
    <?php m360_operational_shell_render_stylesheets(); ?>
</head>
<body class="m360-wx-page">
<div class="w1c-wrap m360-wx-wrap">
    <?php
    $opsStrip = null;
    if ($jc !== null) {
        $opsStrip = m360_operational_shell_build_jobcard_strip($conn, $jc, 'work', $workStatus, $allowed, $gateMessage);
    }
    $boardBack = $unitContext !== ''
        ? ('erp-unit-work-board.php?unit=' . rawurlencode($unitContext))
        : 'erp-work-execution-board.php';
    m360_operational_shell_render_detail('work_detail', $boardBack, $jobcardId, $opsStrip);
    ?>
    <?php if ($flash !== ''): ?><div class="m360-wx-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_work_h($flash) ?></div><?php endif; ?>

    <?php if ($jc === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <h2>پرونده کار <?= m360_work_h(m360_fulljob_to_persian_digits((string)($jc['jobcard_number'] ?? $jobcardId))) ?></h2>
            <div class="m360-wx-grid">
                <div>
                    <p class="m360-wx-kv"><strong>مشتری:</strong> <?= m360_work_h((string)($jc['customer_name'] ?? '-')) ?></p>
                    <p class="m360-wx-kv"><strong>موبایل:</strong> <?= m360_work_h(m360_fulljob_to_persian_digits((string)($jc['customer_mobile'] ?? '-'))) ?></p>
                    <p class="m360-wx-kv"><strong>خودرو:</strong> <?= m360_work_h(trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')) ?: '-') ?></p>
                    <p class="m360-wx-kv"><strong>پلاک:</strong> <?= m360_work_h((string)($jc['plate_number'] ?? '-')) ?></p>
                </div>
                <div>
                    <p class="m360-wx-kv"><strong>وضعیت اجرا:</strong> <span class="m360-wx-badge"><?= m360_work_h(m360_work_status_label($workStatus)) ?></span></p>
                    <p class="m360-wx-kv"><strong>وضعیت فنی:</strong> <?= m360_work_h(m360_fulljob_status_label_fa((string)($jc['technical_status'] ?? ''))) ?></p>
                    <p class="m360-wx-kv"><strong>برآورد:</strong> <?= m360_work_h(m360_estimate_status_label((string)($jc['estimate_status'] ?? ''))) ?></p>
                    <p class="m360-wx-kv"><strong>واحد انجام‌دهنده:</strong>
                        <?php if ($resolvedUnit !== ''): ?>
                            <?= m360_work_h(m360_fulljob_team_label_fa($resolvedUnit)) ?>
                        <?php else: ?>
                            <span class="m360-wx-badge">نامشخص</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <p class="m360-wx-kv"><strong>عیب‌یابی:</strong> <?= m360_work_h((string)($jc['diagnosis_summary'] ?? '-')) ?></p>
            <?php if (!$gatesOk): ?>
                <div class="m360-wx-alert"><?= m360_work_h($gateMessage !== '' ? $gateMessage : 'گیت‌های P4/P1.5 باز نشده‌اند.') ?></div>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h3>زمان‌های عملیاتی واحد</h3>
            <?php
            $teamReferral = '';
            foreach ($activeTeams as $t) {
                if ($resolvedUnit !== '' && strtoupper(trim((string)($t['team_code'] ?? ''))) === $resolvedUnit) {
                    $teamReferral = trim((string)($t['created_at'] ?? ''));
                    break;
                }
            }
            if ($teamReferral === '' && $activeTeams !== []) {
                $teamReferral = trim((string)($activeTeams[0]['created_at'] ?? ''));
            }
            $techAssign = '';
            $techRows = is_resource($conn) ? m360_fulljob_active_technician_assignments($conn, $jobcardId) : [];
            if ($techRows !== []) {
                $techAssign = trim((string)($techRows[0]['created_at'] ?? ''));
            }
            ?>
            <div class="m360-wx-grid">
                <p class="m360-wx-kv"><strong>ارجاع به واحد:</strong> <?= m360_work_h(m360_fulljob_display_jalali_datetime($teamReferral !== '' ? $teamReferral : null)) ?></p>
                <p class="m360-wx-kv"><strong>تخصیص تکنسین:</strong> <?= m360_work_h(m360_fulljob_display_jalali_datetime($techAssign !== '' ? $techAssign : null)) ?></p>
                <p class="m360-wx-kv"><strong>شروع کار:</strong> <?= m360_work_h(m360_fulljob_display_jalali_datetime((string)($jc['work_started_at'] ?? ''))) ?></p>
                <p class="m360-wx-kv"><strong>اتمام واحد:</strong> <?= m360_work_h(m360_fulljob_display_jalali_datetime((string)($jc['work_completed_at'] ?? ''))) ?></p>
                <p class="m360-wx-kv"><strong>ارسال به کنترل کیفیت:</strong> <?= m360_work_h(m360_fulljob_display_jalali_datetime((string)($jc['ready_for_qc_at'] ?? ''))) ?></p>
            </div>
            <?php
            $pauseShown = false;
            $returnShown = false;
            foreach (($wxTimeline['events'] ?? []) as $ev) {
                $title = (string)($ev['title'] ?? '');
                if (!$pauseShown && $title === 'توقف کار') {
                    echo '<p class="m360-wx-kv"><strong>توقف:</strong> ' . m360_work_h(m360_fulljob_display_jalali_datetime((string)$ev['at'])) . '</p>';
                    $pauseShown = true;
                }
                if (!$returnShown && $title === 'بازگشت به مدیر سالن') {
                    echo '<p class="m360-wx-kv"><strong>بازگشت به مدیر سالن:</strong> ' . m360_work_h(m360_fulljob_display_jalali_datetime((string)$ev['at'])) . '</p>';
                    $returnShown = true;
                }
            }
            if (!$pauseShown) {
                echo '<p class="m360-wx-kv"><strong>توقف:</strong> ثبت نشده</p>';
            }
            echo '<p class="m360-wx-kv"><strong>ادامه:</strong> ثبت نشده</p>';
            if (!$returnShown) {
                echo '<p class="m360-wx-kv"><strong>بازگشت به مدیر سالن:</strong> ثبت نشده</p>';
            }
            ?>
        </section>

        <?php if ($est !== null): ?>
        <section class="w1c-card">
            <h3>برآورد تأیید‌شده</h3>
            <p class="m360-wx-kv">مبلغ کل: <?= m360_work_h(number_format((float)($est['total_amount'] ?? 0))) ?> تومان |
               علی‌الحساب: <?= m360_work_h(number_format((float)($est['advance_required_amount'] ?? 0))) ?></p>
        </section>
        <?php endif; ?>

        <section class="w1c-card m360-wx-actions">
            <h3>عملیات</h3>
            <?php if (in_array('move_to_work_queue', $allowed, true)): m360_wx_form('move_to_work_queue', $jobcardId, 'انتقال به صف', 'secondary', $resolvedUnit); endif; ?>
            <?php if (in_array('start_work', $allowed, true)): m360_wx_form('start_work', $jobcardId, 'شروع کار', '', $resolvedUnit); endif; ?>
            <?php if (in_array('waiting_for_parts', $allowed, true)): m360_wx_form('waiting_for_parts', $jobcardId, 'انتظار قطعه', 'secondary', $resolvedUnit); endif; ?>

            <?php if (in_array('complete_technical_work', $allowed, true)): ?>
                <?php if ($completionBlockedMulti): ?>
                    <form method="post" action="erp-work-execution-action.php" class="m360-wx-inline-form" style="display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;">
                        <?= erp_csrf_input(M360_WORK_CSRF) ?>
                        <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                        <input type="hidden" name="action" value="complete_technical_work">
                        <label for="team_code_complete"><strong>واحد انجام‌دهنده:</strong></label>
                        <select id="team_code_complete" name="team_code" required>
                            <option value="">انتخاب واحد</option>
                            <?php foreach ($activeTeams as $team): ?>
                                <?php $code = strtoupper(trim((string)($team['team_code'] ?? ''))); ?>
                                <?php if (!m360_fulljob_is_valid_team_code($code)) { continue; } ?>
                                <option value="<?= m360_work_h($code) ?>"><?= m360_work_h(m360_fulljob_team_label_fa($code)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="m360-wx-btn">اتمام کار واحد و ارسال برای بررسی مدیر سالن</button>
                    </form>
                <?php elseif ($resolvedUnit !== ''): ?>
                    <?php m360_wx_form('complete_technical_work', $jobcardId, 'اتمام کار واحد و ارسال برای بررسی مدیر سالن', '', $resolvedUnit); ?>
                <?php else: ?>
                    <p class="m360-wx-alert">تخصیص فعال و معتبری برای تکمیل این کار یافت نشد.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (in_array('ready_for_qc', $allowed, true)): m360_wx_form('ready_for_qc', $jobcardId, 'ارسال به کنترل کیفیت'); endif; ?>
            <?php if (in_array('hold', $allowed, true)): m360_wx_form('hold', $jobcardId, 'معلق', 'secondary', $resolvedUnit); endif; ?>
            <?php if (in_array('cancel', $allowed, true)): m360_wx_form('cancel', $jobcardId, 'لغو اجرا', 'danger', $resolvedUnit); endif; ?>
        </section>

        <section class="w1c-card">
            <h3>یادداشت تکمیل فنی</h3>
            <form method="post" action="erp-work-execution-action.php">
                <?= erp_csrf_input(M360_WORK_CSRF) ?>
                <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                <input type="hidden" name="action" value="save_completion_notes">
                <?php if ($resolvedUnit !== ''): ?>
                    <input type="hidden" name="team_code" value="<?= m360_work_h($resolvedUnit) ?>">
                <?php endif; ?>
                <textarea name="technical_completion_notes" class="m360-wx-textarea" placeholder="نتیجه کار و توضیحات تکنسین"><?= m360_work_h((string)($jc['technical_completion_notes'] ?? '')) ?></textarea>
                <button type="submit" class="m360-wx-btn secondary" style="margin-top:0.5rem;">ذخیره یادداشت</button>
            </form>
        </section>

        <section class="w1c-card">
            <h3>عملیات سرویس</h3>
            <?php if ($serviceOps === []): ?>
                <p class="m360-wx-kv">عملیات سرویس ثبت نشده — در صورت عدم نیاز، تکمیل واحد مجاز است.</p>
            <?php else: ?>
                <table class="m360-wx-table">
                    <thead><tr><th>شناسه</th><th>عنوان</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                    <tbody>
                    <?php foreach ($serviceOps as $so): ?>
                        <tr>
                            <td><?= m360_work_h(m360_fulljob_to_persian_digits((string)($so['service_operation_id'] ?? ''))) ?></td>
                            <td><?= m360_work_h((string)($so['operation_title'] ?? $so['service_title'] ?? '-')) ?></td>
                            <td><?= m360_work_h(m360_fulljob_status_label_fa((string)($so['service_status'] ?? '-'))) ?></td>
                            <td>
                                <?php if (in_array('start_service_operation', $allowed, true) && strtoupper((string)($so['service_status'] ?? '')) === M360_SO_STATUS_CREATED): ?>
                                <form method="post" action="erp-work-execution-action.php" class="m360-wx-inline-form">
                                    <?= erp_csrf_input(M360_WORK_CSRF) ?>
                                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                                    <input type="hidden" name="action" value="start_service_operation">
                                    <input type="hidden" name="service_operation_id" value="<?= (int)($so['service_operation_id'] ?? 0) ?>">
                                    <?php if ($resolvedUnit !== ''): ?><input type="hidden" name="team_code" value="<?= m360_work_h($resolvedUnit) ?>"><?php endif; ?>
                                    <button type="submit" class="m360-wx-btn secondary">شروع</button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array('complete_service_operation', $allowed, true) && strtoupper((string)($so['service_status'] ?? '')) === M360_SO_STATUS_STARTED): ?>
                                <form method="post" action="erp-work-execution-action.php" class="m360-wx-inline-form">
                                    <?= erp_csrf_input(M360_WORK_CSRF) ?>
                                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                                    <input type="hidden" name="action" value="complete_service_operation">
                                    <input type="hidden" name="service_operation_id" value="<?= (int)($so['service_operation_id'] ?? 0) ?>">
                                    <?php if ($resolvedUnit !== ''): ?><input type="hidden" name="team_code" value="<?= m360_work_h($resolvedUnit) ?>"><?php endif; ?>
                                    <input type="text" name="operation_result_note" class="m360-wx-input" placeholder="نتیجه عملیات" required style="max-width:180px;display:inline-block;">
                                    <button type="submit" class="m360-wx-btn">تکمیل</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h3>قطعات تأیید‌شده برای مصرف</h3>
            <?php if ($approvedParts === []): ?>
                <p class="m360-wx-kv">قطعه‌ای در برآورد نیست.</p>
            <?php else: ?>
                <table class="m360-wx-table">
                    <thead><tr><th>عنوان</th><th>تعداد</th><th>مصرف‌شده</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($approvedParts as $p):
                        $partId = m360_parts_resolve_part_id($conn, $p);
                        $consumed = m360_parts_consumed_qty($conn, $jobcardId, $partId, (int)($p['estimate_item_id'] ?? 0));
                        $stock = m360_parts_stock_available($conn, $partId);
                    ?>
                        <tr>
                            <td><?= m360_work_h((string)($p['item_title'] ?? '-')) ?></td>
                            <td><?= m360_work_h((string)($p['quantity'] ?? '1')) ?></td>
                            <td><?= m360_work_h((string)$consumed) ?><?= $stock !== null ? ' / موجودی: ' . m360_work_h((string)$stock) : '' ?></td>
                            <td>
                                <?php if (in_array('consume_approved_part', $allowed, true) && $consumed < (float)($p['quantity'] ?? 1)): ?>
                                <form method="post" action="erp-work-execution-action.php" class="m360-wx-inline-form">
                                    <?= erp_csrf_input(M360_WORK_CSRF) ?>
                                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                                    <input type="hidden" name="action" value="consume_approved_part">
                                    <input type="hidden" name="estimate_item_id" value="<?= (int)($p['estimate_item_id'] ?? 0) ?>">
                                    <input type="hidden" name="quantity" value="<?= m360_work_h((string)($p['quantity'] ?? '1')) ?>">
                                    <?php if ($resolvedUnit !== ''): ?><input type="hidden" name="team_code" value="<?= m360_work_h($resolvedUnit) ?>"><?php endif; ?>
                                    <button type="submit" class="m360-wx-btn secondary">مصرف</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if ($consumedParts !== []): ?>
        <section class="w1c-card">
            <h3>قطعات مصرف‌شده</h3>
            <table class="m360-wx-table">
                <thead><tr><th>قطعه</th><th>تعداد</th><th>وضعیت</th><th>زمان</th></tr></thead>
                <tbody>
                <?php foreach ($consumedParts as $c): ?>
                    <tr>
                        <td><?= m360_work_h(m360_fulljob_to_persian_digits((string)($c['part_id'] ?? ''))) ?></td>
                        <td><?= m360_work_h(m360_fulljob_to_persian_digits((string)($c['quantity'] ?? ''))) ?></td>
                        <td><?= m360_work_h(m360_fulljob_status_label_fa((string)($c['usage_status'] ?? ''))) ?></td>
                        <td><?= m360_work_h(m360_fulljob_display_jalali_datetime((string)($c['created_at'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>

        <section class="w1c-card">
            <h3>تاریخچه / رویدادها</h3>
            <?php if ($history === [] && $events === []): ?>
                <p class="m360-wx-kv">هنوز رویدادی ثبت نشده.</p>
            <?php else: ?>
                <table class="m360-wx-table">
                    <thead><tr><th>نوع</th><th>قبل</th><th>بعد</th><th>خلاصه</th><th>زمان</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= m360_work_h(m360_fulljob_work_event_label_fa((string)($h['change_type'] ?? ''))) ?></td>
                            <td><?= m360_work_h(m360_fulljob_status_label_fa((string)($h['previous_status'] ?? ''))) ?></td>
                            <td><?= m360_work_h(m360_fulljob_status_label_fa((string)($h['new_status'] ?? ''))) ?></td>
                            <td><?= m360_work_h((string)($h['change_summary'] ?? '')) ?></td>
                            <td><?= m360_work_h(m360_fulljob_display_jalali_datetime((string)($h['changed_at'] ?? ''))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($events as $e): ?>
                        <tr>
                            <td><?= m360_work_h(m360_fulljob_work_event_label_fa((string)($e['event_name'] ?? ''))) ?></td>
                            <td colspan="3"><?= m360_work_h((string)($e['event_note'] ?? '')) ?></td>
                            <td><?= m360_work_h(m360_fulljob_display_jalali_datetime((string)($e['created_at'] ?? ''))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>

