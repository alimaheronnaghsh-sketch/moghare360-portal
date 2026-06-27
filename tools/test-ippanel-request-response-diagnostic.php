<?php
declare(strict_types=1);

/**
 * MOGHARE360 — IPPanel request/response diagnostic (CLI only).
 *
 * Usage:
 *   php tools/test-ippanel-request-response-diagnostic.php --mobile=09XXXXXXXXX --otp=123456
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';

/**
 * @return array{mobile:string,otp:string}
 */
function ippanel_diag_parse_args(array $argv): array
{
    $mobile = '';
    $otp = '';
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--mobile=')) {
            $mobile = trim(substr($arg, 9));
        } elseif (str_starts_with($arg, '--otp=')) {
            $otp = trim(substr($arg, 6));
        }
    }

    return ['mobile' => $mobile, 'otp' => $otp];
}

function ippanel_diag_print_line(string $label, string $value): void
{
    echo $label . ': ' . $value . "\n";
}

/**
 * @param array<string, mixed> $trace
 */
function ippanel_diag_print_trace(array $trace, bool $resultOk, string $resultMessage): void
{
    echo "MOGHARE360 IPPanel Request/Response Diagnostic\n";
    echo str_repeat('=', 72) . "\n";
    ippanel_diag_print_line('result_ok', $resultOk ? 'true' : 'false');
    ippanel_diag_print_line('result_message', $resultMessage);
    ippanel_diag_print_line('timestamp', (string)($trace['timestamp'] ?? gmdate('c')));
    ippanel_diag_print_line('provider', (string)($trace['provider'] ?? 'ippanel'));
    ippanel_diag_print_line('endpoint', (string)($trace['endpoint'] ?? ''));
    ippanel_diag_print_line('method', (string)($trace['method'] ?? 'POST'));
    ippanel_diag_print_line('http_status', (string)($trace['http_status'] ?? '0'));

    echo "headers:\n";
    foreach (($trace['headers'] ?? []) as $header) {
        echo '  - ' . (string)$header . "\n";
    }

    echo "request_body:\n";
    echo json_encode($trace['request_body'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

    echo "response_body:\n";
    $response = (string)($trace['response_body'] ?? '');
    if ($response === '') {
        echo "(empty)\n";
    } else {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            echo json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $response . "\n";
        }
    }

    $curlErr = trim((string)($trace['curl_error'] ?? ''));
    ippanel_diag_print_line('curl_error', $curlErr !== '' ? $curlErr : '(none)');
    ippanel_diag_print_line('log_path', m360_otp_ippanel_debug_log_path());
    echo str_repeat('=', 72) . "\n";
}

$args = ippanel_diag_parse_args($argv);
$mobile = $args['mobile'];
$otp = preg_replace('/\D+/', '', $args['otp']) ?? '';

if ($mobile === '' || $otp === '') {
    fwrite(STDERR, "Usage: php tools/test-ippanel-request-response-diagnostic.php --mobile=09XXXXXXXXX --otp=123456\n");
    exit(1);
}

if (strlen($otp) !== 6) {
    fwrite(STDERR, "OTP must be exactly 6 digits.\n");
    exit(1);
}

$normalized = m360_otp_normalize_phone($mobile);
if ($normalized === null) {
    fwrite(STDERR, "Invalid mobile format. Use 09xxxxxxxxx.\n");
    exit(1);
}

if (!m360_otp_sms_configured()) {
    fwrite(STDERR, "IPPanel SMS is not configured. Set private/m360-otp-config.php or environment variables.\n");
    exit(1);
}

$settings = m360_otp_sms_settings();
echo "Sending diagnostic OTP via central helper (provider=" . ($settings['provider'] ?? '') . ")\n";

$result = m360_otp_send_sms_diagnostic($normalized, $otp);
$trace = $result['trace'] ?? [];

if ($trace === []) {
    fwrite(STDERR, "No diagnostic trace captured. Ensure CLI mode and IPPanel provider are active.\n");
    exit(1);
}

ippanel_diag_print_trace($trace, (bool)($result['ok'] ?? false), (string)($result['message'] ?? ''));

if (!m360_otp_ippanel_debug_write_log($trace, $result)) {
    fwrite(STDERR, "Warning: could not write " . m360_otp_ippanel_debug_log_path() . "\n");
} else {
    echo "Log written: " . m360_otp_ippanel_debug_log_path() . "\n";
}

exit(($result['ok'] ?? false) ? 0 : 1);
