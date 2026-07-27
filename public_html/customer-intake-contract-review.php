<?php
declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

$rawToken = trim((string)($_GET['t'] ?? $_GET['token'] ?? $_POST['t'] ?? $_POST['token'] ?? ''));
$taskId = (int)($_GET['task_id'] ?? $_POST['task_id'] ?? 0);
$flashMsg = trim((string)($_GET['msg'] ?? ''));
$flashOk = (string)($_GET['ok'] ?? '') === '1';
$isAjax = (string)($_POST['ajax'] ?? '') === '1'
    || stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
$error = '';
$request = null;
$payload = [];
$summary = [];
$contractHtml = '';
$contractRow = null;
$otpVerified = false;
$accepted = false;
$workflow = [];
$entryMode = '';
$readOnly = false;
$invalidMessage = 'مأموریت قرارداد مشتری یافت نشد یا منقضی شده است.';

function m360_contract_review_json_response(array $payload, int $status = 200): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function m360_contract_review_return_url(string $entryMode, int $taskId, string $rawToken): string
{
    if ($entryMode === M360_CONTRACT_ENTRY_TASK && $taskId > 0) {
        return 'customer-intake-contract-review.php?task_id=' . (string)$taskId;
    }
    if ($rawToken !== '') {
        return 'customer-intake-contract-review.php?t=' . rawurlencode($rawToken);
    }

    return 'customer-profile.php';
}

