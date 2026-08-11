<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Online request detail (GET read-only; actions via intake shell).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';

m360_reception_require_staff();

$requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';

$conn = customer_core_db();
$row = null;
$history = [];
$gate = null;
$gateLabel = '';

if ($conn !== false && $requestId > 0) {
    $row = m360_online_req_fetch_by_id($conn, $requestId);
    $history = m360_reception_fetch_history($conn, $requestId);
    if ($row !== null) {
        $file = m360_rw_build_intake_file($conn, $requestId);
        $gate = $file['gate'] ?? null;
        $gateLabel = (string)($gate['label_fa'] ?? '');
    }
}

$payload = $row !== null ? m360_online_req_parse_payload($row['request_payload_json'] ?? null) : [];
$convertedJobcardId = $row !== null ? m360_online_req_converted_jobcard_id($row) : 0;
$canAct = $row !== null && !m360_online_req_is_converted($row) && strtoupper((string)($row['request_status'] ?? '')) !== M360_ONLINE_REQ_STATUS_REJECTED;
$gatePanel = isset($_GET['gate']) ? trim((string)$_GET['gate']) : '';
$gateContent = $gatePanel === 'convert' ? m360_reception_action_error_content('convert_prereq', $requestId) : null;
$customerRequestType = trim((string)($row['request_type'] ?? ($payload['request_type'] ?? '')));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات درخواست آنلاین #<?= $requestId ?> — ماهین 360°</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-public-shell m360-rw-page">
<div class="m360-wrap m360-rw-wrap">
    <header class="m360-rw-header">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-online-requests.php">← درخواست‌های آنلاین</a>
            <a class="m360-rw-back" href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>
        </div>
        <h1 class="m360-rw-title">جزئیات درخواست آنلاین</h1>
        <p class="m360-rw-subtitle">شناسه <?= m360_reception_h((string)$requestId) ?></p>
    </header>

    <?php if ($gateContent !== null): ?>
        <section class="m360-rw-warn" role="alert">
            <strong><?= m360_reception_h($gateContent['title']) ?></strong>
            <p><?= m360_reception_h($gateContent['text']) ?></p>
        </section>
    <?php endif; ?>

    <?php if ($flash !== ''): ?>
        <section class="m360-rw-panel <?= $flashOk ? 'm360-rw-flash-ok' : 'm360-rw-alert' ?>"><?= m360_reception_h($flash) ?></section>
    <?php endif; ?>

    <?php if ($row === null): ?>
        <section class="m360-rw-alert">درخواست یافت نشد.</section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به فهرست</a>
        </div>
    <?php else: ?>
        <section class="m360-rw-panel">
            <div class="m360-rw-field-grid">
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">وضعیت</span><span class="m360-rw-field-val"><?= m360_reception_h(m360_online_req_status_label_fa((string)($row['request_status'] ?? ''))) ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">تاریخ ثبت</span><span class="m360-rw-field-val"><?= m360_reception_h((string)($row['created_at'] ?? '')) ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">موبایل</span><span class="m360-rw-field-val"><?= m360_reception_h((string)($row['mobile'] ?? '')) ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">نام مشتری</span><span class="m360-rw-field-val"><?= m360_reception_h((string)($row['customer_name'] ?? '')) ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">پلاک</span><span class="m360-rw-field-val"><?= m360_reception_h((string)($row['vehicle_plate'] ?? '')) ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">تاریخ مراجعه</span><span class="m360-rw-field-val"><?= m360_reception_h((string)($row['visit_date'] ?? ($payload['visit_date'] ?? '—'))) ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">نوع درخواست مشتری</span><span class="m360-rw-field-val"><?= m360_reception_h($customerRequestType !== '' ? $customerRequestType : '—') ?></span></div>
                <div class="m360-rw-field"><span class="m360-rw-field-lbl">منبع</span><span class="m360-rw-field-val"><?= m360_reception_h((string)($row['source_channel'] ?? '')) ?></span></div>
            </div>
            <?php if ($customerRequestType !== ''): ?>
                <p class="m360-rw-muted" style="margin-top:0.75rem;"><?= m360_rw_h(M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA) ?></p>
            <?php endif; ?>
        </section>

        <section class="m360-rw-panel">
            <h2>شرح درخواست</h2>
            <p class="m360-rw-note"><?= m360_reception_h((string)($row['service_note'] ?? '')) ?: '—' ?></p>
        </section>

        <?php if ($convertedJobcardId > 0): ?>
            <section class="m360-rw-panel">
                <h2>کارت کار</h2>
                <p>شناسه کارت کار: <strong><?= m360_reception_h((string)$convertedJobcardId) ?></strong></p>
                <a class="m360-rw-btn m360-rw-btn-secondary" href="erp-jobcard-detail.php?jobcard_id=<?= $convertedJobcardId ?>">مشاهده کارت کار</a>
            </section>
        <?php endif; ?>

        <?php if ($canAct): ?>
            <section class="m360-rw-panel m360-rw-primary-cta">
                <h2>اقدام پذیرش</h2>
                <p class="m360-rw-muted">ابتدا پرونده پذیرش را تکمیل و وضعیت Gate را بررسی کنید.</p>
                <a class="m360-rw-btn" href="erp-reception-intake-file.php?online_request_id=<?= $requestId ?>">تکمیل پرونده پذیرش</a>
            </section>

            <section class="m360-rw-panel m360-rw-controlled-actions">
                <h2>اقدامات پس از بررسی پرونده</h2>
                <p class="m360-rw-muted">اقدامات حساس (رد، پذیرش، تبدیل) فقط پس از تکمیل پرونده و بررسی Gate در صفحه پرونده پذیرش انجام می‌شوند.</p>
                <ul class="m360-rw-controlled-list">
                    <li>در <strong>پذیرش موقت</strong>، پس از بررسی پرونده، <strong>رد درخواست</strong> و <strong>درخواست تکمیل اطلاعات</strong> مجاز است.</li>
                    <li><strong>تبدیل به کارت کار</strong> فقط وقتی مجاز است که مسیر عیب/خدمت مشخص و Gate آماده تبدیل باشد.</li>
                    <li>ارجاع کارشناسی / عیب‌یابی اولیه در فاز تکمیل عملیات پذیرش ثبت می‌شود.</li>
                </ul>
                <?php if ($gateLabel !== ''): ?>
                    <p class="m360-rw-gate-status">وضعیت Gate (خلاصه): <?= m360_rw_h($gateLabel) ?></p>
                <?php endif; ?>
                <p class="m360-rw-warn" style="margin-top:0.75rem;">برای انجام اقدام، به <a href="erp-reception-intake-file.php?online_request_id=<?= $requestId ?>">پرونده پذیرش</a> بروید — تبدیل به کارت کار در این صفحه به‌صورت دکمه آماده نمایش داده نمی‌شود.</p>
            </section>
        <?php endif; ?>

        <?php if ($history !== []): ?>
            <section class="m360-rw-panel">
                <h2>سابقه تغییرات</h2>
                <ul class="m360-rw-list">
                    <?php foreach ($history as $h): ?>
                        <li>
                            <?= m360_reception_h((string)($h['created_at'] ?? '')) ?>
                            — <?= m360_reception_h((string)($h['event_type'] ?? '')) ?>
                            <?php if ((string)($h['new_status'] ?? '') !== ''): ?>
                                (<?= m360_reception_h(m360_online_req_status_label_fa((string)$h['new_status'])) ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="m360-rw-footer">
        <a href="erp-reception-board.php">بازگشت به مرکز ارتباط با مشتریان</a>
        <a href="erp-reception-intake-file.php?online_request_id=<?= $requestId ?>">تکمیل پرونده پذیرش</a>
        <a href="erp-reception-online-requests.php">بازگشت به فهرست</a>
    </nav>
</div>
</body>
</html>
