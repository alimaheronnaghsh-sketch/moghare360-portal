<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — V0 Admin Read-Only Dashboard
 * LOCAL READ-ONLY ERP DASHBOARD - REMOVE OR PROTECT BEFORE DEPLOYMENT
 *
 * SELECT queries only. No config.php dependency. ODBC + Trusted_Connection.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

/** @var list<array{code:string,title:string,expected:string,actual:string,status:string}> */
$checks = [];

function erp_dash_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_dash_add_check(
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

function erp_dash_connect()
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

function erp_dash_scalar($connection, string $sql): ?string
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

function erp_dash_row($connection, string $sql): ?array
{
    $result = @odbc_exec($connection, $sql);
    if ($result === false) {
        return null;
    }

    if (!@odbc_fetch_row($result)) {
        @odbc_free_result($result);
        return null;
    }

    $normalized = [];
    $columnCount = @odbc_num_fields($result);
    if ($columnCount === false || $columnCount < 1) {
        @odbc_free_result($result);
        return null;
    }

    for ($i = 1; $i <= $columnCount; $i++) {
        $name = @odbc_field_name($result, $i);
        if ($name === false) {
            continue;
        }
        $value = @odbc_result($result, $i);
        $normalized[strtolower((string)$name)] = $value === false ? '' : (string)$value;
    }

    @odbc_free_result($result);

    return $normalized !== [] ? $normalized : null;
}

/**
 * @return list<array<string, string>>
 */
function erp_dash_rows($connection, string $sql): array
{
    $result = @odbc_exec($connection, $sql);
    if ($result === false) {
        return [];
    }

    $rows = [];
    while (@odbc_fetch_row($result)) {
        $normalized = [];
        $columnCount = @odbc_num_fields($result);
        if ($columnCount === false || $columnCount < 1) {
            continue;
        }
        for ($i = 1; $i <= $columnCount; $i++) {
            $name = @odbc_field_name($result, $i);
            if ($name === false) {
                continue;
            }
            $value = @odbc_result($result, $i);
            $normalized[strtolower((string)$name)] = $value === false ? '' : (string)$value;
        }
        if ($normalized !== []) {
            $rows[] = $normalized;
        }
    }

    @odbc_free_result($result);

    return $rows;
}

function erp_dash_int(?string $raw, int $default = -1): int
{
    if ($raw === null || $raw === '') {
        return $default;
    }

    return (int)$raw;
}

$phpVersion = PHP_VERSION;
$odbcEnabled = extension_loaded('odbc');
$connection = null;
$connectionOk = false;
$driverLabel = '—';
$serverTime = '—';
$dbName = '—';
$collation = '—';
$coreTableCount = -1;
$coreTableNames = [];
$departmentCount = -1;
$positionCount = -1;
$roleCount = -1;
$permissionCount = -1;
$rolePermissionCount = -1;
$approvalRuleCount = -1;
$userCount = -1;
$accessRequestCount = -1;
$auditCountOwner = -1;
$auditCountAll = -1;
$historyCountOwner = -1;
$historyCountAll = -1;
$customerRoleCount = -1;
$userRoleCountAll = -1;
$ownerRow = null;
$ownerRoles = [];
$accessRequests = [];
$approvalRules = [];
$roleSummaries = [];
$latestAuditActions = [];

erp_dash_add_check($checks, 'D01', 'نسخه PHP', 'PHP 8.0+', $phpVersion, version_compare($phpVersion, '8.0.0', '>='));
erp_dash_add_check($checks, 'D02', 'افزونه ODBC', 'فعال', $odbcEnabled ? 'فعال' : 'غیرفعال', $odbcEnabled);

if ($odbcEnabled) {
    try {
        $connection = erp_dash_connect();
        $connectionOk = $connection !== false;
        $driverLabel = 'ODBC Trusted Connection (local)';
    } catch (Throwable $e) {
        $connectionOk = false;
        $driverLabel = 'خطای اتصال';
    }
}

erp_dash_add_check($checks, 'D03', 'اتصال SQL Server', 'موفق', $connectionOk ? 'موفق' : 'ناموفق', $connectionOk);

if ($connectionOk && $connection !== false) {
    $dbName = erp_dash_scalar($connection, 'SELECT DB_NAME()') ?? '—';
    erp_dash_add_check($checks, 'D04', 'نام دیتابیس', 'moghare360_ERP', $dbName, $dbName === 'moghare360_ERP');

    $collation = erp_dash_scalar(
        $connection,
        "SELECT CONVERT(NVARCHAR(128), DATABASEPROPERTYEX(DB_NAME(), 'Collation'))"
    ) ?? '—';
    erp_dash_add_check($checks, 'D05', 'Collation', 'Persian_100_CI_AS', $collation, $collation === 'Persian_100_CI_AS');

    $serverTime = erp_dash_scalar($connection, 'SELECT CONVERT(NVARCHAR(30), SYSDATETIME(), 120)') ?? '—';

    $coreTableCount = erp_dash_int(erp_dash_scalar(
        $connection,
        "SELECT COUNT(*) FROM sys.tables WHERE name LIKE 'core[_]%' ESCAPE '\\'"
    ));
    erp_dash_add_check($checks, 'D06', 'تعداد جداول core_*', '16', (string)$coreTableCount, $coreTableCount === 16);

    $coreTableNames = array_map(
        static fn(array $row): string => (string)($row['name'] ?? ''),
        erp_dash_rows($connection, "SELECT name FROM sys.tables WHERE name LIKE 'core[_]%' ESCAPE '\\' ORDER BY name")
    );

    $departmentCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_departments'));
    erp_dash_add_check($checks, 'D07', 'تعداد واحدها', '14', (string)$departmentCount, $departmentCount === 14);

    $positionCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_positions'));
    erp_dash_add_check($checks, 'D08', 'تعداد سمت‌ها', '43', (string)$positionCount, $positionCount === 43);

    $roleCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_roles'));
    erp_dash_add_check($checks, 'D09', 'تعداد نقش‌ها', '18', (string)$roleCount, $roleCount === 18);

    $permissionCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_permissions'));
    erp_dash_add_check($checks, 'D10', 'تعداد مجوزها', '43', (string)$permissionCount, $permissionCount === 43);

    $rolePermissionCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_role_permissions'));
    erp_dash_add_check($checks, 'D11', 'تعداد role_permissions', '165', (string)$rolePermissionCount, $rolePermissionCount === 165);

    $approvalRuleCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_access_approval_rules'));
    erp_dash_add_check($checks, 'D12', 'تعداد قوانین تأیید', '16', (string)$approvalRuleCount, $approvalRuleCount === 16);

    $userCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_users'));
    erp_dash_add_check($checks, 'D13', 'تعداد کاربران', '1', (string)$userCount, $userCount === 1);

    $ownerRow = erp_dash_row(
        $connection,
        'SELECT user_id, username, full_name, lifecycle_state, is_system_owner, is_login_enabled
         FROM dbo.core_users WHERE user_id = 10001'
    );
    $ownerExists = $ownerRow !== null && (int)($ownerRow['user_id'] ?? 0) === 10001;
    $ownerUsername = $ownerExists ? trim((string)($ownerRow['username'] ?? '')) : '';
    $ownerOk = $ownerExists && $ownerUsername === 'mahin.paradigm.owner';
    erp_dash_add_check(
        $checks,
        'D14',
        'مالک پلتفرم (user_id / username)',
        '10001 / mahin.paradigm.owner',
        $ownerOk ? '10001 / mahin.paradigm.owner' : 'یافت نشد',
        $ownerOk
    );

    $ownerRoles = erp_dash_rows(
        $connection,
        "SELECT r.role_key, r.role_name
         FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
         WHERE ur.user_id = 10001 AND ur.revoked_at IS NULL
         ORDER BY r.sort_order, r.role_key"
    );
    $ownerRoleKeys = array_map(static fn(array $r): string => (string)($r['role_key'] ?? ''), $ownerRoles);
    $ownerRolesOk = in_array('owner', $ownerRoleKeys, true) && in_array('system_admin', $ownerRoleKeys, true);
    erp_dash_add_check(
        $checks,
        'D15',
        'نقش‌های مالک پلتفرم',
        'owner, system_admin',
        $ownerRoleKeys !== [] ? implode(', ', $ownerRoleKeys) : '—',
        $ownerRolesOk
    );

    $accessRequestCount = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_access_requests'));
    erp_dash_add_check($checks, 'D16', 'تعداد درخواست‌های دسترسی', '1', (string)$accessRequestCount, $accessRequestCount === 1);

    $auditCountOwner = erp_dash_int(erp_dash_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_audit_logs WHERE subject_user_id = 10001'
    ));
    $auditCountAll = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_audit_logs'));
    erp_dash_add_check($checks, 'D17', 'تعداد audit (مالک پلتفرم)', '>= 1', (string)$auditCountOwner, $auditCountOwner >= 1);

    $historyCountOwner = erp_dash_int(erp_dash_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_access_change_history WHERE user_id = 10001'
    ));
    $historyCountAll = erp_dash_int(erp_dash_scalar($connection, 'SELECT COUNT(*) FROM dbo.core_access_change_history'));
    erp_dash_add_check($checks, 'D18', 'تعداد history (مالک پلتفرم)', '>= 3', (string)$historyCountOwner, $historyCountOwner >= 3);

    $customerRoleCount = erp_dash_int(erp_dash_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_roles WHERE access_level = N'CUSTOMER' OR role_key IN (N'customer', N'CUSTOMER')"
    ));
    erp_dash_add_check($checks, 'D19', 'تعداد نقش CUSTOMER', '0', (string)$customerRoleCount, $customerRoleCount === 0);

    $userRoleCountAll = erp_dash_int(erp_dash_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_user_roles WHERE revoked_at IS NULL'
    ));

    $accessRequests = erp_dash_rows(
        $connection,
        'SELECT TOP 10 request_number, request_type, request_state, migration_source, is_emergency, subject_user_id
         FROM dbo.core_access_requests
         ORDER BY request_id'
    );

    $approvalRules = erp_dash_rows(
        $connection,
        'SELECT TOP 20 request_type, approver_capacity, required_order, is_active
         FROM dbo.core_access_approval_rules
         ORDER BY request_type, required_order'
    );

    $roleSummaries = erp_dash_rows(
        $connection,
        'SELECT TOP 18 r.role_key, r.role_name, r.access_level,
                (SELECT COUNT(*) FROM dbo.core_role_permissions rp WHERE rp.role_id = r.role_id) AS perm_count
         FROM dbo.core_roles r
         ORDER BY r.sort_order, r.role_key'
    );

    $latestAuditActions = erp_dash_rows(
        $connection,
        'SELECT TOP 5 action, subject_user_id, created_at
         FROM dbo.core_audit_logs
         ORDER BY audit_id DESC'
    );

    @odbc_close($connection);
    $connection = null;
} else {
    $skipped = [
        ['D04', 'نام دیتابیس', 'moghare360_ERP'],
        ['D05', 'Collation', 'Persian_100_CI_AS'],
        ['D06', 'تعداد جداول core_*', '16'],
        ['D07', 'تعداد واحدها', '14'],
        ['D08', 'تعداد سمت‌ها', '43'],
        ['D09', 'تعداد نقش‌ها', '18'],
        ['D10', 'تعداد مجوزها', '43'],
        ['D11', 'تعداد role_permissions', '165'],
        ['D12', 'تعداد قوانین تأیید', '16'],
        ['D13', 'تعداد کاربران', '1'],
        ['D14', 'مالک پلتفرم', '10001'],
        ['D15', 'نقش‌های مالک پلتفرم', 'owner, system_admin'],
        ['D16', 'تعداد درخواست‌های دسترسی', '1'],
        ['D17', 'تعداد audit', '>= 1'],
        ['D18', 'تعداد history', '>= 3'],
        ['D19', 'تعداد نقش CUSTOMER', '0'],
    ];
    foreach ($skipped as [$code, $title, $expected]) {
        erp_dash_add_check($checks, $code, $title, $expected, 'رد شده — بدون اتصال', false);
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
  <title>داشبورد فقط‌خواندنی ادمین ERP — MOGHARE360 V0</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; margin: 0; background: #f0f4f8; color: #1a1a1a; line-height: 1.5; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 20px; }
    .warn { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; margin-bottom: 16px; text-align: center; }
    .card { background: #fff; border: 1px solid #d8dee4; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    h1 { margin: 0 0 8px; font-size: 1.4rem; }
    h2 { margin: 0 0 12px; font-size: 1.1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
    .muted { color: #5c6670; font-size: 0.92rem; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
    .stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
    .stat strong { display: block; font-size: 1.4rem; color: #0f172a; }
    .stat span { font-size: 0.85rem; color: #64748b; }
    table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-top: 8px; }
    th, td { border: 1px solid #d8dee4; padding: 7px 9px; text-align: right; vertical-align: top; }
    th { background: #eef2f6; }
    .ok { color: #166534; font-weight: bold; }
    .fail { color: #b91c1c; font-weight: bold; }
    .banner-ok { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px; border-radius: 8px; font-weight: bold; text-align: center; }
    .banner-fail { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px; border-radius: 8px; font-weight: bold; text-align: center; }
    code { background: #f1f5f9; padding: 1px 4px; border-radius: 4px; font-size: 0.88rem; }
    ul { margin: 8px 0; padding-right: 20px; }
    .tag-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .tag { background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 6px; font-size: 0.85rem; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="warn">LOCAL READ-ONLY ERP DASHBOARD - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
      <h1>داشبورد فقط‌خواندنی ادمین ERP</h1>
      <p class="muted">MOGHARE360 ERP V0 — خلاصه زیرساخت و چرخه عمر دسترسی — فقط SELECT</p>
    </div>

    <div class="<?= $allOk ? 'banner-ok' : 'banner-fail' ?>">
      وضعیت کلی: <?= $allOk ? 'OK' : 'FAIL' ?>
    </div>

    <div class="card">
      <h2>۱. محیط (Environment)</h2>
      <table>
        <tbody>
          <tr><th>زمان اجرا</th><td><?= erp_dash_h($runAt) ?> (Asia/Tehran)</td></tr>
          <tr><th>نسخه PHP</th><td><?= erp_dash_h($phpVersion) ?></td></tr>
          <tr><th>افزونه ODBC</th><td><?= $odbcEnabled ? 'فعال' : 'غیرفعال' ?></td></tr>
          <tr><th>روش اتصال</th><td><?= erp_dash_h($driverLabel) ?></td></tr>
          <tr><th>SQL Server</th><td><code>.\SQLEXPRESS</code></td></tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>۲. سلامت دیتابیس (Database Health)</h2>
      <table>
        <tbody>
          <tr><th>وضعیت اتصال</th><td class="<?= $connectionOk ? 'ok' : 'fail' ?>"><?= $connectionOk ? 'OK' : 'FAIL' ?></td></tr>
          <tr><th>نام دیتابیس</th><td><code><?= erp_dash_h($dbName) ?></code></td></tr>
          <tr><th>Collation</th><td><code><?= erp_dash_h($collation) ?></code></td></tr>
          <tr><th>زمان سرور</th><td><?= erp_dash_h($serverTime) ?></td></tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>۳. خلاصه جداول هسته (Core Tables)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_dash_h((string)$coreTableCount) ?></strong><span>جداول core_*</span></div>
      </div>
      <?php if ($coreTableNames !== []): ?>
        <div class="tag-list">
          <?php foreach ($coreTableNames as $tableName): ?>
            <?php if ($tableName !== ''): ?>
              <span class="tag"><code><?= erp_dash_h($tableName) ?></code></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>۴. خلاصه سازمان (Organization)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_dash_h((string)$departmentCount) ?></strong><span>واحدها</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$positionCount) ?></strong><span>سمت‌ها</span></div>
      </div>
    </div>

    <div class="card">
      <h2>۵. خلاصه نقش‌ها و مجوزها (Roles &amp; Permissions)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_dash_h((string)$roleCount) ?></strong><span>نقش‌ها</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$permissionCount) ?></strong><span>مجوزها</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$rolePermissionCount) ?></strong><span>role_permissions</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$userRoleCountAll) ?></strong><span>اختصاص نقش فعال</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$customerRoleCount) ?></strong><span>نقش CUSTOMER</span></div>
      </div>
      <?php if ($roleSummaries !== []): ?>
        <table>
          <thead><tr><th>role_key</th><th>نام نقش</th><th>access_level</th><th>تعداد مجوز</th></tr></thead>
          <tbody>
            <?php foreach ($roleSummaries as $role): ?>
              <tr>
                <td><code><?= erp_dash_h((string)($role['role_key'] ?? '')) ?></code></td>
                <td><?= erp_dash_h((string)($role['role_name'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($role['access_level'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($role['perm_count'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>۶. خلاصه قوانین تأیید (Approval Rules)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_dash_h((string)$approvalRuleCount) ?></strong><span>قوانین تأیید</span></div>
      </div>
      <?php if ($approvalRules !== []): ?>
        <table>
          <thead><tr><th>نوع درخواست</th><th>ظرفیت تأییدکننده</th><th>ترتیب</th><th>فعال</th></tr></thead>
          <tbody>
            <?php foreach ($approvalRules as $rule): ?>
              <tr>
                <td><code><?= erp_dash_h((string)($rule['request_type'] ?? '')) ?></code></td>
                <td><?= erp_dash_h((string)($rule['approver_capacity'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($rule['required_order'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($rule['is_active'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>۷. خلاصه مالک پلتفرم (Platform Owner)</h2>
      <?php if ($ownerRow !== null): ?>
        <table>
          <tbody>
            <tr><th>user_id</th><td><?= erp_dash_h((string)($ownerRow['user_id'] ?? '')) ?></td></tr>
            <tr><th>username</th><td><code><?= erp_dash_h((string)($ownerRow['username'] ?? '')) ?></code></td></tr>
            <tr><th>full_name</th><td><?= erp_dash_h((string)($ownerRow['full_name'] ?? '')) ?></td></tr>
            <tr><th>lifecycle_state</th><td><?= erp_dash_h((string)($ownerRow['lifecycle_state'] ?? '')) ?></td></tr>
            <tr><th>is_system_owner</th><td><?= erp_dash_h((string)($ownerRow['is_system_owner'] ?? '')) ?></td></tr>
            <tr><th>is_login_enabled</th><td><?= erp_dash_h((string)($ownerRow['is_login_enabled'] ?? '')) ?></td></tr>
          </tbody>
        </table>
        <?php if ($ownerRoles !== []): ?>
          <p class="muted">نقش‌های فعال:</p>
          <div class="tag-list">
            <?php foreach ($ownerRoles as $role): ?>
              <span class="tag"><code><?= erp_dash_h((string)($role['role_key'] ?? '')) ?></code> — <?= erp_dash_h((string)($role['role_name'] ?? '')) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p class="fail">مالک پلتفرم (user_id = 10001) یافت نشد.</p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>۸. خلاصه درخواست‌های دسترسی (Access Requests)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_dash_h((string)$accessRequestCount) ?></strong><span>کل درخواست‌ها</span></div>
      </div>
      <?php if ($accessRequests !== []): ?>
        <table>
          <thead><tr><th>شماره</th><th>نوع</th><th>وضعیت</th><th>منبع</th><th>اضطراری</th><th>subject_user_id</th></tr></thead>
          <tbody>
            <?php foreach ($accessRequests as $req): ?>
              <tr>
                <td><code><?= erp_dash_h((string)($req['request_number'] ?? '')) ?></code></td>
                <td><?= erp_dash_h((string)($req['request_type'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($req['request_state'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($req['migration_source'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($req['is_emergency'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($req['subject_user_id'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>۹. خلاصه ممیزی و تاریخچه (Audit / History)</h2>
      <div class="grid">
        <div class="stat"><strong><?= erp_dash_h((string)$auditCountOwner) ?></strong><span>audit مالک</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$auditCountAll) ?></strong><span>audit کل</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$historyCountOwner) ?></strong><span>history مالک</span></div>
        <div class="stat"><strong><?= erp_dash_h((string)$historyCountAll) ?></strong><span>history کل</span></div>
      </div>
      <?php if ($latestAuditActions !== []): ?>
        <table>
          <thead><tr><th>action</th><th>subject_user_id</th><th>created_at</th></tr></thead>
          <tbody>
            <?php foreach ($latestAuditActions as $audit): ?>
              <tr>
                <td><code><?= erp_dash_h((string)($audit['action'] ?? '')) ?></code></td>
                <td><?= erp_dash_h((string)($audit['subject_user_id'] ?? '')) ?></td>
                <td><?= erp_dash_h((string)($audit['created_at'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>۱۰. هشدارهای امنیتی (Security Warnings)</h2>
      <ul>
        <li>این صفحه <strong>فقط خواندنی</strong> است — هیچ INSERT/UPDATE/DELETE انجام نمی‌شود.</li>
        <li><code>password_hash</code> و اطلاعات حساس نمایش داده نمی‌شود.</li>
        <li>قبل از استقرار عمومی، این فایل را <strong>حذف یا محافظت</strong> کنید.</li>
        <li>از صفحات عمومی پرتال به این آدرس <strong>لینک ندهید</strong>.</li>
        <li>ورود پرسنل فعلی (<code>staff-auth.php</code>) تغییر نکرده است.</li>
      </ul>
    </div>

    <div class="card">
      <h2>۱۱. اقدامات بعدی (Next Actions)</h2>
      <ul>
        <li><span class="ok">✓</span> صفحه تشخیصی Bootstrap (<code>erp-bootstrap-status.php</code>)</li>
        <li><span class="<?= $allOk ? 'ok' : 'fail' ?>"><?= $allOk ? '✓' : '○' ?></span> داشبورد فقط‌خواندنی (این صفحه)</li>
        <li>طرح و پیاده‌سازی ورود ادمین ERP — پس از تأیید تست این داشبورد</li>
        <li>مهاجرت ورود پرسنل پرتال — فقط با تأیید صریح (آینده)</li>
      </ul>
    </div>

    <div class="card">
      <h2>جدول بررسی‌ها (Checks)</h2>
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
              <td><code><?= erp_dash_h($row['code']) ?></code></td>
              <td><?= erp_dash_h($row['title']) ?></td>
              <td><?= erp_dash_h($row['expected']) ?></td>
              <td><?= erp_dash_h($row['actual']) ?></td>
              <td class="<?= $row['status'] === 'OK' ? 'ok' : 'fail' ?>"><?= erp_dash_h($row['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
