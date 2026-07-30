<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/fin360-finance-helper.php';
require_once __DIR__ . '/includes/fin360-posting-helper.php';

fin360_csrf_boot();
$actorInfo = fin360_actor();
$actor = $actorInfo['actor'];
$devMode = $actorInfo['dev_mode'];

$tabs = [
    'dashboard' => 'داشبورد مالی',
    'settlement' => 'تسویه JobCard',
    'receipts' => 'دریافت‌ها',
    'prepayments' => 'پیش‌دریافت و پیش‌پرداخت',
    'invoices' => 'فاکتور خدمات',
    'parts' => 'فاکتور قطعات',
    'sales' => 'فروش و درآمد',
    'purchases' => 'خرید و بدهی‌ها',
    'cashbank' => 'صندوق، بانک و چک',
    'ar' => 'مطالبات',
    'ap' => 'بدهی‌ها',
    'costing' => 'بهای تمام‌شده و سودآوری',
    'tax' => 'مالیات و سامانه مؤدیان',
    'pnl' => 'سود و زیان',
    'cashflow' => 'جریان نقد',
    'posting' => 'دفتر کل و Posting',
    'audit' => 'Audit مالی',
    'settings' => 'تنظیمات مالی',
];
$tab = preg_replace('/[^a-z_]/', '', (string)($_GET['tab'] ?? 'dashboard')) ?: 'dashboard';
if (!isset($tabs[$tab])) {
    $tab = 'dashboard';
}

$flash = $_SESSION['fin360_flash'] ?? null;
unset($_SESSION['fin360_flash']);

$dbOk = true;
$dbErr = '';
try {
    $conn = fin360_db();
} catch (Throwable $e) {
    $conn = null;
    $dbOk = false;
    $dbErr = 'اتصال به moghare360_ERP برقرار نشد.';
}

$kpi = [
    'cash' => 0, 'in_today' => 0, 'out_today' => 0, 'net_today' => 0,
    'ar_open' => 0, 'ap_open' => 0, 'jc_ready' => 0, 'jc_open' => 0,
    'svc_sales' => 0, 'parts_sales' => 0, 'gross' => 0, 'tax_review' => 0,
    'draft' => 0, 'posted' => 0, 'mismatch' => 0, 'override' => 0,
];
$parties = $cashAccounts = $docs = $settlements = $payments = $audits = $taxRules = $taxDocs = $journals = $accounts = $rules = [];
$agingAr = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd90p' => 0];
$agingAp = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd90p' => 0];
$pnl = ['rev' => 0, 'disc' => 0, 'cogs' => 0, 'opex' => 0];
$cf = ['open' => 0, 'in' => 0, 'out' => 0];

