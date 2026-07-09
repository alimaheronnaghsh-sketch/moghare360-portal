<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';

$rawToken = trim((string)($_GET['t'] ?? $_GET['token'] ?? $_POST['t'] ?? $_POST['token'] ?? ''));
$flashMsg = trim((string)($_GET['msg'] ?? ''));
$flashOk = (string)($_GET['ok'] ?? '') === '1';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rawToken !== '') {
    $action = trim((string)($_POST['workflow_action'] ?? ''));
    $resolved = m360_contract_resolve_token($rawToken);
    $redirect = 'customer-intake-contract-review.php?t=' . rawurlencode($rawToken);
    if (!$resolved['ok'] || !is_array($resolved['contract'])) {
        header('Location: ' . $redirect . '&msg=' . rawurlencode($resolved['message']) . '&ok=0');
        exit;
    }
    $contractRowPost = $resolved['contract'];
    $result = ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
    if ($action === 'mark_review_complete') {
        $result = m360_contract_mark_review_completed($contractRowPost);
    } elseif ($action === 'mark_consent') {
        $consent = isset($_POST['customer_consent']) && (string)$_POST['customer_consent'] === '1';
        if (!$consent) {
            $result = ['ok' => false, 'message' => 'پذیرش صریح متن قرارداد الزامی است.'];
        } else {
            $result = m360_contract_mark_consent_accepted($contractRowPost);
        }
    } elseif ($action === 'save_signature_draft') {
        $result = m360_contract_save_signature_draft($contractRowPost, trim((string)($_POST['signature_data'] ?? '')));
    } elseif ($action === 'legacy_accept') {
        $conn = customer_core_db();
        if ($conn !== false) {
            $result = m360_rw_intake_process_customer_contract_accept($conn, $rawToken, $_POST, $_SERVER);
        }
    }
    header('Location: ' . $redirect . '&msg=' . rawurlencode((string)$result['message']) . '&ok=' . (!empty($result['ok']) ? '1' : '0'));
    exit;
}

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
                    $task = m360_rw_intake_contract_cartable_task($payload);
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
$signatureDraft = trim((string)($workflow['signature_draft_hash'] ?? '')) !== '';
$signed = is_array($contractRow) && m360_intake_contract_is_signed($contractRow);
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
            <?php elseif (!$otpVerified): ?>
                <p class="m360-rw-warn">ابتدا احراز هویت موبایل مشتری باید توسط OTP پذیرش تکمیل شود.</p>
            <?php else: ?>
                <div id="m360_contract_scroll" class="m360-contract-scroll-host" style="max-height:60vh;overflow-y:auto;border:1px solid #cbd5e1;border-radius:12px;padding:1rem;margin:1rem 0;background:#fff;"><?= $contractHtml ?></div>
                <p id="m360_scroll_hint" class="m360-rw-muted" <?= $reviewDone ? 'style="display:none"' : '' ?>>برای فعال شدن پذیرش، متن قرارداد را تا انتها مطالعه کنید.</p>

                <?php if (!$reviewDone): ?>
                    <form class="m360-rw-form" method="post" action="customer-intake-contract-review.php" id="m360_review_form">
                        <input type="hidden" name="t" value="<?= m360_rw_h($rawToken) ?>">
                        <input type="hidden" name="workflow_action" value="mark_review_complete">
                        <button type="submit" class="m360-rw-btn m360-rw-btn-secondary" id="m360_review_btn" disabled>ثبت مطالعه کامل قرارداد</button>
                    </form>
                <?php else: ?>
                    <p class="m360-rw-flash is-ok">مطالعه کامل قرارداد ثبت شد.</p>
                <?php endif; ?>

                <form class="m360-rw-form" method="post" action="customer-intake-contract-review.php" id="m360_consent_form">
                    <input type="hidden" name="t" value="<?= m360_rw_h($rawToken) ?>">
                    <input type="hidden" name="workflow_action" value="mark_consent">
                    <label class="m360-rw-check-label m360-rw-confirm-check">
                        <input type="checkbox" name="customer_consent" value="1" id="m360_consent_check" <?= $consentDone ? 'checked disabled' : '' ?> <?= $reviewDone ? '' : 'disabled' ?>>
                        <?= m360_rw_h(M360_CONTRACT_CONSENT_TEXT_FA) ?>
                    </label>
                    <?php if (!$consentDone): ?>
                        <button type="submit" class="m360-rw-btn m360-rw-btn-secondary" id="m360_consent_btn" disabled>ثبت پذیرش متن قرارداد</button>
                    <?php else: ?>
                        <p class="m360-rw-flash is-ok">پذیرش صریح متن قرارداد ثبت شد.</p>
                    <?php endif; ?>
                </form>

                <div id="m360_signature_block" <?= $consentDone ? '' : 'style="display:none"' ?>>
                    <h2 class="m360-rw-section-title">امضای مستقیم</h2>
                    <canvas id="m360_signature_canvas" class="m360-contract-sign-canvas" aria-label="امضا"></canvas>
                    <button type="button" id="m360_signature_clear" class="m360-contract-btn secondary">پاک کردن امضا</button>
                    <form class="m360-rw-form" method="post" action="customer-intake-contract-review.php" id="m360_signature_form">
                        <input type="hidden" name="t" value="<?= m360_rw_h($rawToken) ?>">
                        <input type="hidden" name="workflow_action" value="save_signature_draft">
                        <input type="hidden" name="signature_data" id="m360_signature_data" value="">
                        <button type="submit" class="m360-rw-btn" id="m360_signature_save_btn" disabled>ثبت امضا</button>
                    </form>
                    <?php if ($signatureDraft): ?>
                        <p class="m360-rw-flash is-ok">امضای مستقیم ثبت شد.</p>
                    <?php endif; ?>
                </div>

                <div id="m360_otp_block" <?= ($signatureDraft && $consentDone) ? '' : 'style="display:none"' ?>>
                    <h2 class="m360-rw-section-title">تأیید نهایی با OTP قرارداد</h2>
                    <form id="m360_sign_form" method="post" action="api/customer/contract-sign.php">
                        <input type="hidden" name="token" value="<?= m360_rw_h($rawToken) ?>">
                        <input type="hidden" name="signature_data" id="m360_signature_data_final" value="">
                        <input type="hidden" name="confirm_read" value="1">
                        <div class="m360-contract-otp-row">
                            <button type="button" id="m360_send_contract_otp" class="m360-contract-btn secondary">ارسال کد تأیید قرارداد</button>
                            <input type="text" name="otp_code" id="m360_otp_code" inputmode="numeric" maxlength="6" placeholder="کد ۶ رقمی" required>
                            <button type="submit" class="m360-contract-btn primary">تأیید نهایی و قفل قرارداد</button>
                        </div>
                        <p id="m360_otp_status" class="m360-rw-muted"></p>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php if ($error === '' && !$accepted && !$signed && $otpVerified): ?>
