<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "CLI-only\n";
    exit(1);
}
require __DIR__ . '/../public_html/includes/m360-access-matrix-catalog.php';
$d = m360_access_matrix_catalog_definitions();
$pages = [];
$acts = [];
foreach ($d as $r) {
    $pages[$r['module_key'] . '|' . $r['page_key']] = 1;
    $acts[$r['action_key']] = ($acts[$r['action_key']] ?? 0) + 1;
}
echo 'catalog_defs=' . count($d) . "\n";
echo 'page_task_groups=' . count($pages) . "\n";
echo 'modules=' . count(m360_access_matrix_module_tabs()) . "\n";
echo 'base=' . count(m360_access_matrix_base_permission_keys()) . "\n";
foreach ($acts as $k => $v) {
    echo $k . '=' . $v . "\n";
}
