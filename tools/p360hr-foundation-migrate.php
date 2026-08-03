<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * MOGHARE360 P360HR Foundation Migration (idempotent, transactional).
 * Database: moghare360_ERP only.
 * No DROP / TRUNCATE / blanket DELETE.
 * Does not print password hashes or plaintext passwords.
 *
 * Usage: php tools/p360hr-foundation-migrate.php
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-config-loader.php';

const P360HR_SCHEMA_VERSION = 'P360HR_FOUNDATION_20260802';
const P360HR_TEMP_PASSWORD = 'M360123456'; // policy value; hashed immediately, never logged
const P360HR_RESERVED_CODE = 'M360-100001';
const P360HR_COMPANY_ID = 1;

function p360hr_conn()
{
    $c = erp_load_config()['database'];
    $server = trim((string)$c['server']);
    $name = trim((string)$c['name']);
    if ($name !== 'moghare360_ERP') {
        throw new RuntimeException('Refusing migration: database must be moghare360_ERP, got ' . $name);
    }
    $trusted = (bool)$c['trusted_connection'];
    $user = $trusted ? '' : (string)$c['username'];
    $pass = $trusted ? '' : (string)$c['password'];
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $d) {
        $dsn = "Driver={{$d}};Server={$server};Database={$name};";
        if ($trusted) {
            $dsn .= 'Trusted_Connection=Yes;';
        }
        if ($d === 'ODBC Driver 18 for SQL Server') {
            $dsn .= 'TrustServerCertificate=Yes;';
        }
        $conn = @odbc_connect($dsn, $user, $pass);
        if ($conn !== false) {
            return $conn;
        }
    }
    throw new RuntimeException('ODBC connect failed');
}

function p360hr_exec($conn, string $sql, array $params = []): bool
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        throw new RuntimeException('prepare failed: ' . odbc_errormsg($conn) . ' :: ' . substr($sql, 0, 160));
    }
    if (!@odbc_execute($stmt, $params)) {
        throw new RuntimeException('execute failed: ' . odbc_errormsg($conn) . ' :: ' . substr($sql, 0, 160));
    }
    return true;
}

function p360hr_rows($conn, string $sql, array $params = []): array
{
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        throw new RuntimeException('query failed: ' . odbc_errormsg($conn));
    }
    $rows = [];
    while ($r = odbc_fetch_array($stmt)) {
        $n = [];
        foreach ($r as $k => $v) {
            $n[strtolower((string)$k)] = $v;
        }
        $rows[] = $n;
    }
    return $rows;
}

function p360hr_one($conn, string $sql, array $params = []): ?array
{
    $rows = p360hr_rows($conn, $sql, $params);
    return $rows[0] ?? null;
}

function p360hr_scalar($conn, string $sql, array $params = []): ?string
{
    $row = p360hr_one($conn, $sql, $params);
    if ($row === null) {
        return null;
    }
    return (string)reset($row);
}

