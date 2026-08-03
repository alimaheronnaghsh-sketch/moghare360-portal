<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-contract-engine.php';

p360hr_require_password_changed_for_cartable();

$contractId = (int)($_GET['contract_id'] ?? 0);
$c = p360hr_contract_get($contractId);
if ($c === null) {
    http_response_code(404);
    echo 'یافت نشد.';
    exit;
}

$eid = (int)$c['employee_id'];
p360hr_assert_own_employee($eid);

$path = (string)($c['final_pdf_path'] ?? '');
if ($path === '' || !is_file($path)) {
    $gen = p360hr_generate_pdf($contractId, (int)($c['is_locked'] ?? 0) === 1);
    if (empty($gen['ok'])) {
        http_response_code(404);
        echo p360hr_h((string)($gen['message'] ?? 'PDF موجود نیست.'));
        exit;
    }
    $path = (string)$gen['path'];
    $bytes = (string)$gen['bytes'];
} else {
    $bytes = (string)file_get_contents($path);
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="contract-' . $contractId . '.pdf"');
header('Content-Length: ' . strlen($bytes));
header('X-Content-Type-Options: nosniff');
echo $bytes;
exit;