$conn = customer_core_db();
$contextInput = ['token' => $rawToken, 'task_id' => $taskId];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($taskId > 0 || $rawToken !== '')) {
    $action = trim((string)($_POST['workflow_action'] ?? ''));
    if ($conn === false) {
        if ($isAjax) {
            m360_contract_review_json_response([
                'ok' => false,
                'message' => 'خطا در اتصال به سامانه.',
                'error_code' => 'db_unavailable',
            ], 503);
        }
        $error = 'اتصال به پایگاه داده برقرار نشد.';
    } else {
        $context = m360_contract_resolve_customer_context($conn, $contextInput);
        if (!empty($context['redirect_login'])) {
            header('Location: ' . m360_rw_customer_profile_unauthenticated_redirect_url(), true, 302);
            exit;
        }
        if (!$context['ok'] || !is_array($context['contract'])) {
            if ($isAjax) {
                m360_contract_review_json_response([
                    'ok' => false,
                    'message' => $context['message'],
                    'error_code' => 'context_invalid',
                    'workflow_state' => 'CONTRACT_OPEN',
                    'signature_locked' => false,
                    'otp_send_status' => 'not_requested',
                    'masked_destination' => '',
                ], (int)($context['http_status'] ?? 403));
            }
            $returnUrl = m360_contract_review_return_url(
                (string)($context['entry_mode'] ?? ''),
                (int)($context['task_id'] ?? $taskId),
                (string)($context['raw_token'] ?? $rawToken)
            );
            header('Location: ' . $returnUrl . '&msg=' . rawurlencode((string)$context['message']) . '&ok=0');
            exit;
        }

        $entryMode = (string)$context['entry_mode'];
        $taskId = (int)($context['task_id'] ?? $taskId);
        $rawToken = (string)($context['raw_token'] ?? $rawToken);
        $contractRowPost = $context['contract'];
        $result = ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
        $otpResult = null;

        if (!empty($context['read_only'])) {
            $result = ['ok' => false, 'message' => 'قرارداد قبلاً امضا شده است.'];
        } elseif ($action === 'confirm_review_and_consent') {
            $consentChecked = isset($_POST['customer_consent']) && (string)$_POST['customer_consent'] === '1';
            if (!$consentChecked) {
                $result = ['ok' => false, 'message' => 'پذیرش صریح متن قرارداد الزامی است.'];
            } else {
                $result = m360_contract_confirm_review_and_consent($contractRowPost);
            }
        } elseif ($action === 'request_correction') {
            $corrNote = trim((string)($_POST['correction_note'] ?? ''));
            $payloadForCorr = is_array($context['payload'] ?? null) ? $context['payload'] : [];
            $requestForCorr = is_array($context['request'] ?? null) ? $context['request'] : null;
            $corr = m360_contract_request_correction(
                $conn,
                $contractRowPost,
                $requestForCorr,
                $payloadForCorr,
                $corrNote,
                $taskId
            );
            $result = ['ok' => !empty($corr['ok']), 'message' => (string)($corr['message'] ?? '')];
            if ($result['ok']) {
                if ($isAjax) {
                    m360_contract_review_json_response([
                        'ok' => true,
                        'message' => $result['message'],
                        'redirect' => 'customer-profile.php?contract_correction=1',
                    ]);
                }
                header('Location: customer-profile.php?contract_correction=1&msg=' . rawurlencode($result['message']) . '&ok=1', true, 302);
                exit;
            }
        } elseif ($action === 'confirm_signature') {
            $result = m360_contract_confirm_signature_locked($contractRowPost, trim((string)($_POST['signature_data'] ?? '')));
            if ($result['ok']) {
                $contractRowPost = m360_intake_contract_fetch_by_id($conn, (int)($contractRowPost['contract_id'] ?? 0)) ?? $contractRowPost;
                $otpResult = m360_contract_send_otp($contractRowPost);
            }
        } elseif ($action === 'send_otp') {
            $otpResult = m360_contract_send_otp($contractRowPost);
            $result = [
                'ok' => !empty($otpResult['ok']),
                'message' => (string)($otpResult['message'] ?? ''),
            ];
        } elseif ($action === 'complete_signature') {
            $result = m360_contract_complete_signature(
                $contractRowPost,
                $rawToken,
                trim((string)($_POST['signature_data'] ?? '')),
                true,
                true,
                true,
                trim((string)($_POST['otp_code'] ?? ''))
            );
            if ($result['ok']) {
                m360_contract_clear_task_context();
                if ($isAjax) {
                    m360_contract_review_json_response([
                        'ok' => true,
                        'message' => $result['message'],
                        'redirect' => 'customer-profile.php?contract_signed=1',
                    ]);
                }
                header('Location: customer-profile.php?contract_signed=1', true, 302);
                exit;
            }
        } elseif ($action === 'legacy_accept' && $rawToken !== '') {
            $result = m360_rw_intake_process_customer_contract_accept($conn, $rawToken, $_POST, $_SERVER);
        }

        if ($isAjax) {
            $contractRowPost = m360_intake_contract_fetch_by_id($conn, (int)($contractRowPost['contract_id'] ?? 0)) ?? $contractRowPost;
            $ajaxPayload = m360_contract_review_ajax_payload($result, $otpResult, $contractRowPost, $action);
            $httpStatus = !empty($result['ok']) ? 200 : 400;
            m360_contract_review_json_response($ajaxPayload, $httpStatus);
        }

        $redirect = m360_contract_review_return_url($entryMode, $taskId, $rawToken);
        header('Location: ' . $redirect . '&msg=' . rawurlencode((string)$result['message']) . '&ok=' . (!empty($result['ok']) ? '1' : '0'));
        exit;
    }
}

header('Content-Type: text/html; charset=UTF-8');

