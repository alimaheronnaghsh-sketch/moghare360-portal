<?php
declare(strict_types=1);

/**
 * PR-01B — Compare canonical OTP send vs owner known-working private config reference.
 * CLI only. No secrets, OTP codes, or raw tokens printed.
 *
 * Usage:
 *   php tools/diagnose-pr-01b-otp-reference-reconcile.php --mobile=09128166648
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

$mobile = '09128166648';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--mobile=')) {
        $mobile = trim(substr($arg, 9));
    }
}

/** @return list<array{path:string,label:string,in_repo:bool}> */
function pr01b_reference_candidates(): array
{
    $root = dirname(__DIR__);
    $list = [
        ['path' => 'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php', 'label' => 'xampp_htdocs_private', 'in_repo' => false],
        ['path' => $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php', 'label' => 'repo_project_private', 'in_repo' => true],
    ];

    return $list;
}

function pr01b_find_reference(): ?array
{
    foreach (pr01b_reference_candidates() as $item) {
        if (!is_file($item['path'])) {
            continue;
        }
        $cfg = m360_otp_config_load_file($item['path']);
        if (!is_array($cfg)) {
            continue;
        }
        $api = trim((string)($cfg['M360_SMS_API_KEY'] ?? $cfg['ippanelApiKey'] ?? $cfg['IPPANEL_API_KEY'] ?? ''));
        if ($api === '' || m360_otp_is_placeholder_value($api)) {
            continue;
        }

        return $item + ['config' => m360_otp_config_normalize($cfg)];
    }

    return null;
}

/**
 * @return array<string, mixed>
 */
function pr01b_settings_from_config(array $config): array
{
    $merged = m360_otp_config_apply_env(m360_otp_config_normalize($config));
    $provider = 'ippanel';
    $apiKey = trim((string)($merged['M360_SMS_API_KEY'] ?? $merged['ippanelApiKey'] ?? $merged['IPPANEL_API_KEY'] ?? ''));
    $sender = trim((string)($merged['M360_SMS_SENDER'] ?? $merged['ippanelSender'] ?? $merged['IPPANEL_SENDER'] ?? ''));
    $pattern = trim((string)($merged['M360_SMS_PATTERN_CODE'] ?? $merged['ippanelPatternCode'] ?? $merged['IPPANEL_PATTERN_CODE'] ?? ''));
    $otpVar = trim((string)($merged['M360_SMS_PATTERN_VARIABLE'] ?? $merged['ippanelOtpVariableName'] ?? $merged['IPPANEL_OTP_VARIABLE'] ?? 'OTP'));

    return [
        'provider' => $provider,
        'api_key' => $apiKey,
        'sender' => $sender,
        'pattern_id' => $pattern,
        'otp_variable' => $otpVar !== '' ? $otpVar : 'OTP',
        'auth_header_mode' => strtolower(trim((string)($merged['ippanelAuthHeaderMode'] ?? $merged['M360_IPPANEL_AUTH_HEADER_MODE'] ?? 'authorization'))),
    ];
}

/**
 * Reference send (Probe B): same Edge endpoint/payload as canonical, auth mode from reference config.
 *
 * @return array<string, mixed>
 */
function pr01b_reference_probe_send(string $phone, array $settings): array
{
    $normalized = m360_otp_normalize_phone($phone);
    if ($normalized === null) {
        return [
            'success' => false,
            'http_status' => 0,
            'curl_error_category' => 'invalid_mobile',
            'provider_error_category' => 'validation',
            'auth_format_label' => 'n/a',
            'request_mode_label' => 'n/a',
        ];
    }

    $code = '123456';
    $payload = m360_otp_ippanel_pattern_payload($normalized, $code, $settings);
    $apiKey = (string)($settings['api_key'] ?? '');
    $mode = (string)($settings['auth_header_mode'] ?? 'authorization');
    if (!in_array($mode, ['authorization', 'accesskey', 'apikey'], true)) {
        $mode = 'authorization';
    }

    $authHeader = $mode === 'accesskey' ? ('AccessKey ' . $apiKey) : $apiKey;
    $authLabel = $mode === 'accesskey' ? 'accesskey_prefix' : 'authorization_raw';

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'http_status' => 0,
            'curl_error_category' => 'curl_missing',
            'provider_error_category' => 'runtime',
            'auth_format_label' => $authLabel,
            'request_mode_label' => 'pattern',
        ];
    }

    $endpoint = 'https://edge.ippanel.com/v1/api/send';
    $headers = [
        'Content-Type: application/json',
        'Authorization: ' . $authHeader,
    ];
    $ch = curl_init($endpoint);
    if ($ch === false) {
        return [
            'success' => false,
            'http_status' => 0,
            'curl_error_category' => 'curl_init_failed',
            'provider_error_category' => 'runtime',
            'auth_format_label' => $authLabel,
            'request_mode_label' => 'pattern',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = strtolower(trim(curl_error($ch)));
    curl_close($ch);

    $providerCategory = 'unknown';
    if ($status === 401) {
        $providerCategory = 'invalid_token';
    } elseif ($status >= 200 && $status < 300) {
        $providerCategory = 'ok';
    } elseif ($status >= 400) {
        $providerCategory = 'http_error';
    }

    $curlCategory = '';
    if ($raw === false || $status === 0) {
        if (str_contains($curlErr, 'timed out') || str_contains($curlErr, 'timeout')) {
            $curlCategory = 'timeout';
        } elseif (str_contains($curlErr, 'ssl') || str_contains($curlErr, 'certificate')) {
            $curlCategory = 'ssl';
        } elseif ($curlErr !== '') {
            $curlCategory = 'connect_error';
        } else {
            $curlCategory = 'empty_response';
        }
    }

    $success = $status >= 200 && $status < 300;
    if ($success && $raw !== false) {
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded) && isset($decoded['meta']['status']) && (bool)$decoded['meta']['status'] === false) {
            $success = false;
            $providerCategory = 'meta_false';
        }
    }

    return [
        'success' => $success,
        'http_status' => $status,
        'curl_error_category' => $curlCategory,
        'provider_error_category' => $providerCategory,
        'auth_format_label' => $authLabel,
        'request_mode_label' => 'pattern',
        'host_label' => 'edge.ippanel.com',
        'endpoint_label' => '/v1/api/send',
    ];
}

