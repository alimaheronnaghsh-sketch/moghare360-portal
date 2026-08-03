<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-work-report-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$ctx = m360_ws_require_actor_context();
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
$reportId = (int)($_GET['work_report_id'] ?? $_POST['work_report_id'] ?? 0);
m360_ws_assert_jobcard_object_scope($conn, $jobcardId);

$message = '';
$okFlag = false;

if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    if ($action === 'save_draft') {
        m360_ws_require('workshop.work_report.create', $jobcardId);
        m360_ws_reject_injected_prices($_POST);
        $res = m360_ws_wr_create_or_update_draft($conn, (int)$ctx['company_id'], $jobcardId, (int)($_POST['work_item_id'] ?? 0) ?: null, $_POST, (int)$actor['user_id']);
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
        $reportId = (int)($res['work_report_id'] ?? $reportId);
    } elseif ($action === 'submit') {
        m360_ws_require('workshop.work_report.create', $jobcardId);
        $res = m360_ws_wr_submit($conn, $reportId, (int)$actor['user_id']);
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
    } elseif ($action === 'approve') {
        m360_ws_require('workshop.work_report.approve', $jobcardId);
        $res = m360_ws_wr_approve($conn, $reportId, (int)$actor['user_id']);
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
    } elseif ($action === 'return') {
        m360_ws_require('workshop.work_report.return', $jobcardId);
        $res = m360_ws_wr_return($conn, $reportId, (int)$actor['user_id'], (string)($_POST['return_reason'] ?? ''));
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
        if (!empty($res['new_report_id'])) {
            $reportId = (int)$res['new_report_id'];
        }
    }
}

$reports = is_resource($conn) ? m360_ws_wr_list_for_jobcard($conn, $jobcardId) : [];
$current = $reportId > 0 ? m360_ws_wr_fetch($conn, $reportId) : ($reports[0] ?? null);
if ($current !== null) {
    $reportId = (int)$current['work_report_id'];
}
$canCreate = m360_ws_can('workshop.work_report.create');
$canApprove = m360_ws_can('workshop.work_report.approve');
$canReturn = m360_ws_can('workshop.work_report.return');

mirror_render_head('گزارش انجام کار', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">گزارش امور انجام‌شده</h1>
  <p class="m360-muted">جدا از گزارش تشخیص — تأیید این گزارش قیمت مشتری را تأیید نمی‌کند.</p>
  <?php if ($message !== ''): ?><p class="m360-alert <?= $okFlag ? 'm360-alert-success' : 'm360-alert-error' ?>"><?= m360_am_h($message) ?></p><?php endif; ?>
  <?php if ($canCreate): ?>
  <form method="post" class="m360-form">
    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
    <input type="hidden" name="work_report_id" value="<?= $reportId ?>">
    <input type="hidden" name="action" value="save_draft">
    <label>شرح کار انجام‌شده<textarea name="work_description" required><?= m360_am_h((string)($current['work_description'] ?? '')) ?></textarea></label>
    <label>شناسه مجری<input name="performer_user_id" inputmode="numeric" value="<?= (int)($current['performer_user_id'] ?? $actor['user_id']) ?>"></label>
    <label>مدت (دقیقه)<input name="duration_minutes" inputmode="numeric" value="<?= (int)($current['duration_minutes'] ?? 0) ?>"></label>
    <label>نتیجه<textarea name="result_summary"><?= m360_am_h((string)($current['result_summary'] ?? '')) ?></textarea></label>
    <label>عیب باقیمانده<textarea name="remaining_fault"><?= m360_am_h((string)($current['remaining_fault'] ?? '')) ?></textarea></label>
    <label>پیشنهاد<textarea name="recommendation"><?= m360_am_h((string)($current['recommendation'] ?? '')) ?></textarea></label>
    <button class="m360-btn m360-btn-primary" type="submit">ذخیره پیش‌نویس</button>
  </form>
  <?php if ($reportId > 0 && in_array(strtoupper((string)($current['status'] ?? '')), ['DRAFT', 'RETURNED'], true)): ?>
  <form method="post" class="m360-form">
    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
    <input type="hidden" name="work_report_id" value="<?= $reportId ?>">
    <input type="hidden" name="action" value="submit">
    <button class="m360-btn" type="submit">ارسال برای تأیید</button>
  </form>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($current !== null): ?>
    <p>وضعیت: <strong><?= m360_am_h((string)$current['status']) ?></strong> — نسخه <?= (int)$current['revision_no'] ?></p>
    <?php if ($canApprove && strtoupper((string)$current['status']) === 'SUBMITTED'): ?>
    <form method="post" class="m360-form">
      <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
      <input type="hidden" name="work_report_id" value="<?= $reportId ?>">
      <input type="hidden" name="action" value="approve">
      <button class="m360-btn m360-btn-primary" type="submit">تأیید گزارش انجام کار</button>
    </form>
    <?php endif; ?>
    <?php if ($canReturn && strtoupper((string)$current['status']) === 'SUBMITTED'): ?>
    <form method="post" class="m360-form">
      <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
      <input type="hidden" name="work_report_id" value="<?= $reportId ?>">
      <input type="hidden" name="action" value="return">
      <label>دلیل برگشت<textarea name="return_reason" required></textarea></label>
      <button class="m360-btn" type="submit">برگشت برای اصلاح</button>
    </form>
    <?php endif; ?>
  <?php endif; ?>

  <h2 class="m360-section-title">سوابق</h2>
  <ul>
    <?php foreach ($reports as $r): ?>
      <li><a href="?jobcard_id=<?= $jobcardId ?>&work_report_id=<?= (int)$r['work_report_id'] ?>">نسخه <?= (int)$r['revision_no'] ?> — <?= m360_am_h((string)$r['status']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php mirror_render_foot(); ?>
