<?php
declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

$rawToken = trim((string)($_GET['t'] ?? $_GET['token'] ?? $_POST['t'] ?? $_POST['token'] ?? ''));
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rawToken !== '') {
    $action = trim((string)($_POST['workflow_action'] ?? ''));
    $resolved = m360_contract_resolve_token($rawToken);
    if (!$resolved['ok'] || !is_array($resolved['contract'])) {
        if ($isAjax) {
            m360_contract_review_json_response([
                'ok' => false,
                'message' => $resolved['message'],
                'error_code' => 'token_invalid',
                'workflow_state' => 'CONTRACT_OPEN',
                'signature_locked' => false,
                'otp_send_status' => 'not_requested',
                'masked_destination' => '',
            ], 403);
        }
        header('Location: customer-intake-contract-review.php?t=' . rawurlencode($rawToken) . '&msg=' . rawurlencode($resolved['message']) . '&ok=0');
        exit;
    }
    $contractRowPost = $resolved['contract'];
    $result = ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
    $otpResult = null;

    if ($action === 'confirm_review_and_consent') {
        $consentChecked = isset($_POST['customer_consent']) && (string)$_POST['customer_consent'] === '1';
        if (!$consentChecked) {
            $result = ['ok' => false, 'message' => 'پذیرش صریح متن قرارداد الزامی است.'];
        } else {
            $result = m360_contract_confirm_review_and_consent($contractRowPost);
        }
    } elseif ($action === 'confirm_signature') {
        $result = m360_contract_confirm_signature_locked($contractRowPost, trim((string)($_POST['signature_data'] ?? '')));
        if ($result['ok']) {
            $contractRowPost = m360_intake_contract_fetch_by_id(customer_core_db(), (int)($contractRowPost['contract_id'] ?? 0)) ?? $contractRowPost;
            $otpResult = m360_contract_send_otp($contractRowPost);
        }
    } elseif ($action === 'legacy_accept') {
        $conn = customer_core_db();
        if ($conn !== false) {
            $result = m360_rw_intake_process_customer_contract_accept($conn, $rawToken, $_POST, $_SERVER);
        }
    }

    if ($isAjax) {
        $contractRowPost = m360_intake_contract_fetch_by_id(customer_core_db(), (int)($contractRowPost['contract_id'] ?? 0)) ?? $contractRowPost;
        $ajaxPayload = m360_contract_review_ajax_payload($result, $otpResult, $contractRowPost, $action);
        $httpStatus = !empty($result['ok']) ? 200 : 400;
        m360_contract_review_json_response($ajaxPayload, $httpStatus);
    }

    $redirect = 'customer-intake-contract-review.php?t=' . rawurlencode($rawToken);
    header('Location: ' . $redirect . '&msg=' . rawurlencode((string)$result['message']) . '&ok=' . (!empty($result['ok']) ? '1' : '0'));
    exit;
}

header('Content-Type: text/html; charset=UTF-8');

if ($rawToken !== '') {
    $requestId = m360_rw_intake_contract_parse_review_token($rawToken);
    $conn = customer_core_db();
    if ($conn === false) {
        $error = 'اتصال به پایگاه داده برقرار نشد.';
    } elseif ($requestId < 1) {
        $error = $invalidMessage;
    } else {
        $request = m360_online_req_fetch_by_id($conn, $requestId);
        if ($request === null) {
            $error = $invalidMessage;
        } else {
            $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
            $payload = m360_rw_intake_payload_for_recovery($payload);
            $tokenCheck = m360_rw_intake_contract_validate_review_token($payload, $requestId, $rawToken);
            if (!$tokenCheck['ok']) {
                $error = $tokenCheck['error'];
            } else {
                $resolved = m360_contract_resolve_token($rawToken);
                if (!$resolved['ok'] || !is_array($resolved['contract'])) {
                    m360_rw_intake_ensure_db_contract_for_cartable($conn, $requestId, $request, $payload, $rawToken);
                    $request = m360_online_req_fetch_by_id($conn, $requestId) ?? $request;
                    $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
                    $payload = m360_rw_intake_payload_for_recovery($payload);
                    $resolved = m360_contract_resolve_token($rawToken);
                }
                if (!$resolved['ok'] || !is_array($resolved['contract'])) {
                    $error = $resolved['message'];
                } else {
                    $contractRow = $resolved['contract'];
                    if ($conn !== false) {
                        m360_intake_contract_mark_viewed($conn, (int)$contractRow['contract_id']);
                    }
                    $summary = m360_rw_intake_contract_review_summary($payload, $request);
                    $snapshot = m360_intake_contract_snapshot_from_row($contractRow);
                    $contractHtml = m360_contract_render_html($snapshot, true);
                    $workflow = m360_intake_contract_get_workflow_meta($contractRow);
                    $otpVerified = m360_online_req_payload_otp_verified($request);
                    $accepted = m360_rw_intake_contract_customer_accepted($payload) || m360_intake_contract_is_signed($contractRow);
                }
            }
        }
    }
} else {
    $error = $invalidMessage;
}

