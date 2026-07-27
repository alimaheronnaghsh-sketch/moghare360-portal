<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-contract-signature-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-tree-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-header.php';

m360_intake_contract_require_staff();

function m360_contract_detail_short_hash(string $hash): string
{
    $hash = trim($hash);
    if ($hash === '') {
        return '—';
    }
    if (strlen($hash) <= 24) {
        return $hash;
    }

    return substr($hash, 0, 12) . '…' . substr($hash, -8);
}

function m360_contract_detail_event_label(string $eventName): string
{
    static $labels = [
        'CONTRACT_ISSUED' => 'صدور قرارداد',
        'CUSTOMER_SIGNATURE_TASK_CREATED' => 'ایجاد کارتابل امضای مشتری',
        'CONTRACT_VIEWED' => 'مشاهده قرارداد',
        'CONTRACT_REVIEW_COMPLETED' => 'تکمیل مرور قرارداد',
        'CUSTOMER_CONSENT_ACCEPTED' => 'پذیرش مفاد توسط مشتری',
        'SIGNATURE_CONFIRMED_AND_LOCKED' => 'امضا تأیید و قفل شد',
        'CONTRACT_OTP_SENT' => 'ارسال کد پیامکی قرارداد',
        'CONTRACT_OTP_VERIFIED' => 'تأیید کد پیامکی قرارداد',
        'CONTRACT_CONFIRMED' => 'تأیید قرارداد',
        'CONTRACT_LOCKED' => 'قفل قرارداد',
        'CONTRACT_SIGNED' => 'امضای قرارداد',
        'CONTRACT_SENT' => 'ارسال قرارداد برای مشتری',
        'CONTRACT_MANAGER_OVERRIDE' => 'تأیید مدیریتی قرارداد',
    ];
    $key = strtoupper(trim($eventName));

    return $labels[$key] ?? ($key !== '' ? $key : 'رویداد نامشخص');
}

function m360_contract_detail_value(mixed $value): string
{
    $text = trim((string)$value);

    return $text !== '' ? $text : '—';
}

$contractId = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
$flash = trim((string)($_GET['msg'] ?? ''));
$flashOk = (string)($_GET['ok'] ?? '') === '1';
$conn = customer_core_db();
$row = ($conn !== false && $contractId > 0) ? m360_intake_contract_fetch_by_id($conn, $contractId) : null;
$events = ($conn !== false && $row !== null) ? m360_intake_contract_events($conn, $contractId) : [];
$snapshot = $row !== null ? m360_intake_contract_snapshot_from_row($row) : [];
$fullHash = $row !== null ? (string)($row['contract_body_hash'] ?? '') : '';
$shortHash = m360_contract_detail_short_hash($fullHash);
$displaySnapshot = $snapshot;
if ($displaySnapshot !== []) {
    $displaySnapshot['contract_hash'] = $shortHash;
}
$html = $displaySnapshot !== [] ? m360_contract_render_html($displaySnapshot, false) : '';
$signed = $row !== null && m360_intake_contract_is_signed($row);
$canonicalMobile = ($conn !== false && $row !== null) ? m360_contract_resolve_canonical_customer_mobile($conn, $row) : ['ok' => false, 'mobile' => '', 'source' => ''];
$canonicalMaskedMobile = !empty($canonicalMobile['ok']) ? m360_contract_mask_mobile((string)$canonicalMobile['mobile']) : '';
$snapshotMaskedMobile = $row !== null ? m360_contract_mask_mobile((string)$row['mobile']) : '';
$m360StageTree = m360_case_stage_tree_resolve($conn, [
    'contract_id' => $contractId,
    'online_request_id' => (int)($row['online_request_id'] ?? 0),
    'jobcard_id' => (int)($row['jobcard_id'] ?? 0),
]);
$vehicleSummary = trim((string)($snapshot['vehicle'] ?? ''));
$plateSummary = trim((string)($snapshot['plate'] ?? ''));
if ($vehicleSummary !== '' && $plateSummary !== '' && $plateSummary !== '-') {
    $vehicleSummary .= ' — پلاک ' . $plateSummary;
}
$isSyntheticUat = stripos(json_encode($snapshot, JSON_UNESCAPED_UNICODE) ?: '', 'AUTO-UAT') !== false;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات قرارداد پذیرش #<?= m360_intake_contract_h((string)$contractId) ?></title>
    <link rel="stylesheet" href="assets/css/mirror.css">
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
    <link rel="stylesheet" href="assets/css/m360-contract.css">
