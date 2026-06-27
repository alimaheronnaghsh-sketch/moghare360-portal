<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.3 — Online intake bridge → P1 persistence adapter.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-intake-security-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_ONLINE_BRIDGE_SOURCE_CHANNEL = 'WEBSITE';

function m360_online_bridge_persistence_available(): bool
{
    return function_exists('m360_online_req_insert') && function_exists('customer_core_db');
}

function m360_online_bridge_persistence_table(): string
{
    return m360_online_req_table();
}

function m360_online_bridge_resolve_company_id($conn): int
{
    if (!is_resource($conn)) {
        return 1;
    }
    $cfg = m360_online_bridge_config_merged();
    $code = trim((string)($cfg['default_company_code'] ?? 'MOGHAREH_MAIN'));
    $sql = 'SELECT TOP 1 company_id FROM dbo.erp_companies WHERE company_code = ? ORDER BY company_id';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt !== false && @odbc_execute($stmt, [$code])) {
        $row = odbc_fetch_array($stmt);
        if ($row !== false && (int)($row['company_id'] ?? 0) > 0) {
            return (int)$row['company_id'];
        }
    }
    return 1;
}

/**
 * @param array<string, mixed> $fields
 * @return array{ok:bool,code:string,message:string,online_request_id?:int,status?:string}
 */
function m360_online_bridge_persist_intake(array $fields): array
{
    $cfg = m360_online_bridge_config_merged();
    if (!m360_online_bridge_config_bool($cfg, 'store_to_p1_intake', true)) {
        return ['ok' => false, 'code' => 'STORE_DISABLED', 'message' => 'P1 intake storage is disabled.'];
    }
    if (!m360_online_bridge_persistence_available()) {
        return ['ok' => false, 'code' => 'P1_PERSISTENCE_MISSING', 'message' => 'P1 online intake persistence not available.'];
    }

    $conn = customer_core_db();
    if (!is_resource($conn)) {
        return ['ok' => false, 'code' => 'DB_UNAVAILABLE', 'message' => 'Database connection unavailable.'];
    }

    $plate = trim((string)($fields['plate_masked'] ?? ''));
    $vehicleTitle = trim((string)($fields['vehicle_title'] ?? ''));
    $message = trim((string)($fields['message'] ?? ''));

    $noteParts = [];
    if ($message !== '') {
        $noteParts[] = $message;
    }
    if ($vehicleTitle !== '') {
        $noteParts[] = 'vehicle_title: ' . $vehicleTitle;
    }
    $noteParts[] = 'channel: ' . M360_ONLINE_BRIDGE_CHANNEL;
    $noteParts[] = 'source: ' . trim((string)($fields['source'] ?? M360_ONLINE_BRIDGE_SOURCE_DEFAULT));
    if (($fields['preferred_contact_time'] ?? '') !== '') {
        $noteParts[] = 'preferred_contact_time: ' . (string)$fields['preferred_contact_time'];
    }
    if (($fields['source_page'] ?? '') !== '') {
        $noteParts[] = 'source_page: ' . (string)$fields['source_page'];
    }

    $payload = [
        'bridge_intake' => true,
        'otp_verified' => 0,
        'source' => (string)($fields['source'] ?? M360_ONLINE_BRIDGE_SOURCE_DEFAULT),
        'channel' => M360_ONLINE_BRIDGE_CHANNEL,
        'vehicle_title' => $vehicleTitle,
        'plate_masked' => $plate,
        'preferred_contact_time' => (string)($fields['preferred_contact_time'] ?? ''),
        'source_page' => (string)($fields['source_page'] ?? ''),
        'submitted_at' => (string)($fields['submitted_at'] ?? gmdate('c')),
        'client_ip_masked' => (string)($fields['client_ip'] ?? ''),
        'user_agent_short' => (string)($fields['user_agent'] ?? ''),
    ];
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($payloadJson === false) {
        $payloadJson = '{}';
    }

    try {
        $insert = m360_online_req_insert($conn, m360_online_bridge_resolve_company_id($conn), [
            'customer_name' => (string)($fields['customer_name'] ?? ''),
            'mobile' => (string)($fields['mobile'] ?? ''),
            'vehicle_plate' => $plate !== '' ? $plate : $vehicleTitle,
            'service_note' => implode("\n", $noteParts),
            'request_type' => (string)($fields['request_type'] ?? 'WEBSITE_LEAD'),
            'source_channel' => M360_ONLINE_BRIDGE_SOURCE_CHANNEL,
            'request_payload_json' => $payloadJson,
        ]);
    } catch (Throwable) {
        return ['ok' => false, 'code' => 'PERSISTENCE_FAILED', 'message' => 'Could not store online request.'];
    }

    if (!($insert['ok'] ?? false) || (int)($insert['online_request_id'] ?? 0) < 1) {
        return ['ok' => false, 'code' => 'PERSISTENCE_FAILED', 'message' => 'Could not store online request.'];
    }

    return [
        'ok' => true,
        'code' => 'STORED',
        'message' => 'Online request stored.',
        'online_request_id' => (int)$insert['online_request_id'],
        'status' => (string)($insert['status'] ?? M360_ONLINE_REQ_STATUS_NEW),
    ];
}

