<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — V0 Bootstrap Status (read-only diagnostic)
 * LOCAL DIAGNOSTIC ONLY — REMOVE OR PROTECT BEFORE DEPLOYMENT
 *
 * SELECT queries only. No config.php dependency. ODBC + Trusted_Connection.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

/** @var list<array{code:string,title:string,expected:string,actual:string,status:string}> */
$checks = [];

function erp_diag_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erp_diag_add_check(
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

function erp_diag_connect()
{
    $dsns = [
        'Driver={ODBC Driver 17 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=yes;',
        'Driver={ODBC Driver 18 for SQL Server};Server=.\SQLEXPRESS;Database=moghare360_ERP;Trusted_Connection=yes;TrustServerCertificate=yes;',
    ];

    $lastError = 'اتصال برقرار نشد';

    foreach ($dsns as $dsn) {
        $conn = @odbc_connect($dsn, '', '');
        if ($conn !== false) {
            return $conn;
        }
        $lastError = 'اتصال ODBC ناموفق';
    }

    throw new RuntimeException($lastError);
}

function erp_diag_scalar($connection, string $sql): ?string
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

function erp_diag_row($connection, string $sql): ?array
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

$phpVersion = PHP_VERSION;
$odbcEnabled = extension_loaded('odbc');
$connection = null;
$connectionOk = false;
$driverLabel = '—';

erp_diag_add_check(
    $checks,
    'C01',
    'نسخه PHP',
    'PHP 8.0+',
    $phpVersion,
    version_compare($phpVersion, '8.0.0', '>=')
);

erp_diag_add_check(
    $checks,
    'C02',
    'افزونه ODBC فعال',
    'فعال',
    $odbcEnabled ? 'فعال' : 'غیرفعال',
    $odbcEnabled
);

if ($odbcEnabled) {
    try {
        $connection = erp_diag_connect();
        $connectionOk = $connection !== false;
        $driverLabel = $connectionOk ? 'ODBC (Trusted Connection)' : '—';
    } catch (Throwable $e) {
        $connectionOk = false;
        $driverLabel = 'خطای اتصال';
    }
}

erp_diag_add_check(
    $checks,
    'C03',
    'اتصال SQL Server',
    'موفق',
    $connectionOk ? 'موفق' : 'ناموفق',
    $connectionOk
);

if ($connectionOk && $connection !== false) {
    $dbName = erp_diag_scalar($connection, 'SELECT DB_NAME()');
    $dbNameOk = $dbName === 'moghare360_ERP';
    erp_diag_add_check(
        $checks,
        'C04',
        'نام دیتابیس جاری',
        'moghare360_ERP',
        $dbName ?? '—',
        $dbNameOk
    );

    $collation = erp_diag_scalar(
        $connection,
        "SELECT CONVERT(NVARCHAR(128), DATABASEPROPERTYEX(DB_NAME(), 'Collation'))"
    );
    $collationOk = $collation === 'Persian_100_CI_AS';
    erp_diag_add_check(
        $checks,
        'C05',
        'Collation دیتابیس',
        'Persian_100_CI_AS',
        $collation ?? '—',
        $collationOk
    );

    $coreTableCountRaw = erp_diag_scalar(
        $connection,
        "SELECT COUNT(*) FROM sys.tables WHERE name LIKE 'core[_]%' ESCAPE '\\'"
    );
    $coreTableCount = $coreTableCountRaw !== null ? (int)$coreTableCountRaw : -1;
    erp_diag_add_check(
        $checks,
        'C06',
        'تعداد جداول core_*',
        '16',
        $coreTableCountRaw ?? '—',
        $coreTableCount === 16
    );

    $ownerRow = erp_diag_row(
        $connection,
        'SELECT user_id, username, full_name FROM dbo.core_users WHERE user_id = 10001'
    );
    $userExists = $ownerRow !== null && isset($ownerRow['user_id']);
    erp_diag_add_check(
        $checks,
        'C07',
        'وجود کاربر Bootstrap (user_id)',
        '10001',
        $userExists ? (string)$ownerRow['user_id'] : 'یافت نشد',
        $userExists && (int)$ownerRow['user_id'] === 10001
    );

    $username = $userExists ? trim((string)($ownerRow['username'] ?? '')) : '';
    $usernameOk = $username === 'mahin.paradigm.owner';
    erp_diag_add_check(
        $checks,
        'C08',
        'نام کاربری Bootstrap',
        'mahin.paradigm.owner',
        $username !== '' ? $username : '—',
        $usernameOk
    );

    $ownerRoleCountRaw = erp_diag_scalar(
        $connection,
        "SELECT COUNT(*)
         FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
         WHERE ur.user_id = 10001
           AND ur.revoked_at IS NULL
           AND r.role_key = N'owner'"
    );
    $ownerRoleOk = $ownerRoleCountRaw !== null && (int)$ownerRoleCountRaw >= 1;
    erp_diag_add_check(
        $checks,
        'C09',
        'نقش owner اختصاص‌یافته',
        'بله (>= 1)',
        $ownerRoleCountRaw ?? '—',
        $ownerRoleOk
    );

    $systemAdminRoleCountRaw = erp_diag_scalar(
        $connection,
        "SELECT COUNT(*)
         FROM dbo.core_user_roles ur
         INNER JOIN dbo.core_roles r ON r.role_id = ur.role_id
         WHERE ur.user_id = 10001
           AND ur.revoked_at IS NULL
           AND r.role_key = N'system_admin'"
    );
    $systemAdminRoleOk = $systemAdminRoleCountRaw !== null && (int)$systemAdminRoleCountRaw >= 1;
    erp_diag_add_check(
        $checks,
        'C10',
        'نقش system_admin اختصاص‌یافته',
        'بله (>= 1)',
        $systemAdminRoleCountRaw ?? '—',
        $systemAdminRoleOk
    );

    $bootstrapRequestCountRaw = erp_diag_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_access_requests WHERE request_number = N'BOOTSTRAP-10001'"
    );
    $bootstrapRequestOk = $bootstrapRequestCountRaw !== null && (int)$bootstrapRequestCountRaw >= 1;
    erp_diag_add_check(
        $checks,
        'C11',
        'درخواست Bootstrap',
        'BOOTSTRAP-10001',
        $bootstrapRequestOk ? 'موجود' : 'یافت نشد',
        $bootstrapRequestOk
    );

    $auditCountRaw = erp_diag_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_audit_logs WHERE subject_user_id = 10001'
    );
    $auditCount = $auditCountRaw !== null ? (int)$auditCountRaw : 0;
    erp_diag_add_check(
        $checks,
        'C12',
        'تعداد audit برای کاربر Bootstrap',
        '>= 1',
        (string)$auditCount,
        $auditCount >= 1
    );

    $historyCountRaw = erp_diag_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.core_access_change_history WHERE user_id = 10001'
    );
    $historyCount = $historyCountRaw !== null ? (int)$historyCountRaw : 0;
    erp_diag_add_check(
        $checks,
        'C13',
        'تعداد history برای کاربر Bootstrap',
        '>= 3',
        (string)$historyCount,
        $historyCount >= 3
    );

    $customerRoleCountRaw = erp_diag_scalar(
        $connection,
        "SELECT COUNT(*) FROM dbo.core_roles WHERE access_level = N'CUSTOMER' OR role_key IN (N'customer', N'CUSTOMER')"
    );
    $customerRoleCount = $customerRoleCountRaw !== null ? (int)$customerRoleCountRaw : -1;
    erp_diag_add_check(
        $checks,
        'C14',
        'تعداد نقش CUSTOMER',
        '0',
        $customerRoleCountRaw ?? '—',
        $customerRoleCount === 0
    );

    @odbc_close($connection);
    $connection = null;
} else {
    $skipped = [
        ['C04', 'نام دیتابیس جاری', 'moghare360_ERP'],
        ['C05', 'Collation دیتابیس', 'Persian_100_CI_AS'],
        ['C06', 'تعداد جداول core_*', '16'],
        ['C07', 'وجود کاربر Bootstrap (user_id)', '10001'],
        ['C08', 'نام کاربری Bootstrap', 'mahin.paradigm.owner'],
        ['C09', 'نقش owner اختصاص‌یافته', 'بله (>= 1)'],
        ['C10', 'نقش system_admin اختصاص‌یافته', 'بله (>= 1)'],
        ['C11', 'درخواست Bootstrap', 'BOOTSTRAP-10001'],
        ['C12', 'تعداد audit برای کاربر Bootstrap', '>= 1'],
        ['C13', 'تعداد history برای کاربر Bootstrap', '>= 3'],
        ['C14', 'تعداد نقش CUSTOMER', '0'],
    ];

    foreach ($skipped as [$code, $title, $expected]) {
        erp_diag_add_check($checks, $code, $title, $expected, 'رد شده — بدون اتصال', false);
    }
}

