<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Validation Engine Runtime Test (Wave 1A)
 *
 * Browser harness only. No database. No production form submission.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-validation-engine-test-cases.php';

$summary = wave_1a_run_validation_tests();

$overallPass = $summary['failed'] === 0;

function wave1a_h(string $value): string
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
    <title>Validation Engine Runtime Test — Wave 1A</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 1.5rem; color: #171717; }
        .w1a-wrap { max-width: 920px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
        .w1a-banner { background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff; border-radius: 14px; padding: 1.25rem 1.4rem; }
        .w1a-banner h1 { margin: 0 0 0.35rem; font-size: 1.35rem; }
        .w1a-banner p { margin: 0; font-size: 0.9rem; opacity: 0.95; }
        .w1a-card { background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 1rem 1.1rem; }
        .w1a-status { font-size: 1.1rem; font-weight: 700; padding: 0.85rem 1rem; border-radius: 10px; }
        .w1a-status.pass { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .w1a-status.fail { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .w1a-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .w1a-table th, .w1a-table td { padding: 0.55rem 0.65rem; border-bottom: 1px solid #eee; text-align: right; }
        .w1a-table th { background: #fafafa; font-size: 0.8rem; color: #525252; }
        .w1a-badge { display: inline-block; padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
        .w1a-badge.pass { background: #d1fae5; color: #065f46; }
        .w1a-badge.fail { background: #fee2e2; color: #991b1b; }
        .w1a-note { font-size: 0.85rem; color: #525252; line-height: 1.6; }
        code { background: #f5f5f5; padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.85em; }
    </style>
</head>
<body>
<div class="w1a-wrap">
    <header class="w1a-banner">
        <h1>Validation Engine Runtime Test — Wave 1A</h1>
        <p>صفحه آزمایش موتور اعتبارسنجی — بدون نوشتن در پایگاه داده · بدون ارسال فرم تولیدی</p>
    </header>

    <section class="w1a-card">
        <div class="w1a-status <?= $overallPass ? 'pass' : 'fail' ?>">
            <?= $overallPass ? 'وضعیت کلی: PASS' : 'وضعیت کلی: FAIL' ?>
            — <?= (int)$summary['passed'] ?> / <?= count($summary['results']) ?> تست
        </div>
    </section>

    <section class="w1a-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">نتایج تست‌ها</h2>
        <table class="w1a-table">
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
                    <td><?= wave1a_h($row['name']) ?></td>
                    <td>
                        <span class="w1a-badge <?= $row['pass'] ? 'pass' : 'fail' ?>">
                            <?= $row['pass'] ? 'PASS' : 'FAIL' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="w1a-card w1a-note">
        <p><strong>محدوده Wave 1A:</strong> شامل <code>moghare360-validation-engine.php</code> و ثبت قوانین Critical Forms v2 است.</p>
        <p>فرم‌های تولیدی موجود هنوز تغییر نکرده‌اند. مرحله بعد: <strong>WAVE 1B</strong> — یکپارچه‌سازی کنترل‌شده با فرم‌های منتخب.</p>
        <p>جریان قفل‌شده: UI → Validation Engine → Workflow Engine → Database → Audit Log</p>
    </section>
</div>
</body>
</html>
