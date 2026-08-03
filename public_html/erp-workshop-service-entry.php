<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-service-line-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER', 'TECHNICIAN']);
$ctx = m360_ws_require_actor_context();
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
$workItemId = (int)($_GET['work_item_id'] ?? $_POST['work_item_id'] ?? 0);
$lineId = (int)($_GET['service_line_id'] ?? $_POST['service_line_id'] ?? 0);
m360_ws_assert_jobcard_object_scope($conn, $jobcardId);

$message = '';
$okFlag = false;
m360_ws_require('workshop.service_line.create_no_price', $jobcardId);

if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    if ($action === 'save_draft') {
        m360_ws_reject_injected_prices($_POST);
        $res = m360_ws_sl_create_or_update_draft(
            $conn,
            (int)$ctx['company_id'],
            $jobcardId,
            (int)($_POST['work_item_id'] ?? 0),
            $_POST,
            (int)$actor['user_id'],
            !empty($ctx['is_owner'])
        );
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
        $lineId = (int)($res['service_line_id'] ?? $lineId);
        $workItemId = (int)($_POST['work_item_id'] ?? $workItemId);
    } elseif ($action === 'submit') {
        $res = m360_ws_sl_submit($conn, $lineId, (int)$actor['user_id'], !empty($ctx['is_owner']));
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
    }
}

$workItems = is_resource($conn)
    ? customer_core_fetch_rows($conn, 'SELECT * FROM dbo.erp_workshop_work_items WHERE jobcard_id=? ORDER BY work_item_id', [$jobcardId])
    : [];
$current = $lineId > 0 ? m360_ws_sl_fetch($conn, $lineId) : null;
if ($current !== null) {
    $current = m360_ws_sl_public_row($current, false);
    $workItemId = (int)($current['work_item_id'] ?? $workItemId);
}
$lines = array_map(
    static fn(array $r): array => m360_ws_sl_public_row($r, false),
    is_resource($conn) ? m360_ws_sl_list_for_jobcard($conn, $jobcardId) : []
);

mirror_render_head('ثبت خدمات فروش', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">ثبت خط خدمت (بدون مبلغ)</h1>
  <p class="m360-muted">سرفصل فروش جدا از واحد عملیاتی است. مبلغ توسط مدیر سالن تعیین می‌شود.</p>
  <p>
    <a href="erp-workshop-service-summary.php?jobcard_id=<?= $jobcardId ?>">خلاصه خدمات</a>
    · <a href="erp-workshop-work-report.php?jobcard_id=<?= $jobcardId ?>">گزارش انجام کار</a>
  </p>
  <?php if ($message !== ''): ?><p class="m360-alert <?= $okFlag ? 'm360-alert-success' : 'm360-alert-error' ?>"><?= m360_am_h($message) ?></p><?php endif; ?>

  <form method="post" class="m360-form" autocomplete="off">
    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
    <input type="hidden" name="service_line_id" value="<?= $lineId ?>">
    <input type="hidden" name="action" value="save_draft">
    <label>آیتم کاری
      <select name="work_item_id" required>
        <option value="">— انتخاب —</option>
        <?php foreach ($workItems as $wi): ?>
          <option value="<?= (int)$wi['work_item_id'] ?>" <?= $workItemId === (int)$wi['work_item_id'] ? 'selected' : '' ?>>
            #<?= (int)$wi['work_item_id'] ?> — <?= m360_am_h((string)$wi['service_family']) ?> — <?= m360_am_h((string)$wi['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>سرفصل فروش
      <select name="sales_category" required>
        <?php foreach (M360_WS_SL_CATEGORY_LABELS_FA as $code => $fa): ?>
          <option value="<?= m360_am_h($code) ?>" <?= strtoupper((string)($current['sales_category'] ?? '')) === $code ? 'selected' : '' ?>><?= m360_am_h($fa) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>عنوان خدمت<input name="service_title" required maxlength="300" value="<?= m360_am_h((string)($current['service_title'] ?? '')) ?>"></label>
    <label>شرح کار<textarea name="service_description"><?= m360_am_h((string)($current['service_description'] ?? '')) ?></textarea></label>
    <label>مدت واقعی (دقیقه)<input name="actual_minutes" inputmode="numeric" required value="<?= (int)($current['actual_minutes'] ?? 0) ?>"></label>
    <label>محدوده توافق
      <select name="agreement_scope">
        <option value="WITHIN_AGREEMENT" <?= strtoupper((string)($current['agreement_scope'] ?? '')) === 'WITHIN_AGREEMENT' ? 'selected' : '' ?>>درون توافق اولیه</option>
        <option value="ADDITIONAL" <?= strtoupper((string)($current['agreement_scope'] ?? '')) === 'ADDITIONAL' ? 'selected' : '' ?>>اضافه / خارج از توافق</option>
      </select>
    </label>
    <button class="m360-btn m360-btn-primary" type="submit">ذخیره پیش‌نویس</button>
  </form>

  <?php if ($lineId > 0 && in_array(strtoupper((string)($current['status'] ?? '')), ['DRAFT', 'RETURNED'], true)): ?>
  <form method="post" class="m360-form">
    <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
    <input type="hidden" name="service_line_id" value="<?= $lineId ?>">
    <input type="hidden" name="action" value="submit">
    <button class="m360-btn" type="submit">ارسال برای بررسی فنی</button>
  </form>
  <?php endif; ?>

  <?php if ($current !== null): ?>
    <p>وضعیت: <strong><?= m360_am_h((string)$current['status']) ?></strong>
      — <?= m360_am_h((string)($current['display_title'] ?? '')) ?></p>
  <?php endif; ?>

  <h2 class="m360-section-title">خطوط این پرونده</h2>
  <ul>
    <?php foreach ($lines as $ln): ?>
      <li>
        <a href="?jobcard_id=<?= $jobcardId ?>&service_line_id=<?= (int)$ln['service_line_id'] ?>">
          <?= m360_am_h((string)$ln['display_title']) ?> — <?= m360_am_h((string)$ln['status']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php mirror_render_foot(); ?>
