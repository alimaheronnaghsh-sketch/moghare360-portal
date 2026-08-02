<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-fulljob-lifecycle-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-tree-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-case-stage-header.php';

$conn = customer_core_db();
$actor = m360_fulljob_require_role($conn, ['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER']);
$jobcardId = (int)($_GET['jobcard_id'] ?? $_POST['jobcard_id'] ?? 0);

$allowedTabs = ['overview', 'assignments', 'requests', 'external', 'timeline', 'quality'];
$tabRaw = strtolower(trim((string)($_GET['tab'] ?? $_POST['tab'] ?? 'overview')));
$tab = in_array($tabRaw, $allowedTabs, true) ? $tabRaw : 'overview';

if (is_resource($conn) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $result = ['ok' => false, 'message' => 'اقدام نامعتبر است.'];
    if ($action === 'assign_team') {
        $result = m360_fulljob_assign_team(
            $conn,
            $jobcardId,
            (string)($_POST['team_code'] ?? ''),
            (int)$actor['user_id'],
            (string)($_POST['assignment_description'] ?? '')
        );
    } elseif ($action === 'assign_technician') {
        $result = m360_fulljob_assign_technician(
            $conn,
            $jobcardId,
            (int)($_POST['technician_user_id'] ?? 0),
            (int)($_POST['assistant_user_id'] ?? 0) ?: null,
            (int)$actor['user_id'],
            (string)($_POST['priority'] ?? 'NORMAL'),
            (string)($_POST['assignment_description'] ?? '')
        );
    }

    $flashType = 'error';
    if (!empty($result['ok']) && !empty($result['idempotent'])) {
        $flashType = 'info';
    } elseif (!empty($result['ok'])) {
        $flashType = 'success';
    } elseif (!empty($result['conflict'])) {
        $flashType = 'info';
    }

    m360_fulljob_set_hall_flash([
        'ok' => !empty($result['ok']),
        'idempotent' => !empty($result['idempotent']),
        'type' => $flashType,
        'message' => (string)($result['message'] ?? ''),
    ]);
    header('Location: erp-hall-jobcard-detail.php?jobcard_id=' . $jobcardId . '&tab=assignments');
    exit;
}

$flash = m360_fulljob_consume_hall_flash();
$message = (string)($flash['message'] ?? '');
$flashType = (string)($flash['type'] ?? '');
$ok = !empty($flash['ok']);

$jobcard = is_resource($conn) ? m360_fulljob_fetch_jobcard($conn, $jobcardId) : null;
$assignments = is_resource($conn) ? m360_fulljob_list_assignments($conn, $jobcardId) : [];
$activeTeams = is_resource($conn) ? m360_fulljob_active_team_assignments($conn, $jobcardId) : [];
$activeTechnicians = is_resource($conn) ? m360_fulljob_active_technician_assignments($conn, $jobcardId) : [];
$requests = ($tab === 'requests' || $tab === 'overview') && is_resource($conn)
    ? m360_fulljob_list_requests($conn, $jobcardId)
    : [];
$externalServices = $tab === 'external' && is_resource($conn)
    ? m360_fulljob_list_external_services($conn, $jobcardId)
    : [];
$customerGate = is_resource($conn)
    ? m360_fulljob_customer_approval_blocked($conn, $jobcardId)
    : ['ok' => false, 'message' => 'پایگاه داده در دسترس نیست'];
$m360StageTree = m360_case_stage_tree_resolve($conn, ['jobcard_id' => $jobcardId]);
$timeline = $tab === 'timeline' && is_resource($conn)
    ? m360_fulljob_build_jobcard_timeline($conn, $jobcardId)
    : ['events' => [], 'missing' => []];

$activeTeamCodes = [];
foreach ($activeTeams as $team) {
    $code = strtoupper(trim((string)($team['team_code'] ?? '')));
    if ($code !== '') {
        $activeTeamCodes[$code] = true;
    }
}
$availableTeamCodes = array_values(array_filter(
    m360_fulljob_team_codes(),
    static fn(string $code): bool => !isset($activeTeamCodes[strtoupper($code)])
));

$currentAssignments = [];
$historyAssignments = [];
foreach ($assignments as $assignment) {
    $st = strtoupper(trim((string)($assignment['status'] ?? '')));
    if ($st === 'ACTIVE') {
        $currentAssignments[] = $assignment;
    } else {
        $historyAssignments[] = $assignment;
    }
}
$hasActiveTechnician = $activeTechnicians !== [];

$stageNumber = (int)($m360StageTree['current_stage_number'] ?? 0);
$stageLabel = trim((string)($m360StageTree['current_stage'] ?? 'نامشخص'));
$currentStatus = trim((string)($m360StageTree['current_status'] ?? 'نامشخص'));
$nextAction = trim((string)($m360StageTree['next_action'] ?? 'نیازمند بررسی'));
$actionOwner = function_exists('m360_case_stage_owner_label')
    ? m360_case_stage_owner_label((string)($m360StageTree['action_owner'] ?? 'system'))
    : 'نامشخص';
$blocker = trim((string)($m360StageTree['blocker_reason'] ?? ''));
$blockerDisplay = $blocker !== '' ? $blocker : 'بدون مانع';

