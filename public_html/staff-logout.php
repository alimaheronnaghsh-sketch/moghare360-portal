<?php
declare(strict_types=1);

/**
 * Central ERP logout — destroys the PHP session and expires its cookie.
 * Clears SW static caches on the client before returning to login.
 * Does not clear P360SESSID / INV360SESSID / WORK360SESSID (deferred to G1.4).
 * Does not accept an arbitrary redirect destination.
 * Performs no database write.
 */

require_once __DIR__ . '/includes/m360-staff-home-helper.php';

erp_auth_destroy_central_session();

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-store">
  <title>خروج از سامانه</title>
</head>
<body>
  <p>در حال خروج امن…</p>
  <script src="assets/js/m360-pwa.js?v=20260803"></script>
  <script>
  (function () {
    var go = function () { window.location.replace('staff-login.php?logged_out=1'); };
    if (window.M360PWA && M360PWA.onLogout) {
      M360PWA.onLogout().then(go).catch(go);
      setTimeout(go, 1500);
    } else {
      go();
    }
  })();
  </script>
</body>
</html>
