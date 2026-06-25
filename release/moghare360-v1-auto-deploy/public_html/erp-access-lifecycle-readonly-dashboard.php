<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — V0 Access Lifecycle Read-Only Dashboard
 * LOCAL READ-ONLY ACCESS LIFECYCLE DASHBOARD - REMOVE OR PROTECT BEFORE DEPLOYMENT
 *
 * SELECT queries only. No config.php dependency. ODBC + Trusted_Connection.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

/** @var list<array{code:string,title:string,expected:string,actual:string,status:string}> */
$checks = [];

function erp_alc_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_alc_add_check(
    array &$checks,
    string $code,
    string $title,
    string $expected,
    string $actual,
    bool $ok
): void {
    $checks[] = [
        'code' => $code,
        'title' => $title,
        'expected' => $expected,
        'actual' => $actual,
        'status' => $ok ? 'OK' : 'FAIL',
    ];
}

function erp_alc_connect()
{
    $dsns = [
        'Driver={ODBC Driver 17 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=yes;',
        'Driver={ODBC Driver 18 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=yes;TrustServerCertificate=yes;',
    ];

    foreach ($dsns as $dsn) {
        $conn = @odbc_connect($dsn, '', '');
        if ($conn !== false) {
            return $conn;
        }
    }

    throw new RuntimeException('اتصال ODBC ناموفق');
}

function erp_alc_scalar($connection, string $sql): ?string
{
    $result = @odbc_exec($connection, $sql);
    if ($result === false) {
        return null;
    }

    if (!@odbc_fetch_row($result)) {
        @odbc_free_result($result);
        return null;
    }

    $value = @odbc_result($result, 1);
    @odbc_free_result($result);

    if ($value === false || $value === null) {
        return null;
    }

    return (string)$value;
}

function erp_alc_int(?string $raw, int $default = -1): int
{
    if ($raw === null || $raw === '') {
        return $default;
    }

    return (int)$raw;
}

function erp_alc_table_exists($connection, string $tableName): bool
{
    $sql = "SELECT COUNT(*) FROM sys.tables WHERE name = N'" . str_replace("'", "''", $tableName) . "'";
    $raw = erp_alc_scalar($connection, $sql);

    return $raw !== null && (int)$raw >= 1;
}

$connection = null;
$connectionOk = false;
$driverLabel = '—';
$dbName = '—';
$dashboardMode = 'Local Read-Only Diagnostic';

$totalRequests = -1;
$pendingRequests = -1;
$appliedRequests = -1;
$emergencyRequests = -1;
$totalApprovals = -1;
$pendingApprovals = -1;
$approvedApprovals = -1;
$rejectedApprovals = -1;
$activeUserRoles = -1;
$activeSuspensions = -1;
$activeRestrictions = -1;
$historyCount = -1;
$auditCount = -1;

if (extension_loaded('odbc')) {
    try {
        $connection = erp_alc_connect();
        $connectionOk = $connection !== false;
        $driverLabel = 'ODBC Trusted Connection (local)';
    } catch (Throwable $e) {
        $connectionOk = false;
        $driverLabel = 'خطای اتصال';
    }
}

erp_alc_add_check(
    $checks,
    'A01',
    'Database Connection',
    'OK',
    $connectionOk ? 'OK' : 'FAIL',
    $connectionOk
);

