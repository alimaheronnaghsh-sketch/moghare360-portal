<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1.5 — Staff preview of intake contract template (V1 ERP).
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';

m360_intake_contract_require_staff();

$jobcardId = isset($_GET['jobcard_id']) ? (int)$_GET['jobcard_id'] : 0;
$contractId = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
$conn = customer_core_db();

if ($contractId > 0 && $conn !== false) {
    $row = m360_intake_contract_fetch_by_id($conn, $contractId);
    if ($row !== null) {
        $snapshot = m360_intake_contract_snapshot_from_row($row);
        echo m360_contract_render_html($snapshot, true);
        exit;
    }
}

$snapshot = m360_intake_contract_build_snapshot($conn, $jobcardId > 0 ? $jobcardId : null, null);
$snapshot['contract_hash'] = m360_intake_contract_hash(m360_contract_render_html($snapshot, false));
echo m360_contract_render_html($snapshot, true);
