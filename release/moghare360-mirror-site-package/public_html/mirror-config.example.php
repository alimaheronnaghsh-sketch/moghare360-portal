<?php
declare(strict_types=1);

/**
 * Mirror site configuration — EXAMPLE ONLY (no secrets).
 * Copy to mirror-config.php on host and set MASTER_SERVER_BASE_URL.
 */

return [
    'MASTER_SERVER_BASE_URL' => 'http://localhost:8080/moghare360',
    'MIRROR_MODE' => true,
    'LOCAL_STORAGE_ALLOWED' => false,
    'HOST_DATABASE_ALLOWED' => false,
    'API_TIMEOUT_SECONDS' => 15,
    'BRAND_NAME' => 'MOGHAREH MOTORS / MOGHARE360',
    'SUPPORT_PHONE' => '021-00000000',
];