if ($conn === false) {
    $error = 'اتصال به پایگاه داده برقرار نشد.';
} elseif ($taskId < 1 && $rawToken === '') {
    $error = $invalidMessage;
} else {
    $context = m360_contract_resolve_customer_context($conn, $contextInput);
    if (!empty($context['redirect_login'])) {
        header('Location: ' . m360_rw_customer_profile_unauthenticated_redirect_url(), true, 302);
        exit;
    }
    if (!$context['ok'] || !is_array($context['contract'])) {
        if ((int)($context['http_status'] ?? 403) === 403 && !headers_sent()) {
            http_response_code(403);
        }
        $error = (string)$context['message'];
    } else {
        $entryMode = (string)$context['entry_mode'];
        $taskId = (int)($context['task_id'] ?? $taskId);
        $rawToken = (string)($context['raw_token'] ?? $rawToken);
        $contractRow = $context['contract'];
        $request = is_array($context['request'] ?? null) ? $context['request'] : null;
        $payload = is_array($context['payload'] ?? null) ? $context['payload'] : [];
        $readOnly = !empty($context['read_only']);

        if ($entryMode === M360_CONTRACT_ENTRY_TOKEN && $rawToken !== '' && $request !== null) {
            $resolved = m360_contract_resolve_token($rawToken);
            if (!$resolved['ok'] || !is_array($resolved['contract'])) {
                m360_rw_intake_ensure_db_contract_for_cartable(
                    $conn,
                    (int)($request['online_request_id'] ?? 0),
                    $request,
                    $payload,
                    $rawToken
                );
                $request = m360_online_req_fetch_by_id($conn, (int)($request['online_request_id'] ?? 0)) ?? $request;
                $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
                $payload = m360_rw_intake_payload_for_recovery($payload);
                $resolved = m360_contract_resolve_token($rawToken);
                if ($resolved['ok'] && is_array($resolved['contract'])) {
                    $contractRow = $resolved['contract'];
                }
            }
        }

        if (is_array($contractRow)) {
            m360_intake_contract_mark_viewed($conn, (int)$contractRow['contract_id']);
            if ($entryMode === M360_CONTRACT_ENTRY_TASK && $taskId > 0 && !$readOnly) {
                m360_cartable_mark_opened($conn, $taskId, 'CUSTOMER', null);
            }
            if ($request === null && (int)($contractRow['online_request_id'] ?? 0) > 0) {
                $request = m360_online_req_fetch_by_id($conn, (int)$contractRow['online_request_id']);
                if (is_array($request)) {
                    $payload = m360_rw_intake_payload_for_recovery(m360_online_req_parse_payload($request['request_payload_json'] ?? null));
                }
            }
            $summary = m360_rw_intake_contract_review_summary($payload, is_array($request) ? $request : []);
            $snapshot = m360_intake_contract_snapshot_from_row($contractRow);
            $contractHtml = m360_contract_render_html($snapshot, true);
            $workflow = m360_intake_contract_get_workflow_meta($contractRow);
            $sessionBindingEarly = m360_contract_require_verified_session_mobile_for_contract($contractRow);
            $otpVerified = (is_array($request) && m360_online_req_payload_otp_verified($request))
                || !empty($sessionBindingEarly['ok']);
            $accepted = m360_rw_intake_contract_customer_accepted($payload) || m360_intake_contract_is_signed($contractRow);
        } else {
            $error = $invalidMessage;
        }
    }
}

