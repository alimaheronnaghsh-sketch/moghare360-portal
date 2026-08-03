<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/m360-otp-helper.php';
require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-reception-workbench-helper.php';

m360_otp_session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && (string)($_GET['logout'] ?? '') === '1') {
    if (function_exists('m360_otp_reset_verified')) {
        m360_otp_reset_verified();
    }
    header('Location: ' . m360_rw_customer_portal_app_root_url('/customer-request.php'), true, 302);
    exit;
}

$mobile = m360_rw_customer_profile_require_verified_session();

$conn = customer_core_db();
if (!is_resource($conn)) {
    m360_rw_customer_profile_render_error_page('اتصال به سامانه در حال حاضر ممکن نیست. لطفاً بعداً دوباره تلاش کنید.');
}

try {
    $dashboard = m360_rw_customer_dashboard_build($conn, $mobile, [
        'contract_signed' => (string)($_GET['contract_signed'] ?? ''),
        'request_created' => (string)($_GET['request_created'] ?? ''),
        'history_page' => (string)($_GET['history_page'] ?? '1'),
    ]);
    $identity = is_array($dashboard['identity'] ?? null) ? $dashboard['identity'] : [];
    $customer = is_array($dashboard['customer'] ?? null) ? $dashboard['customer'] : [];
    $fullName = (string)($identity['display_name'] ?? 'مشتری');
    $profileNeedsCompletion = (bool)($identity['needs_completion'] ?? false);
    $banners = is_array($dashboard['banners'] ?? null) ? $dashboard['banners'] : [];
    $inbox = is_array($dashboard['inbox'] ?? null) ? $dashboard['inbox'] : [];
    $activeCases = is_array($dashboard['active_cases'] ?? null) ? $dashboard['active_cases'] : [];
    $historyCases = is_array($dashboard['history_cases'] ?? null) ? $dashboard['history_cases'] : [];
    $vehicles = is_array($dashboard['vehicles'] ?? null) ? $dashboard['vehicles'] : [];
    $financial = is_array($dashboard['financial'] ?? null) ? $dashboard['financial'] : [];
    $notifications = is_array($dashboard['notifications'] ?? null) ? $dashboard['notifications'] : [];
    $historyPage = (int)($dashboard['history_page'] ?? 1);
    $historyPages = (int)($dashboard['history_pages'] ?? 1);
    $metrics = is_array($dashboard['metrics'] ?? null) ? $dashboard['metrics'] : [];

    mirror_render_head('داشبورد مشتری', 'customer');
    ?>
<style>
.m360-dash { max-width: 960px; margin: 0 auto; padding: 0 0 2rem; }
.m360-dash-hero { padding: 1rem 0 0.5rem; }
.m360-dash-hero h2 { margin: 0 0 0.25rem; font-size: 1.35rem; color: #ecfdf5; }
.m360-dash-hero p { margin: 0; color: #a7f3d0; font-size: 0.92rem; }
.m360-dash-section { background: #0f1f17; border: 1px solid #1f4d3a; border-radius: 14px; padding: 1rem 1.1rem; margin: 0.85rem 0; }
.m360-dash-section h3 { margin: 0 0 0.65rem; font-size: 1.05rem; color: #ecfdf5; }
.m360-dash-sub { margin: -0.35rem 0 0.75rem; color: #86efac; font-size: 0.88rem; }
.m360-dash-banner { background: #14532d; border: 1px solid #22c55e; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 0.65rem; position: relative; }
.m360-dash-banner h4 { margin: 0 0 0.35rem; color: #ecfdf5; font-size: 0.98rem; }
.m360-dash-banner p { margin: 0; color: #bbf7d0; font-size: 0.88rem; }
.m360-dash-banner__close { position: absolute; left: 0.65rem; top: 0.55rem; background: transparent; border: 0; color: #86efac; font-size: 1.1rem; cursor: pointer; min-width: 2rem; min-height: 2rem; }
.m360-dash-card { background: #13261c; border: 1px solid #234d38; border-radius: 12px; padding: 0.85rem; margin-bottom: 0.65rem; }
.m360-dash-card:last-child { margin-bottom: 0; }
.m360-dash-card__title { margin: 0 0 0.35rem; color: #f0fdf4; font-size: 0.96rem; }
.m360-dash-meta { margin: 0.15rem 0; color: #a7f3d0; font-size: 0.84rem; line-height: 1.55; }
.m360-dash-chip { display: inline-block; padding: 0.15rem 0.55rem; border-radius: 999px; background: #166534; color: #ecfdf5; font-size: 0.78rem; margin-left: 0.35rem; }
.m360-dash-empty { color: #86efac; font-size: 0.9rem; margin: 0; }
.m360-dash-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.65rem; }
.m360-dash-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 2.75rem; padding: 0.55rem 1rem; border-radius: 10px; text-decoration: none; font-size: 0.92rem; border: 1px solid transparent; }
.m360-dash-btn--primary { background: #16a34a; color: #fff; }
.m360-dash-btn--secondary { background: transparent; color: #bbf7d0; border-color: #22c55e; }
.m360-dash-btn--ghost { background: transparent; color: #fca5a5; border-color: #7f1d1d; }
.m360-dash-grid { display: grid; gap: 0.65rem; }
@media (min-width: 640px) { .m360-dash-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 900px) { .m360-dash-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
.m360-dash-account { display: flex; gap: 0.85rem; align-items: flex-start; }
.m360-dash-avatar { width: 3rem; height: 3rem; border-radius: 999px; background: #166534; color: #ecfdf5; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.m360-dash-pager { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; }
.m360-dash-pager a { color: #86efac; text-decoration: none; padding: 0.35rem 0.65rem; border: 1px solid #234d38; border-radius: 8px; font-size: 0.85rem; }
.m360-dash-legacy { color: #fde68a; font-size: 0.82rem; margin-top: 0.35rem; }
body.m360-public-shell { overflow-x: hidden; }
</style>

<div class="m360-dash">
    <header class="m360-dash-hero">
        <h2>داشبورد مشتری</h2>
        <p>خلاصه پرونده‌ها، اقدامات موردنیاز و حساب کاربری شما</p>
    </header>

    <?php if ($banners !== []): ?>
    <section class="m360-dash-section" id="m360_dash_banners" aria-label="اعلان‌ها">
        <?php foreach ($banners as $banner): ?>
            <div class="m360-dash-banner" data-banner-id="<?= mirror_h((string)($banner['id'] ?? '')) ?>">
                <button type="button" class="m360-dash-banner__close" aria-label="بستن">×</button>
                <h4><?= mirror_h((string)($banner['title'] ?? '')) ?></h4>
                <p><?= mirror_h((string)($banner['message'] ?? '')) ?></p>
            </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="m360-dash-section" aria-labelledby="m360_inbox_title">
        <h3 id="m360_inbox_title">اقدامات موردنیاز من</h3>
        <?php if ($inbox === []): ?>
            <p class="m360-dash-empty">در حال حاضر اقدامی از سوی شما موردنیاز نیست.</p>
        <?php else: ?>
            <?php
            $inboxContracts = [];
            $inboxEstimates = [];
            $inboxOther = [];
            foreach ($inbox as $task) {
                $group = (string)($task['group'] ?? '');
                if ($group === '') {
                    $url = strtolower((string)($task['action_url'] ?? ''));
                    if (str_contains($url, 'intake-contract-review')) {
                        $group = 'contract';
                    } elseif (str_contains($url, 'estimate-approval')) {
                        $group = 'estimate';
                    } else {
                        $group = 'other';
                    }
                }
                if ($group === 'contract') {
                    $inboxContracts[] = $task;
                } elseif ($group === 'estimate') {
                    $inboxEstimates[] = $task;
                } else {
                    $inboxOther[] = $task;
                }
            }
            $inboxGroups = [
                'قراردادهای نیازمند امضا' => $inboxContracts,
                'برآوردهای نیازمند تأیید' => $inboxEstimates,
                'سایر اقدامات' => $inboxOther,
            ];
            ?>
            <p class="m360-dash-meta"><span class="m360-dash-chip"><?= mirror_h((string)count($inbox)) ?> مورد فعال</span></p>
            <?php foreach ($inboxGroups as $groupTitle => $groupTasks): ?>
                <?php if ($groupTasks === []) { continue; } ?>
                <h4 class="m360-dash-card__title" style="margin-top:0.75rem"><?= mirror_h($groupTitle) ?></h4>
                <?php foreach ($groupTasks as $task): ?>
                    <article class="m360-dash-card">
                        <h4 class="m360-dash-card__title"><?= mirror_h((string)($task['title'] ?? '')) ?></h4>
                        <p class="m360-dash-meta"><?= mirror_h((string)($task['message'] ?? '')) ?></p>
                        <?php if (trim((string)($task['context'] ?? '')) !== ''): ?>
                            <p class="m360-dash-meta">پرونده: <?= mirror_h((string)$task['context']) ?></p>
                        <?php endif; ?>
                        <p class="m360-dash-meta">وضعیت: <span class="m360-dash-chip"><?= mirror_h((string)($task['status_label'] ?? '')) ?></span></p>
                        <?php if (trim((string)($task['action_url'] ?? '')) !== ''): ?>
                            <div class="m360-dash-actions">
                                <a class="m360-dash-btn m360-dash-btn--primary" href="<?= mirror_h((string)$task['action_url']) ?>">
                                    <?= mirror_h((string)($task['action_label'] ?? 'اقدام')) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_active_cases_title">
        <h3 id="m360_active_cases_title">پرونده‌های در جریان</h3>
        <?php if ($activeCases === []): ?>
            <p class="m360-dash-empty">پرونده فعالی برای نمایش وجود ندارد.</p>
        <?php else: ?>
            <div class="m360-dash-grid m360-dash-grid--2">
                <?php foreach ($activeCases as $case): ?>
                    <article class="m360-dash-card">
                        <h4 class="m360-dash-card__title">پرونده <?= mirror_h((string)($case['reference'] ?? '')) ?></h4>
                        <p class="m360-dash-meta"><?= mirror_h((string)($case['vehicle_display'] ?? '')) ?><?php if (trim((string)($case['vehicle_plate'] ?? '')) !== ''): ?> — <?= mirror_h((string)$case['vehicle_plate']) ?><?php endif; ?></p>
                        <p class="m360-dash-meta">نوع خدمت: <?= mirror_h((string)($case['service_type'] ?? '')) ?></p>
                        <p class="m360-dash-meta">مرحله: <span class="m360-dash-chip"><?= mirror_h((string)($case['stage_label'] ?? '')) ?></span></p>
                        <?php
                        $nextActorRaw = trim((string)($case['next_actor'] ?? ''));
                        $nextActorFriendly = $nextActorRaw;
                        if ($nextActorRaw !== '') {
                            $na = mb_strtolower($nextActorRaw);
                            if (str_contains($na, 'reception') || str_contains($na, 'پذیرش') || str_contains($na, 'staff')) {
                                $nextActorFriendly = 'پذیرش مجموعه';
                            } elseif (str_contains($na, 'customer') || str_contains($na, 'مشتری')) {
                                $nextActorFriendly = 'شما (مشتری)';
                            }
                            echo '<p class="m360-dash-meta">ادامه پیگیری: ' . mirror_h($nextActorFriendly) . '</p>';
                        }
                        ?>
                        <p class="m360-dash-meta">اقدام پیشنهادی: <?= mirror_h((string)($case['next_action'] ?? '')) ?></p>
                        <p class="m360-dash-meta">آخرین به‌روزرسانی: <?= mirror_h((string)($case['last_update'] ?? '—')) ?></p>
                        <?php if (trim((string)($case['contract_legacy_label'] ?? '')) !== ''): ?>
                            <p class="m360-dash-legacy"><?= mirror_h((string)$case['contract_legacy_label']) ?></p>
                        <?php endif; ?>
                        <?php
                        $caseReqId = (int)($case['online_request_id'] ?? 0);
                        $caseContract = ($caseReqId > 0 && is_resource($conn))
                            ? m360_intake_contract_find_active_for_online_request($conn, $caseReqId)
                            : null;
                        if (is_array($caseContract) && (int)($caseContract['contract_id'] ?? 0) > 0) {
                            $pdfLabel = m360_contract_pdf_download_label($caseContract);
                            $pdfHref = m360_contract_pdf_download_url((int)$caseContract['contract_id'], 'customer');
                            echo '<div class="m360-dash-actions"><a class="m360-dash-btn m360-dash-btn--secondary" href="'
                                . mirror_h($pdfHref) . '">' . mirror_h($pdfLabel) . '</a></div>';
                        }
                        ?>
                        <?php if (trim((string)($case['action_url'] ?? '')) !== ''): ?>
                            <div class="m360-dash-actions">
                                <a class="m360-dash-btn m360-dash-btn--secondary" href="<?= mirror_h((string)$case['action_url']) ?>">
                                    <?= mirror_h((string)($case['action_label'] ?? 'اقدام')) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_new_request_title">
        <h3 id="m360_new_request_title">ثبت درخواست خدمت</h3>
        <p class="m360-dash-sub">رزرو پذیرش، کارشناسی، سرویس یا تعمیر خودرو</p>
        <div class="m360-dash-actions">
            <a class="m360-dash-btn m360-dash-btn--primary" href="customer-request.php?mode=new">ثبت درخواست جدید</a>
        </div>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_vehicles_title">
        <h3 id="m360_vehicles_title">خودروهای من</h3>
        <?php if ($vehicles === []): ?>
            <p class="m360-dash-empty">خودروی ثبت‌شده‌ای یافت نشد.</p>
        <?php else: ?>
            <div class="m360-dash-grid m360-dash-grid--2">
                <?php foreach ($vehicles as $vehicle): ?>
                    <article class="m360-dash-card">
                        <h4 class="m360-dash-card__title"><?= mirror_h((string)($vehicle['label'] ?? 'خودرو')) ?></h4>
                        <?php if (trim((string)($vehicle['plate'] ?? '')) !== ''): ?>
                            <p class="m360-dash-meta">پلاک: <?= mirror_h((string)$vehicle['plate']) ?></p>
                        <?php endif; ?>
                        <p class="m360-dash-meta">پرونده فعال: <?= mirror_h((string)($vehicle['active_case_count'] ?? 0)) ?></p>
                        <p class="m360-dash-meta">وضعیت: <?= mirror_h((string)($vehicle['status_label'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_history_title">
        <h3 id="m360_history_title">سوابق خدمات</h3>
        <?php if ($historyCases === []): ?>
            <p class="m360-dash-empty">سابقه خدمات پایان‌یافته‌ای ثبت نشده است.</p>
        <?php else: ?>
            <?php foreach ($historyCases as $case): ?>
                <article class="m360-dash-card">
                    <h4 class="m360-dash-card__title">پرونده <?= mirror_h((string)($case['reference'] ?? '')) ?></h4>
                    <p class="m360-dash-meta"><?= mirror_h((string)($case['vehicle_display'] ?? '')) ?> — <?= mirror_h((string)($case['service_type'] ?? '')) ?></p>
                    <p class="m360-dash-meta">وضعیت نهایی: <span class="m360-dash-chip"><?= mirror_h((string)($case['status_label'] ?? '—')) ?></span></p>
                    <p class="m360-dash-meta">تاریخ: <?= mirror_h((string)($case['last_update'] ?? '—')) ?></p>
                    <?php if (trim((string)($case['contract_legacy_label'] ?? '')) !== ''): ?>
                        <p class="m360-dash-legacy"><?= mirror_h((string)$case['contract_legacy_label']) ?></p>
                    <?php endif; ?>
                    <?php
                    $histReqId = (int)($case['online_request_id'] ?? 0);
                    $histContract = ($histReqId > 0 && is_resource($conn))
                        ? m360_intake_contract_find_active_for_online_request($conn, $histReqId)
                        : null;
                    if (is_array($histContract) && (int)($histContract['contract_id'] ?? 0) > 0
                        && function_exists('m360_intake_contract_is_signed')
                        && m360_intake_contract_is_signed($histContract)
                    ) {
                        $pdfLabel = m360_contract_pdf_download_label($histContract);
                        $pdfHref = m360_contract_pdf_download_url((int)$histContract['contract_id'], 'customer');
                        echo '<div class="m360-dash-actions"><a class="m360-dash-btn m360-dash-btn--secondary" href="'
                            . mirror_h($pdfHref) . '">' . mirror_h($pdfLabel) . '</a></div>';
                    }
                    ?>
                </article>
            <?php endforeach; ?>
            <?php
            $cartableHistory = is_array($dashboard['cartable_history'] ?? null) ? $dashboard['cartable_history'] : [];
            foreach ($cartableHistory as $histTask):
            ?>
                <article class="m360-dash-card">
                    <h4 class="m360-dash-card__title"><?= mirror_h((string)($histTask['title'] ?? 'اقدام تکمیل‌شده')) ?></h4>
                    <p class="m360-dash-meta"><?= mirror_h((string)($histTask['message'] ?? '')) ?></p>
                    <p class="m360-dash-meta">وضعیت: <span class="m360-dash-chip"><?= mirror_h((string)($histTask['status_label'] ?? 'تکمیل‌شده')) ?></span></p>
                    <?php if (trim((string)($histTask['context'] ?? '')) !== ''): ?>
                        <p class="m360-dash-meta">پرونده: <?= mirror_h((string)$histTask['context']) ?></p>
                    <?php endif; ?>
                    <p class="m360-dash-meta">تاریخ: <?= mirror_h((string)($histTask['completed_at'] ?? $histTask['updated_at'] ?? '—')) ?></p>
                </article>
            <?php endforeach; ?>
            <?php if ($historyPages > 1): ?>
                <nav class="m360-dash-pager" aria-label="صفحه‌بندی سوابق">
                    <?php for ($p = 1; $p <= $historyPages; $p++): ?>
                        <?php
                        $qs = http_build_query(array_filter([
                            'history_page' => $p > 1 ? (string)$p : null,
                            'contract_signed' => (string)($_GET['contract_signed'] ?? '') === '1' ? '1' : null,
                            'request_created' => (string)($_GET['request_created'] ?? '') === '1' ? '1' : null,
                        ]));
                        ?>
                        <a href="customer-profile.php<?= $qs !== '' ? '?' . mirror_h($qs) : '' ?>"><?= mirror_h((string)$p) ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_financial_title">
        <h3 id="m360_financial_title">صورتحساب‌ها و پرداخت‌ها</h3>
        <p class="m360-dash-empty"><?= mirror_h((string)($financial['message'] ?? 'صورتحساب باز یا پرداخت معوقی برای شما ثبت نشده است.')) ?></p>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_notifications_title">
        <h3 id="m360_notifications_title">پیام‌ها و اعلان‌ها</h3>
        <p class="m360-dash-empty"><?= mirror_h((string)($notifications['message'] ?? 'پیام یا اعلان جدیدی ثبت نشده است.')) ?></p>
    </section>

    <section class="m360-dash-section" aria-labelledby="m360_account_title">
        <h3 id="m360_account_title">حساب و اطلاعات من</h3>
        <div class="m360-dash-account">
            <div class="m360-dash-avatar" aria-hidden="true"><?= mirror_h(m360_rw_customer_profile_initial_letter($fullName !== '' ? $fullName : $mobile)) ?></div>
            <div>
                <h4 class="m360-dash-card__title"><?= mirror_h($fullName) ?></h4>
                <p class="m360-dash-meta mobile-field"><?= mirror_h(function_exists('m360_format_masked_mobile') ? m360_format_masked_mobile($mobile) : '***') ?></p>
                <?php if ($profileNeedsCompletion): ?>
                    <p class="m360-dash-meta">برای استفاده کامل از خدمات، اطلاعات حساب خود را تکمیل کنید.</p>
                <?php else: ?>
                    <p class="m360-dash-meta">پروفایل شما تکمیل است.</p>
                <?php endif; ?>
                <?php
                $cityDisplay = (string)($identity['city'] ?? $customer['city'] ?? '');
                $addressDisplay = (string)($identity['address'] ?? $customer['address'] ?? '');
                ?>
                <?php if ($addressDisplay !== ''): ?>
                    <p class="m360-dash-meta"><?= mirror_h($addressDisplay) ?></p>
                <?php endif; ?>
                <?php if ($cityDisplay !== ''): ?>
                    <p class="m360-dash-meta"><?= mirror_h($cityDisplay) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="m360-dash-actions">
            <a class="m360-dash-btn m360-dash-btn--secondary" href="customer-request.php?mode=new">تکمیل یا ویرایش اطلاعات حساب</a>
            <a class="m360-dash-btn m360-dash-btn--ghost" href="customer-profile.php?logout=1">خروج از حساب</a>
        </div>
    </section>
</div>

<script>
(function () {
    document.querySelectorAll('.m360-dash-banner__close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var banner = btn.closest('.m360-dash-banner');
            if (banner) banner.remove();
        });
    });
})();
</script>
<?php
    mirror_render_foot();
} catch (Throwable $e) {
    m360_rw_customer_profile_render_error_page('خطا در نمایش داشبورد مشتری.');
}
