<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
function p360_rule_set_active($conn, string $code): ?array {
    return p360_one($conn, 'SELECT TOP 1 * FROM dbo.p360_rule_sets WHERE rule_set_code=? AND is_active=1', [$code]);
}
function p360_rule_eval_json(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $d = json_decode($json, true);
    return is_array($d) ? $d : [];
}