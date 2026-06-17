<?php
/**
 * MOGHARE360 ERP Example Configuration
 *
 * This file is safe to commit because it contains placeholders only.
 * Copy this file to private/erp-config.php for local development.
 *
 * Never commit private/erp-config.php.
 * Never place real secrets inside public_html.
 */

return [
    'environment' => 'local',
    'debug' => false,

    'database' => [
        'server' => 'localhost\\SQLEXPRESS',
        'name' => 'moghare360_ERP',
        'driver' => 'odbc',
        'trusted_connection' => true,
        'username' => '',
        'password' => '',
    ],

    'security' => [
        'display_errors_to_browser' => false,
        'log_errors_internally' => true,
    ],
];
