<?php
declare(strict_types=1);

/**
 * Phase 2 — invoice source uniqueness for WORKSHOP_SERVICE_LINE.
 * Idempotent, moghare360_ERP only. No DROP of data.
 *
 * CLI: php tools/p360-workshop-service-invoice-migrate.php
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

function wsi_scalar($conn, string $sql, array $params = [])
{
    $rows = customer_core_fetch_rows($conn, $sql, $params);
    if ($rows === []) {
        return null;
    }
    $row = $rows[0];
    return $row[array_key_first($row)] ?? null;
}

function wsi_exec($conn, string $sql, array $params = []): bool
{
    $st = @odbc_prepare($conn, $sql);
    return $st !== false && @odbc_execute($st, $params);
}

$noop = true;
@odbc_autocommit($conn, false);

try {
    $idx = (int)(wsi_scalar(
        $conn,
        "SELECT COUNT(*) FROM sys.indexes WHERE name=N'UX_erp_fii_workshop_service_line' AND object_id=OBJECT_ID(N'dbo.erp_final_invoice_items')"
    ) ?? 0);
    if ($idx < 1) {
        // Filtered unique: one active invoice line per workshop service line.
        $sql = "CREATE UNIQUE INDEX UX_erp_fii_workshop_service_line
                ON dbo.erp_final_invoice_items(source_type, source_id)
                WHERE source_type = N'WORKSHOP_SERVICE_LINE' AND source_id IS NOT NULL";
        if (!wsi_exec($conn, $sql)) {
            throw new RuntimeException('create UX_erp_fii_workshop_service_line failed');
        }
        $noop = false;
        echo "INDEX UX_erp_fii_workshop_service_line\n";
    }

    // Optional snapshot columns if missing (non-destructive).
    $cols = [
        'source_snapshot_json' => 'source_snapshot_json NVARCHAR(MAX) NULL',
    ];
    foreach ($cols as $name => $ddl) {
        $exists = (int)(wsi_scalar(
            $conn,
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=N'erp_final_invoice_items' AND COLUMN_NAME=?",
            [$name]
        ) ?? 0);
        if ($exists < 1) {
            if (!wsi_exec($conn, "ALTER TABLE dbo.erp_final_invoice_items ADD {$ddl}")) {
                throw new RuntimeException("ADD {$name} failed");
            }
            $noop = false;
            echo "ADDED_COL erp_final_invoice_items.{$name}\n";
        }
    }

    if (!@odbc_commit($conn)) {
        throw new RuntimeException('commit failed');
    }
    echo $noop ? "NOOP\n" : "CHANGED\n";
    echo "MIGRATE_OK\n";
    exit(0);
} catch (Throwable $e) {
    @odbc_rollback($conn);
    fwrite(STDERR, 'FAIL ' . $e->getMessage() . "\n");
    exit(1);
}
