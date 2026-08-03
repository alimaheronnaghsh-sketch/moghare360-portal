<?php
declare(strict_types=1);

/**
 * MOGHARE360 Workshop R1A-2R2 — ISSUED consumable reversal + pending-cost completion columns.
 * Idempotent, transactional, moghare360_ERP only. No DROP / TRUNCATE / blanket DELETE.
 *
 * CLI: php tools/p360-workshop-r1a2r2-migrate.php
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

function r2_scalar($conn, string $sql, array $params = [])
{
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return null;
    }
    $row = $rows[0];
    return $row[array_key_first($row)] ?? null;
}

function r2_table_exists($conn, string $table): bool
{
    return (int)(r2_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=?",
        [$table]
    ) ?? 0) > 0;
}

function r2_col_exists($conn, string $table, string $col): bool
{
    return (int)(r2_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=? AND COLUMN_NAME=?",
        [$table, $col]
    ) ?? 0) > 0;
}

function r2_exec($conn, string $sql, array $params = []): bool
{
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

function r2_ensure_col($conn, string $table, string $col, string $ddl): void
{
    if (r2_col_exists($conn, $table, $col)) {
        echo "COL_OK {$table}.{$col}\n";
        return;
    }
    if (!r2_exec($conn, "ALTER TABLE dbo.{$table} ADD {$ddl}")) {
        throw new RuntimeException("ALTER_FAIL {$table}.{$col}");
    }
    echo "COL_ADD {$table}.{$col}\n";
}

@odbc_autocommit($conn, false);
$ok = false;

try {
    if (!r2_table_exists($conn, 'erp_workshop_internal_consumable_request_items')) {
        throw new RuntimeException('IC request items table missing — run R1A-2 first');
    }

    r2_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'quantity_reversed', 'quantity_reversed DECIMAL(18,4) NOT NULL CONSTRAINT DF_ic_item_qty_rev DEFAULT (0)');
    r2_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'reversed_total_cost', 'reversed_total_cost DECIMAL(18,4) NULL');
    r2_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_completed_at', 'cost_completed_at DATETIME2 NULL');
    r2_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_completed_by_user_id', 'cost_completed_by_user_id INT NULL');
    r2_ensure_col($conn, 'erp_workshop_internal_consumable_request_items', 'cost_completion_reason', 'cost_completion_reason NVARCHAR(500) NULL');

    if (r2_table_exists($conn, 'erp_inventory_cost_pending_tasks')) {
        r2_ensure_col($conn, 'erp_inventory_cost_pending_tasks', 'pending_qty', 'pending_qty DECIMAL(18,4) NULL');
        r2_ensure_col($conn, 'erp_inventory_cost_pending_tasks', 'original_qty', 'original_qty DECIMAL(18,4) NULL');
    }

    if (!r2_table_exists($conn, 'erp_workshop_ic_reversals')) {
        $created = r2_exec(
            $conn,
            "CREATE TABLE dbo.erp_workshop_ic_reversals (
                reversal_id BIGINT NOT NULL IDENTITY(1,1),
                company_id INT NOT NULL,
                request_id BIGINT NOT NULL,
                request_item_id BIGINT NOT NULL,
                jobcard_id BIGINT NOT NULL,
                work_item_id BIGINT NULL,
                original_movement_id BIGINT NULL,
                reversing_movement_id BIGINT NULL,
                reversal_qty DECIMAL(18,4) NOT NULL,
                reason_category NVARCHAR(80) NOT NULL,
                reason_detail NVARCHAR(1000) NOT NULL,
                status NVARCHAR(40) NOT NULL CONSTRAINT DF_ic_rev_status DEFAULT (N'REVERSAL_REQUESTED'),
                requested_by_user_id INT NOT NULL,
                requested_at DATETIME2 NOT NULL CONSTRAINT DF_ic_rev_req_at DEFAULT (SYSUTCDATETIME()),
                approved_by_user_id INT NULL,
                approved_at DATETIME2 NULL,
                rejected_by_user_id INT NULL,
                rejected_at DATETIME2 NULL,
                reject_reason NVARCHAR(500) NULL,
                reversed_unit_cost_snapshot DECIMAL(18,4) NULL,
                reversed_total_cost DECIMAL(18,4) NULL,
                cost_was_pending BIT NOT NULL CONSTRAINT DF_ic_rev_pending DEFAULT (0),
                created_at DATETIME2 NOT NULL CONSTRAINT DF_ic_rev_created DEFAULT (SYSUTCDATETIME()),
                updated_at DATETIME2 NOT NULL CONSTRAINT DF_ic_rev_updated DEFAULT (SYSUTCDATETIME()),
                CONSTRAINT PK_erp_workshop_ic_reversals PRIMARY KEY CLUSTERED (reversal_id)
            )"
        );
        if (!$created) {
            throw new RuntimeException('CREATE erp_workshop_ic_reversals failed');
        }
        echo "TABLE_ADD erp_workshop_ic_reversals\n";
    } else {
        echo "TABLE_OK erp_workshop_ic_reversals\n";
    }

    $ix = (int)(r2_scalar(
        $conn,
        "SELECT COUNT(*) FROM sys.indexes WHERE name=N'IX_ic_rev_item_status' AND object_id=OBJECT_ID(N'dbo.erp_workshop_ic_reversals')"
    ) ?? 0);
    if ($ix < 1 && r2_table_exists($conn, 'erp_workshop_ic_reversals')) {
        r2_exec(
            $conn,
            'CREATE NONCLUSTERED INDEX IX_ic_rev_item_status ON dbo.erp_workshop_ic_reversals (request_item_id, status)'
        );
        echo "IX_ADD IX_ic_rev_item_status\n";
    }

    $ix2 = (int)(r2_scalar(
        $conn,
        "SELECT COUNT(*) FROM sys.indexes WHERE name=N'UX_ic_rev_active_item' AND object_id=OBJECT_ID(N'dbo.erp_workshop_ic_reversals')"
    ) ?? 0);
    // Filtered unique: one active REVERSAL_REQUESTED per item (SQL Server filtered index)
    if ($ix2 < 1 && r2_table_exists($conn, 'erp_workshop_ic_reversals')) {
        r2_exec(
            $conn,
            "CREATE UNIQUE NONCLUSTERED INDEX UX_ic_rev_active_item
             ON dbo.erp_workshop_ic_reversals (request_item_id)
             WHERE status = N'REVERSAL_REQUESTED'"
        );
        echo "IX_ADD UX_ic_rev_active_item\n";
    }

    // Extend request status CHECK to include reversal lifecycle (no DROP of table).
    $ck = customer_core_fetch_rows(
        $conn,
        "SELECT name, definition FROM sys.check_constraints
         WHERE parent_object_id = OBJECT_ID(N'dbo.erp_workshop_internal_consumable_requests')
           AND name = N'CK_erp_ws_ic_status'"
    );
    if ($ck !== []) {
        $def = (string)($ck[0]['definition'] ?? '');
        if (!str_contains($def, 'REVERSED')) {
            if (!r2_exec($conn, 'ALTER TABLE dbo.erp_workshop_internal_consumable_requests DROP CONSTRAINT CK_erp_ws_ic_status')) {
                throw new RuntimeException('DROP CK_erp_ws_ic_status failed');
            }
            if (!r2_exec(
                $conn,
                "ALTER TABLE dbo.erp_workshop_internal_consumable_requests ADD CONSTRAINT CK_erp_ws_ic_status CHECK (
                    [status]=N'VOIDED' OR [status]=N'CANCELLED' OR [status]=N'ISSUED' OR [status]=N'RETURNED'
                    OR [status]=N'APPROVED' OR [status]=N'SUBMITTED' OR [status]=N'DRAFT'
                    OR [status]=N'REVERSAL_REQUESTED' OR [status]=N'REVERSED' OR [status]=N'REVERSAL_REJECTED'
                )"
            )) {
                throw new RuntimeException('ADD CK_erp_ws_ic_status failed');
            }
            echo "CK_UPDATE CK_erp_ws_ic_status\n";
        } else {
            echo "CK_OK CK_erp_ws_ic_status\n";
        }
    } else {
        echo "CK_MISSING_SKIP\n";
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

echo $ok ? "R1A2R2_MIGRATE_DONE\n" : "R1A2R2_MIGRATE_FAIL\n";
exit($ok ? 0 : 1);
