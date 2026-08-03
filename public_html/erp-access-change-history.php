<?php
declare(strict_types=1);

/**
 * Access change history — G0.2R8 theme / containment / Persian / return only.
 * Audit queries and records unchanged.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-audit-helper.php';
require_once __DIR__ . '/includes/reception-ui-helper.php';
require_once __DIR__ . '/includes/m360-access-matrix-guard.php';

m360_am_guard('access.audit.view');

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
if ($userId !== null && $userId <= 0) {
    $userId = null;
}

$history = $conn !== false ? m360_access_audit_list_history($conn, $userId, 200) : [];

$changeTypeFa = static function (string $raw): string {
    $code = strtoupper(trim($raw));
    $map = [
        'CREATE_USER' => 'ایجاد کاربر',
        'UPDATE_USER' => 'به‌روزرسانی کاربر',
        'ASSIGN_ROLE' => 'تخصیص نقش',
        'REVOKE_ROLE' => 'لغو نقش',
        'RESET_PASSWORD' => 'بازنشانی رمز',
        'ENABLE_LOGIN' => 'فعال‌سازی ورود',
        'DISABLE_LOGIN' => 'غیرفعال‌سازی ورود',
        'LIFECYCLE' => 'تغییر وضعیت چرخه',
        'PERMISSION' => 'تغییر دسترسی',
    ];
    return $map[$code] ?? ($raw !== '' ? $raw : '—');
};

$entityFa = static function (string $raw): string {
    $code = strtoupper(trim($raw));
    $map = [
        'USER' => 'کاربر',
        'ROLE' => 'نقش',
        'PERMISSION' => 'دسترسی',
        'SESSION' => 'نشست',
    ];
    return $map[$code] ?? ($raw !== '' ? $raw : '—');
};

m360_access_mgmt_render_head('تاریخچه تغییرات دسترسی');
?>
<style>
/* G0.2R8: contain access history inside frame; neutralize blue legacy accents on this page only */
.m360-access-wrap { max-width:1120px; margin:0 auto; padding:1rem; overflow-x:hidden; box-sizing:border-box; }
.m360-access-banner {
    background:linear-gradient(145deg,rgba(28,43,36,.95),rgba(15,23,42,.78));
    border:1px solid rgba(34,197,94,.22); color:#f3f4f6;
}
.m360-access-banner h1 { color:#f3f4f6; font-size:1.35rem; }
.m360-access-banner p { color:#9ca3af; opacity:1; }
.m360-access-nav a {
    color:#e5e7eb; border-color:rgba(34,197,94,.28); background:rgba(15,23,42,.45);
}
.m360-access-nav a:hover { background:rgba(15,23,42,.7); }
.m360-ah-top {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:.5rem; margin:0 0 .85rem;
}
.m360-ah-top .m360-rc-btn,
.m360-access-wrap .m360-rc-btn {
    min-height:32px; padding:.22rem .7rem; font-size:.78rem; font-weight:600;
    border-radius:10px; box-shadow:none; width:auto; display:inline-flex; text-decoration:none;
}
.m360-access-card {
    background:linear-gradient(160deg,rgba(22,34,29,.94),rgba(15,23,42,.72));
    border:1px solid rgba(34,197,94,.2); border-radius:12px; padding:.85rem;
    margin-bottom:.85rem; color:#e5e7eb; max-width:100%; overflow:hidden; box-sizing:border-box;
}
.m360-ah-meta { margin:0 0 .65rem; font-size:.84rem; color:#9ca3af; }
.m360-ah-meta a { color:#cbd5e1; }
.m360-ah-table-wrap {
    width:100%; max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;
    border:1px solid rgba(34,197,94,.12); border-radius:10px;
}
.m360-access-table { width:100%; min-width:720px; border-collapse:collapse; font-size:.78rem; table-layout:fixed; }
.m360-access-table th, .m360-access-table td {
    border-bottom:1px solid rgba(34,197,94,.12); padding:.4rem .35rem; text-align:right; vertical-align:top;
    color:#e5e7eb; word-break:break-word; overflow-wrap:anywhere;
}
.m360-access-table th { color:#9ca3af; font-weight:600; white-space:nowrap; background:rgba(0,0,0,.18); }
.m360-access-table td.m360-ah-now { white-space:nowrap; }
.m360-access-table td.m360-ah-json {
    white-space:normal; max-width:14rem; overflow:hidden;
    display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;
}
.m360-access-table code {
    display:block; font-size:.72rem; color:#cbd5e1; white-space:pre-wrap; word-break:break-word;
    max-width:100%; overflow:hidden;
}
.m360-access-btn, .m360-access-btn.secondary {
    background:transparent !important; color:#e8efe9 !important;
    border:1px solid rgba(212,175,55,.5) !important; padding:.28rem .7rem !important;
    min-height:32px; font-size:.78rem; border-radius:10px; box-shadow:none;
}
.m360-access-alert-error {
    background:rgba(127,29,29,.35); border-color:rgba(248,113,113,.35); color:#fecaca;
}
.m360-access-foot { max-width:1120px; margin:.75rem auto 0; color:#9ca3af; opacity:1; }
body.m360-access-page { overflow-x:hidden; }
</style>
<div class="m360-ah-top">
    <p class="m360-ah-meta" style="margin:0;">
        فیلتر:
        <?= $userId !== null ? 'کاربر شماره ' . m360_access_mgmt_h((string)$userId) : 'همه کاربران' ?>
        · <a href="erp-access-change-history.php">نمایش همه</a>
    </p>
    <a class="m360-rc-btn secondary" href="erp-user-role-admin.php">بازگشت به کاربران و تنظیمات</a>
</div>
<section class="m360-access-card">
<?php if ($conn === false): ?>
    <div class="m360-access-alert m360-access-alert-error">اتصال به پایگاه داده برقرار نشد.</div>
<?php elseif ($history === []): ?>
    <p style="color:#9ca3af;margin:0;">رکورد تاریخچه‌ای یافت نشد.</p>
<?php else: ?>
    <div class="m360-ah-table-wrap">
    <table class="m360-access-table">
        <thead>
        <tr>
            <th>زمان</th>
            <th>کاربر</th>
            <th>نوع تغییر</th>
            <th>موجودیت</th>
            <th>اقدام‌کننده</th>
            <th>شناسه درخواست</th>
            <th>مقدار پس از تغییر</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $row):
            $after = (string)($row['after_json'] ?? '');
            $after = preg_replace('/password[^"]*"[^"]*"/i', 'password":"[REDACTED]"', $after) ?? $after;
            $afterShow = substr($after, 0, 180);
            $changedAt = (string)($row['changed_at'] ?? '');
            $jalali = function_exists('m360_rui_jalali_date') ? m360_rui_jalali_date($changedAt) : $changedAt;
            if ($jalali === '' || $jalali === '—' || $jalali === '-') {
                $jalali = 'ثبت نشده';
            }
        ?>
            <tr>
                <td class="m360-ah-now"><?= m360_access_mgmt_h($jalali) ?></td>
                <td><?= m360_access_mgmt_h((string)($row['subject_username'] ?? $row['user_id'] ?? '')) ?></td>
                <td><?= m360_access_mgmt_h($changeTypeFa((string)($row['change_type'] ?? ''))) ?></td>
                <td><?= m360_access_mgmt_h($entityFa((string)($row['entity_type'] ?? ''))) ?></td>
                <td><?= m360_access_mgmt_h((string)($row['changed_by_username'] ?? $row['changed_by_user_id'] ?? '')) ?></td>
                <td class="m360-ah-now"><?= m360_access_mgmt_h((string)($row['request_id'] ?? '—')) ?></td>
                <td class="m360-ah-json" title="<?= m360_access_mgmt_h($afterShow) ?>"><code><?= m360_access_mgmt_h($afterShow) ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
</section>
<?php
m360_access_mgmt_render_foot();
?>
