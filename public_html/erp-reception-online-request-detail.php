<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Online request detail (GET read-only; POST actions go to accept handler).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php';

m360_reception_require_staff();

$requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && (string)$_GET['ok'] === '1';

$conn = customer_core_db();
$row = null;
$history = [];

if ($conn !== false && $requestId > 0) {
    $row = m360_online_req_fetch_by_id($conn, $requestId);
    $history = m360_reception_fetch_history($conn, $requestId);
}

$payload = $row !== null ? m360_online_req_parse_payload($row['request_payload_json'] ?? null) : [];
$convertedJobcardId = $row !== null ? m360_online_req_converted_jobcard_id($row) : 0;
$canAct = $row !== null && !m360_online_req_is_converted($row) && strtoupper((string)($row['request_status'] ?? '')) !== M360_ONLINE_REQ_STATUS_REJECTED;

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات درخواست آنلاین #<?= $requestId ?></title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <style>
        .p1-det-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem; }
        .p1-det-item { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 0.85rem; }
        .p1-det-item strong { display: block; font-size: 0.78rem; color: #71717a; margin-bottom: 0.25rem; }
        .p1-det-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; }
        .p1-det-actions form { margin: 0; }
        .p1-det-btn { border: 0; border-radius: 0.5rem; padding: 0.55rem 1rem; cursor: pointer; font-size: 0.9rem; }
        .p1-det-btn.review { background: #fef3c7; color: #92400e; }
        .p1-det-btn.accept { background: #dcfce7; color: #166534; }
        .p1-det-btn.convert { background: #166534; color: #fff; }
        .p1-det-btn.reject { background: #fee2e2; color: #991b1b; }
        .p1-det-note { white-space: pre-wrap; line-height: 1.6; }
        .p1-det-history { font-size: 0.88rem; }
        .p1-det-history li { margin-bottom: 0.35rem; }
        .p1-flash { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .p1-flash.ok { background: #dcfce7; color: #166534; }
        .p1-flash.err { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body style="background:#f8fafc;margin:0;padding:1.25rem;color:#18181b;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>جزئیات درخواست آنلاین</h1>
        <p>شناسه <?= m360_reception_h((string)$requestId) ?></p>
    </header>

    <?php if ($flash !== ''): ?>
        <div class="p1-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_reception_h($flash) ?></div>
    <?php endif; ?>

    <?php if ($row === null): ?>
        <section class="w1c-card w1c-error-box">
            <p>درخواست یافت نشد.</p>
            <p><a href="erp-reception-online-requests.php">بازگشت به فهرست</a></p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <div class="p1-det-grid">
                <div class="p1-det-item"><strong>وضعیت</strong><?= m360_reception_h(m360_online_req_status_label_fa((string)($row['request_status'] ?? ''))) ?></div>
                <div class="p1-det-item"><strong>تاریخ ثبت</strong><?= m360_reception_h((string)($row['created_at'] ?? '')) ?></div>
                <div class="p1-det-item"><strong>موبایل (تأیید‌شده)</strong><?= m360_reception_h((string)($row['mobile'] ?? '')) ?></div>
                <div class="p1-det-item"><strong>نام مشتری</strong><?= m360_reception_h((string)($row['customer_name'] ?? '')) ?></div>
                <div class="p1-det-item"><strong>پلاک</strong><?= m360_reception_h((string)($row['vehicle_plate'] ?? '')) ?></div>
                <div class="p1-det-item"><strong>تاریخ مراجعه</strong><?= m360_reception_h((string)($row['visit_date'] ?? ($payload['visit_date'] ?? '—'))) ?></div>
                <div class="p1-det-item"><strong>نوع درخواست</strong><?= m360_reception_h((string)($row['request_type'] ?? ($payload['request_type'] ?? '—'))) ?></div>
                <div class="p1-det-item"><strong>منبع</strong><?= m360_reception_h((string)($row['source_channel'] ?? '')) ?></div>
                <div class="p1-det-item"><strong>شناسه مشتری ERP</strong><?= m360_reception_h((string)($row['customer_id'] ?? '—')) ?></div>
                <div class="p1-det-item"><strong>شناسه خودرو ERP</strong><?= m360_reception_h((string)($row['vehicle_id'] ?? '—')) ?></div>
            </div>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">شرح درخواست</h2>
            <div class="p1-det-note"><?= m360_reception_h((string)($row['service_note'] ?? '')) ?></div>
        </section>

        <?php if ($convertedJobcardId > 0): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار</h2>
                <p>شناسه کارت کار: <strong><?= m360_reception_h((string)$convertedJobcardId) ?></strong></p>
                <p><a href="erp-jobcard-detail.php?jobcard_id=<?= $convertedJobcardId ?>">مشاهده کارت کار</a></p>
            </section>
        <?php endif; ?>

        <?php if ($canAct): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">اقدامات پذیرش</h2>
                <div class="p1-det-actions">
                    <form method="post" action="erp-reception-online-request-accept.php">
                        <?= erp_csrf_input(M360_RECEPTION_CSRF_PURPOSE) ?>
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="under_review">
                        <button type="submit" class="p1-det-btn review">علامت‌گذاری در حال بررسی</button>
                    </form>
                    <form method="post" action="erp-reception-online-request-accept.php">
                        <?= erp_csrf_input(M360_RECEPTION_CSRF_PURPOSE) ?>
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="p1-det-btn accept">پذیرش درخواست</button>
                    </form>
                    <form method="post" action="erp-reception-online-request-accept.php" onsubmit="return confirm('درخواست به کارت کار تبدیل شود؟');">
                        <?= erp_csrf_input(M360_RECEPTION_CSRF_PURPOSE) ?>
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="convert_to_jobcard">
                        <button type="submit" class="p1-det-btn convert">تبدیل به کارت کار</button>
                    </form>
                    <form method="post" action="erp-reception-online-request-accept.php" onsubmit="return confirm('درخواست رد شود؟');">
                        <?= erp_csrf_input(M360_RECEPTION_CSRF_PURPOSE) ?>
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="p1-det-btn reject">رد درخواست</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($history !== []): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">سابقه تغییرات</h2>
                <ul class="p1-det-history">
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

    <nav class="w1c-card w1c-links">
        <a href="erp-reception-online-requests.php">بازگشت به فهرست</a>
        <a href="erp-jobcard-command-center.php">مرکز کارت کار</a>
    </nav>
</div>
</body>
</html>
