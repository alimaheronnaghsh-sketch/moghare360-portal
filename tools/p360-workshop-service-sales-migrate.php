<?php
declare(strict_types=1);

/**
 * MOGHARE360 Workshop Service Sales — erp_workshop_service_lines + history.
 * Idempotent, transactional, moghare360_ERP only. No DROP / TRUNCATE / blanket DELETE.
 *
 * CLI: php tools/p360-workshop-service-sales-migrate.php
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

function wss_exec($conn, string $sql, array $params = []): bool
{
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

function wss_scalar($conn, string $sql, array $params = [])
{
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return null;
    }
    $row = $rows[0];
    return $row[array_key_first($row)] ?? null;
}

function wss_table_exists($conn, string $table): bool
{
    return (int)(wss_scalar(
        $conn,
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=?",
        [$table]
    ) ?? 0) > 0;
}

function wss_index_exists($conn, string $name): bool
{
    return (int)(wss_scalar(
        $conn,
        'SELECT COUNT(*) FROM sys.indexes WHERE name=? AND object_id=OBJECT_ID(N\'dbo.erp_workshop_service_lines\')',
        [$name]
    ) ?? 0) > 0;
}

@odbc_autocommit($conn, false);
$created = [];
$noop = true;

try {
    if (!wss_table_exists($conn, 'erp_workshop_service_lines')) {
        $sql = "CREATE TABLE dbo.erp_workshop_service_lines (
            service_line_id BIGINT IDENTITY(1,1) NOT NULL,
            company_id INT NOT NULL,
            jobcard_id INT NOT NULL,
            work_item_id BIGINT NOT NULL,
            sales_category NVARCHAR(40) NOT NULL,
            service_title NVARCHAR(300) NOT NULL,
            service_description NVARCHAR(MAX) NULL,
            actual_minutes INT NOT NULL CONSTRAINT DF_erp_ws_sl_minutes DEFAULT (0),
            agreement_scope NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_ws_sl_scope DEFAULT (N'WITHIN_AGREEMENT'),
            status NVARCHAR(40) NOT NULL CONSTRAINT DF_erp_ws_sl_status DEFAULT (N'DRAFT'),
            price_irr BIGINT NULL,
            created_by_user_id INT NOT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_sl_created DEFAULT (SYSUTCDATETIME()),
            updated_by_user_id INT NULL,
            updated_at DATETIME2 NULL,
            submitted_by_user_id INT NULL,
            submitted_at DATETIME2 NULL,
            technical_reviewed_by_user_id INT NULL,
            technical_reviewed_at DATETIME2 NULL,
            return_reason NVARCHAR(MAX) NULL,
            priced_by_user_id INT NULL,
            priced_at DATETIME2 NULL,
            price_change_reason NVARCHAR(MAX) NULL,
            ready_for_invoice_at DATETIME2 NULL,
            ready_for_invoice_by_user_id INT NULL,
            invoiced_at DATETIME2 NULL,
            invoice_item_id BIGINT NULL,
            row_version ROWVERSION NOT NULL,
            CONSTRAINT PK_erp_workshop_service_lines PRIMARY KEY CLUSTERED (service_line_id),
            CONSTRAINT CK_erp_ws_sl_category CHECK (sales_category IN (
                N'PERIODIC_SERVICE', N'ENGINE', N'TRANSMISSION', N'SUSPENSION',
                N'ELECTRICAL', N'OPTIONS', N'PREPURCHASE_INSPECTION'
            )),
            CONSTRAINT CK_erp_ws_sl_scope CHECK (agreement_scope IN (N'WITHIN_AGREEMENT', N'ADDITIONAL')),
            CONSTRAINT CK_erp_ws_sl_status CHECK (status IN (
                N'DRAFT', N'SUBMITTED', N'RETURNED', N'TECHNICALLY_APPROVED', N'PRICING_PENDING',
                N'PRICED', N'READY_FOR_INVOICE', N'INVOICED', N'VOIDED'
            )),
            CONSTRAINT CK_erp_ws_sl_price_positive CHECK (price_irr IS NULL OR price_irr > 0),
            CONSTRAINT CK_erp_ws_sl_minutes CHECK (actual_minutes >= 0)
        )";
        if (!wss_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_service_lines failed');
        }
        $created[] = 'erp_workshop_service_lines';
        $noop = false;
    }

    if (!wss_index_exists($conn, 'IX_erp_ws_sl_jobcard_status')) {
        if (!wss_exec($conn, 'CREATE INDEX IX_erp_ws_sl_jobcard_status ON dbo.erp_workshop_service_lines(jobcard_id, status)')) {
            throw new RuntimeException('IX_erp_ws_sl_jobcard_status failed');
        }
        $noop = false;
        echo "INDEX IX_erp_ws_sl_jobcard_status\n";
    }
    if (!wss_index_exists($conn, 'IX_erp_ws_sl_company_status')) {
        if (!wss_exec($conn, 'CREATE INDEX IX_erp_ws_sl_company_status ON dbo.erp_workshop_service_lines(company_id, status)')) {
            throw new RuntimeException('IX_erp_ws_sl_company_status failed');
        }
        $noop = false;
        echo "INDEX IX_erp_ws_sl_company_status\n";
    }
    if (!wss_index_exists($conn, 'IX_erp_ws_sl_work_item')) {
        if (!wss_exec($conn, 'CREATE INDEX IX_erp_ws_sl_work_item ON dbo.erp_workshop_service_lines(work_item_id, status)')) {
            throw new RuntimeException('IX_erp_ws_sl_work_item failed');
        }
        $noop = false;
        echo "INDEX IX_erp_ws_sl_work_item\n";
    }

    if (!wss_table_exists($conn, 'erp_workshop_service_line_history')) {
        $sql = "CREATE TABLE dbo.erp_workshop_service_line_history (
            history_id BIGINT IDENTITY(1,1) NOT NULL,
            service_line_id BIGINT NOT NULL,
            company_id INT NOT NULL,
            jobcard_id INT NOT NULL,
            work_item_id BIGINT NOT NULL,
            event_name NVARCHAR(80) NOT NULL,
            old_status NVARCHAR(40) NULL,
            new_status NVARCHAR(40) NULL,
            old_price_irr BIGINT NULL,
            new_price_irr BIGINT NULL,
            event_note NVARCHAR(MAX) NULL,
            actor_user_id INT NOT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_ws_sl_hist_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT PK_erp_workshop_service_line_history PRIMARY KEY CLUSTERED (history_id)
        )";
        if (!wss_exec($conn, $sql)) {
            throw new RuntimeException('create erp_workshop_service_line_history failed');
        }
        $created[] = 'erp_workshop_service_line_history';
        $noop = false;
        wss_exec($conn, 'CREATE INDEX IX_erp_ws_sl_hist_line ON dbo.erp_workshop_service_line_history(service_line_id, created_at)');
        wss_exec($conn, 'CREATE INDEX IX_erp_ws_sl_hist_jobcard ON dbo.erp_workshop_service_line_history(jobcard_id, created_at)');
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('commit failed');
    }
    echo $noop && $created === [] ? "NOOP\n" : ("CREATED=" . implode(',', $created) . "\n");
    echo "MIGRATE_OK\n";
    exit(0);
} catch (Throwable $e) {
    @odbc_rollback($conn);
    fwrite(STDERR, 'FAIL ' . $e->getMessage() . "\n");
    exit(1);
}
