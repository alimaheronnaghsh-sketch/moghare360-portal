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
$csrfInputHtml = $canAct ? m360_reception_csrf_input_html() : '';
$csrfConvertHtml = ($canAct && !empty($gate['can_show_convert'])) ? $csrfInputHtml : '';
$jobcardId = (int)($gate['converted_jobcard_id'] ?? 0);
$vehicleId = (int)($request['vehicle_id'] ?? 0);
$gateClass = m360_rw_gate_status_chip_class((string)($gate['status'] ?? 'needs_completion'));
$fieldRecovery = $file['field_recovery'] ?? ($gate['field_recovery'] ?? []);
$customerRequestType = trim((string)($request['request_type'] ?? m360_rw_pick([$file['payload'] ?? []], 'request_type')));
$formValues = ($request !== null) ? m360_rw_intake_form_values($file['payload'] ?? [], $request) : [];
$diagSubCodes = is_array($formValues['service_diag_sub_codes'] ?? null) ? $formValues['service_diag_sub_codes'] : [];
$flashMsg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';
$saveUrl = 'erp-reception-intake-save.php';
$editSection = trim((string)($_GET['edit_section'] ?? ''));
$payloadData = $file['payload'] ?? [];
$otpSend = m360_rw_intake_otp_send_available();
$otpVerified = $request !== null && m360_online_req_payload_otp_verified($request);

function m360_rw_intake_field(string $label, string $value): void
{
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">' . m360_rw_h($label) . '</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($value !== '' ? $value : '—') . '</span></div>';
}

