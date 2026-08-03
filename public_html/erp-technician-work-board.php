<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-matrix-guard.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-access-enforcement.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-workshop-work-item-helper.php';

// No broad OR grant: user must hold at least one family view; rows filtered server-side.
$viewableFamilies = m360_ws_wi_viewable_families_for_actor();
if ($viewableFamilies === []) {
    m360_am_forbidden('مجوز مشاهده خانواده خدمات برای این تابلو را ندارید.');
}
$wsCtx = m360_ws_require_actor_context();

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$rows = is_resource($conn)
    ? m360_fulljob_assigned_technician_jobs($conn, $actor, (int)$wsCtx['company_id'], (bool)$wsCtx['is_owner'])
    : [];
// Attach allowed work items only
$workItemRows = [];
if (is_resource($conn)) {
    foreach ($rows as $row) {
        $wis = m360_ws_wi_filter_rows(m360_ws_wi_list_for_jobcard($conn, (int)$row['jobcard_id']), $viewableFamilies);
        foreach ($wis as $wi) {
            if ((int)($wi['assigned_technician_user_id'] ?? 0) === (int)$actor['user_id'] || (int)($wi['assigned_technician_user_id'] ?? 0) === 0) {
                $workItemRows[] = $wi + ['jobcard_number' => $row['jobcard_number'] ?? ''];
            }
        }
    }
}

mirror_render_head('تابلوی کار تکنسین', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">تابلوی کار تکنسین</h1>
  <p class="m360-muted">تکنسین فقط آیتم‌های مجاز خانواده خدمات خود را می‌بیند؛ تأیید مالی در اختیار تکنسین نیست.</p>
  <h2 class="m360-section-title">آیتم‌های کاری مجاز</h2>
  <table class="m360-table">
    <thead><tr><th>آیتم</th><th>پرونده</th><th>خانواده</th><th>تخصص</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($workItemRows as $wi): ?>
      <tr>
        <td><?= (int)$wi['work_item_id'] ?></td>
        <td><?= m360_fulljob_h((string)($wi['jobcard_number'] ?? $wi['jobcard_id'])) ?></td>
        <td><?= m360_fulljob_h(m360_ws_wi_family_label_fa((string)$wi['service_family'])) ?></td>
        <td><?= m360_fulljob_h((string)($wi['specialty_code'] ?? '—')) ?></td>
        <td><?= m360_fulljob_h((string)$wi['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($workItemRows === []): ?><tr><td colspan="5">آیتم کاری مجازی یافت نشد.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <h2 class="m360-section-title">پرونده‌های تخصیص‌یافته (میراث)</h2>
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
