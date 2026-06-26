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
    'BRAND_NAME' => 'MOGHAREH360',
    'SUPPORT_PHONE' => '021-00000000',
    'SMS_OTP_ENABLED' => false,
    'SMS_GATEWAY_CONFIGURED' => false,
    'M360_SMS_PROVIDER' => '',
    'M360_SMS_API_KEY' => '',
    'M360_SMS_SENDER' => '',
    'M360_SMS_PATTERN_ID' => '',
    'SMS_PROVIDER' => '',
    'SMS_API_KEY' => '',
    'SMS_SENDER' => '',
    'IPPANEL_API_KEY' => '',
    'IPPANEL_SENDER' => '',
];
