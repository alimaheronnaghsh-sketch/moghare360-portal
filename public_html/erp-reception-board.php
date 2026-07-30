<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/crm360-helper.php';
require_once __DIR__ . '/includes/reception-ui-helper.php';

crm360_csrf_boot();
$auth = crm360_auth_context();
$actor = $auth['actor'];
$devMode = $auth['dev_mode'];

$layers = crm360_hub_layers();
$hubResolved = crm360_resolve_hub_tab((string)($_GET['tab'] ?? 'dashboard'));
$tab = $hubResolved['tab'];
$panel = $hubResolved['panel'];
$layerLabel = $layers[$tab] ?? 'داشبورد';

$validPanels = [
    'create_customer', 'create_vehicle', 'vehicles', 'cases', 'documents',
    'cartable', 'satisfaction', 'complaints', 'reminders', 'returns',
    'promotions', 'sms', 'club',
];
if ($panel !== '' && !in_array($panel, $validPanels, true)) {
    $panel = '';
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
    'crm_contract_signed' => 0,
    'ready_jobcard' => 0,
    'closed_done' => 0,
    'cases_today' => 0,
];
$customers = $vehicles = $cases = $documents = $cartable = $surveys = $complaints = $clubs = $reminders = $returns = $promotions = $assignments = $campaigns = $recipients = $audits = $onlineRequests = $walkinRequests = [];
$vipCandidates = $vipRules = $vipPending = $vipCustomers = $importBatches = $importRows = [];

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
    $rx['case_incomplete'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status=N'PROFILE_INCOMPLETE' OR profile_completion_percent < 100") ?? 0);
    $rx['ready_contract'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status IN (N'READY_FOR_CONTRACT',N'CONTRACT_PENDING')") ?? 0);
    $rx['cases_today'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE CONVERT(date, created_at)=CONVERT(date, SYSUTCDATETIME())") ?? 0);
    $rx['crm_contract_signed'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status=N'CONTRACT_SIGNED' OR contract_status=N'SIGNED'") ?? 0);
    if (crm360_table_exists($conn, 'erp_intake_contracts')) {
        $rx['contract_unsigned'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_intake_contracts WHERE contract_status IN (N'DRAFT',N'SENT',N'VIEWED')") ?? 0);
        $rx['contract_signed'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.erp_intake_contracts WHERE contract_status IN (N'SIGNED',N'OVERRIDDEN')") ?? 0);
    }
    if ($rx['contract_signed'] === 0) {
        $rx['contract_signed'] = (int)$rx['crm_contract_signed'];
    }
    if ($rx['closed_done'] === 0) {
        $rx['closed_done'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status IN (N'DELIVERED',N'CLOSED')") ?? 0);
    }
    $walkinRequests = [];
    if (crm360_table_exists($conn, 'erp_customer_online_requests')) {
        $walkinRequests = crm360_rows($conn, "SELECT TOP 100 online_request_id, mobile, vehicle_plate, request_status, created_at FROM dbo.erp_customer_online_requests WHERE request_payload_json LIKE N'%STAFF_ASSISTED_WALKIN%' ORDER BY online_request_id DESC");
    }

    $customers = crm360_rows($conn, 'SELECT TOP 100 * FROM dbo.crm360_customer_profiles ORDER BY customer_profile_id DESC');
    $vehicles = crm360_rows($conn, 'SELECT TOP 100 v.*, c.full_name FROM dbo.crm360_vehicle_profiles v INNER JOIN dbo.crm360_customer_profiles c ON c.customer_profile_id=v.customer_profile_id ORDER BY v.vehicle_profile_id DESC');
    $cases = crm360_rows($conn, 'SELECT TOP 100 c.*, cu.full_name, v.brand, v.model FROM dbo.crm360_reception_cases c LEFT JOIN dbo.crm360_customer_profiles cu ON cu.customer_profile_id=c.customer_profile_id LEFT JOIN dbo.crm360_vehicle_profiles v ON v.vehicle_profile_id=c.vehicle_profile_id ORDER BY c.case_id DESC');
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

    $vipRules = crm360_active_vip_rules($conn);
    if (crm360_table_exists($conn, 'crm360_customer_profiles')) {
        $vipCandidates = crm360_vip_candidates($conn, 50);
    }
    if (crm360_table_exists($conn, 'crm360_vip_requests')) {
        $vipPending = crm360_rows(
            $conn,
            "SELECT TOP 100 r.*, c.full_name, c.mobile FROM dbo.crm360_vip_requests r
             INNER JOIN dbo.crm360_customer_profiles c ON c.customer_profile_id=r.customer_profile_id
             WHERE request_status=N'SUBMITTED' ORDER BY r.vip_request_id DESC"
        );
    }
    $vipCustomers = crm360_rows(
        $conn,
        "SELECT TOP 100 * FROM dbo.crm360_customer_profiles
         WHERE vip_level IN (N'VIP',N'GOLD',N'PLATINUM') ORDER BY customer_profile_id DESC"
    );
    if (crm360_table_exists($conn, 'crm360_import_batches')) {
        $importBatches = crm360_rows($conn, 'SELECT TOP 100 * FROM dbo.crm360_import_batches ORDER BY import_batch_id DESC');
    }
    $batchIdSel = (int)($_GET['batch_id'] ?? 0);
    if ($batchIdSel > 0 && crm360_table_exists($conn, 'crm360_import_rows')) {
        $importRows = crm360_rows($conn, 'SELECT * FROM dbo.crm360_import_rows WHERE import_batch_id=? ORDER BY row_no', [$batchIdSel]);
    }
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

$casesPage = max(1, (int)($_GET['cases_page'] ?? 1));
$casesSorted = m360_rui_sort_rows($cases, 'case_id', 'desc');
$casesPageInfo = m360_rui_paginate($casesSorted, $casesPage, 10);
$hubCases = $casesPageInfo['rows'];

$receptionCards = [
    ['title' => 'درخواست‌های آنلاین', 'href' => 'erp-reception-online-requests.php', 'count' => (int)$rx['online_new'], 'unit' => 'جدید'],
    ['title' => 'پذیرش حضوری', 'href' => 'erp-reception-walkin-create.php', 'count' => (int)$rx['walkin_today'], 'unit' => 'امروز'],
    ['title' => 'جستجوی مشتری پذیرش', 'href' => 'erp-reception-walkin-create.php#m360_section_customer_search', 'count' => null, 'unit' => ''],
    ['title' => 'جستجوی مشتری / خودرو', 'href' => 'erp-customer-vehicle-workbench.php?role=reception', 'count' => null, 'unit' => ''],
    ['title' => 'تکمیل پرونده پذیرش', 'href' => 'erp-reception-online-requests.php', 'count' => (int)$rx['online_pending'], 'unit' => 'در انتظار'],
    ['title' => 'پرونده‌های در جریان', 'href' => 'erp-reception-jobcards.php', 'count' => (int)$rx['ready_jobcard'], 'unit' => 'آماده سالن'],
    ['title' => 'قرارداد و مدارک', 'href' => 'erp-intake-contracts.php', 'count' => (int)$rx['contract_unsigned'], 'unit' => 'امضانشده'],
    ['title' => 'کارتابل مشتری', 'href' => '?tab=experience&panel=cartable', 'count' => (int)$kpi['cartable_open'], 'unit' => 'باز'],
];

$dashboardQuickActions = [
    ['title' => 'افزودن مشتری جدید', 'href' => '?tab=customers&panel=create_customer', 'meta' => 'ثبت پروفایل'],
    ['title' => 'افزودن خودرو جدید', 'href' => '?tab=customers&panel=create_vehicle', 'meta' => 'ثبت خودرو'],
    ['title' => 'ایجاد پرونده پذیرش', 'href' => '?tab=reception&panel=cases', 'meta' => 'پرونده جدید'],
    ['title' => 'ورود اطلاعات قدیمی', 'href' => '?tab=legacy', 'meta' => 'Legacy'],
    ['title' => 'VIP و باشگاه', 'href' => '?tab=vip', 'meta' => (int)count($vipPending) . ' در انتظار'],
    ['title' => 'درخواست‌های آنلاین', 'href' => 'erp-reception-online-requests.php', 'meta' => (int)$rx['online_new'] . ' جدید'],
    ['title' => 'پذیرش حضوری', 'href' => 'erp-reception-walkin-create.php', 'meta' => (int)$rx['walkin_today'] . ' امروز'],
];

$dashMetrics = $dbOk && $conn ? crm360_dashboard_metrics($conn) : crm360_dashboard_metrics(null);
$weekCases = (int)$dashMetrics['cases_week'];
$weekGaugePct = (int)min(100, $weekCases);
$weekCompleted = (int)$dashMetrics['completed_week'];
$weekCompletePct = $weekCases > 0 ? (int)round(100 * $weekCompleted / max(1, $weekCases)) : 0;
$carsInside = (int)$dashMetrics['cars_inside'];
$dissat = (int)$dashMetrics['dissatisfaction'];
$dissatPct = (int)min(100, $dissat * 10);
$satPctDash = (int)$dashMetrics['satisfaction_pct'];
$loyalMonth = (int)$dashMetrics['loyal_month'];
$loyalPct = (int)$dashMetrics['customers_total'] > 0
    ? (int)round(100 * $loyalMonth / max(1, (int)$dashMetrics['customers_total']))
    : 0;
$normalPct = (int)$dashMetrics['normal_pct'];
$vipPct = (int)$dashMetrics['vip_pct'];
$returnsQ = (int)$dashMetrics['returns_quarter'];
$serviceMix = is_array($dashMetrics['service_mix'] ?? null) ? $dashMetrics['service_mix'] : [];
$serviceMixTop = array_slice($serviceMix, 0, 4);
$serviceMixTitle = '';
foreach ($serviceMixTop as $sm) {
    $serviceMixTitle .= ($sm['label'] ?? '') . ' ' . (int)($sm['pct'] ?? 0) . '% · ';
}
$serviceMixTitle = rtrim($serviceMixTitle, ' · ');
$serviceMixPrimaryPct = (int)($serviceMixTop[0]['pct'] ?? 0);

$customerHubCards = [
    ['title' => 'افزودن مشتری جدید', 'href' => '?tab=customers&panel=create_customer'],
    ['title' => 'افزودن خودرو جدید', 'href' => '?tab=customers&panel=create_vehicle'],
    ['title' => 'جستجوی مشتری پذیرش', 'href' => 'erp-reception-walkin-create.php#m360_section_customer_search'],
    ['title' => 'جستجوی مشتری / خودرو', 'href' => 'erp-customer-vehicle-workbench.php?role=reception'],
    ['title' => 'فهرست مشتریان', 'href' => '?tab=customers'],
    ['title' => 'فهرست خودروها', 'href' => '?tab=customers&panel=vehicles'],
];

$experiencePanels = [
    'cartable' => ['label' => 'کارتابل', 'count' => (int)$kpi['cartable_open']],
    'satisfaction' => ['label' => 'رضایت‌سنجی', 'count' => (int)$kpi['surveys_done']],
    'complaints' => ['label' => 'شکایات', 'count' => (int)$kpi['complaints_open']],
    'reminders' => ['label' => 'یادآوری', 'count' => (int)$kpi['reminders_due']],
    'returns' => ['label' => 'بازگشت مشتری', 'count' => (int)$kpi['returns_open']],
    'promotions' => ['label' => 'پروموشن', 'count' => (int)$kpi['promos_active']],
    'sms' => ['label' => 'پیامک', 'count' => (int)$kpi['sms_ready']],
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
  <div class="c360-crumb"><a href="personnel.html">پرسنل</a> / <a href="erp-reception-board.php">ارتباط با مشتریان</a> / <?= crm360_h($layerLabel) ?></div>
  <header class="c360-head">
    <div>
      <h1>مرکز ارتباط با مشتریان</h1>
      <p>پذیرش، پرونده، پیگیری، رضایت، وفاداری و کمپین</p>
      <div class="c360-head-meta">
        <a class="c360-btn" href="personnel.html">بازگشت به صفحه پرسنل</a>
        <span class="c360-badge">moghare360_ERP</span>
        <span class="c360-badge"><?= crm360_h($auth['role_label']) ?></span>
      </div>
    </div>
    <div>
      <?php if ($devMode): ?><span class="c360-badge">حالت توسعه محلی</span><?php endif; ?>
      <?php if (strcasecmp($actor, 'local_owner') === 0): ?><span class="c360-badge">local_owner</span><?php endif; ?>
      <div class="c360-muted" style="margin-top:.4rem;font-size:.75rem">actor: <?= crm360_h($actor) ?></div>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="c360-flash <?= crm360_h($flash['type']) ?>"><?= crm360_h($flash['msg']) ?></div>
  <?php endif; ?>
  <?php if (!$dbOk): ?>
    <div class="c360-flash err"><?= crm360_h($dbErr) ?></div>
  <?php endif; ?>

  <nav class="c360-tabs sticky">
    <?php foreach ($layers as $k => $label): ?>
      <a href="?tab=<?= crm360_h($k) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= crm360_h($label) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'dashboard'): ?>
  <h2 class="c360-layer-title">داشبورد</h2>
  <section class="c360-panel c360-hub-section">
    <div class="c360-section-head"><div><h2>اقدام سریع</h2></div></div>
    <div class="crm-qa-strip">
      <?php foreach ($dashboardQuickActions as $card): ?>
        <a class="c360-hub-card crm-qa-card" href="<?= crm360_h($card['href']) ?>">
          <strong><?= crm360_h($card['title']) ?></strong>
          <em class="c360-hub-meta"><?= crm360_h($card['meta']) ?></em>
          <span class="c360-hub-go">ورود</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="c360-panel c360-hub-section">
    <div class="c360-section-head"><div><h2>شاخص‌های عملیاتی</h2></div></div>
    <div class="crm-gauge-strip" aria-label="شاخص‌های داشبورد">
      <?= crm360_gauge_card([
          'pct' => $weekGaugePct,
          'label' => 'پرونده‌های هفته',
          'value' => $weekCases > 100 ? '100+' : (string)$weekCases,
          'sub' => 'از ۱۰۰',
          'color' => '#3ecf8e',
          'title' => 'تعداد واقعی: ' . $weekCases,
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => $weekCompletePct,
          'label' => 'تکمیل امروز / هفته',
          'value' => (string)$weekCompletePct . '%',
          'sub' => 'امروز ' . (int)$dashMetrics['completed_today'] . ' · هفته ' . $weekCompleted,
          'color' => '#66bb6a',
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => (int)min(100, $carsInside * 5),
          'label' => 'خودرو داخل مجموعه',
          'value' => (string)$carsInside,
          'sub' => 'در حال خدمات',
          'color' => '#42a5f5',
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => $dissatPct,
          'label' => 'نارضایتی',
          'value' => (string)$dissat,
          'sub' => $dissat > 0 ? 'نیازمند پیگیری' : 'بدون مورد',
          'color' => $dissat > 0 ? '#e57373' : '#66bb6a',
          'tone' => $dissat > 0 ? 'warn' : '',
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => $satPctDash,
          'label' => 'رضایت',
          'value' => ((float)$dashMetrics['satisfaction_avg'] > 0 ? number_format((float)$dashMetrics['satisfaction_avg'], 1) : '—'),
          'sub' => $satPctDash > 0 ? ($satPctDash . '% امتیاز خوب') : 'بدون داده',
          'color' => '#3ecf8e',
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => $loyalPct,
          'label' => 'وفادارهای ماه',
          'value' => (string)$loyalMonth,
          'sub' => 'باشگاه مشتریان',
          'color' => '#e8b84a',
          'title' => 'بر اساس سطح باشگاه GOLD/PLATINUM/VIP و فعالیت از اول ماه',
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => $vipPct,
          'label' => 'عادی / VIP',
          'value' => $vipPct . '%',
          'sub' => 'عادی ' . $normalPct . '% · VIP ' . $vipPct . '%',
          'color' => '#ab47bc',
          'title' => 'عادی: ' . (int)$dashMetrics['customers_normal'] . ' · VIP: ' . (int)$dashMetrics['customers_vip'],
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => $serviceMixPrimaryPct,
          'label' => 'تفکیک خدمات',
          'value' => $serviceMixPrimaryPct > 0 ? ($serviceMixPrimaryPct . '%') : '—',
          'sub' => $serviceMixTitle !== '' ? $serviceMixTitle : 'بدون داده',
          'color' => '#26a69a',
          'title' => $serviceMixTitle !== '' ? $serviceMixTitle : 'بدون داده ۳۰ روز اخیر',
      ]) ?>
      <?= crm360_gauge_card([
          'pct' => (int)min(100, $returnsQ * 10),
          'label' => 'بازگشت فصل گذشته',
          'value' => (string)$returnsQ,
          'sub' => '۹۰ روز اخیر',
          'color' => '#ffa726',
      ]) ?>
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
                <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)($cb['item_status'] ?? ''))) ?></span></td>
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
                <td><?= crm360_h(m360_rui_label((string)($au['action_code'] ?? ''))) ?></td>
                <td><?= crm360_h((string)($au['entity_name'] ?? '')) ?> #<?= crm360_h((string)($au['entity_id'] ?? '')) ?></td>
                <td><?= crm360_h(m360_rui_jalali_date((string)($au['event_time'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

<?php elseif ($tab === 'reception'): ?>
  <h2 class="c360-layer-title">پرونده‌های پذیرش</h2>
  <section class="c360-status-grid c360-rx-grid">
    <div class="c360-status-card"><span class="c360-light <?= $rx['online_new'] ? 'warn' : 'idle' ?>"></span><span>درخواست آنلاین جدید</span><strong><?= (int)$rx['online_new'] ?></strong></div>
    <div class="c360-status-card"><span class="c360-light <?= $rx['online_pending'] ? 'warn' : 'ok' ?>"></span><span>آنلاین در انتظار</span><strong><?= (int)$rx['online_pending'] ?></strong></div>
    <div class="c360-status-card"><span class="c360-light <?= $rx['walkin_today'] ? 'ok' : 'idle' ?>"></span><span>پذیرش حضوری امروز</span><strong><?= (int)$rx['walkin_today'] ?></strong></div>
    <div class="c360-status-card"><span class="c360-light <?= $rx['case_incomplete'] ? 'warn' : 'ok' ?>"></span><span>پرونده ناقص</span><strong><?= (int)$rx['case_incomplete'] ?></strong></div>
    <div class="c360-status-card"><span class="c360-light <?= $rx['ready_jobcard'] ? 'warn' : 'idle' ?>"></span><span>آماده سالن</span><strong><?= (int)$rx['ready_jobcard'] ?></strong></div>
    <div class="c360-status-card"><span class="c360-light idle"></span><span>بسته‌شده</span><strong><?= (int)$rx['closed_done'] ?></strong></div>
  </section>
  <div class="c360-hub-grid" style="margin-bottom:1rem">
    <?php foreach ($receptionCards as $card): ?>
      <a class="c360-hub-card c360-hub-card--rx" href="<?= crm360_h($card['href']) ?>">
        <strong><?= crm360_h($card['title']) ?></strong>
        <?php if ($card['count'] !== null): ?><em class="c360-hub-meta"><?= (int)$card['count'] ?> <?= crm360_h($card['unit']) ?></em><?php else: ?><em class="c360-hub-meta">&nbsp;</em><?php endif; ?>
        <span class="c360-hub-go">ورود</span>
      </a>
    <?php endforeach; ?>
  </div>
  <nav class="c360-filters">
    <a href="?tab=reception" class="<?= $panel === '' ? 'is-active' : '' ?>">فهرست پرونده‌ها</a>
    <a href="?tab=reception&panel=cases" class="<?= $panel === 'cases' ? 'is-active' : '' ?>">ایجاد پرونده</a>
    <a href="?tab=reception&panel=documents" class="<?= $panel === 'documents' ? 'is-active' : '' ?>">مدارک</a>
  </nav>
  <?php if ($panel === 'cases'): ?>
    <?php $prefReq = (int)($_GET['req'] ?? 0); ?>
    <section class="c360-panel">
      <h2>ایجاد پرونده پذیرش</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_case">
        <input type="hidden" name="return_tab" value="cases">
        <?= crm360_render_search_picker(
            'customer',
            'customer_profile_id',
            'جستجوی مشتری',
            'نام، موبایل، کد ملی یا کد مشتری را وارد کنید',
            '?tab=customers&panel=create_customer',
            'افزودن مشتری جدید',
            'مشتری پیدا نشد'
        ) ?>
        <?= crm360_render_search_picker(
            'vehicle',
            'vehicle_profile_id',
            'جستجوی خودرو',
            'پلاک، VIN، برند یا مدل را وارد کنید',
            '?tab=customers&panel=create_vehicle',
            'افزودن خودرو جدید',
            'خودرو پیدا نشد'
        ) ?>
        <label>شناسه درخواست آنلاین<input name="existing_request_id" type="number" value="<?= $prefReq > 0 ? $prefReq : '' ?>"></label>
        <label>نوع<select name="case_type"><option value="WALKIN">حضوری</option><option value="ONLINE">آنلاین</option><option value="RETURNING">مراجع مجدد</option></select></label>
        <label>نوع خدمت<input name="service_type" required></label>
        <label>مسئول<input name="responsible_staff" required></label>
        <label>پذیرش‌کننده<input name="assigned_staff"></label>
        <label>منبع<input name="reception_source"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <button type="submit" class="c360-btn primary">ایجاد پرونده</button>
      </form>
    </section>
  <?php elseif ($panel === 'documents'): ?>
    <?php $docList = m360_rui_paginate(m360_rui_sort_rows($documents, 'document_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
    <section class="c360-panel">
      <h2>مدارک پرونده</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php" style="max-width:420px;margin-bottom:1rem">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="generate_docs_checklist">
        <input type="hidden" name="return_tab" value="documents">
        <label>پرونده<select name="case_id" required><?= $caseOpts ?></select></label>
        <button type="submit" class="c360-btn">ایجاد چک‌لیست</button>
      </form>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>پرونده</th><th>نوع</th><th>عنوان</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
        <tbody><?php foreach ($docList['rows'] as $d): ?><tr>
          <td><?= crm360_h((string)($d['case_code'] ?? $d['case_id'])) ?></td>
          <td><?= crm360_h(m360_rui_label((string)$d['document_type'])) ?></td>
          <td><?= crm360_h((string)$d['document_title']) ?></td>
          <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)$d['document_status'])) ?></span></td>
          <td><?= crm360_h(m360_rui_jalali_date((string)($d['updated_at'] ?? ''))) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($docList, m360_rui_query_keep(['tab' => 'reception', 'panel' => 'documents'], ['list_page']), 'list_page'); ?>
    </section>
  <?php else: ?>
    <section class="c360-panel">
      <div class="c360-section-head"><div><h2>پرونده‌های پذیرش</h2></div><a class="c360-btn" href="?tab=reception&panel=cases">ایجاد</a></div>
      <?php if (!$hubCases): ?><p class="c360-muted">پرونده‌ای ثبت نشده است.</p><?php else: ?>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>کد</th><th>مشتری</th><th>خودرو</th><th>نوع</th><th>وضعیت</th><th>تکمیل</th><th>بروزرسانی</th></tr></thead>
        <tbody><?php foreach ($hubCases as $c): ?><tr>
          <td><?= crm360_h((string)($c['case_code'] ?? '')) ?></td>
          <td><?= crm360_h((string)($c['full_name'] ?? '')) ?></td>
          <td><?= crm360_h(trim((string)($c['brand'] ?? '') . ' ' . (string)($c['model'] ?? ''))) ?></td>
          <td><?= crm360_h(m360_rui_label((string)($c['case_type'] ?? ''))) ?></td>
          <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)($c['case_status'] ?? ''))) ?></span></td>
          <td><?= (int)($c['profile_completion_percent'] ?? 0) ?>%</td>
          <td><?= crm360_h(m360_rui_jalali_date((string)($c['updated_at'] ?? $c['created_at'] ?? ''))) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($casesPageInfo, m360_rui_query_keep(['tab' => 'reception'], ['cases_page']), 'cases_page'); ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>

<?php elseif ($tab === 'customers'): ?>
  <h2 class="c360-layer-title">مشتریان و خودروها</h2>
  <div class="c360-hub-grid" style="margin-bottom:1rem">
    <?php foreach ($customerHubCards as $card): ?>
      <a class="c360-hub-card" href="<?= crm360_h($card['href']) ?>">
        <strong><?= crm360_h($card['title']) ?></strong>
        <span class="c360-hub-go">ورود</span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php
    $custQ = trim((string)($_GET['q'] ?? ''));
    $vehQ = trim((string)($_GET['vq'] ?? ''));
    $custSort = (string)($_GET['csort'] ?? 'customer_profile_id');
    $custDir = strtolower((string)($_GET['cdir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    $vehSort = (string)($_GET['vsort'] ?? 'vehicle_profile_id');
    $vehDir = strtolower((string)($_GET['vdir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    if (!in_array($custSort, ['customer_profile_id', 'full_name', 'mobile', 'created_at'], true)) {
        $custSort = 'customer_profile_id';
    }
    if (!in_array($vehSort, ['vehicle_profile_id', 'plate_no', 'brand', 'full_name'], true)) {
        $vehSort = 'vehicle_profile_id';
    }
    $customersFiltered = $customers;
    if ($custQ !== '') {
        $customersFiltered = array_values(array_filter($customers, static function ($c) use ($custQ) {
            $hay = mb_strtolower(trim(
                (string)($c['full_name'] ?? '') . ' ' .
                (string)($c['mobile'] ?? '') . ' ' .
                (string)($c['national_id'] ?? '') . ' ' .
                (string)($c['customer_ref_text'] ?? '') . ' ' .
                (string)($c['customer_profile_id'] ?? '')
            ));
            return mb_strpos($hay, mb_strtolower($custQ)) !== false;
        }));
    }
    $vehiclesFiltered = $vehicles;
    if ($vehQ !== '') {
        $vehiclesFiltered = array_values(array_filter($vehicles, static function ($v) use ($vehQ) {
            $hay = mb_strtolower(trim(
                (string)($v['plate_no'] ?? '') . ' ' .
                (string)($v['vin'] ?? '') . ' ' .
                (string)($v['brand'] ?? '') . ' ' .
                (string)($v['model'] ?? '') . ' ' .
                (string)($v['full_name'] ?? '') . ' ' .
                (string)($v['vehicle_profile_id'] ?? '')
            ));
            return mb_strpos($hay, mb_strtolower($vehQ)) !== false;
        }));
    }
    $custList = m360_rui_paginate(m360_rui_sort_rows($customersFiltered, $custSort, $custDir), max(1, (int)($_GET['cust_page'] ?? 1)), 10);
    $vehList = m360_rui_paginate(m360_rui_sort_rows($vehiclesFiltered, $vehSort, $vehDir), max(1, (int)($_GET['veh_page'] ?? 1)), 10);
  ?>
  <?php if ($panel === 'create_customer'): ?>
    <section class="c360-panel">
      <h2>افزودن مشتری جدید</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_customer">
        <input type="hidden" name="return_tab" value="customers">
        <label>نام کامل<input name="full_name" required></label>
        <label>موبایل<input name="mobile" inputmode="tel"></label>
        <label>کد ملی<input name="national_id"></label>
        <label>نوع<select name="customer_type"><option value="PERSON">حقیقی</option><option value="COMPANY">حقوقی</option></select></label>
        <label>کانال ترجیحی<input name="preferred_contact_channel"></label>
        <label><input type="checkbox" name="consent_sms" value="1"> رضایت SMS</label>
        <label><input type="checkbox" name="consent_marketing" value="1"> رضایت بازاریابی</label>
        <label><input type="checkbox" name="consent_service_reminder" value="1"> یادآوری سرویس</label>
        <label>کانال منبع<input name="source_channel"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <?php if (crm360_can('customer_create', $auth)): ?><button type="submit" class="c360-btn primary">ثبت مشتری</button><?php else: ?><p class="c360-muted">مجوز ثبت مشتری ندارید.</p><?php endif; ?>
      </form>
    </section>
  <?php elseif ($panel === 'create_vehicle'): ?>
    <section class="c360-panel">
      <h2>افزودن خودرو جدید</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="create_vehicle">
        <input type="hidden" name="return_tab" value="customers">
        <?= crm360_render_search_picker(
            'customer',
            'customer_profile_id',
            'جستجوی مشتری',
            'نام، موبایل، کد ملی یا کد مشتری را وارد کنید',
            '?tab=customers&panel=create_customer',
            'افزودن مشتری جدید',
            'مشتری پیدا نشد'
        ) ?>
        <label>برند<select name="brand" required><?= crm360_brand_options() ?></select></label>
        <label>مدل<input name="model" required></label>
        <label>پلاک<input name="plate_no"></label>
        <label>VIN<input name="vin"></label>
        <label>کارکرد (km)<input name="mileage" type="number" min="0"></label>
        <label>فاصله سرویس (km)<input name="service_interval_km" type="number" min="0"></label>
        <label>فاصله سرویس (ماه)<input name="service_interval_months" type="number" min="0"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <?php if (crm360_can('vehicle_create', $auth)): ?><button type="submit" class="c360-btn primary">ثبت خودرو</button><?php else: ?><p class="c360-muted">مجوز ثبت خودرو ندارید.</p><?php endif; ?>
      </form>
    </section>
  <?php endif; ?>
  <?php if ($panel === '' || $panel === 'create_customer'): ?>
    <section class="c360-panel">
      <h2>فهرست مشتریان</h2>
      <form class="c360-filter-bar" method="get" action="erp-reception-board.php">
        <input type="hidden" name="tab" value="customers">
        <?php if ($panel !== ''): ?><input type="hidden" name="panel" value="<?= crm360_h($panel) ?>"><?php endif; ?>
        <label>جستجو<input type="search" name="q" value="<?= crm360_h($custQ) ?>" placeholder="نام، موبایل، کد ملی"></label>
        <label>مرتب‌سازی
          <select name="csort">
            <option value="customer_profile_id" <?= $custSort === 'customer_profile_id' ? 'selected' : '' ?>>شناسه</option>
            <option value="full_name" <?= $custSort === 'full_name' ? 'selected' : '' ?>>نام</option>
            <option value="mobile" <?= $custSort === 'mobile' ? 'selected' : '' ?>>موبایل</option>
            <option value="created_at" <?= $custSort === 'created_at' ? 'selected' : '' ?>>تاریخ</option>
          </select>
        </label>
        <label>جهت
          <select name="cdir">
            <option value="desc" <?= $custDir === 'desc' ? 'selected' : '' ?>>نزولی</option>
            <option value="asc" <?= $custDir === 'asc' ? 'selected' : '' ?>>صعودی</option>
          </select>
        </label>
        <label>&nbsp;<button type="submit" class="c360-btn">اعمال</button></label>
      </form>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>شناسه</th><th>نام</th><th>موبایل</th><th>وضعیت</th><th>VIP</th><th>ثبت</th></tr></thead>
        <tbody><?php foreach ($custList['rows'] as $c): ?><tr>
          <td><?= (int)$c['customer_profile_id'] ?></td>
          <td><?= crm360_h((string)$c['full_name']) ?></td>
          <td><?= crm360_h((string)($c['mobile'] ?? '')) ?></td>
          <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)$c['customer_status'])) ?></span></td>
          <td><?= crm360_h(m360_rui_label((string)$c['vip_level'])) ?></td>
          <td><?= crm360_h(m360_rui_jalali_date((string)($c['created_at'] ?? ''))) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($custList, m360_rui_query_keep(['tab' => 'customers', 'panel' => $panel, 'q' => $custQ, 'csort' => $custSort, 'cdir' => $custDir], ['cust_page']), 'cust_page'); ?>
    </section>
  <?php endif; ?>
  <?php if ($panel === '' || $panel === 'vehicles' || $panel === 'create_vehicle'): ?>
    <section class="c360-panel">
      <h2>فهرست خودروها</h2>
      <form class="c360-filter-bar" method="get" action="erp-reception-board.php">
        <input type="hidden" name="tab" value="customers">
        <input type="hidden" name="panel" value="<?= crm360_h($panel ?: 'vehicles') ?>">
        <label>جستجو<input type="search" name="vq" value="<?= crm360_h($vehQ) ?>" placeholder="پلاک، VIN، برند، مدل"></label>
        <label>مرتب‌سازی
          <select name="vsort">
            <option value="vehicle_profile_id" <?= $vehSort === 'vehicle_profile_id' ? 'selected' : '' ?>>شناسه</option>
            <option value="plate_no" <?= $vehSort === 'plate_no' ? 'selected' : '' ?>>پلاک</option>
            <option value="brand" <?= $vehSort === 'brand' ? 'selected' : '' ?>>برند</option>
            <option value="full_name" <?= $vehSort === 'full_name' ? 'selected' : '' ?>>مشتری</option>
          </select>
        </label>
        <label>جهت
          <select name="vdir">
            <option value="desc" <?= $vehDir === 'desc' ? 'selected' : '' ?>>نزولی</option>
            <option value="asc" <?= $vehDir === 'asc' ? 'selected' : '' ?>>صعودی</option>
          </select>
        </label>
        <label>&nbsp;<button type="submit" class="c360-btn">اعمال</button></label>
      </form>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>شناسه</th><th>مشتری</th><th>برند/مدل</th><th>پلاک</th><th>VIN</th><th>کارکرد</th></tr></thead>
        <tbody><?php foreach ($vehList['rows'] as $v): ?><tr>
          <td><?= (int)$v['vehicle_profile_id'] ?></td>
          <td><?= crm360_h((string)$v['full_name']) ?></td>
          <td><?= crm360_h((string)$v['brand']) ?> <?= crm360_h((string)$v['model']) ?></td>
          <td><?= crm360_h((string)($v['plate_no'] ?? '')) ?></td>
          <td><?= crm360_h((string)($v['vin'] ?? '')) ?></td>
          <td><?= crm360_h((string)($v['mileage'] ?? '')) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($vehList, m360_rui_query_keep(['tab' => 'customers', 'panel' => $panel ?: 'vehicles', 'vq' => $vehQ, 'vsort' => $vehSort, 'vdir' => $vehDir], ['veh_page']), 'veh_page'); ?>
    </section>
  <?php endif; ?>

<?php elseif ($tab === 'legacy'): ?>
  <h2 class="c360-layer-title">ورود اطلاعات قدیمی</h2>
  <?php $batchList = m360_rui_paginate(m360_rui_sort_rows($importBatches, 'import_batch_id', 'desc'), max(1, (int)($_GET['batch_page'] ?? 1)), 10); ?>
  <div class="c360-grid2">
    <?php if (crm360_can('legacy_draft', $auth)): ?>
    <section class="c360-panel">
      <h2>ثبت مشتری قدیمی</h2>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="legacy_create_customer">
        <input type="hidden" name="return_tab" value="legacy">
        <label>نام<input name="full_name" required></label>
        <label>موبایل<input name="mobile"></label>
        <label>کد ملی<input name="national_id"></label>
        <label>نوع<select name="customer_type"><option value="PERSON">حقیقی</option><option value="COMPANY">حقوقی</option></select></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <button type="submit" class="c360-btn primary">ثبت</button>
      </form>
      <h3 style="margin-top:1rem">ثبت پرونده قدیمی</h3>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="legacy_create_case">
        <input type="hidden" name="return_tab" value="legacy">
        <?= crm360_render_search_picker(
            'customer',
            'customer_profile_id',
            'جستجوی مشتری',
            'نام، موبایل، کد ملی یا کد مشتری را وارد کنید',
            '?tab=customers&panel=create_customer',
            'افزودن مشتری جدید',
            'مشتری پیدا نشد'
        ) ?>
        <?= crm360_render_search_picker(
            'vehicle',
            'vehicle_profile_id',
            'جستجوی خودرو',
            'پلاک، VIN، برند یا مدل را وارد کنید',
            '?tab=customers&panel=create_vehicle',
            'افزودن خودرو جدید',
            'خودرو پیدا نشد'
        ) ?>
        <label>خدمت<input name="service_type"></label>
        <label>مرجع قدیمی<input name="source_ref_text"></label>
        <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
        <button type="submit" class="c360-btn">ثبت پرونده</button>
      </form>
      <h3 style="margin-top:1rem">ورود گروهی CSV</h3>
      <form class="c360-form" method="post" action="erp-crm-action.php">
        <?= crm360_csrf_field() ?>
        <input type="hidden" name="action" value="legacy_batch_draft">
        <input type="hidden" name="return_tab" value="legacy">
        <label>نام منبع<input name="source_name" value="CSV_PASTE"></label>
        <label>CSV<textarea name="paste_csv" rows="6" placeholder="full_name,mobile"></textarea></label>
        <button type="submit" class="c360-btn">ایجاد پیش‌نویس</button>
      </form>
    </section>
    <?php else: ?>
    <section class="c360-panel"><p class="c360-muted">مجوز ورود اطلاعات قدیمی ندارید.</p></section>
    <?php endif; ?>
    <section class="c360-panel">
      <h2>دسته‌های ورود</h2>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>کد</th><th>منبع</th><th>وضعیت</th><th>ردیف</th><th>اقدام</th></tr></thead>
        <tbody><?php foreach ($batchList['rows'] as $b): ?><tr>
          <td><a href="?tab=legacy&batch_id=<?= (int)$b['import_batch_id'] ?>"><?= crm360_h((string)$b['batch_code']) ?></a></td>
          <td><?= crm360_h((string)($b['source_name'] ?? '')) ?></td>
          <td><?= crm360_h(m360_rui_label((string)($b['import_status'] ?? ''))) ?></td>
          <td><?= (int)($b['total_rows'] ?? 0) ?></td>
          <td class="c360-inline-form">
            <?php if (crm360_can('legacy_draft', $auth)): ?>
            <form method="post" action="erp-crm-action.php" style="display:inline"><?= crm360_csrf_field() ?>
              <input type="hidden" name="action" value="legacy_batch_validate">
              <input type="hidden" name="return_tab" value="legacy">
              <input type="hidden" name="import_batch_id" value="<?= (int)$b['import_batch_id'] ?>">
              <button type="submit" class="c360-btn">اعتبارسنجی</button>
            </form>
            <?php endif; ?>
            <?php if (crm360_can('legacy_approve', $auth)): ?>
            <form method="post" action="erp-crm-action.php" style="display:inline"><?= crm360_csrf_field() ?>
              <input type="hidden" name="action" value="legacy_batch_import">
              <input type="hidden" name="return_tab" value="legacy">
              <input type="hidden" name="import_batch_id" value="<?= (int)$b['import_batch_id'] ?>">
              <button type="submit" class="c360-btn">ورود نهایی</button>
            </form>
            <?php endif; ?>
          </td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($batchList, m360_rui_query_keep(['tab' => 'legacy'], ['batch_page', 'batch_id']), 'batch_page'); ?>
      <?php if ($importRows): ?>
      <h3 style="margin-top:1rem">ردیف‌های دسته #<?= (int)($_GET['batch_id'] ?? 0) ?></h3>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>#</th><th>وضعیت</th><th>پیام</th></tr></thead>
        <tbody><?php foreach ($importRows as $ir): ?><tr>
          <td><?= (int)$ir['row_no'] ?></td>
          <td><?= crm360_h(m360_rui_label((string)($ir['validation_status'] ?? ''))) ?></td>
          <td><?= crm360_h((string)($ir['validation_message'] ?? '')) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </section>
  </div>

<?php elseif ($tab === 'vip'): ?>
  <h2 class="c360-layer-title">VIP و باشگاه مشتریان</h2>
  <section class="c360-status-grid">
    <div class="c360-status-card"><span>نامزد VIP</span><strong><?= count($vipCandidates) ?></strong></div>
    <div class="c360-status-card"><span>در انتظار تأیید</span><strong><?= count($vipPending) ?></strong></div>
    <div class="c360-status-card"><span>مشتریان VIP</span><strong><?= count($vipCustomers) ?></strong></div>
    <div class="c360-status-card"><span>قوانین فعال</span><strong><?= count($vipRules) ?></strong></div>
  </section>
  <nav class="c360-filters">
    <a href="?tab=vip" class="<?= $panel === '' ? 'is-active' : '' ?>">VIP</a>
    <a href="?tab=vip&panel=club" class="<?= $panel === 'club' ? 'is-active' : '' ?>">باشگاه</a>
  </nav>
  <?php if ($panel === 'club'): ?>
    <?php $clubList = m360_rui_paginate(m360_rui_sort_rows($clubs, 'club_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
    <section class="c360-panel">
      <h2>باشگاه مشتریان</h2>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>مشتری</th><th>سطح</th><th>امتیاز</th><th>بازدید</th><th>ریسک</th></tr></thead>
        <tbody><?php foreach ($clubList['rows'] as $cl): ?><tr>
          <td><?= crm360_h((string)$cl['full_name']) ?></td>
          <td><?= crm360_h(m360_rui_label((string)$cl['tier_code'])) ?></td>
          <td><?= (int)$cl['points_balance'] ?></td>
          <td><?= (int)$cl['visit_count'] ?></td>
          <td><?= crm360_h(m360_rui_label((string)$cl['churn_risk_level'])) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($clubList, m360_rui_query_keep(['tab' => 'vip', 'panel' => 'club'], ['list_page']), 'list_page'); ?>
    </section>
  <?php else: ?>
    <?php
      $candList = m360_rui_paginate($vipCandidates, max(1, (int)($_GET['cand_page'] ?? 1)), 10);
      $pendList = m360_rui_paginate($vipPending, max(1, (int)($_GET['pend_page'] ?? 1)), 10);
      $vipList = m360_rui_paginate($vipCustomers, max(1, (int)($_GET['vip_page'] ?? 1)), 10);
      $ruleList = m360_rui_paginate($vipRules, max(1, (int)($_GET['rule_page'] ?? 1)), 10);
    ?>
    <div class="c360-grid2">
      <?php if (crm360_can('vip_nominate', $auth)): ?>
      <section class="c360-panel">
        <h2>معرفی VIP</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php">
          <?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="vip_nominate">
          <input type="hidden" name="return_tab" value="vip">
          <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
          <label>سطح<select name="requested_tier"><option value="VIP">VIP</option><option value="GOLD">طلایی</option><option value="PLATINUM">پلاتین</option></select></label>
          <label>دلیل<textarea name="reason" required rows="2"></textarea></label>
          <button type="submit" class="c360-btn primary">ثبت معرفی</button>
        </form>
      </section>
      <?php endif; ?>
      <?php if (crm360_can('vip_approve', $auth)): ?>
      <section class="c360-panel">
        <h2>بررسی درخواست</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php">
          <?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="vip_review">
          <input type="hidden" name="return_tab" value="vip">
          <label>شناسه درخواست<input name="vip_request_id" type="number" required></label>
          <label>تصمیم<select name="decision"><option value="APPROVED">تأیید</option><option value="REJECTED">رد</option></select></label>
          <label>یادداشت<textarea name="review_note" rows="2"></textarea></label>
          <button type="submit" class="c360-btn">ثبت</button>
        </form>
      </section>
      <?php endif; ?>
    </div>
    <section class="c360-panel">
      <h2>نامزدهای VIP</h2>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>مشتری</th><th>موبایل</th><th>مراجعه</th><th>دلیل پیشنهادی</th></tr></thead>
        <tbody><?php foreach ($candList['rows'] as $vc): ?><tr>
          <td><?= crm360_h((string)$vc['full_name']) ?></td>
          <td><?= crm360_h((string)($vc['mobile'] ?? '')) ?></td>
          <td><?= (int)($vc['visit_count'] ?? 0) ?></td>
          <td><?= crm360_h((string)($vc['suggested_reason'] ?? '')) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($candList, m360_rui_query_keep(['tab' => 'vip'], ['cand_page']), 'cand_page'); ?>
    </section>
    <section class="c360-panel">
      <h2>درخواست‌های در انتظار تأیید</h2>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>شناسه</th><th>مشتری</th><th>سطح</th><th>دلیل</th><th>تاریخ</th></tr></thead>
        <tbody><?php foreach ($pendList['rows'] as $vp): ?><tr>
          <td><?= (int)$vp['vip_request_id'] ?></td>
          <td><?= crm360_h((string)$vp['full_name']) ?></td>
          <td><?= crm360_h(m360_rui_label((string)($vp['requested_tier'] ?? ''))) ?></td>
          <td><?= crm360_h((string)($vp['reason'] ?? '')) ?></td>
          <td><?= crm360_h(m360_rui_jalali_date((string)($vp['created_at'] ?? ''))) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($pendList, m360_rui_query_keep(['tab' => 'vip'], ['pend_page']), 'pend_page'); ?>
    </section>
    <section class="c360-panel">
      <h2>مشتریان VIP</h2>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>نام</th><th>موبایل</th><th>سطح</th></tr></thead>
        <tbody><?php foreach ($vipList['rows'] as $vc): ?><tr>
          <td><?= crm360_h((string)$vc['full_name']) ?></td>
          <td><?= crm360_h((string)($vc['mobile'] ?? '')) ?></td>
          <td><?= crm360_h(m360_rui_label((string)($vc['vip_level'] ?? ''))) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($vipList, m360_rui_query_keep(['tab' => 'vip'], ['vip_page']), 'vip_page'); ?>
    </section>
    <?php if (crm360_can('vip_rules', $auth)): ?>
    <section class="c360-panel">
      <h2>قوانین VIP</h2>
      <div class="c360-table-wrap"><table class="c360-table">
        <thead><tr><th>عنوان</th><th>نوع</th><th>آستانه</th><th>فعال</th><th>به‌روز</th></tr></thead>
        <tbody><?php foreach ($ruleList['rows'] as $vr): ?><tr>
          <td><?= crm360_h((string)$vr['rule_title']) ?></td>
          <td><?= crm360_h(m360_rui_label((string)$vr['rule_type'])) ?></td>
          <td>
            <form class="c360-inline-form" method="post" action="erp-crm-action.php">
              <?= crm360_csrf_field() ?>
              <input type="hidden" name="action" value="vip_rule_update">
              <input type="hidden" name="return_tab" value="vip">
              <input type="hidden" name="vip_rule_id" value="<?= (int)$vr['vip_rule_id'] ?>">
              <input type="hidden" name="rule_title" value="<?= crm360_h((string)$vr['rule_title']) ?>">
              <input name="threshold_value" value="<?= crm360_h((string)($vr['threshold_value'] ?? '')) ?>" style="width:80px">
              <label><input type="checkbox" name="is_active" value="1" <?= ((int)($vr['is_active'] ?? 0)) ? 'checked' : '' ?>> فعال</label>
              <button type="submit" class="c360-btn">ذخیره</button>
            </form>
          </td>
          <td><?= ((int)($vr['is_active'] ?? 0)) ? 'بله' : 'خیر' ?></td>
          <td><?= crm360_h(m360_rui_jalali_date((string)($vr['updated_at'] ?? ''))) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
      <?php m360_rui_render_pagination($ruleList, m360_rui_query_keep(['tab' => 'vip'], ['rule_page']), 'rule_page'); ?>
    </section>
    <?php endif; ?>
  <?php endif; ?>

<?php elseif ($tab === 'experience'): ?>
  <h2 class="c360-layer-title">تجربه و پیگیری</h2>
  <nav class="c360-filters">
    <a href="?tab=experience" class="<?= $panel === '' ? 'is-active' : '' ?>">همه</a>
    <?php foreach ($experiencePanels as $pk => $pinfo): ?>
      <a href="?tab=experience&panel=<?= crm360_h($pk) ?>" class="<?= $panel === $pk ? 'is-active' : '' ?>"><?= crm360_h($pinfo['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php if ($panel === ''): ?>
    <div class="c360-hub-grid">
      <?php foreach ($experiencePanels as $pk => $pinfo): ?>
        <a class="c360-hub-card" href="?tab=experience&panel=<?= crm360_h($pk) ?>">
          <strong><?= crm360_h($pinfo['label']) ?></strong>
          <em class="c360-hub-meta"><?= (int)$pinfo['count'] ?></em>
          <span class="c360-hub-go">ورود</span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php elseif ($panel === 'cartable'): ?>
    <?php $cbList = m360_rui_paginate(m360_rui_sort_rows($cartable, 'cartable_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
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
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>مشتری</th><th>عنوان</th><th>وضعیت</th><th>اولویت</th><th>سررسید</th><th>بستن</th></tr></thead>
          <tbody><?php foreach ($cbList['rows'] as $cb): ?><tr>
            <td><?= crm360_h((string)$cb['full_name']) ?></td>
            <td><?= crm360_h((string)$cb['item_title']) ?></td>
            <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)$cb['item_status'])) ?></span></td>
            <td><?= crm360_h(m360_rui_label((string)$cb['priority_code'])) ?></td>
            <td><?= crm360_h(m360_rui_jalali_date((string)($cb['due_date'] ?? ''), false)) ?></td>
            <td><?php if (($cb['item_status'] ?? '') === 'OPEN'): ?><form method="post" action="erp-crm-action.php" class="c360-inline-form"><?= crm360_csrf_field() ?>
              <input type="hidden" name="action" value="cartable_done"><input type="hidden" name="return_tab" value="cartable">
              <input type="hidden" name="cartable_id" value="<?= (int)$cb['cartable_id'] ?>"><button type="submit" class="c360-btn">انجام شد</button></form><?php endif; ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($cbList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'cartable'], ['list_page']), 'list_page'); ?>
      </section>
    </div>
  <?php elseif ($panel === 'satisfaction'): ?>
    <?php $satList = m360_rui_paginate(m360_rui_sort_rows($surveys, 'survey_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
    <div class="c360-grid2">
      <section class="c360-panel">
        <h2>ایجاد نظرسنجی</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="create_survey"><input type="hidden" name="return_tab" value="satisfaction">
          <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
          <label>پرونده<select name="case_id"><option value="">—</option><?= $caseOpts ?></select></label>
          <button type="submit" class="c360-btn">پیش‌نویس</button></form>
        <h3 style="margin-top:1rem">ثبت امتیاز</h3>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="submit_survey"><input type="hidden" name="return_tab" value="satisfaction">
          <label>شناسه<input name="survey_id" type="number" required></label>
          <label>امتیاز<input name="overall_score" type="number" min="1" max="10" required></label>
          <label>NPS<input name="nps_score" type="number" min="0" max="10"></label>
          <label>نظر<textarea name="comment" rows="2"></textarea></label>
          <label><input type="checkbox" name="create_complaint" value="1"> شکایت اگر ≤۳</label>
          <button type="submit" class="c360-btn primary">ثبت</button></form>
      </section>
      <section class="c360-panel">
        <h2>نظرسنجی‌ها</h2>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>شناسه</th><th>مشتری</th><th>امتیاز</th><th>وضعیت</th></tr></thead>
          <tbody><?php foreach ($satList['rows'] as $s): ?><tr>
            <td><?= (int)$s['survey_id'] ?></td><td><?= crm360_h((string)$s['full_name']) ?></td>
            <td><?= (int)$s['overall_score'] ?></td>
            <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)$s['survey_status'])) ?></span></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($satList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'satisfaction'], ['list_page']), 'list_page'); ?>
      </section>
    </div>
  <?php elseif ($panel === 'complaints'): ?>
    <?php $cpList = m360_rui_paginate(m360_rui_sort_rows($complaints, 'complaint_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
    <div class="c360-grid2">
      <section class="c360-panel">
        <h2>ثبت شکایت</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="create_complaint"><input type="hidden" name="return_tab" value="complaints">
          <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
          <label>عنوان<input name="title" required></label>
          <label>شرح<textarea name="description" required rows="3"></textarea></label>
          <label>نوع<select name="complaint_type"><option value="SERVICE">خدمات</option><option value="PRICE">قیمت</option><option value="DELAY">تأخیر</option></select></label>
          <label>شدت<select name="severity"><option value="LOW">کم</option><option value="MEDIUM" selected>متوسط</option><option value="HIGH">بالا</option></select></label>
          <button type="submit" class="c360-btn primary">ثبت</button></form>
      </section>
      <section class="c360-panel">
        <h2>شکایات</h2>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>کد</th><th>مشتری</th><th>عنوان</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
          <tbody><?php foreach ($cpList['rows'] as $cp): ?><tr>
            <td><?= crm360_h((string)$cp['complaint_code']) ?></td>
            <td><?= crm360_h((string)$cp['full_name']) ?></td>
            <td><?= crm360_h((string)$cp['title']) ?></td>
            <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)$cp['complaint_status'])) ?></span></td>
            <td><form class="c360-inline-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
              <input type="hidden" name="action" value="update_complaint_status"><input type="hidden" name="return_tab" value="complaints">
              <input type="hidden" name="complaint_id" value="<?= (int)$cp['complaint_id'] ?>">
              <select name="complaint_status"><option value="OPEN">باز</option><option value="IN_PROGRESS">در حال تکمیل</option><option value="CLOSED">بسته</option></select>
              <button type="submit" class="c360-btn">ثبت</button></form></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($cpList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'complaints'], ['list_page']), 'list_page'); ?>
      </section>
    </div>
  <?php elseif ($panel === 'reminders'): ?>
    <?php $remList = m360_rui_paginate(m360_rui_sort_rows($reminders, 'reminder_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
    <div class="c360-grid2">
      <section class="c360-panel">
        <h2>یادآوری سرویس</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="create_reminder"><input type="hidden" name="return_tab" value="reminders">
          <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
          <label>خودرو<select name="vehicle_profile_id" required><?= $vehOpts ?></select></label>
          <label>عنوان<input name="reminder_title" required></label>
          <label>نوع<select name="reminder_type"><option value="SERVICE">سرویس</option><option value="INSPECTION">بازدید</option></select></label>
          <label>سررسید<input name="due_date" type="date"></label>
          <button type="submit" class="c360-btn primary">ایجاد</button></form>
      </section>
      <section class="c360-panel">
        <h2>یادآوری‌ها</h2>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>مشتری</th><th>خودرو</th><th>عنوان</th><th>سررسید</th><th>وضعیت</th></tr></thead>
          <tbody><?php foreach ($remList['rows'] as $r): ?><tr>
            <td><?= crm360_h((string)$r['full_name']) ?></td>
            <td><?= crm360_h((string)$r['brand']) ?> <?= crm360_h((string)$r['model']) ?></td>
            <td><?= crm360_h((string)$r['reminder_title']) ?></td>
            <td><?= crm360_h(m360_rui_jalali_date((string)($r['due_date'] ?? ''), false)) ?></td>
            <td><span class="c360-status"><?= crm360_h(m360_rui_label((string)$r['reminder_status'])) ?></span></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($remList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'reminders'], ['list_page']), 'list_page'); ?>
      </section>
    </div>
  <?php elseif ($panel === 'returns'): ?>
    <?php $retList = m360_rui_paginate(m360_rui_sort_rows($returns, 'return_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10); ?>
    <div class="c360-grid2">
      <section class="c360-panel">
        <h2>بازگشت مشتری</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="create_return"><input type="hidden" name="return_tab" value="returns">
          <label>مشتری<select name="customer_profile_id" required><?= $custOpts ?></select></label>
          <label>دلیل<select name="reason_code"><option value="CHURN">ریزش</option><option value="INACTIVE">غیرفعال</option></select></label>
          <label>مسئول<input name="assigned_to"></label>
          <button type="submit" class="c360-btn primary">ثبت</button></form>
      </section>
      <section class="c360-panel">
        <h2>خط لوله</h2>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>مشتری</th><th>مرحله</th><th>نتیجه</th></tr></thead>
          <tbody><?php foreach ($retList['rows'] as $ret): ?><tr>
            <td><?= crm360_h((string)$ret['full_name']) ?></td>
            <td><?= crm360_h(m360_rui_label((string)$ret['return_stage'])) ?></td>
            <td><?= crm360_h(m360_rui_label((string)$ret['result_status'])) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($retList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'returns'], ['list_page']), 'list_page'); ?>
      </section>
    </div>
  <?php elseif ($panel === 'promotions'): ?>
    <?php
      $promoList = m360_rui_paginate(m360_rui_sort_rows($promotions, 'promotion_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10);
      $asgList = m360_rui_paginate(m360_rui_sort_rows($assignments, 'assignment_id', 'desc'), max(1, (int)($_GET['asg_page'] ?? 1)), 10);
    ?>
    <div class="c360-grid2">
      <section class="c360-panel">
        <h2>ایجاد پروموشن</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="create_promotion"><input type="hidden" name="return_tab" value="promotions">
          <label>عنوان<input name="title" required></label>
          <label>نوع<select name="promotion_type"><option value="DISCOUNT">تخفیف</option><option value="PACKAGE">پکیج</option></select></label>
          <label>شروع<input name="start_date" type="date" value="<?= date('Y-m-d') ?>"></label>
          <label>پایان<input name="end_date" type="date"></label>
          <button type="submit" class="c360-btn primary">ایجاد</button></form>
      </section>
      <section class="c360-panel">
        <h2>پروموشن‌ها</h2>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>کد</th><th>عنوان</th><th>وضعیت</th><th>شروع</th></tr></thead>
          <tbody><?php foreach ($promoList['rows'] as $p): ?><tr>
            <td><?= crm360_h((string)$p['promotion_code']) ?></td>
            <td><?= crm360_h((string)$p['title']) ?></td>
            <td><?= crm360_h(m360_rui_label((string)$p['promotion_status'])) ?></td>
            <td><?= crm360_h(m360_rui_jalali_date((string)$p['start_date'], false)) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($promoList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'promotions'], ['list_page', 'asg_page']), 'list_page'); ?>
        <h3 style="margin-top:1rem">تخصیص‌ها</h3>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>پروموشن</th><th>مشتری</th><th>وضعیت</th></tr></thead>
          <tbody><?php foreach ($asgList['rows'] as $pa): ?><tr>
            <td><?= crm360_h((string)$pa['title']) ?></td>
            <td><?= crm360_h((string)$pa['full_name']) ?></td>
            <td><?= crm360_h(m360_rui_label((string)$pa['assignment_status'])) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($asgList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'promotions'], ['asg_page', 'list_page']), 'asg_page'); ?>
      </section>
    </div>
  <?php elseif ($panel === 'sms'): ?>
    <?php
      $smsList = m360_rui_paginate(m360_rui_sort_rows($campaigns, 'campaign_id', 'desc'), max(1, (int)($_GET['list_page'] ?? 1)), 10);
      $rcpList = m360_rui_paginate(m360_rui_sort_rows($recipients, 'recipient_id', 'desc'), max(1, (int)($_GET['rcp_page'] ?? 1)), 10);
    ?>
    <div class="c360-grid2">
      <section class="c360-panel">
        <h2>کمپین پیامکی</h2>
        <form class="c360-form" method="post" action="erp-crm-action.php"><?= crm360_csrf_field() ?>
          <input type="hidden" name="action" value="create_sms_campaign"><input type="hidden" name="return_tab" value="sms">
          <label>عنوان<input name="title" required></label>
          <label>متن<textarea name="message_text" required rows="3"></textarea></label>
          <button type="submit" class="c360-btn primary">ایجاد</button></form>
      </section>
      <section class="c360-panel">
        <h2>کمپین‌ها</h2>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>کد</th><th>عنوان</th><th>وضعیت</th></tr></thead>
          <tbody><?php foreach ($smsList['rows'] as $camp): ?><tr>
            <td><?= crm360_h((string)$camp['campaign_code']) ?></td>
            <td><?= crm360_h((string)$camp['title']) ?></td>
            <td><?= crm360_h(m360_rui_label((string)$camp['campaign_status'])) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($smsList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'sms'], ['list_page', 'rcp_page']), 'list_page'); ?>
        <h3 style="margin-top:1rem">گیرندگان</h3>
        <div class="c360-table-wrap"><table class="c360-table">
          <thead><tr><th>کمپین</th><th>موبایل</th><th>وضعیت</th></tr></thead>
          <tbody><?php foreach ($rcpList['rows'] as $rc): ?><tr>
            <td><?= crm360_h((string)$rc['campaign_title']) ?></td>
            <td><?= crm360_h((string)$rc['mobile']) ?></td>
            <td><?= crm360_h(m360_rui_label((string)$rc['recipient_status'])) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php m360_rui_render_pagination($rcpList, m360_rui_query_keep(['tab' => 'experience', 'panel' => 'sms'], ['rcp_page', 'list_page']), 'rcp_page'); ?>
      </section>
    </div>
  <?php endif; ?>
