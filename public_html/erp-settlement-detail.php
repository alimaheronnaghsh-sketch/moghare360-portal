<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-settlement-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-jobcard-close-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_fi_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && $_GET['ok'] === '1';

/** @var array<string, string> */
$settleLabels = [
    M360_SETTLE_PAYMENT_PENDING => 'در انتظار پرداخت',
    M360_SETTLE_PARTIAL => 'تسویه جزئی',
    M360_SETTLE_SETTLED => 'تسویه کامل',
    M360_SETTLE_MANAGER_RELEASE => 'مجوز مدیریتی',
    M360_SETTLE_BLOCKED => 'مسدود',
];

function m360_settle_detail_label(string $status, array $labels): string
{
    return $labels[strtoupper(trim($status))] ?? $status;
}

function m360_settle_form(string $action, int $jobcardId, int $settlementId, string $label, string $class = '', bool $withReason = false, string $reasonLabel = 'توضیح'): void
{
    echo '<form method="post" action="erp-settlement-action.php" class="m360-est-inline-form">';
    echo erp_csrf_input(M360_FI_CSRF);
    echo '<input type="hidden" name="jobcard_id" value="' . (int)$jobcardId . '">';
    echo '<input type="hidden" name="settlement_id" value="' . (int)$settlementId . '">';
    echo '<input type="hidden" name="action" value="' . m360_fi_h($action) . '">';
    if ($withReason) {
        echo '<textarea name="reason" class="m360-est-input" placeholder="' . m360_fi_h($reasonLabel) . '" required minlength="3" style="min-height:70px"></textarea>';
    }
    echo '<button type="submit" class="m360-est-btn ' . m360_fi_h($class) . '">' . m360_fi_h($label) . '</button>';
    echo '</form>';
}

$conn = customer_core_db();
$jc = null;
$invoice = null;
$settlement = null;
$canRelease = false;
$releaseMessage = '';

if ($conn !== false && $jobcardId > 0) {
    $jc = m360_fi_fetch_jobcard($conn, $jobcardId);
    $invoice = m360_fi_fetch_active($conn, $jobcardId);
    $settlement = m360_settlement_fetch_active($conn, $jobcardId);
    if ($settlement !== null) {
        $gate = m360_settlement_can_release($conn, $jobcardId, $settlement);
        $canRelease = $gate['ok'];
        $releaseMessage = $gate['message'];
    }
}