<script src="assets/js/m360-signature-pad.js?v=b2"></script>
<script>
(function () {
    var scrollHost = document.getElementById('m360_contract_scroll');
    var reviewBtn = document.getElementById('m360_review_btn');
    var reviewForm = document.getElementById('m360_review_form');
    var consentCheck = document.getElementById('m360_consent_check');
    var consentBtn = document.getElementById('m360_consent_btn');
    var signatureBlock = document.getElementById('m360_signature_block');
    var signatureSaveBtn = document.getElementById('m360_signature_save_btn');
    var otpBlock = document.getElementById('m360_otp_block');
    var scrollHint = document.getElementById('m360_scroll_hint');
    var reviewDone = <?= $reviewDone ? 'true' : 'false' ?>;
    var consentDone = <?= $consentDone ? 'true' : 'false' ?>;
    var signatureDraft = <?= $signatureDraft ? 'true' : 'false' ?>;

    function atScrollEnd() {
        if (!scrollHost) return false;
        return scrollHost.scrollTop + scrollHost.clientHeight >= scrollHost.scrollHeight - 8;
    }

    function updateScrollGate() {
        if (reviewDone || !reviewBtn) return;
        if (atScrollEnd()) {
            reviewBtn.disabled = false;
            if (scrollHint) scrollHint.style.display = 'none';
        }
    }

    if (scrollHost && !reviewDone) {
        scrollHost.addEventListener('scroll', updateScrollGate, { passive: true });
        updateScrollGate();
    }

    if (consentCheck && consentBtn && !consentDone) {
        consentCheck.addEventListener('change', function () {
            consentBtn.disabled = !consentCheck.checked;
        });
    }

    if (typeof initSignaturePad === 'function' && consentDone && !signatureDraft) {
        initSignaturePad('m360_signature_canvas', 'm360_signature_clear', 'm360_signature_data');
        var hidden = document.getElementById('m360_signature_data');
        var finalHidden = document.getElementById('m360_signature_data_final');
        if (hidden && signatureSaveBtn) {
            hidden.addEventListener('change', function () {});
            setInterval(function () {
                var val = hidden.value || '';
                signatureSaveBtn.disabled = val.length < 100;
                if (finalHidden) finalHidden.value = val;
            }, 400);
        }
    }

    if (signatureDraft && finalHidden) {
        // signature already saved server-side; final submit still needs canvas data — user must re-sign or we copy on load
    }

    var sendBtn = document.getElementById('m360_send_contract_otp');
    var status = document.getElementById('m360_otp_status');
    if (sendBtn) {
        sendBtn.addEventListener('click', function () {
            status.textContent = 'در حال ارسال...';
            fetch('api/customer/contract-send-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ token: <?= json_encode($rawToken, JSON_UNESCAPED_UNICODE) ?> })
            }).then(function (r) { return r.json(); }).then(function (data) {
                status.textContent = data.message || (data.ok ? 'کد ارسال شد.' : 'خطا');
            }).catch(function () {
                status.textContent = 'خطا در ارتباط با سرور.';
            });
        });
    }

    var signForm = document.getElementById('m360_sign_form');
    if (signForm) {
        signForm.addEventListener('submit', function (e) {
            var finalHidden = document.getElementById('m360_signature_data_final');
            var draftHidden = document.getElementById('m360_signature_data');
            if (finalHidden && (!finalHidden.value || finalHidden.value.length < 100) && draftHidden) {
                finalHidden.value = draftHidden.value || '';
            }
            if (!finalHidden || finalHidden.value.length < 100) {
                e.preventDefault();
                alert('امضای مستقیم باید قبل از تأیید نهایی ثبت شود.');
            }
        });
    }
})();
</script>
<?php endif; ?>
</body>
</html>
