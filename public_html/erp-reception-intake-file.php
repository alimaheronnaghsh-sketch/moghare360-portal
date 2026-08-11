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

/**
 * G2 — operator-facing text sanitizer (presentation only; does not alter workflow values).
 */
function m360_g2_operator_text(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $replacements = [
        'HALL_CANNOT_BYPASS_GATE:' => '',
        'HALL_CANNOT_BYPASS_GATE' => '',
        'گیت پیش‌پرداخت قبل از Step 8' => 'بررسی پیش‌پرداخت',
        'در انتظار گیت پیش‌پرداخت و ارسال Step 8' => 'در انتظار پیش‌پرداخت و ارسال به سالن',
        'گیت Step 8 قبلاً مجاز شده است.' => 'ارسال به سالن قبلاً مجاز شده است.',
        'وضعیت گیت:' => 'وضعیت:',
        'تا مؤثر شدن گیت' => 'تا تعیین تکلیف پیش‌پرداخت',
        'گیت پیش‌پرداخت' => 'پیش‌پرداخت',
        'نیازمند تأیید مشتری / OTP — این وضعیت Gate را عبور نمی‌دهد' => 'تأیید مشتری هنوز تکمیل نشده است.',
        'نیازمند تأیید مشتری / OTP' => 'تأیید مشتری هنوز تکمیل نشده است',
        'موبایل/OTP روشن است؟' => 'تأیید مشتری انجام شده؟',
        'جستجوی مشتری حضوری (موبایل / کد ملی) — OTP اولیه لازم نیست' => 'شناسایی مشتری با موبایل یا کد ملی',
        'تأیید OTP فقط از طریق فرم آنلاین مشتری انجام می‌شود. پذیرش مجاز به ارسال OTP برای مشتری نیست.' => 'تأیید مشتری فقط از مسیر مشتری انجام می‌شود.',
        'این درخواست هنوز توسط مشتری تأیید OTP نشده و قابل مشاهده در پذیرش نیست.' => 'تأیید مشتری هنوز تکمیل نشده است.',
        'اطلاعات پذیرش تکمیل شد. لینک امضا و تأیید قرارداد برای مشتری ارسال شد. مشتری باید با OTP وارد پروفایل خود شود و قرارداد را امضا و تأیید کند.' => 'تأیید مشتری برای ادامه پرونده الزامی است. لینک تأیید برای مشتری ارسال شده است.',
        'پس از تکمیل اطلاعات پذیرش و تحویل خودرو به پذیرش، قرارداد برای مشاهده، امضا و تأیید پیامکی مشتری فعال می‌شود. این مرحله فقط توسط مشتری یا نماینده مجاز مشتری انجام می‌شود و کاربر پذیرش مجاز به امضا یا تأیید به‌جای مشتری نیست.' => 'تأیید مشتری برای ادامه پرونده الزامی است.',
        'پس از تأیید قرارداد و تعیین تکلیف پیش‌پرداخت، پرونده برای بررسی مسئول سالن، تخصیص تیم و ادامه عملیات فنی ارسال می‌شود.' => 'پس از تأیید مشتری و پیش‌پرداخت، پرونده برای ارسال به سالن آماده می‌شود.',
        'تا قبل از امضا و OTP مشتری' => 'تا قبل از تأیید مشتری',
        'امضا و OTP' => 'تأیید مشتری',
        'امضا، ورود OTP یا تأیید قرارداد' => 'تأیید قرارداد به‌جای مشتری',
        'با امضا و OTP' => 'با تأیید مشتری',
        'و OTP قرارداد' => 'و تأیید قرارداد',
        'و OTP' => '',
        ' / OTP' => '',
        'وضعیت OTP' => 'وضعیت تأیید',
        'تأیید OTP' => 'تأیید مشتری',
        'احراز هویت OTP مشتری' => 'تأیید مشتری',
        'لطفاً چک‌لیست پذیرش، دیاگ و توافق اولیه هزینه را در Step 6 تکمیل کنید.' => 'لطفاً مدارک، دیاگ و توافق هزینه را تکمیل کنید.',
        'در Step 6' => 'در بخش مدارک و توافقات',
        'Step 5 و Step 6' => 'بررسی خودرو و مدارک',
        'Step 8' => 'ارسال به سالن',
        'Step 7' => 'تأیید مشتری',
        'Step 6' => 'مدارک و توافقات',
        'Step 5' => 'بررسی خودرو',
        'بازیگر بعدی' => 'اقدام بعدی',
        'ماتریس بازیگر' => '',
        'مسئول: پذیرش' => '',
        'مسئول: مشتری' => '',
        'از پرونده خودرو ERP' => 'از سوابق خودرو',
    ];
    $out = str_replace(array_keys($replacements), array_values($replacements), $text);
    $out = preg_replace('/\bGate\b/', 'وضعیت', $out) ?? $out;
    $out = preg_replace('/\b(Mission|WAVE|Wave|Canonical|Workflow|Engine|SSMS)\b/', '', $out) ?? $out;
    $out = preg_replace('/[ \t]{2,}/u', ' ', $out) ?? $out;

    return trim($out);
}

