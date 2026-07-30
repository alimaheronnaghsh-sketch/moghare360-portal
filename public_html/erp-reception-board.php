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
    'docs_missing' => 0, 'cartable_open' => 0, 'complaints_open' => 0,
    'surveys_low' => 0, 'reminders_due' => 0, 'returns_open' => 0,
    'promos_active' => 0, 'sms_draft' => 0, 'online_requests' => 0,
    'avg_completion' => 0,
];
$customers = $vehicles = $cases = $documents = $cartable = $surveys = $complaints = $clubs = $reminders = $returns = $promotions = $assignments = $campaigns = $recipients = $audits = $onlineRequests = [];

if ($dbOk && $conn) {
    $kpi['customers'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_customer_profiles') ?? 0);
    $kpi['vehicles'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_vehicle_profiles') ?? 0);
    $kpi['cases'] = (int)(crm360_scalar($conn, 'SELECT COUNT(*) FROM dbo.crm360_reception_cases') ?? 0);
    $kpi['cases_draft'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_reception_cases WHERE case_status IN ('DRAFT','IN_PROGRESS')") ?? 0);
    $kpi['docs_missing'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_case_documents WHERE document_status='MISSING'") ?? 0);
    $kpi['cartable_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_customer_cartable WHERE item_status='OPEN'") ?? 0);
    $kpi['complaints_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_complaints WHERE complaint_status='OPEN'") ?? 0);
    $kpi['surveys_low'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_satisfaction_surveys WHERE overall_score<=3 AND overall_score>0") ?? 0);
    $kpi['reminders_due'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_service_reminders WHERE reminder_status IN ('SCHEDULED','NEEDS_FOLLOWUP') AND due_date<=CAST(GETDATE() AS DATE)") ?? 0);
    $kpi['returns_open'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_return_pipeline WHERE result_status='OPEN'") ?? 0);
    $kpi['promos_active'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_promotions WHERE promotion_status='ACTIVE'") ?? 0);
    $kpi['sms_draft'] = (int)(crm360_scalar($conn, "SELECT COUNT(*) FROM dbo.crm360_sms_campaigns WHERE campaign_status='DRAFT'") ?? 0);
    $kpi['avg_completion'] = (int)(crm360_scalar($conn, 'SELECT ISNULL(AVG(profile_completion_percent),0) FROM dbo.crm360_reception_cases') ?? 0);
    $onlineRequests = crm360_online_requests($conn, 30);
    $kpi['online_requests'] = count($onlineRequests);

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

$completionPct = $kpi['avg_completion'];
$cartPct = $kpi['cartable_open'] > 0 ? max(20, 100 - min(80, $kpi['cartable_open'] * 5)) : 90;
$docPct = $kpi['docs_missing'] > 0 ? max(10, 100 - min(90, $kpi['docs_missing'] * 3)) : 95;
$satPct = $kpi['surveys_low'] > 0 ? max(15, 100 - $kpi['surveys_low'] * 10) : 88;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مرکز پذیرش و CRM — MOGHARE360</title>
<link rel="stylesheet" href="assets/css/m360-suite-theme.css">
<link rel="stylesheet" href="assets/css/m360-crm.css">
</head>
<body class="c360-body">
<div class="c360-wrap">
  <div class="c360-crumb"><a href="personnel.html">پرسنل</a> / <a href="personnel.html">CRM و پذیرش</a> / مرکز پذیرش</div>
  <header class="c360-head">
    <div>
      <h1>مرکز پذیرش و CRM</h1>
      <p>پروفایل مشتری و خودرو، تکمیل پرونده پذیرش، مدارک، کارتابل، رضایت‌سنجی، شکایت، باشگاه، یادآوری، بازگشت مشتری، پروموشن و کمپین پیامکی</p>
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

  <div class="c360-actions">
    <a class="c360-btn primary" href="?tab=customers">ثبت مشتری</a>
    <a class="c360-btn" href="?tab=vehicles">ثبت خودرو</a>
    <a class="c360-btn" href="?tab=cases">ایجاد پرونده</a>
    <a class="c360-btn" href="?tab=documents">مدارک</a>
    <a class="c360-btn" href="?tab=cartable">کارتابل</a>
    <a class="c360-btn" href="?tab=satisfaction">رضایت‌سنجی</a>
    <a class="c360-btn" href="?tab=complaints">شکایت</a>
    <a class="c360-btn" href="?tab=sms">کمپین پیامک</a>
    <a class="c360-btn" href="?tab=audit">Audit</a>
    <a class="c360-btn" href="erp-reception-workbench.php">میز کار پذیرش</a>
  </div>

  <nav class="c360-tabs">
    <?php foreach ($tabs as $k => $label): ?>
      <a href="?tab=<?= crm360_h($k) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= crm360_h($label) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'dashboard'): ?>
  <section class="c360-kpi-grid">
    <?php
    $cards = [
        ['مشتریان', $kpi['customers'], false],
        ['خودروها', $kpi['vehicles'], false],
        ['پرونده‌های پذیرش', $kpi['cases'], false],
        ['پرونده ناقص', $kpi['cases_draft'], false],
        ['مدارک MISSING', $kpi['docs_missing'], false],
        ['کارتابل باز', $kpi['cartable_open'], false],
        ['شکایت باز', $kpi['complaints_open'], false],
        ['رضایت پایین', $kpi['surveys_low'], false],
        ['یادآوری سررسید', $kpi['reminders_due'], false],
        ['بازگشت مشتری', $kpi['returns_open'], false],
        ['درخواست آنلاین', $kpi['online_requests'], false],
        ['میانگین تکمیل', $kpi['avg_completion'] . '%', false],
    ];
    foreach ($cards as [$lbl, $val, $money]): ?>
      <div class="c360-kpi"><span><?= crm360_h($lbl) ?></span><strong><?= crm360_h((string)$val) ?></strong></div>
    <?php endforeach; ?>
  </section>
  <div class="c360-gauge-row">
    <?= crm360_gauge($completionPct, 'تکمیل پرونده') ?>
    <?= crm360_gauge($docPct, 'وضعیت مدارک', '#66bb6a') ?>
    <?= crm360_gauge($cartPct, 'کارتابل', '#e8b84a') ?>
    <?= crm360_gauge($satPct, 'رضایت مشتری', '#3ecf8e') ?>
  </div>
  <?php if ($onlineRequests): ?>
  <section class="c360-panel">
    <h2>درخواست‌های آنلاین (فقط خواندنی)</h2>
    <div class="c360-table-wrap">
      <table class="c360-table">
        <thead><tr><th>شناسه</th><th>موبایل</th><th>پلاک</th><th>وضعیت</th><th>تاریخ</th><th>اقدام</th></tr></thead>
        <tbody>
        <?php foreach ($onlineRequests as $or): ?>
          <tr>
            <td><?= crm360_h((string)$or['online_request_id']) ?></td>
            <td><?= crm360_h((string)($or['mobile'] ?? '')) ?></td>
            <td><?= crm360_h((string)($or['vehicle_plate'] ?? '')) ?></td>
            <td><span class="c360-status"><?= crm360_h((string)($or['request_status'] ?? '')) ?></span></td>
            <td><?= crm360_h((string)($or['created_at'] ?? '')) ?></td>
            <td><a class="c360-btn" href="?tab=cases&amp;req=<?= (int)$or['online_request_id'] ?>">ایجاد پرونده</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

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