$jobcardStatus = strtoupper(trim((string)($jobcard['jobcard_status'] ?? '')));
$techStatus = strtoupper(trim((string)($jobcard['technical_status'] ?? '')));
$wxStatus = strtoupper(trim((string)($jobcard['work_execution_status'] ?? '')));
$qcStatus = strtoupper(trim((string)($jobcard['qc_status'] ?? '')));
$closedAt = trim((string)($jobcard['jobcard_closed_at'] ?? ''));
$isClosedCase = $stageNumber === 14
    || $closedAt !== ''
    || in_array($jobcardStatus, ['CLOSED', 'DELIVERED', 'COMPLETED'], true)
    || str_contains($stageLabel, 'بسته‌شده')
    || str_contains($stageLabel, 'بسته');

$hasActiveOperational = false;
$hasActiveHallIntake = false;
foreach ($currentAssignments as $a) {
    $t = strtoupper(trim((string)($a['assignment_type'] ?? '')));
    if ($t === 'HALL_INTAKE') {
        $hasActiveHallIntake = true;
        $hasActiveOperational = true;
    }
    if ($t === 'TEAM_ASSIGNMENT' || $t === 'TECHNICIAN_ASSIGNMENT') {
        $hasActiveOperational = true;
    }
}
$wxOpen = in_array($wxStatus, ['READY_FOR_QC', 'WORK_STARTED', 'SERVICE_IN_PROGRESS', 'TECHNICAL_COMPLETION_REVIEW', 'WORK_QUEUE'], true);

$consistencyIssues = [];
if ($isClosedCase && $hasActiveOperational) {
    $consistencyIssues[] = 'این پرونده بسته‌شده است، اما تخصیص فعال یا وضعیت اجرایی باز دارد. پیش از ادامه عملیات باید وضعیت پرونده بررسی شود.';
}
if ($isClosedCase && $wxOpen) {
    $consistencyIssues[] = 'مرحله نهایی بسته‌شده با وضعیت اجرایی باز هم‌خوانی ندارد.';
}
if ($isClosedCase && $hasActiveHallIntake) {
    $consistencyIssues[] = 'پرونده بسته‌شده هنوز تحویل فعال به مدیر سالن دارد.';
}
$hasConsistencyWarning = $consistencyIssues !== [];
$allowAssignmentForms = !$isClosedCase && !$hasConsistencyWarning;

/**
 * @param array<string, mixed> $assignment
 */
