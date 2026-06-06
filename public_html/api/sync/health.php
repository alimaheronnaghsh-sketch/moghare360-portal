<?php
declare(strict_types=1);

require_once __DIR__ . '/config-sync.php';

syncRequireToken();

syncJson([
    'ok' => true,
    'app' => 'MOGHARE360 cPanel Sync API',
    'time' => date('Y-m-d H:i:s')
]);