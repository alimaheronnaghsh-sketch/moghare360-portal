<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "CLI-only\n";
    exit(1);
}
$j = json_decode(file_get_contents(__DIR__ . '/access-matrix-route-discovery.json'), true);
$c = [];
foreach ($j as $r) {
    $cls = (string)($r['class'] ?? 'UNKNOWN');
    $c[$cls] = ($c[$cls] ?? 0) + 1;
}
ksort($c);
foreach ($c as $k => $v) {
    echo $k . '=' . $v . "\n";
}
echo 'TOTAL=' . count($j) . "\n";
