<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-settlement-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_fi_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$invoiceId = isset($_GET['final_invoice_id']) ? (int)$_GET['final_invoice_id'] : 0;
$flash = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$flashOk = isset($_GET['ok']) && $_GET['ok'] === '1';

$conn = customer_core_db();
$jc = null;
$inv = null;
$items = [];
$settlement = null;
$gatesOk = false;
$gateMessage = '';
$invoiceStatus = '';
$allowed = [];

if ($conn !== false) {
    if ($invoiceId > 0) {
        $inv = m360_fi_fetch_invoice($conn, $invoiceId);
        if ($inv !== null) {
            $jobcardId = (int)($inv['jobcard_id'] ?? $jobcardId);
        }
    } elseif ($jobcardId > 0) {
        $inv = m360_fi_fetch_active($conn, $jobcardId);
        if ($inv !== null) {
            $invoiceId = (int)($inv['final_invoice_id'] ?? 0);
        }
    }

    if ($jobcardId > 0) {
        $jc = m360_fi_fetch_jobcard($conn, $jobcardId);
        if ($jc !== null) {
            $gate = m360_p7_assert_gates($conn, $jobcardId, $jc);
            $gatesOk = $gate['ok'];
            $gateMessage = (string)($gate['message'] ?? '');
        }
        $settlement = m360_settlement_fetch_active($conn, $jobcardId);
    }

    if ($invoiceId > 0) {
        $items = m360_fi_list_items($conn, $invoiceId);
    }

    if ($inv !== null) {
        $invoiceStatus = strtoupper(trim((string)($inv['invoice_status'] ?? '')));
    }

    if (function_exists('m360_fi_allowed_actions')) {
        $allowed = m360_fi_allowed_actions($jc ?? [], $inv, $gatesOk, $settlement);
    } else {
        $allowed = m360_fi_detail_default_actions($invoiceStatus, $inv !== null, $gatesOk);
    }
}

/**
 * @return list<string>
 */
function m360_fi_detail_default_actions(string $invoiceStatus, bool $hasInvoice, bool $gatesOk): array
{
    if (!$hasInvoice) {
        return $gatesOk ? ['create_draft_invoice'] : [];
    }
    $actions = [];
    if (in_array($invoiceStatus, [M360_FI_DRAFT, M360_FI_CALCULATED], true)) {
        $actions[] = 'calculate_invoice';
        $actions[] = 'add_approved_manual_item';
        $actions[] = 'apply_discount';
        $actions[] = 'cancel_draft_invoice';
    }
    if ($invoiceStatus === M360_FI_CALCULATED) {
        $actions[] = 'finalize_invoice';
    }
    if ($invoiceStatus === M360_FI_FINALIZED) {
        $actions[] = 'recalculate_settlement';
        $actions[] = 'notify_customer';
    }
    return $actions;
}

function m360_fi_detail_form(string $action, int $jobcardId, int $invoiceId, string $label, string $class = 'secondary'): void
{
    echo '<form method="post" action="erp-final-invoice-action.php" class="m360-fi-inline-form">';
    echo erp_csrf_input(M360_FI_CSRF);
    echo '<input type="hidden" name="jobcard_id" value="' . (int)$jobcardId . '">';
    if ($invoiceId > 0) {
        echo '<input type="hidden" name="final_invoice_id" value="' . (int)$invoiceId . '">';
    }
    echo '<input type="hidden" name="action" value="' . m360_fi_h($action) . '">';
    echo '<button type="submit" class="m360-fi-btn ' . m360_fi_h($class) . '">' . m360_fi_h($label) . '</button>';
    echo '</form>';
}

function m360_fi_money(float $amount): string
{
    return number_format($amount);
}

