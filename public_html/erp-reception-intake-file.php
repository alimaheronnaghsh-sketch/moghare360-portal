<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.9-C-2B/C-2C — Reception intake completion (read GET + controlled POST forms).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-tree-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-header.php';

m360_reception_require_staff();

$onlineRequestId = isset($_GET['online_request_id']) ? (int)$_GET['online_request_id'] : 0;
if ($onlineRequestId < 1 && isset($_GET['request_id'])) {
    $onlineRequestId = (int)$_GET['request_id'];
}

$csrfTokenHtml = m360_reception_csrf_input_html();
$csrfInputHtml = $csrfTokenHtml;
$m360RuntimeBuild = 'P1-EMERGENCY-' . gmdate('Ymd-His');

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$conn = customer_core_db();
$file = ($conn !== false && $onlineRequestId > 0)
    ? m360_rw_build_intake_file($conn, $onlineRequestId)
    : m360_rw_build_intake_file(false, 0);

$request = $file['request'];
$gate = $file['gate'];
$serviceClass = $file['service_classification'] ?? m360_rw_parse_service_classification($file['payload'] ?? [], $request ?? []);
$payloadRows = $file['payload_rows'];
$payloadMeta = $file['payload_meta'];
$canAct = $request !== null
    && !m360_online_req_is_converted($request)
    && strtoupper((string)($request['request_status'] ?? '')) !== M360_ONLINE_REQ_STATUS_REJECTED;
$canShowTempActions = !empty($gate['can_show_temp_actions']) && $canAct;
$jobcardId = (int)($gate['converted_jobcard_id'] ?? 0);
$jobcard = is_array($file['jobcard'] ?? null) ? $file['jobcard'] : null;
$m360StageTree = m360_case_stage_tree_resolve($conn, [
    'online_request_id' => $onlineRequestId,
    'jobcard_id' => $jobcardId,
]);
$vehicleId = (int)($request['vehicle_id'] ?? 0);
$gateClass = m360_rw_gate_status_chip_class((string)($gate['status'] ?? 'needs_completion'));
$fieldRecovery = $file['field_recovery'] ?? ($gate['field_recovery'] ?? []);
$customerRequestType = trim((string)($request['request_type'] ?? m360_rw_pick([$file['payload'] ?? []], 'request_type')));
$formValues = ($request !== null) ? m360_rw_intake_form_values($file['payload'] ?? [], $request) : [];
$vehicleDossier = ($request !== null)
    ? m360_rw_intake_resolve_vehicle_dossier_fields($file['payload'] ?? [], $request, $file['vehicle'] ?? null)
    : [];
if ($formValues !== [] && $vehicleDossier !== []) {
    foreach (['plate', 'vin', 'brand', 'model', 'mileage', 'vehicle_class', 'vehicle_type', 'fuel_level'] as $vf) {
        if (trim((string)($formValues[$vf] ?? '')) === '' && trim((string)($vehicleDossier[$vf]['value'] ?? '')) !== '') {
            $formValues[$vf] = (string)$vehicleDossier[$vf]['value'];
        }
    }
}
if ($formValues !== [] && trim((string)($formValues['model'] ?? '')) === '' && trim((string)($formValues['vehicle_class'] ?? '')) !== '') {
    $formValues['model'] = (string)$formValues['vehicle_class'];
}
$diagSubCodes = is_array($formValues['service_diag_sub_codes'] ?? null) ? $formValues['service_diag_sub_codes'] : [];
$flashMsg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';
$photoSavedSlot = preg_replace('/[^a-z_]/', '', strtolower(trim((string)($_GET['photo_saved'] ?? '')))) ?? '';
if ($photoSavedSlot !== '' && $flashOk) {
    $flashMsg = 'عکس ذخیره شد.';
}
$saveUrl = 'erp-reception-intake-save.php';
$editSection = trim((string)($_GET['edit_section'] ?? ''));
$payloadData = $file['payload'] ?? [];
$otpSend = m360_rw_intake_otp_send_available();
$otpSent = isset($_GET['otp_sent']) && (string)$_GET['otp_sent'] === '1';
$otpStatusUi = m360_rw_intake_otp_ui_status($payloadData, $request, $otpSent, $flashOk, $flashMsg);
$otpVerified = $otpStatusUi['verified'];
$otpAccessBlocked = $request !== null && !m360_rw_intake_reception_otp_verified($request);
$activeStep = ($request !== null && !$otpAccessBlocked)
    ? m360_rw_intake_resolve_active_step($_GET, $request, $payloadData, $formValues)
    : 'otp';
$otpMobile = ($request !== null) ? m360_rw_intake_resolve_mobile_for_otp($request, $payloadData) : '';
$isLocked = m360_rw_intake_is_locked($payloadData);
$wizardEditMode = isset($_GET['wizard_edit']) && (string)$_GET['wizard_edit'] === '1';
$prevAmendStep = ($request !== null)
    ? m360_rw_intake_wizard_prev_amendable_step($activeStep, $payloadData, $request, $formValues)
    : null;
$canShowStepForm = m360_rw_intake_can_show_operational_step_forms($request, $payloadData);
$csrfInputHtml = $canShowStepForm ? $csrfTokenHtml : '';
$csrfConvertHtml = ($canAct && !empty($gate['can_show_convert'])) ? $csrfTokenHtml : '';
if ($onlineRequestId > 0) {
    $payloadData['online_request_id'] = (string)$onlineRequestId;
}
$photoStatus = m360_rw_intake_reception_photo_status($payloadData);
$wizardStepDef = m360_rw_intake_stepper_definition()[$activeStep] ?? ['label' => '', 'num' => 0];
$wizardStepState = ($request !== null)
    ? m360_rw_intake_get_wizard_step_state($payloadData, $request)
    : ['steps' => [], 'first_incomplete' => 'otp', 'blocker_message' => ''];
$signoffStatus = ($request !== null)
    ? m360_rw_intake_vehicle_signoff_status($payloadData, $request, $onlineRequestId)
    : ['blocked' => false, 'reason_fa' => ''];

