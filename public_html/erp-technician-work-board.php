<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$rows = is_resource($conn) ? m360_fulljob_assigned_technician_jobs($conn, $actor) : [];

mirror_render_head('تابلوی کار تکنسین', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">تابلوی کار تکنسین</h1>
  <p class="m360-muted">تکنسین فقط می‌تواند کار ارجاع‌شده را ببیند و درخواست فنی ثبت کند؛ تأیید مالی، امضا یا تصمیم مشتری در اختیار تکنسین نیست.</p>
  <table class="m360-table">
    <thead><tr><th>کارت کار</th><th>مشتری</th><th>پلاک</th><th>تکنسین</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= m360_fulljob_h((string)$row['jobcard_number']) ?></td>
        <td><?= m360_fulljob_h((string)$row['customer_name']) ?></td>
        <td><?= m360_fulljob_h((string)$row['plate_number']) ?></td>
        <td><?= m360_fulljob_h((string)$row['assigned_to_user_id']) ?></td>
        <td><?= m360_fulljob_h((string)$row['technical_status'] . ' / ' . (string)$row['work_execution_status']) ?></td>
        <td><a class="m360-btn m360-btn-primary" href="erp-technical-request-center.php?jobcard_id=<?= (int)$row['jobcard_id'] ?>">ثبت درخواست فنی</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?><tr><td colspan="6">کارت کاری به این کاربر تخصیص داده نشده است.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php mirror_render_foot(); ?>
