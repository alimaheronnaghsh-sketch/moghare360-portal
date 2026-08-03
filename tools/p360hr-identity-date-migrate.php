<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * Identity + duration + reminder schema (idempotent). moghare360_ERP only.
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-config-loader.php';

function idx_conn()
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

function idx_exec($conn, string $sql, array $params = []): void
{
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        throw new RuntimeException(odbc_errormsg($conn) . ' :: ' . substr($sql, 0, 180));
    }
}

function idx_one($conn, string $sql, array $params = []): ?array
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

function idx_exists($conn, string $table): bool
{
    return idx_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$table]) !== null;
}

function idx_col($conn, string $table, string $col): bool
{
    return idx_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $col]) !== null;
}

function idx_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (!idx_col($conn, $table, $col)) {
        idx_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}");
        echo "COL {$table}.{$col}\n";
    }
}

$conn = idx_conn();
@odbc_autocommit($conn, false);

try {
    idx_ensure_col($conn, 'p360_employees', 'display_name_override', 'display_name_override nvarchar(200) NULL');
    idx_ensure_col($conn, 'p360_employees', 'imported_full_name', 'imported_full_name nvarchar(200) NULL');
    idx_ensure_col($conn, 'p360_employees', 'hire_date_unknown', 'hire_date_unknown bit NOT NULL CONSTRAINT DF_p360emp_hdu DEFAULT 0');

    if (!idx_exists($conn, 'p360_hr_identity_audit')) {
        idx_exec($conn, "
        CREATE TABLE dbo.p360_hr_identity_audit (
            id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            employee_id int NOT NULL,
            field_name nvarchar(60) NOT NULL,
            old_value nvarchar(400) NULL,
            new_value nvarchar(400) NULL,
            actor_user_id int NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hia_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT FK_p360hia_emp FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id)
        )");
        echo "CREATED p360_hr_identity_audit\n";
    }

    if (idx_exists($conn, 'p360_hr_contracts')) {
        idx_ensure_col($conn, 'p360_hr_contracts', 'duration_months', 'duration_months int NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'duration_unit', 'duration_unit nvarchar(20) NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'duration_preset', 'duration_preset nvarchar(40) NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'calculation_rule_version', 'calculation_rule_version nvarchar(40) NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'previous_contract_id', 'previous_contract_id bigint NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'renewal_status', 'renewal_status nvarchar(40) NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'reminder_trigger_date', 'reminder_trigger_date date NULL');
        idx_ensure_col($conn, 'p360_hr_contracts', 'duration_snapshot_json', 'duration_snapshot_json nvarchar(max) NULL');
    }

    if (!idx_exists($conn, 'p360_hr_contract_reminders')) {
        idx_exec($conn, "
        CREATE TABLE dbo.p360_hr_contract_reminders (
            reminder_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            contract_id bigint NOT NULL,
            employee_id int NOT NULL,
            reminder_type nvarchar(40) NOT NULL CONSTRAINT DF_p360hcr_type DEFAULT N'CONTRACT_EXPIRY_15D',
            trigger_date date NOT NULL,
            contract_end_date date NOT NULL,
            days_remaining int NULL,
            status nvarchar(30) NOT NULL CONSTRAINT DF_p360hcr_st DEFAULT N'NEW',
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hcr_cr DEFAULT SYSUTCDATETIME(),
            seen_at datetime2 NULL,
            actioned_at datetime2 NULL,
            action_type nvarchar(40) NULL,
            actioned_by_user_id int NULL,
            related_new_contract_id bigint NULL,
            note_fa nvarchar(400) NULL,
            CONSTRAINT FK_p360hcr_c FOREIGN KEY (contract_id) REFERENCES dbo.p360_hr_contracts(contract_id),
            CONSTRAINT UX_p360hcr UNIQUE (contract_id, reminder_type, contract_end_date)
        )");
        echo "CREATED p360_hr_contract_reminders\n";
    }

    if (!idx_exists($conn, 'p360_hr_exit_cases')) {
        idx_exec($conn, "
        CREATE TABLE dbo.p360_hr_exit_cases (
            exit_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            employee_id int NOT NULL,
            contract_id bigint NULL,
            reminder_id bigint NULL,
            exit_type nvarchar(60) NULL,
            last_work_date date NULL,
            reason_fa nvarchar(200) NULL,
            notes_fa nvarchar(max) NULL,
            assets_returned bit NOT NULL CONSTRAINT DF_p360hex_ar DEFAULT 0,
            settlement_status nvarchar(40) NULL,
            guarantee_status nvarchar(40) NULL,
            contract_status nvarchar(40) NULL,
            account_status nvarchar(40) NULL,
            confirmed bit NOT NULL CONSTRAINT DF_p360hex_cf DEFAULT 0,
            confirmed_by_user_id int NULL,
            confirmed_at datetime2 NULL,
            created_by_user_id int NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hex_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT FK_p360hex_emp FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id)
        )");
        echo "CREATED p360_hr_exit_cases\n";
    }

    $ver = idx_one($conn, "SELECT version_key FROM dbo.p360_hr_schema_version WHERE version_key=N'P360HR_IDENTITY_DATE_20260803'");
    if ($ver === null && idx_exists($conn, 'p360_hr_schema_version')) {
        idx_exec($conn, "INSERT INTO dbo.p360_hr_schema_version (version_key, notes) VALUES (N'P360HR_IDENTITY_DATE_20260803', N'Identity edit + Jalali duration + expiry reminder')");
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