function m360_g2_status_label_fa(string $raw): string
{
    $key = strtoupper(trim($raw));
    $map = [
        'NEW' => 'جدید',
        'PENDING' => 'در انتظار بررسی',
        'UNDER_REVIEW' => 'در حال بررسی',
        'CONVERTED_TO_JOBCARD' => 'تبدیل‌شده به دستورکار',
        'REJECTED' => 'ردشده',
        'COMPLETED' => 'تکمیل‌شده',
        'LOCKED' => 'قفل‌شده',
        'ACTIVE' => 'فعال',
        'NONE' => 'ثبت نشده',
        'APPROVED' => 'تأیید شده',
        'OWNER_APPROVAL_REQUIRED' => 'در انتظار تأیید مالک',
        'OWNER_REJECTED' => 'ردشده توسط مالک',
        'READY' => 'آماده',
        'BLOCKED' => 'مسدود',
        'ALLOWED' => 'مجاز',
        'DENIED' => 'غیرمجاز',
    ];
    if (isset($map[$key])) {
        return $map[$key];
    }
    if ($key === '' || preg_match('/^[A-Z0-9_]+$/', $key) === 1) {
        return $key !== '' ? 'در حال پیگیری' : '—';
    }

    return m360_g2_operator_text($raw);
}

function m360_g2_gate_label_fa(array $gate): string
{
    $status = strtolower(trim((string)($gate['status'] ?? '')));
    $byStatus = [
        'needs_completion' => 'نیازمند تکمیل اطلاعات',
        'needs_otp' => 'تأیید مشتری هنوز تکمیل نشده است',
        'ready_full_reception' => 'آماده تکمیل پذیرش',
        'ready_convert' => 'آماده ارسال به سالن',
        'pending_step8_gate' => 'در انتظار پیش‌پرداخت و ارسال به سالن',
        'converted' => 'تبدیل‌شده به دستورکار',
        'rejected' => 'ردشده',
    ];
    if (isset($byStatus[$status])) {
        return $byStatus[$status];
    }

    return m360_g2_operator_text((string)($gate['label_fa'] ?? 'در حال پیگیری'));
}

/** @return array<string, string> */
function m360_g2_step_labels_fa(): array
{
    return [
        'otp' => 'شناسایی',
        'customer' => 'مشتری',
        'vehicle' => 'خودرو',
        'service' => 'خدمات',
        'condition' => 'بررسی خودرو',
        'documents' => 'مدارک و توافقات',
        'signature' => 'تأیید مشتری',
        'referral' => 'آماده‌سازی پذیرش',
        'locked_summary' => 'خلاصه قفل‌شده',
    ];
}

function m360_g2_step_label_fa(string $stepKey): string
{
    $labels = m360_g2_step_labels_fa();

    return $labels[$stepKey] ?? 'بخش فعال';
}

/**
 * @param list<string> $missing
 * @return list<string>
 */
function m360_g2_missing_labels(array $missing): array
{
    $out = [];
    foreach ($missing as $item) {
        $clean = m360_g2_operator_text((string)$item);
        if ($clean !== '') {
            $out[] = $clean;
        }
    }

    return $out;
}

/**
 * Sanitize helper-rendered HTML without altering href/action/src/value attributes.
 */
function m360_g2_sanitize_html_fragment(string $html): string
{
    if ($html === '') {
        return '';
    }
    $parts = preg_split(
        '/(\s+(?:href|action|src|value|data-href|data-[a-z0-9_-]+)\s*=\s*(?:"[^"]*"|\'[^\']*\'))/i',
        $html,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );
    if ($parts === false) {
        return $html;
    }
    $out = '';
    foreach ($parts as $i => $part) {
        if ($i % 2 === 1) {
            $out .= $part;
            continue;
        }
        // Phrase replace only — do not trim whole HTML chunks.
        $chunk = str_replace(
            [
                'لطفاً چک‌لیست پذیرش، دیاگ و توافق اولیه هزینه را در Step 6 تکمیل کنید.',
                'گیت پیش‌پرداخت قبل از Step 8',
                'تا قبل از امضا و OTP مشتری',
                'HALL_CANNOT_BYPASS_GATE:',
                'HALL_CANNOT_BYPASS_GATE',
                'Step 8',
                'Step 7',
                'Step 6',
                'Step 5',
                'وضعیت گیت:',
                'بازیگر بعدی',
            ],
            [
                'لطفاً مدارک، دیاگ و توافق هزینه را تکمیل کنید.',
                'بررسی پیش‌پرداخت',
                'تا قبل از تأیید مشتری',
                '',
                '',
                'ارسال به سالن',
                'تأیید مشتری',
                'مدارک و توافقات',
                'بررسی خودرو',
                'وضعیت:',
                'اقدام بعدی',
            ],
            $part
        );
        $chunk = preg_replace('/\bGate\b/', 'وضعیت', $chunk) ?? $chunk;
        $chunk = str_replace(
            m360_rw_canonical_contract_step_text_fa(),
            'تأیید مشتری برای ادامه پرونده الزامی است.',
            $chunk
        );
        $out .= $chunk;
    }

    return $out;
}

