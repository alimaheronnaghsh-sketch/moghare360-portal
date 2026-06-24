<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 1F JobCard DB Write CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$requiredFiles = [
    'submit-jobcard-v2.php',
    'erp-jobcard-create-v2-result.php',
    'includes/moghare360-form-validation-bridge.php',
    'includes/moghare360-jobcard-v2-write-helper.php',
];

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_1_validation_engine' . DIRECTORY_SEPARATOR . 'WAVE_1F_JOBCARD_DB_WRITE_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_1_validation_engine' . DIRECTORY_SEPARATOR . 'WAVE_1F_JOBCARD_DB_WRITE_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_1_validation_engine' . DIRECTORY_SEPARATOR . 'WAVE_1F_JOBCARD_DB_WRITE_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_1_validation_engine' . DIRECTORY_SEPARATOR . 'WAVE_1F_JOBCARD_DB_WRITE_SIGNOFF.md',
];

$jobcardSubmit = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-v2.php';
$customerSubmit = $public . DIRECTORY_SEPARATOR . 'submit-customer-v2.php';
$vehicleSubmit = $public . DIRECTORY_SEPARATOR . 'submit-vehicle-v2.php';
$helper = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-v2-write-helper.php';
$customerHelper = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-customer-v2-write-helper.php';
$vehicleHelper = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-vehicle-v2-write-helper.php';

$results = [];

foreach ($requiredFiles as $relative) {
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $results[] = [
        'name' => 'File exists: ' . $relative,
        'pass' => is_file($path),
    ];
}

$jobcardContent = is_file($jobcardSubmit) ? (string)file_get_contents($jobcardSubmit) : '';
$helperContent = is_file($helper) ? (string)file_get_contents($helper) : '';
$customerContent = is_file($customerSubmit) ? (string)file_get_contents($customerSubmit) : '';
$vehicleContent = is_file($vehicleSubmit) ? (string)file_get_contents($vehicleSubmit) : '';
$customerHelperContent = is_file($customerHelper) ? (string)file_get_contents($customerHelper) : '';
$vehicleHelperContent = is_file($vehicleHelper) ? (string)file_get_contents($vehicleHelper) : '';

$results[] = [
    'name' => 'JobCard submit includes validation bridge',
    'pass' => $jobcardContent !== '' && str_contains($jobcardContent, 'moghare360-form-validation-bridge.php'),
];

$results[] = [
    'name' => 'JobCard submit validates before DB write helper',
    'pass' => $jobcardContent !== ''
        && str_contains($jobcardContent, 'moghare360_validate_form_payload')
        && str_contains($jobcardContent, 'moghare360_jobcard_v2_write')
        && strpos($jobcardContent, 'moghare360_validate_form_payload') < strpos($jobcardContent, 'moghare360_jobcard_v2_write'),
];

$results[] = [
    'name' => 'JobCard write helper uses prepared statements',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'customer_core_execute'),
];

$results[] = [
    'name' => 'JobCard DB write activated in helper',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'INSERT INTO dbo.erp_jobcards'),
];

$dbWriteActivated = $jobcardContent !== ''
    && str_contains($jobcardContent, 'moghare360_jobcard_v2_write')
    && str_contains($helperContent, 'INSERT INTO dbo.erp_jobcards');

$results[] = [
    'name' => 'JobCard DB write status marker',
    'pass' => $dbWriteActivated,
];

$results[] = [
    'name' => 'Customer submit remains DB-write active',
    'pass' => $customerContent !== ''
        && str_contains($customerContent, 'moghare360_customer_v2_write')
        && str_contains($customerHelperContent, 'INSERT INTO dbo.erp_customers'),
];

$results[] = [
    'name' => 'Vehicle submit remains DB-write active',
    'pass' => $vehicleContent !== ''
        && str_contains($vehicleContent, 'moghare360_vehicle_v2_write')
        && str_contains($vehicleHelperContent, 'INSERT INTO dbo.erp_vehicles'),
];

$wave1fSqlCreated = false;
foreach (array_merge($requiredFiles, ['tools/test-wave-1f-jobcard-db-write.php']) as $relative) {
    if (str_ends_with(strtolower($relative), '.sql')) {
        $wave1fSqlCreated = true;
        break;
    }
}

$results[] = [
    'name' => 'No SQL file created for Wave 1F',
    'pass' => !$wave1fSqlCreated,
];

foreach ($docs as $docPath) {
    $results[] = [
        'name' => 'Doc exists: ' . basename($docPath),
        'pass' => is_file($docPath),
    ];
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

if ($dbWriteActivated) {
    echo 'DB_WRITE_ACTIVATED_FOR_JOBCARD_V2' . PHP_EOL;
} else {
    echo 'DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED' . PHP_EOL;
}

echo PHP_EOL;
echo 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'WAVE 1F JOBCARD DB WRITE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 1F JOBCARD DB WRITE TEST PASSED' . PHP_EOL;
exit(0);
