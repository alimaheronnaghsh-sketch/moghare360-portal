<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);

$canToggleTest = m360_fulljob_role_can_hall((string)($actor['role_code'] ?? ''));
$showTest = $canToggleTest && isset($_GET['show_test']) && (string)$_GET['show_test'] === '1';
$rows = is_resource($conn) ? m360_fulljob_hall_cartable($conn, $showTest) : [];

mirror_render_head('کارتابل مدیر سالن', 'staff');
?>
<style>
  .m360-hall-time-cell { font-size: .82rem; line-height: 1.45; white-space: normal; min-width: 7.5rem; }
  .m360-hall-time-cell .m360-hall-time-label { color: #6b7280; display: block; font-size: .75rem; }
  .m360-hall-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .m360-hall-table-wrap .m360-table { min-width: 720px; }
  @media (max-width: 720px) {
    .m360-hall-table-wrap .m360-table { min-width: 0; }
    .m360-hall-table-wrap .m360-table thead { display: none; }
    .m360-hall-table-wrap .m360-table tr { display: block; margin-bottom: .85rem; border: 1px solid #e5e7eb; border-radius: 8px; padding: .55rem .65rem; }
    .m360-hall-table-wrap .m360-table td { display: flex; justify-content: space-between; gap: .75rem; border: 0; padding: .28rem 0; }
    .m360-hall-table-wrap .m360-table td::before { content: attr(data-label); font-weight: 600; color: #6b7280; flex: 0 0 42%; }
  }
</style>
<section class="m360-card">
  <h1 class="m360-step-title">کارتابل مدیر سالن</h1>
  <p class="m360-muted">پرونده‌های پذیرش و قرارداد که باید توسط مدیر سالن بررسی، تیم‌بندی و به تکنسین ارجاع شوند.</p>
  <p class="m360-alert m360-alert-info">اقدام بعدی مجاز: بررسی سالن و تخصیص تیم. اقدام ممنوع: شروع کار بدون تأیید گیت مشتری یا درخواست‌های باز.</p>

  <?php if ($canToggleTest): ?>
    <p>
      <?php if ($showTest): ?>
        <a class="m360-btn" href="erp-hall-cartable.php">مخفی‌کردن داده‌های آزمایشی</a>
        <span class="m360-muted">نمایش داده‌های آزمایشی فعال است.</span>
      <?php else: ?>
        <a class="m360-btn" href="erp-hall-cartable.php?show_test=1">نمایش داده‌های آزمایشی</a>
      <?php endif; ?>
    </p>
  <?php endif; ?>

  <div class="m360-hall-table-wrap">
  <table class="m360-table">
    <thead>
      <tr>
        <th>شماره پرونده کار</th>
        <th>شماره درخواست</th>
        <th>مشتری</th>
        <th>نوع پذیرش</th>
        <th>خودرو</th>
        <th>پلاک</th>
        <th>زمان ثبت پذیرش</th>
        <th>زمان ارجاع به مدیر سالن</th>
        <th>وضعیت</th>
        <th>اقدام</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $channel = trim((string)($row['request_source_channel'] ?? ''));
        if ($channel === '') {
            $channel = trim((string)($row['request_source'] ?? ''));
        }
        $isTest = m360_fulljob_is_test_source_channel($channel);
        $vehicle = trim((string)($row['brand'] ?? '') . ' ' . (string)($row['model'] ?? ''));
        $receptionRaw = m360_fulljob_resolve_reception_created_at(
            (string)($row['request_created_at'] ?? ''),
            (string)($row['jobcard_created_at'] ?? ''),
            (string)($row['reception_at'] ?? '')
        );
        $hallRefRaw = trim((string)($row['hall_referral_at'] ?? $row['created_at'] ?? ''));
        $receptionIsDateOnly = $receptionRaw !== null && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]00:00:00(?:\.0+)?)?$/', $receptionRaw) === 1
            && trim((string)($row['request_created_at'] ?? '')) === ''
            && trim((string)($row['jobcard_created_at'] ?? '')) === '';
        $receptionFa = $receptionIsDateOnly
            ? m360_fulljob_display_jalali_date($receptionRaw)
            : m360_fulljob_display_jalali_datetime($receptionRaw);
        $hallRefFa = m360_fulljob_display_jalali_datetime($hallRefRaw !== '' ? $hallRefRaw : null);
      ?>
      <tr>
        <td data-label="شماره پرونده کار">
          <?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)$row['jobcard_number'])) ?>
          <?php if ($isTest): ?>
            <span class="m360-alert m360-alert-warning" style="display:inline;padding:0.1rem 0.4rem;margin-inline-start:0.35rem;">داده آزمایشی</span>
          <?php endif; ?>
        </td>
        <td data-label="شماره درخواست"><?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)$row['online_request_id'])) ?></td>
        <td data-label="مشتری"><?= m360_fulljob_h((string)$row['customer_name']) ?></td>
        <td data-label="نوع پذیرش"><?= m360_fulljob_h(m360_fulljob_reception_type_label_fa($channel)) ?></td>
        <td data-label="خودرو"><?= m360_fulljob_h($vehicle) ?></td>
        <td data-label="پلاک"><?= m360_fulljob_h((string)($row['plate_number'] ?? '')) ?></td>
        <td data-label="زمان ثبت پذیرش" class="m360-hall-time-cell" title="برای این رویداد زمان معتبر در سوابق فعلی ثبت نشده است.">
          <span class="m360-hall-time-label">زمان ثبت پذیرش</span>
          <?= m360_fulljob_h($receptionFa) ?>
        </td>
        <td data-label="زمان ارجاع به مدیر سالن" class="m360-hall-time-cell">
          <span class="m360-hall-time-label">زمان ارجاع به مدیر سالن</span>
          <?= m360_fulljob_h($hallRefFa) ?>
        </td>
        <td data-label="وضعیت"><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$row['status'])) ?></td>
        <td data-label="اقدام"><a class="m360-btn m360-btn-primary" href="erp-hall-jobcard-detail.php?jobcard_id=<?= (int)$row['jobcard_id'] ?>">باز کردن</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
      <tr><td colspan="10">مورد فعالی در کارتابل مدیر سالن وجود ندارد.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</section>
<?php mirror_render_foot(); ?>
