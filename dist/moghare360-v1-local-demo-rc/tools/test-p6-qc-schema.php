<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/P6_qc_final_inspection_delivery_readiness.sql') ?: '';
$qc = file_get_contents($root . '/public_html/includes/m360-qc-helper.php') ?: '';

function p6s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

$results = [];
$results[] = p6s_pass('Migration exists', is_file($root . '/database/migrations/P6_qc_final_inspection_delivery_readiness.sql'));
$results[] = p6s_pass('No DROP/DELETE/TRUNCATE', !preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $migration));
$results[] = p6s_pass('qc_status column', str_contains($migration, 'qc_status'));
$results[] = p6s_pass('delivery_readiness_status', str_contains($migration, 'delivery_readiness_status'));
$results[] = p6s_pass('delivery_ready_at', str_contains($migration, 'delivery_ready_at'));
$results[] = p6s_pass('erp_qc_check_items', str_contains($migration, 'erp_qc_check_items'));
$results[] = p6s_pass('erp_qc_events', str_contains($migration, 'erp_qc_events'));
$results[] = p6s_pass('erp_qc_media_events metadata only', str_contains($migration, 'erp_qc_media_events') && str_contains($migration, 'DIRECT_CAMERA'));
$results[] = p6s_pass('No file upload table', !preg_match('/file_path|upload_path|blob/i', $migration));
$results[] = p6s_pass('Non-destructive pattern', str_contains($migration, 'IF OBJECT_ID') && str_contains($migration, 'IF COL_LENGTH'));
$results[] = p6s_pass('QC workflow statuses', str_contains($qc, 'QC_IN_PROGRESS') && str_contains($qc, 'DELIVERY_READY'));

$pass = 0; $fail = 0;
echo "# P6 QC Schema Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
