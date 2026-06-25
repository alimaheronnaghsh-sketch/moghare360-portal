<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 1A Validation Engine CLI Test
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-validation-engine-test-cases.php';

$summary = wave_1a_run_validation_tests();

foreach ($summary['results'] as $row) {
    $label = $row['pass'] ? 'PASS' : 'FAIL';
    echo $label . ' — ' . $row['name'] . PHP_EOL;
}

echo PHP_EOL;
echo 'Passed: ' . $summary['passed'] . ' / ' . $summary['total'] . PHP_EOL;

if (!$summary['ok']) {
    fwrite(STDERR, 'WAVE 1A VALIDATION ENGINE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 1A VALIDATION ENGINE TEST PASSED' . PHP_EOL;
exit(0);
