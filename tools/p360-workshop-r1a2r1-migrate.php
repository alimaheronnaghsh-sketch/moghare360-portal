<?php
declare(strict_types=1);

/**
 * MOGHARE360 Workshop R1A-2R1 — JobCard company_id + inventory internal valuation.
 * Idempotent, transactional, moghare360_ERP only. No DROP / TRUNCATE / blanket DELETE.
 *
 * CLI: php tools/p360-workshop-r1a2r1-migrate.php
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

function r1r1_scalar($conn, string $sql, array $params = [])
{
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return null;
    }
    $row = $rows[0];
    return $row[array_key_first($row)] ?? null;
}

function r1r1_table_exists($conn, string $table): bool
{
    return (int)(r1r1_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=?",
        [$table]
    ) ?? 0) > 0;
}

function r1r1_col_exists($conn, string $table, string $col): bool
{
    return (int)(r1r1_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=? AND COLUMN_NAME=?",
        [$table, $col]
    ) ?? 0) > 0;
}

function r1r1_exec($conn, string $sql, array $params = []): bool
{
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

function r1r1_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (r1r1_col_exists($conn, $table, $col)) {
        echo "COL_OK {$table}.{$col}\n";
        return;
    }
    if (!r1r1_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}")) {
        throw new RuntimeException("ALTER_FAIL {$table}.{$col}");
    }
    echo "COL_ADD {$table}.{$col}\n";
}

@odbc_autocommit($conn, false);
$ok = false;

try {
    // --- JobCard company_id ---
    if (!r1r1_col_exists($conn, 'erp_jobcards', 'company_id')) {
        if (!r1r1_exec($conn, 'ALTER TABLE dbo.erp_jobcards ADD company_id INT NULL')) {
            throw new RuntimeException('ADD company_id failed');
        }
        echo "COL_ADD erp_jobcards.company_id\n";
    } else {
        echo "COL_OK erp_jobcards.company_id\n";
    }

    // Backfill only when creator/reception resolve to exactly one company (no guessing).
    $backfilled = r1r1_exec(
        $conn,
        "UPDATE j
         SET j.company_id = x.company_id
         FROM dbo.erp_jobcards j
         INNER JOIN (
             SELECT j2.jobcard_id, MIN(cu.company_id) AS company_id
             FROM dbo.erp_jobcards j2
             INNER JOIN dbo.erp_company_users cu
               ON cu.is_active = 1 AND cu.company_id > 0
              AND cu.user_id IN (j2.created_by_user_id, j2.reception_user_id)
             WHERE j2.company_id IS NULL
             GROUP BY j2.jobcard_id
             HAVING COUNT(DISTINCT cu.company_id) = 1
         ) x ON x.jobcard_id = j.jobcard_id
         WHERE j.company_id IS NULL"
    );
    echo $backfilled ? "BACKFILL_OK\n" : "BACKFILL_SKIP\n";

    $filled = (int)(r1r1_scalar($conn, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE company_id IS NOT NULL AND company_id > 0') ?? 0);
    $amb = (int)(r1r1_scalar($conn, 'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE company_id IS NULL') ?? 0);
    echo "JOBCARDS_WITH_COMPANY={$filled} AMBIGUOUS_OR_UNRESOLVED={$amb}\n";

    // Index (idempotent)
    $ix = (int)(r1r1_scalar(
        $conn,
        "SELECT COUNT(*) FROM sys.indexes WHERE name=N'IX_erp_jobcards_company_id' AND object_id=OBJECT_ID(N'dbo.erp_jobcards')"
    ) ?? 0);
    if ($ix < 1) {
        r1r1_exec($conn, 'CREATE NONCLUSTERED INDEX IX_erp_jobcards_company_id ON dbo.erp_jobcards (company_id)');
        echo "IX_ADD IX_erp_jobcards_company_id\n";
    } else {
        echo "IX_OK IX_erp_jobcards_company_id\n";
    }

    // FK if companies table exists and FK absent
    $fk = (int)(r1r1_scalar(
        $conn,
        "SELECT COUNT(*) FROM sys.foreign_keys WHERE name=N'FK_erp_jobcards_company'"
    ) ?? 0);
    if ($fk < 1 && r1r1_table_exists($conn, 'erp_companies')) {
        // Only add FK when no orphan company_id values
        $orphans = (int)(r1r1_scalar(
            $conn,
            "SELECT COUNT(*) FROM dbo.erp_jobcards j
             WHERE j.company_id IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM dbo.erp_companies c WHERE c.company_id = j.company_id)"
        ) ?? 0);
        if ($orphans === 0) {
            r1r1_exec(
                $conn,
                'ALTER TABLE dbo.erp_jobcards ADD CONSTRAINT FK_erp_jobcards_company
                 FOREIGN KEY (company_id) REFERENCES dbo.erp_companies (company_id)'
            );
            echo "FK_ADD FK_erp_jobcards_company\n";
        } else {
            echo "FK_SKIP orphans={$orphans}\n";
        }
    } else {
        echo "FK_OK_OR_SKIP\n";
    }

    // --- IC cost status columns ---
    if (r1r1_table_exists($conn, 'erp_workshop_internal_consumable_request_items')) {
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_status', 'cost_status NVARCHAR(40) NULL');
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'valuation_method', 'valuation_method NVARCHAR(40) NULL');
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'valuation_id', 'valuation_id BIGINT NULL');
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_currency', 'cost_currency NVARCHAR(10) NULL');
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_source_document_type', 'cost_source_document_type NVARCHAR(80) NULL');
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_source_document_id', 'cost_source_document_id BIGINT NULL');
        r1r1_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_snapshot_at', 'cost_snapshot_at DATETIME2 NULL');
    }

    // --- Valuation table ---
    if (!r1r1_table_exists($conn, 'erp_inventory_item_valuations')) {
        $created = r1r1_exec(
            $conn,
            "CREATE TABLE dbo.erp_inventory_item_valuations (
                valuation_id BIGINT NOT NULL IDENTITY(1,1),
                inventory_item_id BIGINT NOT NULL,
                company_id INT NOT NULL,
                valuation_method NVARCHAR(40) NOT NULL,
                standard_internal_unit_cost DECIMAL(18,4) NULL,
                moving_average_unit_cost DECIMAL(18,4) NULL,
                last_approved_unit_cost DECIMAL(18,4) NULL,
                unit_cost DECIMAL(18,4) NOT NULL,
                currency NVARCHAR(10) NOT NULL CONSTRAINT DF_erp_inv_val_currency DEFAULT (N'IRR'),
                effective_from DATETIME2 NOT NULL CONSTRAINT DF_erp_inv_val_from DEFAULT (SYSUTCDATETIME()),
                effective_to DATETIME2 NULL,
                source_document_type NVARCHAR(80) NULL,
                source_document_id BIGINT NULL,
                status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_inv_val_status DEFAULT (N'DRAFT'),
                entered_by_user_id INT NOT NULL,
                submitted_at DATETIME2 NULL,
                approved_by_user_id INT NULL,
                approved_at DATETIME2 NULL,
                voided_at DATETIME2 NULL,
                void_reason NVARCHAR(500) NULL,
                created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_inv_val_created DEFAULT (SYSUTCDATETIME()),
                updated_at DATETIME2 NOT NULL CONSTRAINT DF_erp_inv_val_updated DEFAULT (SYSUTCDATETIME()),
                CONSTRAINT PK_erp_inventory_item_valuations PRIMARY KEY CLUSTERED (valuation_id)
            )"
        );
        if (!$created) {
            throw new RuntimeException('CREATE erp_inventory_item_valuations failed');
        }
        echo "TABLE_ADD erp_inventory_item_valuations\n";
    } else {
        echo "TABLE_OK erp_inventory_item_valuations\n";
    }

    $ix2 = (int)(r1r1_scalar(
        $conn,
        "SELECT COUNT(*) FROM sys.indexes WHERE name=N'IX_erp_inv_val_item_company_status' AND object_id=OBJECT_ID(N'dbo.erp_inventory_item_valuations')"
    ) ?? 0);
    if ($ix2 < 1 && r1r1_table_exists($conn, 'erp_inventory_item_valuations')) {
        r1r1_exec(
            $conn,
            'CREATE NONCLUSTERED INDEX IX_erp_inv_val_item_company_status
             ON dbo.erp_inventory_item_valuations (inventory_item_id, company_id, status, effective_from DESC)'
        );
        echo "IX_ADD IX_erp_inv_val_item_company_status\n";
    }

    // Cost-pending task table (minimal cartable for finance)
    if (!r1r1_table_exists($conn, 'erp_inventory_cost_pending_tasks')) {
        $t = r1r1_exec(
            $conn,
            "CREATE TABLE dbo.erp_inventory_cost_pending_tasks (
                task_id BIGINT NOT NULL IDENTITY(1,1),
                company_id INT NOT NULL,
                inventory_item_id BIGINT NULL,
                jobcard_id BIGINT NULL,
                request_id BIGINT NULL,
                request_item_id BIGINT NULL,
                task_status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_inv_cost_task_status DEFAULT (N'OPEN'),
                task_note NVARCHAR(500) NULL,
                created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_inv_cost_task_created DEFAULT (SYSUTCDATETIME()),
                closed_at DATETIME2 NULL,
                closed_by_user_id INT NULL,
                CONSTRAINT PK_erp_inventory_cost_pending_tasks PRIMARY KEY CLUSTERED (task_id)
            )"
        );
        if (!$t) {
            throw new RuntimeException('CREATE erp_inventory_cost_pending_tasks failed');
        }
        echo "TABLE_ADD erp_inventory_cost_pending_tasks\n";
    } else {
        echo "TABLE_OK erp_inventory_cost_pending_tasks\n";
    }

    @odbc_commit($conn);
    $ok = true;
    echo "COMMIT_OK\n";
} catch (Throwable $e) {
    @odbc_rollback($conn);
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    @odbc_autocommit($conn, true);
}

echo $ok ? "R1A2R1_MIGRATE_DONE\n" : "R1A2R1_MIGRATE_FAIL\n";
exit($ok ? 0 : 1);
