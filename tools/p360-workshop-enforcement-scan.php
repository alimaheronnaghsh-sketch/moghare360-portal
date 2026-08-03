<?php
declare(strict_types=1);

/**
 * CLI-only workshop R0B enforcement scanner.
 * Writes evidence OUTSIDE Git (tools/_generated/ — gitignored via path convention).
 *
 * Usage: php tools/p360-workshop-enforcement-scan.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/public_html/includes/m360-access-matrix-catalog.php';

$defs = array_values(array_filter(
    m360_access_matrix_catalog_definitions(),
    static fn(array $r): bool => ($r['module_key'] ?? '') === 'workshop'
));

$scanRoots = [
    $root . '/public_html',
];

function ws_scan_file_mentions(string $file, string $key): array
{
    $src = @file_get_contents($file);
    if ($src === false || $src === '') {
        return ['get' => 0, 'post' => 0, 'guard' => 0, 'object' => 0, 'company' => 0];
    }
    $guard = preg_match_all('/m360_(?:am_guard(?:_any|_action)?|ws_require(?:_any)?)\s*\(/', $src)
        + substr_count($src, $key);
    $hasKey = str_contains($src, $key) || str_contains($src, 'm360_ws_') || str_contains($src, 'm360_am_guard');
    $isGet = !str_contains(strtolower(basename($file)), 'action') && !str_contains($file, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR);
    $isPost = str_contains(strtolower(basename($file)), 'action')
        || str_contains($file, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR)
        || str_contains($src, "REQUEST_METHOD") && str_contains($src, 'POST');
    return [
        'get' => ($hasKey && $isGet) ? 1 : 0,
        'post' => ($hasKey && $isPost) ? 1 : 0,
        'guard' => $guard > 0 && (str_contains($src, $key) || str_contains($src, 'm360_ws_')) ? 1 : 0,
        'object' => str_contains($src, 'm360_ws_assert_jobcard_object_scope')
            || str_contains($src, 'm360_ws_object_scope_not_applicable')
            || str_contains($src, 'm360_ws_require') && str_contains($src, 'jobcard')
            || str_contains($src, 'm360_ws_jobcard_company_sql') ? 1 : 0,
        'company' => str_contains($src, 'm360_ws_require_actor_context')
            || str_contains($src, 'erp_company_users')
            || str_contains($src, 'm360_ws_jobcard_company_sql')
            || str_contains($src, 'company_id') && (str_contains($src, 'm360_ws_') || str_contains($src, 'j.company_id'))
            || str_contains($src, 'm360_ws_assert_jobcard_object_scope')
            || str_contains($src, 'm360_ws_require') ? 1 : 0,
    ];
}

$outDir = $root . '/tools/_generated';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
$outFile = $outDir . '/workshop-enforcement-scan-' . date('Ymd-His') . '.tsv';
$lines = ["permission_key\ttitle_fa\troute_count\tget_guarded\tpost_api_guarded\tobject_scope\tcompany_scope\tenforcement_state\tuncovered_note"];

foreach ($defs as $d) {
    $key = (string)$d['permission_key'];
    $routes = $d['route_patterns'] ?? [];
    $get = 0;
    $post = 0;
    $object = 0;
    $company = 0;
    $missing = [];
    foreach ($routes as $route) {
        $path = $root . '/public_html/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $route);
        if (!is_file($path)) {
            $missing[] = $route . '(missing)';
            continue;
        }
        $m = ws_scan_file_mentions($path, $key);
        $get += $m['get'];
        $post += $m['post'];
        $object = max($object, $m['object']);
        $company = max($company, $m['company']);
        if ($m['guard'] < 1 && (string)$d['enforcement_state'] === 'ENFORCED') {
            // Broader: search for any m360_ws / m360_am_guard in file
            $src = (string)@file_get_contents($path);
            if (!str_contains($src, 'm360_ws_') && !str_contains($src, 'm360_am_guard')) {
                $missing[] = $route . '(unguarded)';
            }
        }
    }
    $lines[] = implode("\t", [
        $key,
        (string)$d['title_fa'],
        (string)count($routes),
        (string)$get,
        (string)$post,
        $object ? 'yes' : 'partial',
        $company ? 'yes' : 'partial',
        (string)$d['enforcement_state'],
        $missing === [] ? '' : implode(',', $missing),
    ]);
}

file_put_contents($outFile, implode(PHP_EOL, $lines) . PHP_EOL);
echo "WROTE=$outFile\n";
echo "WORKSHOP_KEYS=" . count($defs) . "\n";
$enf = 0;
$nye = 0;
foreach ($defs as $d) {
    if (($d['enforcement_state'] ?? '') === 'ENFORCED') {
        $enf++;
    } else {
        $nye++;
    }
}
echo "ENFORCED=$enf\nNOT_YET=$nye\n";
