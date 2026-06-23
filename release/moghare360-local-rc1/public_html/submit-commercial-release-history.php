<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    cs_error('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('commercial_release_history', $_POST['erp_csrf_token'] ?? null);

$type = trim((string)($_POST['release_type'] ?? 'COMMERCIAL_DEMO'));
$title = trim((string)($_POST['release_title'] ?? ''));
$summary = trim((string)($_POST['release_summary'] ?? ''));

if ($title === '') cs_error('خطای اعتبارسنجی', 'عنوان الزامی است.');

$c = false;
try {
    $c = commercial_db();
    if ($c === false) throw new RuntimeException('اتصال برقرار نشد.');
    cs_require_auth($c, 'commercial.checklist.write');
    if (!commercial_insert_release_history($c, $type, $title, $summary, 'READY')) {
        throw new RuntimeException('ثبت release انجام نشد.');
    }
} catch (Throwable) {
    cs_error('خطا', 'ثبت release انجام نشد.');
} finally {
    if ($c !== false) @odbc_close($c);
}

commercial_safe_redirect('moghare360-commercial-checklist.php?ok=release_ok');
