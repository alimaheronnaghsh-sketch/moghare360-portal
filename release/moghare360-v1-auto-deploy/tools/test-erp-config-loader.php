<?php
/**
 * MOGHARE360 ERP Config Loader Local Test
 *
 * CLI-only local test.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/erp-config-loader.php';

$checks = [];

function add_check(array &$checks, string $code, string $name, bool $ok, string $details = ''): void
{
    $checks[] = [
        'code' => $code,
        'name' => $name,
        'result' => $ok ? 'OK' : 'FAIL',
        'details' => $details,
    ];
}

try {
    $path = erp_config_path();

    add_check($checks, 'L01', 'Config path resolved', is_string($path) && $path !== '');
    add_check($checks, 'L02', 'Private config file exists', is_file($path));

    $config = erp_load_config();

    add_check($checks, 'L03', 'Config loaded as array', is_array($config));
    add_check($checks, 'L04', 'Environment exists', isset($config['environment']) && is_string($config['environment']));
    add_check($checks, 'L05', 'Debug flag is boolean', isset($config['debug']) && is_bool($config['debug']));
    add_check($checks, 'L06', 'Database section exists', isset($config['database']) && is_array($config['database']));
    add_check($checks, 'L07', 'Security section exists', isset($config['security']) && is_array($config['security']));
    add_check($checks, 'L08', 'Driver is ODBC', ($config['database']['driver'] ?? '') === 'odbc');
    add_check($checks, 'L09', 'Trusted connection enabled', ($config['database']['trusted_connection'] ?? false) === true);
    add_check($checks, 'L10', 'No database password in local config', ($config['database']['password'] ?? null) === '');

} catch (Throwable $e) {
    add_check($checks, 'L99', 'Config loader exception', false, 'Generic failure');
}

$allOk = true;

echo "MOGHARE360 ERP Config Loader Local Test\n";
echo "======================================\n";

foreach ($checks as $check) {
    if ($check['result'] !== 'OK') {
        $allOk = false;
    }

    echo $check['code'] . "\t" . $check['name'] . "\t" . $check['result'];

    if ($check['details'] !== '') {
        echo "\t" . $check['details'];
    }

    echo PHP_EOL;
}

echo "======================================\n";
echo "Overall Status\t" . ($allOk ? 'OK' : 'FAIL') . PHP_EOL;

exit($allOk ? 0 : 1);
