<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11.3 — Secure online intake receive API (cPanel → laptop, HMAC).
 * No public debug. Controlled JSON for forwarder only.
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-intake-bridge-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    m360_online_bridge_json_response(false, 'METHOD_NOT_ALLOWED', 'Only POST is allowed.', 405);
}

$rawBody = m360_online_bridge_read_raw_body();
$verify = m360_online_bridge_verify_request($rawBody);
if (!$verify['ok']) {
    m360_online_bridge_write_log([
        'event' => 'request_rejected',
        'code' => $verify['code'],
    ]);
    m360_online_bridge_json_response(false, (string)$verify['code'], (string)$verify['message'], 403);
}

$validated = m360_online_bridge_validate_payload($verify['payload'] ?? []);
if (!$validated['ok']) {
    m360_online_bridge_write_log([
        'event' => 'payload_rejected',
        'code' => $validated['code'],
    ]);
    m360_online_bridge_json_response(false, (string)$validated['code'], (string)$validated['message'], 422);
}

$stored = m360_online_bridge_persist_intake($validated['fields'] ?? []);
if (!$stored['ok']) {
    m360_online_bridge_write_log([
        'event' => 'persistence_failed',
        'code' => $stored['code'],
        'mobile' => (string)($validated['fields']['mobile'] ?? ''),
    ]);
    m360_online_bridge_json_response(false, (string)$stored['code'], (string)$stored['message'], 500);
}

m360_online_bridge_write_log([
    'event' => 'intake_stored',
    'code' => $stored['code'],
    'online_request_id' => (int)($stored['online_request_id'] ?? 0),
    'status' => (string)($stored['status'] ?? ''),
    'mobile' => (string)($validated['fields']['mobile'] ?? ''),
]);

m360_online_bridge_json_response(true, 'STORED', 'Online request stored.', 201, [
    'online_request_id' => (int)($stored['online_request_id'] ?? 0),
    'status' => (string)($stored['status'] ?? M360_ONLINE_REQ_STATUS_NEW),
]);
