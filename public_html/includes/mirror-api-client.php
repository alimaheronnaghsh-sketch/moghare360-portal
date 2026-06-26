<?php
declare(strict_types=1);

/**
 * MOGHARE360 public site API client (forwards to configured service base URL).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'mirror-layout.php';

function mirror_api_endpoint_path(string $endpoint): string
{
    $endpoint = '/' . ltrim($endpoint, '/');
    if (!str_ends_with($endpoint, '.php')) {
        $endpoint .= '.php';
    }
    return $endpoint;
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, status: int, message: string, data: mixed, endpoint: string}
 */
function mirror_api_post(string $endpoint, array $payload): array
{
    $config = mirror_config();
    $base = rtrim((string)($config['MASTER_SERVER_BASE_URL'] ?? ''), '/');
    $timeout = max(3, min(60, (int)($config['API_TIMEOUT_SECONDS'] ?? 15)));

    if ($base === '' || str_contains($base, 'example.invalid')) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'سرویس در حال حاضر پیکربندی نشده است. لطفاً بعداً تلاش کنید.',
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    $url = $base . mirror_api_endpoint_path($endpoint);
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
            'message' => 'امکان ارسال درخواست در حال حاضر وجود ندارد.',
            'data' => null,
            'endpoint' => $endpoint,
        ];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'خطا در اتصال به سرویس.',
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        $targetHost = parse_url($url, PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($targetHost !== false && $currentHost !== '' && strcasecmp((string)$targetHost, $currentHost) === 0) {
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        }
    }

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'status' => $status,
            'message' => 'خطا در ارتباط با سرویس. لطفاً دوباره تلاش کنید.',
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
            : ($status >= 200 && $status < 300 ? 'درخواست ارسال شد.' : 'پاسخ ناموفق از سرویس.'),
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