if ($dbOk && $conn) {
    $kpi['cash'] = (float)(fin360_scalar($conn, 'SELECT ISNULL(SUM(current_book_balance),0) FROM dbo.fin360_cash_accounts WHERE is_active=1') ?? 0);
    $kpi['in_today'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(amount),0) FROM dbo.fin360_payments WHERE payment_direction='IN' AND payment_date=CAST(GETDATE() AS DATE) AND status_code NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $kpi['out_today'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(amount),0) FROM dbo.fin360_payments WHERE payment_direction='OUT' AND payment_date=CAST(GETDATE() AS DATE) AND status_code NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $kpi['net_today'] = $kpi['in_today'] - $kpi['out_today'];
    $kpi['ar_open'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(remaining_amount),0) FROM dbo.fin360_documents WHERE document_type IN ('SERVICE_INVOICE','PARTS_INVOICE','JOBCARD_SETTLEMENT') AND document_status NOT IN ('CANCELLED','REVERSED','SETTLED','CLOSED') AND remaining_amount>0") ?? 0);
    $kpi['ap_open'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(d.remaining_amount),0) FROM dbo.fin360_documents d LEFT JOIN dbo.fin360_parties p ON p.party_id=d.party_id WHERE d.document_type IN ('PURCHASE_INVOICE','EXTERNAL_SERVICE_INVOICE','EXPENSE') AND d.document_status NOT IN ('CANCELLED','REVERSED','SETTLED','CLOSED') AND d.remaining_amount>0 AND (p.party_type IS NULL OR p.party_type <> 'CUSTOMER')") ?? 0);
    $kpi['jc_ready'] = (int)(fin360_scalar($conn, "SELECT COUNT(*) FROM dbo.fin360_jobcard_settlements WHERE settlement_status='READY_FOR_SETTLEMENT'") ?? 0);
    $kpi['jc_open'] = (int)(fin360_scalar($conn, "SELECT COUNT(*) FROM dbo.fin360_jobcard_settlements WHERE settlement_status IN ('PARTIALLY_SETTLED','READY_FOR_SETTLEMENT','NOT_READY')") ?? 0);
    $kpi['svc_sales'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(total_amount),0) FROM dbo.fin360_documents WHERE document_type='SERVICE_INVOICE' AND document_status NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $kpi['parts_sales'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(total_amount),0) FROM dbo.fin360_documents WHERE document_type='PARTS_INVOICE' AND document_status NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $costs = (float)(fin360_scalar($conn, 'SELECT ISNULL(SUM(cost_amount),0) FROM dbo.fin360_document_lines') ?? 0);
    $kpi['gross'] = ($kpi['svc_sales'] + $kpi['parts_sales']) - $costs;
    $kpi['tax_review'] = (int)(fin360_scalar($conn, "SELECT COUNT(*) FROM dbo.fin360_tax_documents WHERE submission_status IN ('NOT_READY','REJECTED','NEEDS_CORRECTION')") ?? 0);
    $kpi['draft'] = (int)(fin360_scalar($conn, "SELECT COUNT(*) FROM dbo.fin360_documents WHERE document_status='DRAFT'") ?? 0);
    $kpi['posted'] = (int)(fin360_scalar($conn, "SELECT COUNT(*) FROM dbo.fin360_documents WHERE document_status='POSTED'") ?? 0);
    $kpi['mismatch'] = (int)(fin360_scalar($conn, 'SELECT COUNT(*) FROM dbo.fin360_journal_headers WHERE ABS(total_debit-total_credit)>0.01') ?? 0);
    $kpi['override'] = (int)(fin360_scalar($conn, "SELECT COUNT(*) FROM dbo.fin360_jobcard_settlements WHERE settlement_status='SETTLED_WITH_OVERRIDE'") ?? 0);

    $parties = fin360_rows($conn, 'SELECT TOP 200 * FROM dbo.fin360_parties WHERE is_active=1 ORDER BY party_id DESC');
    $cashAccounts = fin360_rows($conn, 'SELECT * FROM dbo.fin360_cash_accounts WHERE is_active=1 ORDER BY cash_account_id');
    $docs = fin360_rows($conn, 'SELECT TOP 100 d.*, p.display_name, p.party_type FROM dbo.fin360_documents d LEFT JOIN dbo.fin360_parties p ON p.party_id=d.party_id ORDER BY d.document_id DESC');
    $settlements = fin360_rows($conn, 'SELECT TOP 50 s.*, p.display_name, p.party_type FROM dbo.fin360_jobcard_settlements s LEFT JOIN dbo.fin360_parties p ON p.party_id=s.customer_party_id ORDER BY s.settlement_id DESC');
    $payments = fin360_rows($conn, 'SELECT TOP 100 pay.*, p.display_name, p.party_type, c.account_title FROM dbo.fin360_payments pay LEFT JOIN dbo.fin360_parties p ON p.party_id=pay.party_id LEFT JOIN dbo.fin360_cash_accounts c ON c.cash_account_id=pay.cash_account_id ORDER BY pay.payment_id DESC');
    $audits = fin360_rows($conn, 'SELECT TOP 100 * FROM dbo.fin360_audit_log ORDER BY audit_id DESC');
    $taxRules = fin360_rows($conn, 'SELECT TOP 50 * FROM dbo.fin360_tax_rules ORDER BY tax_rule_id DESC');
    $taxDocs = fin360_rows($conn, 'SELECT TOP 50 td.*, d.document_code FROM dbo.fin360_tax_documents td LEFT JOIN dbo.fin360_documents d ON d.document_id=td.document_id ORDER BY td.tax_document_id DESC');
    $journals = fin360_rows($conn, 'SELECT TOP 50 * FROM dbo.fin360_journal_headers ORDER BY journal_id DESC');
    $accounts = fin360_rows($conn, 'SELECT * FROM dbo.fin360_accounts WHERE is_active=1 ORDER BY account_code');
    $rules = fin360_rows($conn, 'SELECT r.*, da.account_code AS debit_code, ca.account_code AS credit_code FROM dbo.fin360_posting_rules r LEFT JOIN dbo.fin360_accounts da ON da.account_id=r.debit_account_id LEFT JOIN dbo.fin360_accounts ca ON ca.account_id=r.credit_account_id WHERE r.is_active=1 ORDER BY r.posting_rule_id');

    foreach (fin360_rows($conn, "SELECT remaining_amount, DATEDIFF(day, document_date, GETDATE()) AS age_days FROM dbo.fin360_documents WHERE document_type IN ('SERVICE_INVOICE','PARTS_INVOICE','JOBCARD_SETTLEMENT') AND remaining_amount>0 AND document_status NOT IN ('CANCELLED','REVERSED')") as $r) {
        $a = (int)$r['age_days'];
        $v = (float)$r['remaining_amount'];
        if ($a <= 0) {
            $agingAr['current'] += $v;
        } elseif ($a <= 30) {
            $agingAr['d30'] += $v;
        } elseif ($a <= 60) {
            $agingAr['d60'] += $v;
        } elseif ($a <= 90) {
            $agingAr['d90'] += $v;
        } else {
            $agingAr['d90p'] += $v;
        }
    }
    foreach (fin360_rows($conn, "SELECT d.remaining_amount, DATEDIFF(day, d.document_date, GETDATE()) AS age_days FROM dbo.fin360_documents d LEFT JOIN dbo.fin360_parties p ON p.party_id=d.party_id WHERE d.document_type IN ('PURCHASE_INVOICE','EXTERNAL_SERVICE_INVOICE','EXPENSE') AND d.remaining_amount>0 AND d.document_status NOT IN ('CANCELLED','REVERSED') AND (p.party_type IS NULL OR p.party_type IN ('SUPPLIER','CONTRACTOR','EMPLOYEE','OWNER','PARTNER','TAX_ORG','SOCIAL_SECURITY','BANK','BROKER','COURIER','SERVICE_PROVIDER','EXPENSE_PERSON','GOVERNMENT','OTHER'))") as $r) {
        $a = (int)$r['age_days'];
        $v = (float)$r['remaining_amount'];
        if ($a <= 0) {
            $agingAp['current'] += $v;
        } elseif ($a <= 30) {
            $agingAp['d30'] += $v;
        } elseif ($a <= 60) {
            $agingAp['d60'] += $v;
        } elseif ($a <= 90) {
            $agingAp['d90'] += $v;
        } else {
            $agingAp['d90p'] += $v;
        }
    }
    $pnl['rev'] = $kpi['svc_sales'] + $kpi['parts_sales'];
    $pnl['disc'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(discount_amount),0) FROM dbo.fin360_documents WHERE document_status NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $pnl['cogs'] = $costs + (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(total_amount),0) FROM dbo.fin360_documents WHERE document_type='PURCHASE_INVOICE' AND document_status NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $pnl['opex'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(total_amount),0) FROM dbo.fin360_documents WHERE document_type='EXPENSE' AND document_status NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $cf['open'] = (float)(fin360_scalar($conn, 'SELECT ISNULL(SUM(opening_balance),0) FROM dbo.fin360_cash_accounts WHERE is_active=1') ?? 0);
    $cf['in'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(amount),0) FROM dbo.fin360_payments WHERE payment_direction='IN' AND status_code NOT IN ('CANCELLED','REVERSED')") ?? 0);
    $cf['out'] = (float)(fin360_scalar($conn, "SELECT ISNULL(SUM(amount),0) FROM dbo.fin360_payments WHERE payment_direction='OUT' AND status_code NOT IN ('CANCELLED','REVERSED')") ?? 0);
}

function f360_gauge(int $pct, string $label, string $color = '#3ecf8e'): string
{
    $pct = max(0, min(100, $pct));
    $c = 2 * M_PI * 45;
    $off = $c * (1 - $pct / 100);
    return '<div class="f360-gauge-card"><div class="f360-gauge"><svg viewBox="0 0 100 100"><circle class="f360-gauge-track" cx="50" cy="50" r="45"/><circle class="f360-gauge-value" cx="50" cy="50" r="45" stroke="' . fin360_h($color) . '" stroke-dasharray="' . $c . '" stroke-dashoffset="' . $off . '"/></svg><div class="f360-gauge-center"><strong>' . $pct . '%</strong><span>وضعیت</span></div></div><h4>' . fin360_h($label) . '</h4></div>';
}

function f360_party_options(array $parties, string $type = ''): string
{
    $html = '<option value="">— انتخاب طرف حساب موجود —</option>';
    foreach ($parties as $p) {
        if ($type !== '' && strtoupper((string)$p['party_type']) !== $type) {
            continue;
        }
        $label = fin360_party_type_label((string)$p['party_type']);
        $html .= '<option value="' . (int)$p['party_id'] . '">' . fin360_h((string)$p['display_name']) . ' — ' . fin360_h($label) . '</option>';
    }
    return $html;
}

function f360_kpi_value($val, bool $isMoney): string
{
    if ($isMoney) {
        return fin360_money($val);
    }
    return (string)(int)$val;
}

