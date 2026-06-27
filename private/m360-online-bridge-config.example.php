<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.3 — Online bridge config example (placeholders only).
 * Copy to private/m360-online-bridge-config.php (gitignored).
 */

return [
    'bridge_enabled' => true,
    'bridge_secret' => 'PUT_LONG_RANDOM_SECRET_HERE',
    'allowed_sources' => ['moghareh360.ir'],
    'allowed_methods' => ['POST'],
    'max_payload_bytes' => 16384,
    'request_ttl_seconds' => 300,
    'log_enabled' => true,
    'mask_logs' => true,
    'store_to_p1_intake' => true,
    'default_company_code' => 'MOGHAREH_MAIN',
];
