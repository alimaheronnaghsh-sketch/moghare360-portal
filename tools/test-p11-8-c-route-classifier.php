<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-route-operational-safety-helper.php';

function p118c_cls_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];

$results[] = p118c_cls_pass('classify function exists', function_exists('m360_route_ops_classify'));
$results[] = p118c_cls_pass('enrich function exists', function_exists('m360_route_ops_enrich_audit_rows'));
$results[] = p118c_cls_pass('action endpoint helper exists', function_exists('m360_route_ops_is_action_endpoint'));
$results[] = p118c_cls_pass('guided helper exists', function_exists('m360_route_ops_is_guided_detail'));

$allowed = [
    M360_ROUTE_OPS_CLASS_OPERATIONAL,
    M360_ROUTE_OPS_CLASS_GUIDED,
    M360_ROUTE_OPS_CLASS_ACTION,
    M360_ROUTE_OPS_CLASS_API,
    M360_ROUTE_OPS_CLASS_CUSTOMER,
    M360_ROUTE_OPS_CLASS_DIAGNOSTIC,
    M360_ROUTE_OPS_CLASS_RUNTIME_HOLD,
];

foreach (m360_nav_registry() as $route) {
    $route['file_exists'] = m360_nav_file_exists((string)$route['url']);
    $enriched = m360_route_ops_classify($route);
    $class = (string)($enriched['ops_class'] ?? '');
    $results[] = p118c_cls_pass('class assigned: ' . ($route['route_key'] ?? ''), in_array($class, $allowed, true), $class);
    $results[] = p118c_cls_pass('badge fa: ' . ($route['route_key'] ?? ''), ($enriched['ops_badge_fa'] ?? '') !== '');
    $results[] = p118c_cls_pass('reason fa: ' . ($route['route_key'] ?? ''), ($enriched['ops_reason_fa'] ?? '') !== '');
}

$results[] = p118c_cls_pass('accept is action endpoint', m360_route_ops_is_action_endpoint('erp-reception-online-request-accept.php'));
$results[] = p118c_cls_pass('contract generate is action', m360_route_ops_is_action_endpoint('erp-intake-contract-generate.php'));
$results[] = p118c_cls_pass('timeline is guided', m360_route_ops_is_guided_detail('erp-jobcard-timeline.php'));

$sample = m360_route_ops_classify([
    'url' => 'api/customer/request.php',
    'expected_method' => 'POST',
    'is_api' => true,
    'file_exists' => true,
]);
$results[] = p118c_cls_pass('api sample class', ($sample['ops_class'] ?? '') === M360_ROUTE_OPS_CLASS_API);

$pass = 0;
$fail = 0;
echo "# P11.8-C Route Classifier Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