$reviewDone = trim((string)($workflow['review_completed_at'] ?? '')) !== '';
$consentDone = trim((string)($workflow['consent_at'] ?? '')) !== '';
$reviewConsentDone = $reviewDone && $consentDone;
$signatureConfirmed = m360_contract_workflow_signature_confirmed(is_array($contractRow) ? $contractRow : []);
$signed = is_array($contractRow) && m360_intake_contract_is_signed($contractRow);
$sessionBinding = is_array($contractRow) ? m360_contract_require_verified_session_mobile_for_contract($contractRow) : ['ok' => false, 'mobile' => ''];
$maskedMobile = $sessionBinding['ok'] ? m360_contract_mask_mobile((string)$sessionBinding['mobile']) : '';
$snapshotMobile = is_array($contractRow) ? m360_cartable_normalize_mobile((string)($contractRow['mobile'] ?? '')) : '';
$snapshotMaskedMobile = $snapshotMobile !== '' ? m360_contract_mask_mobile($snapshotMobile) : '';
$taskTitle = M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA;
$taskMessage = M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA;
$formActionUrl = m360_contract_review_return_url($entryMode, $taskId, $rawToken);
$jsTaskId = $entryMode === M360_CONTRACT_ENTRY_TASK ? $taskId : 0;
$jsToken = $entryMode === M360_CONTRACT_ENTRY_TOKEN ? $rawToken : '';

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>کارتابل مشتری — <?= m360_rw_h($taskTitle) ?></title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <link rel="stylesheet" href="assets/css/m360-contract.css">
    <style>
        .m360-contract-review-panel.is-collapsed .m360-contract-doc-host,
        .m360-contract-review-panel.is-collapsed .m360-contract-review-actions,
        .m360-contract-review-panel.is-collapsed .m360-contract-consent-anchor,
        .m360-contract-review-panel.is-collapsed .m360-contract-correction-box { display: none; }
        .m360-contract-doc-host {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            overflow-y: visible;
            margin: 1rem 0;
            padding: 1.25rem 1rem;
            border: 1px solid #d4d4d8;
            border-radius: 14px;
            background: #fff;
            color: #18181b;
        }
        .m360-contract-doc-host .m360-contract-sheet { max-width: 100%; }
        .m360-contract-consent-anchor {
            margin-top: 1.25rem;
            padding: 1rem 1.1rem;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
        }
        .m360-contract-consent-label {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 1.05rem;
            line-height: 1.8;
            color: #0f172a;
            font-weight: 600;
            cursor: pointer;
        }
        .m360-contract-consent-label input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            min-width: 1.25rem;
            margin-top: 0.3rem;
            accent-color: #166534;
        }
        .m360-contract-correction-box {
            margin-top: 1.25rem;
            padding: 1rem;
            border: 1px dashed #94a3b8;
            border-radius: 12px;
            background: #fff;
        }
        .m360-contract-correction-box textarea {
            width: 100%;
            margin: 0.5rem 0 0.75rem;
            padding: 0.65rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
        }
        .m360-contract-step { margin: 1.25rem 0; padding: 1rem; border: 1px solid #e4e4e7; border-radius: 12px; background: #fff; }
        .m360-contract-step.is-locked canvas { pointer-events: none; opacity: 0.85; }
        .m360-contract-step.is-hidden { display: none; }
        .m360-contract-page { max-width: 1100px; }
        @media (max-width: 720px) {
            .m360-contract-doc-host { padding: 1rem 0.75rem; }
            .m360-contract-consent-label { font-size: 1rem; }
        }
    </style>
</head>
<body class="m360-public-shell m360-rw-page">
<div class="m360-wrap m360-rw-wrap m360-contract-page">
    <header class="m360-rw-header">
        <h1 class="m360-rw-title">کارتابل مشتری</h1>
        <p class="m360-rw-subtitle"><?= m360_rw_h($taskTitle) ?></p>
        <p class="m360-rw-muted"><?= m360_rw_h($taskMessage) ?></p>
        <?php if ($flashMsg !== ''): ?>
            <div class="m360-rw-flash <?= $flashOk ? 'is-ok' : 'is-err' ?>"><?= m360_rw_h($flashMsg) ?></div>
        <?php endif; ?>
    </header>

    <?php if ($error !== ''): ?>
        <section class="m360-rw-alert"><?= m360_rw_h($error) ?></section>
        <p class="m360-action-row"><a class="m360-rw-btn" href="customer-profile.php">بازگشت به داشبورد مشتری</a></p>
    <?php else: ?>
        <section class="m360-rw-wizard-page-card">
            <div class="m360-rw-field-grid">
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">مشتری</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['customer_name'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">پلاک</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['plate'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">خودرو</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['brand_model'] ?? '—') ?></span></div>
                <?php if ($maskedMobile !== ''): ?>
                    <div class="m360-rw-field"><span class="m360-rw-field-lbl">شماره تأیید پیامکی</span><span class="m360-rw-field-val"><?= m360_rw_h($maskedMobile) ?></span></div>
                <?php endif; ?>
                <?php if ($snapshotMaskedMobile !== '' && $snapshotMaskedMobile !== $maskedMobile): ?>
                    <div class="m360-rw-field"><span class="m360-rw-field-lbl">شماره ثبت اولیه</span><span class="m360-rw-field-val"><?= m360_rw_h($snapshotMaskedMobile) ?></span></div>
                <?php endif; ?>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع خدمت</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['service_type_fa'] ?? $summary['service_route'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">کیلومتر</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['mileage'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">سطح بنزین</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['fuel_level'] ?? '—') ?></span></div>
            </div>

            <?php if ($signed || $accepted): ?>
                <p class="m360-rw-flash is-ok">قرارداد پذیرش با امضای دیجیتال و OTP تأیید شد.</p>
                <?php
                m360_contract_pdf_render_download_button(
                    is_array($contractRow) ? $contractRow : null,
                    'customer',
                    $entryMode === M360_CONTRACT_ENTRY_TOKEN ? $rawToken : '',
                    'm360-rw-btn'
                );
                ?>
                <p class="m360-action-row"><a class="m360-rw-btn" href="customer-profile.php">بازگشت به داشبورد مشتری</a></p>
            <?php elseif (!$otpVerified): ?>
                <p class="m360-rw-warn">ابتدا احراز هویت موبایل مشتری باید توسط OTP پذیرش تکمیل شود.</p>
                <p class="m360-action-row"><a class="m360-rw-btn" href="customer-profile.php">بازگشت به داشبورد مشتری</a></p>
            <?php else: ?>
                <?php
                m360_contract_pdf_render_download_button(
                    is_array($contractRow) ? $contractRow : null,
                    'customer',
                    $entryMode === M360_CONTRACT_ENTRY_TOKEN ? $rawToken : '',
                    'm360-rw-btn m360-rw-btn-secondary'
                );
                ?>
                <div id="m360_review_panel" class="m360-contract-review-panel m360-contract-step <?= $reviewConsentDone ? 'is-collapsed' : '' ?>">
                    <h2 class="m360-rw-section-title">مطالعه قرارداد</h2>
                    <?php if ($reviewConsentDone): ?>
                        <p class="m360-rw-flash is-ok">مطالعه و پذیرش قرارداد ثبت شد.</p>
                    <?php else: ?>
                        <div id="m360_contract_scroll" class="m360-contract-doc-host">
                            <?= $contractHtml ?>
                        </div>
                        <div class="m360-contract-consent-anchor">
                            <label class="m360-contract-consent-label" for="m360_consent_check">
                                <input type="checkbox" id="m360_consent_check" value="1">
                                <span><?= m360_rw_h(M360_CONTRACT_CONSENT_TEXT_FA) ?></span>
                            </label>
                        </div>
                        <p id="m360_scroll_hint" class="m360-rw-muted">پس از مطالعه، گزینه پذیرش را علامت بزنید تا دکمه تأیید فعال شود.</p>
                        <div class="m360-contract-review-actions">
                            <button type="button" class="m360-rw-btn" id="m360_review_confirm_btn" disabled>تأیید مطالعه و پذیرش قرارداد</button>
                        </div>
                        <div class="m360-contract-correction-box">
                            <label for="m360_correction_note" class="m360-rw-muted">اگر اطلاعات قرارداد نادرست است:</label>
                            <textarea id="m360_correction_note" rows="2" maxlength="500" placeholder="مثلاً مبلغ توافق‌شده متفاوت است / نوع خدمت اشتباه است"></textarea>
                            <button type="button" class="m360-rw-btn m360-rw-btn-secondary" id="m360_request_correction_btn">نیاز به اصلاح دارد</button>
                            <p id="m360_correction_status" class="m360-rw-muted" role="status"></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="m360_signature_panel" class="m360-contract-step <?= $reviewConsentDone ? '' : 'is-hidden' ?> <?= $signatureConfirmed ? 'is-locked' : '' ?>">
                    <h2 class="m360-rw-section-title">امضای مستقیم</h2>
                    <?php if ($signatureConfirmed): ?>
                        <p class="m360-rw-flash is-ok">امضای شما ثبت و قفل شد.</p>
                    <?php else: ?>
                        <canvas id="m360_signature_canvas" class="m360-contract-sign-canvas" aria-label="امضا"></canvas>
                        <div class="m360-contract-actions">
                            <button type="button" id="m360_signature_clear" class="m360-contract-btn secondary">پاک کردن امضا</button>
                            <button type="button" id="m360_signature_confirm_btn" class="m360-contract-btn primary" disabled>تأیید امضا</button>
                        </div>
                        <p id="m360_signature_status" class="m360-rw-muted"></p>
                    <?php endif; ?>
                </div>

                <div id="m360_otp_panel" class="m360-contract-step <?= ($signatureConfirmed && !$signed) ? '' : 'is-hidden' ?>">
                    <h2 class="m360-rw-section-title">تأیید نهایی با OTP قرارداد</h2>
                    <?php if ($maskedMobile !== ''): ?>
                        <p class="m360-rw-muted">کد تأیید به شماره تأیید پیامکی <?= m360_rw_h($maskedMobile) ?> ارسال می‌شود.</p>
                    <?php endif; ?>
                    <form id="m360_sign_form" method="post" action="<?= m360_rw_h($formActionUrl) ?>">
                        <?php if ($jsTaskId > 0): ?>
                            <input type="hidden" name="task_id" value="<?= (int)$jsTaskId ?>">
                        <?php elseif ($jsToken !== ''): ?>
                            <input type="hidden" name="token" value="<?= m360_rw_h($jsToken) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="workflow_action" value="complete_signature">
                        <input type="hidden" name="signature_data" id="m360_signature_data_final" value="">
                        <input type="hidden" name="confirm_read" value="1">
                        <div class="m360-contract-otp-row">
                            <button type="button" id="m360_send_contract_otp" class="m360-contract-btn secondary">ارسال مجدد کد</button>
                            <input type="text" name="otp_code" id="m360_otp_code" inputmode="numeric" maxlength="6" placeholder="کد ۶ رقمی" required>
                            <button type="submit" class="m360-contract-btn primary">تأیید کد و امضای نهایی قرارداد</button>
                        </div>
                        <p id="m360_otp_status" class="m360-rw-muted"></p>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php if ($error === '' && !$accepted && !$signed && $otpVerified): ?>
<script>
(function () {
    var token = <?= json_encode($jsToken, JSON_UNESCAPED_UNICODE) ?>;
    var taskId = <?= (int)$jsTaskId ?>;
    var postUrl = <?= json_encode($formActionUrl, JSON_UNESCAPED_UNICODE) ?>;
    var reviewConsentDone = <?= $reviewConsentDone ? 'true' : 'false' ?>;
    var signatureConfirmed = <?= $signatureConfirmed ? 'true' : 'false' ?>;
    var scrollHost = document.getElementById('m360_contract_scroll');
    var consentCheck = document.getElementById('m360_consent_check');
    var reviewConfirmBtn = document.getElementById('m360_review_confirm_btn');
    var scrollHint = document.getElementById('m360_scroll_hint');
    var reviewPanel = document.getElementById('m360_review_panel');
    var signaturePanel = document.getElementById('m360_signature_panel');
    var otpPanel = document.getElementById('m360_otp_panel');
    var canvas = document.getElementById('m360_signature_canvas');
    var clearBtn = document.getElementById('m360_signature_clear');
    var confirmSigBtn = document.getElementById('m360_signature_confirm_btn');
    var sigStatus = document.getElementById('m360_signature_status');
    var finalHidden = document.getElementById('m360_signature_data_final');
    var sendBtn = document.getElementById('m360_send_contract_otp');
    var otpStatus = document.getElementById('m360_otp_status');
    var signForm = document.getElementById('m360_sign_form');
    var padInitialized = false;
    var hasInk = false;
    var signatureLocked = signatureConfirmed;
    var signatureData = '';

    function appendAuth(body) {
        if (taskId > 0) {
            body.set('task_id', String(taskId));
        } else if (token) {
            body.set('t', token);
        }
        return body;
    }

    function parseJsonResponse(r) {
        return r.text().then(function (text) {
            var trimmed = (text || '').trim();
            try {
                return JSON.parse(trimmed);
            } catch (err) {
                throw new Error(trimmed.slice(0, 160) || 'پاسخ سرور JSON نیست.');
            }
        });
    }

    function signaturePayloadValid(data) {
        return typeof data === 'string' && data.indexOf('data:image/') === 0 && data.length >= 100;
    }

    function lockSignatureUi(dataUrl) {
        signatureLocked = true;
        signatureConfirmed = true;
        if (signaturePanel) signaturePanel.classList.add('is-locked');
        if (clearBtn) {
            clearBtn.disabled = true;
            clearBtn.style.display = 'none';
        }
        if (confirmSigBtn) confirmSigBtn.disabled = true;
        if (sigStatus) sigStatus.textContent = 'امضای شما ثبت و قفل شد.';
        if (otpPanel) otpPanel.classList.remove('is-hidden');
        if (finalHidden && dataUrl) finalHidden.value = dataUrl;
    }

    function atScrollEnd() {
        return true;
    }

    function updateScrollGate() {
        // Consent is available without nested scroll trap; customer must still check the box.
        if (reviewConsentDone || !consentCheck) return;
        consentCheck.disabled = false;
        if (scrollHint) scrollHint.style.display = '';
    }

    if (!reviewConsentDone) {
        updateScrollGate();
    }

    if (consentCheck && reviewConfirmBtn && !reviewConsentDone) {
        consentCheck.addEventListener('change', function () {
            if (consentCheck.checked) {
                reviewConfirmBtn.disabled = false;
            } else {
                reviewConfirmBtn.disabled = true;
            }
        });
        reviewConfirmBtn.addEventListener('click', function () {
            reviewConfirmBtn.disabled = true;
            var body = appendAuth(new URLSearchParams());
            body.set('workflow_action', 'confirm_review_and_consent');
            body.set('customer_consent', consentCheck.checked ? '1' : '0');
            body.set('ajax', '1');
            fetch(postUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: body.toString()
            }).then(parseJsonResponse).then(function (data) {
                if (!data.ok) {
                    alert(data.message || 'خطا در ثبت مطالعه قرارداد');
                    reviewConfirmBtn.disabled = false;
                    return;
                }
                reviewConsentDone = true;
                if (reviewPanel) reviewPanel.classList.add('is-collapsed');
                if (signaturePanel) signaturePanel.classList.remove('is-hidden');
                initSignaturePad();
            }).catch(function (err) {
                alert((err && err.message) ? err.message : 'خطا در ارتباط با سرور.');
                reviewConfirmBtn.disabled = false;
            });
        });
    }

    var correctionBtn = document.getElementById('m360_request_correction_btn');
    var correctionNote = document.getElementById('m360_correction_note');
    var correctionStatus = document.getElementById('m360_correction_status');
    if (correctionBtn && !reviewConsentDone) {
        correctionBtn.addEventListener('click', function () {
            if (!window.confirm('قرارداد امضا نمی‌شود و برای اصلاح به پذیرش بازمی‌گردد. ادامه می‌دهید؟')) {
                return;
            }
            correctionBtn.disabled = true;
            var body = appendAuth(new URLSearchParams());
            body.set('workflow_action', 'request_correction');
            body.set('correction_note', correctionNote ? String(correctionNote.value || '') : '');
            body.set('ajax', '1');
            fetch(postUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: body.toString()
            }).then(parseJsonResponse).then(function (data) {
                if (!data.ok) {
                    if (correctionStatus) correctionStatus.textContent = data.message || 'ثبت درخواست اصلاح ناموفق بود.';
                    correctionBtn.disabled = false;
                    return;
                }
                if (correctionStatus) correctionStatus.textContent = data.message || 'درخواست اصلاح ثبت شد.';
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }).catch(function (err) {
                if (correctionStatus) correctionStatus.textContent = (err && err.message) ? err.message : 'خطا در ارتباط با سرور.';
                correctionBtn.disabled = false;
            });
        });
    }

    function initSignaturePad() {
        if (padInitialized || !canvas || signatureLocked || typeof canvas.getContext !== 'function') return;
        padInitialized = true;
        var ctx = canvas.getContext('2d');
        var drawing = false;
        var ratio = window.devicePixelRatio || 1;

        function resize() {
            var rect = canvas.getBoundingClientRect();
            canvas.width = Math.max(1, Math.floor(rect.width * ratio));
            canvas.height = Math.max(1, Math.floor(rect.height * ratio));
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#0f172a';
            hasInk = false;
            signatureData = '';
            if (finalHidden) finalHidden.value = '';
            updateConfirmState();
        }

        function pos(e) {
            var rect = canvas.getBoundingClientRect();
            var clientX = e.clientX;
            var clientY = e.clientY;
            if (e.touches && e.touches[0]) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        function updateConfirmState() {
            if (confirmSigBtn) confirmSigBtn.disabled = signatureLocked || !signaturePayloadValid(signatureData);
        }

        function onDown(e) {
            if (signatureLocked) return;
            drawing = true;
            var p = pos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            if (canvas.setPointerCapture && e.pointerId !== undefined) {
                try { canvas.setPointerCapture(e.pointerId); } catch (err) {}
            }
            e.preventDefault();
        }

        function onMove(e) {
            if (!drawing || signatureLocked) return;
            hasInk = true;
            var p = pos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }

        function onUp() {
            if (!drawing) return;
            drawing = false;
            if (hasInk) {
                signatureData = canvas.toDataURL('image/png');
                if (finalHidden) finalHidden.value = signatureData;
            }
            updateConfirmState();
        }

        resize();
        window.addEventListener('resize', resize);
        canvas.addEventListener('pointerdown', onDown);
        canvas.addEventListener('pointermove', onMove);
        canvas.addEventListener('pointerup', onUp);
        canvas.addEventListener('pointercancel', onUp);
        canvas.addEventListener('pointerleave', onUp);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (signatureLocked) return;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasInk = false;
                signatureData = '';
                if (finalHidden) finalHidden.value = '';
                updateConfirmState();
            });
        }

        if (confirmSigBtn) {
            confirmSigBtn.addEventListener('click', function () {
                if (signatureLocked) return;
                if (!signaturePayloadValid(signatureData)) {
                    if (sigStatus) sigStatus.textContent = 'ابتدا امضای کافی روی صفحه بکشید.';
                    return;
                }
                confirmSigBtn.disabled = true;
                if (sigStatus) sigStatus.textContent = 'در حال ثبت امضا...';
                var body = appendAuth(new URLSearchParams());
                body.set('workflow_action', 'confirm_signature');
                body.set('signature_data', signatureData);
                body.set('ajax', '1');
                fetch(postUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: body.toString()
                }).then(parseJsonResponse).then(function (data) {
                    if (!data.ok || !data.signature_locked) {
                        if (sigStatus) sigStatus.textContent = data.message || 'خطا در ثبت امضا';
                        confirmSigBtn.disabled = false;
                        return;
                    }
                    lockSignatureUi(signatureData);
                    var otp = data.otp || {};
                    var otpMsg = otp.message || '';
                    if (data.masked_destination) {
                        otpMsg = (otpMsg ? otpMsg + ' ' : '') + '(' + data.masked_destination + ')';
                    }
                    if (otpStatus) {
                        if (data.otp_send_status === 'failed') {
                            otpStatus.textContent = otpMsg || 'امضا ثبت شد. ارسال OTP ناموفق بود؛ «ارسال مجدد کد» را بزنید.';
                        } else {
                            otpStatus.textContent = otpMsg || (data.otp_send_status === 'mock_sent' ? 'کد تأیید (حالت تست) آماده است.' : 'کد تأیید ارسال شد.');
                        }
                    }
                }).catch(function (err) {
                    if (sigStatus) sigStatus.textContent = (err && err.message) ? err.message : 'خطا در ارتباط با سرور.';
                    confirmSigBtn.disabled = false;
                });
            });
        }

        updateConfirmState();
    }

    if (reviewConsentDone && !signatureConfirmed) {
        initSignaturePad();
    }

    function sendOtp() {
        if (!sendBtn) return;
        sendBtn.disabled = true;
        if (otpStatus) otpStatus.textContent = 'در حال ارسال...';
        var body = appendAuth(new URLSearchParams());
        body.set('workflow_action', 'send_otp');
        body.set('ajax', '1');
        fetch(postUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: body.toString()
        }).then(parseJsonResponse).then(function (data) {
            var msg = data.message || (data.ok ? 'کد ارسال شد.' : 'خطا');
            if (data.masked_destination) {
                msg += ' (' + data.masked_destination + ')';
            }
            if (otpStatus) otpStatus.textContent = msg;
            sendBtn.disabled = false;
        }).catch(function () {
            if (otpStatus) otpStatus.textContent = 'خطا در ارتباط با سرور.';
            sendBtn.disabled = false;
        });
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', sendOtp);
    }

    if (signatureConfirmed && otpPanel) {
        otpPanel.classList.remove('is-hidden');
    }

    if (signForm) {
        signForm.addEventListener('submit', function (e) {
            if (!finalHidden || finalHidden.value.length < 100) {
                e.preventDefault();
                alert('امضای قفل‌شده برای تأیید نهایی یافت نشد.');
            }
        });
    }
})();
</script>
<?php endif; ?>
</body>
</html>
