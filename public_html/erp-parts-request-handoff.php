<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'PARTS']);
$rows = is_resource($conn) ? m360_fulljob_list_requests_by_status($conn, ['SENT_TO_INVENTORY', 'SENT_TO_PURCHASE']) : [];

mirror_render_head('تحویل درخواست قطعه', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">مسیر قطعه / مواد / خرید</h1>
  <p class="m360-muted">درخواست قطعه باید نشان دهد چه کسی درخواست داده، مدیر سالن چه تصمیمی گرفته، اقدام بعدی انبار چیست و آیا تأیید مشتری لازم است یا نه.</p>
  <table class="m360-table"><thead><tr><th>ID</th><th>کارت کار</th><th>عنوان</th><th>وضعیت</th><th>هزینه</th><th>اقدام بعدی</th></tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr><td><?= (int)$row['technical_request_id'] ?></td><td><?= m360_fulljob_h((string)$row['jobcard_number']) ?></td><td><?= m360_fulljob_h((string)$row['title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td><td><?= m360_fulljob_h((string)$row['estimated_cost_impact']) ?></td><td>انبار/خرید باید رزرو، صدور یا خرید را در ERP ثبت کند.</td></tr><?php endforeach; ?>
  <?php if ($rows === []): ?><tr><td colspan="6">درخواست قطعه ارسال‌شده به انبار یا خرید وجود ندارد.</td></tr><?php endif; ?>
  </tbody></table>
</section>
<?php mirror_render_foot(); ?>
