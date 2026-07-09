<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/m360-otp-helper.php';
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-reception-workbench-helper.php';

m360_otp_session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && (string)($_GET['logout'] ?? '') === '1') {
    if (function_exists('m360_otp_reset_verified')) {
        m360_otp_reset_verified();
    }
    header('Location: customer-request.php', true, 302);
    exit;
}

$mobile = m360_rw_customer_profile_require_verified_session();

$conn = customer_core_db();
if (!is_resource($conn)) {
    m360_rw_customer_profile_render_error_page('اتصال به سامانه در حال حاضر ممکن نیست. لطفاً بعداً دوباره تلاش کنید.');
}

try {
    $customerRow = m360_rw_customer_profile_fetch_customer_row($conn, $mobile);
    $customer = m360_rw_customer_profile_normalize_customer_row($customerRow);
    $fullName = $customer['full_name'];
    $customerId = (int)($customer['customer_id'] ?? 0);

    $activeOnlineRequest = m360_rw_customer_profile_detect_active_online_request($conn, $mobile);
    $vehicles = $customerId > 0 ? m360_rw_customer_profile_list_vehicles($conn, $customerId) : [];

    $erpContractTasks = m360_rw_customer_profile_contract_cartable_tasks($conn, $mobile);
    $erpContractBadgeCount = m360_rw_customer_profile_contract_badge_count($erpContractTasks);
    $erpActiveContractTask = m360_rw_customer_profile_active_contract_task($erpContractTasks);

    $isProfileComplete = $fullName !== ''
        && preg_match('/^[0-9]{10}$/', $customer['national_id']) === 1
        && $customer['address'] !== '';

    mirror_render_head('پروفایل مشتری', 'customer');
    ?>
<section class="m360-hero m360-hero--luxury">
    <h2>پروفایل مشتری</h2>
    <p>خلاصه حساب کاربری، کارتابل قرارداد و پرونده‌های فعال شما</p>
</section>

<section class="m360-card">
    <?php if ($erpContractBadgeCount > 0): ?>
        <p class="m360-pill">کارتابل: <?= mirror_h((string)$erpContractBadgeCount) ?> مأموریت نیازمند اقدام</p>
    <?php endif; ?>
    <div class="m360-profile-summary">
        <div class="m360-avatar" aria-hidden="true"><?= mirror_h(m360_rw_customer_profile_initial_letter($fullName !== '' ? $fullName : $mobile)) ?></div>
        <div>
            <h3 class="m360-step-title"><?= mirror_h($fullName !== '' ? $fullName : 'مشتری') ?></h3>
            <p class="m360-muted mobile-field"><?= mirror_h($mobile) ?></p>
            <p class="m360-muted"><?= mirror_h($customer['address'] !== '' ? $customer['address'] : 'آدرس ثبت نشده') ?></p>
            <?php if ($customer['city'] !== ''): ?>
                <p class="m360-muted"><?= mirror_h($customer['city']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="m360-card m360-step-card">
    <h3 class="m360-step-title">مسیر پذیرش خودرو</h3>
    <ol class="m360-customer-wizard-progress__list">
        <li<?= $isProfileComplete ? ' class="is-done"' : '' ?>>تکمیل پروفایل</li>
        <li<?= $activeOnlineRequest !== null ? ' class="is-active"' : '' ?>>ثبت پذیرش خودرو</li>
        <li<?= ($activeOnlineRequest !== null && $erpContractBadgeCount < 1) ? ' class="is-active"' : '' ?>>تأیید قرارداد</li>
        <li>پیگیری و تحویل</li>
    </ol>
</section>

<section class="m360-card">
    <h3 class="m360-step-title">داشبورد مشتری</h3>

    <?php if (is_array($erpActiveContractTask)): ?>
        <div class="m360-step-card m360-step-card--active" style="border:2px solid #b45309;background:#fffbeb;margin-bottom:1rem;">
            <h4 class="m360-step-title"><?= mirror_h((string)$erpActiveContractTask['title']) ?></h4>
            <p class="m360-muted">کد پرونده: <?= mirror_h((string)$erpActiveContractTask['jobcard_code']) ?></p>
            <p><?= mirror_h((string)$erpActiveContractTask['message']) ?></p>
            <p class="m360-muted">وضعیت: <?= mirror_h((string)$erpActiveContractTask['status_label']) ?></p>
            <p class="m360-action-row">
                <a class="m360-btn m360-btn-primary" href="<?= mirror_h((string)$erpActiveContractTask['review_url']) ?>">
                    <?= mirror_h((string)$erpActiveContractTask['action_label']) ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <?php foreach ($erpContractTasks as $erpTask): ?>
        <?php if (!empty($erpTask['is_active'])): ?>
            <?php continue; ?>
        <?php endif; ?>
        <?php if (empty($erpTask['is_completed']) && empty($erpTask['is_legacy'])): ?>
            <?php continue; ?>
        <?php endif; ?>
        <?php
        $historyStyle = !empty($erpTask['is_legacy'])
            ? 'margin-bottom:1rem;opacity:0.95;border:1px solid #d1d5db;background:#f9fafb;'
            : 'margin-bottom:1rem;';
        ?>
        <div class="m360-step-card" style="<?= mirror_h($historyStyle) ?>">
            <h4 class="m360-step-title"><?= mirror_h((string)$erpTask['title']) ?></h4>
            <p class="m360-muted">کد پرونده: <?= mirror_h((string)$erpTask['jobcard_code']) ?></p>
            <?php if (trim((string)($erpTask['message'] ?? '')) !== ''): ?>
                <p><?= mirror_h((string)$erpTask['message']) ?></p>
            <?php endif; ?>
            <p class="m360-muted">وضعیت: <?= mirror_h((string)$erpTask['status_label']) ?></p>
        </div>
    <?php endforeach; ?>

    <?php if ($activeOnlineRequest !== null): ?>
        <div class="m360-step-card" style="margin-bottom:1rem;">
            <h4 class="m360-step-title">درخواست فعال</h4>
            <p class="m360-muted">
                کد پرونده: <?= mirror_h((string)$activeOnlineRequest['jobcard_code']) ?>
                | وضعیت: <?= mirror_h((string)$activeOnlineRequest['request_status_label']) ?>
                <?php if (trim((string)($activeOnlineRequest['vehicle_plate'] ?? '')) !== ''): ?>
                    | پلاک: <?= mirror_h((string)$activeOnlineRequest['vehicle_plate']) ?>
                <?php endif; ?>
            </p>
            <p class="m360-action-row">
                <a class="m360-btn m360-btn-primary" href="customer-request.php">ادامه و پیگیری پرونده</a>
            </p>
        </div>
    <?php else: ?>
        <p class="m360-muted">در حال حاضر پرونده فعالی ندارید.</p>
        <p class="m360-action-row">
            <a class="m360-btn m360-btn-primary" href="customer-request.php">شروع پذیرش خودرو</a>
        </p>
    <?php endif; ?>

    <?php if ($vehicles !== []): ?>
        <div class="m360-step-card" style="margin-top:1rem;">
            <h4 class="m360-step-title">خودروهای ثبت‌شده</h4>
            <ul class="m360-muted">
                <?php foreach ($vehicles as $vehicle): ?>
                    <li><?= mirror_h((string)($vehicle['label'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$isProfileComplete): ?>
        <p class="m360-alert m360-alert-info" style="margin-top:1rem;">برای تکمیل اطلاعات پروفایل، از فرم درخواست آنلاین استفاده کنید.</p>
        <p class="m360-action-row">
            <a class="m360-btn m360-btn-secondary" href="customer-request.php">تکمیل پروفایل</a>
        </p>
    <?php endif; ?>

    <p class="m360-action-row" style="margin-top:1.5rem;">
        <a class="m360-btn m360-btn-secondary" href="customer-request.php">درخواست جدید</a>
        <a class="m360-btn m360-btn-ghost" href="./">بازگشت</a>
        <a class="m360-btn m360-btn-danger" href="customer-profile.php?logout=1">خروج از حساب</a>
    </p>
</section>
    <?php
    mirror_render_foot();
} catch (Throwable $e) {
    m360_rw_customer_profile_render_error_page('خطا در نمایش پروفایل مشتری.');
}