function f360_cash_options(array $cashAccounts): string
{
    $html = '<option value="">— انتخاب —</option>';
    foreach ($cashAccounts as $c) {
        $html .= '<option value="' . (int)$c['cash_account_id'] . '">' . fin360_h((string)$c['account_title']) . ' — ' . fin360_money($c['current_book_balance']) . '</option>';
    }
    return $html;
}

$liqPct = $kpi['cash'] > 0 ? min(100, (int)round(($kpi['cash'] / max($kpi['cash'] + $kpi['ap_open'], 1)) * 100)) : 0;
$arPct = ($kpi['ar_open'] + $kpi['svc_sales'] + $kpi['parts_sales']) > 0 ? min(100, (int)round((1 - $kpi['ar_open'] / max($kpi['svc_sales'] + $kpi['parts_sales'] + $kpi['ar_open'], 1)) * 100)) : 50;
$apPct = $kpi['ap_open'] > 0 ? max(10, 100 - min(90, (int)($agingAp['d90p'] > 0 ? 80 : 40))) : 85;
$jcPct = ($kpi['jc_ready'] + $kpi['jc_open']) > 0 ? (int)round(($kpi['jc_ready'] / max($kpi['jc_ready'] + $kpi['jc_open'], 1)) * 100) : 40;
$taxPct = count($taxRules) > 0 ? 70 : 15;
$docPct = $kpi['posted'] + $kpi['draft'] > 0 ? (int)round(($kpi['posted'] / max($kpi['posted'] + $kpi['draft'] + $kpi['mismatch'], 1)) * 100) : 50;
$netSales = $pnl['rev'] - $pnl['disc'];
$gross = $netSales - $pnl['cogs'];
$opProfit = $gross - $pnl['opex'];
$cfNet = $cf['in'] - $cf['out'];
$cfClose = $cf['open'] + $cfNet;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مرکز مالی و تسویه MOGHARE360</title>
<link rel="stylesheet" href="assets/css/m360-suite-theme.css">
<link rel="stylesheet" href="assets/css/m360-finance.css">
</head>
<body class="f360-body">
<div class="f360-wrap">
  <div class="f360-crumb"><a href="personnel.html">پرسنل</a> / مالی / مرکز مالی و تسویه</div>
  <header class="f360-head">
    <div>
      <h1>مرکز مالی و تسویه MOGHARE360</h1>
      <p>داشبورد مالی، تسویه JobCard، دریافت‌ها، پرداخت‌ها، صندوق و بانک، مطالبات، بدهی‌ها و Audit مالی</p>
    </div>
    <div>
      <?php if ($devMode): ?><span class="f360-badge">حالت توسعه محلی</span><?php endif; ?>
      <div class="f360-muted" style="margin-top:.4rem;font-size:.75rem">actor: <?= fin360_h($actor) ?></div>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="f360-flash <?= fin360_h($flash['type']) ?>"><?= fin360_h($flash['msg']) ?></div>
  <?php endif; ?>
  <?php if (!$dbOk): ?>
    <div class="f360-flash err"><?= fin360_h($dbErr) ?></div>
  <?php endif; ?>

  <div class="f360-actions">
    <a class="f360-btn primary" href="?tab=receipts">ثبت دریافت</a>
    <a class="f360-btn" href="?tab=prepayments">ثبت پیش‌دریافت</a>
    <a class="f360-btn" href="?tab=settlement">ایجاد تسویه JobCard</a>
    <a class="f360-btn" href="?tab=invoices">ثبت فاکتور خدمات</a>
    <a class="f360-btn" href="?tab=parts">ثبت فاکتور قطعات</a>
    <a class="f360-btn" href="?tab=purchases">ثبت خرید / بدهی</a>
    <a class="f360-btn" href="?tab=purchases#pay">ثبت پرداخت</a>
    <a class="f360-btn" href="?tab=audit">مشاهده Audit</a>
    <a class="f360-btn" href="?tab=pnl">مشاهده P&amp;L</a>
    <a class="f360-btn" href="?tab=cashflow">مشاهده جریان نقد</a>
  </div>

  <nav class="f360-tabs">
    <?php foreach ($tabs as $k => $label): ?>
      <a href="?tab=<?= fin360_h($k) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= fin360_h($label) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'dashboard'): ?>
  <section class="f360-kpi-grid">
    <?php
    $cards = [
        ['نقد قابل استفاده', $kpi['cash'], true],
        ['دریافت‌های امروز', $kpi['in_today'], true],
        ['پرداخت‌های امروز', $kpi['out_today'], true],
        ['خالص جریان نقد امروز', $kpi['net_today'], true],
        ['مطالبات باز', $kpi['ar_open'], true],
        ['بدهی‌های باز', $kpi['ap_open'], true],
        ['JobCard آماده تسویه', $kpi['jc_ready'], false],
        ['JobCard تسویه‌نشده', $kpi['jc_open'], false],
        ['فروش خدمات', $kpi['svc_sales'], true],
        ['فروش قطعات', $kpi['parts_sales'], true],
        ['سود ناخالص', $kpi['gross'], true],
        ['مالیات نیازمند بررسی', $kpi['tax_review'], false],
        ['اسناد پیش‌نویس', $kpi['draft'], false],
        ['اسناد Posted', $kpi['posted'], false],
        ['مغایرت‌ها', $kpi['mismatch'], false],
        ['Override مالی', $kpi['override'], false],
    ];
    foreach ($cards as [$lab, $val, $isMoney]): ?>
      <div class="f360-kpi"><span><?= fin360_h($lab) ?></span><strong><?= f360_kpi_value($val, $isMoney) ?></strong></div>
    <?php endforeach; ?>
  </section>
  <div class="f360-gauge-row">
    <?= f360_gauge($liqPct, 'نقدینگی') ?>
    <?= f360_gauge($arPct, 'وصول مطالبات', '#5ec8ff') ?>
    <?= f360_gauge($apPct, 'بدهی‌های سررسیدشده', $apPct < 50 ? '#e57373' : '#e8b84a') ?>
    <?= f360_gauge($jcPct, 'تسویه JobCard') ?>
    <?= f360_gauge($taxPct, 'آمادگی مالیاتی', $taxPct < 30 ? '#e8b84a' : '#3ecf8e') ?>
    <?= f360_gauge($docPct, 'صحت اسناد') ?>
  </div>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>صف تسویه JobCard</h2>
      <table class="f360-table"><thead><tr><th>کد</th><th>JobCard</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>
      <?php foreach (array_slice($settlements, 0, 8) as $s): ?>
        <tr><td><?= fin360_h($s['settlement_code']) ?></td><td><?= fin360_h($s['jobcard_ref_text']) ?></td><td><?= fin360_money($s['remaining_amount']) ?></td><td><span class="f360-status"><?= fin360_h($s['settlement_status']) ?></span></td></tr>
      <?php endforeach; if (!$settlements): ?><tr><td colspan="4" class="f360-muted">موردی نیست</td></tr><?php endif; ?>
      </tbody></table>
    </div>
    <div class="f360-panel"><h2>آخرین Audit</h2>
      <table class="f360-table"><thead><tr><th>زمان</th><th>اقدام</th><th>بازیگر</th></tr></thead><tbody>
      <?php foreach (array_slice($audits, 0, 8) as $a): ?>
        <tr><td><?= fin360_h((string)$a['event_time']) ?></td><td><?= fin360_h($a['action_code']) ?></td><td><?= fin360_h($a['actor_user']) ?></td></tr>
      <?php endforeach; if (!$audits): ?><tr><td colspan="3" class="f360-muted">خالی</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
  <div class="f360-panel f360-lock"><h2>ماژول‌های آینده</h2><p class="f360-muted">Payroll کامل · استهلاک دارایی ثابت · بستن حساب‌ها · AI Copilot · اتصال زنده بانک · ارسال زنده سامانه مؤدیان</p></div>