$varianceClass = 'm360-fi-variance-ok';
$varianceStatus = strtoupper(trim((string)($inv['variance_status'] ?? '')));
if ($varianceStatus === 'BLOCKED' || $varianceStatus === 'OVERRIDE_REQUIRED') {
    $varianceClass = 'm360-fi-variance-block';
} elseif ($varianceStatus === 'WARNING' || abs((float)($inv['variance_amount'] ?? 0)) > 0) {
    $varianceClass = 'm360-fi-variance-warn';
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات فاکتور نهایی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-final-delivery.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
</head>
<body class="m360-fi-page">
<div class="w1c-wrap m360-fi-wrap">
    <?php
    $opsStrip = null;
    if ($jc !== null) {
        $opsStrip = m360_operational_shell_build_jobcard_strip($conn, $jc, 'invoice', $invoiceStatus, $allowed, $gateMessage);
        $opsStrip['doc_type_fa'] = 'سند فاکتور نهایی';
    }
    m360_operational_shell_render_detail('invoice_detail', 'erp-final-invoice-board.php', $jobcardId, $opsStrip);
    ?>
    <?php if ($flash !== ''): ?><div class="m360-fi-flash <?= $flashOk ? 'ok' : 'err' ?>"><?= m360_fi_h($flash) ?></div><?php endif; ?>

    <?php if ($jc === null): ?>
        <section class="w1c-card"><p>کارت کار یافت نشد.</p></section>
    <?php else: ?>
        <section class="w1c-card">
            <h2>فاکتور نهایی — کارت کار <?= m360_fi_h((string)$jobcardId) ?></h2>
            <div class="m360-fi-grid">
                <div>
                    <p class="m360-fi-kv"><strong>مشتری:</strong> <?= m360_fi_h((string)($jc['customer_name'] ?? '-')) ?></p>
                    <p class="m360-fi-kv"><strong>موبایل:</strong> <?= m360_fi_h((string)($jc['customer_mobile'] ?? '-')) ?></p>
                    <p class="m360-fi-kv"><strong>خودرو:</strong> <?= m360_fi_h(trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')) ?: '-') ?></p>
                    <p class="m360-fi-kv"><strong>پلاک:</strong> <?= m360_fi_h((string)($jc['plate_number'] ?? '-')) ?></p>
                    <p class="m360-fi-kv"><strong>قرارداد:</strong> <?= m360_fi_h((string)($jc['contract_status'] ?? '-')) ?></p>
                </div>
                <div>
                    <p class="m360-fi-kv"><strong>QC:</strong> <span class="m360-fi-badge"><?= m360_fi_h(m360_qc_status_label(m360_qc_effective_status($jc))) ?></span></p>
                    <p class="m360-fi-kv"><strong>آمادگی تحویل:</strong> <?= m360_fi_h((string)($jc['delivery_readiness_status'] ?? '-')) ?></p>
                    <p class="m360-fi-kv"><strong>برآورد:</strong> <?= m360_fi_h(m360_estimate_status_label((string)($jc['estimate_status'] ?? ''))) ?></p>
                    <p class="m360-fi-kv"><strong>اجرای کار:</strong> <?= m360_fi_h(m360_work_status_label(m360_work_effective_status($jc))) ?></p>
                    <p class="m360-fi-kv"><strong>وضعیت تحویل مشتری:</strong> <?= m360_fi_h((string)($jc['customer_delivery_status'] ?? '-')) ?></p>
                </div>
            </div>
            <p class="m360-fi-kv"><strong>یادداشت تکمیل فنی:</strong> <?= m360_fi_h((string)($jc['technical_completion_notes'] ?? '-')) ?></p>
            <?php if (!$gatesOk): ?>
                <div class="m360-fi-alert"><?= m360_fi_h($gateMessage !== '' ? $gateMessage : 'گیت‌های P6/P5/P4/P1.5 باز نشده‌اند.') ?></div>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h3>خلاصه فاکتور</h3>
            <?php if ($inv === null): ?>
                <p class="m360-fi-kv">فاکتور نهایی هنوز ایجاد نشده است.</p>
            <?php else: ?>
                <p class="m360-fi-kv">
                    <strong>شماره:</strong> <?= m360_fi_h((string)($inv['invoice_no'] ?? '-')) ?> |
                    <strong>وضعیت:</strong> <span class="m360-fi-badge"><?= m360_fi_h(function_exists('m360_fi_status_label') ? m360_fi_status_label($invoiceStatus) : $invoiceStatus) ?></span>
                </p>
                <p class="m360-fi-kv">
                    <strong>جمع جزء:</strong> <?= m360_fi_h(m360_fi_money((float)($inv['subtotal_amount'] ?? 0))) ?> |
                    <strong>تخفیف:</strong> <?= m360_fi_h(m360_fi_money((float)($inv['discount_amount'] ?? 0))) ?> |
                    <strong>مالیات:</strong> <?= m360_fi_h(m360_fi_money((float)($inv['tax_amount'] ?? 0))) ?> |
                    <strong>مبلغ نهایی:</strong> <?= m360_fi_h(m360_fi_money((float)($inv['total_amount'] ?? 0))) ?> تومان
                </p>
                <p class="m360-fi-kv">
                    <strong>برآورد تأییدشده:</strong> <?= m360_fi_h(m360_fi_money((float)($inv['estimate_total_amount'] ?? 0))) ?> |
                    <strong>اختلاف:</strong> <span class="<?= $varianceClass ?>"><?= m360_fi_h(m360_fi_money((float)($inv['variance_amount'] ?? 0))) ?></span>
                    <?php if ($varianceStatus !== ''): ?> (<?= m360_fi_h($varianceStatus) ?>)<?php endif; ?>
                </p>
                <?php if (trim((string)($inv['variance_override_reason'] ?? '')) !== ''): ?>
                    <p class="m360-fi-kv"><strong>دلیل override اختلاف:</strong> <?= m360_fi_h((string)$inv['variance_override_reason']) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h3>اقلام فاکتور نهایی</h3>
            <?php if ($items === []): ?>
                <p class="m360-fi-kv">—</p>
            <?php else: ?>
                <table class="m360-fi-table">
                    <thead><tr><th>عنوان</th><th>منبع</th><th>تعداد</th><th>قیمت واحد</th><th>جمع</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= m360_fi_h((string)($item['item_title'] ?? '-')) ?></td>
                            <td><?= m360_fi_h((string)($item['source_type'] ?? '-')) ?></td>
                            <td><?= m360_fi_h((string)($item['quantity'] ?? '1')) ?></td>
                            <td><?= m360_fi_h(m360_fi_money((float)($item['unit_price'] ?? 0))) ?></td>
                            <td><?= m360_fi_h(m360_fi_money((float)($item['line_total'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="w1c-card">
            <h3>خلاصه تسویه</h3>
            <?php if ($settlement === null): ?>
                <p class="m360-fi-kv">رکورد تسویه هنوز ایجاد نشده — پس از نهایی‌سازی فاکتور محاسبه می‌شود.</p>
            <?php else: ?>
                <p class="m360-fi-kv">
                    <strong>وضعیت:</strong> <?= m360_fi_h((string)($settlement['settlement_status'] ?? '-')) ?> |
                    <strong>مبلغ قابل پرداخت:</strong> <?= m360_fi_h(m360_fi_money((float)($settlement['total_due_amount'] ?? 0))) ?> |
                    <strong>پرداخت‌شده:</strong> <?= m360_fi_h(m360_fi_money((float)($settlement['total_paid_amount'] ?? 0))) ?> |
                    <strong>مانده:</strong> <?= m360_fi_h(m360_fi_money((float)($settlement['remaining_amount'] ?? 0))) ?>
                </p>
                <?php if ((bool)($settlement['manager_release_approved'] ?? false)): ?>
                    <p class="m360-fi-kv"><strong>مجوز مدیریتی:</strong> <?= m360_fi_h((string)($settlement['manager_release_reason'] ?? 'ثبت شده')) ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <a class="m360-fi-section-link" href="erp-settlement-detail.php?jobcard_id=<?= $jobcardId ?>">→ جزئیات و عملیات تسویه</a>
        </section>

        <section class="w1c-card m360-fi-actions">
            <h3>عملیات فاکتور نهایی</h3>

            <?php if (in_array('create_draft_invoice', $allowed, true)): ?>
                <?php m360_fi_detail_form('create_draft_invoice', $jobcardId, $invoiceId, 'ایجاد پیش‌نویس فاکتور'); ?>
            <?php endif; ?>

            <?php if (in_array('calculate_invoice', $allowed, true)): ?>
                <?php m360_fi_detail_form('calculate_invoice', $jobcardId, $invoiceId, 'محاسبه فاکتور'); ?>
            <?php endif; ?>

            <?php if (in_array('add_approved_manual_item', $allowed, true)): ?>
            <div class="m360-fi-action-block">
                <h4>افزودن آیتم دستی تأییدشده</h4>
                <form method="post" action="erp-final-invoice-action.php" class="m360-fi-inline-fields">
                    <?= erp_csrf_input(M360_FI_CSRF) ?>
                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                    <input type="hidden" name="final_invoice_id" value="<?= $invoiceId ?>">
                    <input type="hidden" name="action" value="add_approved_manual_item">
                    <input type="text" name="title" class="m360-fi-input" placeholder="عنوان" required>
                    <input type="number" name="qty" class="m360-fi-input" placeholder="تعداد" value="1" min="0.01" step="0.01" required>
                    <input type="number" name="price" class="m360-fi-input" placeholder="قیمت واحد" min="0" step="1" required>
                    <button type="submit" class="m360-fi-btn secondary">افزودن</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (in_array('apply_discount', $allowed, true)): ?>
            <div class="m360-fi-action-block">
                <h4>اعمال تخفیف</h4>
                <form method="post" action="erp-final-invoice-action.php" class="m360-fi-inline-fields">
                    <?= erp_csrf_input(M360_FI_CSRF) ?>
                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                    <input type="hidden" name="final_invoice_id" value="<?= $invoiceId ?>">
                    <input type="hidden" name="action" value="apply_discount">
                    <input type="number" name="amount" class="m360-fi-input" placeholder="مبلغ تخفیف" min="0" step="1" required>
                    <button type="submit" class="m360-fi-btn secondary">اعمال تخفیف</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (in_array('finalize_invoice', $allowed, true)): ?>
            <div class="m360-fi-action-block">
                <h4>نهایی‌سازی فاکتور</h4>
                <form method="post" action="erp-final-invoice-action.php">
                    <?= erp_csrf_input(M360_FI_CSRF) ?>
                    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
                    <input type="hidden" name="final_invoice_id" value="<?= $invoiceId ?>">
                    <input type="hidden" name="action" value="finalize_invoice">
                    <textarea name="variance_override_reason" class="m360-fi-textarea" placeholder="در صورت اختلاف بیش از حد مجاز، دلیل override مدیر را وارد کنید"></textarea>
                    <button type="submit" class="m360-fi-btn pass" style="margin-top:0.5rem;">نهایی‌سازی فاکتور</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (in_array('recalculate_settlement', $allowed, true)): ?>
                <?php m360_fi_detail_form('recalculate_settlement', $jobcardId, $invoiceId, 'محاسبه مجدد تسویه', 'secondary'); ?>
            <?php endif; ?>

            <?php if (in_array('notify_customer', $allowed, true)): ?>
                <?php m360_fi_detail_form('notify_customer', $jobcardId, $invoiceId, 'اطلاع‌رسانی به مشتری (لینک تحویل)', 'pass'); ?>
            <?php endif; ?>

            <?php if (in_array('cancel_draft_invoice', $allowed, true)): ?>
                <?php m360_fi_detail_form('cancel_draft_invoice', $jobcardId, $invoiceId, 'لغو پیش‌نویس', 'danger'); ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