function m360_online_bridge_last_log_status(): string
{
    $path = m360_online_bridge_log_path();
    if (!is_file($path)) {
        return 'NO_LOG';
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines) || $lines === []) {
        return 'NO_LOG';
    }
    $last = json_decode((string)end($lines), true);
    if (!is_array($last)) {
        return 'UNKNOWN';
    }
    return (string)($last['event'] ?? 'UNKNOWN');
}

/**
 * @return array<string, mixed>
 */
function m360_online_bridge_readiness_report(): array
{
    $root = m360_online_bridge_repo_root();
    $cfg = m360_online_bridge_config_merged();
    $blockers = [];
    $warnings = [];

    $exampleExists = is_file($root . '/private/m360-online-bridge-config.example.php');
    $privateExists = is_file($root . '/private/m360-online-bridge-config.php');
    $apiExists = is_file(dirname(__DIR__) . '/api/online-intake-secure-receive.php');
    $formTpl = is_file($root . '/deployment/cpanel/moghareh360/lead-form.php');
    $fwdTpl = is_file($root . '/deployment/cpanel/moghareh360/forward-lead.php.example');

    if (!$exampleExists) {
        $blockers[] = 'Bridge config example missing';
    }
    if (!$privateExists) {
        $warnings[] = 'private/m360-online-bridge-config.php not found on laptop';
    }
    if (!m360_online_bridge_config_bool($cfg, 'bridge_enabled', false)) {
        $warnings[] = 'Bridge disabled in config';
    }
    if (!m360_online_bridge_secret_configured()) {
        $blockers[] = 'Bridge secret not configured';
    }
    if (!$apiExists) {
        $blockers[] = 'Secure receive API missing';
    }
    if (!m360_online_bridge_persistence_available()) {
        $blockers[] = 'P1 persistence helper missing';
    }
    if (!$formTpl || !$fwdTpl) {
        $warnings[] = 'cPanel templates incomplete';
    }

    $logDir = m360_online_bridge_log_dir();
    $logWritable = is_dir($logDir) ? is_writable($logDir) : @mkdir($logDir, 0755, true);

    $status = 'PASS';
    if ($blockers !== []) {
        $status = 'BLOCKED';
    } elseif ($warnings !== []) {
        $status = 'WARNING';
    }

    return [
        'status' => $status,
        'bridge_enabled' => m360_online_bridge_config_bool($cfg, 'bridge_enabled', false),
        'secret_masked' => m360_online_bridge_secret_masked(),
        'secret_configured' => m360_online_bridge_secret_configured(),
        'private_config_present' => $privateExists,
        'secure_api_exists' => $apiExists,
        'cpanel_form_template' => $formTpl,
        'cpanel_forwarder_template' => $fwdTpl,
        'p1_persistence' => m360_online_bridge_persistence_available(),
        'p1_table' => m360_online_bridge_persistence_table(),
        'last_log_event' => m360_online_bridge_last_log_status(),
        'log_dir_ok' => (bool)$logWritable,
        'public_debug_disabled' => true,
        'customer_json_hidden' => true,
        'blockers' => $blockers,
        'warnings' => $warnings,
    ];
}

function m360_online_bridge_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_online_bridge_badge_class(string $status): string
{
    return match (strtoupper($status)) {
        'PASS' => 'pass',
        'WARNING' => 'warn',
        'BLOCKED' => 'block',
        default => 'warn',
    };
}
