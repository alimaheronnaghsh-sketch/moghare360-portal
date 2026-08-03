<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This administrative tool is CLI-only.\n";
    exit(1);
}

/**
 * P360 Dynamic Personnel + Contract Engine migration (idempotent).
 * Database: moghare360_ERP only. No DROP/TRUNCATE/blanket DELETE.
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-config-loader.php';

function cex_conn()
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

function cex_exec($conn, string $sql, array $params = []): void
{
    $st = @odbc_prepare($conn, $sql);
    if ($st === false || !@odbc_execute($st, $params)) {
        throw new RuntimeException(odbc_errormsg($conn) . ' :: ' . substr($sql, 0, 180));
    }
}

function cex_one($conn, string $sql, array $params = []): ?array
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

function cex_exists($conn, string $table): bool
{
    return cex_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$table]) !== null;
}

function cex_col($conn, string $table, string $col): bool
{
    return cex_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $col]) !== null;
}

function cex_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (!cex_col($conn, $table, $col)) {
        cex_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}");
        echo "COL {$table}.{$col}\n";
    }
}

$conn = cex_conn();
@odbc_autocommit($conn, false);

try {
    if (!cex_exists($conn, 'p360_hr_personnel_profile')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_personnel_profile (
            employee_id int NOT NULL PRIMARY KEY,
            father_name nvarchar(80) NULL,
            birth_certificate_no nvarchar(40) NULL,
            birth_date date NULL,
            birth_place nvarchar(120) NULL,
            gender nvarchar(20) NULL,
            nationality nvarchar(60) NULL,
            emergency_name nvarchar(120) NULL,
            emergency_mobile nvarchar(30) NULL,
            emergency_relation nvarchar(60) NULL,
            province nvarchar(80) NULL,
            city nvarchar(80) NULL,
            address_full nvarchar(400) NULL,
            postal_code nvarchar(20) NULL,
            marital_status nvarchar(40) NULL,
            spouse_name nvarchar(120) NULL,
            child_count int NULL,
            child_under_18_count int NULL,
            child_allowance_eligible bit NOT NULL CONSTRAINT DF_p360hpp_child DEFAULT 0,
            marriage_allowance_eligible bit NOT NULL CONSTRAINT DF_p360hpp_marr DEFAULT 0,
            education_degree nvarchar(80) NULL,
            education_field nvarchar(120) NULL,
            education_school nvarchar(160) NULL,
            education_year int NULL,
            military_status nvarchar(60) NULL,
            military_card_type nvarchar(60) NULL,
            military_card_no nvarchar(60) NULL,
            military_issue_date date NULL,
            military_not_applicable bit NOT NULL CONSTRAINT DF_p360hpp_mil DEFAULT 0,
            direct_supervisor nvarchar(160) NULL,
            workplace nvarchar(200) NULL,
            cooperation_type nvarchar(60) NULL,
            cooperation_start date NULL,
            cooperation_end date NULL,
            exit_reason nvarchar(200) NULL,
            bank_name nvarchar(120) NULL,
            bank_account nvarchar(40) NULL,
            iban nvarchar(34) NULL,
            card_no nvarchar(30) NULL,
            account_holder_name nvarchar(160) NULL,
            account_holder_national_id nvarchar(20) NULL,
            account_self_confirmed bit NOT NULL CONSTRAINT DF_p360hpp_self DEFAULT 0,
            bank_finance_verified bit NOT NULL CONSTRAINT DF_p360hpp_bfv DEFAULT 0,
            bank_verified_at datetime2 NULL,
            bank_verified_by_user_id int NULL,
            guarantee_type nvarchar(80) NULL,
            guarantee_amount decimal(18,2) NULL,
            guarantee_ref nvarchar(80) NULL,
            guarantee_issuer nvarchar(120) NULL,
            guarantee_received_at date NULL,
            guarantee_storage nvarchar(160) NULL,
            guarantee_status nvarchar(40) NULL,
            guarantee_returned_at date NULL,
            guarantee_receiver nvarchar(120) NULL,
            guarantee_notes nvarchar(400) NULL,
            updated_at datetime2 NULL,
            updated_by_user_id int NULL,
            CONSTRAINT FK_p360hpp_emp FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id)
        )");
        echo "CREATED p360_hr_personnel_profile\n";
    }

    if (!cex_exists($conn, 'p360_hr_employer_profile')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_employer_profile (
            employer_profile_id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
            trade_name nvarchar(200) NOT NULL,
            legal_name nvarchar(200) NULL,
            employer_name nvarchar(160) NULL,
            representative_name nvarchar(160) NULL,
            representative_title nvarchar(120) NULL,
            national_or_reg_id nvarchar(40) NULL,
            registration_no nvarchar(40) NULL,
            address_full nvarchar(400) NULL,
            postal_code nvarchar(20) NULL,
            phone nvarchar(40) NULL,
            default_workplace nvarchar(200) NULL,
            stamp_path nvarchar(500) NULL,
            signature_path nvarchar(500) NULL,
            is_active bit NOT NULL CONSTRAINT DF_p360hep_act DEFAULT 1,
            profile_complete bit NOT NULL CONSTRAINT DF_p360hep_comp DEFAULT 0,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hep_cr DEFAULT SYSUTCDATETIME(),
            updated_at datetime2 NULL
        )");
        echo "CREATED p360_hr_employer_profile\n";
    }

    $emp = cex_one($conn, 'SELECT TOP 1 employer_profile_id FROM dbo.p360_hr_employer_profile WHERE is_active=1');
    if ($emp === null) {
        cex_exec($conn, "INSERT INTO dbo.p360_hr_employer_profile
            (trade_name, employer_name, representative_name, representative_title, default_workplace, profile_complete)
            VALUES (N'ظ…ط¬ظ…ظˆط¹ظ‡ ظ…ظ‚ط§ط±ظ‡ ظ…ظˆطھظˆط±ط²', N'ظ…ط³ط¹ظˆط¯ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯', N'ظ…ط³ط¹ظˆط¯ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯', N'ظ…ط¯غŒط±غŒطھ', N'ط®ط¯ظ…ط§طھ ظپظ†غŒ ظ…ظ‚ط§ط±ظ‡ ط¹ط§ط¨ط¯', 0)");
        echo "SEEDED employer profile\n";
    }

    if (!cex_exists($conn, 'p360_hr_contract_templates')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_contract_templates (
            template_id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
            template_code nvarchar(40) NOT NULL,
            version_no int NOT NULL,
            title_fa nvarchar(200) NOT NULL,
            body_text nvarchar(max) NOT NULL,
            is_active bit NOT NULL CONSTRAINT DF_p360hct_act DEFAULT 1,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hct_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT UX_p360hct UNIQUE (template_code, version_no)
        )");
        echo "CREATED p360_hr_contract_templates\n";
    }

    if (!cex_exists($conn, 'p360_hr_contracts')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_contracts (
            contract_id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            employee_id int NOT NULL,
            personnel_code nvarchar(40) NOT NULL,
            contract_version int NOT NULL CONSTRAINT DF_p360hc_ver DEFAULT 1,
            contract_type nvarchar(30) NOT NULL,
            stage_code nvarchar(40) NOT NULL CONSTRAINT DF_p360hc_stage DEFAULT N'DRAFT',
            contract_job_title nvarchar(160) NULL,
            org_job_title nvarchar(160) NULL,
            unit_name nvarchar(120) NULL,
            direct_supervisor nvarchar(160) NULL,
            workplace nvarchar(200) NULL,
            contract_subject nvarchar(400) NULL,
            contract_subject_details nvarchar(max) NULL,
            job_duties_text nvarchar(max) NULL,
            created_date date NULL,
            start_date date NULL,
            end_date date NULL,
            duration_text nvarchar(120) NULL,
            wage_model nvarchar(40) NULL,
            eid_payment_method nvarchar(40) NULL,
            severance_payment_method nvarchar(40) NULL,
            shift_snapshot_json nvarchar(max) NULL,
            employer_snapshot_json nvarchar(max) NULL,
            employee_snapshot_json nvarchar(max) NULL,
            wage_snapshot_json nvarchar(max) NULL,
            rendered_body nvarchar(max) NULL,
            contract_hash char(64) NULL,
            wage_table_hash char(64) NULL,
            acceptance_checked bit NOT NULL CONSTRAINT DF_p360hc_acc DEFAULT 0,
            accepted_at datetime2 NULL,
            accepted_ip nvarchar(64) NULL,
            accepted_ua nvarchar(400) NULL,
            accepted_by_user_id int NULL,
            otp_verified_at datetime2 NULL,
            employee_signed_at datetime2 NULL,
            employer_signed_at datetime2 NULL,
            employee_signature_path nvarchar(500) NULL,
            employer_signature_path nvarchar(500) NULL,
            final_pdf_path nvarchar(500) NULL,
            final_pdf_hash char(64) NULL,
            is_locked bit NOT NULL CONSTRAINT DF_p360hc_lock DEFAULT 0,
            update_master_job_title bit NOT NULL CONSTRAINT DF_p360hc_umj DEFAULT 0,
            created_by_user_id int NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hc_cr DEFAULT SYSUTCDATETIME(),
            updated_at datetime2 NULL,
            CONSTRAINT FK_p360hc_emp FOREIGN KEY (employee_id) REFERENCES dbo.p360_employees(employee_id)
        )");
        echo "CREATED p360_hr_contracts\n";
    }

    if (!cex_exists($conn, 'p360_hr_contract_wage_components')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_contract_wage_components (
            id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            contract_id bigint NOT NULL,
            row_no int NOT NULL,
            component_code nvarchar(40) NOT NULL,
            title_fa nvarchar(120) NOT NULL,
            extra_info_fa nvarchar(80) NULL,
            calc_method nvarchar(40) NOT NULL,
            input_value decimal(18,4) NULL,
            daily_amount decimal(18,2) NULL,
            monthly_amount decimal(18,2) NULL,
            enabled bit NOT NULL CONSTRAINT DF_p360hcwc_en DEFAULT 1,
            taxable bit NOT NULL CONSTRAINT DF_p360hcwc_tax DEFAULT 1,
            insurance_subject bit NOT NULL CONSTRAINT DF_p360hcwc_ins DEFAULT 1,
            payroll_include bit NOT NULL CONSTRAINT DF_p360hcwc_pay DEFAULT 1,
            pdf_display bit NOT NULL CONSTRAINT DF_p360hcwc_pdf DEFAULT 1,
            notes nvarchar(200) NULL,
            CONSTRAINT FK_p360hcwc_c FOREIGN KEY (contract_id) REFERENCES dbo.p360_hr_contracts(contract_id)
        )");
        echo "CREATED p360_hr_contract_wage_components\n";
    }

    if (!cex_exists($conn, 'p360_hr_contract_benefit_payments')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_contract_benefit_payments (
            id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            contract_id bigint NOT NULL,
            benefit_code nvarchar(40) NOT NULL,
            payroll_period_key nvarchar(20) NOT NULL,
            amount decimal(18,2) NOT NULL,
            paid_at datetime2 NOT NULL CONSTRAINT DF_p360hcbp_paid DEFAULT SYSUTCDATETIME(),
            note nvarchar(200) NULL,
            CONSTRAINT FK_p360hcbp_c FOREIGN KEY (contract_id) REFERENCES dbo.p360_hr_contracts(contract_id),
            CONSTRAINT UX_p360hcbp UNIQUE (contract_id, benefit_code, payroll_period_key)
        )");
        echo "CREATED p360_hr_contract_benefit_payments\n";
    }

    if (!cex_exists($conn, 'p360_hr_contract_otp')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_contract_otp (
            id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            contract_id bigint NOT NULL,
            employee_id int NOT NULL,
            contract_version int NOT NULL,
            otp_hash char(64) NOT NULL,
            expires_at datetime2 NOT NULL,
            attempt_count int NOT NULL CONSTRAINT DF_p360hco_att DEFAULT 0,
            max_attempts int NOT NULL CONSTRAINT DF_p360hco_max DEFAULT 5,
            consumed_at datetime2 NULL,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hco_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT FK_p360hco_c FOREIGN KEY (contract_id) REFERENCES dbo.p360_hr_contracts(contract_id)
        )");
        echo "CREATED p360_hr_contract_otp\n";
    }

    if (!cex_exists($conn, 'p360_hr_payroll_contract_link')) {
        cex_exec($conn, "
        CREATE TABLE dbo.p360_hr_payroll_contract_link (
            id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
            employee_id int NOT NULL,
            contract_id bigint NOT NULL,
            contract_version int NOT NULL,
            wage_model nvarchar(40) NULL,
            eid_payment_method nvarchar(40) NULL,
            severance_payment_method nvarchar(40) NULL,
            effective_from date NOT NULL,
            effective_to date NULL,
            is_active bit NOT NULL CONSTRAINT DF_p360hpcl_act DEFAULT 1,
            created_at datetime2 NOT NULL CONSTRAINT DF_p360hpcl_cr DEFAULT SYSUTCDATETIME(),
            CONSTRAINT FK_p360hpcl_c FOREIGN KEY (contract_id) REFERENCES dbo.p360_hr_contracts(contract_id)
        )");
        echo "CREATED p360_hr_payroll_contract_link\n";
    }

    // Ensure template v1 placeholder marker exists (body filled by PHP seed helper if empty)
    $tpl = cex_one($conn, "SELECT template_id FROM dbo.p360_hr_contract_templates WHERE template_code=N'EMPLOYMENT_V1' AND version_no=1");
    if ($tpl === null) {
        cex_exec($conn, "INSERT INTO dbo.p360_hr_contract_templates (template_code, version_no, title_fa, body_text, is_active)
            VALUES (N'EMPLOYMENT_V1', 1, N'ظ‚ط±ط§ط±ط¯ط§ط¯ ع©ط§ط±', N'{{TEMPLATE_BODY}}', 1)");
        echo "SEEDED template stub\n";
    }

    $ver = cex_one($conn, "SELECT version_key FROM dbo.p360_hr_schema_version WHERE version_key=N'P360HR_CONTRACT_ENGINE_20260803'");
    if ($ver === null && cex_exists($conn, 'p360_hr_schema_version')) {
        cex_exec($conn, "INSERT INTO dbo.p360_hr_schema_version (version_key, notes) VALUES (N'P360HR_CONTRACT_ENGINE_20260803', N'Dynamic personnel + contract engine')");
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
