<?php
declare(strict_types=1);

/**
 * Central ERP logout — destroys the PHP session and expires its cookie.
 * Does not clear P360SESSID / INV360SESSID / WORK360SESSID (deferred to G1.4).
 * Does not accept an arbitrary redirect destination.
 * Performs no database write.
 */

require_once __DIR__ . '/includes/m360-staff-home-helper.php';

erp_auth_destroy_central_session();

header('Location: staff-login.php');
exit;