$reviewDone = trim((string)($workflow['review_completed_at'] ?? '')) !== '';
$consentDone = trim((string)($workflow['consent_at'] ?? '')) !== '';
$reviewConsentDone = $reviewDone && $consentDone;
$signatureConfirmed = m360_contract_workflow_signature_confirmed(is_array($contractRow) ? $contractRow : []);
$signed = is_array($contractRow) && m360_intake_contract_is_signed($contractRow);
$sessionBinding = is_array($contractRow) ? m360_contract_require_verified_session_mobile_for_contract($contractRow) : ['ok' => false, 'mobile' => ''];
$maskedMobile = $sessionBinding['ok'] ? m360_contract_mask_mobile((string)$sessionBinding['mobile']) : '';
$taskTitle = M360_RW_INTAKE_CARTABLE_TASK_TITLE_FA;
$taskMessage = M360_RW_INTAKE_CARTABLE_TASK_MESSAGE_FA;

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
        .m360-contract-review-panel.is-collapsed .m360-contract-scroll-host { display: none; }
        .m360-contract-review-panel.is-collapsed .m360-contract-review-actions { display: none; }
        .m360-contract-consent-anchor { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e4e4e7; }
        .m360-contract-step { margin: 1.25rem 0; padding: 1rem; border: 1px solid #e4e4e7; border-radius: 12px; background: #fff; }
        .m360-contract-step.is-locked canvas { pointer-events: none; opacity: 0.85; }
        .m360-contract-step.is-hidden { display: none; }
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
    <?php else: ?>
        <section class="m360-rw-wizard-page-card">
            <div class="m360-rw-field-grid">
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">مشتری</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['customer_name'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">پلاک</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['plate'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">خودرو</span><span class="m360-rw-field-val"><?= m360_rw_h($summary['brand_model'] ?? '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">نسخه قرارداد</span><span class="m360-rw-field-val"><?= m360_rw_h(M360_CONTRACT_VERSION) ?></span></div>
            </div>

            <?php if ($signed || $accepted): ?>
                <p class="m360-rw-flash is-ok">قرارداد پذیرش با امضای دیجیتال و OTP تأیید شد.</p>
                <p class="m360-action-row"><a class="m360-rw-btn" href="customer-profile.php">بازگشت به داشبورد مشتری</a></p>
            <?php elseif (!$otpVerified): ?>
                <p class="m360-rw-warn">ابتدا احراز هویت موبایل مشتری باید توسط OTP پذیرش تکمیل شود.</p>
            <?php else: ?>
                <div id="m360_review_panel" class="m360-contract-review-panel m360-contract-step <?= $reviewConsentDone ? 'is-collapsed' : '' ?>">
                    <h2 class="m360-rw-section-title">مطالعه قرارداد</h2>
                    <?php if ($reviewConsentDone): ?>
                        <p class="m360-rw-flash is-ok">مطالعه و پذیرش قرارداد ثبت شد.</p>
                    <?php else: ?>
                        <div id="m360_contract_scroll" class="m360-contract-scroll-host" style="max-height:60vh;overflow-y:auto;border:1px solid #cbd5e1;border-radius:12px;padding:1rem;margin:1rem 0;background:#fff;">
                            <?= $contractHtml ?>
                            <div class="m360-contract-consent-anchor">
                                <label class="m360-rw-check-label m360-rw-confirm-check">
                                    <input type="checkbox" id="m360_consent_check" value="1" disabled>
                                    <?= m360_rw_h(M360_CONTRACT_CONSENT_TEXT_FA) ?>
                                </label>
                            </div>
                        </div>
                        <p id="m360_scroll_hint" class="m360-rw-muted">برای فعال شدن پذیرش، متن قرارداد را تا انتها مطالعه کنید.</p>
                        <div class="m360-contract-review-actions">
                            <button type="button" class="m360-rw-btn m360-rw-btn-secondary" id="m360_review_confirm_btn" style="display:none" disabled>تأیید مطالعه و پذیرش قرارداد</button>
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
                        <p class="m360-rw-muted">کد تأیید به شماره <?= m360_rw_h($maskedMobile) ?> ارسال می‌شود.</p>
                    <?php endif; ?>
                    <form id="m360_sign_form" method="post" action="api/customer/contract-sign.php">
                        <input type="hidden" name="token" value="<?= m360_rw_h($rawToken) ?>">
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
    var token = <?= json_encode($rawToken, JSON_UNESCAPED_UNICODE) ?>;
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
        if (!scrollHost) return false;
        return scrollHost.scrollTop + scrollHost.clientHeight >= scrollHost.scrollHeight - 8;
    }

    function updateScrollGate() {
        if (reviewConsentDone || !consentCheck) return;
        if (atScrollEnd()) {
            consentCheck.disabled = false;
            if (scrollHint) scrollHint.style.display = 'none';
        }
    }

    if (scrollHost && !reviewConsentDone) {
        scrollHost.addEventListener('scroll', updateScrollGate, { passive: true });
        updateScrollGate();
    }

    if (consentCheck && reviewConfirmBtn && !reviewConsentDone) {
        consentCheck.addEventListener('change', function () {
            if (consentCheck.checked) {
                reviewConfirmBtn.style.display = 'inline-block';
                reviewConfirmBtn.disabled = false;
            } else {
                reviewConfirmBtn.style.display = 'none';
                reviewConfirmBtn.disabled = true;
            }
        });
        reviewConfirmBtn.addEventListener('click', function () {
            reviewConfirmBtn.disabled = true;
            var body = new URLSearchParams();
            body.set('t', token);
            body.set('workflow_action', 'confirm_review_and_consent');
            body.set('customer_consent', consentCheck.checked ? '1' : '0');
            body.set('ajax', '1');
            fetch('customer-intake-contract-review.php', {
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

        function onUp(e) {
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
                var body = new URLSearchParams();
                body.set('t', token);
                body.set('workflow_action', 'confirm_signature');
                body.set('signature_data', signatureData);
                body.set('ajax', '1');
                fetch('customer-intake-contract-review.php', {
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
        fetch('api/customer/contract-send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ token: token })
        }).then(function (r) { return parseJsonResponse(r); }).then(function (data) {
            var msg = data.message || (data.ok ? 'کد ارسال شد.' : 'خطا');
            if (data.data && data.data.masked_mobile) {
                msg += ' (' + data.data.masked_mobile + ')';
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
        if (!otpStatus || otpStatus.textContent === '') {
            sendOtp();
        }
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