function p360hr_table_exists($conn, string $table): bool
{
    $r = p360hr_one($conn, "SELECT 1 AS x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$table]);
    return $r !== null;
}

function p360hr_column_exists($conn, string $table, string $column): bool
{
    $r = p360hr_one($conn, "SELECT 1 AS x FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $column]);
    return $r !== null;
}

function p360hr_ensure_column($conn, string $table, string $column, string $ddl): void
{
    if (!p360hr_column_exists($conn, $table, $column)) {
        p360hr_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}");
        echo "ALTERED {$table}.{$column}\n";
    } else {
        echo "SKIP_COL {$table}.{$column}\n";
    }
}

function p360hr_personnel_code_from_id(int $employeeId): string
{
    if ($employeeId < 1 || $employeeId > 9999) {
        throw new RuntimeException('ط¸ط±ظپغŒطھ ع©ط¯ ظ¾ط±ط³ظ†ظ„غŒ ط§ط³طھط§ظ†ط¯ط§ط±ط¯ ط¨ظ‡ ظ¾ط§غŒط§ظ† ط±ط³غŒط¯ظ‡ ط§ط³طھ. طھظˆط³ط¹ظ‡ ط¨ط§ط²ظ‡ ع©ط¯ظ‡ط§ ظ†غŒط§ط²ظ…ظ†ط¯ طھط£غŒغŒط¯ ظ…ط§ظ„ع© ط³ط§ظ…ط§ظ†ظ‡ ط§ط³طھ.');
    }
    return 'M360-' . str_pad((string)$employeeId, 4, '0', STR_PAD_LEFT);
}

function p360hr_split_name(string $full): array
{
    $full = trim(preg_replace('/\s+/u', ' ', $full) ?? $full);
    $parts = preg_split('/\s+/u', $full) ?: [];
    if (count($parts) <= 1) {
        return [$full !== '' ? $full : 'â€”', 'â€”'];
    }
    $first = array_shift($parts);
    return [$first, implode(' ', $parts)];
}

function p360hr_norm_name(string $s): string
{
    $s = trim(mb_strtolower($s, 'UTF-8'));
    $s = str_replace(['ظٹ', 'ظƒ', 'â€Œ', 'ظ€'], ['غŒ', 'ع©', '', ''], $s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return $s;
}

/** @return list<array<string,mixed>> */
function p360hr_personnel_seed(): array
{
    return [
        ['hire_year_jalali' => 1394, 'unit_code' => 1, 'unit_name' => 'ظ…ط¯غŒط±غŒطھ', 'job_title' => 'ظ…ط§ظ„ع© / ظ…ط¯غŒط± ع©ظ„', 'full_name' => 'ط¹ظ„غŒ ط±ط¶ط§ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯'],
        ['hire_year_jalali' => 1395, 'unit_code' => 9, 'unit_name' => 'ط®ط¯ظ…ط§طھ', 'job_title' => 'ط®ط¯ظ…ط§طھ', 'full_name' => 'ظ…ظ†طµظˆط± ظ‚ط¯ط±طھغŒ'],
        ['hire_year_jalali' => 1398, 'unit_code' => 3, 'unit_name' => 'ط®ط±غŒط¯ ظˆ ط§ظ†ط¨ط§ط±', 'job_title' => 'ط³ط±ظ¾ط±ط³طھ ط®ط±غŒط¯ ظˆ ط§ظ†ط¨ط§ط±', 'full_name' => 'ط¹ظ„غŒ غŒط²ط¯ط§ظ†غŒ'],
        ['hire_year_jalali' => 1398, 'unit_code' => 3, 'unit_name' => 'ط®ط±غŒط¯ ظˆ ط§ظ†ط¨ط§ط±', 'job_title' => 'ط§ظ†ط¨ط§ط±ط¯ط§ط±', 'full_name' => 'ط¬ط¹ظپط± ظ…ط§ظ‡ط±ط§ظ„ظ†ظ‚ط´'],
        ['hire_year_jalali' => 1398, 'unit_code' => 5, 'unit_name' => 'ظ…ع©ط§ظ†غŒع©', 'job_title' => 'ظ…ع©ط§ظ†غŒع© ط§ط±ط´ط¯', 'full_name' => 'ط¯ط§ظˆظˆط¯ ط§ط±ط¯ط§ظ†ظ‡'],
        ['hire_year_jalali' => 1398, 'unit_code' => 4, 'unit_name' => 'CRM / طھط¬ط±ط¨ظ‡ ظ…ط´طھط±غŒ', 'job_title' => 'ط¯غŒط¬غŒطھط§ظ„ ظ…ط§ط±ع©طھغŒظ†ع¯', 'full_name' => 'ظ…ط­ظ…ط¯ ظ…ط¹غŒظ† غŒط§ط±غŒط§ظ†'],
        ['hire_year_jalali' => 1401, 'unit_code' => 2, 'unit_name' => 'ظ…ط§ظ„غŒ / ط§ط¯ط§ط±غŒ', 'job_title' => 'ط³ط±ظ¾ط±ط³طھ ط­ط³ط§ط¨ط¯ط§ط±غŒ / ط§ط¯ط§ط±غŒ', 'full_name' => 'ظ…غŒطھط±ط§ ط®ط´ظ†'],
        ['hire_year_jalali' => 1402, 'unit_code' => 5, 'unit_name' => 'ظ…ع©ط§ظ†غŒع©', 'job_title' => 'ظ…ع©ط§ظ†غŒع©', 'full_name' => 'ظ…ظ‡ط¯غŒ ظ‚ط§ط±ط¯ط§ط´غŒ'],
        ['hire_year_jalali' => 1402, 'unit_code' => 6, 'unit_name' => 'ط¨ط±ظ‚', 'job_title' => 'ط¨ط±ظ‚â€Œع©ط§ط±', 'full_name' => 'ط¹ظ„غŒ ظ‚ط§ط³ظ…غŒ'],
        ['hire_year_jalali' => 1403, 'unit_code' => 8, 'unit_name' => 'ظ„ط¬ط³طھغŒع© / طھط¯ط§ط±ع©ط§طھ', 'job_title' => 'ع©ط§ط±ط´ظ†ط§ط³ طھط¯ط§ط±ع©ط§طھ', 'full_name' => 'ط³ط¹غŒط¯ ط´ط§ظ‡ ط²ظ…ط§ظ†غŒ'],
        ['hire_year_jalali' => 1403, 'unit_code' => 5, 'unit_name' => 'ظ…ع©ط§ظ†غŒع©', 'job_title' => 'ظ…ع©ط§ظ†غŒع©', 'full_name' => 'ظˆظ„غŒâ€Œط§ظ„ظ‡ ط°ع©غŒ'],
        ['hire_year_jalali' => 1403, 'unit_code' => 9, 'unit_name' => 'ط®ط¯ظ…ط§طھ', 'job_title' => 'ع©ط§ط±ظˆط§ط´', 'full_name' => 'ط­ط§ظ…ط¯ ط­ط§طھظ…غŒâ€Œظ¾ظˆط±'],
        ['hire_year_jalali' => 1404, 'unit_code' => 1, 'unit_name' => 'ظ…ط¯غŒط±غŒطھ', 'job_title' => 'ظ…ط¯غŒط± ط¯ط§ط®ظ„غŒ / ط¯ط³طھغŒط§ط± ظ…ط¯غŒط±ع©ظ„', 'secondary_position' => 'ظ…ط¯غŒط± ط³ط§ظ„ظ†', 'full_name' => 'ظ…ط­ظ…ط¯ط¬ظˆط§ط¯ ط·ط§ظ„ط¨غŒ'],
        ['hire_year_jalali' => 1404, 'unit_code' => 2, 'unit_name' => 'ظ…ط§ظ„غŒ / ط§ط¯ط§ط±غŒ', 'job_title' => 'ع©ط§ط±ظ…ظ†ط¯ ط­ط³ط§ط¨ط¯ط§ط±غŒ / ط§ط¯ط§ط±غŒ', 'full_name' => 'ط³ظ¾غŒط¯ظ‡ ط³ط§ط¯ط§طھ ظ†ط¬ط§ط± ظ„ظ†ط¨ط§ظ†غŒ'],
        ['hire_year_jalali' => 1404, 'unit_code' => 3, 'unit_name' => 'ط®ط±غŒط¯ ظˆ ط§ظ†ط¨ط§ط±', 'job_title' => 'ع©ظ…ع©â€Œط§ظ†ط¨ط§ط±ط¯ط§ط±', 'secondary_position' => 'ظ‡ظ…ع©ط§ط±غŒ ظپظ†غŒ ط¯ط± ط´ظ†ط§ط³ط§غŒغŒ ظ‚ط·ط¹ط§طھ', 'full_name' => 'ط§ظ…غŒط¯ ط³ظ„غŒظ…غŒ'],
        ['hire_year_jalali' => 1404, 'unit_code' => 6, 'unit_name' => 'ط¨ط±ظ‚', 'job_title' => 'ع©ظ…ع© ط¨ط±ظ‚â€Œع©ط§ط±', 'full_name' => 'ط³ظ‡غŒظ„ ط³ظ„ط·ط§ظ†غŒ'],
        ['hire_year_jalali' => 1404, 'unit_code' => 4, 'unit_name' => 'CRM / طھط¬ط±ط¨ظ‡ ظ…ط´طھط±غŒ', 'job_title' => 'ع©ط§ط±ط´ظ†ط§ط³ ظ¾ط°غŒط±ط´طŒ ع©ظ†طھط±ظ„ ع©غŒظپغŒطھ ظˆ طھط±ط®غŒطµ', 'full_name' => 'ظ…ظ‡ط±ط¯ط§ط¯ طµط§ط¯ظ‚غŒ'],
        ['hire_year_jalali' => 1404, 'unit_code' => 5, 'unit_name' => 'ظ…ع©ط§ظ†غŒع©', 'job_title' => 'ظ…ع©ط§ظ†غŒع©', 'full_name' => 'ط¹ظ„غŒ ط±ط¶ط§ ع©ط«غŒط±غŒ'],
        ['hire_year_jalali' => 1404, 'unit_code' => 5, 'unit_name' => 'ظ…ع©ط§ظ†غŒع©', 'job_title' => 'ظ…ع©ط§ظ†غŒع©', 'full_name' => 'ط§ظ…غŒط±ط­ط³غŒظ† ظ†طµط±'],
        ['hire_year_jalali' => 1405, 'unit_code' => 2, 'unit_name' => 'ظ…ط§ظ„غŒ / ط§ط¯ط§ط±غŒ', 'job_title' => 'ظ…ط¯غŒط± ط­ط³ط§ط¨ط¯ط§ط±غŒ / ط§ط¯ط§ط±غŒ', 'full_name' => 'ط­ظ…غŒط¯ ط±ط¶ط§ ط±ط­ظ…ط§ظ†غŒ'],
        ['hire_year_jalali' => 1405, 'unit_code' => 3, 'unit_name' => 'ط®ط±غŒط¯ ظˆ ط§ظ†ط¨ط§ط±', 'job_title' => 'ط³ط±ظ¾ط±ط³طھ ط§ظ†ط¨ط§ط±', 'full_name' => 'ط­ظ…غŒط¯ ظپطھط­غŒ'],
        ['hire_year_jalali' => 1405, 'unit_code' => 0, 'unit_name' => 'ظ†ط±ظ…â€Œط§ظپط²ط§ط±', 'job_title' => 'ظ…ط§ظ„ع© ظ…ط­طµظˆظ„ ظˆ ظ…ط¯غŒط± ط³ط§ظ…ط§ظ†ظ‡ MOGHARE360', 'full_name' => 'ط¹ظ„غŒ ظ…ط§ظ‡ط±ط§ظ„ظ†ظ‚ط´', 'reserved_personnel_code' => P360HR_RESERVED_CODE, 'is_software_owner' => true],
        ['hire_year_jalali' => 1405, 'unit_code' => 4, 'unit_name' => 'CRM / طھط¬ط±ط¨ظ‡ ظ…ط´طھط±غŒ', 'job_title' => 'ط§ظ…ظˆط± ظ…ط´طھط±غŒط§ظ† / ط¯غŒط¬غŒطھط§ظ„ ظ…ط§ط±ع©طھغŒظ†ع¯', 'full_name' => 'ط¹ط·غŒظ‡ ظ†ط§ط¸ظ…غŒ'],
        ['hire_year_jalali' => 1405, 'unit_code' => 8, 'unit_name' => 'ظ„ط¬ط³طھغŒع© / طھط¯ط§ط±ع©ط§طھ', 'job_title' => 'ع©ط§ط±ط´ظ†ط§ط³ طھط¯ط§ط±ع©ط§طھ', 'full_name' => 'ط¹ط¨ط§ط³ ط®ظ„غŒظ„غŒ'],
        ['hire_year_jalali' => 1405, 'unit_code' => 6, 'unit_name' => 'ط¨ط±ظ‚', 'job_title' => 'ط¨ط±ظ‚â€Œع©ط§ط± ط§ط±ط´ط¯', 'full_name' => 'ط¯ط§ظˆظˆط¯ ظ…ط­ظ…ظˆط¯غŒ'],
    ];
}

$conn = p360hr_conn();
echo "DB=moghare360_ERP\n";
echo "EMP_BEFORE=" . (p360hr_scalar($conn, 'SELECT COUNT(*) FROM dbo.p360_employees') ?? '?') . "\n";

@odbc_autocommit($conn, false);

$stats = [
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'users_created' => 0,
    'users_updated' => 0,
    'memberships' => 0,
    'conflicts' => [],
];

try {
    // Schema version table
    if (!p360hr_table_exists($conn, 'p360_hr_schema_version')) {
        p360hr_exec($conn, "
            CREATE TABLE dbo.p360_hr_schema_version (
                version_key nvarchar(80) NOT NULL PRIMARY KEY,
                applied_at datetime2 NOT NULL CONSTRAINT DF_p360_hr_schema_version_applied DEFAULT SYSUTCDATETIME(),
                notes nvarchar(400) NULL
            )
        ");
        echo "CREATED p360_hr_schema_version\n";
    }

    // core_users password policy columns
    p360hr_ensure_column($conn, 'core_users', 'must_change_password', 'must_change_password bit NOT NULL CONSTRAINT DF_core_users_must_change_password DEFAULT 0');
    p360hr_ensure_column($conn, 'core_users', 'password_changed_at', 'password_changed_at datetime2 NULL');
    p360hr_ensure_column($conn, 'core_users', 'password_reset_required_at', 'password_reset_required_at datetime2 NULL');
    p360hr_ensure_column($conn, 'core_users', 'password_reset_by_user_id', 'password_reset_by_user_id int NULL');

    // p360_employees HR master extensions
    p360hr_ensure_column($conn, 'p360_employees', 'core_user_id', 'core_user_id int NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'lifecycle_state', "lifecycle_state nvarchar(30) NOT NULL CONSTRAINT DF_p360_employees_lifecycle DEFAULT N'ACTIVE'");
    p360hr_ensure_column($conn, 'p360_employees', 'hire_year_jalali', 'hire_year_jalali int NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'unit_code', 'unit_code int NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'unit_name', 'unit_name nvarchar(120) NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'job_title', 'job_title nvarchar(160) NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'secondary_position', 'secondary_position nvarchar(160) NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'personnel_photo_path', 'personnel_photo_path nvarchar(500) NULL');
    p360hr_ensure_column($conn, 'p360_employees', 'is_software_owner_reserve', 'is_software_owner_reserve bit NOT NULL CONSTRAINT DF_p360_employees_soft_owner DEFAULT 0');

    // Unique filtered index for core_user_id when present
    $idx = p360hr_one($conn, "SELECT 1 AS x FROM sys.indexes WHERE name='UX_p360_employees_core_user_id' AND object_id=OBJECT_ID('dbo.p360_employees')");
    if ($idx === null) {
        p360hr_exec($conn, "CREATE UNIQUE INDEX UX_p360_employees_core_user_id ON dbo.p360_employees(core_user_id) WHERE core_user_id IS NOT NULL");
        echo "CREATED UX_p360_employees_core_user_id\n";
    }

    // Device user mapping
    if (!p360hr_table_exists($conn, 'p360_employee_device_users')) {
        p360hr_exec($conn, "
            CREATE TABLE dbo.p360_employee_device_users (
                id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                device_id int NOT NULL,
                employee_id int NOT NULL,
                device_user_code nvarchar(80) NOT NULL,
                is_active bit NOT NULL CONSTRAINT DF_p360_edu_active DEFAULT 1,
                verified_at datetime2 NULL,
                verified_by_user_id int NULL,
                created_at datetime2 NOT NULL CONSTRAINT DF_p360_edu_created DEFAULT SYSUTCDATETIME(),
                CONSTRAINT FK_p360_edu_device FOREIGN KEY (device_id) REFERENCES dbo.p360_attendance_devices(id),
                CONSTRAINT FK_p360_edu_employee FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id),
                CONSTRAINT UX_p360_edu_device_code UNIQUE (device_id, device_user_code)
            )
        ");
        echo "CREATED p360_employee_device_users\n";
    }

    // Document security extensions
    p360hr_ensure_column($conn, 'p360_employee_documents', 'doc_category', 'doc_category nvarchar(40) NULL');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'slot_no', 'slot_no int NULL');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'mime_type', 'mime_type nvarchar(120) NULL');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'sha256', 'sha256 char(64) NULL');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'version_no', 'version_no int NOT NULL CONSTRAINT DF_p360_edoc_ver DEFAULT 1');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'is_final', 'is_final bit NOT NULL CONSTRAINT DF_p360_edoc_final DEFAULT 0');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'storage_path', 'storage_path nvarchar(500) NULL');
    p360hr_ensure_column($conn, 'p360_employee_documents', 'uploaded_by_user_id', 'uploaded_by_user_id int NULL');

    // Password reset audit
    if (!p360hr_table_exists($conn, 'p360_password_reset_audit')) {
        p360hr_exec($conn, "
            CREATE TABLE dbo.p360_password_reset_audit (
                id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
                target_user_id int NOT NULL,
                target_employee_id int NULL,
                actor_user_id int NOT NULL,
                reason_fa nvarchar(400) NOT NULL,
                created_at datetime2 NOT NULL CONSTRAINT DF_p360_pra_created DEFAULT SYSUTCDATETIME()
            )
        ");
        echo "CREATED p360_password_reset_audit\n";
    }

    // Shift rules foundation
    if (!p360hr_table_exists($conn, 'p360_hr_shift_rules')) {
        p360hr_exec($conn, "
            CREATE TABLE dbo.p360_hr_shift_rules (
                rule_key nvarchar(40) NOT NULL PRIMARY KEY,
                day_scope nvarchar(40) NOT NULL,
                start_time time NOT NULL,
                end_time time NOT NULL,
                break_minutes int NOT NULL,
                break_label_fa nvarchar(80) NULL,
                scheduled_minutes int NOT NULL,
                friday_closed bit NOT NULL CONSTRAINT DF_p360_hsr_fri DEFAULT 0,
                requires_approval bit NOT NULL CONSTRAINT DF_p360_hsr_appr DEFAULT 0,
                is_active bit NOT NULL CONSTRAINT DF_p360_hsr_act DEFAULT 1,
                notes_fa nvarchar(200) NULL
            )
        ");
        echo "CREATED p360_hr_shift_rules\n";
    }
    $shiftSeeds = [
        ['SAT_WED', 'saturday_wednesday', '08:00:00', '18:00:00', 60, 'ظ†ط§ظ‡ط§ط± ظˆ ظ†ظ…ط§ط²', 540, 0, 0],
        ['THURSDAY', 'thursday', '08:00:00', '15:00:00', 0, null, 420, 0, 0],
        ['HOLIDAY', 'official_holiday', '08:00:00', '15:00:00', 0, null, 420, 0, 0],
        ['FRIDAY', 'friday', '08:00:00', '08:00:00', 0, null, 0, 1, 1],
    ];
    foreach ($shiftSeeds as $s) {
        $ex = p360hr_one($conn, 'SELECT rule_key FROM dbo.p360_hr_shift_rules WHERE rule_key=?', [$s[0]]);
        if ($ex === null) {
            // Bind times as strings with explicit CAST to avoid ODBC time cast errors.
            p360hr_exec($conn, 'INSERT INTO dbo.p360_hr_shift_rules (rule_key, day_scope, start_time, end_time, break_minutes, break_label_fa, scheduled_minutes, friday_closed, requires_approval, notes_fa)
                VALUES (?, ?, CAST(? AS time), CAST(? AS time), ?, ?, ?, ?, ?, ?)',
                [$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], 'P360HR foundation']);
        }
    }

    // HR request type catalog
    if (!p360hr_table_exists($conn, 'p360_hr_request_types')) {
        p360hr_exec($conn, "
            CREATE TABLE dbo.p360_hr_request_types (
                request_type_code nvarchar(40) NOT NULL PRIMARY KEY,
                title_fa nvarchar(120) NOT NULL,
                is_active bit NOT NULL CONSTRAINT DF_p360_hrt_act DEFAULT 1,
                sort_order int NOT NULL CONSTRAINT DF_p360_hrt_sort DEFAULT 100
            )
        ");
        echo "CREATED p360_hr_request_types\n";
    }
    $reqTypes = [
        ['OVERTIME', 'ط¯ط±ط®ظˆط§ط³طھ ط§ط¶ط§ظپظ‡â€Œع©ط§ط±غŒ', 10],
        ['ADVANCE', 'ط¯ط±ط®ظˆط§ط³طھ ظ…ط³ط§ط¹ط¯ظ‡', 20],
        ['LOAN', 'ط¯ط±ط®ظˆط§ط³طھ ظˆط§ظ…', 30],
        ['LEAVE_DAILY', 'ط¯ط±ط®ظˆط§ط³طھ ظ…ط±ط®طµغŒ ط±ظˆط²ط§ظ†ظ‡', 40],
        ['LEAVE_HOURLY', 'ط¯ط±ط®ظˆط§ط³طھ ظ…ط±ط®طµغŒ ط³ط§ط¹طھغŒ', 50],
        ['LEAVE_SICK', 'ط¯ط±ط®ظˆط§ط³طھ ظ…ط±ط®طµغŒ ط§ط³طھط¹ظ„ط§ط¬غŒ', 60],
        ['MISSION', 'ط¯ط±ط®ظˆط§ط³طھ ظ…ط£ظ…ظˆط±غŒطھ', 70],
        ['SHIFT_CHANGE', 'ط¯ط±ط®ظˆط§ط³طھ طھط؛غŒغŒط± ط´غŒظپطھ', 80],
        ['ATTENDANCE_CORRECTION', 'ط¯ط±ط®ظˆط§ط³طھ ط§طµظ„ط§ط­ ظˆط±ظˆط¯ ظˆ ط®ط±ظˆط¬', 90],
        ['FRIDAY_ATTENDANCE', 'ط¯ط±ط®ظˆط§ط³طھ طھط£غŒغŒط¯ ط­ط¶ظˆط± ط±ظˆط² ط¬ظ…ط¹ظ‡', 100],
        ['EMPLOYMENT_CERT', 'ط¯ط±ط®ظˆط§ط³طھ ع¯ظˆط§ظ‡غŒ ط§ط´طھط؛ط§ظ„', 110],
        ['PAYROLL_FOLLOWUP', 'ط¯ط±ط®ظˆط§ط³طھ ظ¾غŒع¯غŒط±غŒ ط­ظ‚ظˆظ‚ غŒط§ ع©ط³ظˆط±ط§طھ', 120],
        ['OTHER', 'ط³ط§غŒط± ط¯ط±ط®ظˆط§ط³طھâ€Œظ‡ط§غŒ ظ¾ط±ط³ظ†ظ„غŒ', 130],
    ];
    foreach ($reqTypes as $rt) {
        $ex = p360hr_one($conn, 'SELECT request_type_code FROM dbo.p360_hr_request_types WHERE request_type_code=?', [$rt[0]]);
        if ($ex === null) {
            p360hr_exec($conn, 'INSERT INTO dbo.p360_hr_request_types (request_type_code, title_fa, sort_order) VALUES (?,?,?)', $rt);
        }
    }

    // Extend employee requests for typed HR requests
    p360hr_ensure_column($conn, 'p360_employee_requests', 'request_type_code', 'request_type_code nvarchar(40) NULL');
    p360hr_ensure_column($conn, 'p360_employee_requests', 'request_no', 'request_no nvarchar(40) NULL');
    p360hr_ensure_column($conn, 'p360_employee_requests', 'submitted_at', 'submitted_at datetime2 NULL');
    p360hr_ensure_column($conn, 'p360_employee_requests', 'decided_at', 'decided_at datetime2 NULL');
    p360hr_ensure_column($conn, 'p360_employee_requests', 'current_approver_user_id', 'current_approver_user_id int NULL');

    // Contract stage foundation table (draft-safe; no fake final)
    if (!p360hr_table_exists($conn, 'p360_hr_employment_contract_flow')) {
        p360hr_exec($conn, "
            CREATE TABLE dbo.p360_hr_employment_contract_flow (
                id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
                employee_id int NOT NULL,
                contract_version int NOT NULL,
                stage_code nvarchar(40) NOT NULL,
                contract_hash char(64) NULL,
                body_snapshot nvarchar(max) NULL,
                acceptance_checked bit NOT NULL CONSTRAINT DF_p360_hecf_acc DEFAULT 0,
                accepted_at datetime2 NULL,
                accepted_ip nvarchar(64) NULL,
                accepted_ua nvarchar(400) NULL,
                otp_verified_at datetime2 NULL,
                signed_at datetime2 NULL,
                final_pdf_path nvarchar(500) NULL,
                final_pdf_hash char(64) NULL,
                is_locked bit NOT NULL CONSTRAINT DF_p360_hecf_lock DEFAULT 0,
                created_at datetime2 NOT NULL CONSTRAINT DF_p360_hecf_created DEFAULT SYSUTCDATETIME(),
                updated_at datetime2 NULL,
                CONSTRAINT FK_p360_hecf_emp FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id)
            )
        ");
        echo "CREATED p360_hr_employment_contract_flow\n";
    }

    // Seed / reconcile 25 personnel
    $tempHash = password_hash(P360HR_TEMP_PASSWORD, PASSWORD_DEFAULT);
    foreach (p360hr_personnel_seed() as $person) {
        $full = (string)$person['full_name'];
        $norm = p360hr_norm_name($full);
        $reserved = (string)($person['reserved_personnel_code'] ?? '');
        $isOwnerReserve = !empty($person['is_software_owner']);

        // Find by reserved code or normalized full name match on existing foundation records
        $existing = null;
        if ($reserved !== '') {
            $existing = p360hr_one($conn, 'SELECT TOP 1 * FROM dbo.p360_employees WHERE employee_code=?', [$reserved]);
        }
        if ($existing === null) {
            $cands = p360hr_rows($conn, 'SELECT employee_id, first_name, last_name, employee_code, core_user_id FROM dbo.p360_employees WHERE is_software_owner_reserve=1 OR (hire_year_jalali IS NOT NULL)');
            foreach ($cands as $c) {
                $cn = p360hr_norm_name(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')));
                if ($cn === $norm) {
                    $existing = $c;
                    break;
                }
            }
        }

        [$fn, $ln] = p360hr_split_name($full);
        $unitCode = (int)$person['unit_code'];
        $unitName = (string)$person['unit_name'];
        $job = (string)$person['job_title'];
        $sec = (string)($person['secondary_position'] ?? '');
        $hy = (int)$person['hire_year_jalali'];

        if ($existing !== null) {
            $eid = (int)$existing['employee_id'];
            p360hr_exec($conn, 'UPDATE dbo.p360_employees SET first_name=?, last_name=?, hire_year_jalali=?, unit_code=?, unit_name=?, job_title=?, secondary_position=?, lifecycle_state=N\'ACTIVE\', employee_status=N\'active\', is_active=1, company_id=?, is_software_owner_reserve=? WHERE employee_id=?',
                [$fn, $ln, $hy, $unitCode, $unitName, $job, $sec !== '' ? $sec : null, P360HR_COMPANY_ID, $isOwnerReserve ? 1 : 0, $eid]);
            if ($reserved !== '' && (string)($existing['employee_code'] ?? '') !== $reserved) {
                $clash = p360hr_one($conn, 'SELECT employee_id FROM dbo.p360_employees WHERE employee_code=? AND employee_id<>?', [$reserved, $eid]);
                if ($clash !== null) {
                    $stats['conflicts'][] = "reserved_code_clash:$reserved";
                    $stats['skipped']++;
                    continue;
                }
                p360hr_exec($conn, 'UPDATE dbo.p360_employees SET employee_code=? WHERE employee_id=?', [$reserved, $eid]);
            }
            $stats['updated']++;
            $employeeId = $eid;
            $personnelCode = $reserved !== '' ? $reserved : (string)$existing['employee_code'];
            if ($reserved === '' && !preg_match('/^M360-\d{4}$/', $personnelCode)) {
                // Repair code from employee_id if still legacy-like on foundation rows only
                try {
                    $personnelCode = p360hr_personnel_code_from_id($employeeId);
                    p360hr_exec($conn, 'UPDATE dbo.p360_employees SET employee_code=? WHERE employee_id=?', [$personnelCode, $employeeId]);
                } catch (Throwable $e) {
                    $stats['conflicts'][] = $e->getMessage();
                }
            }
        } else {
            // Insert placeholder then assign code from identity (except reserved)
            $placeholder = 'TMP-' . strtoupper(bin2hex(random_bytes(4)));
            if ($reserved !== '') {
                $placeholder = $reserved;
                $clash = p360hr_one($conn, 'SELECT employee_id FROM dbo.p360_employees WHERE employee_code=?', [$reserved]);
                if ($clash !== null) {
                    $stats['conflicts'][] = "reserved_exists:$reserved";
                    $stats['skipped']++;
                    continue;
                }
            }
            p360hr_exec($conn, 'INSERT INTO dbo.p360_employees
                (employee_code, first_name, last_name, company_id, hire_date, employee_status, is_active, created_by, hire_year_jalali, unit_code, unit_name, job_title, secondary_position, lifecycle_state, is_software_owner_reserve)
                VALUES (?,?,?,?,NULL,N\'active\',1,NULL,?,?,?,?,?,N\'ACTIVE\',?)',
                [$placeholder, $fn, $ln, P360HR_COMPANY_ID, $hy, $unitCode, $unitName, $job, $sec !== '' ? $sec : null, $isOwnerReserve ? 1 : 0]);
            $employeeId = (int)(p360hr_scalar($conn, 'SELECT TOP 1 employee_id FROM dbo.p360_employees WHERE employee_code=?', [$placeholder]) ?? 0);
            if ($employeeId < 1) {
                throw new RuntimeException('insert identity failed for ' . $full);
            }
            if ($reserved === '') {
                // Block if next id would exceed 9999
                if ($employeeId > 9999) {
                    throw new RuntimeException('ط¸ط±ظپغŒطھ ع©ط¯ ظ¾ط±ط³ظ†ظ„غŒ ط§ط³طھط§ظ†ط¯ط§ط±ط¯ ط¨ظ‡ ظ¾ط§غŒط§ظ† ط±ط³غŒط¯ظ‡ ط§ط³طھ. طھظˆط³ط¹ظ‡ ط¨ط§ط²ظ‡ ع©ط¯ظ‡ط§ ظ†غŒط§ط²ظ…ظ†ط¯ طھط£غŒغŒط¯ ظ…ط§ظ„ع© ط³ط§ظ…ط§ظ†ظ‡ ط§ط³طھ.');
                }
                $personnelCode = p360hr_personnel_code_from_id($employeeId);
                $clash = p360hr_one($conn, 'SELECT employee_id FROM dbo.p360_employees WHERE employee_code=? AND employee_id<>?', [$personnelCode, $employeeId]);
                if ($clash !== null) {
                    $stats['conflicts'][] = "code_clash:$personnelCode";
                    // leave TMP code; skip user link
                    $stats['skipped']++;
                    continue;
                }
                p360hr_exec($conn, 'UPDATE dbo.p360_employees SET employee_code=? WHERE employee_id=?', [$personnelCode, $employeeId]);
            } else {
                $personnelCode = $reserved;
            }
            $stats['inserted']++;
        }

        // Central user link
        $user = p360hr_one($conn, 'SELECT TOP 1 user_id, username, is_system_owner, is_login_enabled FROM dbo.core_users WHERE username=?', [$personnelCode]);
        if ($isOwnerReserve) {
            // Prefer Amir 20016 as canonical active user
            $amir = p360hr_one($conn, 'SELECT TOP 1 user_id, username FROM dbo.core_users WHERE user_id=20016');
            $mahin = p360hr_one($conn, 'SELECT TOP 1 user_id, username, LEN(password_hash) hash_len, LEFT(password_hash,4) hash_prefix FROM dbo.core_users WHERE user_id=10001');
            if ($amir === null) {
                $stats['conflicts'][] = 'OWNER_IDENTITY_BLOCKER:Amir_missing';
            } else {
                $canonicalId = 20016;
                // Transfer Mahin hash only if bcrypt-compatible (prefix $2y$ / $2a$ / $2b$) without printing value
                if ($mahin !== null && in_array((string)$mahin['hash_prefix'], ['$2y$', '$2a$', '$2b$'], true) && (int)$mahin['hash_len'] >= 50) {
                    p360hr_exec($conn, 'UPDATE dbo.core_users SET password_hash=(SELECT password_hash FROM dbo.core_users WHERE user_id=10001), username=?, full_name=?, is_system_owner=1, is_login_enabled=1, lifecycle_state=N\'ACTIVE\', must_change_password=0, updated_at=SYSUTCDATETIME() WHERE user_id=?',
                        [P360HR_RESERVED_CODE, 'ط¹ظ„غŒ ظ…ط§ظ‡ط±ط§ظ„ظ†ظ‚ط´', $canonicalId]);
                    $stats['users_updated']++;
                    echo "OWNER_CANONICAL_USER=20016 username=" . P360HR_RESERVED_CODE . " hash_transfer=yes\n";
                    // Disable duplicate Mahin login only after canonical username set
                    $check = p360hr_one($conn, 'SELECT username, is_login_enabled FROM dbo.core_users WHERE user_id=?', [$canonicalId]);
                    if ($check && (string)$check['username'] === P360HR_RESERVED_CODE) {
                        p360hr_exec($conn, 'UPDATE dbo.core_users SET is_login_enabled=0, updated_at=SYSUTCDATETIME() WHERE user_id=10001 AND username=N\'mahin.paradigm.owner\'');
                        echo "MAHIN_LOGIN_DISABLED=yes\n";
                    }
                } else {
                    p360hr_exec($conn, 'UPDATE dbo.core_users SET username=?, full_name=?, is_system_owner=1, is_login_enabled=1, lifecycle_state=N\'ACTIVE\', updated_at=SYSUTCDATETIME() WHERE user_id=?',
                        [P360HR_RESERVED_CODE, 'ط¹ظ„غŒ ظ…ط§ظ‡ط±ط§ظ„ظ†ظ‚ط´', $canonicalId]);
                    $stats['users_updated']++;
                    echo "OWNER_PASSWORD_TRANSFER_BLOCKER=hash_incompatible_or_missing\n";
                }
                p360hr_exec($conn, 'UPDATE dbo.p360_employees SET core_user_id=? WHERE employee_id=?', [$canonicalId, $employeeId]);
                $mem = p360hr_one($conn, 'SELECT company_user_id FROM dbo.erp_company_users WHERE user_id=? AND company_id=? AND is_active=1', [$canonicalId, P360HR_COMPANY_ID]);
                if ($mem === null) {
                    p360hr_exec($conn, 'INSERT INTO dbo.erp_company_users (company_id, user_id, role_code, is_active) VALUES (?,?,N\'SYSTEM_ADMIN\',1)', [P360HR_COMPANY_ID, $canonicalId]);
                    $stats['memberships']++;
                }
            }
            continue;
        }

        // Ordinary employee central account
        if ($user === null) {
            $nextUid = (int)(p360hr_scalar($conn, 'SELECT ISNULL(MAX(user_id), 20000) + 1 FROM dbo.core_users') ?? 0);
            if ($nextUid < 1) {
                throw new RuntimeException('unable to allocate core_users.user_id');
            }
            p360hr_exec($conn, 'INSERT INTO dbo.core_users (user_id, username, password_hash, full_name, lifecycle_state, is_system_owner, is_login_enabled, must_change_password, password_changed_at, created_at)
                VALUES (?,?,?,?,N\'ACTIVE\',0,1,1,NULL,SYSUTCDATETIME())', [$nextUid, $personnelCode, $tempHash, $full]);
            $uid = $nextUid;
            $stats['users_created']++;
        } else {
            $uid = (int)$user['user_id'];
            p360hr_exec($conn, 'UPDATE dbo.core_users SET full_name=?, lifecycle_state=N\'ACTIVE\', is_login_enabled=1, updated_at=SYSUTCDATETIME() WHERE user_id=?', [$full, $uid]);
            $stats['users_updated']++;
        }
        if ($uid > 0) {
            p360hr_exec($conn, 'UPDATE dbo.p360_employees SET core_user_id=? WHERE employee_id=?', [$uid, $employeeId]);
            $mem = p360hr_one($conn, 'SELECT company_user_id FROM dbo.erp_company_users WHERE user_id=? AND company_id=?', [$uid, P360HR_COMPANY_ID]);
            if ($mem === null) {
                p360hr_exec($conn, 'INSERT INTO dbo.erp_company_users (company_id, user_id, role_code, is_active) VALUES (?,?,N\'EMPLOYEE\',1)', [P360HR_COMPANY_ID, $uid]);
                $stats['memberships']++;
            } else {
                p360hr_exec($conn, 'UPDATE dbo.erp_company_users SET is_active=1 WHERE user_id=? AND company_id=?', [$uid, P360HR_COMPANY_ID]);
            }
        }
    }

    // Mark schema version
    $ver = p360hr_one($conn, 'SELECT version_key FROM dbo.p360_hr_schema_version WHERE version_key=?', [P360HR_SCHEMA_VERSION]);
    if ($ver === null) {
        p360hr_exec($conn, 'INSERT INTO dbo.p360_hr_schema_version (version_key, notes) VALUES (?,?)', [P360HR_SCHEMA_VERSION, 'PeopleOS HR foundation']);
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('commit failed');
    }
    echo "TRANSACTION=COMMITTED\n";
} catch (Throwable $e) {
    @odbc_rollback($conn);
    echo "TRANSACTION=ROLLED_BACK\n";
    echo "ERROR=" . $e->getMessage() . "\n";
    exit(1);
}

echo "EMP_AFTER=" . (p360hr_scalar($conn, 'SELECT COUNT(*) FROM dbo.p360_employees') ?? '?') . "\n";
echo "FOUNDATION_ROWS=" . (p360hr_scalar($conn, 'SELECT COUNT(*) FROM dbo.p360_employees WHERE hire_year_jalali IS NOT NULL') ?? '?') . "\n";
echo "INSERTED={$stats['inserted']}\n";
echo "UPDATED={$stats['updated']}\n";
echo "SKIPPED={$stats['skipped']}\n";
echo "USERS_CREATED={$stats['users_created']}\n";
echo "USERS_UPDATED={$stats['users_updated']}\n";
echo "MEMBERSHIPS={$stats['memberships']}\n";
if ($stats['conflicts'] !== []) {
    echo "CONFLICTS=" . implode(' || ', $stats['conflicts']) . "\n";
}
echo "RESERVED=" . (p360hr_scalar($conn, 'SELECT employee_code FROM dbo.p360_employees WHERE employee_code=?', [P360HR_RESERVED_CODE]) ?? 'MISSING') . "\n";
echo "CANONICAL_USER=" . (p360hr_scalar($conn, 'SELECT CAST(user_id AS nvarchar(20))+N\'|\'+username FROM dbo.core_users WHERE username=?', [P360HR_RESERVED_CODE]) ?? 'MISSING') . "\n";
echo "MIGRATION_OK\n";
@odbc_close($conn);
