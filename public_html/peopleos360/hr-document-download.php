<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_central_login();
$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    echo 'یافت نشد.';
    exit;
}
$conn = p360hr_odbc();
$stmt = @odbc_prepare($conn, 'SELECT TOP 1 id, employee_id, storage_path, mime_type, doc_title, is_final FROM dbo.p360_employee_documents WHERE id=?');
if ($stmt === false || !@odbc_execute($stmt, [$id])) {
    http_response_code(404);
    echo 'یافت نشد.';
    exit;
}
$row = odbc_fetch_array($stmt);
if (!is_array($row)) {
    http_response_code(404);
    echo 'یافت نشد.';
    exit;
}
$employeeId = (int)($row['employee_id'] ?? $row['EMPLOYEE_ID'] ?? 0);
p360hr_assert_own_employee($employeeId);

$path = (string)($row['storage_path'] ?? $row['STORAGE_PATH'] ?? '');
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    echo 'فایل در دسترس نیست.';
    exit;
}
$mime = (string)($row['mime_type'] ?? $row['MIME_TYPE'] ?? 'application/octet-stream');
$title = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', (string)($row['doc_title'] ?? 'document')) ?: 'document';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . rawurlencode($title) . '"');
header('Cache-Control: private, no-store');
readfile($path);
exit;
