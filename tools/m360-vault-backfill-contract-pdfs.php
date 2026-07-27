<?php
declare(strict_types=1);

/**
 * Backfill contract PDF disk mirrors into dbo.erp_document_blobs (read-only on disk; additive DB).
 * Usage: C:\xampp\php\php.exe tools/m360-vault-backfill-contract-pdfs.php [--limit=50]
 */

$root = dirname(__DIR__);
chdir($root . DIRECTORY_SEPARATOR . 'public_html');

require_once __DIR__ . '/../public_html/includes/m360-intake-contract-helper.php';

$limit = 50;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int)substr($arg, 8));
    }
}

$conn = customer_core_db();
if ($conn === false || !m360_vault_table_exists($conn)) {
    fwrite(STDERR, "DB or vault table unavailable\n");
    exit(2);
}

$rows = customer_core_fetch_rows(
    $conn,
    'SELECT TOP (' . $limit . ') contract_id, online_request_id, customer_id, contract_data_json
     FROM dbo.' . M360_CONTRACT_TABLE . ' ORDER BY contract_id DESC'
);

$stats = ['scanned' => 0, 'backfilled' => 0, 'skipped' => 0, 'errors' => 0];

foreach ($rows as $row) {
    $stats['scanned']++;
    $contractId = (int)($row['contract_id'] ?? 0);
    $data = json_decode((string)($row['contract_data_json'] ?? ''), true);
    if (!is_array($data)) {
        $stats['skipped']++;
        continue;
    }
    $pdfs = m360_contract_pdf_list_from_data($data);
    foreach ($pdfs as $pdf) {
        if (strtoupper((string)($pdf['status'] ?? '')) !== 'ACTIVE') {
            continue;
        }
        if ((int)($pdf['document_blob_id'] ?? 0) > 0) {
            $stats['skipped']++;
            continue;
        }
        $rel = trim((string)($pdf['relative_url'] ?? ''));
        if ($rel === '' || !str_starts_with($rel, 'storage/')) {
            $stats['skipped']++;
            continue;
        }
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($abs)) {
            $stats['skipped']++;
            continue;
        }
        $bytes = (string)@file_get_contents($abs);
        if ($bytes === '' || !str_starts_with($bytes, '%PDF')) {
            $stats['skipped']++;
            continue;
        }
        $vault = m360_vault_store_contract_pdf(
            $conn,
            $contractId,
            (int)($row['online_request_id'] ?? 0),
            (int)($row['customer_id'] ?? 0),
            $bytes,
            (string)($pdf['filename'] ?? 'contract.pdf'),
            str_replace('storage/', '', $rel),
            ERP_PHASE1_PLATFORM_OWNER_ID
        );
        if (!$vault['ok']) {
            $stats['errors']++;
            continue;
        }
        $pdf['document_blob_id'] = (string)$vault['blob_id'];
        $pdf['sha256_hash'] = (string)$vault['sha256'];
        $pdf['vault_canonical'] = '1';
        $data['contract_pdfs'] = array_map(
            static fn($p) => is_array($p) && (string)($p['pdf_id'] ?? '') === (string)($pdf['pdf_id'] ?? '') ? $pdf : $p,
            $pdfs
        );
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded !== false) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_data_json = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ?',
                [$encoded, $contractId]
            );
        }
        $stats['backfilled']++;
        break;
    }
}

echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
