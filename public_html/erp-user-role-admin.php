<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-access-management-helper.php';

function m360_ura_role_label(string $roleCode): string
{
    $roleCode = strtoupper(trim($roleCode));
    $map = m360_access_mgmt_role_code_map();

    return (string)($map[$roleCode]['label_fa'] ?? ($roleCode !== '' ? $roleCode : 'بدون نقش'));
}

function m360_ura_yes_no(mixed $value): string
{
    return (string)$value === '1' || $value === 1 || $value === true ? 'بله' : 'خیر';
}

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$selectedRole = strtoupper(trim((string)($_GET['role'] ?? '')));

$actorRow = null;
$users = [];
$uatRows = [];
$uatUsernames = [
    'AUTO-UAT-HALL-MANAGER',
    'AUTO-UAT-TECHNICIAN',
    'AUTO-UAT-RECEPTION',
    'AUTO-UAT-INVENTORY',
    'AUTO-UAT-CRM',
    'AUTO-UAT-OVERNIGHT-admin',
    'mahin.paradigm.owner',
];

if ($conn !== false) {
    $actorRow = m360_access_fetch_rows(
        $conn,
        "SELECT TOP 1 u.user_id, u.username, u.full_name, u.lifecycle_state, u.is_login_enabled, u.is_system_owner,
                cu.role_code, cu.company_id, cu.is_active AS company_user_active
         FROM dbo.core_users u
         LEFT JOIN dbo.erp_company_users cu ON cu.user_id = u.user_id AND cu.company_id = 1
         WHERE u.user_id = ?",
        [$actorId]
    )[0] ?? null;

    $users = m360_access_fetch_rows(
        $conn,
        "SELECT u.user_id, u.username, u.full_name, u.lifecycle_state, u.is_login_enabled, u.is_system_owner,
                cu.company_id, cu.role_code, cu.is_active AS company_user_active
         FROM dbo.core_users u
         LEFT JOIN dbo.erp_company_users cu ON cu.user_id = u.user_id AND cu.company_id = 1
         WHERE u.lifecycle_state = N'ACTIVE'
         ORDER BY u.is_system_owner DESC, u.user_id"
    );

    $placeholders = implode(',', array_fill(0, count($uatUsernames), '?'));
    $uatRows = m360_access_fetch_rows(
        $conn,
        "SELECT u.user_id, u.username, u.full_name, u.lifecycle_state, u.is_login_enabled, u.is_system_owner,
                cu.company_id, cu.role_code, cu.is_active AS company_user_active
         FROM dbo.core_users u
         LEFT JOIN dbo.erp_company_users cu ON cu.user_id = u.user_id AND cu.company_id = 1
         WHERE u.username IN ($placeholders)
         ORDER BY CASE WHEN u.username = N'mahin.paradigm.owner' THEN 0 ELSE 1 END, u.username",
        $uatUsernames
    );
}

$roleMap = m360_access_mgmt_role_code_map();
$roleBuckets = [];
foreach ($roleMap as $code => $meta) {
    $roleBuckets[$code] = [
        'code' => $code,
        'label' => (string)$meta['label_fa'],
        'users' => [],
        'login_enabled' => 0,
        'owner_flags' => 0,
    ];
}
$roleBuckets['__NONE__'] = [
    'code' => '__NONE__',
    'label' => 'بدون نقش',
    'users' => [],
    'login_enabled' => 0,
    'owner_flags' => 0,
];

foreach ($users as $row) {
    $code = strtoupper(trim((string)($row['role_code'] ?? '')));
    if ($code === '' || !isset($roleBuckets[$code])) {
        $code = '__NONE__';
    }
    $roleBuckets[$code]['users'][] = $row;
    if ((string)($row['is_login_enabled'] ?? '0') === '1' && (string)($row['company_user_active'] ?? '0') === '1') {
        $roleBuckets[$code]['login_enabled']++;
    }
    if ((string)($row['is_system_owner'] ?? '0') === '1') {
        $roleBuckets[$code]['owner_flags']++;
    }
}

if ($selectedRole !== '' && $selectedRole !== '__NONE__' && !isset($roleBuckets[$selectedRole])) {
    $selectedRole = '';
}

$routeMatrix = [
    ['label' => 'خانه محصول', 'href' => 'erp-product-home.php'],
    ['label' => 'میز پذیرش', 'href' => 'erp-reception-workbench.php?section=reception'],
    ['label' => 'شروع درخواست حضوری توسط پذیرش', 'href' => 'erp-reception-walkin-create.php'],
    ['label' => 'کارتابل مدیر سالن', 'href' => 'erp-hall-cartable.php'],
    ['label' => 'داشبورد مدیریت', 'href' => 'erp-management-dashboard.php'],
];

