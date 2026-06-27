<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-v1-master-console-helper.php';

/** @return list<array<string, string>> */
function v1mc_access_units(): array
{
    return [
        [
            'unit_name' => 'OWNER',
            'suggested_role_code' => 'OWNER',
            'related_permission_area' => 'Platform / company owner oversight',
            'access_route' => 'api/auth/owner-login.php · erp-operational-command-center.php',
            'user_creation_route' => 'private/templates/production-users.template.json → private/production-users.json',
            'access_request_route' => 'api/access/request.php · erp-access-request-admin.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'SYSTEM_ADMIN',
            'suggested_role_code' => 'SYSTEM_ADMIN',
            'related_permission_area' => 'System administration / deployment',
            'access_route' => 'staff-login.php · api/auth/staff-login.php',
            'user_creation_route' => 'tools/production/CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1',
            'access_request_route' => 'erp-access-request-workflow-readonly.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'RECEPTION',
            'suggested_role_code' => 'RECEPTION',
            'related_permission_area' => 'Customer intake / jobcard reception',
            'access_route' => 'staff-login.php · erp-jobcard-command-center.php',
            'user_creation_route' => 'private/production-users.json (role RECEPTION)',
            'access_request_route' => 'mirror: user-access-request.php · master: api/access/request.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'TECHNICIAN',
            'suggested_role_code' => 'TECHNICIAN',
            'related_permission_area' => 'Service operations / workshop',
            'access_route' => 'staff-login.php · erp-technician-board.php',
            'user_creation_route' => 'private/production-users.json (role TECHNICIAN)',
            'access_request_route' => 'erp-access-request-admin.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'INVENTORY',
            'suggested_role_code' => 'INVENTORY',
            'related_permission_area' => 'Parts / stock',
            'access_route' => 'staff-login.php · erp-stock-board.php',
            'user_creation_route' => 'private/production-users.json (role INVENTORY)',
            'access_request_route' => 'erp-access-request-admin.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'FINANCE',
            'suggested_role_code' => 'FINANCE',
            'related_permission_area' => 'Payments / financial preview',
            'access_route' => 'staff-login.php · erp-payment-readonly-list.php',
            'user_creation_route' => 'private/production-users.json (role FINANCE)',
            'access_request_route' => 'erp-access-request-admin.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'QC',
            'suggested_role_code' => 'QC',
            'related_permission_area' => 'Quality check / delivery gate',
            'access_route' => 'staff-login.php · erp-qc-check.php',
            'user_creation_route' => 'private/production-users.json (role QC)',
            'access_request_route' => 'erp-access-request-admin.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'CRM',
            'suggested_role_code' => 'CRM',
            'related_permission_area' => 'Customer relationship / follow-up',
            'access_route' => 'staff-login.php · erp-crm-report.php',
            'user_creation_route' => 'private/production-users.json (role CRM)',
            'access_request_route' => 'api/access/request.php',
            'status' => 'DOCUMENTED',
        ],
        [
            'unit_name' => 'COMPANY_OWNER_VIEWER',
            'suggested_role_code' => 'COMPANY_OWNER_VIEWER',
            'related_permission_area' => 'Read-only owner dashboard',
            'access_route' => 'api/auth/owner-login.php · erp-soft-run-home.php?role=owner',
            'user_creation_route' => 'private/production-users.json (role COMPANY_OWNER_VIEWER)',
            'access_request_route' => 'docs/release/MOGHARE360_V1_PRODUCTION_USER_ACCESS_PLAN.md',
            'status' => 'DOCUMENTED',
        ],
    ];
}

v1mc_render_head('MOGHARE360 V1 — Unit Access Check Console');
$units = v1mc_access_units();
?>
<div class="v1mc-banner">Unit Access Check Console — مسیرهای امن فقط · بدون ساخت کاربر · بدون دریافت رمز</div>
<div class="v1mc-hero">
  <h1 style="margin:0 0 .4rem;font-size:1.25rem">کنترل دسترسی واحدبه‌واحد</h1>
  <p style="margin:0;font-size:.9rem;opacity:.92">
    این صفحه فقط مسیرهای مجاز را نشان می‌دهد. کاربر واقعی فقط با
    <code>private/production-users.json</code> روی سرور و اسکریپت import ساخته می‌شود.
  </p>
</div>

<nav class="v1mc-nav">
  <a href="erp-v1-master-console.php">بازگشت به Master Console</a>
  <a href="staff-login.php">staff-login.php</a>
  <a href="api/auth/owner-login.php">api/auth/owner-login.php</a>
</nav>

<div class="v1mc-card">
  <h2 style="margin-top:0;font-size:1rem">منابع امن (template / docs / script)</h2>
  <ul class="v1mc-muted" style="margin:0;padding-right:1.2rem;line-height:1.7">
    <li><code>private/templates/production-users.template.json</code> — الگوی نقش‌ها (بدون credential)</li>
    <li><code>tools/production/CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1</code> — import روی سرور</li>
    <li><code>docs/release/MOGHARE360_V1_PRODUCTION_USER_ACCESS_PLAN.md</code> — نقشه دسترسی Production</li>
    <li>Mirror self-service: <code>release/moghare360-mirror-site-package/public_html/user-access-request.php</code></li>
  </ul>
</div>

<table class="v1mc-table">
  <thead>
    <tr>
      <th>واحد</th>
      <th>role_code</th>
      <th>حوزه دسترسی</th>
      <th>مسیر ورود / دسترسی</th>
      <th>ایجاد کاربر</th>
      <th>درخواست دسترسی</th>
      <th>وضعیت</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($units as $row): ?>
    <tr>
      <td><strong><?= v1mc_h($row['unit_name']) ?></strong></td>
      <td><code><?= v1mc_h($row['suggested_role_code']) ?></code></td>
      <td><?= v1mc_h($row['related_permission_area']) ?></td>
      <td><?= v1mc_h($row['access_route']) ?></td>
      <td><?= v1mc_h($row['user_creation_route']) ?></td>
      <td><?= v1mc_h($row['access_request_route']) ?></td>
      <td><span class="v1mc-badge v1mc-badge-ready"><?= v1mc_h($row['status']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<p class="v1mc-muted" style="margin-top:1rem">
  قوانین: staff-auth.php و access-control.php تغییر نکرده‌اند · private/erp-config.php دست‌نخورده ·
  هیچ SQL destructive اجرا نمی‌شود.
</p>
<?php v1mc_render_foot(); ?>