if ($connectionOk && $connection !== false) {
    $dbName = erp_alc_scalar($connection, 'SELECT DB_NAME()') ?? '—';

    $tables = [
        'A02' => ['core_access_requests', 'core_access_requests exists'],
        'A03' => ['core_access_request_items', 'core_access_request_items exists'],
        'A04' => ['core_access_approvals', 'core_access_approvals exists'],
        'A05' => ['core_user_roles', 'core_user_roles exists'],
        'A06' => ['core_access_suspensions', 'core_access_suspensions exists'],
        'A07' => ['core_access_restrictions', 'core_access_restrictions exists'],
        'A08' => ['core_access_change_history', 'core_access_change_history exists'],
        'A09' => ['core_audit_logs', 'core_audit_logs exists'],
    ];

    foreach ($tables as $code => [$table, $title]) {
        $exists = erp_alc_table_exists($connection, $table);
        erp_alc_add_check($checks, $code, $title, 'exists', $exists ? 'exists' : 'missing', $exists);
    }

    $totalRequests = erp_alc_int(erp_alc_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_access_requests'));
    erp_alc_add_check($checks, 'A10', 'Total Access Requests', 'readable', (string)$totalRequests, $totalRequests >= 0);

    $pendingRequests = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_requests
         WHERE request_state IN (N'SUBMITTED', N'UNDER_REVIEW', N'APPROVED', N'PARTIALLY_APPROVED')"
    ));
    erp_alc_add_check($checks, 'A11', 'Pending Access Requests', 'readable', (string)$pendingRequests, $pendingRequests >= 0);

    $appliedRequests = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_requests WHERE request_state = N'APPLIED'"
    ));
    erp_alc_add_check($checks, 'A12', 'Applied Access Requests', 'readable', (string)$appliedRequests, $appliedRequests >= 0);

    $emergencyRequests = erp_alc_int(erp_alc_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_access_requests WHERE is_emergency = 1'
    ));
    erp_alc_add_check($checks, 'A13', 'Emergency Access Requests', 'readable', (string)$emergencyRequests, $emergencyRequests >= 0);

    $totalApprovals = erp_alc_int(erp_alc_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_access_approvals'));
    erp_alc_add_check($checks, 'A14', 'Total Approval Records', 'readable', (string)$totalApprovals, $totalApprovals >= 0);

    $pendingApprovals = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_requests
         WHERE request_state IN (N'SUBMITTED', N'UNDER_REVIEW')"
    ));
    erp_alc_add_check($checks, 'A15', 'Pending Approval Records', 'readable', (string)$pendingApprovals, $pendingApprovals >= 0);

    $activeUserRoles = erp_alc_int(erp_alc_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_user_roles WHERE revoked_at IS NULL'
    ));
    erp_alc_add_check($checks, 'A16', 'Active User Roles', 'readable', (string)$activeUserRoles, $activeUserRoles >= 0);

    $activeSuspensions = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_suspensions
         WHERE lifted_at IS NULL
           AND starts_at <= SYSDATETIME()
           AND (ends_at IS NULL OR ends_at > SYSDATETIME())"
    ));
    erp_alc_add_check($checks, 'A17', 'Active Suspensions', 'readable', (string)$activeSuspensions, $activeSuspensions >= 0);

    $activeRestrictions = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_restrictions
         WHERE lifted_at IS NULL
           AND starts_at <= SYSDATETIME()
           AND (ends_at IS NULL OR ends_at > SYSDATETIME())"
    ));
    erp_alc_add_check($checks, 'A18', 'Active Restrictions', 'readable', (string)$activeRestrictions, $activeRestrictions >= 0);

    $historyCount = erp_alc_int(erp_alc_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_access_change_history'));
    erp_alc_add_check($checks, 'A19', 'Access History Count', 'readable', (string)$historyCount, $historyCount >= 0);

    $auditCount = erp_alc_int(erp_alc_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_audit_logs'));
    erp_alc_add_check($checks, 'A20', 'Access Audit Count', 'readable', (string)$auditCount, $auditCount >= 0);

    $approvedApprovals = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_approvals WHERE decision = N'APPROVED'"
    ));
    $rejectedApprovals = erp_alc_int(erp_alc_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_approvals WHERE decision = N'REJECTED'"
    ));

    @odbc_close($connection);
    $connection = null;
} else {
    $skipped = [
        ['A02', 'core_access_requests exists', 'exists'],
        ['A03', 'core_access_request_items exists', 'exists'],
        ['A04', 'core_access_approvals exists', 'exists'],
        ['A05', 'core_user_roles exists', 'exists'],
        ['A06', 'core_access_suspensions exists', 'exists'],
        ['A07', 'core_access_restrictions exists', 'exists'],
        ['A08', 'core_access_change_history exists', 'exists'],
        ['A09', 'core_audit_logs exists', 'exists'],
        ['A10', 'Total Access Requests', 'readable'],
        ['A11', 'Pending Access Requests', 'readable'],
        ['A12', 'Applied Access Requests', 'readable'],
        ['A13', 'Emergency Access Requests', 'readable'],
        ['A14', 'Total Approval Records', 'readable'],
        ['A15', 'Pending Approval Records', 'readable'],
        ['A16', 'Active User Roles', 'readable'],
        ['A17', 'Active Suspensions', 'readable'],
        ['A18', 'Active Restrictions', 'readable'],
        ['A19', 'Access History Count', 'readable'],
        ['A20', 'Access Audit Count', 'readable'],
    ];

    foreach ($skipped as [$code, $title, $expected]) {
        erp_alc_add_check($checks, $code, $title, $expected, 'skipped — no connection', false);
    }
}

$allOk = true;
foreach ($checks as $row) {
    if ($row['status'] !== 'OK') {
        $allOk = false;
        break;
    }
}

