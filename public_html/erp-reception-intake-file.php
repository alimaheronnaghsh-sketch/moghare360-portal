<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.9-C-2B/C-2C — Reception intake completion (read GET + controlled POST forms).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

m360_reception_require_staff();

$onlineRequestId = isset($_GET['online_request_id']) ? (int)$_GET['online_request_id'] : 0;
if ($onlineRequestId < 1 && isset($_GET['request_id'])) {
    $onlineRequestId = (int)$_GET['request_id'];
}

$csrfInputHtml = m360_reception_csrf_input_html();

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
$csrfInputHtml = $canAct ? $csrfInputHtml : '';
$csrfConvertHtml = ($canAct && !empty($gate['can_show_convert'])) ? $csrfInputHtml : '';
$jobcardId = (int)($gate['converted_jobcard_id'] ?? 0);
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
$diagSubCodes = is_array($formValues['service_diag_sub_codes'] ?? null) ? $formValues['service_diag_sub_codes'] : [];
$flashMsg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';
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
$canShowStepForm = $canAct && !$isLocked;
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
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">' . m360_rw_h($label) . '</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($value !== '' ? $value : '—') . '</span></div>';
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
$m360LuxCssVer = is_file($m360LuxCssPath) ? (string)filemtime($m360LuxCssPath) : '1';
$m360MirrorCssVer = is_file($m360MirrorCssPath) ? (string)filemtime($m360MirrorCssPath) : '1';
$m360RwJsVer = is_file($m360RwJsPath) ? (string)filemtime($m360RwJsPath) : '1';

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
<body class="m360-public-shell m360-rw-page m360-rw-wizard-page">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-workbench.php">← میز کار پذیرش</a>
            <span class="m360-rw-gate-chip <?= m360_rw_h($gateClass) ?>"><?= m360_rw_h((string)($gate['label_fa'] ?? '')) ?></span>
        </div>
        <h1 class="m360-rw-title">پرونده پذیرش</h1>
        <p class="m360-rw-subtitle">درخواست آنلاین #<?= m360_rw_h((string)$onlineRequestId) ?> — بررسی Gate و تکمیل اطلاعات</p>
        <?php if ($flashMsg !== ''): ?>
            <div class="m360-rw-flash <?= $flashOk ? 'is-ok' : 'is-err' ?>"><?= m360_rw_h($flashMsg) ?></div>
        <?php endif; ?>
        <?php m360_rw_intake_render_payload_invalid_notice($payloadMeta); ?>
        <?php m360_rw_intake_render_diagnostic_request_notice($onlineRequestId); ?>
        <?php if (!empty($signoffStatus['blocked'])): ?>
            <div class="m360-rw-flash is-warn" role="status"><?= m360_rw_h((string)$signoffStatus['reason_fa']) ?></div>
        <?php endif; ?>
    </header>

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

                        case 'vehicle': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php if (!empty($wizardStepState['steps']['vehicle']['complete'])): ?>
                                <div class="m360-rw-field-grid">
                                    <?php
                                    $vehicleCanon = m360_rw_intake_vehicle_canonical($payloadData, $request, $file['vehicle'] ?? null);
                                    $vehicleFields = is_array($vehicleCanon['fields'] ?? null) ? $vehicleCanon['fields'] : $vehicleDossier;
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
                                <p class="m360-rw-muted">اطلاعات خودرو از پرونده تأیید شده است.</p>
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
                                    <?php m360_rw_intake_field_recovered('مدل', $vehicleDossier['model'] ?? ['value' => (string)($formValues['model'] ?? '')]); ?>
                                    <?php m360_rw_intake_field_recovered('کیلومتر', $vehicleDossier['mileage'] ?? ['value' => (string)($formValues['mileage'] ?? '')]); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php break;

                        case 'condition': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php if ($canShowStepForm): ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="save_condition_notes">
                                    <?php m360_rw_intake_return_step_hidden('condition'); ?>
                                    <div class="m360-rw-form-grid">
                                        <?php
                                        m360_rw_intake_form_field('لوازم داخل خودرو', 'vehicle_items', $formValues['vehicle_items'] ?? '', 'textarea', true);
                                        m360_rw_intake_form_field('آسیب ظاهری', 'visible_damage', $formValues['visible_damage'] ?? '', 'textarea', true);
                                        m360_rw_intake_form_field('وضعیت اولیه خودرو', 'initial_vehicle_condition', $formValues['initial_vehicle_condition'] ?? '', 'textarea', true);
                                        ?>
                                    </div>
                                    <button type="submit" class="m360-rw-btn">ذخیره و ادامه</button>
                                </form>
                                <?php endif; ?>
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
                                $hallManagerAllowed = m360_rw_intake_operation_gate_hall_manager_allowed($payloadData);
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

                        case 'photos': ?>
                            <div class="m360-rw-wizard-step-body">
                                <p class="m360-rw-muted">عکس‌های پذیرش: <?= m360_rw_h((string)$photoStatus['count']) ?>/<?= m360_rw_h((string)$photoStatus['min_required']) ?></p>
                                <?php if ($canShowStepForm) {
                                    m360_rw_intake_render_reception_photos_section(
                                        $onlineRequestId,
                                        $payloadData,
                                        $request,
                                        $formValues,
                                        $editSection,
                                        $canAct,
                                        $csrfInputHtml,
                                        $saveUrl,
                                        $activeStep,
                                        true
                                    );
                                } ?>
                            </div>
                        <?php break;

                        case 'documents': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php
                                $receptionComplete = m360_rw_intake_reception_is_completed($payloadData);
                                $contractPending = m360_rw_intake_contract_cartable_pending($payloadData)
                                    || trim((string)($payloadData['contract_status'] ?? '')) === M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                                $contractAccepted = m360_rw_intake_contract_customer_accepted($payloadData);
                                if ($receptionComplete): ?>
                                    <p class="m360-rw-flash is-ok">پذیرش ثبت شد.</p>
                                    <?php if ($contractAccepted): ?>
                                        <p class="m360-rw-flash is-ok">قرارداد توسط مشتری تأیید شده است.</p>
                                    <?php elseif ($contractPending): ?>
                                        <p class="m360-rw-flash is-info">قرارداد در انتظار تأیید مشتری</p>
                                    <?php else: ?>
                                        <p class="m360-rw-flash is-info">قرارداد در انتظار تأیید مشتری</p>
                                    <?php endif; ?>
                                    <p class="m360-rw-warn">عملیات هنوز مجاز نیست</p>
                                    <?php
                                    $smsMeta = is_array($payloadData['reception_intake']['operation_gate']['contract_sms'] ?? null)
                                        ? $payloadData['reception_intake']['operation_gate']['contract_sms']
                                        : [];
                                    if (($smsMeta['sent'] ?? false) === true): ?>
                                        <p class="m360-rw-muted">پیامک اطلاع‌رسانی قرارداد ارسال شد.</p>
                                    <?php elseif (trim((string)($smsMeta['skipped_reason'] ?? '')) === 'sms_not_configured'): ?>
                                        <p class="m360-rw-muted">ارسال پیامک قرارداد در این محیط پیکربندی نشده است.</p>
                                    <?php endif; ?>
                                <?php elseif (!$contractAccepted && m360_rw_intake_documents_cartable_ui_state($payloadData, $request) === 'pending'): ?>
                                    <p class="m360-rw-flash is-info">قرارداد در انتظار تأیید مشتری در کارتابل</p>
                                <?php endif; ?>
                                <?php if ($canShowStepForm): ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>" enctype="multipart/form-data">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="save_diagnostic_pdf">
                                    <?php m360_rw_intake_return_step_hidden('documents'); ?>
                                    <input class="m360-rw-form-input" type="file" name="diagnostic_pdf" accept="application/pdf,.pdf">
                                    <button type="submit" class="m360-rw-btn m360-rw-btn-secondary">بارگذاری PDF دیاگ</button>
                                </form>
                                <?php
                                m360_rw_intake_render_documents_contract_staff_block(
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    $canShowStepForm
                                );
                                ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="save_documents_and_cost">
                                    <?php m360_rw_intake_return_step_hidden('documents'); ?>
                                    <?php m360_rw_intake_form_field('توافق هزینه', 'cost_agreement', $formValues['cost_agreement'] ?? '', 'text', true); ?>
                                    <?php m360_rw_intake_form_field('یادداشت توافق هزینه', 'cost_agreement_note', $formValues['cost_agreement_note'] ?? '', 'textarea'); ?>
                                    <button type="submit" class="m360-rw-btn">ذخیره مستندات و ادامه</button>
                                </form>
                                <?php
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
                                    <p class="m360-rw-muted">با تکمیل پذیرش، قرارداد برای بررسی مشتری آماده می‌شود و شروع عملیات منوط به تأیید قرارداد و مجوز مالی خواهد بود.</p>
                                    <button type="submit" class="m360-rw-btn">تکمیل پذیرش</button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php break;

                        case 'signature': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php m360_rw_intake_render_signature_checklist($payloadData, $request); ?>
                                <div class="m360-rw-signature-summary">
                                    <?php m360_rw_intake_field('مشتری', trim((string)($request['customer_name'] ?? ''))); ?>
                                    <?php m360_rw_intake_field('موبایل', $otpMobile); ?>
                                    <?php m360_rw_intake_field('پلاک', $formValues['plate'] ?? ''); ?>
                                    <?php m360_rw_intake_field('عکس‌ها', (string)$photoStatus['count'] . '/' . (string)$photoStatus['min_required']); ?>
                                    <?php m360_rw_intake_field('دسته خدمت', $formValues['service_primary'] ?? ''); ?>
                                    <?php m360_rw_intake_field('توافق هزینه', $formValues['cost_agreement'] ?? ''); ?>
                                    <?php m360_rw_intake_field('قرارداد', m360_rw_intake_contract_customer_accepted($payloadData) ? 'تأیید مشتری' : '—'); ?>
                                </div>
                                <?php if (($formValues['service_path_clear'] ?? '') !== '1'): ?>
                                    <p class="m360-rw-warn">پرونده در پذیرش موقت باقی می‌ماند تا مسیر عیب/خدمت روشن شود.</p>
                                <?php elseif ($canShowStepForm): ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="sign_and_lock_intake">
                                    <?php m360_rw_intake_return_step_hidden('signature'); ?>
                                    <label class="m360-rw-check-label m360-rw-confirm-check">
                                        <input type="checkbox" name="customer_intake_approved" value="1" required>
                                        مشتری اطلاعات فوق را مشاهده و تأیید کرد
                                    </label>
                                    <?php m360_rw_intake_form_field('نام مشتری (اختیاری)', 'customer_signature_name', $formValues['customer_name'] ?? ''); ?>
                                    <label class="m360-rw-check-label m360-rw-confirm-check">
                                        <input type="checkbox" name="confirmed_by_receptionist" value="1" required>
                                        تأیید نهایی پذیرشگر — پرونده آماده قفل است
                                    </label>
                                    <?php m360_rw_intake_form_field('یادداشت تأیید', 'confirmation_note', $formValues['confirmation_note'] ?? '', 'textarea'); ?>
                                    <button type="submit" class="m360-rw-btn">امضا و قفل پرونده پذیرش</button>
                                </form>
                                <?php endif; ?>
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
