<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * R1 gap migration: occupational medicine dates + reminder recipients + manager relation.
 * Database: moghare360_ERP only.
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-config-loader.php';

function r1_conn()
{
    $c = erp_load_config()['database'];
    if (trim((string)$c['name']) !== 'moghare360_ERP') {
        throw new RuntimeException('Refuse non-canonical database');
    }
    $server = trim((string)$c['server']);
    $trusted = (bool)$c['trusted_connection'];
    $user = $trusted ? '' : (string)$c['username'];
    $pass = $trusted ? '' : (string)$c['password'];
    foreach (['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'] as $d) {
        $dsn = "Driver={{$d}};Server={$server};Database=moghare360_ERP;";
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
    throw new RuntimeException('connect failed');
}

function r1_exec($conn, string $sql, array $params = []): void
{
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        throw new RuntimeException(odbc_errormsg($conn) . ' :: ' . substr($sql, 0, 200));
    }
}

function r1_one($conn, string $sql, array $params = []): ?array
{
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        throw new RuntimeException(odbc_errormsg($conn));
    }
    $r = odbc_fetch_array($st);
    if (!is_array($r)) {
        return null;
    }
    $n = [];
    foreach ($r as $k => $v) {
        $n[strtolower((string)$k)] = $v;
    }
    return $n;
}

function r1_exists($conn, string $table): bool
{
    return r1_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$table]) !== null;
}

function r1_col($conn, string $table, string $col): bool
{
    return r1_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $col]) !== null;
}

function r1_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (!r1_col($conn, $table, $col)) {
        r1_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}");
        echo "COL {$table}.{$col}\n";
    }
}

$conn = r1_conn();
@odbc_autocommit($conn, false);

try {
    if (r1_exists($conn, 'p360_hr_personnel_profile')) {
        r1_ensure_col($conn, 'p360_hr_personnel_profile', 'direct_manager_employee_id', 'direct_manager_employee_id int NULL');
    }

    if (!r1_exists($conn, 'p360_hr_occ_med_reports')) {
        r1_exec($conn, "
        CREATE TABLE dbo.p360_hr_occ_med_reports (
            report_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            employee_id int NOT NULL,
            document_id int NULL,
            examination_date date NULL,
            report_issue_date date NULL,
            valid_from_date date NULL,
            expiry_date date NULL,
            no_expiry bit NOT NULL CONSTRAINT DF_p360hom_nx DEFAULT 0,
            result_status nvarchar(40) NULL,
            medical_center_name nvarchar(200) NULL,
            report_ref nvarchar(120) NULL,
            notes nvarchar(400) NULL,
            lifecycle_state nvarchar(40) NOT NULL CONSTRAINT DF_p360hom_ls DEFAULT N'DRAFT',
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hom_cr DEFAULT SYSUTCDATETIME(),
            updated_at datetime2 NULL,
            created_by_user_id int NULL,
            updated_by_user_id int NULL,
            CONSTRAINT FK_p360hom_emp FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id)
        )");
        echo "CREATED p360_hr_occ_med_reports\n";
    }

    if (!r1_exists($conn, 'p360_hr_contract_reminder_recipients')) {
        r1_exec($conn, "
        CREATE TABLE dbo.p360_hr_contract_reminder_recipients (
            id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            reminder_id bigint NOT NULL,
            recipient_user_id int NOT NULL,
            recipient_type nvarchar(40) NOT NULL,
            recipient_status nvarchar(30) NOT NULL CONSTRAINT DF_p360hcrr_st DEFAULT N'NEW',
            seen_at datetime2 NULL,
            actioned_at datetime2 NULL,
            recommendation_code nvarchar(40) NULL,
            recommendation_note nvarchar(400) NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hcrr_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT FK_p360hcrr_r FOREIGN KEY (reminder_id) REFERENCES dbo.p360_hr_contract_reminders(reminder_id),
            CONSTRAINT UX_p360hcrr UNIQUE (reminder_id, recipient_user_id, recipient_type)
        )");
        echo "CREATED p360_hr_contract_reminder_recipients\n";
    }

    $ver = r1_one($conn, "SELECT version_key FROM dbo.p360_hr_schema_version WHERE version_key=N'P360HR_IDENTITY_DATE_R1_20260803'");
    if ($ver === null && r1_exists($conn, 'p360_hr_schema_version')) {
        r1_exec($conn, "INSERT INTO dbo.p360_hr_schema_version (version_key, notes) VALUES (N'P360HR_IDENTITY_DATE_R1_20260803', N'Occ-med dates + manager reminder recipients')");
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('commit failed');
    }
    echo "TRANSACTION=COMMITTED\nMIGRATION_OK\n";
} catch (Throwable $e) {
    @odbc_rollback($conn);
    echo "TRANSACTION=ROLLED_BACK\nERROR=" . $e->getMessage() . "\n";
    exit(1);
}