function m360_rw_intake_field(string $label, string $value): void
{
    $display = $value;
    $labelFa = $label;
    $isMoney = str_contains($labelFa, 'هزینه') || str_contains($labelFa, 'مبلغ') || str_contains($labelFa, 'توافق')
        || str_contains($labelFa, 'پیش‌پرداخت') || str_contains($labelFa, 'پیش پرداخت') || str_contains($labelFa, 'ریال');
    $isMileage = str_contains($labelFa, 'کیلومتر') || str_contains($labelFa, 'کارکرد');
    $isIdentity = str_contains($labelFa, 'موبایل') || str_contains($labelFa, 'کد ملی') || str_contains($labelFa, 'ملی')
        || str_contains($labelFa, 'VIN') || str_contains($labelFa, 'vin') || str_contains($labelFa, 'شاسی')
        || str_contains($labelFa, 'پلاک') || str_contains($labelFa, 'کد پرونده') || str_contains($labelFa, 'شناسه');

    if ($value !== '' && $value !== '—' && !$isIdentity) {
        if ($isMileage && function_exists('m360_format_mileage')) {
            $display = m360_format_mileage($value);
        } elseif ($isMoney && function_exists('m360_format_money_irr')) {
            $display = m360_format_money_irr($value);
        } elseif ($isMoney && function_exists('m360_format_number') && preg_match('/\d{4,}/', $value) === 1) {
            $display = m360_format_number($value);
        }
    }
    if ($isIdentity && function_exists('m360_format_plain_digits')) {
        if (str_contains($labelFa, 'موبایل') && function_exists('m360_format_masked_mobile')) {
            // Staff intake may show exact mobile; avoid money commas only.
            $display = m360_format_plain_digits(preg_replace('/\D+/', '', $value) ?: $value);
        } elseif (str_contains($labelFa, 'ملی') && function_exists('m360_format_national_code')) {
            $display = m360_format_national_code($value);
        } elseif ((str_contains($labelFa, 'VIN') || str_contains($labelFa, 'vin') || str_contains($labelFa, 'شاسی')) && function_exists('m360_format_vin')) {
            $display = m360_format_vin($value);
        } else {
            $display = m360_format_plain_digits($value);
        }
    }
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">' . m360_rw_h($label) . '</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($display !== '' ? $display : '—') . '</span></div>';
}

/** @param array{value?:string,source_label?:string,missing_label?:string,detail?:string,partial?:bool,warning?:string,mojibake?:bool} $field */
function m360_rw_intake_field_recovered(string $label, array $field): void
{
    $value = trim((string)($field['value'] ?? ''));
    $warning = trim((string)($field['warning'] ?? ''));
    if ($value === '' && $warning === '' && trim((string)($field['missing_label'] ?? '')) !== '') {
        $warning = '';
    }
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">' . m360_rw_h($label) . '</span>';
    if ($value !== '') {
        echo '<span class="m360-rw-field-val">' . m360_rw_h($value) . '</span>';
    } elseif ($warning !== '') {
        echo '<span class="m360-rw-field-miss">' . m360_rw_h($warning) . '</span>';
    } elseif (trim((string)($field['missing_label'] ?? '')) !== '') {
        echo '<span class="m360-rw-field-miss">' . m360_rw_h((string)$field['missing_label']) . '</span>';
    } else {
        echo '<span class="m360-rw-field-val">—</span>';
    }
    if ($value !== '' && trim((string)($field['source_label'] ?? '')) !== '') {
        echo '<span class="m360-rw-field-src">' . m360_rw_h((string)$field['source_label']) . '</span>';
    }
    if (!empty($field['partial']) && trim((string)($field['detail'] ?? '')) !== '') {
        echo '<span class="m360-rw-field-partial">' . m360_rw_h((string)$field['detail']) . '</span>';
    }
    echo '</div>';
}

