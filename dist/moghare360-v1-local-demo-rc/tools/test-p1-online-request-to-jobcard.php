<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Online request to JobCard conversion tests.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p1j_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p1j_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

$receptionHelper = p1j_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php');
$onlineHelper = p1j_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php');
$migration = p1j_read($root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . 'P1_online_request_intake.sql');
$acceptPage = p1j_read($public . DIRECTORY_SEPARATOR . 'erp-reception-online-request-accept.php');

$results = [];

$results[] = p1j_pass('Convert function exists', str_contains($receptionHelper, 'function m360_reception_convert_to_jobcard'));
$results[] = p1j_pass('Idempotent converted check', str_contains($receptionHelper, 'already_converted') && str_contains($receptionHelper, 'm360_online_req_is_converted'));
$results[] = p1j_pass('Uses existing jobcard v2 write', str_contains($receptionHelper, 'moghare360_jobcard_v2_write'));
$results[] = p1j_pass('Binds customer_id on convert', str_contains($receptionHelper, 'm360_reception_bind_request_entities'));
$results[] = p1j_pass('Ensures customer vehicle relation', str_contains($receptionHelper, 'm360_reception_ensure_relation'));
$results[] = p1j_pass('Status CONVERTED_TO_JOBCARD update', str_contains($receptionHelper, 'M360_ONLINE_REQ_STATUS_CONVERTED'));
$results[] = p1j_pass('Stores converted_jobcard_id when column exists', str_contains($receptionHelper, 'converted_jobcard_id'));
$results[] = p1j_pass('History on convert', str_contains($receptionHelper, 'M360_ONLINE_REQ_HISTORY_CONVERTED'));
$results[] = p1j_pass('OTP verified required for convert', str_contains($receptionHelper, 'm360_online_req_payload_otp_verified'));
$results[] = p1j_pass('Reject blocks convert', str_contains($receptionHelper, 'M360_ONLINE_REQ_STATUS_REJECTED'));
$results[] = p1j_pass('Accept handler wires convert action', str_contains($acceptPage, 'convert_to_jobcard') && str_contains($acceptPage, 'm360_reception_convert_to_jobcard'));
$results[] = p1j_pass('is_converted helper checks jobcard id', str_contains($onlineHelper, 'function m360_online_req_is_converted'));
$results[] = p1j_pass('No SQL DROP in migration', !preg_match('/\bDROP\s+(TABLE|COLUMN)\b/i', $migration));
$results[] = p1j_pass('No DELETE in reception helper', !preg_match('/\bDELETE\s+FROM\b/i', $receptionHelper));
$results[] = p1j_pass('JobCard detail link in detail page', str_contains(p1j_read($public . DIRECTORY_SEPARATOR . 'erp-reception-online-request-detail.php'), 'erp-jobcard-detail.php?jobcard_id='));

require_once $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
$convertedRow = ['request_status' => M360_ONLINE_REQ_STATUS_CONVERTED, 'converted_jobcard_id' => '42'];
$results[] = p1j_pass('Runtime is_converted true for converted row', m360_online_req_is_converted($convertedRow));
$results[] = p1j_pass('Runtime converted jobcard id', m360_online_req_converted_jobcard_id($convertedRow) === 42);

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P1 Online Request to JobCard Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
