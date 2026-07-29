<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
require_once __DIR__ . '/p360-validation.php';
function p360_search_employees($conn, string $q, int $limit = 50): array {
    $q = trim($q);
    if ($q === '') return [];
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $limit = max(1, min(200, $limit));
    return p360_rows($conn, "SELECT TOP $limit employee_id, employee_code, first_name, last_name, mobile, email, employee_status FROM dbo.p360_employees WHERE is_active=1 AND (employee_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR national_code LIKE ? OR mobile LIKE ?) ORDER BY employee_id DESC", [$like, $like, $like, $like, $like]);
}