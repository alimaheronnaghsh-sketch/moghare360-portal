<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$rows = is_resource($conn) ? m360_fulljob_list_requests_by_status($conn, ['EXECUTION_BLOCKED']) : [];

mirror_render_head('تابلوی توقف کار', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">توقف کار / توقف ایمنی</h1>
  <p class="m360-muted">اگر توقف کار فعال باشد، اجرای کار تا تصمیم مدیر سالن و رفع ریسک مسدود می‌ماند. اگر تغییر هزینه، زمان، محدوده یا ریسک رخ دهد، تأیید مشتری لازم است.</p>
  <table class="m360-table"><thead><tr><th>ID</th><th>کارت کار</th><th>عنوان</th><th>ریسک</th><th>وضعیت</th><th>چگونگی رفع</th></tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr><td><?= (int)$row['technical_request_id'] ?></td><td><?= m360_fulljob_h((string)$row['jobcard_number']) ?></td><td><?= m360_fulljob_h((string)$row['title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_risk_label_fa((string)$row['risk_level'])) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td><td>رفع توقف فقط با تصمیم ثبت‌شده مدیر سالن و تکمیل گیت‌های مشتری/ایمنی مجاز است.</td></tr><?php endforeach; ?>
  <?php if ($rows === []): ?><tr><td colspan="6">توقف کار فعال ثبت نشده است.</td></tr><?php endif; ?>
  </tbody></table>
</section>
<?php mirror_render_foot(); ?>
