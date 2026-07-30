<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/crm360-helper.php';

crm360_csrf_boot();
$actorInfo = crm360_actor();
$actor = $actorInfo['actor'];
$devMode = $actorInfo['dev_mode'];

$tabs = [
    'dashboard' => 'داشبورد',
    'customers' => 'مشتریان',
    'vehicles' => 'خودروها',
    'cases' => 'پرونده پذیرش',
    'documents' => 'مدارک',
    'cartable' => 'کارتابل',
    'satisfaction' => 'رضایت‌سنجی',
    'complaints' => 'شکایات',
    'club' => 'باشگاه',
    'reminders' => 'یادآوری',
    'returns' => 'بازگشت مشتری',
    'promotions' => 'پروموشن',
    'sms' => 'پیامک',
    'audit' => 'Audit',
];
$tab = preg_replace('/[^a-z_]/', '', (string)($_GET['tab'] ?? 'dashboard')) ?: 'dashboard';
if (!isset($tabs[$tab])) {
    $tab = 'dashboard';
}

$flash = $_SESSION['crm360_flash'] ?? null;
unset($_SESSION['crm360_flash']);

$dbOk = true;
$dbErr = '';
$conn = null;
try {
    $conn = crm360_db();
} catch (Throwable $e) {
    $dbOk = false;
    $dbErr = 'اتصال به moghare360_ERP برقرار نشد.';
}

$kpi = [
    'customers' => 0, 'vehicles' => 0, 'cases' => 0, 'cases_draft' => 0,
    'cases_open' => 0, 'cases_complete' => 0,
    'docs_total' => 0, 'docs_ok' => 0, 'docs_missing' => 0,
    'cartable_open' => 0, 'complaints_open' => 0, 'complaints_critical' => 0,
    'surveys_low' => 0, 'surveys_done' => 0, 'survey_avg' => 0,
    'reminders_due' => 0, 'returns_open' => 0, 'returns_progress' => 0,
    'promos_active' => 0, 'sms_draft' => 0, 'sms_ready' => 0,
    'vip_club' => 0, 'online_requests' => 0, 'avg_completion' => 0,
];
$rx = [
    'online_new' => 0,
    'online_pending' => 0,
    'walkin_today' => 0,
    'case_incomplete' => 0,
    'ready_contract' => 0,
    'contract_unsigned' => 0,
    'contract_signed' => 0,
    'ready_jobcard' => 0,
    'closed_done' => 0,
];
$customers = $vehicles = $cases = $documents = $cartable = $surveys = $complaints = $clubs = $reminders = $returns = $promotions = $assignments = $campaigns = $recipients = $audits = $onlineRequests = [];

