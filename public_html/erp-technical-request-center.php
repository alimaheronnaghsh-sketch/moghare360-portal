<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
if (is_resource($conn) && !m360_fulljob_technician_can_open($conn, $jobcardId, $actor)) {
    http_response_code(403);
    echo 'این کارت کار به شما تخصیص ندارد.';
    exit;
}
$message = trim((string)($_GET['msg'] ?? ''));
$requests = is_resource($conn) ? m360_fulljob_list_requests($conn, $jobcardId) : [];
$jobcard = is_resource($conn) ? m360_fulljob_fetch_jobcard($conn, $jobcardId) : null;

mirror_render_head('مرکز درخواست فنی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">مرکز درخواست فنی</h1>
  <?php if ($message !== ''): ?><p class="m360-alert m360-alert-success"><?= m360_fulljob_h($message) ?></p><?php endif; ?>
  <p>کارت کار: <?= m360_fulljob_h((string)($jobcard['jobcard_number'] ?? $jobcardId)) ?></p>
  <p class="m360-muted">درخواست باید در ERP ثبت شود. پیام شفاهی، تماس تلفنی و پیام‌رسان عملیاتی معتبر نیستند.</p>
  <form method="post" action="api/staff/technical-request-create.php" class="m360-form">
    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
    <label>نوع درخواست</label>
    <select name="request_type">
      <?php foreach (M360_FULLJOB_REQUEST_TYPES as $type): ?><option value="<?= m360_fulljob_h($type) ?>"><?= m360_fulljob_h(m360_fulljob_request_type_label_fa($type)) ?></option><?php endforeach; ?>
    </select>
    <label>عنوان</label><input name="title" required>
    <label>شرح</label><textarea name="description" required></textarea>
    <label>اولویت</label><select name="priority"><?php foreach (M360_FULLJOB_PRIORITIES as $priority): ?><option value="<?= m360_fulljob_h($priority) ?>"><?= m360_fulljob_h(m360_fulljob_priority_label_fa($priority)) ?></option><?php endforeach; ?></select>
    <label>ریسک</label><select name="risk_level"><?php foreach (M360_FULLJOB_RISKS as $risk): ?><option value="<?= m360_fulljob_h($risk) ?>"><?= m360_fulljob_h(m360_fulljob_risk_label_fa($risk)) ?></option><?php endforeach; ?></select>
    <label>اثر هزینه</label><input name="estimated_cost_impact" inputmode="decimal" value="0">
    <label>اثر زمانی به دقیقه</label><input name="estimated_time_impact_minutes" inputmode="numeric" value="0">
    <label><input type="checkbox" name="requires_customer_approval" value="1"> نیازمند تأیید مشتری</label>
    <label><input type="checkbox" name="requires_part" value="1"> نیازمند قطعه</label>
    <label><input type="checkbox" name="requires_external_service" value="1"> نیازمند خدمت خارج از مجموعه</label>
    <label>شواهد</label><input name="evidence_files" placeholder="مسیر یا شناسه شواهد ثبت‌شده">
    <button class="m360-btn m360-btn-primary" type="submit">ثبت درخواست</button>
  </form>
  <h2 class="m360-section-title">درخواست‌های ثبت‌شده</h2>
  <table class="m360-table"><thead><tr><th>ID</th><th>نوع</th><th>عنوان</th><th>وضعیت</th><th>بازبینی</th></tr></thead><tbody>
  <?php foreach ($requests as $request): ?><tr><td><?= (int)$request['technical_request_id'] ?></td><td><?= m360_fulljob_h(m360_fulljob_request_type_label_fa((string)$request['request_type'])) ?></td><td><?= m360_fulljob_h((string)$request['title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$request['status'])) ?></td><td><a href="erp-technical-request-detail.php?technical_request_id=<?= (int)$request['technical_request_id'] ?>">جزئیات</a></td></tr><?php endforeach; ?>
  <?php if ($requests === []): ?><tr><td colspan="5">درخواستی برای این کارت کار ثبت نشده است.</td></tr><?php endif; ?>
  </tbody></table>
</section>
<?php mirror_render_foot(); ?>
