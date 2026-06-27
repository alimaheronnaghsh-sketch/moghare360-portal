<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-demo-readiness-helper.php';

m360_soft_run_require_staff();

$conn = customer_core_db();
$flash = '';
$flashOk = true;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $conn !== false) {
    erp_csrf_require_valid(M360_SOFT_RUN_CSRF, $_POST['erp_csrf_token'] ?? null);
    $key = trim((string)($_POST['checklist_key'] ?? ''));
    $status = trim((string)($_POST['checklist_status'] ?? ''));
    $note = trim((string)($_POST['checklist_note'] ?? ''));
    $userId = (int)(erp_auth_current_user_id() ?? 0);
    $res = m360_readiness_update_checklist_item($conn, $key, $status, $note, $userId);
    $flash = (string)$res['message'];
    $flashOk = !empty($res['ok']);
}

$items = $conn !== false ? m360_readiness_checklist_items($conn) : m360_readiness_checklist_items(false);

function m360_sr_badge(string $s): string {
    return match (strtoupper($s)) {
        M360_SOFT_RUN_STATUS_PASS => 'pass',
        M360_SOFT_RUN_STATUS_WARNING => 'warn',
        M360_SOFT_RUN_STATUS_BLOCKED => 'block',
        default => 'notrun',
    };
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چک‌لیست Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-soft-run.css">
</head>
<body class="m360-sr-page">
<div class="w1c-wrap m360-sr-wrap">
    <header class="w1c-banner">
        <h1>چک‌لیست Soft Run</h1>
        <p>POST فقط روی erp_soft_run_checklist — بدون تغییر workflow عملیاتی</p>
    </header>
    <nav class="m360-sr-nav">
        <?php foreach (m360_soft_run_nav() as $link): ?>
            <a href="<?= m360_soft_run_h($link['href']) ?>" class="<?= $link['href'] === 'erp-soft-run-checklist.php' ? 'active' : '' ?>"><?= m360_soft_run_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($flash !== ''): ?><p class="m360-sr-note"><?= m360_soft_run_h($flash) ?></p><?php endif; ?>
    <section class="w1c-card">
        <table class="m360-sr-table">
            <thead><tr><th>گروه</th><th>آیتم</th><th>وضعیت</th><th>یادداشت</th><th>به‌روزرسانی</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= m360_soft_run_h((string)$it['group']) ?></td>
                    <td><?= m360_soft_run_h((string)$it['checklist_title']) ?></td>
                    <td><span class="m360-sr-badge <?= m360_sr_badge((string)$it['checklist_status']) ?>"><?= m360_soft_run_h((string)$it['checklist_status']) ?></span></td>
                    <td><?= m360_soft_run_h((string)$it['checklist_note']) ?></td>
                    <td>
                        <form method="post" action="erp-soft-run-checklist.php">
                            <?= erp_csrf_input(M360_SOFT_RUN_CSRF) ?>
                            <input type="hidden" name="checklist_key" value="<?= m360_soft_run_h((string)$it['checklist_key']) ?>">
                            <select name="checklist_status">
                                <?php foreach ([M360_SOFT_RUN_STATUS_PASS, M360_SOFT_RUN_STATUS_WARNING, M360_SOFT_RUN_STATUS_BLOCKED, M360_SOFT_RUN_STATUS_NOT_RUN] as $st): ?>
                                    <option value="<?= m360_soft_run_h($st) ?>" <?= $st === ($it['checklist_status'] ?? '') ? 'selected' : '' ?>><?= m360_soft_run_h($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="checklist_note" value="<?= m360_soft_run_h((string)$it['checklist_note']) ?>" placeholder="یادداشت">
                            <button type="submit" class="m360-sr-btn">ذخیره</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
