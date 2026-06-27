<?php
declare(strict_types=1);
require_once __DIR__ . '/inventory-helpers.php';
ensureSessionStarted();
try {
    $staff = requireStaffLogin();
    if (!meetingCanAccessStaffModule($staff, 'inventory')) { showErrorPage('دسترسی شما به ثبت خروج کالا فعال نیست.'); }
    $canEdit = inventoryCanEditFull($staff) && !meetingIsViewOnly($staff) && !inventoryCanRegisterInboundOnly($staff);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();
        if (!$canEdit) { flash('نقش شما اجازه ثبت خروج کالا ندارد.', 'bad'); redirect('staff-inventory-outbound.php'); }
        $itemName = trim((string)($_POST['item_name'] ?? ''));
        if ($itemName === '') { flash('نام کالا الزامی است.', 'bad'); redirect('staff-inventory-outbound.php'); }
        getPdo()->prepare('INSERT INTO inventory_movements_staging (movement_type, item_name, technical_code, oem_code, internal_code, quantity, unit_name, source_location, related_jobcard, movement_note, created_by_staff_id, sync_status, created_at) VALUES ("OUT", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Pending", NOW())')->execute([
            $itemName, trim((string)($_POST['technical_code'] ?? '')), trim((string)($_POST['oem_code'] ?? '')), trim((string)($_POST['internal_code'] ?? '')), meetingDecimalOrNull((string)($_POST['quantity'] ?? '')) ?: '0', trim((string)($_POST['unit_name'] ?? 'عدد')), trim((string)($_POST['source_location'] ?? '')), trim((string)($_POST['related_jobcard'] ?? '')), trim((string)($_POST['movement_note'] ?? '')), (int)($staff['id'] ?? 0)
        ]);
        flash('خروج کالا در صف انبار ثبت شد.'); redirect('staff-inventory.php');
    }
    renderHeader('ثبت خروج کالا', 'StockCenter Outbound'); inventoryHeaderActions('outbound');
?>
<main class="auth-wrap wide-auth inventory-page"><form class="card form-card stockcenter-form" method="post" action="staff-inventory-outbound.php"><?= csrfField() ?><div class="form-grid"><h3 class="form-section-title wide">خروج کالا / مصرف در پذیرش</h3><label>نام کالا *<input name="item_name" required <?= $canEdit ? '' : 'disabled' ?>></label><label>کد فنی<input class="input-number" name="technical_code" <?= $canEdit ? '' : 'disabled' ?>></label><label>OEM<input class="input-number" name="oem_code" <?= $canEdit ? '' : 'disabled' ?>></label><label>کد داخلی<input class="input-number" name="internal_code" <?= $canEdit ? '' : 'disabled' ?>></label><label>تعداد خروج<input class="stock-field input-number" name="quantity" type="number" step="1" min="1" required <?= $canEdit ? '' : 'disabled' ? inputmode="numeric" pattern="[0-9]*">></label><label>واحد<select name="unit_name" <?= $canEdit ? '' : 'disabled' ?>><?php foreach (inventoryUnits() as $v): ?><option><?= e($v) ?></option><?php endforeach; ?></select></label><label>لوکیشن مبدأ<input name="source_location" <?= $canEdit ? '' : 'disabled' ?>></label><label>JobCard / پذیرش مرتبط<input class="input-number" name="related_jobcard" <?= $canEdit ? '' : 'disabled' ?>></label><label class="wide">توضیحات<textarea name="movement_note" <?= $canEdit ? '' : 'disabled' ?>></textarea></label></div><div class="action-row"><button class="btn primary" <?= $canEdit ? '' : 'disabled' ?>>ثبت خروج</button><a class="btn ghost" href="staff-inventory.php">بازگشت</a></div></form></main>
<?php renderFooter(); } catch (Throwable $e) { showErrorPage('خطا در ثبت خروج کالا.', $e->getMessage()); }
