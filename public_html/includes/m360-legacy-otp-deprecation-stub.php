<?php
declare(strict_types=1);

/**
 * MOGHARE360 — controlled deprecation for legacy mirror OTP routes.
 */

function m360_legacy_otp_route_deprecated(): never
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "این مسیر قدیمی OTP غیرفعال شده است. از مسیر جدید سامانه استفاده کنید.\n");
        exit(1);
    }

    $accept = strtolower(trim((string)($_SERVER['HTTP_ACCEPT'] ?? '')));
    $isJson = str_contains($accept, 'application/json')
        || str_contains(strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? ''))), 'application/json');

    if ($isJson) {
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code(410);
        echo json_encode([
            'ok' => false,
            'message' => 'این مسیر قدیمی OTP غیرفعال شده است. از مسیر جدید سامانه استفاده کنید.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    http_response_code(410);
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مسیر OTP غیرفعال — ماهین 360°</title>
</head>
<body>
    <main style="max-width:32rem;margin:3rem auto;padding:1.5rem;font-family:Tahoma,sans-serif;">
        <h1>مسیر OTP غیرفعال</h1>
        <p>این مسیر قدیمی OTP غیرفعال شده است. از مسیر جدید سامانه استفاده کنید.</p>
        <p><a href="customer-request.php">درخواست آنلاین مشتری</a></p>
    </main>
</body>
</html>
    <?php
    exit;
}