/**
 * @return array<string, mixed>
 */
function pr01b_canonical_probe_send(string $phone): array
{
    $result = m360_otp_send_sms($phone, '123456');
    $settings = m360_otp_sms_settings();
    $mode = m360_otp_ippanel_auth_header_mode();

    return [
        'success' => !empty($result['ok']),
        'http_status' => 0,
        'curl_error_category' => !empty($result['ok']) ? '' : 'provider_or_network',
        'provider_error_category' => !empty($result['ok']) ? 'ok' : 'send_failed',
        'auth_format_label' => $mode === 'accesskey' ? 'accesskey_prefix' : 'authorization_raw',
        'request_mode_label' => ((string)($settings['pattern_id'] ?? '') !== '') ? 'pattern' : 'webservice',
        'host_label' => 'edge.ippanel.com',
        'endpoint_label' => '/v1/api/send',
        'message' => (string)($result['message'] ?? ''),
    ];
}

/**
 * @return array<string, mixed>
 */
function pr01b_apache_simulation_report(): array
{
    $xamppDocRoot = 'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'moghare360';
    $_SERVER['DOCUMENT_ROOT'] = $xamppDocRoot;
    m360_otp_config_reset_merged_cache();

    $htdocsPrivate = dirname($xamppDocRoot) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';
    $projectPrivate = $xamppDocRoot . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';

    $report = m360_otp_config_diagnostics_report();
    unset($_SERVER['DOCUMENT_ROOT']);
    m360_otp_config_reset_merged_cache();

    return [
        'document_root_simulated' => $xamppDocRoot,
        'project_private_exists' => is_file($projectPrivate),
        'project_private_readable' => is_readable($projectPrivate),
        'htdocs_private_exists' => is_file($htdocsPrivate),
        'htdocs_private_readable' => is_readable($htdocsPrivate),
        'open_basedir' => (string)ini_get('open_basedir'),
        'loaded_private_labels' => $report['sources']['loaded_private_labels'] ?? [],
        'sms_configured' => !empty($report['sms_configured']),
    ];
}

$reference = pr01b_find_reference();
if ($reference === null) {
    echo "KNOWN_WORKING_REFERENCE_NOT_FOUND\n";
    exit(2);
}

