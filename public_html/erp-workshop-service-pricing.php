<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/includes/mirror-layout.php';
require_once __DIR__ . '/includes/m360-workshop-service-line-helper.php';
require_once __DIR__ . '/includes/m360-fulljob-lifecycle-helper.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$ctx = m360_ws_require_actor_context();
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
$lineId = (int)($_GET['service_line_id'] ?? $_POST['service_line_id'] ?? 0);

$message = '';
$okFlag = false;

if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    $lineId = (int)($_POST['service_line_id'] ?? 0);
    $row = m360_ws_sl_fetch($conn, $lineId);
    if ($row === null) {
        $message = 'خط خدمت یافت نشد.';
    } else {
        $jobcardId = (int)$row['jobcard_id'];
        m360_ws_assert_jobcard_object_scope($conn, $jobcardId);
        if ($action === 'approve') {
            m360_ws_require('workshop.service_line.review', $jobcardId);
            $res = m360_ws_sl_technical_approve($conn, $lineId, (int)$actor['user_id']);
        } elseif ($action === 'return') {
            m360_ws_require('workshop.service_line.return', $jobcardId);
            $res = m360_ws_sl_return($conn, $lineId, (int)$actor['user_id'], (string)($_POST['return_reason'] ?? ''));
        } elseif ($action === 'set_price') {
            m360_ws_require('workshop.service_line.price', $jobcardId);
            $res = m360_ws_sl_set_price(
                $conn,
                $lineId,
                $_POST['price_irr_input'] ?? '',
                (int)$actor['user_id'],
                (string)($_POST['price_change_reason'] ?? '')
            );
        } elseif ($action === 'ready') {
            m360_ws_require('workshop.service_line.mark_ready_for_invoice', $jobcardId);
            $res = m360_ws_sl_mark_ready_for_invoice($conn, $lineId, (int)$actor['user_id']);
        } else {
            $res = ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
        }
        $message = $res['message'];
        $okFlag = !empty($res['ok']);
    }
}

$canViewPrice = m360_ws_can('workshop.service_line.view_price') || m360_ws_can('workshop.service_line.price');
$queue = is_resource($conn)
    ? m360_ws_sl_list_submitted_queue($conn, (int)$ctx['company_id'], !empty($ctx['is_owner']))
    : [];
$current = $lineId > 0 ? m360_ws_sl_fetch($conn, $lineId) : null;
if ($current !== null) {
    m360_ws_assert_jobcard_object_scope($conn, (int)$current['jobcard_id']);
    $jobcardId = (int)$current['jobcard_id'];
    $current = m360_ws_sl_public_row($current, $canViewPrice);
}
$st = strtoupper((string)($current['status'] ?? ''));

mirror_render_head('قیمت‌گذاری خدمات', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">بررسی فنی و قیمت‌گذاری خدمات</h1>
  <p class="m360-muted">مبلغ به ریال (عدد صحیح). جداکننده هزارتایی فقط نمایشی است.</p>
  <?php if ($message !== ''): ?><p class="m360-alert <?= $okFlag ? 'm360-alert-success' : 'm360-alert-error' ?>"><?= m360_am_h($message) ?></p><?php endif; ?>

  <h2 class="m360-section-title">صف بررسی</h2>
  <ul>
    <?php foreach ($queue as $q): ?>
      <?php $pub = m360_ws_sl_public_row($q, $canViewPrice); ?>
      <li>
        <a href="?service_line_id=<?= (int)$q['service_line_id'] ?>">
          JC<?= (int)$q['jobcard_id'] ?> — <?= m360_am_h((string)$pub['display_title']) ?> — <?= m360_am_h((string)$q['status']) ?>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if ($queue === []): ?><li class="m360-muted">موردی در صف نیست.</li><?php endif; ?>
  </ul>

  <?php if ($current !== null): ?>
    <h2 class="m360-section-title">جزئیات</h2>
    <p><strong><?= m360_am_h((string)$current['display_title']) ?></strong></p>
    <p>وضعیت: <?= m360_am_h($st) ?> · مدت: <?= (int)($current['actual_minutes'] ?? 0) ?> دقیقه</p>
    <p><?= m360_am_h((string)($current['service_description'] ?? '')) ?></p>
    <?php if ($canViewPrice): ?>
      <p>مبلغ: <?= m360_am_h((string)($current['price_irr_display'] ?? '—')) ?></p>
    <?php endif; ?>

    <?php if ($st === 'SUBMITTED' && m360_ws_can('workshop.service_line.review')): ?>
    <form method="post" class="m360-form">
      <input type="hidden" name="service_line_id" value="<?= $lineId ?>">
      <input type="hidden" name="action" value="approve">
      <button class="m360-btn m360-btn-primary" type="submit">تأیید فنی</button>
    </form>
    <?php endif; ?>
    <?php if ($st === 'SUBMITTED' && m360_ws_can('workshop.service_line.return')): ?>
    <form method="post" class="m360-form">
      <input type="hidden" name="service_line_id" value="<?= $lineId ?>">
      <input type="hidden" name="action" value="return">
      <label>دلیل برگشت<textarea name="return_reason" required></textarea></label>
      <button class="m360-btn" type="submit">برگشت برای اصلاح</button>
    </form>
    <?php endif; ?>

    <?php if (in_array($st, ['PRICING_PENDING', 'PRICED', 'TECHNICALLY_APPROVED'], true) && m360_ws_can('workshop.service_line.price')): ?>
    <form method="post" class="m360-form" id="m360-sl-price-form">
      <input type="hidden" name="service_line_id" value="<?= $lineId ?>">
      <input type="hidden" name="action" value="set_price">
      <label>مبلغ (ریال)
        <input name="price_irr_input" id="m360-sl-price-input" inputmode="numeric" required
               value="<?= $canViewPrice && isset($current['price_irr']) ? m360_am_h((string)(int)$current['price_irr']) : '' ?>">
      </label>
      <p class="m360-muted" id="m360-sl-price-preview">—</p>
      <label>دلیل تغییر مبلغ (در صورت ویرایش)<textarea name="price_change_reason"></textarea></label>
      <button class="m360-btn m360-btn-primary" type="submit">ثبت مبلغ</button>
    </form>
    <script>
    (function () {
      var el = document.getElementById('m360-sl-price-input');
      var prev = document.getElementById('m360-sl-price-preview');
      if (!el || !prev) return;
      function faDigits(s) {
        return String(s).replace(/[0-9]/g, function (d) {
          return '۰۱۲۳۴۵۶۷۸۹'[d];
        });
      }
      function format() {
        var raw = el.value.replace(/[^\d۰-۹0-9]/g, '');
        var map = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9'};
        var n = raw.replace(/[۰-۹]/g, function (c) { return map[c] || c; });
        if (!n) { prev.textContent = '—'; return; }
        var withSep = n.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        prev.textContent = faDigits(withSep) + ' ریال';
      }
      el.addEventListener('input', format);
      format();
    })();
    </script>
    <?php endif; ?>

    <?php if ($st === 'PRICED' && m360_ws_can('workshop.service_line.mark_ready_for_invoice')): ?>
    <form method="post" class="m360-form">
      <input type="hidden" name="service_line_id" value="<?= $lineId ?>">
      <input type="hidden" name="action" value="ready">
      <button class="m360-btn" type="submit">آماده‌سازی برای فاکتور</button>
    </form>
    <?php endif; ?>

    <?php if ($jobcardId > 0): ?>
      <p><a href="erp-workshop-service-summary.php?jobcard_id=<?= $jobcardId ?>">خلاصه خدمات پرونده</a></p>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php mirror_render_foot(); ?>
