<?php
declare(strict_types=1);
$action = (string)($_GET['action'] ?? '');
if ($action === 'create') {
    header('Location: erp-reception-board.php?tab=customers&panel=create_vehicle');
} else {
    header('Location: erp-reception-board.php?tab=customers&panel=vehicles');
}
exit;