<?php elseif ($tab === 'settlement'): ?>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>ایجاد تسویه JobCard</h2>
      <form class="f360-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="jobcard_settlement">
        <input type="hidden" name="return_tab" value="settlement">
        <label>مرجع JobCard (متنی)<input name="jobcard_ref_text" required placeholder="JC-...."></label>
        <label>طرف حساب مالی (مشتری JobCard)<select name="party_id"><?= f360_party_options($parties, 'CUSTOMER') ?></select></label>
        <label>فروش خدمات<?= fin360_money_input('service_sales_amount', 0) ?></label>
        <label>فروش قطعات<?= fin360_money_input('parts_sales_amount', 0) ?></label>
        <label>خدمات بیرونی<?= fin360_money_input('external_service_sales_amount', 0) ?></label>
        <label>هزینه اضافی<?= fin360_money_input('additional_charges_amount', 0) ?></label>
        <label>تخفیف<?= fin360_money_input('discount_amount', 0) ?></label>
        <label>مالیات<?= fin360_money_input('tax_amount', 0) ?></label>
        <label>پیش‌دریافت<?= fin360_money_input('prepayment_amount', 0) ?></label>
        <label>پرداخت‌شده<?= fin360_money_input('paid_amount', 0) ?></label>
        <label>دلیل Override (اختیاری)<input name="override_reason"></label>
        <p class="f360-muted">فرمول: خدمات+قطعات+بیرونی+اضافی+مالیات−تخفیف−پیش‌دریافت−پرداخت = مانده</p>
        <button class="f360-btn primary" type="submit">ثبت تسویه</button>
      </form>
    </div>
    <div class="f360-panel"><h2>صف تسویه</h2>
      <table class="f360-table"><thead><tr><th>کد</th><th>JobCard</th><th>نهایی</th><th>مانده</th><th>وضعیت</th><th>آزادسازی</th></tr></thead><tbody>
      <?php foreach ($settlements as $s): ?>
        <tr>
          <td><?= fin360_h($s['settlement_code']) ?></td>
          <td><?= fin360_h($s['jobcard_ref_text']) ?></td>
          <td><?= fin360_money($s['final_amount']) ?></td>
          <td><?= fin360_money($s['remaining_amount']) ?></td>
          <td><span class="f360-status"><?= fin360_h($s['settlement_status']) ?></span></td>
          <td><?= fin360_h($s['financial_release_status']) ?></td>
        </tr>
      <?php endforeach; if (!$settlements): ?><tr><td colspan="6" class="f360-muted">موردی نیست</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>

<?php elseif ($tab === 'receipts'): ?>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>ثبت دریافت</h2>
      <p class="f360-muted">پرداخت‌کننده می‌تواند مشتری، بیمه، شخص ثالث، مالک، شریک، بانک یا سایر باشد.</p>
      <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="register_receipt">
        <input type="hidden" name="return_tab" value="receipts">
        <label>پرداخت‌کننده — انتخاب موجود<select name="payer_party_id"><?= f360_party_options($parties) ?></select></label>
        <fieldset class="f360-fieldset">
          <legend>یا ایجاد سریع طرف حساب مالی</legend>
          <label>نوع طرف<select name="payer_type"><?= fin360_party_type_options('CUSTOMER') ?></select></label>
          <label>نام پرداخت‌کننده<input name="payer_display_name" placeholder="نام طرف حساب"></label>
          <label>موبایل<input name="payer_mobile"></label>
        </fieldset>
        <label>مبلغ<?= fin360_money_input('amount', '', true) ?></label>
        <label>روش دریافت<select name="payment_type" required><option>CASH</option><option>POS</option><option>GATEWAY</option><option>BANK_TRANSFER</option><option>CHEQUE</option><option>INSURANCE</option><option>THIRD_PARTY</option></select></label>
        <label>حساب مقصد<select name="cash_account_id" required><?= f360_cash_options($cashAccounts) ?></select></label>
        <label>تاریخ<input name="payment_date" type="date" value="<?= date('Y-m-d') ?>"></label>
        <label>شماره مرجع<input name="reference_no"></label>
        <label>JobCard (اختیاری)<input name="jobcard_ref_text"></label>
        <label>منبع<input name="source_type" placeholder="MANUAL / INSURANCE / BANK ..."></label>
        <label>شرح<textarea name="description" rows="2"></textarea></label>
        <button class="f360-btn primary" type="submit">ثبت دریافت</button>
      </form>
    </div>
    <div class="f360-panel"><h2>ثبت طرف حساب مالی</h2>
      <form class="f360-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="create_party">
        <input type="hidden" name="return_tab" value="receipts">
        <label>نوع طرف<select name="party_type"><?= fin360_party_type_options() ?></select></label>
        <label>نام<input name="display_name" required></label>
        <label>موبایل<input name="mobile"></label>
        <label>کد ملی<input name="national_id"></label>
        <label>منبع متنی<input name="source_ref_text" placeholder="مرجع دستی"></label>
        <button class="f360-btn" type="submit">ایجاد طرف حساب</button>
      </form>
      <h3 style="margin-top:1rem">دریافت‌ها</h3>
      <table class="f360-table"><thead><tr><th>تاریخ</th><th>پرداخت‌کننده</th><th>نوع طرف</th><th>روش</th><th>حساب مقصد</th><th>مبلغ</th><th>وضعیت</th></tr></thead><tbody>
      <?php foreach ($payments as $p): if (($p['payment_direction'] ?? '') !== 'IN') {
          continue;
      } ?>
        <tr>
          <td><?= fin360_h((string)$p['payment_date']) ?></td>
          <td><?= fin360_h((string)($p['display_name'] ?? '—')) ?></td>
          <td><?= fin360_h(fin360_party_type_label((string)($p['party_type'] ?? 'OTHER'))) ?></td>
          <td><?= fin360_h((string)$p['payment_type']) ?></td>
          <td><?= fin360_h((string)($p['account_title'] ?? '')) ?></td>
          <td><?= fin360_money($p['amount']) ?></td>
          <td><?= fin360_h((string)$p['status_code']) ?></td>
        </tr>
      <?php endforeach; if (!$payments): ?><tr><td colspan="7" class="f360-muted">موردی نیست</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>