$settlementId = is_array($settlement) ? (int)($settlement['settlement_id'] ?? 0) : 0;
$invoiceFinalized = is_array($invoice) && strtoupper((string)($invoice['invoice_status'] ?? '')) === M360_FI_FINALIZED;
$totalDue = (float)(is_array($settlement) ? ($settlement['total_due_amount'] ?? 0) : (is_array($invoice) ? ($invoice['total_amount'] ?? 0) : 0));
$totalPaid = (float)(is_array($settlement) ? ($settlement['total_paid_amount'] ?? 0) : 0);
$remaining = (float)(is_array($settlement) ? ($settlement['remaining_amount'] ?? max(0.0, $totalDue - $totalPaid)) : max(0.0, $totalDue - $totalPaid));
$settleStatus = (string)(is_array($settlement) ? ($settlement['settlement_status'] ?? '') : (is_array($jc) ? ($jc['settlement_status'] ?? '') : ''));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کنترل تسویه</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
    <style>.m360-est-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.m360-est-kv{margin:.3rem 0;font-size:.9rem}.m360-est-kv strong{color:#475569}.m360-est-badge{display:inline-block;padding:.15rem .5rem;border-radius:999px;background:#ccfbf1;color:#0f766e;font-size:.78rem}.m360-est-alert{padding:.8rem;border-radius:.45rem;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;margin:.6rem 0}@media(max-width:720px){.m360-est-grid{grid-template-columns:1fr}}</style>
</head>
<body class="m360-est-page">
<div class="w1c-wrap m360-est-wrap">
    <?php
    $opsStrip = null;
    if ($jc !== null) {
        $jcRow = is_array($settlement) ? array_merge($jc, $settlement) : $jc;
        $settleAllowed = $canRelease ? ['close_jobcard'] : [];
        $opsStrip = m360_operational_shell_build_jobcard_strip($conn, $jcRow, 'settlement', $settleStatus, $settleAllowed, $releaseMessage);
        $opsStrip['doc_type_fa'] = 'سند تسویه';
    }
    m360_operational_shell_render_detail('settlement_detail', 'erp-final-invoice-detail.php?jobcard_id=' . $jobcardId, $jobcardId, $opsStrip);
    ?>
    <?php if ($flash !== ''): ?><div class="m360-est-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_fi_h($flash) ?></div><?php endif; ?>

    <?php if ($jc === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <h2>تسویه — کارت کار <?= m360_fi_h((string)$jobcardId) ?></h2>
            <div class="m360-est-grid">
                <div>
                    <p class="m360-est-kv"><strong>مشتری:</strong> <?= m360_fi_h((string)($jc['customer_name'] ?? '-')) ?></p>
                    <p class="m360-est-kv"><strong>موبایل:</strong> <?= m360_fi_h((string)($jc['customer_mobile'] ?? '-')) ?></p>
                    <p class="m360-est-kv"><strong>خودرو:</strong> <?= m360_fi_h(trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')) ?: '-') ?></p>
                    <p class="m360-est-kv"><strong>پلاک:</strong> <?= m360_fi_h((string)($jc['plate_number'] ?? '-')) ?></p>
                </div>
                <div>
                    <p class="m360-est-kv"><strong>فاکتور نهایی:</strong> <?= $invoiceFinalized ? 'نهایی‌شده' : 'هنوز نهایی نشده' ?></p>
                    <p class="m360-est-kv"><strong>تحویل مشتری:</strong> <?= m360_fi_h((string)($jc['customer_delivery_status'] ?? '-')) ?></p>
                    <p class="m360-est-kv"><strong>خروج خودرو:</strong> <?= trim((string)($jc['vehicle_released_at'] ?? '')) !== '' ? 'تحویل شده' : 'تحویل نشده' ?></p>
                    <p class="m360-est-kv"><strong>وضعیت کارت کار:</strong> <?= m360_fi_h((string)($jc['jobcard_status'] ?? '-')) ?></p>
                </div>
            </div>
        </section>

        <section class="w1c-card">
            <h3>خلاصه تسویه</h3>
            <?php if (!$invoiceFinalized): ?>
                <div class="m360-est-alert">فاکتور نهایی هنوز نهایی نشده — تسویه پس از نهایی‌سازی فعال می‌شود.</div>
            <?php elseif ($settlement === null): ?>
                <div class="m360-est-alert">رکورد تسویه یافت نشد. ابتدا تسویه را محاسبه کنید.</div>
            <?php else: ?>
                <p class="m360-est-kv"><strong>وضعیت:</strong> <span class="m360-est-badge"><?= m360_fi_h(m360_settle_detail_label($settleStatus, $settleLabels)) ?></span></p>
                <p class="m360-est-kv"><strong>مبلغ قابل پرداخت:</strong> <?= m360_fi_h(number_format($totalDue)) ?> تومان</p>
                <p class="m360-est-kv"><strong>پرداخت‌شده:</strong> <?= m360_fi_h(number_format($totalPaid)) ?> تومان</p>
                <p class="m360-est-kv"><strong>مانده:</strong> <?= m360_fi_h(number_format($remaining)) ?> تومان</p>
                <?php if (!empty($settlement['manager_release_reason'])): ?>
                    <p class="m360-est-kv"><strong>دلیل مجوز مدیریتی:</strong> <?= m360_fi_h((string)$settlement['manager_release_reason']) ?></p>
                <?php endif; ?>
                <?php if (!$canRelease && $releaseMessage !== ''): ?>
                    <div class="m360-est-alert"><?= m360_fi_h($releaseMessage) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($invoiceFinalized): ?>
        <section class="w1c-card m360-est-actions">
            <h3>عملیات تسویه</h3>
            <?php m360_settle_form('recalculate_settlement', $jobcardId, $settlementId, 'محاسبه مجدد تسویه', 'secondary'); ?>
            <?php if ($settlementId > 0): ?>
                <?php m360_settle_form('mark_settled', $jobcardId, $settlementId, 'ثبت تسویه کامل', 'primary'); ?>
                <?php m360_settle_form('manager_release_approval', $jobcardId, $settlementId, 'مجوز خروج با مانده بدهی', 'secondary', true, 'دلیل مجوز مدیریتی (اجباری)'); ?>
                <?php m360_settle_form('block_delivery', $jobcardId, $settlementId, 'مسدودسازی تحویل', 'danger', true, 'دلیل مسدودسازی'); ?>
            <?php endif; ?>
        </section>

        <section class="w1c-card m360-est-actions">
            <h3>تحویل و بستن پرونده</h3>
            <?php m360_settle_form('release_vehicle', $jobcardId, $settlementId, 'تحویل خودرو به مشتری', 'primary'); ?>
            <?php m360_settle_form('close_jobcard', $jobcardId, $settlementId, 'بستن کارت کار', 'secondary'); ?>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
