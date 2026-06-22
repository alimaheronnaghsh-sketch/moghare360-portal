<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-business-ready-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    br_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('br_kpi_snapshot', $_POST['erp_csrf_token'] ?? null);

$connection = false;
try {
    $connection = business_ready_db();
    if ($connection === false) throw new RuntimeException('اتصال برقرار نشد.');
    br_require_auth($connection, 'business.ready.snapshot');
    $kpi = business_ready_get_kpi_summary($connection);
    $note = trim((string)($_POST['snapshot_note'] ?? ''));
    $id = business_ready_insert_kpi_snapshot($connection, $kpi, $note);
    if ($id === null) throw new RuntimeException('ثبت snapshot انجام نشد.');
} catch (Throwable) {
    br_error('خطا', 'ثبت snapshot KPI انجام نشد.');
} finally {
    if ($connection !== false) @odbc_close($connection);
}

br_safe_redirect('erp-kpi-report.php?ok=snapshot_ok');
