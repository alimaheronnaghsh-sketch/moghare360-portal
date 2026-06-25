<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-api-client.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$result = mirror_api_company_dashboard(['token' => $token, 'mirror' => true]);

$dashboard = is_array($result['data'] ?? null) ? (array)$result['data'] : [];
$stats = [
    'today_status' => (string)($dashboard['today_status'] ?? '—'),
    'intake_count' => (string)($dashboard['intake_count'] ?? '—'),
    'active_jobcards' => (string)($dashboard['active_jobcards'] ?? '—'),
    'finance_preview' => (string)($dashboard['finance_preview'] ?? '—'),
    'crm_status' => (string)($dashboard['crm_status'] ?? '—'),
    'alerts' => (string)($dashboard['alerts'] ?? '—'),
    'main_dashboard_url' => (string)($dashboard['main_dashboard_url'] ?? ''),
];

mirror_render_head('داشبورد مالک کمپانی — MOGHARE360', 'company');
?>
<section class="m360-hero">
    <h2>داشبورد مدیریتی مالک کمپانی</h2>
    <p>داده‌ها فقط از Master Server خوانده می‌شوند — بدون report cache روی هاست.</p>
</section>

<?php if (!($result['ok'] ?? false)): ?>
    <div class="m360-alert m360-alert-warn">
        <?= mirror_h((string)($result['message'] ?? 'اتصال به Master Server برقرار نیست.')) ?>
        <p style="margin:0.5rem 0 0;font-size:0.85rem">Master API endpoint implementation required on local server</p>
    </div>
<?php endif; ?>

<div class="m360-grid">
    <div class="m360-card"><h3>وضعیت امروز</h3><div class="m360-stat"><?= mirror_h($stats['today_status']) ?></div></div>
    <div class="m360-card"><h3>تعداد پذیرش‌ها</h3><div class="m360-stat"><?= mirror_h($stats['intake_count']) ?></div></div>
    <div class="m360-card"><h3>JobCard فعال</h3><div class="m360-stat"><?= mirror_h($stats['active_jobcards']) ?></div></div>
    <div class="m360-card"><h3>وضعیت مالی (پیش‌نمایش)</h3><p><?= mirror_h($stats['finance_preview']) ?></p></div>
    <div class="m360-card"><h3>وضعیت CRM</h3><p><?= mirror_h($stats['crm_status']) ?></p></div>
    <div class="m360-card"><h3>هشدارها</h3><p><?= mirror_h($stats['alerts']) ?></p></div>
</div>

<section class="m360-card" style="margin-top:1rem">
    <h3>ورود به داشبورد اصلی</h3>
    <?php if ($stats['main_dashboard_url'] !== ''): ?>
        <a class="m360-btn" href="<?= mirror_h($stats['main_dashboard_url']) ?>" rel="noopener">داشبورد Master Server</a>
    <?php else: ?>
        <p>لینک داشبورد پس از پیکربندی Master Server و احراز هویت نمایش داده می‌شود.</p>
        <a class="m360-btn m360-btn-secondary" href="owner-login.php">ورود مالک</a>
    <?php endif; ?>
</section>
<?php mirror_render_foot(); ?>
