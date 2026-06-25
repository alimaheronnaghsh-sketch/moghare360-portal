<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — API bootstrap (JSON, CORS, tenant, errors).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-config-loader.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-tenant-context.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-saas-storage-adapter.php';

function mogh_api_send_cors(): void
{
    $cfg = mogh_saas_load_config();
    $origins = $cfg['mirror_allowed_origins'] ?? [];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, $origins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, X-MOGHARE360-Mirror, X-MOGHARE360-Company-Id, X-MOGHARE360-Company-Code, X-CSRF-Token');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function mogh_api_json_headers(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    mogh_api_send_cors();
}

/** @return array<string, mixed> */
function mogh_api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function mogh_api_ok(string $message, array $data = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode([
        'ok' => true,
        'message' => $message,
        'version' => mogh_saas_api_version(),
        'data' => $data,
        'timestamp' => gmdate('c'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function mogh_api_fail(string $message, int $status = 400, array $data = []): never
{
    http_response_code($status);
    echo json_encode([
        'ok' => false,
        'message' => $message,
        'version' => mogh_saas_api_version(),
        'data' => $data,
        'timestamp' => gmdate('c'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function mogh_api_log_request($conn, int $companyId, string $endpoint, string $method, int $statusCode, ?string $note = null): void
{
    if (!is_resource($conn)) {
        return;
    }
    $sql = 'INSERT INTO dbo.erp_api_request_log (company_id, endpoint_path, http_method, status_code, request_note, source_ip, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME())';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false) {
        return;
    }
    @odbc_execute($stmt, [
        $companyId,
        $endpoint,
        $method,
        $statusCode,
        $note,
        substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 100),
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);
}

function mogh_api_require_csrf_if_present(array $body, string $formKey = 'api_write'): void
{
    $token = trim((string)($body['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($token === '') {
        return;
    }
    mogh_saas_require_file('erp-csrf.php');
    if (!erp_csrf_validate_token($formKey, $token)) {
        mogh_api_fail('توکن CSRF نامعتبر است.', 403);
    }
}

function mogh_api_sanitize_string(?string $value, int $max = 300): string
{
    $v = trim((string)$value);
    if (mb_strlen($v) > $max) {
        $v = mb_substr($v, 0, $max);
    }
    return $v;
}
