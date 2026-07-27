<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$rows = is_resource($conn) ? m360_fulljob_list_requests_by_status($conn, ['UNDER_HALL_REVIEW', 'NEEDS_MORE_EVIDENCE']) : [];

mirror_render_head('صف بررسی درخواست‌های تکنسین', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">صف بررسی سالن</h1>
  <p class="m360-muted">مدیر سالن درخواست‌های تکنسین را بررسی می‌کند و در صورت تغییر هزینه، زمان، محدوده یا ریسک، مسیر برآورد/تأیید مشتری فعال می‌شود.</p>
  <table class="m360-table"><thead><tr><th>ID</th><th>کارت کار</th><th>نوع</th><th>عنوان</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr><td><?= (int)$row['technical_request_id'] ?></td><td><?= m360_fulljob_h((string)$row['jobcard_number']) ?></td><td><?= m360_fulljob_h(m360_fulljob_request_type_label_fa((string)$row['request_type'])) ?></td><td><?= m360_fulljob_h((string)$row['title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td><td><a href="erp-technical-request-detail.php?technical_request_id=<?= (int)$row['technical_request_id'] ?>">بازبینی</a></td></tr><?php endforeach; ?>
  <?php if ($rows === []): ?><tr><td colspan="6">درخواست در انتظار بررسی سالن وجود ندارد.</td></tr><?php endif; ?>
  </tbody></table>
</section>
<?php mirror_render_foot(); ?>