function m360_g2_echo_sanitized_render(callable $renderer): void
{
    ob_start();
    $renderer();
    echo m360_g2_sanitize_html_fragment((string)ob_get_clean());
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 */
function m360_g2_render_wizard_progress(int $onlineRequestId, string $activeStep, array $payload, array $request, array $formValues): void
{
    $furthest = m360_rw_intake_is_locked($payload)
        ? 'locked_summary'
        : m360_rw_intake_wizard_furthest_operational_step($request, $payload, $formValues);
    $operational = m360_rw_intake_wizard_operational_keys();
    $furIdx = array_search($furthest, $operational, true);
    if ($furIdx === false) {
        $furIdx = count($operational) - 1;
    }
    $stepper = m360_rw_intake_stepper_definition($request);
    $labels = m360_g2_step_labels_fa();

    echo '<nav class="m360-rw-wizard-progress" aria-label="پیشرفت پذیرش" data-m360-stage-count="8">';
    echo '<ol class="m360-rw-wizard-progress-track">';
    foreach ($stepper as $stepKey => $meta) {
        if ($stepKey === 'locked_summary') {
            continue;
        }
        $complete = m360_rw_intake_wizard_step_is_complete($stepKey, $payload, $request, $formValues);
        $isActive = $stepKey === $activeStep;
        $opIdx = array_search($stepKey, $operational, true);
        $isFuture = $opIdx !== false && $opIdx > $furIdx;
        $cls = 'm360-rw-wizard-progress-item';
        if ($isActive) {
            $cls .= ' is-active';
        } elseif ($complete) {
            $cls .= ' is-done';
        } elseif ($isFuture) {
            $cls .= ' is-future';
        } else {
            $cls .= ' is-pending';
        }
        echo '<li class="' . m360_rw_h($cls) . '" data-stage-key="' . m360_rw_h($stepKey) . '">';
        $canLink = !$isActive && $opIdx !== false && $onlineRequestId > 0;
        if ($canLink) {
            $href = 'erp-reception-intake-file.php?online_request_id=' . $onlineRequestId
                . '&active_step=' . rawurlencode($stepKey)
                . '#' . rawurlencode((string)$meta['hash']);
            echo '<a class="m360-rw-wizard-progress-link" href="' . m360_rw_h($href) . '">';
        }
        echo '<span class="m360-rw-wizard-progress-num">' . m360_rw_h((string)$meta['num']) . '</span>';
        echo '<span class="m360-rw-wizard-progress-title">' . m360_rw_h($labels[$stepKey] ?? (string)$meta['label']) . '</span>';
        if ($complete) {
            $status = 'تکمیل';
        } elseif ($isFuture) {
            $status = 'بعدی';
        } elseif ($isActive) {
            $status = 'فعال';
        } else {
            $status = 'نیازمند';
        }
        echo '<span class="m360-rw-wizard-progress-status">' . m360_rw_h($status) . '</span>';
        if ($canLink) {
            echo '</a>';
        }
        echo '</li>';
    }
    echo '</ol></nav>';
}

/**
 * @param array<string, mixed>|null $request
 * @param array<string, mixed> $payload
 * @param array<string, string> $formValues
 */
function m360_g2_render_case_strip(?array $request, array $payload, array $formValues, string $activeStep, int $onlineRequestId): void
{
    $customerName = trim((string)($request['customer_name'] ?? ''));
    if ($customerName === '') {
        $customerName = trim((string)($payload['reception_intake']['customer']['full_name'] ?? ''));
    }
    $plate = trim((string)($formValues['plate'] ?? ''));
    if ($plate === '') {
        $plate = trim((string)($payload['reception_intake']['vehicle']['plate'] ?? ''));
    }
    $vehicleLabel = trim(implode(' ', array_filter([
        (string)($formValues['brand'] ?? $payload['reception_intake']['vehicle']['brand'] ?? ''),
        (string)($formValues['model'] ?? $payload['reception_intake']['vehicle']['model'] ?? ''),
    ])));

    echo '<section class="m360-rw-case-strip" aria-label="خلاصه پرونده">';
    echo '<div class="m360-rw-case-strip__row">';
    echo '<span><strong>پرونده پذیرش شماره ' . m360_rw_h((string)$onlineRequestId) . '</strong></span>';
    echo '<span>بخش فعال: ' . m360_rw_h(m360_g2_step_label_fa($activeStep)) . '</span>';
    echo '</div>';
    echo '<div class="m360-rw-case-strip__row">';
    echo '<span>مشتری: ' . m360_rw_h($customerName !== '' ? $customerName : '—') . '</span>';
    echo '<span>خودرو: ' . m360_rw_h($vehicleLabel !== '' ? $vehicleLabel : '—') . '</span>';
    echo '<span>پلاک: ' . m360_rw_h($plate !== '' ? $plate : '—') . '</span>';
    echo '</div>';
    echo '</section>';
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $request
 * @param array<string, string> $formValues
 * @param array{label_fa:string,show_verify_form:bool,verified:bool,status_code:string} $otpStatusUi
 * @param array{available:bool,reason_fa:string} $otpSend
 */
function m360_g2_render_otp_wizard_block(
    int $onlineRequestId,
    array $payload,
    array $request,
    array $formValues,
    array $otpStatusUi,
    array $otpSend,
    bool $canShowStepForm,
    bool $otpVerified,
    string $otpMobile,
    string $csrfInputHtml,
    string $saveUrl
): void {
    echo '<div class="m360-rw-otp-step-block" id="step-otp" data-stage-key="otp">';
    if (m360_online_req_is_staff_walkin($request)) {
        echo '<p class="m360-rw-flash is-info">شناسایی مشتری برای این پرونده حضوری انجام شده است.</p>';
        echo '<div class="m360-rw-field-grid m360-rw-step-compact">';
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">موبایل پرونده</span>';
        echo '<span class="m360-rw-field-val">' . m360_rw_h($otpMobile !== '' ? $otpMobile : '—') . '</span></div>';
        echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">وضعیت شناسایی</span>';
        echo '<span class="m360-rw-field-val">' . m360_rw_h(m360_g2_operator_text((string)($otpStatusUi['label_fa'] ?? '—'))) . '</span></div>';
        echo '</div></div>';

        return;
    }

    echo '<div class="m360-rw-field-grid m360-rw-step-compact">';
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">موبایل فعلی</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h($otpMobile !== '' ? $otpMobile : '—') . '</span></div>';
    echo '<div class="m360-rw-field"><span class="m360-rw-field-lbl">وضعیت تأیید</span>';
    echo '<span class="m360-rw-field-val">' . m360_rw_h(m360_g2_operator_text((string)($otpStatusUi['label_fa'] ?? '—'))) . '</span></div>';
    echo '</div>';

    if ($otpVerified) {
        echo '<p class="m360-rw-flash is-ok">تأیید مشتری ثبت شده است.</p>';
        echo '</div>';

        return;
    }

    echo '<p class="m360-rw-warn">تأیید مشتری هنوز تکمیل نشده است.</p>';
    echo '<p class="m360-rw-muted">کد تأیید به شماره ثبت‌شده مشتری ارسال می‌شود.</p>';

    if (!$canShowStepForm) {
        echo '</div>';

        return;
    }

    echo '<form class="m360-rw-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
    echo $csrfInputHtml;
    echo '<input type="hidden" name="online_request_id" value="' . $onlineRequestId . '">';
    echo '<input type="hidden" name="action_type" value="save_mobile_correction">';
    m360_rw_intake_return_step_hidden('otp');
    echo '<div class="m360-rw-form-field"><label class="m360-rw-form-label" for="mobile_corrected">موبایل اصلاح‌شده <span class="m360-rw-req">*</span></label>';
    echo '<input class="m360-rw-form-input" type="tel" id="mobile_corrected" name="mobile_corrected" value="' . m360_rw_h($formValues['mobile_corrected'] ?? $otpMobile) . '" required></div>';
    echo '<button type="submit" class="m360-rw-btn m360-rw-btn-secondary">ذخیره</button>';
    echo '</form>';
    echo '</div>';
}

/**
 * @param resource|false $conn
 * @param array<string, mixed> $request
 * @param array<string, mixed> $payload
 * @param array<string, mixed>|null $jobcard
 */
function m360_g2_render_prepayment_card(
    $conn,
    int $onlineRequestId,
    array $request,
    array $payload,
    ?array $jobcard,
    string $csrfInputHtml,
    string $saveUrl,
    bool $canAct
): void {
    $state = m360_rw_intake_prepayment_state_for_context($conn, $request, $payload, $jobcard);
    $actor = m360_intake_prepayment_actor_context($conn);
    $ownerStatus = (string)($state['owner_decision_status'] ?? 'NONE');
    $contractSigned = !empty($state['contract_signed']);
    $allow = !empty($state['allow_handoff']);
    $ownerRequestPending = $ownerStatus === M360_INTAKE_PREPAYMENT_GATE_OWNER_APPROVAL_REQUIRED;
    $ownerRejected = $ownerStatus === M360_INTAKE_PREPAYMENT_GATE_OWNER_REJECTED;

    echo '<section class="m360-rw-card m360-rw-prepayment-gate-card">';
    echo '<h3>بررسی پیش‌پرداخت</h3>';
    echo '<p class="' . ($allow ? 'm360-rw-flash is-ok' : 'm360-rw-warn') . '">وضعیت: <strong>'
        . m360_rw_h(m360_g2_status_label_fa((string)($state['gate_status'] ?? ''))) . '</strong> — '
        . m360_rw_h(m360_g2_operator_text((string)($state['label'] ?? ''))) . '</p>';
    if (!empty($state['message'])) {
        echo '<p class="m360-rw-muted">' . m360_rw_h(m360_g2_operator_text((string)$state['message'])) . '</p>';
    }
    if (!empty($state['payment_backend_gap'])) {
        echo '<p class="m360-rw-warn">ثبت پرداخت در این محیط محدود است؛ برای ادامه بدون پیش‌پرداخت، تأیید مالک لازم است.</p>';
    }
    echo '<div class="m360-rw-field-grid">';
    m360_rw_intake_field('قرارداد تأیید شده', m360_rw_intake_yes_no_fa($contractSigned));
    m360_rw_intake_field('پیش‌پرداخت لازم است', m360_rw_intake_yes_no_fa(!empty($state['prepayment_required'])));
    m360_rw_intake_field('مبلغ لازم', m360_rw_intake_prepayment_amount_label($state['required_amount'] ?? 0));
    m360_rw_intake_field('مبلغ پرداخت/ثبت‌شده', m360_rw_intake_prepayment_amount_label($state['paid_amount'] ?? 0));
    m360_rw_intake_field('پرداخت تأیید شده', m360_rw_intake_yes_no_fa(!empty($state['payment_confirmed'])));
    m360_rw_intake_field('وضعیت تصمیم مالک', m360_g2_status_label_fa($ownerStatus));
    echo '</div>';

    if (!$canAct) {
        echo '</section>';

        return;
    }

    if (!$contractSigned) {
        echo '<p class="m360-rw-warn">تأیید مشتری هنوز تکمیل نشده است.</p>';
        echo '</section>';

        return;
    }

    if (!$allow && !$ownerRequestPending && !$ownerRejected) {
        echo '<form class="m360-rw-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
        echo $csrfInputHtml;
        echo '<input type="hidden" name="online_request_id" value="' . (int)$onlineRequestId . '">';
        echo '<input type="hidden" name="action_type" value="request_start_without_prepayment">';
        m360_rw_intake_return_step_hidden('referral');
        echo '<input type="hidden" name="return_section" value="section-referral">';
        m360_rw_intake_form_field('دلیل درخواست شروع بدون پیش‌پرداخت', 'prepayment_gate_reason', '', 'textarea', true);
        echo '<button type="submit" class="m360-rw-btn m360-rw-btn-secondary">درخواست شروع بدون پیش‌پرداخت</button>';
        echo '</form>';
    }

    if ($ownerRequestPending && !empty($actor['can_approve'])) {
        foreach ([
            'approve_start_without_prepayment' => 'تأیید شروع بدون پیش‌پرداخت',
            'reject_start_without_prepayment' => 'رد شروع بدون پیش‌پرداخت',
            'return_start_without_prepayment' => 'برگشت برای اصلاح',
        ] as $action => $label) {
            echo '<form class="m360-rw-form" method="post" action="' . m360_rw_h($saveUrl) . '">';
            echo $csrfInputHtml;
            echo '<input type="hidden" name="online_request_id" value="' . (int)$onlineRequestId . '">';
            echo '<input type="hidden" name="action_type" value="' . m360_rw_h($action) . '">';
            m360_rw_intake_return_step_hidden('referral');
            echo '<input type="hidden" name="return_section" value="section-referral">';
            m360_rw_intake_form_field('دلیل تصمیم مالک/مدیر مجاز', 'prepayment_gate_reason', '', 'textarea', true);
            echo '<button type="submit" class="m360-rw-btn">' . m360_rw_h($label) . '</button>';
            echo '</form>';
        }
    } elseif ($ownerRequestPending) {
        echo '<p class="m360-rw-muted">تصمیم در انتظار مالک یا مدیر مجاز است.</p>';
    }

    if (!$allow) {
        echo '<p class="m360-rw-warn">تا تعیین تکلیف پیش‌پرداخت، ارسال به سالن امکان‌پذیر نیست.</p>';
    }

    echo '</section>';
}

$m360LuxCssPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'moghare360-v1-luxury-ui.css';
$m360MirrorCssPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mirror.css';
$m360RwJsPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'm360-reception-intake.js';
$m360LuxCssVer = 'p1-unification-' . (is_file($m360LuxCssPath) ? (string)filemtime($m360LuxCssPath) : '1');
$m360MirrorCssVer = 'p1-unification-' . (is_file($m360MirrorCssPath) ? (string)filemtime($m360MirrorCssPath) : '1');
$m360RwJsVer = 'p1-unification-' . (is_file($m360RwJsPath) ? (string)filemtime($m360RwJsPath) : '1');
$gateLabelFa = m360_g2_gate_label_fa(is_array($gate) ? $gate : []);
$gateMissingFa = m360_g2_missing_labels(is_array($gate['missing'] ?? null) ? $gate['missing'] : []);
$flashMsg = m360_g2_operator_text($flashMsg);
$pageStepTitle = m360_g2_step_label_fa((string)$activeStep);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>تکمیل پرونده پذیرش — ماهین 360°</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css?v=<?= m360_rw_h($m360LuxCssVer) ?>">
    <link rel="stylesheet" href="assets/css/mirror.css?v=<?= m360_rw_h($m360MirrorCssVer) ?>">
</head>
<body class="m360-public-shell m360-rw-page m360-rw-wizard-page m360-rw-focused-step m360-rw-focused-<?= m360_rw_h((string)$activeStep) ?>">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header m360-rw-header--compact">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>
            <span class="m360-rw-gate-chip <?= m360_rw_h($gateClass) ?>"><?= m360_rw_h($gateLabelFa) ?></span>
        </div>
        <h1 class="m360-rw-title">تکمیل پرونده پذیرش</h1>
        <?php if ($onlineRequestId > 0): ?>
            <p class="m360-rw-subtitle">پرونده پذیرش شماره <?= m360_rw_h((string)$onlineRequestId) ?></p>
        <?php endif; ?>
        <p class="m360-rw-subtitle">اطلاعات، مدارک و توافقات پرونده را تکمیل کنید.</p>
        <?php if ($flashMsg !== ''): ?>
            <div class="m360-rw-flash <?= $flashOk ? 'is-ok' : 'is-err' ?>"><?= m360_rw_h($flashMsg) ?></div>
        <?php endif; ?>
        <?php m360_rw_intake_render_payload_invalid_notice($payloadMeta); ?>
        <?php m360_rw_intake_render_diagnostic_request_notice($onlineRequestId); ?>
        <?php if (!empty($signoffStatus['blocked'])): ?>
            <div class="m360-rw-flash is-warn" role="status"><?= m360_rw_h(m360_g2_operator_text((string)$signoffStatus['reason_fa'])) ?></div>
        <?php endif; ?>
        <?php if ($jobcardId > 0): ?>
            <div class="m360-rw-flash is-info" role="status">
                قبلاً به دستورکار شماره <?= m360_rw_h((string)$jobcardId) ?> تبدیل شده است.
                <?php if (!m360_rw_intake_contract_customer_accepted($payloadData)): ?>
                    تأیید مشتری هنوز تکمیل نشده است و قابل حذف یا دورزدن نیست.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($request !== null): ?>
        <?php m360_g2_render_case_strip($request, $payloadData, $formValues, (string)$activeStep, $onlineRequestId); ?>
    <?php endif; ?>

    <?php if ($request === null): ?>
        <section class="m360-rw-alert">درخواست یافت نشد یا شناسه نامعتبر است.</section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به درخواست‌های آنلاین</a>
        </div>
    <?php elseif ($otpAccessBlocked): ?>
        <section class="m360-rw-alert"><?= m360_rw_h(m360_g2_operator_text(M360_RW_RECEPTION_UNVERIFIED_ACCESS_MESSAGE_FA)) ?></section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به درخواست‌های آنلاین</a>
        </div>
    <?php else: ?>

        <div class="m360-rw-wizard-layout">
            <aside class="m360-rw-wizard-aside" aria-label="وضعیت پرونده">
                <div class="m360-rw-gate-compact">
                    <h2 class="m360-rw-gate-compact-title">وضعیت پرونده</h2>
                    <p class="m360-rw-gate-status"><?= m360_rw_h($gateLabelFa) ?></p>
                    <?php if ($gateMissingFa !== []): ?>
                        <ul class="m360-rw-gate-compact-miss">
                            <?php foreach (array_slice($gateMissingFa, 0, 4) as $miss): ?>
                                <li><?= m360_rw_h($miss) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>
            <main class="m360-rw-wizard-main">
                <?php m360_g2_render_wizard_progress($onlineRequestId, $activeStep, $payloadData, $request, $formValues); ?>

                <article class="m360-rw-wizard-page-card" id="<?= m360_rw_h((string)($wizardStepDef['hash'] ?? 'step-wizard')) ?>">
                    <header class="m360-rw-wizard-page-head">
                        <span class="m360-rw-wizard-page-num"><?= m360_rw_h((string)($wizardStepDef['num'] ?? '')) ?></span>
                        <h2 class="m360-rw-wizard-page-title"><?= m360_rw_h($pageStepTitle) ?></h2>
                        <?php if ($isLocked): ?>
                            <span class="m360-rw-wizard-lock-badge">قفل شده</span>
                        <?php elseif ($wizardEditMode): ?>
                            <span class="m360-rw-wizard-edit-badge">اصلاح قبل از تأیید</span>
                        <?php endif; ?>
                    </header>

                    <?php if ($request !== null): ?>
                        <?php
                        m360_g2_echo_sanitized_render(static function () use ($payloadData, $request, $activeStep): void {
                            m360_rw_intake_render_wizard_blocker_notice($payloadData, $request, $activeStep);
                        });
                        ?>
                    <?php endif; ?>

                    <?php if ($prevAmendStep !== null && !$wizardEditMode && !$isLocked && $activeStep !== 'signature'): ?>
                        <p class="m360-rw-wizard-amend-link">
                            <a href="<?= m360_rw_h(m360_rw_intake_wizard_amend_url($onlineRequestId, $prevAmendStep)) ?>">بازگشت برای اصلاح</a>
                        </p>
                    <?php endif; ?>

                    <?php switch ($activeStep):
                        case 'otp': ?>
                            <div class="m360-rw-wizard-step-body">
                                <?php m360_g2_render_otp_wizard_block(
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $formValues,
                                    $otpStatusUi,
                                    $otpSend,
                                    $canShowStepForm,
                                    $otpVerified,
                                    $otpMobile,
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
                                    m360_rw_intake_field_recovered('مدل', ['value' => $vehicleCanon['model'], 'source_label' => 'از سوابق خودرو']);
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
                                <section class="m360-rw-intake-block" id="section-condition-photos" data-m360-photo-panel="1" aria-label="تصاویر خودرو">
                                    <h3 class="m360-rw-intake-block__title">تصاویر خودرو</h3>
                                    <div class="m360-rw-flash is-info" id="m360_rw_photo_controls_banner" data-m360-photo-controls="1">
                                        عکس‌ها فقط از دوربین ثبت می‌شوند. پس از ثبت هر ۶ عکس، تأیید نهایی الزامی است.
                                    </div>
                                    <div id="m360_rw_photo_controls_root">
                                <?php
                                m360_g2_echo_sanitized_render(static function () use (
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $formValues,
                                    $editSection,
                                    $canShowStepForm,
                                    $csrfInputHtml,
                                    $saveUrl
                                ): void {
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
                                });
                                if ($canShowStepForm): ?>
                                    <p class="m360-rw-muted m360-rw-step5-docs-hint">
                                        گزارش دیاگ و مدارک PDF در بخش مدارک بارگذاری می‌شود —
                                        <a class="m360-rw-btn m360-rw-btn-secondary" href="<?= m360_rw_h(m360_rw_intake_step_go_url($onlineRequestId, 'documents')) ?>#section-diagnostic-pdf">ادامه به مدارک</a>
                                    </p>
                                <?php endif; ?>
                                    </div>
                                </section>
                                <section class="m360-rw-intake-block" id="section-condition-damage" aria-label="وضعیت و بررسی خودرو">
                                    <h3 class="m360-rw-intake-block__title">وضعیت و بررسی خودرو</h3>
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
                                <?php
                                m360_g2_echo_sanitized_render(static function () use (
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
                                ): void {
                                    m360_rw_intake_render_service_wizard_block(
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
                                    );
                                });
                                ?>
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
                                <p class="m360-rw-muted">پس از تأیید مشتری و پیش‌پرداخت، پرونده را به سالن ارسال کنید.</p>
                                <?php m360_g2_render_prepayment_card($conn, $onlineRequestId, $request, $payloadData, $jobcard, $csrfInputHtml, $saveUrl, $canAct); ?>
                                <?php if ($jobcardId > 0): ?>
                                    <p class="m360-rw-flash is-ok">قبلاً به دستورکار شماره <?= m360_rw_h((string)$jobcardId) ?> تبدیل شده است.</p>
                                    <?php if (!m360_rw_intake_contract_customer_accepted($payloadData)): ?>
                                        <p class="m360-rw-warn">تأیید مشتری هنوز تکمیل نشده است.</p>
                                    <?php endif; ?>
                                    <?php if (!$hallManagerAllowed): ?>
                                        <p class="m360-rw-warn">تا تعیین تکلیف پیش‌پرداخت، شروع عملیات مجاز نیست.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php
                                if ($hm['status'] !== ''): ?>
                                    <p class="m360-rw-flash is-ok">وضعیت: <?= m360_rw_h(m360_g2_status_label_fa((string)$hm['status'])) ?></p>
                                    <?php if ($hm['sent_at'] !== ''): ?>
                                        <p class="m360-rw-muted">زمان ارسال: <?= m360_rw_h($hm['sent_at']) ?></p>
                                    <?php endif; ?>
                                <?php elseif (!$hallManagerAllowed): ?>
                                    <p class="m360-rw-warn"><?= m360_rw_h(m360_g2_operator_text(m360_rw_intake_operation_gate_message_fa($payloadData))) ?></p>
                                <?php elseif ($canAct && !m360_rw_intake_hall_manager_step_complete($payloadData)): ?>
                                <form class="m360-rw-form" method="post" action="<?= m360_rw_h($saveUrl) ?>">
                                    <?= $csrfInputHtml ?>
                                    <input type="hidden" name="online_request_id" value="<?= $onlineRequestId ?>">
                                    <input type="hidden" name="action_type" value="send_to_hall_manager">
                                    <?php m360_rw_intake_return_step_hidden('referral'); ?>
                                    <p class="m360-rw-muted">پرونده آماده ارسال است.</p>
                                    <?php m360_rw_intake_form_field('یادداشت (اختیاری)', 'hall_manager_note', $formValues['hall_manager_note'] ?? '', 'textarea'); ?>
                                    <button type="submit" class="m360-rw-btn">ارسال به سالن</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php break;

                        case 'documents': ?>
                            <div class="m360-rw-wizard-step-body m360-rw-documents-focused" data-m360-active-step="documents">
                                <section class="m360-rw-intake-block" id="section-documents-root" aria-label="مدارک و توافقات">
                                <h3 class="m360-rw-intake-block__title">مدارک و توافقات</h3>
                                <?php
                                $conditionStepComplete = !empty($wizardStepState['steps']['condition']['complete']);
                                if (!$conditionStepComplete): ?>
                                    <div class="m360-rw-flash is-err" id="m360_rw_documents_prereq_lock" role="alert">
                                        برای ذخیره مدارک، ابتدا تصاویر خودرو را تکمیل کنید.
                                    </div>
                                    <p class="m360-rw-actions">
                                        <a class="m360-rw-btn" href="<?= m360_rw_h(m360_rw_intake_step_go_url($onlineRequestId, 'condition')) ?>#section-condition-photos">
                                            بازگشت به تصاویر خودرو
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <?php
                                $receptionComplete = m360_rw_intake_reception_is_completed($payloadData);
                                if ($receptionComplete): ?>
                                    <p class="m360-rw-flash is-ok"><?= m360_rw_h(m360_g2_operator_text(m360_rw_reception_contract_link_sent_message_fa())) ?></p>
                                <?php endif; ?>

                                <?php
                                m360_rw_intake_render_documents_files_list($payloadData);
                                ?>

                                <?php if ($canShowStepForm): ?>
                                <section class="m360-rw-doc-upload" id="section-diagnostic-pdf" aria-label="بارگذاری مدرک جدید" data-m360-doc-upload="1">
                                    <h3 class="m360-rw-section-title">بارگذاری مدرک جدید</h3>
                                    <p class="m360-rw-muted">هر بارگذاری یک مدرک جدید اضافه می‌کند.</p>
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
                                m360_g2_echo_sanitized_render(static function () use (
                                    $payloadData,
                                    $formValues,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    $onlineRequestId
                                ): void {
                                    m360_rw_intake_render_agreements_section(
                                        $payloadData,
                                        $formValues,
                                        true,
                                        $csrfInputHtml,
                                        $saveUrl,
                                        $onlineRequestId
                                    );
                                });
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
                                    <p class="m360-rw-muted">تأیید مشتری برای ادامه پرونده الزامی است.</p>
                                    <button type="submit" class="m360-rw-btn">تکمیل پذیرش</button>
                                </form>
                                <?php endif; ?>
                                <?php else: ?>
                                <?php
                                m360_g2_echo_sanitized_render(static function () use (
                                    $payloadData,
                                    $formValues,
                                    $saveUrl,
                                    $onlineRequestId
                                ): void {
                                    m360_rw_intake_render_agreements_section(
                                        $payloadData,
                                        $formValues,
                                        false,
                                        '',
                                        $saveUrl,
                                        $onlineRequestId
                                    );
                                });
                                ?>
                                <?php endif; ?>
                                <?php
                                m360_g2_echo_sanitized_render(static function () use (
                                    $onlineRequestId,
                                    $payloadData,
                                    $request,
                                    $csrfInputHtml,
                                    $saveUrl,
                                    $canShowStepForm
                                ): void {
                                    m360_rw_intake_render_documents_contract_staff_block(
                                        $onlineRequestId,
                                        $payloadData,
                                        $request,
                                        $csrfInputHtml,
                                        $saveUrl,
                                        $canShowStepForm
                                    );
                                });
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
                                <ul class="m360-rw-signature-checklist">
                                    <?php $contractAcceptedUi = m360_rw_intake_contract_customer_accepted($payloadData); ?>
                                    <li class="<?= $contractAcceptedUi ? 'is-done' : 'is-info' ?>">مشاهده قرارداد توسط مشتری</li>
                                    <li class="<?= $contractAcceptedUi ? 'is-done' : 'is-info' ?>">امضای مستقیم مشتری یا نماینده مجاز</li>
                                    <li class="<?= $contractAcceptedUi ? 'is-done' : 'is-info' ?>">تأیید پیامکی قرارداد</li>
                                </ul>
                                <?php
                                $contractPending = m360_rw_intake_contract_cartable_pending($payloadData)
                                    || trim((string)($payloadData['contract_status'] ?? '')) === M360_RW_INTAKE_CONTRACT_STATUS_PENDING_CUSTOMER_REVIEW;
                                $contractAccepted = $contractAcceptedUi;
                                if ($contractAccepted): ?>
                                    <p class="m360-rw-flash is-ok"><?= m360_rw_h(m360_g2_operator_text(m360_rw_contract_accepted_customer_message_fa())) ?></p>
                                <?php elseif ($contractPending): ?>
                                    <p class="m360-rw-flash is-info"><?= m360_rw_h(m360_g2_operator_text(m360_rw_contract_pending_customer_message_fa())) ?></p>
                                    <?php
                                    $smsMeta = is_array($payloadData['reception_intake']['operation_gate']['contract_sms'] ?? null)
                                        ? $payloadData['reception_intake']['operation_gate']['contract_sms']
                                        : [];
                                    if (($smsMeta['sent'] ?? false) === true): ?>
                                        <p class="m360-rw-muted">پیامک اطلاع‌رسانی قرارداد ارسال شد.</p>
                                    <?php elseif (trim((string)($smsMeta['skipped_reason'] ?? '')) === 'sms_not_configured'): ?>
                                        <p class="m360-rw-muted">ارسال پیامک قرارداد در این محیط پیکربندی نشده است.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="m360-rw-muted">تأیید مشتری هنوز تکمیل نشده است.</p>
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
                                <p class="m360-rw-warn">پذیرش مجاز به تأیید قرارداد به‌جای مشتری نیست.</p>
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
                                <p class="m360-rw-muted">مسیر اصلاحیه در حال حاضر غیرفعال است.</p>
                            </div>
                        <?php break; endswitch; ?>
                </article>

                <?php if (!$isLocked && $request !== null): ?>
                    <?php
                    m360_g2_echo_sanitized_render(static function () use ($onlineRequestId, $activeStep): void {
                        m360_rw_intake_render_stepper_footer_nav($onlineRequestId, $activeStep);
                    });
                    ?>
                <?php endif; ?>

                <?php if ($isLocked): ?>
                    <div class="m360-rw-wizard-amendment-placeholder">
                        <button type="button" class="m360-rw-btn m360-rw-btn-secondary" disabled>درخواست اصلاحیه پذیرش</button>
                        <p class="m360-rw-muted">مسیر اصلاحیه در حال حاضر غیرفعال است.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        </div>

        <nav class="m360-rw-footer">
            <a href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>
            <a href="erp-reception-online-requests.php">درخواست‌های آنلاین</a>
            <a href="erp-reception-online-request-detail.php?request_id=<?= $onlineRequestId ?>">جزئیات درخواست</a>
        </nav>
    <?php endif; ?>
</div>
<script src="assets/js/vehicle-brand-classes.js"></script>
<script src="assets/js/m360-reception-intake.js?v=<?= m360_rw_h($m360RwJsVer) ?>"></script>
</body>
</html>
