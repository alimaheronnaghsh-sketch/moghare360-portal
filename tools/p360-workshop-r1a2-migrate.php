<?php
declare(strict_types=1);

/**
 * MOGHARE360 Workshop R1A-2 — work items, diagnosis/work reports, internal consumables.
 * Idempotent, transactional, moghare360_ERP only. No DROP / TRUNCATE / blanket DELETE.
 *
 * CLI: php tools/p360-workshop-r1a2-migrate.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/public_html/includes/erp-customer-core-helper.php';

$conn = customer_core_db();
if ($conn === false) {
    fwrite(STDERR, "NO_DB\n");
    exit(1);
}

function r1a2_exec($conn, string $sql, array $params = []): bool
{
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

function r1a2_scalar($conn, string $sql, array $params = [])
{
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return null;
    }
    $row = $rows[0];
    return $row[array_key_first($row)] ?? null;
}

function r1a2_table_exists($conn, string $table): bool
{
    return (int)(r1a2_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=?",
        [$table]
    ) ?? 0) > 0;
}

function r1a2_col_exists($conn, string $table, string $col): bool
{
    return (int)(r1a2_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=? AND COLUMN_NAME=?",
        [$table, $col]
    ) ?? 0) > 0;
}

function r1a2_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (!r1a2_col_exists($conn, $table, $col)) {
        if (!r1a2_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}")) {
            throw new RuntimeException("ALTER ADD failed: {$table}.{$col}");
        }
        echo "ADDED_COL {$table}.{$col}\n";
    }
}

@odbc_autocommit($conn, false);
$ok = true;
$created = [];

try {
    // —— 1. Work items ——
    if (!r1a2_table_exists($conn, 'erp_workshop_work_items')) {
        $sql = "CREATE TABLE dbo.erp_workshop_work_items (
            work_item_id BIGINT IDENTITY(1,1) NOT NULL,
            company_id INT NOT NULL,
            jobcard_id INT NOT NULL,
            service_family NVARCHAR(40) NOT NULL,
            work_item_type NVARCHAR(60) NOT NULL,
            specialty_code NVARCHAR(40) NULL,
            unit_code NVARCHAR(40) NULL,
            title NVARCHAR(300) NOT NULL,
            status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_ws_wi_status DEFAULT (N'OPEN'),
            assigned_technician_user_id INT NULL,
            created_by_user_id INT NOT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_wi_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            closed_at DATETIME2 NULL,
            CONSTRAINT PK_erp_workshop_work_items PRIMARY KEY CLUSTERED (work_item_id),
            CONSTRAINT CK_erp_ws_wi_family CHECK (service_family IN (N'PERIODIC_SERVICE', N'INSPECTION', N'ELECTRICAL_OPTIONS', N'MECHANICAL')),
            CONSTRAINT CK_erp_ws_wi_status CHECK (status IN (N'OPEN', N'ASSIGNED', N'IN_PROGRESS', N'COMPLETED', N'CANCELLED'))
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_work_items failed');
        }
        $created[] = 'erp_workshop_work_items';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_wi_jobcard ON dbo.erp_workshop_work_items(jobcard_id, service_family, status)');
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_wi_company ON dbo.erp_workshop_work_items(company_id, status)');
    }

    // —— Assignment extension columns ——
    r1a2_ensure_col($conn, 'erp_jobcard_assignments', 'work_item_id', 'work_item_id BIGINT NULL');
    r1a2_ensure_col($conn, 'erp_jobcard_assignments', 'service_family', 'service_family NVARCHAR(40) NULL');
    r1a2_ensure_col($conn, 'erp_jobcard_assignments', 'specialty_code', 'specialty_code NVARCHAR(40) NULL');
    r1a2_ensure_col($conn, 'erp_jobcard_assignments', 'reassign_reason', 'reassign_reason NVARCHAR(1000) NULL');

    // —— 2+3. Diagnosis reports ——
    if (!r1a2_table_exists($conn, 'erp_workshop_diagnosis_reports')) {
        $sql = "CREATE TABLE dbo.erp_workshop_diagnosis_reports (
            diagnosis_report_id BIGINT IDENTITY(1,1) NOT NULL,
            company_id INT NOT NULL,
            jobcard_id INT NOT NULL,
            work_item_id BIGINT NULL,
            revision_no INT NOT NULL CONSTRAINT DF_erp_ws_diag_rev DEFAULT (1),
            status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_ws_diag_status DEFAULT (N'DRAFT'),
            diagnosis_summary NVARCHAR(MAX) NOT NULL,
            fault_assessment NVARCHAR(MAX) NULL,
            probable_cause NVARCHAR(MAX) NULL,
            proposed_work NVARCHAR(MAX) NULL,
            proposed_parts NVARCHAR(MAX) NULL,
            risk_note NVARCHAR(MAX) NULL,
            created_by_user_id INT NOT NULL,
            submitted_at DATETIME2 NULL,
            submitted_by_user_id INT NULL,
            approved_at DATETIME2 NULL,
            approved_by_user_id INT NULL,
            returned_at DATETIME2 NULL,
            returned_by_user_id INT NULL,
            return_reason NVARCHAR(MAX) NULL,
            supersedes_report_id BIGINT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_diag_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            CONSTRAINT PK_erp_workshop_diagnosis_reports PRIMARY KEY CLUSTERED (diagnosis_report_id),
            CONSTRAINT CK_erp_ws_diag_status CHECK (status IN (N'DRAFT', N'SUBMITTED', N'APPROVED', N'RETURNED', N'SUPERSEDED', N'CANCELLED'))
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_diagnosis_reports failed');
        }
        $created[] = 'erp_workshop_diagnosis_reports';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_diag_jobcard ON dbo.erp_workshop_diagnosis_reports(jobcard_id, status, revision_no)');
    }

    if (!r1a2_table_exists($conn, 'erp_workshop_diagnosis_report_history')) {
        $sql = "CREATE TABLE dbo.erp_workshop_diagnosis_report_history (
            history_id BIGINT IDENTITY(1,1) NOT NULL,
            diagnosis_report_id BIGINT NOT NULL,
            jobcard_id INT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            old_status NVARCHAR(40) NULL,
            new_status NVARCHAR(40) NULL,
            event_note NVARCHAR(MAX) NULL,
            actor_user_id INT NOT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_diag_hist_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT PK_erp_workshop_diagnosis_report_history PRIMARY KEY CLUSTERED (history_id)
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_diagnosis_report_history failed');
        }
        $created[] = 'erp_workshop_diagnosis_report_history';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_diag_hist ON dbo.erp_workshop_diagnosis_report_history(diagnosis_report_id, created_at)');
    }

    // —— 4+5. Work/completion reports ——
    if (!r1a2_table_exists($conn, 'erp_workshop_work_reports')) {
        $sql = "CREATE TABLE dbo.erp_workshop_work_reports (
            work_report_id BIGINT IDENTITY(1,1) NOT NULL,
            company_id INT NOT NULL,
            jobcard_id INT NOT NULL,
            work_item_id BIGINT NULL,
            revision_no INT NOT NULL CONSTRAINT DF_erp_ws_wr_rev DEFAULT (1),
            status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_ws_wr_status DEFAULT (N'DRAFT'),
            work_description NVARCHAR(MAX) NOT NULL,
            performer_user_id INT NULL,
            duration_minutes INT NULL,
            result_summary NVARCHAR(MAX) NULL,
            remaining_fault NVARCHAR(MAX) NULL,
            recommendation NVARCHAR(MAX) NULL,
            completed_work_items_json NVARCHAR(MAX) NULL,
            created_by_user_id INT NOT NULL,
            submitted_at DATETIME2 NULL,
            submitted_by_user_id INT NULL,
            approved_at DATETIME2 NULL,
            approved_by_user_id INT NULL,
            returned_at DATETIME2 NULL,
            returned_by_user_id INT NULL,
            return_reason NVARCHAR(MAX) NULL,
            supersedes_report_id BIGINT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_wr_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            CONSTRAINT PK_erp_workshop_work_reports PRIMARY KEY CLUSTERED (work_report_id),
            CONSTRAINT CK_erp_ws_wr_status CHECK (status IN (N'DRAFT', N'SUBMITTED', N'APPROVED', N'RETURNED', N'SUPERSEDED', N'CANCELLED'))
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_work_reports failed');
        }
        $created[] = 'erp_workshop_work_reports';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_wr_jobcard ON dbo.erp_workshop_work_reports(jobcard_id, status, revision_no)');
    }

    if (!r1a2_table_exists($conn, 'erp_workshop_work_report_history')) {
        $sql = "CREATE TABLE dbo.erp_workshop_work_report_history (
            history_id BIGINT IDENTITY(1,1) NOT NULL,
            work_report_id BIGINT NOT NULL,
            jobcard_id INT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            old_status NVARCHAR(40) NULL,
            new_status NVARCHAR(40) NULL,
            event_note NVARCHAR(MAX) NULL,
            actor_user_id INT NOT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_wr_hist_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT PK_erp_workshop_work_report_history PRIMARY KEY CLUSTERED (history_id)
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_work_report_history failed');
        }
        $created[] = 'erp_workshop_work_report_history';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_wr_hist ON dbo.erp_workshop_work_report_history(work_report_id, created_at)');
    }

    // —— 6+7+8. Internal consumables ——
    if (!r1a2_table_exists($conn, 'erp_workshop_internal_consumable_requests')) {
        $sql = "CREATE TABLE dbo.erp_workshop_internal_consumable_requests (
            request_id BIGINT IDENTITY(1,1) NOT NULL,
            company_id INT NOT NULL,
            jobcard_id INT NOT NULL,
            work_item_id BIGINT NULL,
            requesting_user_id INT NOT NULL,
            requesting_employee_id INT NULL,
            consuming_unit NVARCHAR(60) NOT NULL,
            usage_reason NVARCHAR(MAX) NOT NULL,
            status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_ws_ic_status DEFAULT (N'DRAFT'),
            submitted_at DATETIME2 NULL,
            approved_at DATETIME2 NULL,
            approved_by_user_id INT NULL,
            returned_at DATETIME2 NULL,
            returned_by_user_id INT NULL,
            return_reason NVARCHAR(MAX) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_ic_created DEFAULT (SYSUTCDATETIME()),
            updated_at DATETIME2 NULL,
            CONSTRAINT PK_erp_workshop_internal_consumable_requests PRIMARY KEY CLUSTERED (request_id),
            CONSTRAINT CK_erp_ws_ic_status CHECK (status IN (N'DRAFT', N'SUBMITTED', N'APPROVED', N'RETURNED', N'ISSUED', N'CANCELLED', N'VOIDED'))
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_internal_consumable_requests failed');
        }
        $created[] = 'erp_workshop_internal_consumable_requests';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_ic_jobcard ON dbo.erp_workshop_internal_consumable_requests(jobcard_id, status)');
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_ic_company ON dbo.erp_workshop_internal_consumable_requests(company_id, status)');
    }

    if (!r1a2_table_exists($conn, 'erp_workshop_internal_consumable_request_items')) {
        $sql = "CREATE TABLE dbo.erp_workshop_internal_consumable_request_items (
            request_item_id BIGINT IDENTITY(1,1) NOT NULL,
            request_id BIGINT NOT NULL,
            inventory_item_id INT NULL,
            manual_description NVARCHAR(500) NULL,
            quantity DECIMAL(18,4) NOT NULL,
            unit_of_measure NVARCHAR(40) NOT NULL,
            stock_managed BIT NOT NULL CONSTRAINT DF_erp_ws_ici_stock DEFAULT (1),
            inventory_movement_id BIGINT NULL,
            internal_unit_cost DECIMAL(18,4) NULL,
            internal_total_cost DECIMAL(18,4) NULL,
            customer_billable BIT NOT NULL CONSTRAINT DF_erp_ws_ici_billable DEFAULT (0),
            invoice_excluded BIT NOT NULL CONSTRAINT DF_erp_ws_ici_invoice DEFAULT (1),
            notes NVARCHAR(1000) NULL,
            CONSTRAINT PK_erp_workshop_internal_consumable_request_items PRIMARY KEY CLUSTERED (request_item_id),
            CONSTRAINT CK_erp_ws_ici_qty CHECK (quantity > 0),
            CONSTRAINT CK_erp_ws_ici_billable CHECK (customer_billable = 0),
            CONSTRAINT CK_erp_ws_ici_invoice CHECK (invoice_excluded = 1)
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_internal_consumable_request_items failed');
        }
        $created[] = 'erp_workshop_internal_consumable_request_items';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_ici_request ON dbo.erp_workshop_internal_consumable_request_items(request_id)');
        r1a2_exec($conn, 'CREATE UNIQUE INDEX UX_erp_ws_ici_movement ON dbo.erp_workshop_internal_consumable_request_items(inventory_movement_id) WHERE inventory_movement_id IS NOT NULL');
    }

    if (!r1a2_table_exists($conn, 'erp_workshop_internal_consumable_history')) {
        $sql = "CREATE TABLE dbo.erp_workshop_internal_consumable_history (
            history_id BIGINT IDENTITY(1,1) NOT NULL,
            request_id BIGINT NOT NULL,
            jobcard_id INT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            old_status NVARCHAR(40) NULL,
            new_status NVARCHAR(40) NULL,
            event_note NVARCHAR(MAX) NULL,
            actor_user_id INT NOT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_ic_hist_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT PK_erp_workshop_internal_consumable_history PRIMARY KEY CLUSTERED (history_id)
        )";
        if (!r1a2_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_internal_consumable_history failed');
        }
        $created[] = 'erp_workshop_internal_consumable_history';
        r1a2_exec($conn, 'CREATE INDEX IX_erp_ws_ic_hist ON dbo.erp_workshop_internal_consumable_history(request_id, created_at)');
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('commit failed');
    }
    @odbc_autocommit($conn, true);
    echo "TRANSACTION=COMMITTED\n";
    echo 'CREATED=' . implode(',', $created) . "\n";
    echo "MIGRATION_OK\n";
} catch (Throwable $e) {
    @odbc_rollback($conn);
    @odbc_autocommit($conn, true);
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
