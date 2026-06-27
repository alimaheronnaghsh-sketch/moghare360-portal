<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.3 — Online intake bridge security (HMAC, config, masked logs).
 */

const M360_ONLINE_BRIDGE_HEADER_SOURCE = 'HTTP_X_M360_SOURCE';
const M360_ONLINE_BRIDGE_HEADER_TIMESTAMP = 'HTTP_X_M360_TIMESTAMP';
const M360_ONLINE_BRIDGE_HEADER_SIGNATURE = 'HTTP_X_M360_SIGNATURE';

const M360_ONLINE_BRIDGE_SOURCE_DEFAULT = 'moghareh360.ir';
const M360_ONLINE_BRIDGE_CHANNEL = 'WEBSITE';

function m360_online_bridge_repo_root(): string
{
    return dirname(__DIR__, 2);
}

/** @return array<string, mixed> */
function m360_online_bridge_config_example(): array
{
    $path = m360_online_bridge_repo_root() . '/private/m360-online-bridge-config.example.php';
    if (!is_file($path)) {
        return [];
    }
    $loaded = require $path;
    return is_array($loaded) ? $loaded : [];
}

/** @return array<string, mixed> */
function m360_online_bridge_config_merged(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $cfg = m360_online_bridge_config_example();
    $private = m360_online_bridge_repo_root() . '/private/m360-online-bridge-config.php';
    if (is_file($private)) {
        $loaded = require $private;
        if (is_array($loaded)) {
            $cfg = array_merge($cfg, $loaded);
        }
    }

    $envSecret = getenv('M360_ONLINE_BRIDGE_SECRET');
    if ($envSecret !== false && trim((string)$envSecret) !== '') {
        $cfg['bridge_secret'] = trim((string)$envSecret);
    }
    if (getenv('M360_ONLINE_BRIDGE_ENABLED') !== false) {
        $cfg['bridge_enabled'] = in_array(strtolower((string)getenv('M360_ONLINE_BRIDGE_ENABLED')), ['1', 'true', 'yes', 'on'], true);
    }

    return $cfg;
}

function m360_online_bridge_config_bool(array $cfg, string $key, bool $default = false): bool
{
    if (!array_key_exists($key, $cfg)) {
        return $default;
    }
    $val = $cfg[$key];
    if (is_bool($val)) {
        return $val;
    }
    return in_array(strtolower(trim((string)$val)), ['1', 'true', 'yes', 'on'], true);
}

function m360_online_bridge_is_placeholder_secret(string $secret): bool
{
    $s = strtolower(trim($secret));
    if ($s === '') {
        return true;
    }
    if (str_starts_with($s, 'put_') || str_contains($s, 'placeholder') || str_contains($s, 'changeme')) {
        return true;
    }
    return false;
}

function m360_online_bridge_secret_configured(): bool
{
    $secret = trim((string)(m360_online_bridge_config_merged()['bridge_secret'] ?? ''));
    return $secret !== '' && !m360_online_bridge_is_placeholder_secret($secret);
}

function m360_online_bridge_secret_masked(): string
{
    $secret = trim((string)(m360_online_bridge_config_merged()['bridge_secret'] ?? ''));
    if ($secret === '') {
        return '(not configured)';
    }
    if (m360_online_bridge_is_placeholder_secret($secret)) {
        return '(placeholder)';
    }
    $len = strlen($secret);
    if ($len <= 8) {
        return str_repeat('*', $len);
    }
    return str_repeat('*', max(12, $len - 4)) . substr($secret, -4);
}

function m360_online_bridge_log_dir(): string
{
    return m360_online_bridge_repo_root() . '/private/logs/online-bridge';
}

function m360_online_bridge_log_path(): string
{
    return m360_online_bridge_log_dir() . '/bridge-intake.log';
}

function m360_online_bridge_mask_mobile(string $mobile): string
{
    $digits = preg_replace('/\D+/', '', $mobile) ?? '';
    if (strlen($digits) < 4) {
        return '****';
    }
    return substr($digits, 0, 4) . str_repeat('*', max(3, strlen($digits) - 6)) . substr($digits, -2);
}

function m360_online_bridge_mask_ip(string $ip): string
{
    $ip = trim($ip);
    if ($ip === '') {
        return '';
    }
    if (str_contains($ip, ':')) {
        return 'ipv6:' . substr(hash('sha256', $ip), 0, 12);
    }
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
    }
    return 'ip:' . substr(hash('sha256', $ip), 0, 12);
}