function m360_hall_assignment_subject_fa(array $assignment): string
{
    $type = strtoupper(trim((string)($assignment['assignment_type'] ?? '')));
    if ($type === 'TEAM_ASSIGNMENT' || $type === 'HALL_INTAKE') {
        $team = trim((string)($assignment['team_code'] ?? ''));
        if ($team !== '') {
            return m360_fulljob_team_label_fa($team);
        }
        if ($type === 'HALL_INTAKE') {
            return m360_fulljob_assignment_type_label_fa('HALL_INTAKE');
        }

        return 'نامشخص';
    }
    if ($type === 'TECHNICIAN_ASSIGNMENT') {
        $name = trim((string)($assignment['technician_full_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return ((int)($assignment['assigned_to_user_id'] ?? 0) > 0) ? 'تکنسین ثبت‌شده' : 'نامشخص';
    }

    return 'نامشخص';
}

/**
 * @param array<string, mixed> $assignment
 */
function m360_hall_assignment_time_lines(array $assignment): string
{
    $type = strtoupper(trim((string)($assignment['assignment_type'] ?? '')));
    $created = m360_fulljob_display_jalali_datetime((string)($assignment['created_at'] ?? ''));
    $updated = trim((string)($assignment['updated_at'] ?? ''));
    $lines = [];
    if ($type === 'TEAM_ASSIGNMENT' || $type === 'HALL_INTAKE') {
        $lines[] = 'ارجاع: ' . $created;
    } elseif ($type === 'TECHNICIAN_ASSIGNMENT') {
        $lines[] = 'تخصیص: ' . $created;
    } else {
        $lines[] = $created;
    }
    if ($updated !== '') {
        $lines[] = 'آخرین تغییر معتبر: ' . m360_fulljob_display_jalali_datetime($updated);
    }

    return implode("\n", $lines);
}

function m360_hall_tab_url(int $jobcardId, string $tab): string
{
    return 'erp-hall-jobcard-detail.php?jobcard_id=' . $jobcardId . '&tab=' . rawurlencode($tab);
}

$tabLabels = [
    'overview' => 'خلاصه پرونده',
    'assignments' => 'تخصیص‌ها',
    'requests' => 'درخواست‌ها و قطعه',
    'external' => 'خدمات بیرونی',
    'timeline' => 'سیر زمانی',
    'quality' => 'کنترل کیفیت و ترخیص',
];

// Key timestamps for overview (max 4)
$receptionRaw = null;
$hallRefRaw = null;
$lastTeamRef = null;
$lastChange = null;
if ($jobcard !== null) {
    $reqCreated = null;
    $onlineRequestId = (int)($jobcard['online_request_id'] ?? 0);
    if ($onlineRequestId > 0 && is_resource($conn)) {
        $reqCreated = customer_core_scalar(
            $conn,
            'SELECT TOP 1 created_at FROM dbo.erp_customer_online_requests WHERE online_request_id = ?',
            [$onlineRequestId]
        );
    }
    $receptionRaw = m360_fulljob_resolve_reception_created_at(
        $reqCreated !== null ? (string)$reqCreated : null,
        (string)($jobcard['created_at'] ?? ''),
        (string)($jobcard['reception_at'] ?? '')
    );
    foreach ($assignments as $a) {
        if (strtoupper(trim((string)($a['assignment_type'] ?? ''))) === 'HALL_INTAKE') {
            $c = trim((string)($a['created_at'] ?? ''));
            if ($c !== '' && ($hallRefRaw === null || $c < $hallRefRaw)) {
                $hallRefRaw = $c;
            }
        }
        if (strtoupper(trim((string)($a['assignment_type'] ?? ''))) === 'TEAM_ASSIGNMENT') {
            $c = trim((string)($a['created_at'] ?? ''));
            if ($c !== '' && ($lastTeamRef === null || $c > $lastTeamRef)) {
                $lastTeamRef = $c;
            }
        }
        foreach (['updated_at', 'closed_at', 'created_at'] as $col) {
            $c = trim((string)($a[$col] ?? ''));
            if ($c !== '' && ($lastChange === null || $c > $lastChange)) {
                $lastChange = $c;
            }
        }
    }
    if ($hallRefRaw === null || $hallRefRaw === '') {
        $hallRefRaw = trim((string)($jobcard['ready_for_technical_at'] ?? ''));
    }
    $jcUpdated = trim((string)($jobcard['updated_at'] ?? ''));
    if ($jcUpdated !== '' && ($lastChange === null || $jcUpdated > $lastChange)) {
        $lastChange = $jcUpdated;
    }
}

$openRequests = [];
foreach ($requests as $request) {
    $st = strtoupper(trim((string)($request['status'] ?? '')));
    if (!in_array($st, ['CLOSED', 'REJECTED', 'CANCELLED', 'APPROVED'], true)) {
        $openRequests[] = $request;
    }
}

$canQc = $jobcard !== null && m360_fulljob_can_render_ready_for_qc((string)($actor['role_code'] ?? ''), $jobcard);

mirror_render_head('جزئیات سالن کارت کار', 'staff');
?>
<style>
/* G0.2R6: dark-green surfaces + explicit readable text (no white card + inherited light text) */
.m360-hall-shell{max-width:1100px;margin:0 auto;padding-top:.15rem;color:#f3f4f6;scroll-margin-top:6rem}
.m360-hall-shell,.m360-hall-shell *{opacity:1}
.m360-hall-topbar{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:.65rem;margin:0 0 .85rem}
.m360-hall-topbar .m360-step-title{margin:0;flex:1 1 16rem}
.m360-hall-head{
  display:grid;grid-template-columns:1.2fr 1fr;gap:.55rem 1.1rem;padding:.75rem .9rem;margin-bottom:.85rem;
  border:1px solid rgba(34,197,94,.22);border-radius:12px;
  background:linear-gradient(145deg,rgba(28,43,36,.95),rgba(15,23,42,.78));
  color:#f3f4f6
}
.m360-hall-head div{font-size:.9rem;line-height:1.45;color:#e5e7eb}
.m360-hall-head strong{display:inline-block;min-width:6.5rem;color:#9ca3af;font-weight:600}
.m360-hall-badge{display:inline-block;padding:.15rem .55rem;border-radius:999px;background:#111827;color:#fff;font-size:.78rem;font-weight:700}
.m360-hall-badge.warn{background:#b45309;color:#fff7ed}
.m360-hall-tabs{display:flex;gap:.35rem;overflow-x:auto;-webkit-overflow-scrolling:touch;padding:.25rem 0 .75rem;margin-bottom:.35rem;border-bottom:1px solid rgba(34,197,94,.2)}
.m360-hall-tabs a{flex:0 0 auto;text-decoration:none;padding:.45rem .7rem;border-radius:999px;border:1px solid rgba(34,197,94,.28);color:#e5e7eb;font-size:.82rem;white-space:nowrap;background:rgba(15,23,42,.55)}
.m360-hall-tabs a[aria-current="page"]{background:#0f766e;border-color:#14b8a6;color:#fff;font-weight:700}
.m360-hall-mobile-tabs{display:none;margin-bottom:.75rem;color:#e5e7eb}
.m360-hall-mobile-tabs select{width:100%;min-height:42px;margin-top:.35rem;padding:.4rem .55rem;border-radius:10px;border:1px solid rgba(34,197,94,.28);background:rgba(15,23,42,.75);color:#f3f4f6}
.m360-hall-grid2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.m360-hall-card{
  border:1px solid rgba(34,197,94,.2);border-radius:12px;padding:.7rem .8rem;
  background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.78));
  color:#f3f4f6
}
.m360-hall-card h3{margin:0 0 .45rem;font-size:.95rem;color:#fff8df}
.m360-hall-card p,.m360-hall-card li,.m360-hall-card td,.m360-hall-card th{color:#e5e7eb}
.m360-hall-card .m360-muted,.m360-hall-shell .m360-muted{color:#9ca3af !important;opacity:1 !important}
.m360-hall-mini{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem}
.m360-hall-mini .m360-hall-card{min-height:88px}
.m360-hall-actions{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.55rem}
.m360-hall-stage-strip{display:flex;gap:.35rem;overflow-x:auto;padding:.35rem 0 .6rem}
.m360-hall-stage-chip{flex:0 0 auto;border:1px solid rgba(148,163,184,.35);border-radius:999px;padding:.35rem .55rem;font-size:.72rem;background:rgba(15,23,42,.55);color:#e5e7eb}
.m360-hall-stage-chip.is-done{background:rgba(6,95,70,.45);border-color:#6ee7b7;color:#a7f3d0}
.m360-hall-stage-chip.is-current{background:rgba(154,52,18,.4);border-color:#fdba74;color:#ffedd5;font-weight:800}
.m360-hall-stage-chip.is-locked{background:rgba(127,29,29,.4);border-color:#fecaca;color:#fecaca}
.m360-hall-table-wrap{overflow-x:auto}
.m360-hall-table-wrap .m360-table{width:100%;font-size:.84rem;color:#e5e7eb}
.m360-hall-table-wrap td,.m360-hall-table-wrap th{white-space:nowrap;max-width:14rem;overflow:hidden;text-overflow:ellipsis;color:#e5e7eb}
.m360-hall-table-wrap th{color:#9ca3af}
.m360-hall-alert{margin:.55rem 0;opacity:1 !important}
.m360-hall-alert,.m360-hall-alert *{color:inherit}
.m360-hall-shell .m360-alert{opacity:1 !important}
.m360-hall-shell .m360-form input:disabled,
.m360-hall-shell .m360-form select:disabled,
.m360-hall-shell .m360-form button:disabled{opacity:.55}
.m360-hall-shell fieldset:disabled,.m360-hall-shell fieldset[disabled]{opacity:1;color:#e5e7eb}
.m360-hall-shell fieldset:disabled legend,.m360-hall-shell fieldset[disabled] legend{color:#f3f4f6}
@media (max-width:900px){
  .m360-hall-head,.m360-hall-grid2,.m360-hall-mini{grid-template-columns:1fr}
  .m360-hall-tabs{display:none}
  .m360-hall-mobile-tabs{display:block}
  .m360-hall-stage-strip{flex-direction:column}
  .m360-hall-stage-chip{width:100%}
  .m360-hall-topbar{flex-direction:column;align-items:stretch}
}
</style>
<section class="m360-card m360-hall-shell">
  <div class="m360-hall-topbar">
    <h1 class="m360-step-title">جزئیات سالن کارت کار — مقاره ۳۶۰</h1>
    <a class="m360-btn" href="erp-hall-cartable.php">بازگشت به کارتابل مدیر سالن</a>
  </div>
  <?php if ($message !== ''): ?>
    <?php
      $alertClass = 'm360-alert-error';
      if ($flashType === 'success' || ($ok && $flashType !== 'info')) {
          $alertClass = 'm360-alert-success';
      } elseif ($flashType === 'info') {
          $alertClass = 'm360-alert-info';
      }
    ?>
    <p class="m360-alert <?= m360_fulljob_h($alertClass) ?>"><?= m360_fulljob_h($message) ?></p>
  <?php endif; ?>

  <?php if ($jobcard === null): ?>
    <p class="m360-alert m360-alert-error">کارت کار یافت نشد.</p>
  <?php else: ?>
    <header class="m360-hall-head" aria-label="خلاصه پرونده">
      <div><strong>شماره پرونده کار:</strong> <?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)$jobcard['jobcard_number'])) ?>
        <?php if ($isClosedCase): ?> <span class="m360-hall-badge">پرونده بسته‌شده</span><?php endif; ?>
        <?php if ($hasConsistencyWarning): ?> <span class="m360-hall-badge warn">ناهمخوانی وضعیت</span><?php endif; ?>
      </div>
      <div><strong>مرحله فعلی:</strong> <?= m360_fulljob_h(($stageNumber > 0 ? m360_fulljob_to_persian_digits((string)$stageNumber) . ' — ' : '') . $stageLabel) ?></div>
      <div><strong>مشتری:</strong> <?= m360_fulljob_h((string)$jobcard['customer_name']) ?></div>
      <div><strong>وضعیت فعلی:</strong> <?= m360_fulljob_h($currentStatus) ?></div>
      <div><strong>خودرو:</strong> <?= m360_fulljob_h(trim((string)$jobcard['brand'] . ' ' . (string)$jobcard['model'])) ?></div>
      <div><strong>مسئول اقدام:</strong> <?= m360_fulljob_h($actionOwner) ?></div>
      <div><strong>پلاک:</strong> <?= m360_fulljob_h((string)$jobcard['plate_number']) ?></div>
      <div><strong>وضعیت فنی/اجرا:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa($techStatus !== '' ? $techStatus : $wxStatus)) ?></div>
    </header>

    <nav class="m360-hall-tabs" aria-label="بخش‌های پرونده">
      <?php foreach ($tabLabels as $key => $label): ?>
        <a href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, $key)) ?>"
           <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= m360_fulljob_h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="m360-hall-mobile-tabs">
      <label for="m360_hall_tab_select"><strong>بخش:</strong></label>
      <select id="m360_hall_tab_select" onchange="if(this.value){window.location.href=this.value;}">
        <?php foreach ($tabLabels as $key => $label): ?>
          <option value="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, $key)) ?>" <?= $tab === $key ? 'selected' : '' ?>><?= m360_fulljob_h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if ($tab === 'overview'): ?>
      <?php if ($hasConsistencyWarning): ?>
        <div class="m360-alert m360-alert-error m360-hall-alert" role="alert">
          <strong>ناهمخوانی وضعیت پرونده</strong>
          <ul style="margin:.4rem 0 0;padding-inline-start:1.2rem;">
            <?php foreach ($consistencyIssues as $issue): ?>
              <li><?= m360_fulljob_h($issue) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="m360-hall-grid2">
        <section class="m360-hall-card">
          <h3>وضعیت جاری</h3>
          <p><strong>مرحله فعلی:</strong> <?= m360_fulljob_h($stageLabel) ?></p>
          <p><strong>وضعیت فعلی:</strong> <?= m360_fulljob_h($currentStatus) ?></p>
          <p><strong>اقدام بعدی:</strong>
            <?php if ($isClosedCase): ?>
              بررسی نهایی پرونده بسته‌شده و رفع ناهمخوانی‌ها در صورت وجود
            <?php else: ?>
              <?= m360_fulljob_h($nextAction) ?>
            <?php endif; ?>
          </p>
          <p><strong>مسئول اقدام:</strong> <?= m360_fulljob_h($actionOwner) ?></p>
          <p><strong>مانع:</strong> <?= m360_fulljob_h($blockerDisplay) ?></p>
        </section>

        <section class="m360-hall-card">
          <h3>زمان‌های کلیدی</h3>
          <p><strong>زمان ثبت پذیرش:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($receptionRaw)) ?></p>
          <p><strong>زمان ارجاع به مدیر سالن:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($hallRefRaw)) ?></p>
          <p><strong>آخرین ارجاع به واحد:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($lastTeamRef)) ?></p>
          <p><strong>آخرین تغییر معتبر:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($lastChange)) ?></p>
          <?php if ($isClosedCase): ?>
            <p><strong>زمان بستن پرونده:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($closedAt !== '' ? $closedAt : null)) ?></p>
          <?php endif; ?>
        </section>
      </div>

      <section style="margin-top:.75rem;">
        <h3 class="m360-section-title">تخصیص‌های فعال (خلاصه)</h3>
        <div class="m360-hall-mini">
          <?php foreach (m360_fulljob_team_codes() as $code): ?>
            <?php
              $active = null;
              foreach ($activeTeams as $t) {
                  if (strtoupper(trim((string)($t['team_code'] ?? ''))) === $code) {
                      $active = $t;
                      break;
                  }
              }
            ?>
            <div class="m360-hall-card">
              <h3><?= m360_fulljob_h(m360_fulljob_team_label_fa($code)) ?></h3>
              <?php if ($active !== null): ?>
                <p>فعال</p>
                <p><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($active['created_at'] ?? ''))) ?></p>
                <p><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)($active['status'] ?? 'ACTIVE'))) ?></p>
              <?php else: ?>
                <p>غیرفعال</p>
                <p>ثبت نشده</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <div class="m360-hall-card">
            <h3>تکنسین فعال</h3>
            <?php if ($hasActiveTechnician): ?>
              <?php
                $tn = trim((string)($activeTechnicians[0]['technician_full_name'] ?? ''));
                if ($tn === '') {
                    $tn = 'تکنسین ثبت‌شده';
                }
              ?>
              <p><?= m360_fulljob_h($tn) ?></p>
              <p><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($activeTechnicians[0]['created_at'] ?? ''))) ?></p>
              <p>فعال</p>
            <?php else: ?>
              <p>غیرفعال</p>
              <p>ثبت نشده</p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="m360-hall-card" style="margin-top:.75rem;">
        <h3>هشدارهای عملیاتی</h3>
        <ul style="margin:0;padding-inline-start:1.2rem;">
          <?php if ($openRequests !== []): ?><li>درخواست فنی باز: <?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)count($openRequests))) ?></li><?php endif; ?>
          <?php if ($wxStatus === 'WAITING_FOR_PARTS'): ?><li>انتظار قطعه</li><?php endif; ?>
          <?php if (empty($customerGate['ok'])): ?><li>انتظار تأیید مشتری / گیت باز</li><?php endif; ?>
          <?php if (in_array($qcStatus, ['FAILED', 'QC_FAILED'], true) || $wxStatus === 'REWORK_REQUIRED'): ?><li>برگشت از کنترل کیفیت</li><?php endif; ?>
          <?php if ($hasActiveOperational): ?><li>تخصیص فعال</li><?php endif; ?>
          <?php if ($isClosedCase && $hasActiveOperational): ?><li>پرونده بسته با تخصیص فعال</li><?php endif; ?>
          <?php if ($openRequests === [] && $wxStatus !== 'WAITING_FOR_PARTS' && !empty($customerGate['ok']) && !$hasConsistencyWarning): ?>
            <li>هشدار عملیاتی فوری ثبت نشده است.</li>
          <?php endif; ?>
        </ul>
      </section>

      <section class="m360-hall-card" style="margin-top:.75rem;">
        <h3>اقدام مجاز</h3>
        <div class="m360-hall-actions">
          <?php if ($isClosedCase || $hasConsistencyWarning): ?>
            <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'timeline')) ?>">بررسی سیر زمانی</a>
            <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'assignments')) ?>">بازبینی تخصیص‌ها</a>
            <a class="m360-btn m360-btn-primary" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'quality')) ?>">کنترل کیفیت و ترخیص</a>
          <?php else: ?>
            <a class="m360-btn m360-btn-primary" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'assignments')) ?>">مدیریت تخصیص‌ها</a>
            <?php if ($openRequests !== []): ?>
              <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'requests')) ?>">بررسی درخواست‌ها</a>
            <?php endif; ?>
            <?php if ($canQc): ?>
              <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'quality')) ?>">ارسال / بررسی کنترل کیفیت</a>
            <?php else: ?>
              <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_tab_url($jobcardId, 'requests')) ?>">درخواست‌ها و قطعه</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </section>

    <?php elseif ($tab === 'assignments'): ?>
      <?php if ($hasConsistencyWarning || $isClosedCase): ?>
        <p class="m360-alert m360-alert-warning">به‌دلیل وضعیت بسته‌شده یا ناهمخوانی، فرم‌های تخصیص جدید غیرفعال است. سوابق به‌صورت فقط‌خواندنی نمایش داده می‌شود.</p>
      <?php endif; ?>

      <h2 class="m360-section-title">تخصیص واحد</h2>
      <?php if ($activeTeams !== []): ?>
        <ul>
          <?php foreach ($activeTeams as $team): ?>
            <li>
              <?= m360_fulljob_h(m360_fulljob_team_label_fa((string)$team['team_code'])) ?>
              — <strong>تخصیص داده شده</strong>
              — ارجاع: <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($team['created_at'] ?? ''))) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="m360-muted">واحد فعالی تخصیص نشده است.</p>
      <?php endif; ?>

      <?php if ($allowAssignmentForms && $availableTeamCodes !== []): ?>
        <form method="post" class="m360-form">
          <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
          <input type="hidden" name="tab" value="assignments">
          <input type="hidden" name="action" value="assign_team">
          <select name="team_code" required>
            <?php foreach ($availableTeamCodes as $code): ?>
              <option value="<?= m360_fulljob_h($code) ?>"><?= m360_fulljob_h(m360_fulljob_team_label_fa($code)) ?></option>
            <?php endforeach; ?>
          </select>
          <input name="assignment_description" placeholder="شرح تخصیص">
          <button class="m360-btn m360-btn-primary" type="submit">ثبت واحد</button>
        </form>
      <?php elseif ($allowAssignmentForms): ?>
        <p class="m360-alert m360-alert-info">همه واحدهای مجاز برای این پرونده تخصیص داده شده‌اند.</p>
      <?php endif; ?>

      <div class="m360-hall-actions">
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-unit-work-board.php?unit=MECHANICAL', $jobcardId, 'assignments')) ?>">کارتابل مکانیک</a>
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-unit-work-board.php?unit=ELECTRICAL', $jobcardId, 'assignments')) ?>">کارتابل برق</a>
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-unit-work-board.php?unit=OPTIONS', $jobcardId, 'assignments')) ?>">کارتابل آپشن</a>
        <a class="m360-btn" href="erp-technician-work-board.php">تابلوی تکنسین</a>
      </div>

      <h2 class="m360-section-title">تخصیص تکنسین</h2>
      <?php if ($hasActiveTechnician): ?>
        <ul>
          <?php foreach ($activeTechnicians as $tech): ?>
            <?php
              $techName = trim((string)($tech['technician_full_name'] ?? ''));
              if ($techName === '') {
                  $techName = 'تکنسین ثبت‌شده';
              }
            ?>
            <li>
              <?= m360_fulljob_h($techName) ?>
              — <strong>تخصیص داده شده</strong>
              — تخصیص: <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($tech['created_at'] ?? ''))) ?>
              <span class="m360-muted">(مرجع: <?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)(int)($tech['assigned_to_user_id'] ?? 0))) ?>)</span>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="m360-alert m360-alert-info">تکنسین فعال ثبت شده است. تخصیص تکراری یا جایگزینی خام از این فرم مجاز نیست.</p>
      <?php elseif ($allowAssignmentForms): ?>
        <form method="post" class="m360-form">
          <input type="hidden" name="jobcard_id" value="<?= $jobcardId ?>">
          <input type="hidden" name="tab" value="assignments">
          <input type="hidden" name="action" value="assign_technician">
          <input name="technician_user_id" placeholder="شناسه کاربر تکنسین" inputmode="numeric" required>
          <input name="assistant_user_id" placeholder="دستیار اختیاری">
          <select name="priority">
            <?php foreach (M360_FULLJOB_PRIORITIES as $priority): ?>
              <option value="<?= m360_fulljob_h($priority) ?>"><?= m360_fulljob_h(m360_fulljob_priority_label_fa($priority)) ?></option>
            <?php endforeach; ?>
          </select>
          <input name="assignment_description" placeholder="شرح تخصیص تکنسین">
          <button class="m360-btn m360-btn-primary" type="submit">ثبت تکنسین</button>
        </form>
      <?php else: ?>
        <p class="m360-muted">تکنسین فعالی ثبت نشده است.</p>
      <?php endif; ?>

      <h2 class="m360-section-title">تخصیص‌های جاری</h2>
      <div class="m360-hall-table-wrap">
        <table class="m360-table">
          <thead>
            <tr>
              <th>نوع تخصیص</th>
              <th>واحد یا تکنسین</th>
              <th>وضعیت</th>
              <th>اولویت</th>
              <th>زمان ارجاع / تخصیص</th>
              <th>اقدام مجاز</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($currentAssignments as $assignment): ?>
            <tr>
              <td><?= m360_fulljob_h(m360_fulljob_assignment_type_label_fa((string)$assignment['assignment_type'])) ?></td>
              <td><?= m360_fulljob_h(m360_hall_assignment_subject_fa($assignment)) ?></td>
              <td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$assignment['status'])) ?></td>
              <td><?= m360_fulljob_h(m360_fulljob_priority_label_fa((string)($assignment['priority'] ?? ''))) ?></td>
              <td style="white-space:pre-line;"><?= m360_fulljob_h(m360_hall_assignment_time_lines($assignment)) ?></td>
              <td>تخصیص داده شده</td>
            </tr>
          <?php endforeach; ?>
          <?php if ($currentAssignments === []): ?>
            <tr><td colspan="6">تخصیص جاری ثبت نشده است.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <details class="m360-card" style="margin-top:1rem;">
        <summary class="m360-section-title" style="cursor:pointer;">تاریخچه تخصیص‌ها</summary>
        <div class="m360-hall-table-wrap">
          <table class="m360-table">
            <thead>
              <tr>
                <th>نوع تخصیص</th>
                <th>واحد یا تکنسین</th>
                <th>وضعیت نهایی</th>
                <th>زمان شروع</th>
                <th>زمان پایان</th>
                <th>دلیل تغییر</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($historyAssignments as $assignment): ?>
              <?php
                $endRaw = trim((string)($assignment['closed_at'] ?? ''));
                $endFa = $endRaw !== ''
                    ? m360_fulljob_display_jalali_datetime($endRaw)
                    : (strtoupper((string)($assignment['status'] ?? '')) === 'ACTIVE' ? 'در جریان' : 'ثبت نشده');
              ?>
              <tr>
                <td><?= m360_fulljob_h(m360_fulljob_assignment_type_label_fa((string)$assignment['assignment_type'])) ?></td>
                <td><?= m360_fulljob_h(m360_hall_assignment_subject_fa($assignment)) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$assignment['status'])) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($assignment['created_at'] ?? ''))) ?></td>
                <td><?= m360_fulljob_h($endFa) ?></td>
                <td><?= m360_fulljob_h((string)($assignment['assignment_description'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($historyAssignments === []): ?>
              <tr><td colspan="6">تاریخچه‌ای ثبت نشده است.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </details>

    <?php elseif ($tab === 'requests'): ?>
      <h2 class="m360-section-title">درخواست‌ها و قطعه</h2>
      <p class="<?= !empty($customerGate['ok']) ? 'm360-alert m360-alert-success' : 'm360-alert m360-alert-warning' ?>">
        <strong>گیت مشتری:</strong> <?= m360_fulljob_h((string)$customerGate['message']) ?>
      </p>
      <div class="m360-hall-actions">
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-technical-request-center.php?jobcard_id=' . $jobcardId, $jobcardId, 'requests')) ?>">مرکز درخواست فنی</a>
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-parts-request-handoff.php', $jobcardId, 'requests')) ?>">قطعه / مواد</a>
        <a class="m360-btn" href="erp-customer-clarification-queue.php">شفاف‌سازی مشتری</a>
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-work-hold-board.php', $jobcardId, 'requests')) ?>">توقف / ادامه کار</a>
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-hall-review-queue.php', $jobcardId, 'requests')) ?>">صف بررسی سالن</a>
      </div>
      <?php if ($requests === []): ?>
        <p class="m360-muted">درخواست باز ثبت نشده است.</p>
      <?php else: ?>
        <div class="m360-hall-table-wrap">
          <table class="m360-table">
            <thead><tr><th>شناسه</th><th>نوع</th><th>عنوان</th><th>اولویت</th><th>ریسک</th><th>وضعیت</th><th>جزئیات</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
              <tr>
                <td><?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)(int)$request['technical_request_id'])) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_request_type_label_fa((string)$request['request_type'])) ?></td>
                <td title="<?= m360_fulljob_h((string)$request['title']) ?>"><?= m360_fulljob_h((string)$request['title']) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_priority_label_fa((string)$request['priority'])) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_risk_label_fa((string)$request['risk_level'])) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)$request['status'])) ?></td>
                <td><a href="erp-technical-request-detail.php?technical_request_id=<?= (int)$request['technical_request_id'] ?>">بازبینی</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'external'): ?>
      <h2 class="m360-section-title">خدمات بیرونی</h2>
      <div class="m360-hall-actions">
        <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-external-service-handoff.php', $jobcardId, 'external')) ?>">کارتابل خدمت خارجی</a>
      </div>
      <?php if ($externalServices === []): ?>
        <p class="m360-muted">خدمت خارج از مجموعه برای این پرونده ثبت نشده است.</p>
      <?php else: ?>
        <div class="m360-hall-table-wrap">
          <table class="m360-table">
            <thead>
              <tr>
                <th>فروشنده</th>
                <th>وضعیت</th>
                <th>هزینه برآوردی</th>
                <th>زمان ارسال</th>
                <th>بازگشت مورد انتظار</th>
                <th>بازگشت واقعی</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($externalServices as $service): ?>
              <tr>
                <td><?= m360_fulljob_h((string)($service['vendor_name'] ?? '')) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_status_label_fa((string)($service['status'] ?? ''))) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)($service['estimated_cost'] ?? ''))) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($service['created_at'] ?? $service['sent_at'] ?? ''))) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($service['expected_return_at'] ?? ''))) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($service['actual_return_at'] ?? $service['returned_at'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'timeline'): ?>
      <h2 class="m360-section-title">سیر زمانی</h2>
      <p class="m360-muted">این بخش از سوابق موجود ساخته شده و اطلاعات پرونده را تغییر نمی‌دهد.</p>

      <h3 class="m360-section-title">پیشرفت مراحل</h3>
      <?php
        $completed = array_map('intval', is_array($m360StageTree['completed_stages'] ?? null) ? $m360StageTree['completed_stages'] : []);
        $locked = array_map('intval', is_array($m360StageTree['locked_stages'] ?? null) ? $m360StageTree['locked_stages'] : []);
        $stages = is_array($m360StageTree['all_stages'] ?? null) ? $m360StageTree['all_stages'] : (function_exists('m360_cst_stages') ? m360_cst_stages() : []);
      ?>
      <div class="m360-hall-stage-strip" aria-label="مراحل پرونده">
        <?php foreach ($stages as $number => $stage): ?>
          <?php
            $number = (int)$number;
            $label = is_array($stage) ? (string)($stage['label'] ?? '') : (string)$stage;
            $cls = 'm360-hall-stage-chip';
            if ($number === $stageNumber) {
                $cls .= ' is-current';
            } elseif (in_array($number, $completed, true)) {
                $cls .= ' is-done';
            } elseif (in_array($number, $locked, true)) {
                $cls .= ' is-locked';
            }
          ?>
          <span class="<?= m360_fulljob_h($cls) ?>">
            <?= m360_fulljob_h(m360_fulljob_to_persian_digits((string)$number)) ?> — <?= m360_fulljob_h($label) ?>
          </span>
        <?php endforeach; ?>
      </div>

      <h3 class="m360-section-title">رویدادهای زمانی</h3>
      <?php if (($timeline['events'] ?? []) === []): ?>
        <p class="m360-muted">رویداد زمانی معتبری برای نمایش یافت نشد.</p>
      <?php else: ?>
        <div class="m360-hall-table-wrap">
          <table class="m360-table">
            <thead><tr><th>رویداد</th><th>زمان</th><th>واحد / عامل</th><th>وضعیت</th></tr></thead>
            <tbody>
            <?php foreach ($timeline['events'] as $ev): ?>
              <tr>
                <td><?= m360_fulljob_h((string)$ev['title']) ?></td>
                <td><?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)$ev['at'])) ?></td>
                <td><?= m360_fulljob_h(trim((string)($ev['unit'] ?? '') . ((($ev['unit'] ?? '') !== '' && ($ev['actor'] ?? '') !== '') ? ' / ' : '') . (string)($ev['actor'] ?? ''))) ?></td>
                <td><?= m360_fulljob_h((string)($ev['status'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if (($timeline['missing'] ?? []) !== []): ?>
        <section class="m360-hall-card" style="margin-top:.85rem;">
          <h3>رویدادهای فاقد زمان ثبت‌شده</h3>
          <ul>
            <?php foreach ($timeline['missing'] as $m): ?>
              <li>
                <?= m360_fulljob_h((string)$m['title']) ?>:
                <span title="برای این رویداد زمان معتبر در سوابق فعلی ثبت نشده است.">ثبت نشده</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

    <?php elseif ($tab === 'quality'): ?>
      <h2 class="m360-section-title">کنترل کیفیت و ترخیص</h2>
      <?php if ($hasConsistencyWarning): ?>
        <p class="m360-alert m360-alert-error">ناهمخوانی وضعیت پرونده — قبل از اقدام QC/ترخیص وضعیت را بررسی کنید.</p>
      <?php endif; ?>
      <div class="m360-hall-grid2">
        <section class="m360-hall-card">
          <h3>بازبینی سالن / کنترل کیفیت</h3>
          <p><strong>وضعیت پرونده:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa($jobcardStatus)) ?></p>
          <p><strong>وضعیت فنی:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa($techStatus)) ?></p>
          <p><strong>وضعیت اجرا:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa($wxStatus)) ?></p>
          <p><strong>وضعیت کنترل کیفیت:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa($qcStatus !== '' ? $qcStatus : 'نامشخص')) ?></p>
          <p><strong>زمان ارسال به کنترل کیفیت:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($jobcard['ready_for_qc_at'] ?? ''))) ?></p>
          <p><strong>زمان شروع کنترل کیفیت:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($jobcard['qc_started_at'] ?? ''))) ?></p>
          <p><strong>زمان پایان کنترل کیفیت:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($jobcard['qc_completed_at'] ?? ''))) ?></p>
        </section>
        <section class="m360-hall-card">
          <h3>مالی / تحویل</h3>
          <p><strong>وضعیت فاکتور:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa((string)($jobcard['final_invoice_status'] ?? ''))) ?></p>
          <p><strong>وضعیت تسویه:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa((string)($jobcard['settlement_status'] ?? ''))) ?></p>
          <p><strong>آمادگی تحویل:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa((string)($jobcard['delivery_readiness_status'] ?? ''))) ?></p>
          <p><strong>زمان آمادگی تحویل:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($jobcard['delivery_ready_at'] ?? ''))) ?></p>
          <p><strong>وضعیت تحویل مشتری:</strong> <?= m360_fulljob_h(m360_fulljob_status_label_fa((string)($jobcard['customer_delivery_status'] ?? ''))) ?></p>
          <p><strong>امضای تحویل:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime((string)($jobcard['customer_delivery_signed_at'] ?? ''))) ?></p>
          <p><strong>زمان بسته‌شدن پرونده:</strong> <?= m360_fulljob_h(m360_fulljob_display_jalali_datetime($closedAt !== '' ? $closedAt : null)) ?></p>
        </section>
      </div>
      <div class="m360-hall-actions" style="margin-top:.75rem;">
        <?php if ($canQc && !$isClosedCase): ?>
          <a class="m360-btn m360-btn-primary" href="erp-work-execution-detail.php?jobcard_id=<?= $jobcardId ?>">اجرای کار / ارسال به کنترل کیفیت</a>
          <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-qc-board.php', $jobcardId, 'quality')) ?>">کارتابل کنترل کیفیت</a>
        <?php else: ?>
          <a class="m360-btn" href="<?= m360_fulljob_h(m360_hall_append_return_context('erp-qc-board.php', $jobcardId, 'quality')) ?>">کارتابل کنترل کیفیت</a>
          <span class="m360-muted">ارسال مستقیم تکنسین به کنترل کیفیت مجاز نیست؛ اقدام سالن فقط در وضعیت بازبینی مجاز است.</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php mirror_render_foot(); ?>
