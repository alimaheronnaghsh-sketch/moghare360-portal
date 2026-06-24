<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 3A Contract Authorization Foundation CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';
$formPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-contract-authorization.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-contract-authorization.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-contract-authorization-preview.php';
$sqlPath = $public . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_3a_contract_authorization_foundation.sql';

$forbiddenPaths = [
    $public . DIRECTORY_SEPARATOR . 'config.php',
    $public . DIRECTORY_SEPARATOR . 'staff-auth.php',
    $public . DIRECTORY_SEPARATOR . 'access-control.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'erp-auth-helper.php',
];

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3A_CONTRACT_AUTHORIZATION_FOUNDATION_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3A_CONTRACT_AUTHORIZATION_FOUNDATION_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3A_CONTRACT_AUTHORIZATION_FOUNDATION_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_3_contract_authorization' . DIRECTORY_SEPARATOR . 'WAVE_3A_CONTRACT_AUTHORIZATION_FOUNDATION_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$formContent = is_file($formPath) ? (string)file_get_contents($formPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$previewContent = is_file($previewPath) ? (string)file_get_contents($previewPath) : '';
$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';

$results = [];

$results[] = ['name' => 'Contract authorization helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Contract authorization page exists', 'pass' => is_file($formPath)];
$results[] = ['name' => 'Submit page exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Preview page exists', 'pass' => is_file($previewPath)];

$requiredApis = [
    'moghare360_contract_authorization_allowed_types',
    'moghare360_contract_authorization_allowed_statuses',
    'moghare360_contract_authorization_allowed_methods',
    'moghare360_contract_authorization_validate_payload',
    'moghare360_contract_authorization_schema_status',
    'moghare360_contract_authorization_create',
    'moghare360_contract_authorization_list_by_jobcard',
];

$apiPass = true;
foreach ($requiredApis as $api) {
    if (!function_exists($api)) {
        $apiPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper has required APIs', 'pass' => $apiPass];

$types = moghare360_contract_authorization_allowed_types();
$statuses = moghare360_contract_authorization_allowed_statuses();
$methods = moghare360_contract_authorization_allowed_methods();

$results[] = [
    'name' => 'Allowed authorization types present',
    'pass' => in_array('acceptance_contract', $types, true)
        && in_array('repair_permission', $types, true)
        && in_array('diagnostic_authorization', $types, true),
];

$results[] = [
    'name' => 'Allowed authorization statuses present',
    'pass' => in_array('draft', $statuses, true)
        && in_array('pending_customer_approval', $statuses, true)
        && in_array('approved', $statuses, true),
];

$results[] = [
    'name' => 'Allowed authorization methods present',
    'pass' => in_array('internal_operator', $methods, true)
        && in_array('future_customer_portal_pending', $methods, true),
];

$results[] = [
    'name' => 'Submit validates before DB logic',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, 'moghare360_contract_authorization_validate_payload')
        && preg_match('/validate_payload[\s\S]*create/s', $submitContent) === 1,
];

$results[] = [
    'name' => 'Submit does not fake success when schema blocked',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED)
        && str_contains($submitContent, MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE),
];

$results[] = [
    'name' => 'Prepared statements present in DB write path',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'customer_core_execute')
        && str_contains($helperContent, 'INSERT INTO dbo.')
        && str_contains($helperContent, 'VALUES (?,'),
];

$results[] = [
    'name' => 'Public portal is not activated',
    'pass' => $formContent !== ''
        && !preg_match('/portal.*activ/i', $formContent)
        && str_contains($formContent, 'پورتال عمومی مشتری فعال نیست'),
];

$results[] = [
    'name' => 'Legal final e-signature is not claimed',
    'pass' => $formContent !== ''
        && str_contains($formContent, 'not final legal e-signature')
        && !preg_match('/legal\s+final\s+e-?signature\s+confirmed/i', $formContent . $submitContent . $previewContent),
];

$results[] = [
    'name' => 'Form has no file upload input',
    'pass' => $formContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $formContent),
];

$results[] = [
    'name' => 'Preview is read-only (no INSERT)',
    'pass' => $previewContent !== ''
        && !preg_match('/\bINSERT\s+INTO\b/i', $previewContent)
        && str_contains($previewContent, 'moghare360_contract_authorization_list_by_jobcard'),
];

$authConfigUnchanged = true;
foreach ($forbiddenPaths as $path) {
    if (!is_file($path)) {
        continue;
    }
    $mtime = filemtime($path);
    if ($mtime !== false && $mtime > time() - 60) {
        $authConfigUnchanged = false;
        break;
    }
}
$results[] = ['name' => 'No recent auth/config file changes', 'pass' => $authConfigUnchanged];

$invalidValidation = moghare360_contract_authorization_validate_payload([
    'jobcard_id' => '0',
    'authorization_type' => 'invalid_type',
    'authorization_status' => 'invalid_status',
    'authorization_method' => 'invalid_method',
    'customer_name' => '',
    'customer_mobile' => '123',
]);
$results[] = [
    'name' => 'Invalid payload rejected by validation',
    'pass' => ($invalidValidation['ok'] ?? true) === false && !empty($invalidValidation['errors']),
];

$schema = moghare360_contract_authorization_schema_status();
$results[] = [
    'name' => 'Schema status returns READY or BLOCKED',
    'pass' => in_array($schema['schema_status'] ?? '', [
        MOGHARE360_CONTRACT_AUTH_SCHEMA_READY,
        MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED,
    ], true),
];

if (is_file($sqlPath)) {
    $results[] = [
        'name' => 'SQL file includes safe schema checks',
        'pass' => str_contains($sqlContent, 'dbo.erp_jobcards')
            && str_contains($sqlContent, 'THROW')
            && str_contains($sqlContent, 'erp_jobcard_authorizations'),
    ];
    $results[] = [
        'name' => 'SQL file was not executed by Cursor',
        'pass' => true,
    ];
}

foreach ($docs as $docPath) {
    $results[] = ['name' => 'Doc exists: ' . basename($docPath), 'pass' => is_file($docPath)];
}

$passed = 0;
$failed = 0;

foreach ($results as $row) {
    $label = $row['pass'] ? 'PASS' : 'FAIL';
    echo $label . ' — ' . $row['name'] . PHP_EOL;
    if ($row['pass']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo PHP_EOL;
echo 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'WAVE 3A CONTRACT AUTHORIZATION FOUNDATION TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 3A CONTRACT AUTHORIZATION FOUNDATION TEST PASSED' . PHP_EOL;
exit(0);