/** @param array<string, mixed> $data */
function m360_online_bridge_sanitize_for_log(array $data): array
{
    $cfg = m360_online_bridge_config_merged();
    if (!m360_online_bridge_config_bool($cfg, 'mask_logs', true)) {
        return $data;
    }
    $out = $data;
    if (isset($out['mobile'])) {
        $out['mobile'] = m360_online_bridge_mask_mobile((string)$out['mobile']);
    }
    if (isset($out['client_ip'])) {
        $out['client_ip'] = m360_online_bridge_mask_ip((string)$out['client_ip']);
    }
    if (isset($out['signature'])) {
        $out['signature'] = m360_online_bridge_mask_secret((string)$out['signature']);
    }
    return $out;
}

function m360_online_bridge_mask_secret(string $value): string
{
    $v = trim($value);
    if ($v === '') {
        return '';
    }
    if (strlen($v) <= 8) {
        return str_repeat('*', strlen($v));
    }
    return substr($v, 0, 6) . '...' . substr($v, -4);
}

/** @param array<string, mixed> $entry */
function m360_online_bridge_write_log(array $entry): void
{
    $cfg = m360_online_bridge_config_merged();
    if (!m360_online_bridge_config_bool($cfg, 'log_enabled', true)) {
        return;
    }
    $dir = m360_online_bridge_log_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return;
    }
    $entry['logged_at'] = gmdate('c');
    if (m360_online_bridge_config_bool($cfg, 'mask_logs', true)) {
        $entry = m360_online_bridge_sanitize_for_log($entry);
    }
    @file_put_contents(m360_online_bridge_log_path(), json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function m360_online_bridge_json_response(bool $ok, string $code, string $message, int $httpStatus = 200, array $data = []): never
{
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => $ok, 'code' => $code, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function m360_online_bridge_request_header(string $serverKey): string
{
    return trim((string)($_SERVER[$serverKey] ?? ''));
}

function m360_online_bridge_read_raw_body(): string
{
    $raw = file_get_contents('php://input');
    return $raw === false ? '' : $raw;
}

/** @return array{ok:bool,code:string,message:string,payload?:array<string,mixed>} */
function m360_online_bridge_verify_request(string $rawBody): array
{
    $cfg = m360_online_bridge_config_merged();
    if (!m360_online_bridge_config_bool($cfg, 'bridge_enabled', false)) {
        return ['ok' => false, 'code' => 'BRIDGE_DISABLED', 'message' => 'Bridge intake is disabled.'];
    }
    if (!m360_online_bridge_secret_configured()) {
        return ['ok' => false, 'code' => 'BRIDGE_NOT_CONFIGURED', 'message' => 'Bridge secret is not configured.'];
    }

    $method = strtoupper(trim((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')));
    $allowedMethods = $cfg['allowed_methods'] ?? ['POST'];
    if (!is_array($allowedMethods)) {
        $allowedMethods = ['POST'];
    }
    $allowedMethods = array_map(static fn($m): string => strtoupper(trim((string)$m)), $allowedMethods);
    if (!in_array($method, $allowedMethods, true)) {
        return ['ok' => false, 'code' => 'METHOD_NOT_ALLOWED', 'message' => 'Only POST is allowed.'];
    }

    $maxBytes = max(1024, (int)($cfg['max_payload_bytes'] ?? 16384));
    if (strlen($rawBody) > $maxBytes) {
        return ['ok' => false, 'code' => 'PAYLOAD_TOO_LARGE', 'message' => 'Payload exceeds allowed size.'];
    }

    $contentType = strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? '')));
    if ($contentType !== '' && !str_contains($contentType, 'application/json')) {
        return ['ok' => false, 'code' => 'INVALID_CONTENT_TYPE', 'message' => 'Content-Type must be application/json.'];
    }

    $source = m360_online_bridge_request_header(M360_ONLINE_BRIDGE_HEADER_SOURCE);
    $timestampRaw = m360_online_bridge_request_header(M360_ONLINE_BRIDGE_HEADER_TIMESTAMP);
    $signature = m360_online_bridge_request_header(M360_ONLINE_BRIDGE_HEADER_SIGNATURE);
    if ($source === '' || $timestampRaw === '' || $signature === '') {
        return ['ok' => false, 'code' => 'MISSING_SECURITY_HEADERS', 'message' => 'Required security headers are missing.'];
    }

    $allowedSources = $cfg['allowed_sources'] ?? [M360_ONLINE_BRIDGE_SOURCE_DEFAULT];
    if (!is_array($allowedSources)) {
        $allowedSources = [M360_ONLINE_BRIDGE_SOURCE_DEFAULT];
    }
    $allowedSources = array_map(static fn($s): string => strtolower(trim((string)$s)), $allowedSources);
    if (!in_array(strtolower($source), $allowedSources, true)) {
        return ['ok' => false, 'code' => 'SOURCE_NOT_ALLOWED', 'message' => 'Source is not allowed.'];
    }

    if (!ctype_digit($timestampRaw)) {
        return ['ok' => false, 'code' => 'INVALID_TIMESTAMP', 'message' => 'Timestamp is invalid.'];
    }

    $ttl = max(60, (int)($cfg['request_ttl_seconds'] ?? 300));
    if (abs(time() - (int)$timestampRaw) > $ttl) {
        return ['ok' => false, 'code' => 'TIMESTAMP_EXPIRED', 'message' => 'Request timestamp expired.'];
    }

    $secret = trim((string)($cfg['bridge_secret'] ?? ''));
    $expected = hash_hmac('sha256', $timestampRaw . $rawBody, $secret);
    if (!hash_equals($expected, $signature)) {
        m360_online_bridge_write_log(['event' => 'signature_rejected', 'source' => $source, 'timestamp' => $timestampRaw]);
        return ['ok' => false, 'code' => 'INVALID_SIGNATURE', 'message' => 'Signature verification failed.'];
    }

    if ($rawBody === '') {
        return ['ok' => false, 'code' => 'EMPTY_BODY', 'message' => 'Request body is empty.'];
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        return ['ok' => false, 'code' => 'INVALID_JSON', 'message' => 'Request body must be valid JSON.'];
    }

    return ['ok' => true, 'code' => 'OK', 'message' => 'Verified.', 'payload' => $payload];
}

/** @param array<string, mixed> $payload */
function m360_online_bridge_validate_payload(array $payload): array
{
    $honeypot = trim((string)($payload['website'] ?? $payload['hp_field'] ?? ''));
    if ($honeypot !== '') {
        return ['ok' => false, 'code' => 'HONEYPOT', 'message' => 'Request rejected.'];
    }

    $name = trim((string)($payload['customer_name'] ?? ''));
    $mobile = trim((string)($payload['mobile'] ?? ''));
    $vehicleTitle = trim((string)($payload['vehicle_title'] ?? ''));
    if ($name === '' || $mobile === '' || $vehicleTitle === '') {
        return ['ok' => false, 'code' => 'MISSING_FIELDS', 'message' => 'Required fields are missing.'];
    }

    $digits = preg_replace('/\D+/', '', $mobile) ?? '';
    if (preg_match('/^09\d{9}$/', $digits) !== 1) {
        return ['ok' => false, 'code' => 'INVALID_MOBILE', 'message' => 'Mobile format is invalid.'];
    }

    return [
        'ok' => true,
        'code' => 'OK',
        'message' => 'Valid.',
        'fields' => [
            'customer_name' => mb_substr($name, 0, 200),
            'mobile' => $digits,
            'vehicle_title' => mb_substr($vehicleTitle, 0, 200),
            'plate_masked' => mb_substr(trim((string)($payload['plate_masked'] ?? '')), 0, 50),
            'request_type' => mb_substr(trim((string)($payload['request_type'] ?? '')), 0, 80),
            'message' => mb_substr(trim((string)($payload['message'] ?? '')), 0, 2000),
            'preferred_contact_time' => mb_substr(trim((string)($payload['preferred_contact_time'] ?? '')), 0, 100),
            'source_page' => mb_substr(trim((string)($payload['source_page'] ?? '')), 0, 200),
            'source' => mb_substr(trim((string)($payload['source'] ?? M360_ONLINE_BRIDGE_SOURCE_DEFAULT)), 0, 80),
            'channel' => M360_ONLINE_BRIDGE_CHANNEL,
            'submitted_at' => trim((string)($payload['submitted_at'] ?? gmdate('c'))),
            'client_ip' => m360_online_bridge_mask_ip((string)($_SERVER['REMOTE_ADDR'] ?? '')),
            'user_agent' => mb_substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 120),
        ],
    ];
}

function m360_online_bridge_compute_signature(string $timestamp, string $rawBody, string $secret): string
{
    return hash_hmac('sha256', $timestamp . $rawBody, $secret);
}