<?php elseif ($tab === 'audit'): ?>
  <h2 class="c360-layer-title">گزارش و Audit</h2>
  <?php
    $listPage = max(1, (int)($_GET['list_page'] ?? 1));
    $auditList = m360_rui_paginate(m360_rui_sort_rows($audits, 'audit_id', 'desc'), $listPage, 10);
  ?>
  <section class="c360-panel">
    <h2>Audit CRM360</h2>
    <div class="c360-table-wrap">
      <table class="c360-table">
        <thead><tr><th>زمان</th><th>کاربر</th><th>عمل</th><th>موجودیت</th><th>شناسه</th><th>صفحه</th></tr></thead>
        <tbody>
        <?php foreach ($auditList['rows'] as $a): ?>
          <tr>
            <td><?= crm360_h(m360_rui_jalali_date((string)($a['event_time'] ?? ''))) ?></td>
            <td title="<?= crm360_h((string)$a['actor_user']) ?>"><?= crm360_h((string)$a['actor_user']) ?></td>
            <td title="<?= crm360_h((string)$a['action_code']) ?>"><?= crm360_h(m360_rui_label((string)$a['action_code'])) ?></td>
            <td><?= crm360_h((string)$a['entity_name']) ?></td>
            <td><?= crm360_h((string)($a['entity_id'] ?? '')) ?></td>
            <td title="<?= crm360_h((string)($a['source_page'] ?? '')) ?>"><?= crm360_h((string)($a['source_page'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php m360_rui_render_pagination($auditList, m360_rui_query_keep(['tab' => 'audit'], ['list_page', 'page']), 'list_page'); ?>
  </section>
<?php endif; ?>

</div>
<?= crm360_search_picker_script() ?>
</body>
</html>
