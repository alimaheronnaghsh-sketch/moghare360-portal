<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-tree-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-header.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);
$message = '';
$ok = false;
if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'assign_team') {
        $result = m360_fulljob_assign_team($conn, $jobcardId, (string)($_POST['team_code'] ?? ''), (int)$actor['user_id'], (string)($_POST['assignment_description'] ?? ''));
        $message = (string)$result['message'];
        $ok = !empty($result['ok']);
    } elseif ($action === 'assign_technician') {
        $result = m360_fulljob_assign_technician($conn, $jobcardId, (int)($_POST['technician_user_id'] ?? 0), (int)($_POST['assistant_user_id'] ?? 0) ?: null, (int)$actor['user_id'], (string)($_POST['priority'] ?? 'NORMAL'), (string)($_POST['assignment_description'] ?? ''));
        $message = (string)$result['message'];
        $ok = !empty($result['ok']);
    }
}
$jobcard = is_resource($conn) ? m360_fulljob_fetch_jobcard($conn, $jobcardId) : null;
$assignments = is_resource($conn) ? m360_fulljob_list_assignments($conn, $jobcardId) : [];
$requests = is_resource($conn) ? m360_fulljob_list_requests($conn, $jobcardId) : [];
$externalServices = is_resource($conn) ? m360_fulljob_list_external_services($conn, $jobcardId) : [];
$customerGate = is_resource($conn) ? m360_fulljob_customer_approval_blocked($conn, $jobcardId) : ['ok' => false, 'message' => 'پایگاه داده در دسترس نیست'];
$m360StageTree = m360_case_stage_tree_resolve($conn, ['jobcard_id' => $jobcardId]);