m360_access_mgmt_render_head('داشبورد کاربران و نقش‌ها');
m360_access_mgmt_render_flash();
?>
<section class="m360-access-card m360-page-brand-header">
    <div class="m360-brand-lockup" aria-label="MOGHARE360">
        <img class="m360-brand-logo" src="assets/brand/moghareh-motors-logo.jpg" width="40" height="40" alt="MOGHARE360" onerror="this.style.display='none'">
        <div class="m360-brand-wordmark">
            <span class="m360-brand-wordmark__title" lang="en" dir="ltr">MOGHARE360</span>
            <span class="m360-brand-wordmark__sub">داشبورد دسترسی</span>
        </div>
    </div>
    <p class="m360-rc-note">نمایش فقط‌خواندنی نقش‌ها و کاربران فعال — بدون تغییر مجوز.</p>
</section>

<?php if ($conn === false): ?>
    <section class="m360-access-card"><div class="m360-access-alert m360-access-alert-error">اتصال پایگاه داده برقرار نشد.</div></section>
<?php else: ?>
    <section class="m360-access-card">
        <div class="m360-ura-owner-strip">
            <strong>مالک فعال:</strong>
            <?= m360_access_mgmt_h((string)($actorRow['username'] ?? '')) ?>
            · <?= m360_access_mgmt_h(m360_ura_role_label((string)($actorRow['role_code'] ?? 'OWNER'))) ?>
            · ورود <?= m360_ura_yes_no($actorRow['is_login_enabled'] ?? '0') ?>
        </div>
        <div class="m360-ura-compact-warn">مالک سیستم مسیرهای کارکنان را می‌بیند، اما امضا و OTP مشتری را انجام نمی‌دهد.</div>
    </section>

    <section class="m360-access-card">
        <h2>سطوح دسترسی</h2>
        <div class="m360-ura-role-grid">
            <?php foreach ($roleBuckets as $bucket): ?>
                <?php
                $count = count($bucket['users']);
                if ($bucket['code'] === '__NONE__' && $count === 0) {
                    continue;
                }
                $isOpen = $selectedRole === (string)$bucket['code'];
                $href = 'erp-user-role-admin.php?role=' . rawurlencode((string)$bucket['code']);
                ?>
                <a class="m360-ura-role-card<?= $isOpen ? ' is-open' : '' ?>" href="<?= m360_access_mgmt_h($href) ?>">
                    <div class="m360-ura-role-card__name"><?= m360_access_mgmt_h((string)$bucket['label']) ?></div>
                    <div class="m360-ura-role-card__count"><?= (int)$count ?></div>
                    <div class="m360-ura-role-card__meta">
                        ورود فعال: <?= (int)$bucket['login_enabled'] ?>
                        <?php if ((int)$bucket['owner_flags'] > 0): ?>
                            · مالک: <?= (int)$bucket['owner_flags'] ?>
                        <?php endif; ?>
                    </div>
                    <div class="m360-ura-role-card__status"><?= $count > 0 ? 'فعال' : 'خالی' ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($selectedRole !== '' && isset($roleBuckets[$selectedRole])): ?>
        <?php $open = $roleBuckets[$selectedRole]; ?>
        <section class="m360-access-card" id="m360-ura-role-people">
            <div class="m360-ura-people-head">
                <h2>افراد سطح: <?= m360_access_mgmt_h((string)$open['label']) ?></h2>
                <a class="m360-op-button-secondary" href="erp-user-role-admin.php">بستن</a>
            </div>
            <?php if ($open['users'] === []): ?>
                <p class="m360-rc-note">کاربری در این سطح نیست.</p>
            <?php else: ?>
                <table class="m360-access-table">
                    <thead><tr><th>ID</th><th>نام کاربری</th><th>نام</th><th>ورود فعال</th><th>مالک سیستم</th></tr></thead>
                    <tbody>
                    <?php foreach ($open['users'] as $row): ?>
                        <tr>
                            <td><?= m360_access_mgmt_h((string)($row['user_id'] ?? '')) ?></td>
                            <td><?= m360_access_mgmt_h((string)($row['username'] ?? '')) ?></td>
                            <td><?= m360_access_mgmt_h((string)($row['full_name'] ?? '')) ?></td>
                            <td><?= m360_ura_yes_no(((string)($row['is_login_enabled'] ?? '0') === '1' && (string)($row['company_user_active'] ?? '0') === '1') ? '1' : '0') ?></td>
                            <td><?= m360_ura_yes_no($row['is_system_owner'] ?? '0') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="m360-access-card">
        <details class="m360-ura-tools">
            <summary>ابزارهای UAT / مرجع (غیر عملیاتی intake)</summary>
            <div class="m360-rc-cards" style="margin-top:0.75rem">
                <?php foreach ($routeMatrix as $route): ?>
                    <a class="m360-rc-card" href="<?= m360_access_mgmt_h((string)$route['href']) ?>">
                        <div class="val m360-rc-phase">UAT</div>
                        <div class="lbl"><?= m360_access_mgmt_h((string)$route['label']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($uatRows !== []): ?>
                <p class="m360-rc-note" style="margin-top:0.75rem">حساب‌های UAT: <?= (int)count($uatRows) ?> مورد (رمز نمایش داده نمی‌شود).</p>
            <?php endif; ?>
        </details>
    </section>
<?php endif; ?>
<?php m360_access_mgmt_render_foot(); ?>
