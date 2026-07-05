<?php
declare(strict_types=1);

/**
 * PR-01C — Live OTP connectivity + send diagnostic (CLI, safe output only).
 *
 * Usage:
 *   php tools/diagnose-pr-01c-otp-live-fix.php --mobile=09128166648 --mode=connectivity
 *   php tools/diagnose-pr-01c-otp-live-fix.php --mobile=09128166648 --mode=send
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

ob_start();

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

$mobile = '09128166648';
$mode = 'connectivity';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--mobile=')) {
        $mobile = trim(substr($arg, 9));
    }
    if (str_starts_with($arg, '--mode=')) {
        $mode = trim(substr($arg, 7));
    }
}

function pr01c_line(string $label, string $value): void
{
    echo $label . ': ' . $value . PHP_EOL;
}

function pr01c_curl_probe(string $url, bool $forceIpv4): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'http_status' => 0, 'curl_error_category' => 'curl_missing'];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'http_status' => 0, 'curl_error_category' => 'curl_init_failed'];
    }

    $options = [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_NOSIGNAL => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROXY => '',
    ];
    if ($forceIpv4 && defined('CURLOPT_IPRESOLVE')) {
        $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
    curl_setopt_array($ch, $options);
    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $category = '';
    if ($err !== '' || $status === 0) {
        $category = m360_otp_ippanel_safe_error_code($status, $err);
    }

    return [
        'ok' => $err === '' && $status > 0,
        'http_status' => $status,
        'curl_error_category' => $category,
    ];
}

$repoPrivate = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';
$xamppPrivate = 'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php';

echo "MOGHARE360 PR-01C OTP Live Fix Diagnostic\n";
echo str_repeat('-', 60) . PHP_EOL;
pr01c_line('mode', $mode);
pr01c_line('mobile', $mobile);

$dnsOk = false;
if (function_exists('gethostbynamel')) {
    $hosts = gethostbynamel('edge.ippanel.com');
    $dnsOk = is_array($hosts) && $hosts !== [];
}
pr01c_line('dns_ok', $dnsOk ? 'yes' : 'no');

$tcpOk = false;
if (function_exists('fsockopen')) {
    $fp = @fsockopen('ssl://edge.ippanel.com', 443, $errno, $errstr, 8);
    if (is_resource($fp)) {
        $tcpOk = true;
        fclose($fp);
    }
}
pr01c_line('tcp_443_ok', $tcpOk ? 'yes' : 'no');

$curlDefault = pr01c_curl_probe('https://edge.ippanel.com/', false);
$curlIpv4 = pr01c_curl_probe('https://edge.ippanel.com/', true);
pr01c_line('curl_default_connect', !empty($curlDefault['ok']) ? 'yes' : 'no');
pr01c_line('curl_ipv4_connect', !empty($curlIpv4['ok']) ? 'yes' : 'no');
pr01c_line('curl_default_error', (string)($curlDefault['curl_error_category'] ?? ''));
pr01c_line('curl_ipv4_error', (string)($curlIpv4['curl_error_category'] ?? ''));

$configReport = m360_otp_config_source_report();
pr01c_line('config_loaded_labels', json_encode($configReport['loaded_private_labels'] ?? [], JSON_UNESCAPED_UNICODE));
pr01c_line('sms_configured', !empty($configReport['sms_configured']) ? 'yes' : 'no');
pr01c_line('repo_private_exists', is_file($repoPrivate) ? 'yes' : 'no');
pr01c_line('xampp_private_exists', is_file($xamppPrivate) ? 'yes' : 'no');
$repoFp = m360_otp_config_safe_fingerprint($repoPrivate);
$xamppFp = m360_otp_config_safe_fingerprint($xamppPrivate);
pr01c_line('repo_xampp_fingerprint_match', ($repoFp !== '' && $repoFp === $xamppFp) ? 'yes' : 'no');

if ($mode === 'send') {
    $send = m360_otp_send($mobile);
    pr01c_line('send_attempt_success', !empty($send['ok']) ? 'yes' : 'no');
    pr01c_line('http_status', 'n/a');
    pr01c_line('safe_error_code', (string)($send['error_code'] ?? ($send['ok'] ? 'OK' : 'UNKNOWN')));
    if (!empty($send['message'])) {
        pr01c_line('message', (string)$send['message']);
    }
}

$browserUrl = 'http://localhost:8080/moghare360/api/customer/send-otp.php';
if ($mode === 'send' && function_exists('curl_init')) {
    $payload = json_encode(['phone' => $mobile], JSON_UNESCAPED_UNICODE);
    $ch = curl_init($browserUrl);
    if ($ch !== false) {
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 30,
        ]);
        if (defined('CURLOPT_IPRESOLVE')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        pr01c_line('browser_endpoint_ok', is_array($decoded) && !empty($decoded['ok']) ? 'yes' : 'no');
        pr01c_line('browser_endpoint_http_status', (string)$status);
        if (is_array($decoded)) {
            $errCode = (string)($decoded['data']['error_code'] ?? 'UNKNOWN');
            pr01c_line('browser_endpoint_error_code', $errCode);
        }
    }
}

echo str_repeat('-', 60) . PHP_EOL;
if (!$dnsOk) {
    pr01c_line('decision', 'DNS_FAIL');
} elseif (!$tcpOk) {
    pr01c_line('decision', 'HOST_NETWORK_BLOCKER_CONFIRMED');
} elseif ($mode === 'send' && empty($send['ok'])) {
    pr01c_line('decision', (string)($send['error_code'] ?? 'PROVIDER_TIMEOUT'));
} elseif (!empty($curlIpv4['ok']) && empty($curlDefault['ok'])) {
    pr01c_line('decision', 'OTP_FIXED_BY_FORCE_IPV4');
} else {
    pr01c_line('decision', 'CONNECTIVITY_CHECKED');
}
echo "No secret or OTP code printed.\n";

ob_end_clean();

exit(($mode === 'send' && !empty($send['ok'])) || ($tcpOk && $dnsOk) ? 0 : 1);