/** @param array{value?:string,source_label?:string,missing_label?:string,detail?:string,partial?:bool} $field */
function m360_rw_intake_field_recovered(string $label, array $field): void
{
    $value = trim((string)($field['value'] ?? ''));
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">' . m360_rw_h($label) . '</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($value !== '' ? $value : '—') . '</span>';
    if ($value !== '' && trim((string)($field['source_label'] ?? '')) !== '') {
        echo '<span class="m360-rw-field-src">' . m360_rw_h((string)$field['source_label']) . '</span>';
    } elseif ($value === '' && trim((string)($field['missing_label'] ?? '')) !== '') {
        echo '<span class="m360-rw-field-miss">' . m360_rw_h((string)$field['missing_label']) . '</span>';
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

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پرونده پذیرش #<?= $onlineRequestId ?> — MOGHARE360</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <script src="assets/js/m360-reception-intake.js" defer></script>
</head>
<body class="m360-public-shell m360-rw-page">
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
    </header>

    <?php if ($request === null): ?>
        <section class="m360-rw-alert">درخواست یافت نشد یا شناسه نامعتبر است.</section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به درخواست‌های آنلاین</a>
        </div>
    <?php else: ?>

        <!-- 1. وضعیت و Gate -->
        <section class="m360-rw-section-block" id="section-gate-checklist">
            <h2 class="m360-rw-section-title">۱. وضعیت و Gate</h2>
            <div class="m360-rw-panel m360-rw-gate-panel">
                <p class="m360-rw-gate-status"><?= m360_rw_h((string)($gate['label_fa'] ?? '')) ?></p>
                <?php if (!empty($gate['reception_mode'])): ?>
                    <p class="m360-rw-muted">حالت پذیرش: <?= m360_rw_h(match ((string)$gate['reception_mode']) {
                        'temporary' => 'پذیرش موقت',
                        'full' => 'پذیرش کامل',
                        'incomplete' => 'پرونده ناقص',
                        default => '—',
                    }) ?></p>
                <?php endif; ?>
                <?php if (!empty($gate['partial_notes'])): ?>
                    <div class="m360-rw-partial-notes">
                        <?php foreach ($gate['partial_notes'] as $pnote): ?>
                            <p class="m360-rw-muted"><?= m360_rw_h((string)$pnote) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($gate['missing'])): ?>
                    <div class="m360-rw-missing">
                        <strong>موارد ناقص:</strong>
                        <ul>
                            <?php foreach ($gate['missing'] as $miss): ?>
                                <li><?= m360_rw_h($miss) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($canShowTempActions): ?>
            <div class="m360-rw-panel">
                <h3>اقدامات پذیرش موقت</h3>
                <p class="m360-rw-muted">در پذیرش موقت: رد و درخواست تکمیل اطلاعات مجاز است. تبدیل به کارت کار فقط پس از روشن شدن مسیر عیب/خدمت.</p>
                <div class="m360-rw-actions">
                    <form method="post" action="erp-reception-online-request-accept.php" style="display:inline;">
                        <?= $csrfInputHtml ?>
                        <input type="hidden" name="request_id" value="<?= $onlineRequestId ?>">
                        <input type="hidden" name="action" value="under_review">
                        <button type="submit" class="m360-rw-btn m360-rw-btn-secondary">درخواست تکمیل اطلاعات</button>
                    </form>
                    <form method="post" action="erp-reception-online-request-accept.php" onsubmit="return confirm('درخواست رد شود؟');" style="display:inline;">
                        <?= $csrfInputHtml ?>
                        <input type="hidden" name="request_id" value="<?= $onlineRequestId ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="m360-rw-btn m360-rw-btn-danger">رد درخواست</button>
                    </form>
                </div>
                <p class="m360-rw-placeholder">ارجاع کارشناسی / عیب‌یابی اولیه — ثبت عملیاتی در فاز تکمیل عملیات پذیرش.</p>
            </div>
            <?php endif; ?>
        </section>

        <?php
        $secMobile = m360_rw_intake_section_ui_state('mobile_otp', $payloadData, $request, $formValues, $editSection);
        ?>
        <section class="m360-rw-section-block" id="section-mobile-otp">
            <h2 class="m360-rw-section-title">شماره موبایل و تأیید مشتری</h2>
            <div class="m360-rw-panel">
                <?php m360_rw_intake_render_section_header('موبایل و OTP', $secMobile, $onlineRequestId, 'mobile_otp', $canAct); ?>
                <?php if ($secMobile['show_summary']): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('موبایل', (string)($request['mobile'] ?? '')); ?>
                    <?php m360_rw_intake_field('OTP', $otpVerified ? 'تأیید شده' : 'نیازمند تأیید مشتری / OTP'); ?>
                </div>
                <?php endif; ?>
                <?php if ($canAct && $secMobile['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_mobile_correction">
                    <?php m360_rw_intake_return_section_hidden('mobile_otp'); ?>
                    <?php m360_rw_intake_form_field('موبایل اصلاح‌شده', 'mobile_corrected', $formValues['mobile_corrected'] ?? '', 'tel', true); ?>
                    <p class="m360-rw-warn">پس از اصلاح موبایل، OTP همچنان نیازمند تأیید واقعی مشتری است.</p>
                    <button type="submit" class="m360-rw-btn">ذخیره موبایل</button>
                </form>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>" style="margin-top:0.75rem;">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="send_customer_otp">
                    <?php m360_rw_intake_return_section_hidden('mobile_otp'); ?>
                    <button type="submit" class="m360-rw-btn m360-rw-btn-secondary"<?= $otpSend['available'] ? '' : ' disabled' ?>>ارسال OTP به مشتری</button>
                    <?php if (!$otpSend['available']): ?>
                        <p class="m360-rw-muted"><?= m360_rw_h($otpSend['reason_fa']) ?></p>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <!-- 2. اطلاعات مشتری و خودرو -->
        <section class="m360-rw-section-block" id="section-vehicle-identity">
            <h2 class="m360-rw-section-title">۲. اطلاعات مشتری و خودرو</h2>
            <div class="m360-rw-panel">
                <h3>مشتری</h3>
                <div class="m360-rw-field-grid">
                    <?php
                    m360_rw_intake_field_recovered('نام مشتری', $fieldRecovery['customer_name'] ?? []);
                    m360_rw_intake_field_recovered('موبایل', $fieldRecovery['mobile'] ?? []);
                    m360_rw_intake_field_recovered('وضعیت OTP', $fieldRecovery['otp'] ?? ['value' => 'تأیید نشده', 'missing_label' => 'نیازمند تأیید مشتری / OTP']);
                    m360_rw_intake_field('شناسه مشتری ERP', (string)($request['customer_id'] ?? ''));
                    ?>
                </div>
            </div>
            <div class="m360-rw-panel">
                <h3>خودرو</h3>
                <?php if (!empty($fieldRecovery['vehicle']['partial']) && !empty($fieldRecovery['vehicle']['detail'])): ?>
                    <p class="m360-rw-field-partial"><?= m360_rw_h((string)$fieldRecovery['vehicle']['detail']) ?></p>
                <?php endif; ?>
                <div class="m360-rw-field-grid">
                    <?php
                    m360_rw_intake_field_recovered('پلاک', $fieldRecovery['plate'] ?? []);
                    m360_rw_intake_field_recovered('VIN / شاسی', $fieldRecovery['vin'] ?? []);
                    m360_rw_intake_field_recovered('برند', $fieldRecovery['brand'] ?? []);
                    m360_rw_intake_field_recovered('مدل', $fieldRecovery['model'] ?? []);
                    m360_rw_intake_field_recovered('کیلومتر', $fieldRecovery['mileage'] ?? []);
                    m360_rw_intake_field_recovered('سطح سوخت', $fieldRecovery['fuel'] ?? []);
                    m360_rw_intake_field_recovered('لوازم داخل خودرو', $fieldRecovery['belongings'] ?? []);
                    m360_rw_intake_field_recovered('آسیب ظاهری', $fieldRecovery['damage'] ?? []);
                    m360_rw_intake_field('شناسه خودرو ERP', (string)($request['vehicle_id'] ?? ''));
                    ?>
                </div>
                <?php if ($canAct): ?>
                <?php $secVehicle = m360_rw_intake_section_ui_state('vehicle_identity', $payloadData, $request, $formValues, $editSection); ?>
                <?php m360_rw_intake_render_section_header('ثبت خودرو', $secVehicle, $onlineRequestId, 'vehicle_identity', $canAct); ?>
                <?php if ($secVehicle['show_summary']): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('پلاک', $formValues['plate'] ?? ''); ?>
                    <?php m360_rw_intake_field('VIN', $formValues['vin'] ?? ''); ?>
                    <?php m360_rw_intake_field('برند/مدل', trim(($formValues['brand'] ?? '') . ' / ' . ($formValues['model'] ?? ''), ' /')); ?>
                </div>
                <?php endif; ?>
                <?php if ($secVehicle['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_vehicle_identity">
                    <?php m360_rw_intake_return_section_hidden('vehicle_identity'); ?>
                    <h4 class="m360-rw-form-title">ثبت / ویرایش اطلاعات خودرو</h4>
                    <?php m360_rw_intake_render_plate_widget($formValues); ?>
                    <div class="m360-rw-form-grid">
                        <?php
                        m360_rw_intake_form_field('VIN / شاسی', 'vin', $formValues['vin'] ?? '');
                        m360_rw_intake_form_field('برند', 'brand', $formValues['brand'] ?? '');
                        m360_rw_intake_form_field('مدل', 'model', $formValues['model'] ?? '');
                        m360_rw_intake_form_field('کیلومتر ورود', 'mileage', $formValues['mileage'] ?? '', 'number');
                        $fuelOpts = array_combine(m360_rw_intake_fuel_levels(), m360_rw_intake_fuel_levels());
                        m360_rw_intake_form_field('سطح سوخت', 'fuel_level', $formValues['fuel_level'] ?? '', 'select', false, $fuelOpts);
                        ?>
                    </div>
                    <button type="submit" class="m360-rw-btn">ذخیره اطلاعات خودرو</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="m360-rw-panel">
                <h3>شرح درخواست / شکایت مشتری</h3>
                <p class="m360-rw-note"><?= m360_rw_h(m360_rw_pick([$request, $file['payload']], 'service_note', 'complaint') ?: '—') ?></p>
            </div>
            <?php if ($payloadRows !== []): ?>
            <div class="m360-rw-panel">
                <h3>اطلاعات تکمیلی فرم آنلاین</h3>
                <?php if (!$payloadMeta['valid']): ?>
                    <p class="m360-rw-warn"><?= m360_rw_h($payloadMeta['raw_warning']) ?></p>
                <?php endif; ?>
                <div class="m360-rw-field-grid">
                    <?php foreach ($payloadRows as $prow): ?>
                        <?php m360_rw_intake_field($prow['label_fa'], $prow['value']); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <?php $secCondition = m360_rw_intake_section_ui_state('condition_notes', $payloadData, $request, $formValues, $editSection); ?>
        <section class="m360-rw-section-block" id="section-condition-notes">
            <h2 class="m360-rw-section-title">وضعیت خودرو (لوازم / آسیب)</h2>
            <div class="m360-rw-panel">
                <?php m360_rw_intake_render_section_header('یادداشت وضعیت', $secCondition, $onlineRequestId, 'condition_notes', $canAct); ?>
                <?php if ($secCondition['show_summary']): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('لوازم', $formValues['vehicle_items'] ?? ''); ?>
                    <?php m360_rw_intake_field('آسیب ظاهری', $formValues['visible_damage'] ?? ''); ?>
                </div>
                <?php endif; ?>
                <?php if ($canAct && $secCondition['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_condition_notes">
                    <?php m360_rw_intake_return_section_hidden('condition_notes'); ?>
                    <div class="m360-rw-form-grid">
                        <?php
                        m360_rw_intake_form_field('لوازم داخل خودرو', 'vehicle_items', $formValues['vehicle_items'] ?? '', 'textarea');
                        m360_rw_intake_form_field('آسیب ظاهری', 'visible_damage', $formValues['visible_damage'] ?? '', 'textarea');
                        m360_rw_intake_form_field('وضعیت اولیه خودرو', 'initial_vehicle_condition', $formValues['initial_vehicle_condition'] ?? '', 'textarea');
                        ?>
                    </div>
                    <button type="submit" class="m360-rw-btn">ذخیره یادداشت وضعیت</button>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <!-- 3. دسته‌بندی خدمات پذیرشگر -->
        <?php $secService = m360_rw_intake_section_ui_state('service_classification', $payloadData, $request, $formValues, $editSection); ?>
        <section class="m360-rw-section-block" id="section-service-classification">
            <h2 class="m360-rw-section-title">۳. دسته‌بندی خدمات پذیرشگر</h2>
            <div class="m360-rw-panel m360-rw-service-class-panel">
                <p class="m360-rw-muted"><strong>دسته‌بندی داخلی پذیرش</strong> — <span class="m360-rw-help-collapsed">برای گزارش مدیریتی و مسیر عملیات</span></p>
                <details class="m360-rw-help-collapsed"><summary>راهنما</summary><?= m360_rw_h(M360_RW_SERVICE_CLASS_BUSINESS_PURPOSE_FA) ?></details>
                <?php if ($customerRequestType !== ''): ?>
                    <p class="m360-rw-warn">نوع درخواست مشتری: <?= m360_rw_h($customerRequestType) ?> — <?= m360_rw_h(M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA) ?></p>
                <?php endif; ?>
                <?php if (!empty($serviceClass['registered']) && !empty($serviceClass['selected_labels'])): ?>
                    <p><strong>ثبت‌شده:</strong> <?= m360_rw_h(implode('، ', $serviceClass['selected_labels'])) ?></p>
                <?php else: ?>
                    <p class="m360-rw-warn">دسته‌بندی خدمات توسط پذیرشگر ثبت نشده است.</p>
                    <?php if (!$canAct): ?>
                    <p class="m360-rw-placeholder"><?= m360_rw_h(M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="m360-rw-service-taxonomy">
                    <h3>ساختار دسته‌بندی</h3>
                    <?php foreach ($serviceClass['taxonomy'] as $group): ?>
                        <div class="m360-rw-tax-group">
                            <strong><?= m360_rw_h($group['label']) ?></strong>
                            <?php if ($group['subs'] !== []): ?>
                                <ul>
                                    <?php foreach ($group['subs'] as $subLabel): ?>
                                        <li><?= m360_rw_h($subLabel) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($canAct): ?>
                <?php m360_rw_intake_render_section_header('دسته‌بندی خدمات', $secService, $onlineRequestId, 'service_classification', $canAct); ?>
                <?php if ($secService['show_summary'] && trim((string)($formValues['service_primary'] ?? '')) !== ''): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('دسته اصلی', $serviceClass['taxonomy'][$formValues['service_primary'] ?? '']['label'] ?? ($formValues['service_primary'] ?? '')); ?>
                </div>
                <?php endif; ?>
                <?php if ($secService['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_service_classification">
                    <?php m360_rw_intake_return_section_hidden('service_classification'); ?>
                    <p class="m360-rw-muted"><?= m360_rw_h(M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA) ?></p>
                    <h4 class="m360-rw-form-title">ثبت دسته‌بندی توسط پذیرشگر</h4>
                    <?php
                    $primaryOpts = [];
                    foreach ($serviceClass['taxonomy'] as $code => $group) {
                        $primaryOpts[$code] = $group['label'];
                    }
                    m360_rw_intake_form_field('دسته اصلی', 'service_primary', $formValues['service_primary'] ?? '', 'select', true, $primaryOpts);
                    ?>
                    <div class="m360-rw-form-field">
                        <span class="m360-rw-form-label">زیردسته‌های عیب‌یابی (برای کارشناسی و عیب‌یابی)</span>
                        <div class="m360-rw-checkbox-grid">
                            <?php foreach ($serviceClass['taxonomy']['diag']['subs'] as $subCode => $subLabel): ?>
                                <label class="m360-rw-check-label">
                                    <input type="checkbox" name="service_diag_sub[]" value="<?= m360_rw_h($subCode) ?>"<?= in_array($subCode, $diagSubCodes, true) ? ' checked' : '' ?>>
                                    <?= m360_rw_h($subLabel) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                    m360_rw_intake_form_field('مسیر عیب/خدمت روشن است؟', 'service_path_clear', $formValues['service_path_clear'] ?? '', 'select', true, ['1' => 'بله — مسیر مشخص است', '0' => 'خیر — پرونده در پذیرش موقت می‌ماند']);
                    m360_rw_intake_form_field('یادداشت مسیر خدمت', 'service_path_note', $formValues['service_path_note'] ?? '', 'textarea');
                    ?>
                    <button type="submit" class="m360-rw-btn">ذخیره دسته‌بندی خدمات</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php $secTemp = m360_rw_intake_section_ui_state('temporary_reception', $payloadData, $request, $formValues, $editSection); ?>
        <section class="m360-rw-section-block" id="section-temporary-reception">
            <h2 class="m360-rw-section-title">۴. وضعیت پذیرش موقت</h2>
            <div class="m360-rw-panel">
                <p class="m360-rw-muted">تبدیل به کارت کار تا زمانی که مسیر عیب/خدمت روشن نشود و Gate عبور نکند، مسدود می‌ماند.</p>
                <?php m360_rw_intake_render_section_header('پذیرش موقت', $secTemp, $onlineRequestId, 'temporary_reception', $canAct); ?>
                <?php if ($secTemp['show_summary']): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('وضعیت', m360_rw_intake_temp_statuses()[$formValues['temporary_status'] ?? ''] ?? '—'); ?>
                </div>
                <?php endif; ?>
                <?php if ($canAct && $secTemp['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_temporary_reception">
                    <?php m360_rw_intake_return_section_hidden('temporary_reception'); ?>
                    <?php
                    m360_rw_intake_form_field('وضعیت موقت', 'temporary_status', $formValues['temporary_status'] ?? '', 'select', true, m360_rw_intake_temp_statuses());
                    m360_rw_intake_form_field('دلیل', 'temporary_reason', $formValues['temporary_reason'] ?? '', 'textarea');
                    m360_rw_intake_form_field('درخواست اطلاعات بیشتر', 'request_more_info_note', $formValues['request_more_info_note'] ?? '', 'textarea');
                    m360_rw_intake_form_field('یادداشت کارشناسی', 'expert_review_note', $formValues['expert_review_note'] ?? '', 'textarea');
                    ?>
                    <button type="submit" class="m360-rw-btn m360-rw-btn-secondary">ذخیره وضعیت موقت</button>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <?php $secReferral = m360_rw_intake_section_ui_state('referral', $payloadData, $request, $formValues, $editSection); ?>
        <section class="m360-rw-section-block" id="section-referral-team">
            <h2 class="m360-rw-section-title">ارجاع کارشناسی / تیم مسئول</h2>
            <div class="m360-rw-panel">
                <?php m360_rw_intake_render_section_header('ارجاع تیم', $secReferral, $onlineRequestId, 'referral', $canAct); ?>
                <?php if ($secReferral['show_summary']): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('تیم', m360_rw_intake_referral_teams()[$formValues['referral_team_id'] ?? ''] ?? '—'); ?>
                    <?php m360_rw_intake_field('یادداشت', $formValues['referral_note'] ?? ''); ?>
                </div>
                <?php endif; ?>
                <?php if ($canAct && $secReferral['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_referral_team">
                    <?php m360_rw_intake_return_section_hidden('referral'); ?>
                    <?php m360_rw_intake_form_field('تیم مسئول', 'referral_team_id', $formValues['referral_team_id'] ?? '', 'select', true, m360_rw_intake_referral_teams()); ?>
                    <?php m360_rw_intake_form_field('نوع ارجاع', 'referral_type', $formValues['referral_type'] ?? 'service_team', 'select', false, ['expert_review' => 'کارشناسی', 'electrical' => 'برق', 'mechanical' => 'مکانیک', 'service_team' => 'تیم خدمات']); ?>
                    <?php m360_rw_intake_form_field('یادداشت ارجاع', 'referral_note', $formValues['referral_note'] ?? '', 'textarea'); ?>
                    <button type="submit" class="m360-rw-btn">ذخیره ارجاع</button>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <?php
        if ($canAct) {
            m360_rw_intake_render_reception_photos_section(
                $onlineRequestId,
                $payloadData,
                $request,
                $formValues,
                $editSection,
                $canAct,
                $csrfInputHtml,
                $saveUrl
            );
        } else {
            echo '<section class="m360-rw-section-block" id="section-camera-photo"><div class="m360-rw-panel"><p class="m360-rw-muted">عکس‌های پذیرش — فقط مشاهده</p></div></section>';
        }
        $secDiag = m360_rw_intake_section_ui_state('diagnostic_pdf', $payloadData, $request, $formValues, $editSection);
        ?>
        <section class="m360-rw-section-block" id="section-diagnostic-pdf">
            <h2 class="m360-rw-section-title">فایل دیاگ اولیه</h2>
            <div class="m360-rw-panel">
                <?php m360_rw_intake_render_section_header('PDF دیاگ', $secDiag, $onlineRequestId, 'diagnostic_pdf', $canAct); ?>
                <?php if ($secDiag['show_summary'] && ($formValues['diagnostic_pdf'] ?? '') !== ''): ?>
                <div class="m360-rw-sec-summary"><?php m360_rw_intake_field('فایل', 'ثبت شد'); ?></div>
                <?php endif; ?>
                <?php if ($canAct && $secDiag['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>" enctype="multipart/form-data">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_diagnostic_pdf">
                    <?php m360_rw_intake_return_section_hidden('diagnostic_pdf'); ?>
                    <input class="m360-rw-form-input" type="file" name="diagnostic_pdf" accept="application/pdf,.pdf" required>
                    <button type="submit" class="m360-rw-btn">بارگذاری PDF دیاگ</button>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <?php $secContract = m360_rw_intake_section_ui_state('contract', $payloadData, $request, $formValues, $editSection); ?>
        <section class="m360-rw-section-block" id="section-contract">
            <h2 class="m360-rw-section-title">قرارداد پذیرش و تأیید مشتری</h2>
            <div class="m360-rw-panel">
                <?php m360_rw_intake_render_section_header('قرارداد', $secContract, $onlineRequestId, 'contract', $canAct); ?>
                <?php if (($formValues['contract_text'] ?? '') !== ''): ?>
                <div class="m360-rw-contract-text"><?= m360_rw_h($formValues['contract_text']) ?></div>
                <?php endif; ?>
                <?php if ($canAct && $secContract['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>" style="display:inline;">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="run_intake_contract">
                    <?php m360_rw_intake_return_section_hidden('contract'); ?>
                    <button type="submit" class="m360-rw-btn m360-rw-btn-secondary">اجرای قرارداد پذیرش</button>
                </form>
                <?php if (($formValues['contract_text'] ?? '') !== ''): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="approve_intake_contract">
                    <?php m360_rw_intake_return_section_hidden('contract'); ?>
                    <label class="m360-rw-check-label"><input type="checkbox" name="customer_contract_approved" value="1"> مشتری قرارداد را مطالعه و تأیید می‌کند</label>
                    <button type="submit" class="m360-rw-btn">ثبت تأیید مشتری</button>
                </form>
                <?php endif; ?>
                <?php if ($jobcardId > 0): ?>
                <p class="m360-rw-muted"><a href="erp-intake-contract-generate.php?jobcard_id=<?= $jobcardId ?>">قرارداد ساختاریافته P1.5 (JobCard)</a></p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- 5. مستندات و توافق -->
        <section class="m360-rw-section-block" id="section-documents-cost">
            <h2 class="m360-rw-section-title">مستندات و توافق هزینه</h2>
            <div class="m360-rw-panel">
                <?php
                m360_rw_intake_field_recovered('تعداد رکورد عکس/رسانه', $fieldRecovery['photo'] ?? []);
                m360_rw_intake_field_recovered('توافق هزینه', $fieldRecovery['cost'] ?? []);
                m360_rw_intake_field_recovered('وضعیت قرارداد پذیرش', $fieldRecovery['contract'] ?? []);
                m360_rw_intake_field_recovered('دیاگ / وضعیت عیب‌یابی', $fieldRecovery['diag'] ?? []);
                ?>
                <?php if ($jobcardId > 0): ?>
                    <div class="m360-rw-actions">
                        <a class="m360-rw-btn m360-rw-btn-secondary" href="erp-jobcard-camera-capture.php?jobcard_id=<?= $jobcardId ?>">دوربین JobCard</a>
                        <a class="m360-rw-btn m360-rw-btn-secondary" href="erp-jobcard-diagnostic-file.php?jobcard_id=<?= $jobcardId ?>">فایل دیاگ</a>
                    </div>
                <?php else: ?>
                    <p class="m360-rw-muted">پس از تبدیل به کارت کار، لینک دوربین و دیاگ JobCard نیز فعال می‌شود.</p>
                <?php endif; ?>
                <?php if ($canAct): ?>
                <?php $secDocs = m360_rw_intake_section_ui_state('documents_cost', $payloadData, $request, $formValues, $editSection); ?>
                <?php m360_rw_intake_render_section_header('توافق و وضعیت', $secDocs, $onlineRequestId, 'documents_cost', $canAct); ?>
                <?php if ($secDocs['show_summary']): ?>
                <div class="m360-rw-sec-summary">
                    <?php m360_rw_intake_field('توافق هزینه', $formValues['cost_agreement'] ?? ''); ?>
                </div>
                <?php endif; ?>
                <?php if ($secDocs['show_form']): ?>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_documents_and_cost">
                    <?php m360_rw_intake_return_section_hidden('documents_cost'); ?>
                    <h4 class="m360-rw-form-title">ثبت وضعیت مستندات و توافق</h4>
                    <div class="m360-rw-form-grid">
                        <?php
                        $docStatusOpts = ['ثبت شد' => 'ثبت شد', 'در انتظار' => 'در انتظار', 'نیاز نیست' => 'نیاز نیست'];
                        m360_rw_intake_form_field('وضعیت دیاگ', 'diagnostic_status', $formValues['diagnostic_status'] ?? '', 'select', false, $docStatusOpts);
                        m360_rw_intake_form_field('وضعیت قرارداد', 'contract_status', $formValues['contract_status'] ?? '', 'select', false, $docStatusOpts);
                        m360_rw_intake_form_field('توافق هزینه', 'cost_agreement', $formValues['cost_agreement'] ?? '');
                        m360_rw_intake_form_field('یادداشت توافق هزینه', 'cost_agreement_note', $formValues['cost_agreement_note'] ?? '', 'textarea');
                        ?>
                    </div>
                    <p class="m360-rw-muted">وضعیت عکس پذیرش از چک‌لیست ۶ عکس محاسبه می‌شود و در این فرم قابل دور زدن نیست.</p>
                    <button type="submit" class="m360-rw-btn">ذخیره مستندات و توافق</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if ($file['intake'] !== null): ?>
            <div class="m360-rw-panel">
                <h3>پرونده intake (ERP)</h3>
                <div class="m360-rw-field-grid">
                    <?php m360_rw_intake_field('شناسه intake', (string)($file['intake']['intake_id'] ?? '')); ?>
                    <?php m360_rw_intake_field('نام', (string)($file['intake']['full_name'] ?? '')); ?>
                    <?php m360_rw_intake_field('موبایل', (string)($file['intake']['mobile'] ?? '')); ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($file['contracts'] !== []): ?>
            <div class="m360-rw-panel">
                <h3>قراردادهای پذیرش</h3>
                <ul class="m360-rw-list">
                    <?php foreach ($file['contracts'] as $ct): ?>
                        <li>
                            قرارداد #<?= m360_rw_h((string)($ct['contract_id'] ?? '')) ?>
                            — <?= m360_rw_h((string)($ct['contract_status'] ?? '')) ?>
                            <a href="erp-intake-contract-detail.php?contract_id=<?= (int)($ct['contract_id'] ?? 0) ?>">مشاهده</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>

        <?php $secConfirm = m360_rw_intake_section_ui_state('reception_confirmation', $payloadData, $request, $formValues, $editSection); ?>
        <section class="m360-rw-section-block" id="section-reception-confirmation">
            <h2 class="m360-rw-section-title">تأیید نهایی پذیرشگر و مسیر بعدی</h2>
            <?php if ($canAct): ?>
            <div class="m360-rw-panel">
                <?php m360_rw_intake_render_section_header('تأیید نهایی', $secConfirm, $onlineRequestId, 'reception_confirmation', $canAct); ?>
                <?php if ($secConfirm['show_summary']): ?>
                <div class="m360-rw-sec-summary"><?php m360_rw_intake_field('وضعیت', 'تأیید شده'); ?></div>
                <?php endif; ?>
                <?php if ($secConfirm['show_form']): ?>
                <p class="m360-rw-warn">تأیید پذیرشگر جایگزین OTP مشتری نیست و به‌تنهایی Gate را برای تبدیل عبور نمی‌دهد.</p>
                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                    <?= $csrfInputHtml ?>
                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action_type" value="save_reception_confirmation">
                    <?php m360_rw_intake_return_section_hidden('reception_confirmation'); ?>
                    <label class="m360-rw-check-label m360-rw-confirm-check">
                        <input type="checkbox" name="confirmed_by_receptionist" value="1"<?= ($formValues['confirmed_by_receptionist'] ?? '') === '1' ? ' checked' : '' ?>>
                        تأیید نهایی پذیرشگر — پرونده از نظر پذیرش تکمیل است
                    </label>
                    <?php m360_rw_intake_form_field('یادداشت تأیید', 'confirmation_note', $formValues['confirmation_note'] ?? '', 'textarea'); ?>
                    <button type="submit" class="m360-rw-btn">ثبت تأیید نهایی</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="m360-rw-panel">
                <h3>چک‌لیست Gate</h3>
                <ul class="m360-rw-checklist">
                    <?php foreach ($gate['checks'] as $chk):
                        if (($chk['id'] ?? '') === 'converted') {
                            continue;
                        }
                        $ok = !empty($chk['ok']);
                    ?>
                    <li class="<?= $ok ? 'is-ok' : 'is-miss' ?>">
                        <?= m360_rw_h((string)($chk['label'] ?? '')) ?>
                        <?php if ($ok && !empty($chk['source'])): ?>
                            <span class="m360-rw-check-src"><?= m360_rw_h((string)$chk['source']) ?></span>
                        <?php elseif (!$ok && !empty($chk['missing_label'])): ?>
                            <span class="m360-rw-check-src"><?= m360_rw_h((string)$chk['missing_label']) ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($jobcardId > 0): ?>
            <div class="m360-rw-panel">
                <h3>کارت کار مرتبط</h3>
                <p>شناسه: <strong><?= m360_rw_h((string)$jobcardId) ?></strong></p>
                <a class="m360-rw-btn m360-rw-btn-secondary" href="erp-reception-jobcard-detail.php?jobcard_id=<?= $jobcardId ?>">مشاهده JobCard</a>
            </div>
            <?php elseif ($canAct && ($gate['status'] ?? '') === 'ready_convert'): ?>
            <div class="m360-rw-panel">
                <h3>آماده تبدیل به کارت کار</h3>
                <p class="m360-rw-muted">آماده تبدیل به کارت کار — تبدیل در فاز کنترل‌شده بعدی یا با اکشن مجاز انجام می‌شود.</p>
                <form method="post" action="erp-reception-online-request-accept.php" onsubmit="return confirm('درخواست به کارت کار تبدیل شود؟');">
                    <?= $csrfConvertHtml ?>
                    <input type="hidden" name="request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action" value="convert_to_jobcard">
                    <button type="submit" class="m360-rw-btn">تبدیل به کارت کار (کنترل‌شده)</button>
                </form>
            </div>
            <?php elseif ($canAct): ?>
            <div class="m360-rw-panel">
                <h3>تبدیل به کارت کار</h3>
                <?php if (in_array((string)($gate['status'] ?? ''), ['temporary_reception', 'complete_unclear_fault'], true)): ?>
                    <p class="m360-rw-warn">تبدیل در پذیرش موقت مجاز نیست — ابتدا دسته‌بندی خدمات و مسیر عیب/خدمت را مشخص کنید.</p>
                <?php else: ?>
                    <p class="m360-rw-warn">تبدیل تا تکمیل پرونده و گذر از گیت پذیرش کامل غیرفعال است.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <nav class="m360-rw-footer">
            <a href="erp-reception-workbench.php">میز کار پذیرش</a>
            <a href="erp-reception-online-requests.php">درخواست‌های آنلاین</a>
            <a href="erp-reception-online-request-detail.php?request_id=<?= $onlineRequestId ?>">جزئیات درخواست</a>
        </nav>
    <?php endif; ?>
</div>
</body>
</html>
