<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
p360_require_login();
$conn = p360_db();
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p360_csrf_verify()) {
    $r = p360_jalali_year_save($conn, (int)$_POST['jalali_year'], (string)$_POST['starts_on_gregorian'], (string)$_POST['ends_on_gregorian']);
    $msg = $r['message'] ?? 'ثبت شد.';
}
$rows = p360_rows($conn, 'SELECT * FROM dbo.p360_jalali_calendar_years ORDER BY jalali_year DESC', []);
p360_layout_start('تقویم کار جلالی', 'labor-calendar.php');
p360_flash($msg, true);
p360_table($rows, ['jalali_year'=>'سال جلالی','starts_on_gregorian'=>'شروع','ends_on_gregorian'=>'پایان','status'=>'وضعیت']);
echo '<form class="m360-card m360-form" method="post">'.p360_csrf_field().'<label>سال جلالی</label><input name="jalali_year" type="number" required><label>شروع میلادی</label><input name="starts_on_gregorian" placeholder="YYYY-MM-DD"><label>پایان</label><input name="ends_on_gregorian"><button>ثبت سال</button></form>';
p360_layout_end();