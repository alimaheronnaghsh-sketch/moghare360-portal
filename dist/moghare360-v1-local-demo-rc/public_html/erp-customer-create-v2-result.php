<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Customer Create v2 Result (Wave 1D)
 * Reads one-time session result — no direct DB write · no unsafe GET trust
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$result = $_SESSION['moghare360_customer_v2_result'] ?? null;
unset($_SESSION['moghare360_customer_v2_result']);

function wave1d_result_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$hasResult = is_array($result);
$ok = $hasResult && !empty($result['ok']);
$blocked = $hasResult && ($result['error'] ?? '') === 'DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED';
$dbError = $hasResult && !$ok && !$blocked && ($result['error'] ?? '') !== '';

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت مشتری v2 — Wave 1D</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>نتیجه ثبت مشتری v2</h1>
        <p>Wave 1D — Customer Create v2 · Validation First → DB Write</p>
    </header>

    <?php if (!$hasResult): ?>
        <section class="w1c-card w1c-note">
            نتیجه‌ای برای نمایش وجود ندارد. لطفاً از فرم ثبت مشتری ارسال کنید.
        </section>
    <?php elseif ($ok): ?>
        <section class="w1c-card w1c-success">
            <?= wave1d_result_h((string)($result['message'] ?? 'ثبت مشتری با موفقیت انجام شد.')) ?>
        </section>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">جزئیات ثبت</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;line-height:1.7;">
                <li><strong>شناسه مشتری:</strong> <?= wave1d_result_h((string)($result['customer_id'] ?? '')) ?></li>
                <li><strong>کد مشتری:</strong> <?= wave1d_result_h((string)($result['customer_code'] ?? '')) ?></li>
                <li><strong>وضعیت DB:</strong> نوشتن فعال — erp_customers</li>
                <li><strong>Audit:</strong> <?= wave1d_result_h((string)($result['audit_note'] ?? '')) ?></li>
                <li><strong>زمان:</strong> <?= wave1d_result_h((string)($result['created_at'] ?? '')) ?></li>
            </ul>
        </section>
        <?php if (!empty($result['clean']) && is_array($result['clean'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">داده پاک‌شده (clean)</h2>
            <pre class="w1c-payload"><?= wave1d_result_h(json_encode($result['clean'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
        </section>
        <?php endif; ?>
    <?php elseif ($blocked): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">نوشتن DB مسدود شد</h2>
            <p style="margin:0;"><strong>DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED</strong></p>
            <p style="margin:0.5rem 0 0;"><?= wave1d_result_h((string)($result['audit_note'] ?? '')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای ثبت در پایگاه داده</h2>
            <p style="margin:0;"><?= wave1d_result_h((string)($result['error'] ?? 'خطای نامشخص')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-customer-create-v2.php">ثبت مشتری جدید</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>
