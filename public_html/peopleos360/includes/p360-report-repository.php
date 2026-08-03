<?php
declare(strict_types=1);
require_once __DIR__ . '/p360-db.php';
function p360_report_headcount($conn): int { return (int)(p360_scalar($conn, 'SELECT COUNT(*) FROM dbo.p360_employees WHERE is_active=1', []) ?? 0); }
function p360_report_open_leaves($conn): int { return (int)(p360_scalar($conn, "SELECT COUNT(*) FROM dbo.p360_leave_requests WHERE request_status=N'submitted'", []) ?? 0); }
function p360_report_pending_workflows($conn): int { return (int)(p360_scalar($conn, "SELECT COUNT(*) FROM dbo.p360_workflow_tasks WHERE task_status=N'pending'", []) ?? 0); }