<?php
declare(strict_types=1);

/**
 * LEGACY — redirect to canonical customer contract review.
 * Do not delete yet; keep for old bookmarks/SMS links.
 */
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-canonical-host-helper.php';
m360_canonical_local_host_enforce();

$token = trim((string)($_GET['token'] ?? $_GET['t'] ?? ''));
$taskId = (int)($_GET['task_id'] ?? 0);

if ($taskId > 0) {
    header('Location: customer-intake-contract-review.php?task_id=' . $taskId . '&legacy=1', true, 302);
    exit;
}
if ($token !== '') {
    header('Location: customer-intake-contract-review.php?t=' . rawurlencode($token) . '&legacy=1', true, 302);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
http_response_code(410);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مسیر قدیمی قرارداد</title>
    <link rel="stylesheet" href="assets/css/moghare360-v1-luxury-ui.css">
</head>
<body class="m360-public-shell">
<div class="m360-wrap" style="padding:1.5rem;">
    <h1>مسیر قدیمی قرارداد غیرفعال است</h1>
    <p>برای بررسی و امضا فقط از مسیر canonical استفاده کنید:</p>
    <p><a class="m360-rw-btn" href="customer-profile.php">پروفایل مشتری / کارتابل</a></p>
    <p><a class="m360-rw-btn" href="customer-intake-contract-review.php">بررسی قرارداد پذیرش</a></p>
</div>
</body>
</html>
