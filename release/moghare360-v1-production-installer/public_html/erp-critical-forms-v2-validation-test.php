<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Critical Forms v2 Validation Test (Wave 1B)
 *
 * Browser harness only. No database. No production form submission.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-form-validation-bridge-test-cases.php';

$summary = wave_1b_run_validation_tests();
$overallPass = $summary['ok'];

$demoInvalid = moghare360_validate_form_payload('customer_create_v2', wave_1b_sample_customer_invalid());
$demoHtml = moghare360_validation_errors_as_html($demoInvalid);
$demoSummary = moghare360_validation_error_summary($demoInvalid);

function wave1b_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Critical Forms v2 Validation Test — Wave 1B</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 1.5rem; color: #171717; }
        .w1b-wrap { max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
        .w1b-banner { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; border-radius: 14px; padding: 1.25rem 1.4rem; }
        .w1b-banner h1 { margin: 0 0 0.35rem; font-size: 1.35rem; }
        .w1b-banner p { margin: 0; font-size: 0.9rem; opacity: 0.95; }
        .w1b-card { background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 1rem 1.1rem; }
        .w1b-status { font-size: 1.1rem; font-weight: 700; padding: 0.85rem 1rem; border-radius: 10px; }
        .w1b-status.pass { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .w1b-status.fail { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .w1b-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .w1b-table th, .w1b-table td { padding: 0.55rem 0.65rem; border-bottom: 1px solid #eee; text-align: right; }
        .w1b-table th { background: #fafafa; font-size: 0.8rem; color: #525252; }
        .w1b-badge { display: inline-block; padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
        .w1b-badge.pass { background: #d1fae5; color: #065f46; }
        .w1b-badge.fail { background: #fee2e2; color: #991b1b; }
        .w1b-demo { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 0.85rem 1rem; font-size: 0.9rem; }
        .moghare360-validation-errors { margin: 0.5rem 0 0; padding-right: 1.25rem; }
        .w1b-note { font-size: 0.85rem; color: #525252; line-height: 1.6; }
    </style>
</head>
<body>
<div class="w1b-wrap">
    <header class="w1b-banner">
        <h1>Critical Forms v2 Validation Test — Wave 1B</h1>
        <p>آزمایش پل اعتبارسنجی فرم — بدون نوشتن در پایگاه داده · بدون ارسال فرم تولیدی</p>
    </header>

    <section class="w1b-card">
        <div class="w1b-status <?= $overallPass ? 'pass' : 'fail' ?>">
            <?= $overallPass ? 'وضعیت کلی: PASS' : 'وضعیت کلی: FAIL' ?>
            — <?= (int)$summary['passed'] ?> / <?= (int)$summary['total'] ?> تست
        </div>
    </section>

    <section class="w1b-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">نتایج تست‌ها</h2>
        <table class="w1b-table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام تست</th>
                <th>نتیجه</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($summary['results'] as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= wave1b_h($row['name']) ?></td>
                    <td>
                        <span class="w1b-badge <?= $row['pass'] ? 'pass' : 'fail' ?>">
                            <?= $row['pass'] ? 'PASS' : 'FAIL' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="w1b-card w1b-demo">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">نمونه خروجی خطا (customer_create_v2 نامعتبر)</h2>
        <p style="margin:0 0 0.5rem;"><strong>خلاصه:</strong> <?= wave1b_h($demoSummary) ?></p>
        <?= $demoHtml ?>
    </section>

    <section class="w1b-card w1b-note">
        <p><strong>محدوده Wave 1B:</strong> پل <code>moghare360-form-validation-bridge.php</code> برای customer / vehicle / jobcard v2.</p>
        <p>صفحات submit مجاز در لیست Wave 1B یافت نشد (یا برای پورتال مشتری نامناسب بودند) — یکپارچه‌سازی runtime submit در Wave 1C پس از تأیید مالک.</p>
        <p>جریان: UI → Validation Engine → Workflow / Submit موجود → Database → Audit Log</p>
    </section>
</div>
</body>
</html>