<?php elseif ($tab === 'prepayments'): ?>
  <div class="f360-panel" style="max-width:560px"><h2>ثبت پیش‌دریافت</h2>
    <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
      <?= fin360_csrf_field() ?>
      <input type="hidden" name="action" value="customer_prepayment">
      <input type="hidden" name="return_tab" value="prepayments">
      <label>پرداخت‌کننده<select name="payer_party_id"><?= f360_party_options($parties) ?></select></label>
      <label>یا نوع + نام<input name="payer_type" placeholder="CUSTOMER"><input name="payer_display_name" placeholder="نام پرداخت‌کننده"></label>
      <label>مبلغ<?= fin360_money_input('amount', '', true) ?></label>
      <label>حساب مقصد<select name="cash_account_id" required><?= f360_cash_options($cashAccounts) ?></select></label>
      <label>JobCard<input name="jobcard_ref_text"></label>
      <label>شرح<textarea name="description" rows="2"></textarea></label>
      <button class="f360-btn primary" type="submit">ثبت پیش‌دریافت</button>
    </form>
  </div>

<?php elseif ($tab === 'invoices'): ?>
  <div class="f360-panel" style="max-width:560px"><h2>فاکتور خدمات</h2>
    <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
      <?= fin360_csrf_field() ?>
      <input type="hidden" name="action" value="service_invoice">
      <input type="hidden" name="return_tab" value="invoices">
      <label>طرف حساب مالی<select name="party_id"><?= f360_party_options($parties, 'CUSTOMER') ?></select></label>
      <label>JobCard<input name="jobcard_ref_text"></label>
      <label>عنوان خدمت<input name="item_title" required></label>
      <label>تعداد<input name="quantity" type="number" step="0.01" value="1"></label>
      <label>قیمت واحد<?= fin360_money_input('unit_price', '', true) ?></label>
      <label>تخفیف<?= fin360_money_input('discount_amount', 0) ?></label>
      <label>مالیات<?= fin360_money_input('tax_amount', 0) ?></label>
      <label>بهای تمام‌شده<?= fin360_money_input('cost_amount', 0) ?></label>
      <button class="f360-btn primary" type="submit">ثبت فاکتور خدمات</button>
    </form>
  </div>

<?php elseif ($tab === 'parts'): ?>
  <div class="f360-panel" style="max-width:560px"><h2>فاکتور قطعات</h2>
    <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
      <?= fin360_csrf_field() ?>
      <input type="hidden" name="action" value="parts_invoice">
      <input type="hidden" name="return_tab" value="parts">
      <label>طرف حساب مالی<select name="party_id"><?= f360_party_options($parties, 'CUSTOMER') ?></select></label>
      <label>JobCard<input name="jobcard_ref_text"></label>
      <label>عنوان قطعه<input name="item_title" required></label>
      <label>تعداد<input name="quantity" type="number" step="0.01" value="1"></label>
      <label>قیمت واحد<?= fin360_money_input('unit_price', '', true) ?></label>
      <label>تخفیف<?= fin360_money_input('discount_amount', 0) ?></label>
      <label>مالیات<?= fin360_money_input('tax_amount', 0) ?></label>
      <label>بهای تمام‌شده<?= fin360_money_input('cost_amount', 0) ?></label>
      <button class="f360-btn primary" type="submit">ثبت فاکتور قطعات</button>
    </form>
  </div>

<?php elseif ($tab === 'sales'): ?>
  <div class="f360-panel"><h2>فروش و درآمد</h2>
    <table class="f360-table"><thead><tr><th>کد</th><th>نوع</th><th>طرف</th><th>مبلغ</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>
    <?php foreach ($docs as $d): if (!in_array($d['document_type'], ['SERVICE_INVOICE', 'PARTS_INVOICE', 'JOBCARD_SETTLEMENT', 'CUSTOMER_RECEIPT', 'GENERAL_RECEIPT'], true)) {
        continue;
    } ?>
      <tr><td><?= fin360_h($d['document_code']) ?></td><td><?= fin360_h($d['document_type']) ?></td><td><?= fin360_h((string)($d['display_name'] ?? '')) ?></td><td><?= fin360_money($d['total_amount']) ?></td><td><?= fin360_money($d['remaining_amount']) ?></td><td><span class="f360-status"><?= fin360_h($d['document_status']) ?></span></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>

