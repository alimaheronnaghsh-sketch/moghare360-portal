<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-matrix-guard.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-access-enforcement.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$technicalRequestId = (int)($_GET['technical_request_id'] ?? 0);
m360_ws_require_any(
    ['workshop.part_request.view_status', 'workshop.part_request.technical_approve', 'workshop.part_request.reject_return', 'workshop.part_request.create'],
    null
);
if ($technicalRequestId > 0 && is_resource($conn)) {
    m360_ws_assert_request_object_scope($conn, $technicalRequestId);
}
$request = is_resource($conn) ? m360_fulljob_fetch_request($conn, $technicalRequestId) : null;
$events = is_resource($conn) ? m360_fulljob_request_events($conn, $technicalRequestId) : [];

$decisionLabels = [
    'APPROVE_ESTIMATE_REVISION' => 'تأیید برای اصلاح برآورد',
    'REJECT' => 'رد درخواست',
    'NEEDS_MORE_EVIDENCE' => 'نیازمند شواهد بیشتر',
    'ROUTE_OTHER_TEAM' => 'ارجاع به تیم دیگر',
    'SEND_INVENTORY' => 'ارسال به انبار',
    'SEND_PURCHASE' => 'ارسال به خرید',
    'SEND_CRM' => 'ارسال به CRM',
    'SEND_CUSTOMER' => 'ارسال به مشتری',
    'ISSUE_HOLD' => 'صدور توقف کار',
    'ALLOW_EXECUTION' => 'اجازه ادامه اجرا',
    'CLOSE' => 'بستن درخواست',
];

mirror_render_head('جزئیات درخواست فنی', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">جزئیات درخواست فنی</h1>
  <?php if ($request === null): ?>
    <p class="m360-alert m360-alert-error">درخواست یافت نشد.</p>
  <?php else: ?>
    <div class="m360-grid">
      <div><strong>ID:</strong> <?= (int)$request['technical_request_id'] ?></div>
      <div><strong>کارت کار:</strong> <?= (int)$request['jobcard_id'] ?></div>
      <div><strong>نوع:</strong> <?= m360_fulljob_h(m360_fulljob_request_type_label_fa((string)$request['request_type'])) ?></div>
      <div><strong>وضعیت:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$request['status'])) ?></div>
      <div><strong>اولویت:</strong> <?= m360_fulljob_h(m360_fulljob_priority_label_fa((string)$request['priority'])) ?></div>
      <div><strong>ریسک:</strong> <?= m360_fulljob_h(m360_fulljob_risk_label_fa((string)$request['risk_level'])) ?></div>
    </div>
    <h2 class="m360-section-title"><?= m360_fulljob_h((string)$request['title']) ?></h2>
    <p><?= nl2br(m360_fulljob_h((string)$request['description'])) ?></p>
    <p class="m360-alert m360-alert-warning">تکنسین نمی‌تواند درخواست خود را از نظر مالی یا مشتری تأیید کند. تصمیم مدیر سالن باید در ERP ثبت شود.</p>
    <?php if (m360_fulljob_role_can_hall((string)$actor['role_code'])): ?>
      <form method="post" action="api/staff/technical-request-review.php" class="m360-form">
        <input type="hidden" name="technical_request_id" value="<?= (int)$request['technical_request_id'] ?>">
        <label>تصمیم سالن</label>
        <select name="decision">
          <?php foreach ($decisionLabels as $code => $label): ?><option value="<?= m360_fulljob_h($code) ?>"><?= m360_fulljob_h($label) ?></option><?php endforeach; ?>
        </select>
        <textarea name="review_note" placeholder="یادداشت تصمیم"></textarea>
        <button class="m360-btn m360-btn-primary" type="submit">ثبت تصمیم</button>
      </form>
    <?php endif; ?>
    <h2 class="m360-section-title">رویدادها</h2>
    <table class="m360-table"><thead><tr><th>ID</th><th>رویداد</th><th>قدیم</th><th>جدید</th><th>نقش</th><th>زمان</th></tr></thead><tbody>
    <?php foreach ($events as $event): ?><tr><td><?= (int)$event['event_id'] ?></td><td><?= m360_fulljob_h((string)$event['event_name']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$event['old_status'])) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$event['new_status'])) ?></td><td><?= m360_fulljob_h((string)$event['actor_role']) ?></td><td><?= m360_fulljob_h((string)$event['created_at']) ?></td></tr><?php endforeach; ?>
    <?php if ($events === []): ?><tr><td colspan="6">رویدادی ثبت نشده است.</td></tr><?php endif; ?>
    </tbody></table>
  <?php endif; ?>
</section>
<?php mirror_render_foot(); ?>
