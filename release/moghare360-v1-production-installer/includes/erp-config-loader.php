<?php
/**
 * MOGHARE360 ERP Config Loader
 *
 * Loads ERP private configuration safely.
 *
 * Rules:
 * - No output
 * - No login
 * - No session
 * - No database connection
 * - No secret display
 */

declare(strict_types=1);

function erp_config_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php';
}

function erp_config_required_keys(): array
{
    return [
        'environment',
        'debug',
        'database.server',
        'database.name',
        'database.driver',
        'database.trusted_connection',
        'database.username',
        'database.password',
        'security.display_errors_to_browser',
        'security.log_errors_internally',
    ];
}

function erp_config_get_nested(array $config, string $key)
{
    $parts = explode('.', $key);
    $value = $config;

    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return null;
        }

        $value = $value[$part];
    }

    return $value;
}

function erp_config_validate(array $config): void
{
    foreach (erp_config_required_keys() as $key) {
        if (erp_config_get_nested($config, $key) === null) {
            throw new RuntimeException('ERP configuration is invalid.');
        }
    }

    if (!is_string($config['environment'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_bool($config['debug'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_array($config['database'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_array($config['security'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_string($config['database']['server'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_string($config['database']['name'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_string($config['database']['driver'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_bool($config['database']['trusted_connection'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_string($config['database']['username'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_string($config['database']['password'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_bool($config['security']['display_errors_to_browser'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    if (!is_bool($config['security']['log_errors_internally'])) {
        throw new RuntimeException('ERP configuration is invalid.');
    }
}

function erp_load_config(): array
{
    $path = erp_config_path();

    if (!is_file($path)) {
        throw new RuntimeException('ERP configuration is missing.');
    }

    $config = require $path;

    if (!is_array($config)) {
        throw new RuntimeException('ERP configuration is invalid.');
    }

    erp_config_validate($config);

    return $config;
}