</head>
<body class="m360-public-shell m360-rw-page m360-contract-detail-shell">
<div class="m360-wrap m360-rw-wrap m360-contract-page">
    <header class="m360-rw-header">
        <div class="m360-rw-header__top">
            <a class="m360-rw-back" href="erp-reception-intake-file.php?request_id=<?= m360_intake_contract_h((string)($row['online_request_id'] ?? 0)) ?>">← پرونده پذیرش</a>
            <span class="m360-rw-badge">Luxury Dark Green RTL</span>
        </div>
        <h1 class="m360-rw-title">جزئیات قرارداد پذیرش</h1>
        <p class="m360-rw-subtitle">نمای خوانای قرارداد، وضعیت امضا و رویدادها؛ بدون تغییر در متن حقوقی، امضا، OTP یا شواهد immutable.</p>
    </header>

    <?php if ($flash !== ''): ?>
        <div class="m360-contract-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_intake_contract_h($flash) ?></div>
    <?php endif; ?>

    <?= m360_render_case_stage_header($m360StageTree) ?>

    <?php if ($row === null): ?>
        <section class="m360-card m360-rw-empty"><p>قرارداد یافت نشد.</p></section>
    <?php else: ?>
        <section class="m360-card m360-contract-summary-card">
            <div class="m360-contract-summary-head">
                <div>
                    <p class="m360-contract-eyebrow">عنوان</p>
                    <h2>جزئیات قرارداد پذیرش</h2>
                </div>
                <?php if ($isSyntheticUat): ?>
                    <span class="m360-rw-status-badge is-review">داده تست UAT</span>
                <?php endif; ?>
            </div>
            <dl class="m360-contract-summary-grid">
                <div><dt>شناسه قرارداد</dt><dd><?= m360_intake_contract_h((string)$contractId) ?></dd></div>
                <div><dt>وضعیت قرارداد</dt><dd><?= m360_intake_contract_h(M360_CONTRACT_STATUS_LABELS_FA[$row['contract_status']] ?? $row['contract_status']) ?></dd></div>
                <div><dt>JobCard ID</dt><dd><?= m360_intake_contract_h(m360_contract_detail_value($row['jobcard_id'] ?? '')) ?></dd></div>
                <div><dt>Request ID</dt><dd><?= m360_intake_contract_h(m360_contract_detail_value($row['online_request_id'] ?? '')) ?></dd></div>
                <div><dt>Customer ID</dt><dd><?= m360_intake_contract_h(m360_contract_detail_value($row['customer_id'] ?? '')) ?></dd></div>
                <div><dt>خودرو</dt><dd><?= m360_intake_contract_h(m360_contract_detail_value($vehicleSummary)) ?></dd></div>
                <div><dt>موبایل فعال OTP</dt><dd><?= m360_intake_contract_h($canonicalMaskedMobile !== '' ? $canonicalMaskedMobile : '—') ?></dd></div>
                <div><dt>موبایل ثبت اولیه</dt><dd><?= m360_intake_contract_h($snapshotMaskedMobile !== '' ? $snapshotMaskedMobile : '—') ?></dd></div>
                <div class="m360-contract-hash-cell"><dt>هش قرارداد</dt><dd><?= m360_intake_contract_h($shortHash) ?></dd></div>
            </dl>
            <?php if (!$signed): ?>
                <div class="m360-contract-actions">
                    <form method="post" action="erp-intake-contract-send.php">
                        <?= erp_csrf_input(M360_CONTRACT_CSRF_PURPOSE) ?>
                        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                        <button type="submit" class="m360-contract-btn primary">ارسال لینک برای مشتری</button>
                    </form>
                </div>
                <p class="m360-contract-help">لینک امن فقط پس از تولید/ارسال در پیامک یا کانال امن به مشتری داده می‌شود.</p>
            <?php endif; ?>
            <details class="m360-rw-tech-details">
                <summary>اطلاعات فنی / UAT</summary>
                <div class="m360-rw-tech-details-body">
                    <p>منبع موبایل OTP: <?= m360_intake_contract_h((string)($canonicalMobile['source'] ?? 'canonical customer phone resolver')) ?></p>
                    <p>هش کامل قرارداد:</p>
                    <code class="m360-contract-tech-value"><?= m360_intake_contract_h($fullHash !== '' ? $fullHash : '—') ?></code>
                </div>
            </details>
        </section>

        <section class="m360-card m360-contract-body-card" aria-label="متن قرارداد">
            <?= $html ?>
        </section>

        <section class="m360-card m360-contract-events-card">
            <h2 class="m360-rw-sec-title">رویدادهای قرارداد</h2>
            <?php if ($events === []): ?>
                <p class="m360-muted">رویدادی ثبت نشده است.</p>
            <?php else: ?>
                <ol class="m360-contract-events">
                    <?php foreach ($events as $event): ?>
                        <?php $eventName = (string)($event['event_name'] ?? ''); ?>
                        <li>
                            <strong><?= m360_intake_contract_h(m360_contract_detail_event_label($eventName)) ?></strong>
                            <span><?= m360_intake_contract_h((string)($event['created_at'] ?? '')) ?></span>
                            <small><?= m360_intake_contract_h($eventName) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="m360-rw-footer">
        <a href="erp-intake-contracts.php">فهرست قراردادها</a>
        <a href="erp-reception-intake-file.php?request_id=<?= m360_intake_contract_h((string)($row['online_request_id'] ?? 28)) ?>">پرونده پذیرش</a>
        <a href="erp-product-home.php">خانه محصول</a>
    </nav>
</div>
</body>
</html>
