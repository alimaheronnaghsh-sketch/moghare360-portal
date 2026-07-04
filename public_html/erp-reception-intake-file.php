<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.9-C-2B — Reception intake completion shell (read-only GET).
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

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پرونده پذیرش #<?= $onlineRequestId ?> — MOGHARE360</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
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
    </header>

    <?php if ($request === null): ?>
        <section class="m360-rw-alert">درخواست یافت نشد یا شناسه نامعتبر است.</section>
        <div class="m360-rw-actions">
            <a class="m360-rw-btn" href="erp-reception-online-requests.php">بازگشت به درخواست‌های آنلاین</a>
        </div>
    <?php else: ?>

        <!-- 1. وضعیت و Gate -->
        <section class="m360-rw-section-block" id="sec-gate">
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

        <!-- 2. اطلاعات مشتری و خودرو -->
        <section class="m360-rw-section-block" id="sec-customer-vehicle">
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

        <!-- 3. دسته‌بندی خدمات پذیرشگر -->
        <section class="m360-rw-section-block" id="sec-service-class">
            <h2 class="m360-rw-section-title">۳. دسته‌بندی خدمات پذیرشگر</h2>
            <div class="m360-rw-panel m360-rw-service-class-panel">
                <p class="m360-rw-muted"><?= m360_rw_h(M360_RW_SERVICE_CLASS_BUSINESS_PURPOSE_FA) ?></p>
                <?php if ($customerRequestType !== ''): ?>
                    <p class="m360-rw-warn">نوع درخواست مشتری: <?= m360_rw_h($customerRequestType) ?> — <?= m360_rw_h(M360_RW_CUSTOMER_REQUEST_TYPE_NOTE_FA) ?></p>
                <?php endif; ?>
                <?php if (!empty($serviceClass['registered']) && !empty($serviceClass['selected_labels'])): ?>
                    <p><strong>ثبت‌شده:</strong> <?= m360_rw_h(implode('، ', $serviceClass['selected_labels'])) ?></p>
                <?php else: ?>
                    <p class="m360-rw-warn">دسته‌بندی خدمات توسط پذیرشگر ثبت نشده است.</p>
                    <p class="m360-rw-placeholder"><?= m360_rw_h(M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER) ?></p>
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
            </div>
        </section>

        <!-- 4. مستندات -->
        <section class="m360-rw-section-block" id="sec-docs">
            <h2 class="m360-rw-section-title">۴. مستندات / عکس / دیاگ / قرارداد / توافق</h2>
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
                    <p class="m360-rw-muted">پس از تبدیل به کارت کار، لینک دوربین و دیاگ فعال می‌شود.</p>
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

        <!-- 5. چک‌لیست و مسیر بعدی -->
        <section class="m360-rw-section-block" id="sec-next">
            <h2 class="m360-rw-section-title">۵. چک‌لیست و مسیر بعدی</h2>
            <div class="m360-rw-panel">
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
            <?php elseif ($canAct && !empty($gate['can_show_convert'])): ?>
            <div class="m360-rw-panel">
                <h3>تبدیل به کارت کار — پذیرش کامل</h3>
                <p class="m360-rw-muted">مسیر عیب/خدمت مشخص است و گیت پذیرش کامل عبور کرده است.</p>
                <form method="post" action="erp-reception-online-request-accept.php" onsubmit="return confirm('درخواست به کارت کار تبدیل شود؟');">
                    <?= $csrfConvertHtml ?>
                    <input type="hidden" name="request_id" value="<?= $onlineRequestId ?>">
                    <input type="hidden" name="action" value="convert_to_jobcard">
                    <button type="submit" class="m360-rw-btn">تبدیل به کارت کار</button>
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
