<?php
declare(strict_types=1);

/**
 * MOGHARE360 Mirror API Client
 * Forwards requests to Master Server — no local persistence, no file write, no database.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'mirror-layout.php';

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string}
 */
function mirror_api_post(string $endpoint, array $payload): array
{
    $config = mirror_config();
    $base = rtrim((string)($config['MASTER_SERVER_BASE_URL'] ?? ''), '/');
    $timeout = max(3, min(60, (int)($config['API_TIMEOUT_SECONDS'] ?? 15)));

    if ($base === '' || str_contains($base, 'example')) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'Master API endpoint implementation required on local server — MASTER_SERVER_BASE_URL is not configured.',
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    $url = $base . $endpoint;
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'خطا در آماده‌سازی درخواست.',
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'افزونه cURL در هاست فعال نیست — ارسال به Master Server ممکن نیست.',
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'خطا در اتصال به Master Server.',
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'X-MOGHARE360-Mirror: 1',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
    ]);

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'status' => $status,
            'message' => 'خطا در ارتباط با Master Server: ' . ($curlError !== '' ? $curlError : 'نامشخص'),
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    $decoded = json_decode($raw, true);

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'message' => is_array($decoded) && isset($decoded['message'])
            ? (string)$decoded['message']
            : ($status >= 200 && $status < 300 ? 'درخواست ارسال شد.' : 'پاسخ ناموفق از Master Server.'),
        'data' => $decoded,
        'endpoint' => $endpoint,
    ];
}

/** @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string} */
function mirror_api_customer_request(array $payload): array
{
    return mirror_api_post('/api/customer/request', $payload);
}

/** @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string} */
function mirror_api_staff_login(array $payload): array
{
    return mirror_api_post('/api/auth/staff-login', $payload);
}

/** @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string} */
function mirror_api_owner_login(array $payload): array
{
    return mirror_api_post('/api/auth/owner-login', $payload);
}

/** @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string} */
function mirror_api_company_dashboard(array $payload): array
{
    return mirror_api_post('/api/dashboard/company-owner', $payload);
}

/** @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string} */
function mirror_api_access_request(array $payload): array
{
    return mirror_api_post('/api/access/request', $payload);
}

/** @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string} */
function mirror_api_health(): array
{
    return mirror_api_post('/api/mirror/health', ['mirror' => true]);
}
