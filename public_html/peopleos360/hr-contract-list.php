<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-identity-date.php';

p360hr_require_password_changed_for_cartable();
if (!p360hr_can_manage_personnel()) {
    http_response_code(403);
    echo 'دسترسی مجاز نیست.';
    exit;
}

p360hr_reminder_refresh_all();

$filter = strtoupper(trim((string)($_GET['filter'] ?? 'ACTIVE')));
$allowed = ['ACTIVE', 'NEAR_EXPIRY', 'EXPIRED', 'RENEWED', 'NO_RENEW', 'PERMANENT', 'TEMPORARY', 'HOURLY', 'SPECIFIC_WORK', 'CONTRACTUAL'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'ACTIVE';
}

$sql = "SELECT c.*, e.first_name, e.last_name, e.display_name_override, e.employee_code, e.job_title AS emp_job
        FROM dbo.p360_hr_contracts c
        INNER JOIN dbo.p360_employees e ON e.employee_id=c.employee_id WHERE 1=1";
$params = [];
if ($filter === 'ACTIVE') {
    $sql .= " AND c.is_locked=1 AND (c.renewal_status IS NULL OR c.renewal_status NOT IN (N'RENEWED', N'EXITED')) AND (c.end_date IS NULL OR c.end_date >= CAST(GETDATE() AS date))";
} elseif ($filter === 'NEAR_EXPIRY') {
    $sql .= " AND c.is_locked=1 AND c.end_date IS NOT NULL AND c.end_date BETWEEN CAST(GETDATE() AS date) AND DATEADD(day,15,CAST(GETDATE() AS date))";
} elseif ($filter === 'EXPIRED') {
    $sql .= " AND c.is_locked=1 AND c.end_date IS NOT NULL AND c.end_date < CAST(GETDATE() AS date)";
} elseif ($filter === 'RENEWED') {
    $sql .= " AND c.renewal_status=N'RENEWED'";
} elseif ($filter === 'NO_RENEW') {
    $sql .= " AND c.renewal_status IN (N'EXIT_PENDING', N'EXITED')";
} elseif (in_array($filter, ['PERMANENT', 'TEMPORARY', 'HOURLY', 'SPECIFIC_WORK', 'CONTRACTUAL'], true)) {
    $sql .= ' AND c.contract_type=?';
    $params[] = $filter;
}
$sql .= ' ORDER BY c.contract_id DESC';
$rows = p360hr_rows($sql, $params);

p360hr_layout_start('فهرست قراردادها');
echo '<nav class="p360hr-tabs">';
$labels = [
    'ACTIVE' => 'فعال', 'NEAR_EXPIRY' => 'نزدیک به اتمام', 'EXPIRED' => 'منقضی‌شده',
    'RENEWED' => 'تمدیدشده', 'NO_RENEW' => 'عدم تمدید', 'PERMANENT' => 'دائم',
    'TEMPORARY' => 'موقت', 'HOURLY' => 'ساعتی', 'SPECIFIC_WORK' => 'کار معین', 'CONTRACTUAL' => 'پیمانی',
];
foreach ($labels as $k => $fa) {
    $cls = $filter === $k ? ' active' : '';
    echo '<a class="' . trim($cls) . '" href="?filter=' . rawurlencode($k) . '">' . p360hr_h($fa) . '</a>';
}
echo '</nav>';

echo '<table class="m360-table"><thead><tr>
<th>کد قرارداد</th><th>کد پرسنلی</th><th>نام پرسنل</th><th>عنوان شغل</th><th>نوع</th>
<th>شروع</th><th>مدت</th><th>پایان</th><th>روز باقی</th><th>وضعیت تمدید</th><th>وضعیت هشدار</th><th></th>
</tr></thead><tbody>';
foreach ($rows as $r) {
    $emp = ['first_name' => $r['first_name'] ?? '', 'last_name' => $r['last_name'] ?? '', 'display_name_override' => $r['display_name_override'] ?? ''];
    $days = !empty($r['end_date']) ? p360_days_remaining_until(substr((string)$r['end_date'], 0, 10)) : null;
    $near = $days !== null && $days <= 15 && $days >= 0;
    $alert = $near ? 'نزدیک به اتمام' : (($days !== null && $days < 0) ? 'منقضی' : 'عادی');
    $cls = $near ? ' class="p360hr-warn"' : (($days !== null && $days < 0) ? ' class="p360hr-danger"' : '');
    echo '<tr' . $cls . '>';
    echo '<td>' . (int)$r['contract_id'] . '</td>';
    echo '<td>' . p360hr_h((string)$r['personnel_code']) . '</td>';
    echo '<td>' . p360hr_h(p360hr_employee_full_name($emp)) . '</td>';
    echo '<td>' . p360hr_h((string)($r['contract_job_title'] ?? $r['emp_job'] ?? '')) . '</td>';
    echo '<td>' . p360hr_h(p360hr_contract_type_fa((string)$r['contract_type'])) . '</td>';
    echo '<td>' . p360hr_h(p360hr_date_jalali((string)($r['start_date'] ?? ''))) . '</td>';
    echo '<td>' . p360hr_h((string)($r['duration_text'] ?? '—')) . '</td>';
    echo '<td>' . p360hr_h(!empty($r['end_date']) ? p360hr_date_jalali((string)$r['end_date']) : '—') . '</td>';
    echo '<td>' . ($days === null ? '—' : (string)$days) . ($near ? ' (هشدار)' : '') . '</td>';
    echo '<td>' . p360hr_h((string)($r['renewal_status'] ?? '—')) . '</td>';
    echo '<td>' . p360hr_h($alert) . '</td>';
    echo '<td><a href="hr-contract-register.php?contract_id=' . (int)$r['contract_id'] . '&personnel_code=' . rawurlencode((string)$r['personnel_code']) . '">مشاهده</a></td>';
    echo '</tr>';
}
if ($rows === []) {
    echo '<tr><td colspan="12">موردی نیست.</td></tr>';
}
echo '</tbody></table>';
p360hr_layout_end();
