<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Moghare Ready Release Status
 *
 * Mission 37 — Final internal release page. No write.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-ui-shell.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-release-data.php';

$roleMode = moghare360_shell_normalize_role_mode(isset($_GET['role']) ? (string)$_GET['role'] : 'owner');
$activeModule = 'soft_run_gate';
$missions = m37_ux_mission_status_list();

moghare360_render_shell_start('Moghare Ready', $activeModule, $roleMode);
m37_ux_render_release_css_link();
?>

<div class="m37-sr-board">
  <article class="m37-sr-ready-panel">
    <h1 class="m37-sr-ready-title">Soft Run Version 1.0 — Moghareh Internal ERP</h1>
    <p style="margin:0;color:#525252;font-size:0.95rem;">MOGHARE READY — Product UX Layer M31–M37 Complete</p>

    <div class="m37-sr-internal-warning" style="margin-top:1rem;">
      <strong>Soft Run Boundary:</strong> Internal use only · Not SaaS · Not production commercial · No final accounting · No customer portal · No tenant system
    </div>

    <h3 style="margin:1.25rem 0 0.5rem;font-size:1rem;">Mission Status</h3>
    <ul class="m37-sr-checklist">
      <?php foreach ($missions as $m): ?>
        <li>
          <span><?= m37_ux_h((string)$m['mission']) ?> <?= m37_ux_h((string)$m['name']) ?></span>
          <span class="m360-badge m360-badge-success"><?= m37_ux_h((string)$m['status']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <h3 style="margin:1rem 0 0.5rem;font-size:1rem;">Operational Limits</h3>
    <ul class="m37-sr-boundary-list">
      <li>No invoice finalization</li>
      <li>No accounting export</li>
      <li>No customer portal change</li>
      <li>No payment/stock/purchase/QC/delivery write from UX layer</li>
      <li>Controlled prototypes (M15–M30) remain separate write paths</li>
    </ul>

    <div class="m37-sr-launch-grid">
      <a class="m360-btn m360-btn-primary" href="erp-soft-run-home.php?role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">Start Soft Run Home</a>
      <a class="m360-btn m360-btn-secondary" href="erp-soft-run-flow-test.php?jobcard_id=1&role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">Full Flow Test</a>
      <a class="m360-btn m360-btn-secondary" href="erp-jobcard-workbench.php?role=<?= m37_ux_h(rawurlencode($roleMode)) ?>">JobCard Workbench</a>
      <a class="m360-btn m360-btn-secondary" href="erp-customer-vehicle-workbench.php?role=reception">Customer Vehicle Workbench</a>
      <a class="m360-btn m360-btn-secondary" href="erp-service-operation-workbench-ux.php?role=service">Service Operation Workbench</a>
      <a class="m360-btn m360-btn-secondary" href="erp-finance-preview-workbench.php?role=finance">Finance Preview</a>
    </div>
  </article>
</div>

<?php moghare360_render_shell_end(); ?>
