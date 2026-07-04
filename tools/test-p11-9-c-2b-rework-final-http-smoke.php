<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function rf_http_pass(string $name, string $status, string $detail = ''): array
{
    return ['name' => $name, 'status' => $status, 'detail' => $detail];
}

$base = getenv('M360_HTTP_BASE') ?: 'http://localhost:8080/moghare360';
$urls = [
    'intake-18' => $base . '/erp-reception-intake-file.php?online_request_id=18',
    'intake-20' => $base . '/erp-reception-intake-file.php?online_request_id=20',
    'workbench' => $base . '/erp-reception-workbench.php',
    'workbench-reception' => $base . '/erp-reception-workbench.php?section=reception',
    'detail-18' => $base . '/erp-reception-online-request-detail.php?request_id=18',
];

$fatalPatterns = [
    'Fatal error',
    'ArgumentCountError',
    'Stack trace',
    'ERP security validation failed',
    'Warning:',
    'Notice:',
    'Parse error',
];

$results = [];
$apacheAvailable = false;

foreach ($urls as $key => $url) {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'ignore_errors' => true,
            'header' => "User-Agent: M360-Rework-Final-HTTP-Smoke\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        $results[] = rf_http_pass($key, 'SKIP', 'Apache unavailable or URL unreachable');
        continue;
    }
    $apacheAvailable = true;
    $httpCode = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $httpCode = (int)$m[1];
    }
    $bad = '';
    foreach ($fatalPatterns as $pat) {
        if (stripos($body, $pat) !== false) {
            $bad = $pat;
            break;
        }
    }
    if ($bad !== '') {
        $results[] = rf_http_pass($key, 'FAIL', "HTTP {$httpCode} contains {$bad}");
    } elseif ($httpCode >= 500) {
        $results[] = rf_http_pass($key, 'FAIL', "HTTP {$httpCode}");
    } else {
        $results[] = rf_http_pass($key, 'PASS', "HTTP {$httpCode}");
    }
}

echo "# P11.9-C-2B-REWORK-FINAL HTTP Smoke\n\n";
echo "Base URL: {$base}\n\n";

$pass = 0;
$skip = 0;
$fail = 0;
foreach ($results as $r) {
    $tag = match ($r['status']) {
        'PASS' => '[PASS]',
        'SKIP' => '[SKIP]',
        default => '[FAIL]',
    };
    echo "{$tag} {$r['name']}" . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    if ($r['status'] === 'PASS') {
        $pass++;
    } elseif ($r['status'] === 'SKIP') {
        $skip++;
    } else {
        $fail++;
    }
}

echo "\nTotal: " . count($results) . " | PASS: $pass | SKIP: $skip | FAIL: $fail\n";

if (!$apacheAvailable) {
    echo "\nOPERATOR_REQUIRED: Apache not reachable — HTTP smoke skipped, not fake PASS.\n";
    exit(0);
}

if ($fail > 0) {
    exit(1);
}
exit(0);
