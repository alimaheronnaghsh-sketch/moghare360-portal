<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP Soft Run Home — Daily Internal Dashboard
 *
 * Mission 37 — SELECT read-only. Integrates M31–M36 UX entry points.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-release-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'owner');
$activeModule = 'dashboard';

$kpi = [];
$modules = m37_ux_module_cards($roleMode);
$errorMessage = '';
$connection = false;

$session = m37_ux_connect();

if ($session['error'] !== '') {
    $errorMessage = $session['error'];
} else {
    $connection = $session['connection'];
    $kpi = m37_ux_fetch_home_kpi($connection);
}

moghare360_render_shell_start('Soft Run — صفحه اصلی', $activeModule, $roleMode);
m37_ux_render_release_css_link();
?>

<div class="m37-sr-board">
  <div class="m37-sr-release-banner">
    <h2>مغاره ۳۶۰ — Soft Run Internal ERP</h2>
    <p>صفحه اصلی روزانه تعمیرگاه — Mission 37 Release — read-only</p>
  </div>

  <div class="m37-sr-internal-warning">
    <strong>Internal Use Only</strong> — Not SaaS · Not production commercial · No final accounting · No customer portal
  </div>

  <?php if ($errorMessage !== ''): ?>
    <div class="m360-alert m360-alert-danger"><div><?= m37_ux_h($errorMessage) ?></div></div>
  <?php endif; ?>

  <div class="m360-page-toolbar">
    <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-business-command-center.php">Business Command Center</a>
    <a class="m360-btn m360-btn-primary m360-btn-sm" href="erp-moghare-ready.php?role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">Moghare Ready</a>
    <a class="m360-btn m360-btn-secondary m360-btn-sm" href="erp-soft-run-flow-test.php?jobcard_id=1&role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">Flow Test</a>
    <a class="m360-btn m360-btn-ghost m360-btn-sm" href="erp-soft-run-readiness.php?jobcard_id=1">Soft Run Gate M30</a>
  </div>

  <div class="m37-sr-kpi-grid">
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">Customers</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h((string)($kpi['customers'] ?? 0)) ?></div></div>
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">Vehicles</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h((string)($kpi['vehicles'] ?? 0)) ?></div></div>
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">Active JobCards</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h((string)($kpi['active_jobcards'] ?? 0)) ?></div></div>
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">Service Ops</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h((string)($kpi['service_operations'] ?? 0)) ?></div></div>
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">Payments Received</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h(m37_ux_format_amount((string)($kpi['payments_received'] ?? '0'))) ?></div></div>
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">QC Passed</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h((string)($kpi['qc_passed'] ?? 0)) ?></div></div>
    <div class="m37-sr-kpi"><div class="m37-sr-kpi-label">Delivery Released</div><div class="m37-sr-kpi-value m360-num"><?= m37_ux_h((string)($kpi['delivery_released'] ?? 0)) ?></div></div>
  </div>

  <section>
    <h3 class="m360-shell-section-title" style="margin-bottom:0.75rem;">ماژول‌های عملیاتی</h3>
    <div class="m37-sr-module-grid">
      <?php foreach ($modules as $mod): ?>
        <a class="m37-sr-module-card" href="<?= m37_ux_h((string)$mod['href']) ?>">
          <span class="m37-sr-module-icon"><?= m37_ux_h((string)$mod['icon']) ?></span>
          <span class="m37-sr-module-title"><?= m37_ux_h((string)$mod['label']) ?></span>
          <span class="m37-sr-module-sub"><?= m37_ux_h((string)$mod['label_en']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php
if ($connection !== false) {
    @odbc_close($connection);
}
moghare360_render_shell_end();
