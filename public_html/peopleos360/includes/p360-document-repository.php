<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
function p360_employee_documents($conn, int $employeeId): array {
    return p360_rows($conn, 'SELECT * FROM dbo.p360_employee_documents WHERE employee_id=? ORDER BY id DESC', [$employeeId]);
}
function p360_document_add($conn, int $employeeId, string $title, ?string $type, ?string $ref): array {
    p360_exec($conn, 'INSERT INTO dbo.p360_employee_documents (employee_id, doc_title, doc_type, file_ref) VALUES (?,?,?,?)', [$employeeId, $title, $type, $ref]);
    return ['ok' => true];
}
function p360_exit_create_simple($conn, int $employeeId, string $type, string $exitDate, int $uid = 0): array {
    require_once __DIR__ . '/p360-equipment-repository.php';
    return p360_exit_create($conn, ['employee_id' => $employeeId, 'exit_type' => $type, 'exit_date' => $exitDate], $uid);
}
function p360_announcements_list($conn): array { return p360_rows($conn, 'SELECT * FROM dbo.p360_announcements ORDER BY id DESC', []); }
function p360_announcement_add($conn, string $title, string $body, int $uid): array {
    p360_exec($conn, 'INSERT INTO dbo.p360_announcements (title, body_text, created_by) VALUES (?,?,?)', [$title, $body, $uid]);
    return ['ok' => true];
}