$runAt = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran')))->format('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>داشبورد فقط‌خواندنی چرخه عمر دسترسی ERP — MOGHARE360 V0</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; margin: 0; background: #f0f4f8; color: #1a1a1a; line-height: 1.5; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 20px; }
    .warn { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; margin-bottom: 16px; text-align: center; }
    .card { background: #fff; border: 1px solid #d8dee4; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    h1 { margin: 0 0 8px; font-size: 1.35rem; }
    h2 { margin: 0 0 12px; font-size: 1.05rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
    .muted { color: #5c6670; font-size: 0.92rem; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
    .stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
    .stat strong { display: block; font-size: 1.35rem; color: #0f172a; }
    .stat span { font-size: 0.82rem; color: #64748b; }
    table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-top: 8px; }
    th, td { border: 1px solid #d8dee4; padding: 7px 9px; text-align: right; vertical-align: top; }
    th { background: #eef2f6; }
    .ok { color: #166534; font-weight: bold; }
    .fail { color: #b91c1c; font-weight: bold; }
    .banner-ok { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px; border-radius: 8px; font-weight: bold; text-align: center; }
    .banner-fail { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px; border-radius: 8px; font-weight: bold; text-align: center; }
    code { background: #f1f5f9; padding: 1px 4px; border-radius: 4px; font-size: 0.88rem; }
    ul { margin: 8px 0; padding-right: 20px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="warn">LOCAL READ-ONLY ACCESS LIFECYCLE DASHBOARD - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
      <h1>داشبورد فقط‌خواندنی چرخه عمر دسترسی ERP</h1>
      <p class="muted">MOGHARE360 ERP V0 — Access Lifecycle — فقط SELECT</p>
      <p class="muted">زمان اجرا (Asia/Tehran): <?= erp_alc_h($runAt) ?></p>
    </div>

    <div class="<?= $allOk ? 'banner-ok' : 'banner-fail' ?>">
      Overall Status: <?= $allOk ? 'OK' : 'FAIL' ?>
    </div>

    <div class="card">
      <h2>۱. وضعیت سیستم (System Status)</h2>
      <table>
        <tbody>
          <tr><th>Database name</th><td><code><?= erp_alc_h($dbName) ?></code></td></tr>
          <tr><th>Connection status</th><td class="<?= $connectionOk ? 'ok' : 'fail' ?>"><?= $connectionOk ? 'OK' : 'FAIL' ?></td></tr>
          <tr><th>Dashboard mode</th><td><?= erp_alc_h($dashboardMode) ?></td></tr>
          <tr><th>Read-only status</th><td class="ok">SELECT only</td></tr>
          <tr><th>Connection method</th><td><?= erp_alc_h($driverLabel) ?></td></tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>۲. خلاصه درخواست‌های دسترسی (Access Request Summary)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_alc_h((string)$totalRequests) ?></strong><span>کل درخواست‌ها</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$pendingRequests) ?></strong><span>در انتظار</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$appliedRequests) ?></strong><span>اعمال‌شده</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$emergencyRequests) ?></strong><span>اضطراری</span></div>
      </div>
    </div>

    <div class="card">
      <h2>۳. خلاصه تأییدها (Approval Summary)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_alc_h((string)$totalApprovals) ?></strong><span>کل تأییدها</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$pendingApprovals) ?></strong><span>در انتظار تأیید</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$approvedApprovals) ?></strong><span>تأیید شده</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$rejectedApprovals) ?></strong><span>رد شده</span></div>
      </div>
    </div>

    <div class="card">
      <h2>۴. خلاصه دسترسی جاری (Current Access Summary)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_alc_h((string)$activeUserRoles) ?></strong><span>نقش‌های فعال</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$activeSuspensions) ?></strong><span>تعلیق فعال</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$activeRestrictions) ?></strong><span>محدودیت فعال</span></div>
      </div>
    </div>

    <div class="card">
      <h2>۵. خلاصه تاریخچه و ممیزی (History and Audit Summary)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_alc_h((string)$historyCount) ?></strong><span>تاریخچه تغییرات</span></div>
        <div class="stat"><strong><?= erp_alc_h((string)$auditCount) ?></strong><span>لاگ ممیزی</span></div>
      </div>
    </div>

    <div class="card">
      <h2>۶. تأیید ایمنی (Safety Confirmation)</h2>
      <ul>
        <li class="ok">No login logic changed — ورود تغییر نکرده</li>
        <li class="ok">No config secret displayed — راز پیکربندی نمایش داده نشده</li>
        <li class="ok">No password hash displayed — password_hash نمایش داده نشده</li>
        <li class="ok">No user creation performed — کاربری ایجاد نشده</li>
        <li class="ok">No role assignment performed — نقشی اختصاص داده نشده</li>
        <li class="ok">SELECT only — فقط خواندنی</li>
      </ul>
    </div>

    <div class="card">
      <h2>بررسی‌ها (Checks A01–A20)</h2>
      <table>
        <thead>
          <tr>
            <th>کد</th>
            <th>عنوان</th>
            <th>مورد انتظار</th>
            <th>واقعی</th>
            <th>وضعیت</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($checks as $row): ?>
            <tr>
              <td><code><?= erp_alc_h($row['code']) ?></code></td>
              <td><?= erp_alc_h($row['title']) ?></td>
              <td><?= erp_alc_h($row['expected']) ?></td>
              <td><?= erp_alc_h($row['actual']) ?></td>
              <td class="<?= $row['status'] === 'OK' ? 'ok' : 'fail' ?>"><?= erp_alc_h($row['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card muted">
      <p>این صفحه هیچ داده‌ای در دیتابیس ERP نمی‌نویسد. از صفحات عمومی پرتال لینک نشود.</p>
      <p><code>staff-auth.php</code> و <code>access-control.php</code> تغییر نکرده‌اند.</p>
    </div>
  </div>
</body>
</html>