function m360_rw_intake_form_field(string $label, string $name, string $value, string $type = 'text', bool $required = false, ?array $options = null): void
{
    echo '<div class="m360-rw-form-field">';
    echo '<label class="m360-rw-form-label" for="' . m360_rw_h($name) . '">' . m360_rw_h($label);
    if ($required) {
        echo ' <span class="m360-rw-req">*</span>';
    }
    echo '</label>';
    if ($type === 'select' && is_array($options)) {
        echo '<select class="m360-rw-form-input" id="' . m360_rw_h($name) . '" name="' . m360_rw_h($name) . '"' . ($required ? ' required' : '') . '>';
        echo '<option value="">— انتخاب —</option>';
        foreach ($options as $optVal => $optLabel) {
            $sel = ((string)$optVal === $value) ? ' selected' : '';
            echo '<option value="' . m360_rw_h((string)$optVal) . '"' . $sel . '>' . m360_rw_h((string)$optLabel) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'textarea') {
        echo '<textarea class="m360-rw-form-input m360-rw-form-textarea" id="' . m360_rw_h($name) . '" name="' . m360_rw_h($name) . '" rows="3"' . ($required ? ' required' : '') . '>' . m360_rw_h($value) . '</textarea>';
    } else {
        echo '<input class="m360-rw-form-input" type="' . m360_rw_h($type) . '" id="' . m360_rw_h($name) . '" name="' . m360_rw_h($name) . '" value="' . m360_rw_h($value) . '"' . ($required ? ' required' : '') . '>';
    }
    echo '</div>';
}

$m360LuxCssPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'moghare360-v1-luxury-ui.css';
$m360MirrorCssPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mirror.css';
$m360RwJsPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'm360-reception-intake.js';
$m360LuxCssVer = 'p1-unification-' . (is_file($m360LuxCssPath) ? (string)filemtime($m360LuxCssPath) : '1');
$m360MirrorCssVer = 'p1-unification-' . (is_file($m360MirrorCssPath) ? (string)filemtime($m360MirrorCssPath) : '1');
$m360RwJsVer = 'p1-unification-' . (is_file($m360RwJsPath) ? (string)filemtime($m360RwJsPath) : '1');

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پرونده پذیرش #<?= $onlineRequestId ?> — MOGHARE360</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css?v=<?= m360_rw_h($m360LuxCssVer) ?>">
    <link rel="stylesheet" href="assets/css/mirror.css?v=<?= m360_rw_h($m360MirrorCssVer) ?>">
</head>
<body class="m360-public-shell m360-rw-page m360-rw-wizard-page m360-rw-focused-step m360-rw-focused-<?= m360_rw_h((string)$activeStep) ?>">
<!-- RUNTIME_BUILD: <?= m360_rw_h($m360RuntimeBuild) ?> -->
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header m360-rw-header--compact">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-workbench.php">← میز کار پذیرش</a>
            <span class="m360-rw-gate-chip <?= m360_rw_h($gateClass) ?>"><?= m360_rw_h((string)($gate['label_fa'] ?? '')) ?></span>
        </div>
        <h1 class="m360-rw-title">پرونده پذیرش #<?= m360_rw_h((string)$onlineRequestId) ?></h1>
        <?php if ($flashMsg !== ''): ?>
            <div class="m360-rw-flash <?= $flashOk ? 'is-ok' : 'is-err' ?>"><?= m360_rw_h($flashMsg) ?></div>
        <?php endif; ?>
        <?php m360_rw_intake_render_payload_invalid_notice($payloadMeta); ?>
        <?php m360_rw_intake_render_diagnostic_request_notice($onlineRequestId); ?>
        <?php if (!empty($signoffStatus['blocked'])): ?>
            <div class="m360-rw-flash is-warn" role="status"><?= m360_rw_h((string)$signoffStatus['reason_fa']) ?></div>
        <?php endif; ?>
        <?php if ($jobcardId > 0): ?>
            <div class="m360-rw-flash is-info" role="status">
                قبلاً تبدیل شده به کار کارت #<?= m360_rw_h((string)$jobcardId) ?>.
                <?php if (!m360_rw_intake_contract_customer_accepted($payloadData)): ?>
                    قرارداد مشتری همچنان Step 7 است و قابل حذف یا دورزدن نیست.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?= m360_render_case_stage_header($m360StageTree, ['compact' => true]) ?>
    <?php if ($request !== null): ?>
        <?php m360_rw_intake_render_focused_case_strip($request, $payloadData, $formValues, (string)$activeStep, $onlineRequestId); ?>
    <?php endif; ?>

    <?php if ($request === null): ?>
        <section class="m360-rw-alert">درخواست یافت نشد یا شناسه نامعتبر است.</section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به درخواست‌های آنلاین</a>
        </div>
    <?php elseif ($otpAccessBlocked): ?>
        <section class="m360-rw-alert"><?= m360_rw_h(M360_RW_RECEPTION_UNVERIFIED_ACCESS_MESSAGE_FA) ?></section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به درخواست‌های آنلاین</a>
        </div>
    <?php else: ?>

        <div class="m360-rw-wizard-layout">
            <aside class="m360-rw-wizard-aside" aria-label="خلاصه Gate">
                <div class="m360-rw-gate-compact">
                    <h2 class="m360-rw-gate-compact-title">Gate</h2>
                    <p class="m360-rw-gate-status"><?= m360_rw_h((string)($gate['label_fa'] ?? '')) ?></p>
                    <?php if (!empty($gate['missing'])): ?>
                        <ul class="m360-rw-gate-compact-miss">
                            <?php foreach (array_slice($gate['missing'], 0, 4) as $miss): ?>
                                <li><?= m360_rw_h($miss) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>
            <main class="m360-rw-wizard-main">
                <?php m360_rw_intake_render_wizard_progress($onlineRequestId, $activeStep, $payloadData, $request, $formValues); ?>

                <article class="m360-rw-wizard-page-card" id="<?= m360_rw_h((string)($wizardStepDef['hash'] ?? 'step-wizard')) ?>">
                    <header class="m360-rw-wizard-page-head">
                        <span class="m360-rw-wizard-page-num"><?= m360_rw_h((string)($wizardStepDef['num'] ?? '')) ?></span>
                        <h2 class="m360-rw-wizard-page-title"><?= m360_rw_h((string)($wizardStepDef['label'] ?? '')) ?></h2>
                        <?php if ($isLocked): ?>
                            <span class="m360-rw-wizard-lock-badge">قفل شده</span>
                        <?php elseif ($wizardEditMode): ?>
                            <span class="m360-rw-wizard-edit-badge">اصلاح قبل از امضا</span>
                        <?php endif; ?>
                    </header>

                    <?php if ($request !== null): ?>
                        <?php m360_rw_intake_render_wizard_blocker_notice($payloadData, $request, $activeStep); ?>
                    <?php endif; ?>

                    <?php if ($prevAmendStep !== null && !$wizardEditMode && !$isLocked && $activeStep !== 'signature'): ?>
                        <p class="m360-rw-wizard-amend-link">
                            <a href="<?= m360_rw_h(m360_rw_intake_wizard_amend_url($onlineRequestId, $prevAmendStep)) ?>">بازگشت برای اصلاح قبل از امضا</a>
                        </p>
                    <?php endif; ?>

                    <?php switch ($activeStep):
                        case 'otp': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php m360_rw_intake_render_otp_wizard_block(
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $formValues,
                                    $otpStatusUi,
                                    $otpSend,
                                    $canShowStepForm,
                                    $otpVerified,
                                    $otpMobile,
                                    $otpSent,
                                    $flashOk,
                                    $csrfInputHtml,
                                    $saveUrl
                                ); ?>
                            </div>
                        <?php break;

                        case 'customer': ?>
                            <div class="m360-rw-wizard-step-body" id="section-customer-information">
                                <div class="m360-rw-field-grid">
                                    <?php
                                    m360_rw_intake_field('نام مشتری', trim((string)m360_rw_pick([$request, $payloadData], 'customer_name', 'full_name')));
                                    m360_rw_intake_field('موبایل', m360_rw_intake_resolve_mobile_for_otp($request, $payloadData));
                                    m360_rw_intake_field('کد ملی', trim((string)m360_rw_pick([$request, $payloadData], 'national_id', 'national_code')));
                                    m360_rw_intake_field('شماره تماس دوم', trim((string)m360_rw_pick([$payloadData], 'second_phone')));
                                    m360_rw_intake_field('آدرس سکونت', trim((string)m360_rw_pick([$payloadData], 'residence_address', 'address')));
                                    m360_rw_intake_field('آدرس تحویل خودرو', trim((string)m360_rw_pick([$payloadData], 'vehicle_delivery_address')));
                                    ?>
                                </div>
                                <p class="m360-rw-muted">این مرحله همان اطلاعات مشتری در فرم آنلاین و پذیرش حضوری است؛ در مسیر حضوری OTP اولیه آنلاین-only حذف می‌شود.</p>
                            </div>
                        <?php break;

                        case 'vehicle': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php
                                $vehicleCanon = m360_rw_intake_vehicle_canonical($payloadData, $request, $file['vehicle'] ?? null);
                                $vehicleFields = is_array($vehicleCanon['fields'] ?? null) ? $vehicleCanon['fields'] : $vehicleDossier;
                                $vehicleStepCtxPre = m360_rw_intake_resolve_vehicle_step_context($payloadData, $request, []);
                                $vehicleIdentityReady = !empty($vehicleStepCtxPre['identity_ready']);
                                $vehicleBound = !empty($vehicleStepCtxPre['vehicle_bound']) || m360_rw_intake_vehicle_is_bound($payloadData, $request);
                                $yearMissingWarn = !empty($vehicleStepCtxPre['year_missing_warning'])
                                    || ($vehicleBound && trim((string)($vehicleStepCtxPre['production_year'] ?? '')) === '');
                                if (!empty($wizardStepState['steps']['vehicle']['complete'])): ?>
                                <?php if ($vehicleBound): ?>
                                <p class="m360-rw-flash is-info">خودروی انتخاب‌شده از سوابق مشتری</p>
                                <?php endif; ?>
                                <div class="m360-rw-field-grid">
                                    <?php
                                    m360_rw_intake_field_recovered('پلاک', $vehicleFields['plate'] ?? ['value' => $vehicleCanon['plate']]);
                                    m360_rw_intake_field_recovered('نوع خودرو', $vehicleFields['vehicle_type'] ?? ($vehicleDossier['vehicle_type'] ?? []));
                                    m360_rw_intake_field_recovered('کلاس خودرو', $vehicleFields['vehicle_class'] ?? ($vehicleDossier['vehicle_class'] ?? []));
                                    m360_rw_intake_field_recovered('VIN', $vehicleFields['vin'] ?? ($vehicleDossier['vin'] ?? []));
                                    m360_rw_intake_field_recovered('برند', $vehicleFields['brand'] ?? ['value' => $vehicleCanon['brand']]);
                                    m360_rw_intake_field_recovered('مدل', $vehicleFields['model'] ?? ['value' => $vehicleCanon['model']]);
                                    m360_rw_intake_field_recovered('کیلومتر', $vehicleFields['mileage'] ?? ['value' => $vehicleCanon['mileage']]);
                                    m360_rw_intake_field_recovered('سوخت', $vehicleFields['fuel_level'] ?? ['value' => $vehicleCanon['fuel_level']]);
                                    ?>
                                </div>
                                <?php if ($yearMissingWarn): ?>
                                <p class="m360-rw-warn">سال ساخت خودرو در سوابق ناقص است؛ در صورت نیاز اصلاح شود.</p>
                                <?php endif; ?>
                                <p class="m360-rw-muted">اطلاعات خودرو از پرونده تأیید شده است.</p>
                                <?php elseif ($canShowStepForm && ($vehicleIdentityReady || $vehicleBound)): ?>
                                <?php
                                $vehicleStepCtx = m360_rw_intake_resolve_vehicle_step_context($payloadData, $request, []);
                                $yearDisplay = trim((string)($vehicleStepCtx['production_year'] ?? $formValues['vehicle_year_pair'] ?? $payloadData['vehicle_year_pair'] ?? ''));
                                $visitDisplay = trim((string)($vehicleStepCtx['visit_date'] ?? $formValues['visit_date'] ?? $payloadData['visit_date'] ?? $request['visit_date'] ?? ''));
                                $plateDisplay = trim((string)($vehicleCanon['plate'] !== '' ? $vehicleCanon['plate'] : ($formValues['plate'] ?? '')));
                                ?>
                                <?php if ($vehicleBound): ?>
                                <p class="m360-rw-flash is-info">خودروی انتخاب‌شده از سوابق مشتری</p>
                                <?php endif; ?>
                                <div class="m360-rw-field-grid">
                                    <?php
                                    m360_rw_intake_field_recovered('پلاک', $vehicleFields['plate'] ?? ['value' => $vehicleCanon['plate']]);
                                    m360_rw_intake_field_recovered('برند', $vehicleFields['brand'] ?? ['value' => $vehicleCanon['brand']]);
                                    m360_rw_intake_field_recovered('مدل', ['value' => $vehicleCanon['model'], 'source_label' => 'از پرونده خودرو ERP']);
                                    m360_rw_intake_field_recovered('VIN', $vehicleFields['vin'] ?? ($vehicleDossier['vin'] ?? []));
                                    m360_rw_intake_field_recovered('کیلومتر', $vehicleFields['mileage'] ?? ['value' => $vehicleCanon['mileage']]);
                                    m360_rw_intake_field('سال ساخت', $yearDisplay);
                                    m360_rw_intake_field('تاریخ مراجعه', $visitDisplay);
                                    ?>
                                </div>
                                <?php if ($yearDisplay === ''): ?>
                                <p class="m360-rw-warn">سال ساخت خودرو در سوابق ناقص است؛ در صورت نیاز اصلاح شود.</p>
                                <?php endif; ?>
                                <form class="m360-rw-form" id="m360_rw_vehicle_form" method="post" action="<?= m360_rw_h($saveUrl) ?>" novalidate data-vehicle-bound="<?= $vehicleBound ? '1' : '0' ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="save_vehicle_identity">
                                    <?php m360_rw_intake_return_step_hidden('vehicle'); ?>
                                    <?php /* IDs required by m360-reception-intake.js client gate; server resolves identity canonically. */ ?>
                                    <input type="hidden" id="m360_rw_vehicle_brand" name="vehicle_brand" value="<?= m360_rw_h((string)($vehicleStepCtx['brand'] ?: $vehicleCanon['brand'])) ?>">
                                    <input type="hidden" id="m360_rw_vehicle_class" name="vehicle_class" value="<?= m360_rw_h((string)($vehicleStepCtx['model'] ?: $vehicleCanon['model'])) ?>">
                                    <input type="hidden" id="m360_rw_vehicle_year" name="vehicle_year_pair" value="<?= m360_rw_h($yearDisplay) ?>">
                                    <input type="hidden" id="m360_rw_visit_date" name="visit_date" value="<?= m360_rw_h($visitDisplay) ?>">
                                    <input type="hidden" id="plate_display" name="plate_display" value="<?= m360_rw_h($plateDisplay) ?>">
                                    <input type="hidden" name="mileage" value="<?= m360_rw_h((string)($vehicleStepCtx['mileage'] ?: $vehicleCanon['mileage'])) ?>">
                                    <div class="m360-rw-form-grid">
                                        <?php
                                        $fuelOpts = array_combine(m360_rw_intake_fuel_levels(), m360_rw_intake_fuel_levels());
                                        m360_rw_intake_form_field('سطح سوخت', 'fuel_level', $formValues['fuel_level'] ?? $vehicleCanon['fuel_level'] ?? '', 'select', true, $fuelOpts);
                                        ?>
                                    </div>
                                    <p id="m360_rw_vehicle_form_error" class="m360-rw-flash is-err" style="display:none" role="alert"></p>
                                    <button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>
                                </form>
                                <?php elseif ($canShowStepForm): ?>
                                <form class="m360-rw-form" id="m360_rw_vehicle_form" method="post" action="<?= m360_rw_h($saveUrl) ?>" novalidate>
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="save_vehicle_identity">
                                    <?php m360_rw_intake_return_step_hidden('vehicle'); ?>
                                    <?php m360_rw_intake_render_plate_widget($formValues); ?>
                                    <?php m360_rw_intake_render_vehicle_selector($formValues); ?>
                                    <?php m360_rw_intake_render_visit_calendar($formValues); ?>
                                    <div class="m360-rw-form-grid">
                                        <?php
                                        m360_rw_intake_form_field('VIN / شاسی', 'vin', $formValues['vin'] ?? '');
                                        m360_rw_intake_form_field('کیلومتر ورود', 'mileage', $formValues['mileage'] ?? '', 'number', true);
                                        $fuelOpts = array_combine(m360_rw_intake_fuel_levels(), m360_rw_intake_fuel_levels());
                                        m360_rw_intake_form_field('سطح سوخت', 'fuel_level', $formValues['fuel_level'] ?? '', 'select', true, $fuelOpts);
                                        ?>
                                    </div>
                                    <p id="m360_rw_vehicle_form_error" class="m360-rw-flash is-err" style="display:none" role="alert"></p>
                                    <button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>
                                </form>
                                <?php else: ?>
                                <div class="m360-rw-field-grid">
                                    <?php m360_rw_intake_field_recovered('پلاک', $vehicleDossier['plate'] ?? ['value' => (string)($formValues['plate'] ?? '')]); ?>
                                    <?php m360_rw_intake_field_recovered('نوع خودرو', $vehicleDossier['vehicle_type'] ?? []); ?>
                                    <?php m360_rw_intake_field_recovered('کلاس خودرو', $vehicleDossier['vehicle_class'] ?? []); ?>
                                    <?php m360_rw_intake_field_recovered('VIN', $vehicleDossier['vin'] ?? []); ?>
                                    <?php m360_rw_intake_field_recovered('برند', $vehicleDossier['brand'] ?? ['value' => (string)($formValues['brand'] ?? '')]); ?>
                                    <?php m360_rw_intake_field_recovered('مدل', $vehicleDossier['model'] ?? ['value' => (string)($formValues['model'] ?? $vehicleCanon['model'] ?? '')]); ?>
                                    <?php m360_rw_intake_field_recovered('کیلومتر', $vehicleDossier['mileage'] ?? ['value' => (string)($formValues['mileage'] ?? '')]); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php break;

                        case 'condition': ?>
                            <div class="m360-rw-wizard-step-body m360-rw-condition-focused" data-m360-active-step="condition">
                                <section class="m360-rw-intake-block" id="section-condition-photos" data-m360-photo-panel="1" aria-label="ثبت عکس‌های پذیرش خودرو">
                                    <h3 class="m360-rw-intake-block__title">ثبت عکس‌های پذیرش خودرو</h3>
                                    <div class="m360-rw-flash is-info" id="m360_rw_photo_controls_banner" data-m360-photo-controls="1">
                                        ثبت عکس‌های پذیرش از همین بخش انجام می‌شود — فقط از دوربین؛ پس از ثبت هر ۶ عکس، «تأیید نهایی عکس‌های پذیرش» الزامی است.
                                    </div>
                                    <div id="m360_rw_photo_controls_root">
                                <?php
                                m360_rw_intake_render_reception_photos_section(
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $formValues,
                                    $editSection,
                                    $canShowStepForm,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    'condition',
                                    true
                                );
                                if ($canShowStepForm): ?>
                                    <p class="m360-rw-muted m360-rw-step5-docs-hint">
                                        آپلود گزارش دیاگ، اسکنر، کارشناسی و مدارک PDF در مرحله ۶ است —
                                        <a class="m360-rw-btn m360-rw-btn-secondary" href="<?= m360_rw_h(m360_rw_intake_step_go_url($onlineRequestId, 'documents')) ?>#section-diagnostic-pdf">آپلود گزارش دیاگ و مدارک در مرحله ۶</a>
                                    </p>
                                <?php endif; ?>
                                    </div>
                                </section>
                                <section class="m360-rw-intake-block" id="section-condition-damage" aria-label="وضعیت ظاهری و متعلقات">
                                    <h3 class="m360-rw-intake-block__title">وضعیت ظاهری و متعلقات</h3>
                                <?php if ($canShowStepForm) {
                                    $conditionCanonUx = m360_rw_intake_condition_canonical($payloadData);
                                    $damageStructuredUx = !empty($conditionCanonUx['structured']) ? '1' : '0';
                                    ?>
                                <style>
                                /* C11 — structured zone selector wrapper (interactive hotspots removed) */
                                #m360_rw_damage_ux_root{max-width:100%;overflow-x:hidden}
                                #m360_rw_damage_ux_root .m360-rw-damage-legend{margin-bottom:.35rem}
                                </style>
                                <div id="m360_rw_damage_ux_root" data-structured="<?= m360_rw_h($damageStructuredUx) ?>">
                                    <?php
                                    m360_rw_intake_render_condition_structured_form(
                                        $onlineRequestId,
                                        $payloadData,
                                        $formValues,
                                        $csrfInputHtml,
                                        $saveUrl
                                    );
                                    ?>
                                </div>
                                <?php } else {
                                    $conditionCanon = m360_rw_intake_condition_canonical($payloadData);
                                    echo '<div class="m360-rw-field-grid">';
                                    m360_rw_intake_field('متعلقات صندوق', m360_rw_intake_trunk_summary_fa($conditionCanon['trunk_belongings']));
                                    m360_rw_intake_field('وضعیت ظاهری', m360_rw_intake_damage_summary_fa($conditionCanon['damage_zones']));
                                    if ($conditionCanon['legacy_vehicle_items'] !== '') {
                                        m360_rw_intake_field('یادداشت قدیمی متعلقات', $conditionCanon['legacy_vehicle_items']);
                                    }
                                    if ($conditionCanon['legacy_visible_damage'] !== '') {
                                        m360_rw_intake_field('یادداشت قدیمی وضعیت ظاهری', $conditionCanon['legacy_visible_damage']);
                                    }
                                    echo '</div>';
                                } ?>
                                </section>
                            </div>
                        <?php break;

                        case 'service': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php m360_rw_intake_render_service_wizard_block(
                                    $onlineRequestId,
                                    $formValues,
                                    $diagSubCodes,
                                    $serviceClass,
                                    $canShowStepForm,
                                    $canShowTempActions,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    $customerRequestType,
                                    $payloadData
                                ); ?>
                            </div>
                        <?php break;

                        case 'referral': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php
                                $hm = m360_rw_intake_hall_manager_canonical($payloadData);
                                $prepaymentState = m360_rw_intake_prepayment_state_for_context($conn, $request, $payloadData, $jobcard);
                                $hallManagerAllowed = m360_rw_intake_reception_is_completed($payloadData)
                                    && m360_rw_intake_contract_customer_accepted($payloadData)
                                    && !empty($prepaymentState['allow_handoff']);
                                ?>
                                <p class="m360-rw-muted"><?= m360_rw_h(m360_rw_canonical_hall_step_text_fa()) ?></p>
                                <?php m360_rw_intake_render_prepayment_gate_card($conn, $onlineRequestId, $request, $payloadData, $jobcard, $csrfInputHtml, $saveUrl, $canAct); ?>
                                <?php if ($jobcardId > 0): ?>
                                    <p class="m360-rw-flash is-ok">قبلاً تبدیل شده به کار کارت #<?= m360_rw_h((string)$jobcardId) ?></p>
                                    <?php if (!m360_rw_intake_contract_customer_accepted($payloadData)): ?>
                                        <p class="m360-rw-warn">این وضعیت به معنی عبور از Step 7 نیست؛ قرارداد مشتری باید در مسیر مشتری، امضا و OTP شود.</p>
                                    <?php endif; ?>
                                    <?php if (!$hallManagerAllowed): ?>
                                        <p class="m360-rw-warn">این وضعیت به معنی عبور از گیت پیش‌پرداخت نیست؛ شروع عملیات بدون پرداخت یا مجوز مالک/مدیر مجاز نیست.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php
                                if ($hm['status'] !== ''): ?>
                                    <p class="m360-rw-flash is-ok">وضعیت: <?= m360_rw_h($hm['status']) ?></p>
                                    <?php if ($hm['sent_at'] !== ''): ?>
                                        <p class="m360-rw-muted">زمان ارسال: <?= m360_rw_h($hm['sent_at']) ?></p>
                                    <?php endif; ?>
                                <?php elseif (!$hallManagerAllowed): ?>
                                    <p class="m360-rw-warn"><?= m360_rw_h(m360_rw_intake_operation_gate_message_fa($payloadData)) ?></p>
                                <?php elseif ($canAct && !m360_rw_intake_hall_manager_step_complete($payloadData)): ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="send_to_hall_manager">
                                    <?php m360_rw_intake_return_step_hidden('referral'); ?>
                                    <p class="m360-rw-muted">پرونده پذیرش تکمیل شده و قرارداد تأیید شده است. با ارسال، پرونده برای بررسی مسئول سالن آماده می‌شود.</p>
                                    <?php m360_rw_intake_form_field('یادداشت (اختیاری)', 'hall_manager_note', $formValues['hall_manager_note'] ?? '', 'textarea'); ?>
                                    <button type="submit" class="m360-rw-btn">ارسال پرونده به مسئول سالن</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php break;

                        case 'documents': ?>
                            <div class="m360-rw-wizard-step-body m360-rw-documents-focused" data-m360-active-step="documents">
                                <section class="m360-rw-intake-block" id="section-documents-root" aria-label="مدارک، گزارش دیاگ و توافقات پذیرش">
                                <h3 class="m360-rw-intake-block__title">مدارک، گزارش دیاگ و توافقات پذیرش</h3>
                                <?php
                                $conditionStepComplete = !empty($wizardStepState['steps']['condition']['complete']);
                                if (!$conditionStepComplete): ?>
                                    <div class="m360-rw-flash is-err" id="m360_rw_documents_prereq_lock" role="alert">
                                        برای ذخیره مدارک، ابتدا عکس‌های پذیرش را تکمیل کنید.
                                    </div>
                                    <p class="m360-rw-actions">
                                        <a class="m360-rw-btn" href="<?= m360_rw_h(m360_rw_intake_step_go_url($onlineRequestId, 'condition')) ?>#section-condition-photos">
                                            بازگشت به ثبت عکس‌های پذیرش (مرحله ۵)
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <?php
                                $receptionComplete = m360_rw_intake_reception_is_completed($payloadData);
                                if ($receptionComplete): ?>
                                    <p class="m360-rw-flash is-ok"><?= m360_rw_h(m360_rw_reception_contract_link_sent_message_fa()) ?></p>
                                <?php endif; ?>

                                <?php
                                m360_rw_intake_render_documents_files_list($payloadData);
                                ?>

                                <?php if ($canShowStepForm): ?>
                                <section class="m360-rw-doc-upload" id="section-diagnostic-pdf" aria-label="بارگذاری مدرک جدید" data-m360-doc-upload="1">
                                    <h3 class="m360-rw-section-title">بارگذاری مدرک جدید</h3>
                                    <p class="m360-rw-muted">هر بارگذاری یک کارت جدید اضافه می‌کند. فایل‌های قبلی حفظ می‌شوند.</p>
                                    <form class="m360-rw-form m360-rw-doc-upload-form" method="post" action="<?= m360_rw_h($saveUrl) ?>" enctype="multipart/form-data" id="m360_rw_doc_upload_form">
                                        <?= $csrfInputHtml ?>
                                        <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                        <input type="hidden" name="action_type" value="save_intake_document">
                                        <?php m360_rw_intake_return_step_hidden('documents'); ?>
                                        <div class="m360-rw-form-grid">
                                            <div class="m360-rw-form-field">
                                                <label class="m360-rw-form-label" for="document_type">نوع مدرک</label>
                                                <select class="m360-rw-form-input" id="document_type" name="document_type" required
                                                    data-pdf-accept="<?= m360_rw_h(m360_rw_intake_document_upload_rules('diagnostic_report')['accept']) ?>"
                                                    data-video-accept="<?= m360_rw_h(m360_rw_intake_document_upload_rules('vehicle_video')['accept']) ?>">
                                                    <?php foreach (m360_rw_intake_document_types() as $typeKey => $typeLabel): ?>
                                                        <option value="<?= m360_rw_h($typeKey) ?>"<?= $typeKey === 'diagnostic_report' ? ' selected' : '' ?>><?= m360_rw_h($typeLabel) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="m360-rw-form-field">
                                                <label class="m360-rw-form-label" for="intake_document">فایل</label>
                                                <input class="m360-rw-form-input" id="intake_document" type="file" name="intake_document" accept="<?= m360_rw_h(m360_rw_intake_document_upload_rules('diagnostic_report')['accept']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="m360-rw-form-field">
                                            <label class="m360-rw-form-label" for="document_note">توضیح (اختیاری)</label>
                                            <textarea class="m360-rw-form-input m360-rw-form-textarea" id="document_note" name="document_note" rows="2" maxlength="500" placeholder="مثلاً دیاگ دوم / ویدئوی مورد حاد"></textarea>
                                        </div>
                                        <button type="submit" class="m360-rw-btn m360-rw-btn-secondary">بارگذاری فایل جدید</button>
                                    </form>
                                </section>
                                <?php
                                m360_rw_intake_render_agreements_section(
                                    $payloadData,
                                    $formValues,
                                    true,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    $onlineRequestId
                                );
                                $receptionReady = true;
                                foreach (m360_rw_intake_reception_completion_keys() as $rk) {
                                    if (empty($wizardStepState['steps'][$rk]['complete'])) {
                                        $receptionReady = false;
                                        break;
                                    }
                                }
                                if ($canShowStepForm && $receptionReady && !$receptionComplete): ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="complete_reception_intake">
                                    <?php m360_rw_intake_return_step_hidden('documents'); ?>
                                    <p class="m360-rw-muted"><?= m360_rw_h(m360_rw_canonical_contract_step_text_fa()) ?></p>
                                    <button type="submit" class="m360-rw-btn">تکمیل پذیرش</button>
                                </form>
                                <?php endif; ?>
                                <?php else: ?>
                                <?php
                                m360_rw_intake_render_agreements_section(
                                    $payloadData,
                                    $formValues,
                                    false,
                                    '',
                                    $saveUrl,
                                    $onlineRequestId
                                );
                                ?>
                                <?php endif; ?>
                                <?php
                                m360_rw_intake_render_documents_contract_staff_block(
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    $canShowStepForm
                                );
                                $staffContractRow = null;
                                if (is_resource($conn) && $onlineRequestId > 0) {
                                    $staffContractRow = m360_intake_contract_find_active_for_online_request($conn, $onlineRequestId);
                                }
                                m360_contract_pdf_render_download_button($staffContractRow, 'staff', '', 'm360-rw-btn');
                                ?>
                                </section>
                            </div>
                        <?php break;

                        case 'signature': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php m360_rw_intake_render_signature_checklist($payloadData, $request); ?>
                                <?php
                                $contractPending = m360_rw_intake_contract_cartable_pending($payloadData)
                                    || trim((string)($payloadData['contract_status'] ?? '')) === M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                                $contractAccepted = m360_rw_intake_contract_customer_accepted($payloadData);
                                if ($contractAccepted): ?>
                                    <p class="m360-rw-flash is-ok"><?= m360_rw_h(m360_rw_contract_accepted_customer_message_fa()) ?></p>
                                <?php elseif ($contractPending): ?>
                                    <p class="m360-rw-flash is-info"><?= m360_rw_h(m360_rw_contract_pending_customer_message_fa()) ?></p>
                                    <?php
                                    $smsMeta = is_array($payloadData['reception_intake']['operation_gate']['contract_sms'] ?? null)
                                        ? $payloadData['reception_intake']['operation_gate']['contract_sms']
                                        : [];
                                    if (($smsMeta['sent'] ?? false) === true): ?>
                                        <p class="m360-rw-muted">پیامک اطلاع‌رسانی قرارداد ارسال شد.</p>
                                    <?php elseif (trim((string)($smsMeta['skipped_reason'] ?? '')) === 'sms_not_configured'): ?>
                                        <p class="m360-rw-muted">ارسال پیامک قرارداد در این محیط پیکربندی نشده است.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="m360-rw-signature-summary">
                                    <?php m360_rw_intake_field('مشتری', trim((string)($request['customer_name'] ?? ''))); ?>
                                    <?php m360_rw_intake_field('موبایل', $otpMobile); ?>
                                    <?php m360_rw_intake_field('پلاک', $formValues['plate'] ?? ''); ?>
                                    <?php m360_rw_intake_field('عکس‌ها', (string)$photoStatus['count'] . '/' . (string)$photoStatus['min_required']); ?>
                                    <?php m360_rw_intake_field('دسته خدمت', $formValues['service_primary'] ?? ''); ?>
                                    <?php m360_rw_intake_field('توافق هزینه', $formValues['cost_agreement'] ?? ''); ?>
                                    <?php
                                    $sigAgreements = m360_rw_intake_agreements_from_payload($payloadData);
                                    $sigMin = preg_replace('/[^\d]/', '', (string)($sigAgreements['service_cost_min'] ?? '')) ?? '';
                                    $sigMax = preg_replace('/[^\d]/', '', (string)($sigAgreements['service_cost_max'] ?? '')) ?? '';
                                    m360_rw_intake_field('حداقل هزینه خدمات', $sigMin !== '' ? m360_format_money_irr($sigMin) : 'ثبت نشده');
                                    m360_rw_intake_field('حداکثر هزینه خدمات', $sigMax !== '' ? m360_format_money_irr($sigMax) : 'ثبت نشده');
                                    ?>
                                    <?php m360_rw_intake_field('قرارداد', m360_rw_intake_contract_customer_accepted($payloadData) ? 'تأیید مشتری' : '—'); ?>
                                </div>
                                <?php
                                $sigContractRow = null;
                                if (is_resource($conn) && $onlineRequestId > 0) {
                                    $sigContractRow = m360_intake_contract_find_active_for_online_request($conn, $onlineRequestId);
                                }
                                m360_contract_pdf_render_download_button($sigContractRow, 'staff', '', 'm360-rw-btn');
                                ?>
                                <p class="m360-rw-warn">پذیرش مجاز به امضا، ورود OTP یا تأیید قرارداد به‌جای مشتری نیست.</p>
                            </div>
                        <?php break;

                        case 'locked_summary':
                        default:
                            $snap = is_array($payloadData['reception_intake']['locked_snapshot'] ?? null)
                                ? $payloadData['reception_intake']['locked_snapshot']
                                : [];
                            $lockMeta = is_array($payloadData['reception_intake']['intake_lock'] ?? null)
                                ? $payloadData['reception_intake']['intake_lock']
                                : [];
                            ?>
                            <div class="m360-rw-wizard-step-body m360-rw-locked-summary">
                                <p class="m360-rw-flash is-ok">پرونده پذیرش قفل شده است.</p>
                                <?php m360_rw_intake_field('زمان قفل', (string)($lockMeta['locked_at'] ?? '')); ?>
                                <?php m360_rw_intake_field('موبایل', (string)($snap['mobile'] ?? $otpMobile)); ?>
                                <?php m360_rw_intake_field('پلاک', (string)($snap['vehicle']['plate'] ?? $formValues['plate'] ?? '')); ?>
                                <?php m360_rw_intake_field('توافق هزینه', (string)($snap['documents']['cost_agreement'] ?? $formValues['cost_agreement'] ?? '')); ?>
                                <button type="button" class="m360-rw-btn m360-rw-btn-secondary" disabled>درخواست اصلاحیه پذیرش</button>
                                <p class="m360-rw-muted">این مسیر در فاز اصلاحیه کنترل‌شده فعال می‌شود.</p>
                            </div>
                        <?php break; endswitch; ?>
                </article>

                <?php if (!$isLocked && $request !== null): ?>
                    <?php m360_rw_intake_render_stepper_footer_nav($onlineRequestId, $activeStep); ?>
                <?php endif; ?>

                <?php if ($isLocked): ?>
                    <div class="m360-rw-wizard-amendment-placeholder">
                        <button type="button" class="m360-rw-btn m360-rw-btn-secondary" disabled>درخواست اصلاحیه پذیرش</button>
                        <p class="m360-rw-muted">این مسیر در فاز اصلاحیه کنترل‌شده فعال می‌شود.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        </div>

        <nav class="m360-rw-footer">
            <a href="erp-reception-workbench.php">میز کار پذیرش</a>
            <a href="erp-reception-online-requests.php">درخواست‌های آنلاین</a>
            <a href="erp-reception-online-request-detail.php?request_id=<?= $onlineRequestId ?>">جزئیات درخواست</a>
        </nav>
    <?php endif; ?>
</div>
<script src="assets/js/vehicle-brand-classes.js"></script>
<script src="assets/js/m360-reception-intake.js?v=<?= m360_rw_h($m360RwJsVer) ?>"></script>
</body>
</html>
