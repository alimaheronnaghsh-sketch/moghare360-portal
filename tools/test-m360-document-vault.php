<?php
declare(strict_types=1);

/**
 * MOGHARE360 Prompt 0 — document vault verification (CLI, local only).
 * Usage: php tools/test-m360-document-vault.php
 */

$root = dirname(__DIR__);
chdir($root . DIRECTORY_SEPARATOR . 'public_html');

require_once __DIR__ . '/../public_html/includes/m360-document-vault-helper.php';

$report = [
    'table_exists' => false,
    'insert_test' => false,
    'hash_match' => false,
    'load_source' => '',
    'rollback_file' => is_file($root . '/database/migrations/rollback/P_FINAL_00_document_blob_vault_rollback.sql'),
    'mobile_leak' => true,
];

$conn = customer_core_db();
if ($conn === false) {
    fwrite(STDERR, "DB connection failed\n");
    exit(2);
}

$report['table_exists'] = m360_vault_table_exists($conn);
if (!$report['table_exists']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "RUN_MIGRATION=P_FINAL_00_document_blob_vault.sql\n";
    exit(1);
}

$payload = '%PDF-1.4 test vault ' . gmdate('c');
$store = m360_vault_store($conn, [
    'owner_type' => 'ONLINE_REQUEST',
    'owner_id' => 999999001,
    'related_request_id' => 999999001,
    'document_category' => 'CONTRACT_PDF',
    'original_file_name' => 'vault-selftest.pdf',
    'content_type' => 'application/pdf',
    'file_extension' => 'pdf',
    'content_binary' => $payload,
    'disk_mirror_path' => 'contracts/selftest/vault-selftest.pdf',
    'supersede_same_category' => true,
    'notes_json' => ['selftest' => true],
]);

$report['insert_test'] = $store['ok'];
if ($store['ok']) {
    $load = m360_vault_load_bytes($conn, (int)$store['blob_id']);
    $report['load_source'] = (string)($load['source'] ?? '');
    $report['hash_match'] = hash_equals((string)$store['sha256'], hash('sha256', $load['bytes']));
    $report['pdf_prefix'] = str_starts_with($load['bytes'], '%PDF');
}

$grepPaths = [
    $root . '/public_html/includes/m360-document-vault-helper.php',
    $root . '/public_html/m360-document-vault-stream.php',
];
$mobilePattern = '/09\d{9}/';
$report['mobile_leak'] = false;
foreach ($grepPaths as $path) {
    $text = (string)@file_get_contents($path);
    if (preg_match($mobilePattern, $text)) {
        $report['mobile_leak'] = true;
        break;
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($report['insert_test'] && $report['hash_match'] ? 0 : 1);