<?php elseif ($tab === 'purchases'): ?>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>ثبت خرید / بدهی</h2>
      <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="purchase_invoice">
        <input type="hidden" name="return_tab" value="purchases">
        <label>طرف حساب مالی<select name="party_id"><?= f360_party_options($parties) ?></select></label>
        <label>نوع سند<select name="document_type"><option value="PURCHASE_INVOICE">PURCHASE_INVOICE</option><option value="EXTERNAL_SERVICE_INVOICE">EXTERNAL_SERVICE_INVOICE</option></select></label>
        <label>مبلغ<?= fin360_money_input('amount', '', true) ?></label>
        <label>مالیات<?= fin360_money_input('tax_amount', 0) ?></label>
        <label>سررسید<input name="due_date" type="date"></label>
        <label>مرجع<input name="reference_text"></label>
        <button class="f360-btn primary" type="submit">ثبت خرید</button>
      </form>
    </div>
    <div class="f360-panel" id="pay"><h2>ثبت پرداخت</h2>
      <p class="f360-muted">دریافت‌کننده می‌تواند تأمین‌کننده، کارمند، مالیات، پیمانکار، مالک یا سایر باشد.</p>
      <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="register_payment">
        <input type="hidden" name="return_tab" value="purchases">
        <label>دریافت‌کننده — انتخاب موجود<select name="payee_party_id"><?= f360_party_options($parties) ?></select></label>
        <fieldset class="f360-fieldset">
          <legend>یا ایجاد سریع طرف حساب</legend>
          <label>نوع طرف<select name="payee_type"><?= fin360_party_type_options('SUPPLIER') ?></select></label>
          <label>نام دریافت‌کننده<input name="payee_display_name" placeholder="نام طرف حساب"></label>
        </fieldset>
        <label>حساب مبدا<select name="cash_account_id" required><?= f360_cash_options($cashAccounts) ?></select></label>
        <label>مبلغ<?= fin360_money_input('amount', '', true) ?></label>
        <label>روش پرداخت<select name="payment_type"><option>BANK_TRANSFER</option><option>CASH</option><option>CHEQUE</option><option>POS</option><option>WALLET</option></select></label>
        <label>تاریخ<input name="payment_date" type="date" value="<?= date('Y-m-d') ?>"></label>
        <label>مرجع<input name="reference_no"></label>
        <label>شماره چک<input name="cheque_no"></label>
        <label>سررسید چک<input name="cheque_due_date" type="date"></label>
        <label>شرح<textarea name="description" rows="2"></textarea></label>
        <button class="f360-btn primary" type="submit">ثبت پرداخت</button>
      </form>
      <h3 style="margin-top:1rem">پرداخت‌ها</h3>
      <table class="f360-table"><thead><tr><th>تاریخ</th><th>دریافت‌کننده</th><th>نوع طرف</th><th>روش</th><th>حساب مبدا</th><th>مبلغ</th><th>وضعیت</th></tr></thead><tbody>
      <?php foreach ($payments as $p): if (($p['payment_direction'] ?? '') !== 'OUT') {
          continue;
      } ?>
        <tr>
          <td><?= fin360_h((string)$p['payment_date']) ?></td>
          <td><?= fin360_h((string)($p['display_name'] ?? '—')) ?></td>
          <td><?= fin360_h(fin360_party_type_label((string)($p['party_type'] ?? 'OTHER'))) ?></td>
          <td><?= fin360_h((string)$p['payment_type']) ?></td>
          <td><?= fin360_h((string)($p['account_title'] ?? '')) ?></td>
          <td><?= fin360_money($p['amount']) ?></td>
          <td><?= fin360_h((string)$p['status_code']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>

<?php elseif ($tab === 'cashbank'): ?>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>حساب صندوق / بانک</h2>
      <form class="f360-form f360-money-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="create_cash_account">
        <input type="hidden" name="return_tab" value="cashbank">
        <label>نوع<select name="account_type"><option>CASHBOX</option><option>BANK</option><option>POS</option><option>GATEWAY</option><option>PETTY_CASH</option><option>WALLET</option></select></label>
        <label>عنوان<input name="account_title" required></label>
        <label>بانک<input name="bank_name"></label>
        <label>شبا<input name="iban"></label>
        <label>ارز<input name="currency_code" value="IRR"></label>
        <label>موجودی اول دوره<?= fin360_money_input('opening_balance', 0) ?></label>
        <button class="f360-btn primary" type="submit">ایجاد حساب</button>
      </form>
    </div>
    <div class="f360-panel"><h2>موقعیت نقد</h2>
      <table class="f360-table"><thead><tr><th>عنوان</th><th>نوع</th><th>موجودی</th><th>افتتاحیه</th></tr></thead><tbody>
      <?php foreach ($cashAccounts as $c): ?>
        <tr><td><?= fin360_h($c['account_title']) ?></td><td><?= fin360_h($c['account_type']) ?></td><td><?= fin360_money($c['current_book_balance']) ?></td><td><?= fin360_money($c['opening_balance']) ?></td></tr>
      <?php endforeach; if (!$cashAccounts): ?><tr><td colspan="4" class="f360-muted">حسابی تعریف نشده</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
  <div class="f360-panel f360-lock"><h3>انتقال داخلی صندوق/بانک</h3><p class="f360-muted">فاز بعد — انتقال بین حساب‌های نقد بدون برچسب مشتری/تأمین‌کننده</p></div>

<?php elseif ($tab === 'ar'): ?>
  <div class="f360-panel"><h2>مطالبات — Aging</h2>
    <div class="f360-kpi-grid">
      <div class="f360-kpi"><span>جاری</span><strong><?= fin360_money($agingAr['current']) ?></strong></div>
      <div class="f360-kpi"><span>1-30</span><strong><?= fin360_money($agingAr['d30']) ?></strong></div>
      <div class="f360-kpi"><span>31-60</span><strong><?= fin360_money($agingAr['d60']) ?></strong></div>
      <div class="f360-kpi"><span>61-90</span><strong><?= fin360_money($agingAr['d90']) ?></strong></div>
      <div class="f360-kpi"><span>90+</span><strong><?= fin360_money($agingAr['d90p']) ?></strong></div>
    </div>
    <table class="f360-table"><thead><tr><th>کد</th><th>طرف</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>
    <?php foreach ($docs as $d): if (!in_array($d['document_type'], ['SERVICE_INVOICE', 'PARTS_INVOICE', 'JOBCARD_SETTLEMENT'], true) || (float)$d['remaining_amount'] <= 0) {
        continue;
    } ?>
      <tr><td><?= fin360_h($d['document_code']) ?></td><td><?= fin360_h((string)($d['display_name'] ?? '')) ?></td><td><?= fin360_money($d['remaining_amount']) ?></td><td><?= fin360_h($d['document_status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>

<?php elseif ($tab === 'ap'): ?>
  <div class="f360-panel"><h2>بدهی‌ها — Aging</h2>
    <p class="f360-muted">شامل تأمین‌کننده، پیمانکار، کارمند، مالیات، تأمین اجتماعی و سایر بدهی‌ها</p>
    <div class="f360-kpi-grid">
      <div class="f360-kpi"><span>جاری</span><strong><?= fin360_money($agingAp['current']) ?></strong></div>
      <div class="f360-kpi"><span>1-30</span><strong><?= fin360_money($agingAp['d30']) ?></strong></div>
      <div class="f360-kpi"><span>31-60</span><strong><?= fin360_money($agingAp['d60']) ?></strong></div>
      <div class="f360-kpi"><span>61-90</span><strong><?= fin360_money($agingAp['d90']) ?></strong></div>
      <div class="f360-kpi"><span>90+</span><strong><?= fin360_money($agingAp['d90p']) ?></strong></div>
    </div>
    <table class="f360-table"><thead><tr><th>کد</th><th>طرف حساب</th><th>نوع</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>
    <?php foreach ($docs as $d): if (!in_array($d['document_type'], ['PURCHASE_INVOICE', 'EXTERNAL_SERVICE_INVOICE', 'EXPENSE'], true) || (float)$d['remaining_amount'] <= 0) {
        continue;
    } ?>
      <tr>
        <td><?= fin360_h($d['document_code']) ?></td>
        <td><?= fin360_h((string)($d['display_name'] ?? '—')) ?></td>
        <td><?= fin360_h(fin360_party_type_label((string)($d['party_type'] ?? 'OTHER'))) ?></td>
        <td><?= fin360_money($d['remaining_amount']) ?></td>
        <td><?= fin360_h($d['document_status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>

<?php elseif ($tab === 'costing'): ?>
  <div class="f360-panel"><h2>بهای تمام‌شده و سودآوری (پیش‌نمایش)</h2>
    <div class="f360-report-line"><span>فروش خدمات + قطعات</span><span><?= fin360_money($kpi['svc_sales'] + $kpi['parts_sales']) ?></span></div>
    <div class="f360-report-line"><span>بهای تمام‌شده</span><span><?= fin360_money($pnl['cogs']) ?></span></div>
    <div class="f360-report-line total"><span>سود ناخالص تقریبی</span><span><?= fin360_money($kpi['gross']) ?></span></div>
    <div class="f360-panel f360-lock" style="margin-top:1rem"><h3>موتور هزینه کامل</h3><p class="f360-muted">تخصیص مراکز هزینه — آینده</p></div>
  </div>

<?php elseif ($tab === 'tax'): ?>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>آمادگی سامانه مؤدیان</h2>
      <?php if (!$taxRules): ?><p class="f360-status warn">نیازمند تعریف قانون مالیاتی</p><?php endif; ?>
      <p class="f360-muted">بدون ارسال زنده · بدون API خارجی · بدون hardcode نرخ</p>
      <table class="f360-table"><thead><tr><th>سند</th><th>وضعیت ارسال</th></tr></thead><tbody>
      <?php foreach ($taxDocs as $t): ?>
        <tr><td><?= fin360_h((string)($t['document_code'] ?? $t['document_id'])) ?></td><td><?= fin360_h($t['submission_status']) ?></td></tr>
      <?php endforeach; if (!$taxDocs): ?><tr><td colspan="2" class="f360-muted">اسناد مالیاتی ثبت نشده · Payload/Response آرشیو placeholder</td></tr><?php endif; ?>
      </tbody></table>
    </div>
    <div class="f360-panel"><h2>تنظیم قانون مالیاتی</h2>
      <form class="f360-form" method="post" action="erp-finance-action.php">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="tax_rule">
        <input type="hidden" name="return_tab" value="tax">
        <label>نوع قانون<input name="rule_type" required placeholder="VAT / WITHHOLDING ..."></label>
        <label>مرجع قانونی<input name="legal_reference" required></label>
        <label>از تاریخ<input name="effective_from" type="date" value="<?= date('Y-m-d') ?>"></label>
        <label>نرخ یا فرمول<input name="rate_or_formula" required placeholder="از منبع رسمی وارد شود"></label>
        <label>منبع انتشار<input name="published_source"></label>
        <label>وضعیت تأیید<select name="approval_status"><option>DRAFT</option><option>APPROVED</option></select></label>
        <button class="f360-btn primary" type="submit">ذخیره قانون</button>
      </form>
      <h3 style="margin-top:1rem">قوانین</h3>
      <table class="f360-table"><thead><tr><th>نوع</th><th>فرمول</th><th>وضعیت</th></tr></thead><tbody>
      <?php foreach ($taxRules as $tr): ?>
        <tr><td><?= fin360_h($tr['rule_type']) ?></td><td><?= fin360_h($tr['rate_or_formula']) ?></td><td><?= fin360_h($tr['approval_status']) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>

<?php elseif ($tab === 'pnl'): ?>
  <div class="f360-panel" style="max-width:480px"><h2>سود و زیان — پیش‌نمایش</h2>
    <div class="f360-report-line"><span>درآمد</span><span><?= fin360_money($pnl['rev']) ?></span></div>
    <div class="f360-report-line"><span>تخفیف و برگشت</span><span><?= fin360_money($pnl['disc']) ?></span></div>
    <div class="f360-report-line"><span>فروش خالص</span><span><?= fin360_money($netSales) ?></span></div>
    <div class="f360-report-line"><span>بهای تمام‌شده</span><span><?= fin360_money($pnl['cogs']) ?></span></div>
    <div class="f360-report-line"><span>سود ناخالص</span><span><?= fin360_money($gross) ?></span></div>
    <div class="f360-report-line"><span>هزینه‌ها</span><span><?= fin360_money($pnl['opex']) ?></span></div>
    <div class="f360-report-line total"><span>سود عملیاتی</span><span><?= fin360_money($opProfit) ?></span></div>
  </div>

<?php elseif ($tab === 'cashflow'): ?>
  <div class="f360-panel" style="max-width:480px"><h2>جریان نقد — پیش‌نمایش</h2>
    <div class="f360-report-line"><span>موجودی افتتاحیه</span><span><?= fin360_money($cf['open']) ?></span></div>
    <div class="f360-report-line"><span>ورودی‌ها</span><span><?= fin360_money($cf['in']) ?></span></div>
    <div class="f360-report-line"><span>خروجی‌ها</span><span><?= fin360_money($cf['out']) ?></span></div>
    <div class="f360-report-line"><span>خالص</span><span><?= fin360_money($cfNet) ?></span></div>
    <div class="f360-report-line total"><span>موجودی پایانی</span><span><?= fin360_money($cfClose) ?></span></div>
  </div>

<?php elseif ($tab === 'posting'): ?>
  <div class="f360-grid2">
    <div class="f360-panel"><h2>دفتر کل و Posting</h2>
      <form method="post" action="erp-finance-action.php" style="display:inline">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="seed_posting_rules">
        <input type="hidden" name="return_tab" value="posting">
        <button class="f360-btn" type="submit">ایجاد قوانین Posting اسکلت</button>
      </form>
      <form method="post" action="erp-finance-action.php" style="display:inline;margin-right:.5rem">
        <?= fin360_csrf_field() ?>
        <input type="hidden" name="action" value="posting_test">
        <input type="hidden" name="return_tab" value="posting">
        <button class="f360-btn primary" type="submit">ایجاد سند حسابداری آزمایشی</button>
      </form>
      <h3 style="margin-top:1rem">قوانین</h3>
      <table class="f360-table"><thead><tr><th>رویداد</th><th>نوع سند</th><th>بدهکار</th><th>بستانکار</th></tr></thead><tbody>
      <?php foreach ($rules as $r): ?>
        <tr><td><?= fin360_h($r['event_code']) ?></td><td><?= fin360_h($r['document_type']) ?></td><td><?= fin360_h((string)$r['debit_code']) ?></td><td><?= fin360_h((string)$r['credit_code']) ?></td></tr>
      <?php endforeach; if (!$rules): ?><tr><td colspan="4" class="f360-muted">قانونی تعریف نشده</td></tr><?php endif; ?>
      </tbody></table>
      <h3 style="margin-top:1rem">اسناد برای Post</h3>
      <table class="f360-table"><thead><tr><th>کد</th><th>نوع</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>
      <?php foreach (array_slice($docs, 0, 30) as $d): $st = strtoupper((string)$d['document_status']); ?>
        <tr>
          <td><?= fin360_h($d['document_code']) ?></td>
          <td><?= fin360_h($d['document_type']) ?></td>
          <td><span class="f360-status"><?= fin360_h($st) ?></span></td>
          <td>
            <?php if ($st === 'DRAFT'): ?>
              <form method="post" action="erp-finance-action.php" style="display:inline"><?= fin360_csrf_field() ?><input type="hidden" name="action" value="submit_document"><input type="hidden" name="return_tab" value="posting"><input type="hidden" name="document_id" value="<?= (int)$d['document_id'] ?>"><button class="f360-btn" type="submit">Submit</button></form>
            <?php endif; ?>
            <?php if (in_array($st, ['DRAFT', 'SUBMITTED', 'REVIEWED'], true)): ?>
              <form method="post" action="erp-finance-action.php" style="display:inline"><?= fin360_csrf_field() ?><input type="hidden" name="action" value="approve_document"><input type="hidden" name="return_tab" value="posting"><input type="hidden" name="document_id" value="<?= (int)$d['document_id'] ?>"><button class="f360-btn" type="submit">Approve</button></form>
            <?php endif; ?>
            <?php if ($st === 'APPROVED'): ?>
              <form method="post" action="erp-finance-action.php" style="display:inline"><?= fin360_csrf_field() ?><input type="hidden" name="action" value="post_document"><input type="hidden" name="return_tab" value="posting"><input type="hidden" name="document_id" value="<?= (int)$d['document_id'] ?>"><button class="f360-btn primary" type="submit">Post</button></form>
            <?php endif; ?>
            <?php if ($st === 'POSTED'): ?>
              <span class="f360-muted">قفل · </span>
              <form method="post" action="erp-finance-action.php" style="display:inline"><?= fin360_csrf_field() ?><input type="hidden" name="action" value="reverse_document"><input type="hidden" name="return_tab" value="posting"><input type="hidden" name="document_id" value="<?= (int)$d['document_id'] ?>"><input type="hidden" name="reason" value="owner reverse"><button class="f360-btn" type="submit">Reverse</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <div class="f360-panel"><h2>ژورنال‌ها</h2>
      <table class="f360-table"><thead><tr><th>کد</th><th>بدهکار</th><th>بستانکار</th><th>وضعیت</th></tr></thead><tbody>
      <?php foreach ($journals as $j): ?>
        <tr><td><?= fin360_h($j['journal_code']) ?></td><td><?= fin360_money($j['total_debit']) ?></td><td><?= fin360_money($j['total_credit']) ?></td><td><?= fin360_h($j['journal_status']) ?></td></tr>
      <?php endforeach; if (!$journals): ?><tr><td colspan="4" class="f360-muted">خالی</td></tr><?php endif; ?>
      </tbody></table>
      <h3 style="margin-top:1rem">اسکلت حساب‌ها</h3>
      <table class="f360-table"><thead><tr><th>کد</th><th>نام</th><th>نوع</th></tr></thead><tbody>
      <?php foreach ($accounts as $a): ?>
        <tr><td><?= fin360_h($a['account_code']) ?></td><td><?= fin360_h($a['account_name_fa']) ?></td><td><?= fin360_h($a['account_type']) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>

<?php elseif ($tab === 'audit'): ?>
  <div class="f360-panel"><h2>Audit مالی — ۱۰۰ رویداد اخیر</h2>
    <table class="f360-table"><thead><tr><th>زمان</th><th>بازیگر</th><th>اقدام</th><th>موجودیت</th><th>طرف/نقش</th><th>مبلغ</th><th>دلیل</th></tr></thead><tbody>
    <?php foreach ($audits as $a):
        $after = json_decode((string)($a['after_json'] ?? ''), true);
        $partyInfo = '';
        $amt = '';
        if (is_array($after)) {
            if (!empty($after['party'])) {
                $partyInfo = (string)$after['party'];
            } elseif (!empty($after['payer'])) {
                $partyInfo = 'پرداخت‌کننده: ' . $after['payer'];
            } elseif (!empty($after['payee'])) {
                $partyInfo = 'دریافت‌کننده: ' . $after['payee'];
            }
            if (!empty($after['party_type'])) {
                $partyInfo .= ($partyInfo !== '' ? ' · ' : '') . fin360_party_type_label((string)$after['party_type']);
            }
            if (isset($after['amount'])) {
                $amt = fin360_money($after['amount']);
            }
        }
    ?>
      <tr>
        <td><?= fin360_h((string)$a['event_time']) ?></td>
        <td><?= fin360_h($a['actor_user']) ?></td>
        <td><?= fin360_h($a['action_code']) ?></td>
        <td><?= fin360_h($a['entity_name']) ?></td>
        <td><?= fin360_h($partyInfo !== '' ? $partyInfo : (string)($a['entity_id'] ?? '')) ?></td>
        <td><?= fin360_h($amt) ?></td>
        <td><?= fin360_h((string)($a['reason'] ?? '')) ?></td>
      </tr>
    <?php endforeach; if (!$audits): ?><tr><td colspan="7" class="f360-muted">رویدادی نیست</td></tr><?php endif; ?>
    </tbody></table>
  </div>

<?php elseif ($tab === 'settings'): ?>
  <div class="f360-panel"><h2>تنظیمات مالی</h2>
    <p class="f360-muted">پایگاه فعال: <strong>moghare360_ERP</strong> · پیشوند جداول: <strong>fin360_</strong> · بدون اتصال زنده بانک/مالیات</p>
    <div class="f360-panel f360-lock"><h3>بستن دوره حسابداری</h3><p class="f360-muted">آینده</p></div>
    <div class="f360-panel f360-lock"><h3>Payroll / استهلاک / AI</h3><p class="f360-muted">آینده — قفل</p></div>
  </div>
<?php endif; ?>

</div>
<script>
(function(){
  function toEn(s){
    var p=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'],a=['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],e=['0','1','2','3','4','5','6','7','8','9'];
    for(var i=0;i<10;i++){s=s.split(p[i]).join(e[i]);s=s.split(a[i]).join(e[i]);}
    return s;
  }
  function parseMoney(v){
    v=toEn(String(v||'')).replace(/[\s,،٬]/g,'');
    if(!v||v==='-')return '';
    var n=parseFloat(v);
    return isNaN(n)?'':String(n);
  }
  function formatMoney(v){
    var n=parseFloat(parseMoney(v));
    if(isNaN(n))return '';
    var p=(n<0?'-':'');
    n=Math.abs(n);
    var whole=Math.floor(n),dec=Math.round((n-whole)*100);
    var s=String(whole).replace(/\B(?=(\d{3})+(?!\d))/g,',');
    return p+s+(dec?'.'+String(dec).padStart(2,'0').replace(/0$/,''):'');
  }
  document.querySelectorAll('.f360-money-input').forEach(function(inp){
    inp.addEventListener('blur',function(){var f=formatMoney(inp.value);if(f!=='')inp.value=f;});
    inp.addEventListener('focus',function(){inp.value=parseMoney(inp.value);});
  });
  document.querySelectorAll('form.f360-money-form, form.f360-form').forEach(function(form){
    form.addEventListener('submit',function(){
      form.querySelectorAll('.f360-money-input').forEach(function(inp){
        inp.value=parseMoney(inp.value);
      });
    });
  });
})();
</script>
</body>
</html>