$allOk = true;
foreach ($checks as $row) {
    if ($row['status'] !== 'OK') {
        $allOk = false;
        break;
    }
}

erp_diag_add_check(
    $checks,
    'C15',
    'وضعیت کلی',
    'همه بررسی‌ها OK',
    $allOk ? 'OK' : 'FAIL',
    $allOk
);

$ownerDisplayName = '—';
foreach ($checks as $row) {
    if ($row['code'] === 'C08' && $row['status'] === 'OK') {
        $ownerDisplayName = $row['actual'];
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
  <title>وضعیت Bootstrap ERP — تشخیص محلی</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1a1a1a; line-height: 1.5; }
    .wrap { max-width: 980px; margin: 0 auto; padding: 20px; }
    .warn { background: #7f1d1d; color: #fff; padding: 12px 16px; border-radius: 8px; font-weight: bold; margin-bottom: 16px; }
    .card { background: #fff; border: 1px solid #d8dee4; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    h1 { margin: 0 0 8px; font-size: 1.35rem; }
    .muted { color: #5c6670; font-size: 0.92rem; }
    table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
    th, td { border: 1px solid #d8dee4; padding: 8px 10px; text-align: right; vertical-align: top; }
    th { background: #eef2f6; }
    .ok { color: #166534; font-weight: bold; }
    .fail { color: #b91c1c; font-weight: bold; }
    .banner-ok { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px; border-radius: 8px; font-weight: bold; }
    .banner-fail { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 8px; font-weight: bold; }
    code { background: #f1f5f9; padding: 1px 4px; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="warn">LOCAL DIAGNOSTIC ONLY - REMOVE OR PROTECT BEFORE DEPLOYMENT</div>

    <div class="card">
      <h1>وضعیت Bootstrap ERP — صفحه تشخیصی (فقط خواندنی)</h1>
      <p class="muted">MOGHARE360 ERP V0 — بدون تغییر ورود پرتال — فقط SELECT</p>
      <p class="muted">زمان اجرا (Asia/Tehran): <?= erp_diag_h($runAt) ?></p>
      <p class="muted">روش اتصال: <?= erp_diag_h($driverLabel) ?></p>
      <?php if ($ownerDisplayName !== '—'): ?>
        <p class="muted">مالک Bootstrap: <code><?= erp_diag_h($ownerDisplayName) ?></code></p>
      <?php endif; ?>
    </div>

    <div class="card <?= $allOk ? 'banner-ok' : 'banner-fail' ?>">
      <?= $allOk ? 'وضعیت کلی: OK — همه بررسی‌ها موفق' : 'وضعیت کلی: FAIL — حداقل یک بررسی ناموفق است' ?>
    </div>

    <div class="card">
      <table>
        <thead>
          <tr>
            <th>کد</th>
            <th>عنوان بررسی</th>
            <th>مقدار مورد انتظار</th>
            <th>مقدار واقعی</th>
            <th>وضعیت</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($checks as $row): ?>
            <tr>
              <td><code><?= erp_diag_h($row['code']) ?></code></td>
              <td><?= erp_diag_h($row['title']) ?></td>
              <td><?= erp_diag_h($row['expected']) ?></td>
              <td><?= erp_diag_h($row['actual']) ?></td>
              <td class="<?= $row['status'] === 'OK' ? 'ok' : 'fail' ?>"><?= erp_diag_h($row['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card muted">
      <p>این صفحه هیچ داده‌ای در دیتابیس ERP نمی‌نویسد. <code>password_hash</code> و اطلاعات حساس نمایش داده نمی‌شود.</p>
      <p>ورود پرسنل فعلی (<code>staff-auth.php</code>) تغییر نکرده است.</p>
    </div>
  </div>
</body>
</html>
