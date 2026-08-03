<?php
declare(strict_types=1);

/**
 * Hall work-item assignment UI — periodic / inspection specialties.
 */

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-work-item-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$ctx = m360_ws_require_actor_context();
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
m360_ws_require('workshop.jobcard.view', $jobcardId > 0 ? $jobcardId : null);

$message = '';
$ok = false;

if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'ensure_work_item') {
        $family = strtoupper(trim((string)($_POST['service_family'] ?? '')));
        $specialty = strtoupper(trim((string)($_POST['specialty_code'] ?? '')));
        $perm = m360_ws_wi_assign_permission($family, $specialty);
        if ($perm === null) {
            m360_am_forbidden('خانواده/تخصص نامعتبر است.');
        }
        m360_ws_require($perm, $jobcardId);
        $res = m360_ws_wi_ensure($conn, (int)$ctx['company_id'], $jobcardId, $family, $family, $specialty, (int)$actor['user_id']);
        $message = $res['message'];
        $ok = !empty($res['ok']);
    } elseif ($action === 'assign_work_item') {
        $workItemId = (int)($_POST['work_item_id'] ?? 0);
        $wi = m360_ws_wi_fetch($conn, $workItemId);
        if ($wi === null || (int)$wi['jobcard_id'] !== $jobcardId) {
            m360_am_forbidden('آیتم کاری در محدوده پرونده نیست.');
        }
        $perm = m360_ws_wi_assign_permission((string)$wi['service_family'], (string)($wi['specialty_code'] ?? ''));
        if ($perm === null) {
            m360_am_forbidden();
        }
        m360_ws_require($perm, $jobcardId);
        $res = m360_ws_wi_assign_technician(
            $conn,
            $workItemId,
            (int)($_POST['technician_user_id'] ?? 0),
            (int)$actor['user_id'],
            (int)$ctx['company_id'],
            (string)($_POST['priority'] ?? 'NORMAL'),
            (string)($_POST['assignment_description'] ?? ''),
            (string)($_POST['reassign_reason'] ?? '')
        );
        $message = $res['message'];
        $ok = !empty($res['ok']);
    }
}

$items = is_resource($conn) ? m360_ws_wi_list_for_jobcard($conn, $jobcardId) : [];
$jobcard = is_resource($conn) ? m360_fulljob_fetch_jobcard($conn, $jobcardId) : null;

mirror_render_head('تخصیص آیتم کاری', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">تخصیص آیتم کاری (سرویس دوره‌ای / کارشناسی)</h1>
  <p class="m360-muted">تخصیص روی آیتم کاری مستقل است — نه کل پرونده تعمیر به یک نفر.</p>
  <p>پرونده: <?= m360_ws_wi_h((string)($jobcard['jobcard_number'] ?? $jobcardId)) ?></p>
  <?php if ($message !== ''): ?>
    <p class="m360-alert <?= $ok ? 'm360-alert-success' : 'm360-alert-error' ?>"><?= m360_ws_wi_h($message) ?></p>
  <?php endif; ?>

  <h2 class="m360-section-title">ایجاد آیتم کاری</h2>
  <form method="post" class="m360-form">
    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
    <input type="hidden" name="action" value="ensure_work_item">
    <label>خانواده خدمات
      <select name="service_family" required>
        <option value="PERIODIC_SERVICE">سرویس دوره‌ای</option>
        <option value="INSPECTION">کارشناسی</option>
      </select>
    </label>
    <label>تخصص (برای کارشناسی)
      <select name="specialty_code">
        <option value="">—</option>
        <option value="MECHANICAL">مکانیک</option>
        <option value="ELECTRICAL">برق</option>
      </select>
    </label>
    <button class="m360-btn m360-btn-primary" type="submit">ایجاد / بازیابی آیتم</button>
  </form>

  <h2 class="m360-section-title">آیتم‌های جاری</h2>
  <table class="m360-table">
    <thead><tr><th>شناسه</th><th>خانواده</th><th>تخصص</th><th>واحد</th><th>وضعیت</th><th>تکنسین</th><th>تخصیص</th></tr></thead>
    <tbody>
    <?php foreach ($items as $wi): ?>
      <tr>
        <td><?= (int)$wi['work_item_id'] ?></td>
        <td><?= m360_ws_wi_h(m360_ws_wi_family_label_fa((string)$wi['service_family'])) ?></td>
        <td><?= m360_ws_wi_h((string)($wi['specialty_code'] ?? '—')) ?></td>
        <td><?= m360_ws_wi_h((string)($wi['unit_code'] ?? '')) ?></td>
        <td><?= m360_ws_wi_h((string)$wi['status']) ?></td>
        <td><?= (int)($wi['assigned_technician_user_id'] ?? 0) ?></td>
        <td>
          <form method="post" class="m360-form" style="display:flex;gap:.35rem;flex-wrap:wrap;">
            <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
            <input type="hidden" name="action" value="assign_work_item">
            <input type="hidden" name="work_item_id" value="<?= (int)$wi['work_item_id'] ?>">
            <input name="technician_user_id" placeholder="شناسه تکنسین" inputmode="numeric" required>
            <input name="reassign_reason" placeholder="دلیل بازتخصیص (در صورت نیاز)">
            <button class="m360-btn" type="submit">تخصیص</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($items === []): ?><tr><td colspan="7">آیتم کاری ثبت نشده است.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <p><a class="m360-btn" href="erp-hall-jobcard-detail.php?jobcard_id=<?= $jobcardId ?>&tab=assignments">بازگشت به سالن</a></p>
</section>
<?php mirror_render_foot(); ?>