mirror_render_head('جزئیات سالن کارت کار', 'staff');
?>
<section class="m360-card">
  <h1 class="m360-step-title">جزئیات سالن کارت کار</h1>
  <?php if ($message !== ''): ?><p class="<?= $ok ? 'm360-alert m360-alert-success' : 'm360-alert m360-alert-error' ?>"><?= m360_fulljob_h($message) ?></p><?php endif; ?>
  <?php if ($jobcard === null): ?>
    <p class="m360-alert m360-alert-error">کارت کار یافت نشد.</p>
  <?php else: ?>
    <?= m360_render_case_stage_header($m360StageTree) ?>

    <div class="m360-grid">
      <div><strong>کارت کار:</strong> <?= m360_fulljob_h((string)$jobcard['jobcard_number']) ?></div>
      <div><strong>درخواست:</strong> <?= m360_fulljob_h((string)$jobcard['online_request_id']) ?></div>
      <div><strong>مشتری:</strong> <?= m360_fulljob_h((string)$jobcard['customer_name']) ?></div>
      <div><strong>خودرو:</strong> <?= m360_fulljob_h(trim((string)$jobcard['brand'] . ' ' . (string)$jobcard['model'] . ' ' . (string)$jobcard['plate_number'])) ?></div>
      <div><strong>قرارداد:</strong> <?= m360_fulljob_h((string)$jobcard['contract_status']) ?></div>
      <div><strong>فنی:</strong> <?= m360_fulljob_h((string)$jobcard['technical_status']) ?></div>
    </div>

    <section class="m360-alert m360-alert-info">
      <strong>مرحله فعلی:</strong> بررسی سالن، تخصیص تیم/تکنسین و مدیریت درخواست‌های فنی.<br>
      <strong>اقدام بعدی:</strong> تکمیل تخصیص‌ها و بررسی درخواست‌های تکنسین.<br>
      <strong>اقدام ممنوع:</strong> شروع یا تحویل کار با گیت مشتری، درخواست باز، خدمت خارجی حل‌نشده یا هزینه تأییدنشده.
    </section>

    <h2 class="m360-section-title">تخصیص تیم</h2>
    <form method="post" class="m360-form">
      <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
      <input type="hidden" name="action" value="assign_team">
      <select name="team_code"><option value="MECHANICAL">تیم مکانیک</option><option value="ELECTRICAL">تیم برق</option></select>
      <input name="assignment_description" placeholder="شرح تخصیص">
      <button class="m360-btn m360-btn-primary" type="submit">ثبت تیم</button>
    </form>

    <h2 class="m360-section-title">تخصیص تکنسین</h2>
    <form method="post" class="m360-form">
      <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
      <input type="hidden" name="action" value="assign_technician">
      <input name="technician_user_id" value="20005" inputmode="numeric">
      <input name="assistant_user_id" placeholder="دستیار اختیاری">
      <select name="priority"><option>NORMAL</option><option>HIGH</option><option>URGENT</option><option>SAFETY_CRITICAL</option></select>
      <input name="assignment_description" value="AUTO-UAT-FULLJOB technician assignment">
      <button class="m360-btn m360-btn-primary" type="submit">ثبت تکنسین</button>
    </form>

    <h2 class="m360-section-title">وضعیت گیت مشتری</h2>
    <p class="<?= !empty($customerGate['ok']) ? 'm360-alert m360-alert-success' : 'm360-alert m360-alert-warning' ?>"><?= m360_fulljob_h((string)$customerGate['message']) ?></p>

    <h2 class="m360-section-title">مسیرهای کنترل‌شده</h2>
    <p>
      <a class="m360-btn" href="erp-technician-work-board.php?jobcard_id=<?= $jobcardId ?>">تابلوی تکنسین</a>
      <a class="m360-btn" href="erp-technical-request-center.php?jobcard_id=<?= $jobcardId ?>">مرکز درخواست فنی</a>
      <a class="m360-btn" href="erp-hall-review-queue.php">صف بررسی سالن</a>
      <a class="m360-btn" href="erp-parts-request-handoff.php?jobcard_id=<?= $jobcardId ?>">قطعه / مواد</a>
      <a class="m360-btn" href="erp-external-service-handoff.php?jobcard_id=<?= $jobcardId ?>">خدمت خارجی</a>
      <a class="m360-btn" href="erp-customer-clarification-queue.php?jobcard_id=<?= $jobcardId ?>">شفاف‌سازی مشتری</a>
      <a class="m360-btn" href="erp-work-hold-board.php?jobcard_id=<?= $jobcardId ?>">توقف کار</a>
    </p>

    <h2 class="m360-section-title">درخواست‌های تکنسین</h2>
    <table class="m360-table"><thead><tr><th>ID</th><th>نوع</th><th>عنوان</th><th>اولویت</th><th>ریسک</th><th>وضعیت</th><th>جزئیات</th></tr></thead><tbody>
    <?php foreach ($requests as $request): ?><tr>
      <td><?= (int)$request['technical_request_id'] ?></td><td><?= m360_fulljob_h(m360_fulljob_request_type_label_fa((string)$request['request_type'])) ?></td><td><?= m360_fulljob_h((string)$request['title']) ?></td><td><?= m360_fulljob_h(m360_fulljob_priority_label_fa((string)$request['priority'])) ?></td><td><?= m360_fulljob_h(m360_fulljob_risk_label_fa((string)$request['risk_level'])) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$request['status'])) ?></td><td><a href="erp-technical-request-detail.php?technical_request_id=<?= (int)$request['technical_request_id'] ?>">بازبینی</a></td>
    </tr><?php endforeach; ?>
    <?php if ($requests === []): ?><tr><td colspan="7">درخواست فنی ثبت نشده است.</td></tr><?php endif; ?>
    </tbody></table>

    <h2 class="m360-section-title">زنجیره خدمات خارج از مجموعه</h2>
    <table class="m360-table"><thead><tr><th>ID</th><th>فروشنده</th><th>وضعیت</th><th>هزینه</th><th>بازگشت مورد انتظار</th></tr></thead><tbody>
    <?php foreach ($externalServices as $service): ?><tr><td><?= (int)$service['external_service_request_id'] ?></td><td><?= m360_fulljob_h((string)$service['vendor_name']) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$service['status'])) ?></td><td><?= m360_fulljob_h((string)$service['estimated_cost']) ?></td><td><?= m360_fulljob_h((string)$service['expected_return_at']) ?></td></tr><?php endforeach; ?>
    <?php if ($externalServices === []): ?><tr><td colspan="5">خدمت خارج از مجموعه ثبت نشده است.</td></tr><?php endif; ?>
    </tbody></table>

    <h2 class="m360-section-title">مسیرهای بعدی</h2>
    <p>
      <a class="m360-btn" href="erp-estimate-detail.php?jobcard_id=<?= $jobcardId ?>">برآورد</a>
      <a class="m360-btn" href="erp-work-execution-board.php">اجرای کار</a>
      <a class="m360-btn" href="erp-qc-board.php">QC</a>
      <a class="m360-btn" href="erp-final-invoice-board.php">فاکتور نهایی</a>
      <a class="m360-btn" href="erp-delivery-control.php?jobcard_id=<?= $jobcardId ?>">تحویل</a>
    </p>

    <h2 class="m360-section-title">تخصیص‌ها</h2>
    <table class="m360-table"><thead><tr><th>ID</th><th>نوع</th><th>تیم</th><th>تکنسین</th><th>اولویت</th><th>وضعیت</th></tr></thead><tbody>
    <?php foreach ($assignments as $assignment): ?><tr><td><?= (int)$assignment['assignment_id'] ?></td><td><?= m360_fulljob_h((string)$assignment['assignment_type']) ?></td><td><?= m360_fulljob_h((string)$assignment['team_code']) ?></td><td><?= m360_fulljob_h((string)$assignment['assigned_to_user_id']) ?></td><td><?= m360_fulljob_h(m360_fulljob_priority_label_fa((string)$assignment['priority'])) ?></td><td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$assignment['status'])) ?></td></tr><?php endforeach; ?>
    <?php if ($assignments === []): ?><tr><td colspan="6">تخصیصی ثبت نشده است.</td></tr><?php endif; ?>
    </tbody></table>
  <?php endif; ?>
</section>
<?php mirror_render_foot(); ?>
