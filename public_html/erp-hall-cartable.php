<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$rows = is_resource($conn) ? m360_fulljob_hall_cartable($conn) : [];

mirror_render_head('کارتابل مدیر سالن', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">کارتابل مدیر سالن</h1>
  <p class="m360-muted">پرونده‌های پذیرش و قرارداد که باید توسط مدیر سالن بررسی، تیم‌بندی و به تکنسین ارجاع شوند.</p>
  <p class="m360-alert m360-alert-info">اقدام بعدی مجاز: بررسی سالن و تخصیص تیم. اقدام ممنوع: شروع کار بدون تأیید گیت مشتری یا درخواست‌های باز.</p>
  <table class="m360-table">
    <thead><tr><th>کارت کار</th><th>درخواست</th><th>مشتری</th><th>خودرو</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= m360_fulljob_h((string)$row['jobcard_number']) ?></td>
        <td><?= m360_fulljob_h((string)$row['online_request_id']) ?></td>
        <td><?= m360_fulljob_h((string)$row['customer_name']) ?></td>
        <td><?= m360_fulljob_h(trim((string)$row['brand'] . ' ' . (string)$row['model'] . ' ' . (string)$row['plate_number'])) ?></td>
        <td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td>
        <td><a class="m360-btn m360-btn-primary" href="erp-hall-jobcard-detail.php?jobcard_id=<?= (int)$row['jobcard_id'] ?>">باز کردن</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?><tr><td colspan="6">مورد فعالی در کارتابل مدیر سالن وجود ندارد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php mirror_render_foot(); ?>