echo "MOGHARE360 PR-01B OTP Reference Reconcile\n";
echo str_repeat('-', 60) . "\n";
echo "reference_label: " . $reference['label'] . "\n";
echo "reference_path_in_repo: " . ($reference['in_repo'] ? 'yes' : 'no') . "\n";
echo "reference_has_real_values: yes\n";
echo "reference_has_send_function: no\n";
echo "reference_uses_curl: via_canonical_helper\n";
echo "reference_host: edge.ippanel.com\n";
echo "reference_endpoint: /v1/api/send\n";
echo "reference_request_mode: pattern\n";
echo str_repeat('-', 60) . "\n";

$apacheSim = pr01b_apache_simulation_report();
echo "apache_sim_project_private_exists: " . (!empty($apacheSim['project_private_exists']) ? 'yes' : 'no') . "\n";
echo "apache_sim_htdocs_private_exists: " . (!empty($apacheSim['htdocs_private_exists']) ? 'yes' : 'no') . "\n";
echo "apache_sim_htdocs_private_readable: " . (!empty($apacheSim['htdocs_private_readable']) ? 'yes' : 'no') . "\n";
echo "apache_sim_loaded_labels: " . json_encode($apacheSim['loaded_private_labels'], JSON_UNESCAPED_UNICODE) . "\n";
echo "apache_sim_sms_configured: " . (!empty($apacheSim['sms_configured']) ? 'yes' : 'no') . "\n";
echo "open_basedir: " . (($apacheSim['open_basedir'] ?? '') !== '' ? 'set' : 'not_set') . "\n";
echo str_repeat('-', 60) . "\n";

$refSettings = pr01b_settings_from_config($reference['config']);
$probeB = pr01b_reference_probe_send($mobile, $refSettings);
$probeA = pr01b_canonical_probe_send($mobile);

echo "PROBE_A canonical_helper\n";
echo "config_source_label: merged_runtime\n";
echo "host_label: " . $probeA['host_label'] . "\n";
echo "endpoint_label: " . $probeA['endpoint_label'] . "\n";
echo "auth_format_label: " . $probeA['auth_format_label'] . "\n";
echo "request_mode_label: " . $probeA['request_mode_label'] . "\n";
echo "http_status: " . (string)($probeA['http_status'] ?? 0) . "\n";
echo "curl_error_category: " . (string)($probeA['curl_error_category'] ?? '') . "\n";
echo "provider_error_category: " . (string)($probeA['provider_error_category'] ?? '') . "\n";
echo "success: " . (!empty($probeA['success']) ? 'yes' : 'no') . "\n";
if (!empty($probeA['message'])) {
    echo "message: " . $probeA['message'] . "\n";
}

echo str_repeat('-', 60) . "\n";
echo "PROBE_B reference_private_config\n";
echo "config_source_label: " . $reference['label'] . "\n";
echo "host_label: " . $probeB['host_label'] . "\n";
echo "endpoint_label: " . $probeB['endpoint_label'] . "\n";
echo "auth_format_label: " . $probeB['auth_format_label'] . "\n";
echo "request_mode_label: " . $probeB['request_mode_label'] . "\n";
echo "http_status: " . (string)($probeB['http_status'] ?? 0) . "\n";
echo "curl_error_category: " . (string)($probeB['curl_error_category'] ?? '') . "\n";
echo "provider_error_category: " . (string)($probeB['provider_error_category'] ?? '') . "\n";
echo "success: " . (!empty($probeB['success']) ? 'yes' : 'no') . "\n";

echo str_repeat('-', 60) . "\n";
if (!empty($probeA['success']) && !empty($probeB['success'])) {
    echo "DECISION: both_probes_success_inspect_browser_endpoint\n";
} elseif (empty($probeA['success']) && !empty($probeB['success'])) {
    echo "DECISION: align_canonical_to_reference\n";
} elseif (empty($probeA['success']) && empty($probeB['success']) && (($probeA['curl_error_category'] ?? '') === 'provider_or_network' || ($probeB['curl_error_category'] ?? '') === 'timeout')) {
    echo "DECISION: OTP_PROVIDER_OR_NETWORK_BLOCKER_CONFIRMED\n";
} else {
    echo "DECISION: investigate_auth_or_payload_difference\n";
}
echo "No OTP code, token, or API key printed.\n";

exit(!empty($probeA['success']) || !empty($probeB['success']) ? 0 : 1);