if ($dbOk && $conn) {
    $kpi['customers'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_customer_profiles') ?? 0);
    $kpi['vehicles'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_vehicle_profiles') ?? 0);
    $kpi['cases'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_reception_cases') ?? 0);
    $kpi['cases_draft'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status IN ('DRAFT','PROFILE_INCOMPLETE','IN_PROGRESS')") ?? 0);
    $kpi['cases_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status NOT IN ('CLOSED','CANCELLED','DELIVERED')") ?? 0);
    $kpi['cases_complete'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE profile_completion_percent >= 100 OR case_status IN ('READY_FOR_CONTRACT','CONTRACT_PENDING','CONTRACT_SIGNED','IN_SERVICE','READY_FOR_DELIVERY','DELIVERED','CLOSED')") ?? 0);
    $kpi['docs_total'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_case_documents') ?? 0);
    $kpi['docs_ok'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_case_documents WHERE document_status IN ('UPLOADED','VERIFIED','SIGNED')") ?? 0);
    $kpi['docs_missing'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_case_documents WHERE document_status='MISSING'") ?? 0);
    $kpi['cartable_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_customer_cartable WHERE item_status IN ('OPEN','IN_PROGRESS','OVERDUE')") ?? 0);
    $kpi['complaints_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_complaints WHERE complaint_status IN ('OPEN','UNDER_REVIEW','CORRECTION_REQUIRED')") ?? 0);
    $kpi['complaints_critical'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_complaints WHERE severity='CRITICAL' AND complaint_status NOT IN ('CLOSED','CANCELLED','REJECTED')") ?? 0);
    $kpi['surveys_low'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_satisfaction_surveys WHERE overall_score<=3 AND overall_score>0") ?? 0);
    $kpi['surveys_done'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_satisfaction_surveys WHERE survey_status IN ('COMPLETED','NEEDS_FOLLOWUP','CLOSED') AND overall_score>0") ?? 0);
    $kpi['survey_avg'] = (float)(crm360_scalar($conn, "SELECT ISNULL(AVG(CAST(overall_score AS FLOAT)),0) FROM dbo.crm360_satisfaction_surveys WHERE overall_score>0") ?? 0);
    $kpi['reminders_due'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_service_reminders WHERE reminder_status IN ('DUE_SOON','DUE','OVERDUE','SCHEDULED','NEEDS_FOLLOWUP')") ?? 0);
    $kpi['returns_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_return_pipeline WHERE result_status='OPEN' OR return_stage NOT IN ('RETURNED','LOST','CLOSED')") ?? 0);
    $kpi['returns_progress'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_return_pipeline WHERE return_stage IN ('BOOKED','RETURNED','OFFER_SENT','CONTACTED')") ?? 0);
    $kpi['promos_active'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_promotions WHERE promotion_status='ACTIVE'") ?? 0);
    $kpi['sms_draft'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_sms_campaigns WHERE campaign_status='DRAFT'") ?? 0);
    $kpi['sms_ready'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_sms_campaigns WHERE campaign_status IN ('READY_FOR_REVIEW','APPROVED_FOR_EXPORT','EXPORTED')") ?? 0);
    $kpi['vip_club'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_customer_club WHERE tier_code IN ('GOLD','PLATINUM','VIP')") ?? 0);
    $kpi['avg_completion'] = (int)(crm360_scalar($conn, 'SELECT ISNULL(AVG(profile_completion_percent),0) FROM dbo.crm360_reception_cases') ?? 0);
    $onlineRequests = crm360_online_requests($conn, 30);
    $kpi['online_requests'] = count($onlineRequests);

    if (crm360_table_exists($conn, 'erp_customer_online_requests')) {
        $rx['online_new'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_customer_online_requests WHERE request_status=N'NEW'") ?? 0);
        $rx['online_pending'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_customer_online_requests WHERE request_status IN (N'NEW',N'PENDING',N'UNDER_REVIEW')") ?? 0);
        $rx['walkin_today'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_customer_online_requests WHERE CONVERT(date, created_at)=CONVERT(date, SYSUTCDATETIME()) AND request_payload_json LIKE N'%STAFF_ASSISTED_WALKIN%'") ?? 0);
        $rx['ready_jobcard'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_customer_online_requests WHERE request_status IN (N'ACCEPTED',N'UNDER_REVIEW') AND (converted_jobcard_id IS NULL OR converted_jobcard_id=0)") ?? 0);
        $rx['closed_done'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_customer_online_requests WHERE request_status IN (N'CONVERTED_TO_JOBCARD',N'REJECTED')") ?? 0);
    }
    $rx['case_incomplete'] = (int)$kpi['cases_draft'];
    $rx['ready_contract'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status IN (N'READY_FOR_CONTRACT',N'CONTRACT_PENDING')") ?? 0);
    if (crm360_table_exists($conn, 'erp_intake_contracts')) {
        $rx['contract_unsigned'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_intake_contracts WHERE contract_status IN (N'DRAFT',N'SENT',N'VIEWED')") ?? 0);
        $rx['contract_signed'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_intake_contracts WHERE contract_status IN (N'SIGNED',N'OVERRIDDEN')") ?? 0);
    }
    if ($rx['closed_done'] === 0) {
        $rx['closed_done'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status IN (N'DELIVERED',N'CLOSED')") ?? 0);
    }

    $customers = crm360_rows($conn, 'SELECT TOP 100 * FROM dbo.crm360_customer_profiles ORDER BY customer_profile_id DESC');
    $vehicles = crm360_rows($conn, 'SELECT TOP 100 v.*, c.full_name FROM dbo.crm360_vehicle_profiles v INNER JOIN dbo.crm360_customer_profiles c ON c.customer_profile_id=v.customer_profile_id ORDER BY v.vehicle_profile_id DESC');
    $cases = crm360_rows($conn, 'SELECT TOP 100 c.*, cu.full_name, v.brand, v.model FROM dbo.crm360_reception_cases c INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=c.customer_profile_id INNER JOIN dbo.crm360_vehicle_profiles v ON v.vehicle_profile_id=c.vehicle_profile_id ORDER BY c.case_id DESC');
    $documents = crm360_rows($conn, 'SELECT TOP 100 d.*, c.case_code FROM dbo.crm360_case_documents d LEFT JOIN dbo.crm360_reception_cases c ON c.case_id=d.case_id ORDER BY d.document_id DESC');
    $cartable = crm360_rows($conn, 'SELECT TOP 100 cb.*, cu.full_name FROM dbo.crm360_customer_cartable cb INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=cb.customer_profile_id ORDER BY cb.cartable_id DESC');
    $surveys = crm360_rows($conn, 'SELECT TOP 100 s.*, cu.full_name FROM dbo.crm360_satisfaction_surveys s INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=s.customer_profile_id ORDER BY s.survey_id DESC');
    $complaints = crm360_rows($conn, 'SELECT TOP 100 cp.*, cu.full_name FROM dbo.crm360_complaints cp INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=cp.customer_profile_id ORDER BY cp.complaint_id DESC');
    $clubs = crm360_rows($conn, 'SELECT cl.*, cu.full_name, cu.mobile FROM dbo.crm360_customer_club cl INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=cl.customer_profile_id ORDER BY cl.club_id DESC');
    $reminders = crm360_rows($conn, 'SELECT TOP 100 r.*, cu.full_name, v.brand, v.model FROM dbo.crm360_service_reminders r INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=r.customer_profile_id INNER JOIN dbo.crm360_vehicle_profiles v ON v.vehicle_profile_id=r.vehicle_profile_id ORDER BY r.reminder_id DESC');
    $returns = crm360_rows($conn, 'SELECT TOP 100 ret.*, cu.full_name FROM dbo.crm360_return_pipeline ret INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=ret.customer_profile_id ORDER BY ret.return_id DESC');
    $promotions = crm360_rows($conn, 'SELECT TOP 100 * FROM dbo.crm360_promotions ORDER BY promotion_id DESC');
    $assignments = crm360_rows($conn, 'SELECT TOP 100 pa.*, p.title, cu.full_name FROM dbo.crm360_promotion_assignments pa INNER JOIN dbo.crm360_promotions p ON p.promotion_id=pa.promotion_id INNER JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=pa.customer_profile_id ORDER BY pa.assignment_id DESC');
    $campaigns = crm360_rows($conn, 'SELECT TOP 100 * FROM dbo.crm360_sms_campaigns ORDER BY campaign_id DESC');
    $recipients = crm360_rows($conn, 'SELECT TOP 100 r.*, c.title AS campaign_title FROM dbo.crm360_sms_campaign_recipients r INNER JOIN dbo.crm360_sms_campaigns c ON c.campaign_id=r.campaign_id ORDER BY r.recipient_id DESC');
    $audits = crm360_rows($conn, 'SELECT TOP 100 * FROM dbo.crm360_audit_log ORDER BY audit_id DESC');
}

$custOpts = $vehOpts = $caseOpts = $promoOpts = '';
if ($dbOk && $conn) {
    $custOpts = crm360_customer_options($conn);
    $vehOpts = crm360_vehicle_options($conn);
    $caseOpts = crm360_case_options($conn);
    $promoOpts = crm360_promotion_options($conn);
}

$caseDenom = (int)$kpi['cases_open'];
$casePct = $caseDenom > 0 ? (int)round(100 * $kpi['cases_complete'] / $caseDenom) : 0;
$docPct = $kpi['docs_total'] > 0 ? (int)round(100 * $kpi['docs_ok'] / $kpi['docs_total']) : 0;
$satPct = $kpi['survey_avg'] > 0 ? (int)round(($kpi['survey_avg'] / 5) * 100) : 0;
$returnPct = $kpi['returns_open'] > 0 ? (int)round(100 * $kpi['returns_progress'] / $kpi['returns_open']) : 0;
$hubOpenCartable = [];
foreach ($cartable as $cbRow) {
    $st = (string)($cbRow['item_status'] ?? '');
    if (in_array($st, ['OPEN', 'IN_PROGRESS', 'OVERDUE'], true)) {
        $hubOpenCartable[] = $cbRow;
        if (count($hubOpenCartable) >= 5) {
            break;
        }
    }
}
$hubAudits = array_slice($audits, 0, 5);
$receptionCards = [
    ['title' => 'درخواست‌های آنلاین', 'href' => 'erp-reception-online-requests.php', 'count' => (int)$rx['online_new'], 'unit' => 'جدید'],
    ['title' => 'پذیرش حضوری', 'href' => 'erp-reception-walkin-create.php', 'count' => (int)$rx['walkin_today'], 'unit' => 'امروز'],
    ['title' => 'تکمیل پرونده پذیرش', 'href' => 'erp-reception-online-requests.php', 'count' => (int)$rx['online_pending'], 'unit' => 'در انتظار'],
    ['title' => 'پرونده‌های در جریان', 'href' => 'erp-reception-jobcards.php', 'count' => (int)$rx['ready_jobcard'], 'unit' => 'آماده سالن'],
    ['title' => 'قرارداد و مدارک', 'href' => 'erp-intake-contracts.php', 'count' => (int)$rx['contract_unsigned'], 'unit' => 'امضانشده'],
    ['title' => 'کارتابل مشتری', 'href' => 'erp-crm-cartable.php', 'count' => (int)$kpi['cartable_open'], 'unit' => 'باز'],
];
$hubProfile = [
    ['پروفایل مشتری', 'erp-crm-customer-profile.php', (int)$kpi['customers']],
    ['پروفایل خودرو', 'erp-crm-vehicle-profile.php', (int)$kpi['vehicles']],
    ['تکمیل پرونده پذیرش', 'erp-crm-case.php', (int)$kpi['cases_draft']],
    ['قرارداد و مدارک', 'erp-crm-documents.php', (int)$kpi['docs_missing']],
];
$hubFollow = [
    ['کارتابل مشتری', 'erp-crm-cartable.php', (int)$kpi['cartable_open']],
    ['رضایت‌سنجی', 'erp-crm-satisfaction.php', (int)$kpi['surveys_done']],
    ['شکایت و اصلاحیه', 'erp-crm-complaints.php', (int)$kpi['complaints_open']],
    ['یادآوری سرویس‌های دوره‌ای', 'erp-crm-reminders.php', (int)$kpi['reminders_due']],
];
$hubLoyalty = [
    ['باشگاه مشتریان', 'erp-crm-club.php', (int)$kpi['vip_club']],
    ['بازگشت مشتری', 'erp-crm-return.php', (int)$kpi['returns_open']],
    ['پروموشن', 'erp-crm-promotions.php', (int)$kpi['promos_active']],
    ['کمپین پیامکی', 'erp-crm-sms-campaigns.php', (int)$kpi['sms_ready']],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مرکز ارتباط با مشتریان — MOGHARE360</title>
<link rel="stylesheet" href="assets/css/m360-suite-theme.css">
<link rel="stylesheet" href="assets/css/m360-crm.css">
</head>
<body class="c360-body">
<div class="c360-wrap">
  <div class="c360-crumb"><a href="personnel.html">پرسنل</a> / <a href="personnel.html">ارتباط با مشتریان</a> / داشبورد</div>
  <header class="c360-head">
    <div>
      <h1>مرکز ارتباط با مشتریان</h1>
      <p>پذیرش، پرونده، پیگیری، رضایت، وفاداری و کمپین</p>
      <div class="c360-head-meta">
        <a class="c360-btn" href="personnel.html">بازگشت به صفحه پرسنل</a>
        <span class="c360-badge">moghare360_ERP</span>
      </div>
    </div>
    <div>
      <?php if ($devMode): ?><span class="c360-badge">حالت توسعه محلی</span><?php endif; ?>
      <div class="c360-muted" style="margin-top:.4rem;font-size:.75rem">actor: <?= crm360_h($actor) ?></div>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="c360-flash <?= crm360_h($flash['type']) ?>"><?= crm360_h($flash['msg']) ?></div>
  <?php endif; ?>
  <?php if (!$dbOk): ?>
    <div class="c360-flash err"><?= crm360_h($dbErr) ?></div>
  <?php endif; ?>

  <nav class="c360-tabs">
    <?php foreach ($tabs as $k => $label): ?>
      <a href="?tab=<?= crm360_h($k) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= crm360_h($label) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'dashboard'): ?>
  <section class="c360-panel c360-hub-section">
    <div class="c360-section-head">
      <div>
        <h2>پرونده‌های پذیرش</h2>
        <p class="c360-section-sub">ورود سریع به مسیرهای اصلی پذیرش</p>
      </div>
    </div>
    <section class="c360-status-grid c360-rx-grid">
      <div class="c360-status-card"><span class="c360-light <?= $rx['online_new'] ? 'warn' : 'idle' ?>"></span><span>درخواست آنلاین جدید</span><strong><?= (int)$rx['online_new'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['online_pending'] ? 'warn' : 'ok' ?>"></span><span>آنلاین در انتظار تکمیل</span><strong><?= (int)$rx['online_pending'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['walkin_today'] ? 'ok' : 'idle' ?>"></span><span>پذیرش حضوری امروز</span><strong><?= (int)$rx['walkin_today'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['case_incomplete'] ? 'warn' : 'ok' ?>"></span><span>پرونده پذیرش ناقص</span><strong><?= (int)$rx['case_incomplete'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['ready_contract'] ? 'warn' : 'idle' ?>"></span><span>آماده قرارداد</span><strong><?= (int)$rx['ready_contract'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['contract_unsigned'] ? 'warn' : 'ok' ?>"></span><span>قرارداد امضانشده</span><strong><?= (int)$rx['contract_unsigned'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['contract_signed'] ? 'ok' : 'idle' ?>"></span><span>قرارداد امضاشده</span><strong><?= (int)$rx['contract_signed'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light <?= $rx['ready_jobcard'] ? 'warn' : 'idle' ?>"></span><span>آماده انتقال به سالن</span><strong><?= (int)$rx['ready_jobcard'] ?></strong></div>
      <div class="c360-status-card"><span class="c360-light idle"></span><span>تحویل‌شده / بسته‌شده</span><strong><?= (int)$rx['closed_done'] ?></strong></div>
    </section>
    <div class="c360-hub-grid">
      <?php foreach ($receptionCards as $card): ?>
        <a class="c360-hub-card c360-hub-card--rx" href="<?= crm360_h($card['href']) ?>">
          <strong><?= crm360_h($card['title']) ?></strong>
          <em class="c360-hub-meta"><?= (int)$card['count'] ?> <?= crm360_h($card['unit']) ?></em>
          <span class="c360-hub-go">ورود</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="c360-status-grid">
    <div class="c360-status-card">
      <span class="c360-light ok"></span>
      <span>تکمیل پرونده‌ها</span>
      <strong><?= (int)$kpi['cases_complete'] ?> / <?= (int)$caseDenom ?></strong>
    </div>
    <div class="c360-status-card">
      <span class="c360-light <?= $docPct >= 80 ? 'ok' : ($kpi['docs_total'] ? 'warn' : 'idle') ?>"></span>
      <span>مدارک کامل</span>
      <strong><?= (int)$kpi['docs_ok'] ?> / <?= (int)$kpi['docs_total'] ?></strong>
    </div>
    <div class="c360-status-card">
      <span class="c360-light <?= $kpi['survey_avg'] >= 4 ? 'ok' : ($kpi['survey_avg'] > 0 ? 'warn' : 'idle') ?>"></span>
      <span>رضایت مشتری</span>
      <strong><?= number_format($kpi['survey_avg'], 1) ?></strong>
    </div>
    <div class="c360-status-card <?= $kpi['complaints_open'] > 0 ? 'is-alert' : '' ?>">
      <span class="c360-light <?= $kpi['complaints_critical'] > 0 ? 'danger' : ($kpi['complaints_open'] > 0 ? 'warn' : 'ok') ?>"></span>
      <span>شکایات باز</span>
      <strong><?= (int)$kpi['complaints_open'] ?></strong>
    </div>
    <div class="c360-status-card">
      <span class="c360-light <?= $kpi['reminders_due'] > 0 ? 'warn' : 'ok' ?>"></span>
      <span>یادآوری‌های سررسید</span>
      <strong><?= (int)$kpi['reminders_due'] ?></strong>
    </div>
    <div class="c360-status-card">
      <span class="c360-light <?= $kpi['returns_open'] > 0 ? 'warn' : 'idle' ?>"></span>
      <span>بازگشت مشتری</span>
      <strong><?= (int)$kpi['returns_progress'] ?> / <?= (int)$kpi['returns_open'] ?></strong>
    </div>
    <div class="c360-status-card">
      <span class="c360-light <?= $kpi['sms_ready'] > 0 ? 'ok' : 'idle' ?>"></span>
      <span>کمپین آماده</span>
      <strong><?= (int)$kpi['sms_ready'] ?></strong>
    </div>
    <div class="c360-status-card">
      <span class="c360-light <?= $kpi['vip_club'] > 0 ? 'ok' : 'idle' ?>"></span>
      <span>مشتریان VIP</span>
      <strong><?= (int)$kpi['vip_club'] ?></strong>
    </div>
  </section>

  <div class="c360-gauge-row">
    <?= crm360_gauge($casePct, 'تکمیل پرونده‌ها') ?>
    <?= crm360_gauge($docPct, 'مدارک کامل', '#66bb6a') ?>
    <?= crm360_gauge($satPct, 'رضایت مشتری', '#3ecf8e') ?>
    <?= crm360_gauge($returnPct, 'بازگشت مشتری', '#e8b84a') ?>
  </div>

  <section class="c360-panel c360-hub-section">
    <div class="c360-section-head"><div><h2>پروفایل و پرونده</h2></div></div>
    <div class="c360-hub-grid">
      <?php foreach ($hubProfile as [$title, $href, $count]): ?>
        <a class="c360-hub-card" href="<?= crm360_h($href) ?>">
          <strong><?= crm360_h($title) ?></strong>
          <em class="c360-hub-meta"><?= (int)$count ?></em>
          <span class="c360-hub-go">ورود</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="c360-panel c360-hub-section">
    <div class="c360-section-head"><div><h2>تجربه و پیگیری</h2></div></div>
    <div class="c360-hub-grid">
      <?php foreach ($hubFollow as [$title, $href, $count]): ?>
        <a class="c360-hub-card" href="<?= crm360_h($href) ?>">
          <strong><?= crm360_h($title) ?></strong>
          <em class="c360-hub-meta"><?= (int)$count ?></em>
          <span class="c360-hub-go">ورود</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="c360-panel c360-hub-section">
    <div class="c360-section-head"><div><h2>وفاداری و کمپین</h2></div></div>
    <div class="c360-hub-grid">
      <?php foreach ($hubLoyalty as [$title, $href, $count]): ?>
        <a class="c360-hub-card" href="<?= crm360_h($href) ?>">
          <strong><?= crm360_h($title) ?></strong>
          <em class="c360-hub-meta"><?= (int)$count ?></em>
          <span class="c360-hub-go">ورود</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>کارتابل باز</h2>
      <?php if (!$hubOpenCartable): ?>
        <p class="c360-muted">موردی باز نیست.</p>
      <?php else: ?>
        <div class="c360-table-wrap">
          <table class="c360-table">
            <thead><tr><th>عنوان</th><th>مشتری</th><th>وضعیت</th></tr></thead>
            <tbody>
            <?php foreach ($hubOpenCartable as $cb): ?>
              <tr>
                <td><?= crm360_h((string)($cb['item_title'] ?? '')) ?></td>
                <td><?= crm360_h((string)($cb['full_name'] ?? '')) ?></td>
                <td><span class="c360-status"><?= crm360_h((string)($cb['item_status'] ?? '')) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
    <section class="c360-panel">
      <h2>آخرین رویدادهای Audit</h2>
      <?php if (!$hubAudits): ?>
        <p class="c360-muted">رویدادی ثبت نشده است.</p>
      <?php else: ?>
        <div class="c360-table-wrap">
          <table class="c360-table">
            <thead><tr><th>رویداد</th><th>موجودیت</th><th>زمان</th></tr></thead>
            <tbody>
            <?php foreach ($hubAudits as $au): ?>
              <tr>
                <td><?= crm360_h((string)($au['action_code'] ?? '')) ?></td>
                <td><?= crm360_h((string)($au['entity_name'] ?? '')) ?> #<?= crm360_h((string)($au['entity_id'] ?? '')) ?></td>
                <td><?= crm360_h((string)($au['event_time'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <section class="c360-panel">
    <div class="c360-section-head">
      <div><h2>درخواست‌های آنلاین</h2></div>
      <a class="c360-btn" href="erp-reception-online-requests.php">مشاهده همه</a>
    </div>
    <?php if (!$onlineRequests): ?>
      <p class="c360-muted">درخواست آنلاینی ثبت نشده است.</p>
    <?php else: ?>
    <div class="c360-table-wrap">
      <table class="c360-table">
        <thead><tr><th>شناسه</th><th>موبایل</th><th>پلاک</th><th>وضعیت</th><th>تاریخ</th><th>اقدام</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($onlineRequests, 0, 5) as $or): ?>
          <tr>
            <td><?= crm360_h((string)$or['online_request_id']) ?></td>
            <td><?= crm360_h((string)($or['mobile'] ?? '')) ?></td>
            <td><?= crm360_h((string)($or['vehicle_plate'] ?? '')) ?></td>
            <td><span class="c360-status"><?= crm360_h((string)($or['request_status'] ?? '')) ?></span></td>
            <td><?= crm360_h((string)($or['created_at'] ?? '')) ?></td>
            <td>
              <a class="c360-btn" href="erp-reception-online-request-detail.php?request_id=<?= (int)$or['online_request_id'] ?>">جزئیات</a>
              <a class="c360-btn" href="erp-reception-intake-file.php?online_request_id=<?= (int)$or['online_request_id'] ?>">تکمیل پرونده</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

<?php elseif ($tab === 'customers'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ثبت مشتری جدید</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_customer">
        <input type="hidden" name="return_tab" value="customers">
        <label>نام کامل<input name="full_name" required></label>
        <label>موبایل<input name="mobile" inputmode="tel"></label>
        <label>کد ملی<input name="national_id"></label>
        <label>نوع<select name="customer_type"><option value="PERSON">حقیقی</option><option value="COMPANY">حقوقی</option></select></label>
        <label><input type="checkbox" name="consent_sms" value="1"> رضایت SMS</label>
        <label><input type="checkbox" name="consent_marketing" value="1"> رضایت بازاریابی</label>
        <label>کانال منبع<input name="source_channel"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <button type="submit" class="c360-btn primary">ثبت مشتری</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>فهرست مشتریان</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>شناسه</th><th>نام</th><th>موبایل</th><th>وضعیت</th><th>VIP</th></tr></thead>
          <tbody>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td><?= (int)$c['customer_profile_id'] ?></td>
              <td><?= crm360_h((string)$c['full_name']) ?></td>
              <td><?= crm360_h((string)($c['mobile'] ?? '')) ?></td>
              <td><span class="c360-status"><?= crm360_h((string)$c['customer_status']) ?></span></td>
              <td><?= crm360_h((string)$c['vip_level']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'vehicles'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ثبت خودرو</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_vehicle">
        <input type="hidden" name="return_tab" value="vehicles">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>برند<select name="brand" required><?= crm360_brand_options() ?></select></label>
        <label>مدل<input name="model" required></label>
        <label>پلاک<input name="plate_no"></label>
        <label>VIN<input name="vin"></label>
        <label>سال<input name="model_year" type="number" min="1990" max="2030"></label>
        <label>رنگ<input name="color"></label>
        <label>کارکرد (km)<input name="mileage" type="number" min="0"></label>
        <button type="submit" class="c360-btn primary">ثبت خودرو</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>فهرست خودروها</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>شناسه</th><th>مشتری</th><th>برند/مدل</th><th>پلاک</th><th>VIN</th></tr></thead>
          <tbody>
          <?php foreach ($vehicles as $v): ?>
            <tr>
              <td><?= (int)$v['vehicle_profile_id'] ?></td>
              <td><?= crm360_h((string)$v['full_name']) ?></td>
              <td><?= crm360_h((string)$v['brand']) ?> <?= crm360_h((string)$v['model']) ?></td>
              <td><?= crm360_h((string)($v['plate_no'] ?? '')) ?></td>
              <td><?= crm360_h((string)($v['vin'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'cases'): ?>
  <?php $prefReq = (int)($_GET['req'] ?? 0); ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ایجاد پرونده پذیرش</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_case">
        <input type="hidden" name="return_tab" value="cases">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>خودرو<select name="vehicle_profile_id" required><?= $vehOpts ?></select></label>
        <label>شناسه درخواست آنلاین<input name="existing_request_id" type="number" value="<?= $prefReq > 0 ? $prefReq : '' ?>" placeholder="اختیاری"></label>
        <label>نوع پرونده<select name="case_type"><option value="WALKIN">حضوری</option><option value="ONLINE">آنلاین</option><option value="RETURNING">مراجع مجدد</option></select></label>
        <label>نوع خدمت<input name="service_type" required placeholder="مثلاً سرویس دوره‌ای"></label>
        <label>مسئول پرونده<input name="responsible_staff" required></label>
        <label>پذیرش‌کننده<input name="assigned_staff"></label>
        <label>منبع<input name="reception_source"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <button type="submit" class="c360-btn primary">ایجاد پرونده + چک‌لیست مدارک</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>پرونده‌های پذیرش</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>کد</th><th>مشتری</th><th>خودرو</th><th>خدمت</th><th>تکمیل</th><th>وضعیت</th><th>ناقص</th><th>به‌روز</th></tr></thead>
          <tbody>
          <?php foreach ($cases as $c):
              $calc = $dbOk ? crm360_calc_case_completion($conn, (int)$c['case_id']) : ['percent' => 0, 'missing' => []]; ?>
            <tr>
              <td><?= crm360_h((string)$c['case_code']) ?></td>
              <td><?= crm360_h((string)$c['full_name']) ?></td>
              <td><?= crm360_h((string)$c['brand']) ?> <?= crm360_h((string)$c['model']) ?></td>
              <td><?= crm360_h((string)($c['service_type'] ?? '')) ?></td>
              <td><strong><?= (int)$calc['percent'] ?>%</strong></td>
              <td><span class="c360-status"><?= crm360_h((string)$c['case_status']) ?></span></td>
              <td><?php if ($calc['missing']): ?><ul class="c360-missing-list"><?php foreach ($calc['missing'] as $m): ?><li><?= crm360_h($m) ?></li><?php endforeach; ?></ul><?php else: ?>—<?php endif; ?></td>
              <td>
                <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="update_case_fields">
                  <input type="hidden" name="return_tab" value="cases">
                  <input type="hidden" name="case_id" value="<?= (int)$c['case_id'] ?>">
                  <input type="hidden" name="case_type" value="<?= crm360_h((string)$c['case_type']) ?>">
                  <input name="service_type" value="<?= crm360_h((string)($c['service_type'] ?? '')) ?>" placeholder="خدمت" style="width:90px">
                  <input name="responsible_staff" value="<?= crm360_h((string)($c['responsible_staff'] ?? '')) ?>" placeholder="مسئول" style="width:80px">
                  <select name="case_status"><option value="DRAFT">DRAFT</option><option value="IN_PROGRESS" <?= ($c['case_status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>IN_PROGRESS</option><option value="READY" <?= ($c['case_status'] ?? '') === 'READY' ? 'selected' : '' ?>>READY</option><option value="CLOSED" <?= ($c['case_status'] ?? '') === 'CLOSED' ? 'selected' : '' ?>>CLOSED</option></select>
                  <button type="submit" class="c360-btn">ذخیره</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'documents'): ?>
  <section class="c360-panel">
    <h2>مدارک پرونده</h2>
    <p class="c360-note">DOCUMENT_VAULT_INTEGRATION_PENDING — مرجع فایل فعلاً در file_ref_text ذخیره می‌شود.</p>
    <form class="c360-form" method="post" action="erp-crm-action.php" style="max-width:420px;margin-bottom:1rem">
      <?= crm360_csrf_field() ?>
      <input type="hidden" name="action" value="generate_docs_checklist">
      <input type="hidden" name="return_tab" value="documents">
      <label>پرونده<select name="case_id" required><?= $caseOpts ?></select></label>
      <button type="submit" class="c360-btn">ایجاد/تکمیل چک‌لیست (۵ مدرک)</button>
    </form>
    <div class="c360-table-wrap">
      <table class="c360-table">
        <thead><tr><th>پرونده</th><th>نوع</th><th>عنوان</th><th>وضعیت</th><th>مرجع فایل</th><th>به‌روزرسانی</th></tr></thead>
        <tbody>
        <?php foreach ($documents as $d): ?>
          <tr>
            <td><?= crm360_h((string)($d['case_code'] ?? $d['case_id'])) ?></td>
            <td><?= crm360_h((string)$d['document_type']) ?></td>
            <td><?= crm360_h((string)$d['document_title']) ?></td>
            <td><span class="c360-status <?= ($d['document_status'] ?? '') === 'MISSING' ? 'warn' : '' ?>"><?= crm360_h((string)$d['document_status']) ?></span></td>
            <td><?= crm360_h((string)($d['file_ref_text'] ?? '')) ?></td>
            <td>
              <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                <?= crm360_csrf_field() ?>
                <input type="hidden" name="action" value="update_document_status">
                <input type="hidden" name="return_tab" value="documents">
                <input type="hidden" name="document_id" value="<?= (int)$d['document_id'] ?>">
                <select name="document_status">
                  <option value="MISSING">MISSING</option>
                  <option value="UPLOADED">UPLOADED</option>
                  <option value="VERIFIED">VERIFIED</option>
                  <option value="REJECTED">REJECTED</option>
                </select>
                <input name="file_ref_text" placeholder="file_ref" value="<?= crm360_h((string)($d['file_ref_text'] ?? '')) ?>">
                <button type="submit" class="c360-btn">ثبت</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

<?php elseif ($tab === 'cartable'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ایجاد آیتم کارتابل</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_cartable">
        <input type="hidden" name="return_tab" value="cartable">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>عنوان<input name="item_title" required></label>
        <label>نوع<select name="item_type"><option value="FOLLOWUP">پیگیری</option><option value="DOCUMENT">مدرک</option><option value="CALLBACK">تماس</option></select></label>
        <label>اولویت<select name="priority_code"><option value="NORMAL">عادی</option><option value="HIGH">بالا</option><option value="LOW">پایین</option></select></label>
        <label>سررسید<input name="due_date" type="date"></label>
        <label>مسئول<input name="assigned_to"></label>
        <button type="submit" class="c360-btn primary">ایجاد</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>کارتابل مشتری</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>مشتری</th><th>عنوان</th><th>وضعیت</th><th>اولویت</th><th>سررسید</th><th>بستن</th></tr></thead>
          <tbody>
          <?php foreach ($cartable as $cb): ?>
            <tr>
              <td><?= crm360_h((string)$cb['full_name']) ?></td>
              <td><?= crm360_h((string)$cb['item_title']) ?></td>
              <td><span class="c360-status"><?= crm360_h((string)$cb['item_status']) ?></span></td>
              <td><?= crm360_h((string)$cb['priority_code']) ?></td>
              <td><?= crm360_h((string)($cb['due_date'] ?? '')) ?></td>
              <td><?php if (($cb['item_status'] ?? '') === 'OPEN'): ?>
                <form method="post" action="erp-crm-action.php" class="c360-inline-form">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="cartable_done">
                  <input type="hidden" name="return_tab" value="cartable">
                  <input type="hidden" name="cartable_id" value="<?= (int)$cb['cartable_id'] ?>">
                  <button type="submit" class="c360-btn">انجام شد</button>
                </form>
              <?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'satisfaction'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ایجاد نظرسنجی</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_survey">
        <input type="hidden" name="return_tab" value="satisfaction">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>پرونده<select name="case_id"><option value="">—</option><?= $caseOpts ?></select></label>
        <button type="submit" class="c360-btn">پیش‌نویس</button>
      </form>
      <h3 style="margin-top:1rem">ثبت امتیاز</h3>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="submit_survey">
        <input type="hidden" name="return_tab" value="satisfaction">
        <label>شناسه نظرسنجی<input name="survey_id" type="number" required></label>
        <label>امتیاز کلی (۱–۱۰)<input name="overall_score" type="number" min="1" max="10" required></label>
        <label>NPS<input name="nps_score" type="number" min="0" max="10"></label>
        <label>پذیرش<input name="reception_score" type="number" min="1" max="10"></label>
        <label>فنی<input name="technical_score" type="number" min="1" max="10"></label>
        <label>نظر<textarea name="comment" rows="2"></textarea></label>
        <label><input type="checkbox" name="create_complaint" value="1"> ایجاد شکایت در صورت امتیاز ≤۳</label>
        <button type="submit" class="c360-btn primary">ثبت نظرسنجی</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>نظرسنجی‌ها</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>شناسه</th><th>مشتری</th><th>امتیاز</th><th>وضعیت</th></tr></thead>
          <tbody>
          <?php foreach ($surveys as $s): ?>
            <tr>
              <td><?= (int)$s['survey_id'] ?></td>
              <td><?= crm360_h((string)$s['full_name']) ?></td>
              <td><?= (int)$s['overall_score'] ?></td>
              <td><span class="c360-status <?= ($s['survey_status'] ?? '') === 'NEEDS_FOLLOWUP' ? 'warn' : '' ?>"><?= crm360_h((string)$s['survey_status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'complaints'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ثبت شکایت</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_complaint">
        <input type="hidden" name="return_tab" value="complaints">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>عنوان<input name="title" required></label>
        <label>شرح<textarea name="description" required rows="3"></textarea></label>
        <label>نوع<select name="complaint_type"><option value="SERVICE">خدمات</option><option value="PRICE">قیمت</option><option value="DELAY">تأخیر</option></select></label>
        <label>شدت<select name="severity"><option value="LOW">کم</option><option value="MEDIUM" selected>متوسط</option><option value="HIGH">بالا</option></select></label>
        <button type="submit" class="c360-btn primary">ثبت</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>شکایات</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>کد</th><th>مشتری</th><th>عنوان</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
          <tbody>
          <?php foreach ($complaints as $cp): ?>
            <tr>
              <td><?= crm360_h((string)$cp['complaint_code']) ?></td>
              <td><?= crm360_h((string)$cp['full_name']) ?></td>
              <td><?= crm360_h((string)$cp['title']) ?></td>
              <td><span class="c360-status"><?= crm360_h((string)$cp['complaint_status']) ?></span></td>
              <td>
                <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="update_complaint_status">
                  <input type="hidden" name="return_tab" value="complaints">
                  <input type="hidden" name="complaint_id" value="<?= (int)$cp['complaint_id'] ?>">
                  <select name="complaint_status"><option value="OPEN">OPEN</option><option value="IN_PROGRESS">IN_PROGRESS</option><option value="CLOSED">CLOSED</option></select>
                  <input name="correction_action" placeholder="اقدام اصلاحی (برای CLOSED)" style="width:140px">
                  <button type="submit" class="c360-btn">ثبت</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'club'): ?>
  <section class="c360-panel">
    <h2>باشگاه مشتریان</h2>
    <div class="c360-table-wrap">
      <table class="c360-table">
        <thead><tr><th>مشتری</th><th>سطح</th><th>امتیاز</th><th>بازدید</th><th>ریسک ریزش</th><th>به‌روز</th></tr></thead>
        <tbody>
        <?php foreach ($clubs as $cl): ?>
          <tr>
            <td><?= crm360_h((string)$cl['full_name']) ?> — <?= crm360_h((string)($cl['mobile'] ?? '')) ?></td>
            <td><?= crm360_h((string)$cl['tier_code']) ?></td>
            <td><?= (int)$cl['points_balance'] ?></td>
            <td><?= (int)$cl['visit_count'] ?></td>
            <td><?= crm360_h((string)$cl['churn_risk_level']) ?></td>
            <td>
              <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                <?= crm360_csrf_field() ?>
                <input type="hidden" name="action" value="update_club">
                <input type="hidden" name="return_tab" value="club">
                <input type="hidden" name="club_id" value="<?= (int)$cl['club_id'] ?>">
                <select name="tier_code"><option value="NEW">NEW</option><option value="SILVER">SILVER</option><option value="GOLD">GOLD</option><option value="PLATINUM">PLATINUM</option></select>
                <input name="points_balance" type="number" value="<?= (int)$cl['points_balance'] ?>" style="width:70px">
                <input name="visit_count" type="number" value="<?= (int)$cl['visit_count'] ?>" style="width:60px">
                <button type="submit" class="c360-btn">ذخیره</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

<?php elseif ($tab === 'reminders'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>یادآوری سرویس</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_reminder">
        <input type="hidden" name="return_tab" value="reminders">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>خودرو<select name="vehicle_profile_id" required><?= $vehOpts ?></select></label>
        <label>عنوان<input name="reminder_title" required></label>
        <label>نوع<select name="reminder_type"><option value="SERVICE">سرویس</option><option value="INSPECTION">بازدید</option></select></label>
        <label>تاریخ سررسید<input name="due_date" type="date"></label>
        <label>کیلومتر<input name="due_km" type="number"></label>
        <button type="submit" class="c360-btn primary">ایجاد</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>یادآوری‌ها</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>مشتری</th><th>خودرو</th><th>عنوان</th><th>سررسید</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
          <tbody>
          <?php foreach ($reminders as $r): ?>
            <tr>
              <td><?= crm360_h((string)$r['full_name']) ?></td>
              <td><?= crm360_h((string)$r['brand']) ?> <?= crm360_h((string)$r['model']) ?></td>
              <td><?= crm360_h((string)$r['reminder_title']) ?></td>
              <td><?= crm360_h((string)($r['due_date'] ?? '')) ?></td>
              <td><span class="c360-status"><?= crm360_h((string)$r['reminder_status']) ?></span></td>
              <td>
                <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="update_reminder_status">
                  <input type="hidden" name="return_tab" value="reminders">
                  <input type="hidden" name="reminder_id" value="<?= (int)$r['reminder_id'] ?>">
                  <select name="reminder_status"><option value="SCHEDULED">SCHEDULED</option><option value="CONTACTED">CONTACTED</option><option value="NEEDS_FOLLOWUP">NEEDS_FOLLOWUP</option><option value="DONE">DONE</option></select>
                  <button type="submit" class="c360-btn">ثبت</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'returns'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>بازگشت مشتری</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_return">
        <input type="hidden" name="return_tab" value="returns">
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <label>دلیل<select name="reason_code"><option value="CHURN">ریزش</option><option value="INACTIVE">غیرفعال</option><option value="COMPETITOR">رقیب</option></select></label>
        <label>مسئول<input name="assigned_to"></label>
        <label>اقدام بعدی<input name="next_action_date" type="date"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <button type="submit" class="c360-btn primary">ثبت</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>خط لوله بازگشت</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>مشتری</th><th>مرحله</th><th>نتیجه</th><th>مسئول</th><th>به‌روز</th></tr></thead>
          <tbody>
          <?php foreach ($returns as $ret): ?>
            <tr>
              <td><?= crm360_h((string)$ret['full_name']) ?></td>
              <td><?= crm360_h((string)$ret['return_stage']) ?></td>
              <td><?= crm360_h((string)$ret['result_status']) ?></td>
              <td><?= crm360_h((string)($ret['assigned_to'] ?? '')) ?></td>
              <td>
                <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="update_return">
                  <input type="hidden" name="return_tab" value="returns">
                  <input type="hidden" name="return_id" value="<?= (int)$ret['return_id'] ?>">
                  <select name="return_stage"><option value="IDENTIFIED">IDENTIFIED</option><option value="CONTACTED">CONTACTED</option><option value="OFFERED">OFFERED</option><option value="WON">WON</option><option value="LOST">LOST</option></select>
                  <select name="result_status"><option value="OPEN">OPEN</option><option value="SUCCESS">SUCCESS</option><option value="FAILED">FAILED</option></select>
                  <button type="submit" class="c360-btn">ذخیره</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'promotions'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>ایجاد پروموشن</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_promotion">
        <input type="hidden" name="return_tab" value="promotions">
        <label>عنوان<input name="title" required></label>
        <label>نوع<select name="promotion_type"><option value="DISCOUNT">تخفیف</option><option value="PACKAGE">پکیج</option></select></label>
        <label>شروع<input name="start_date" type="date" value="<?= date('Y-m-d') ?>"></label>
        <label>پایان<input name="end_date" type="date"></label>
        <label>مقدار تخفیف<input name="discount_value" type="number" step="0.01"></label>
        <button type="submit" class="c360-btn primary">ایجاد</button>
      </form>
      <h3 style="margin-top:1rem">تخصیص به مشتری</h3>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="assign_promotion">
        <input type="hidden" name="return_tab" value="promotions">
        <label>پروموشن<select name="promotion_id" required><?= $promoOpts ?></select></label>
        <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
        <button type="submit" class="c360-btn">تخصیص</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>پروموشن‌ها و تخصیص‌ها</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>کد</th><th>عنوان</th><th>وضعیت</th><th>شروع</th><th>پایان</th></tr></thead>
          <tbody>
          <?php foreach ($promotions as $p): ?>
            <tr>
              <td><?= crm360_h((string)$p['promotion_code']) ?></td>
              <td><?= crm360_h((string)$p['title']) ?></td>
              <td><?= crm360_h((string)$p['promotion_status']) ?></td>
              <td><?= crm360_h((string)$p['start_date']) ?></td>
              <td><?= crm360_h((string)$p['end_date']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <h3 style="margin-top:1rem">تخصیص‌ها</h3>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>پروموشن</th><th>مشتری</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
          <tbody>
          <?php foreach ($assignments as $pa): ?>
            <tr>
              <td><?= crm360_h((string)$pa['title']) ?></td>
              <td><?= crm360_h((string)$pa['full_name']) ?></td>
              <td><?= crm360_h((string)$pa['assignment_status']) ?></td>
              <td>
                <form class="c360-inline-form" method="post" action="erp-crm-action.php">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="update_assignment">
                  <input type="hidden" name="return_tab" value="promotions">
                  <input type="hidden" name="assignment_id" value="<?= (int)$pa['assignment_id'] ?>">
                  <select name="assignment_status"><option value="ASSIGNED">ASSIGNED</option><option value="USED">USED</option><option value="EXPIRED">EXPIRED</option><option value="CANCELLED">CANCELLED</option></select>
                  <button type="submit" class="c360-btn">ثبت</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'sms'): ?>
  <div class="c360-grid2">
    <section class="c360-panel">
      <h2>کمپین پیامکی (بدون ارسال زنده)</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_sms_campaign">
        <input type="hidden" name="return_tab" value="sms">
        <label>عنوان<input name="title" required></label>
        <label>بخش هدف<input name="target_segment"></label>
        <label>متن پیام<textarea name="message_text" required rows="3"></textarea></label>
        <button type="submit" class="c360-btn primary">ایجاد کمپین</button>
      </form>
      <h3 style="margin-top:1rem">ساخت گیرندگان</h3>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="build_recipients">
        <input type="hidden" name="return_tab" value="sms">
        <label>شناسه کمپین<input name="campaign_id" type="number" required></label>
        <label>رضایت<select name="consent_mode"><option value="consent_sms">consent_sms</option><option value="consent_marketing">consent_marketing</option></select></label>
        <button type="submit" class="c360-btn">افزودن گیرندگان</button>
      </form>
    </section>
    <section class="c360-panel">
      <h2>کمپین‌ها</h2>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>کد</th><th>عنوان</th><th>وضعیت</th><th>اقدام</th></tr></thead>
          <tbody>
          <?php foreach ($campaigns as $camp): ?>
            <tr>
              <td><?= crm360_h((string)$camp['campaign_code']) ?></td>
              <td><?= crm360_h((string)$camp['title']) ?></td>
              <td><?= crm360_h((string)$camp['campaign_status']) ?></td>
              <td class="c360-inline-form">
                <form method="post" action="erp-crm-action.php" style="display:inline">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="export_campaign">
                  <input type="hidden" name="return_tab" value="sms">
                  <input type="hidden" name="campaign_id" value="<?= (int)$camp['campaign_id'] ?>">
                  <button type="submit" class="c360-btn">Export</button>
                </form>
                <form method="post" action="erp-crm-action.php" style="display:inline">
                  <?= crm360_csrf_field() ?>
                  <input type="hidden" name="action" value="mark_sent_manual">
                  <input type="hidden" name="return_tab" value="sms">
                  <input type="hidden" name="campaign_id" value="<?= (int)$camp['campaign_id'] ?>">
                  <button type="submit" class="c360-btn">ارسال دستی</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <h3 style="margin-top:1rem">گیرندگان اخیر</h3>
      <div class="c360-table-wrap">
        <table class="c360-table">
          <thead><tr><th>کمپین</th><th>موبایل</th><th>رضایت</th><th>وضعیت</th><th>Batch</th></tr></thead>
          <tbody>
          <?php foreach ($recipients as $rc): ?>
            <tr>
              <td><?= crm360_h((string)$rc['campaign_title']) ?></td>
              <td><?= crm360_h((string)$rc['mobile']) ?></td>
              <td><?= crm360_h((string)$rc['consent_status']) ?></td>
              <td><?= crm360_h((string)$rc['recipient_status']) ?></td>
              <td><?= crm360_h((string)($rc['export_batch_no'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

<?php elseif ($tab === 'audit'): ?>
  <section class="c360-panel">
    <h2>Audit CRM360</h2>
    <div class="c360-table-wrap">
      <table class="c360-table">
        <thead><tr><th>زمان</th><th>کاربر</th><th>عمل</th><th>موجودیت</th><th>شناسه</th><th>صفحه</th></tr></thead>
        <tbody>
        <?php foreach ($audits as $a): ?>
          <tr>
            <td><?= crm360_h((string)($a['event_time'] ?? '')) ?></td>
            <td><?= crm360_h((string)$a['actor_user']) ?></td>
            <td><?= crm360_h((string)$a['action_code']) ?></td>
            <td><?= crm360_h((string)$a['entity_name']) ?></td>
            <td><?= crm360_h((string)($a['entity_id'] ?? '')) ?></td>
            <td><?= crm360_h((string)($a['source_page'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

</div>
</body>
</html>
