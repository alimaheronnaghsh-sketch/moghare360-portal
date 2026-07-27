<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$rows = is_resource($conn) ? customer_core_fetch_rows($conn, 'SELECT * FROM dbo.erp_external_service_requests ORDER BY external_service_request_id DESC') : [];

mirror_render_head('خدمت خارج از مجموعه', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">زنجیره خدمت خارج از مجموعه</h1>
  <p class="m360-muted">ارسال خودرو/قطعه به بیرون فیک نمی‌شود. وضعیت ارسال، بازگشت، امانت و نیاز به تأیید مشتری باید در ERP روشن باشد.</p>
  <table class="m360-table"><thead><tr><th>ID</th><th>کارت کار</th><th>فروشنده</th><th>خدمت</th><th>وضعیت</th><th>زنجیره امانت</th></tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr><td><?= (int)$row['external_service_request_id'] ?></td><td><?= (int)$row['jobcard_id'] ?></td><td><?= m360_fulljob_h((string)$row['vendor_name']) ?></td><td><?= m360_fulljob_h((string)$row['service_title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td><td><?= m360_fulljob_h((string)$row['chain_of_custody_json']) ?></td></tr><?php endforeach; ?>
  <?php if ($rows === []): ?><tr><td colspan="6">درخواست خدمت خارج از مجموعه ثبت نشده است.</td></tr><?php endif; ?>
  </tbody></table>
</section>
<?php mirror_render_foot(); ?>
