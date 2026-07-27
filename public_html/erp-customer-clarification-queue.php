<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'RECEPTION', 'CRM']);
$rows = is_resource($conn) ? m360_fulljob_list_requests_by_status($conn, ['SENT_TO_CRM', 'SENT_TO_CUSTOMER']) : [];

mirror_render_head('صف شفاف‌سازی مشتری', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">صف شفاف‌سازی / پاسخ مشتری</h1>
  <p class="m360-muted">پاسخ شفاهی معتبر نیست. پاسخ مشتری فقط وقتی معتبر است که در ERP ثبت شود.</p>
  <p class="m360-alert m360-alert-warning">اگر پاسخ واقعی ثبت نشده باشد، وضعیت باید «در انتظار پاسخ مشتری» باقی بماند.</p>
  <table class="m360-table"><thead><tr><th>ID</th><th>کارت کار</th><th>عنوان</th><th>وضعیت</th><th>شرح</th></tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr><td><?= (int)$row['technical_request_id'] ?></td><td><?= m360_fulljob_h((string)$row['jobcard_number']) ?></td><td><?= m360_fulljob_h((string)$row['title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td><td><?= m360_fulljob_h((string)$row['description']) ?></td></tr><?php endforeach; ?>
  <?php if ($rows === []): ?><tr><td colspan="5">در انتظار پاسخ مشتری</td></tr><?php endif; ?>
  </tbody></table>
</section>
<?php mirror_render_foot(); ?>
