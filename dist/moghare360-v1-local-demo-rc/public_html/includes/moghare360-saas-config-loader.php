<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — SaaS config loader (secrets externalized).
 */

function mogh_saas_repo_root(): string
{
    return dirname(__DIR__, 2);
}

function mogh_saas_require_file(string $relative): void
{
    $candidates = [
        __DIR__ . DIRECTORY_SEPARATOR . $relative,
        mogh_saas_repo_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $relative,
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
    throw new RuntimeException('Required file not found: ' . $relative);
}

function mogh_saas_default_config(): array
{
    return [
        'enabled' => true,
        'default_company_code' => 'MOGHAREH_MAIN',
        'storage_root' => mogh_saas_repo_root() . DIRECTORY_SEPARATOR . 'storage',
        'api_base_url' => 'http://localhost:8080/moghare360/',
        'mirror_allowed_origins' => [
            'https://moghareh360.ir',
            'https://www.moghareh360.ir',
            'http://localhost:8080',
        ],
        'api_version' => '1.0.0',
    ];
}

function mogh_saas_find_config_path(): ?string
{
    $candidates = [
        mogh_saas_repo_root() . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php',
        dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'erp-config.php',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

function mogh_saas_load_config(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $saas = mogh_saas_default_config();

    $configPath = mogh_saas_find_config_path();
    if ($configPath !== null) {
        $erp = require $configPath;
        if (is_array($erp)) {
            if (isset($erp['saas']) && is_array($erp['saas'])) {
                $saas = array_merge($saas, $erp['saas']);
            }
            if (isset($erp['environment']) && is_string($erp['environment'])) {
                $saas['environment'] = $erp['environment'];
            }
            $saas['config_missing'] = false;
            $saas['erp_config'] = $erp;
        }
    } else {
        try {
            mogh_saas_require_file('erp-config-loader.php');
            $erp = erp_load_config();
            if (isset($erp['saas']) && is_array($erp['saas'])) {
                $saas = array_merge($saas, $erp['saas']);
            }
            if (isset($erp['environment']) && is_string($erp['environment'])) {
                $saas['environment'] = $erp['environment'];
            }
            $saas['config_missing'] = false;
            $saas['erp_config'] = $erp;
        } catch (Throwable) {
            $saas['config_missing'] = true;
        }
    }

    $cached = $saas;
    return $saas;
}

function mogh_saas_is_enabled(): bool
{
    $cfg = mogh_saas_load_config();
    return !empty($cfg['enabled']);
}

function mogh_saas_api_version(): string
{
    $cfg = mogh_saas_load_config();
    return (string)($cfg['api_version'] ?? '1.0.0');
}
