<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/m360-canonical-host-helper.php';
m360_canonical_local_host_enforce();
header('Location: index.html', true, 302);
exit;
