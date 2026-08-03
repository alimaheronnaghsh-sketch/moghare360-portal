<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';
require_once __DIR__ . '/includes/p360-jalali.php';

p360hr_require_password_changed_for_cartable();
$emp = p360hr_employee_for_current_user();
p360hr_layout_start('حضور و غیاب من');
if ($emp === null) {
    echo '<p>پرونده متصل نیست.</p>';
    p360hr_layout_end();
    exit;
}
$eid = (int)($emp['employee_id'] ?? 0);
$conn = p360hr_odbc();

$now = getdate();
[$jy, $jm, $jd] = p360_gregorian_to_jalali((int)$now['year'], (int)$now['mon'], (int)$now['mday']);
$selY = isset($_GET['jy']) ? (int)$_GET['jy'] : $jy;
$selM = isset($_GET['jm']) ? (int)$_GET['jm'] : $jm;
if ($selM < 1 || $selM > 12) {
    $selM = $jm;
}
if ($selY < 1300 || $selY > 1500) {
    $selY = $jy;
}
$daysInMonth = p360_jalali_month_days($selY, $selM);
$monthName = p360_jalali_month_name($selM);

$prevM = $selM - 1;
$prevY = $selY;
if ($prevM < 1) {
    $prevM = 12;
    $prevY--;
}
$nextM = $selM + 1;
$nextY = $selY;
if ($nextM > 12) {
    $nextM = 1;
    $nextY++;
}

$punchesByDay = [];
$gStart = p360_jalali_to_gregorian($selY, $selM, 1);
$gEnd = p360_jalali_to_gregorian($selY, $selM, $daysInMonth);
$startSql = sprintf('%04d-%02d-%02d 00:00:00', $gStart[0], $gStart[1], $gStart[2]);
$endSql = sprintf('%04d-%02d-%02d 23:59:59', $gEnd[0], $gEnd[1], $gEnd[2]);
$rs2 = @odbc_prepare($conn, 'SELECT punch_at, punch_type FROM dbo.p360_raw_attendance_logs WHERE employee_id=? AND punch_at>=? AND punch_at<=? ORDER BY punch_at ASC');
if ($rs2 && @odbc_execute($rs2, [$eid, $startSql, $endSql])) {
    while ($r = odbc_fetch_array($rs2)) {
        $punchAt = (string)($r['punch_at'] ?? '');
        $ts = strtotime($punchAt);
        if ($ts === false) {
            continue;
        }
        [$py, $pm, $pd] = p360_gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
        if ($py !== $selY || $pm !== $selM) {
            continue;
        }
        if (!isset($punchesByDay[$pd])) {
            $punchesByDay[$pd] = ['in' => '', 'out' => ''];
        }
        $type = strtoupper((string)($r['punch_type'] ?? ''));
        $time = date('H:i', $ts);
        if ($type === 'IN' || $type === 'CHECK_IN' || $type === 'ENTRY' || str_contains($type, 'IN')) {
            if ($punchesByDay[$pd]['in'] === '') {
                $punchesByDay[$pd]['in'] = $time;
            }
        } elseif ($type === 'OUT' || $type === 'CHECK_OUT' || $type === 'EXIT' || str_contains($type, 'OUT')) {
            $punchesByDay[$pd]['out'] = $time;
        } else {
            if ($punchesByDay[$pd]['in'] === '') {
                $punchesByDay[$pd]['in'] = $time;
            } else {
                $punchesByDay[$pd]['out'] = $time;
            }
        }
    }
}

$weekdays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

echo '<div class="m360-card" style="margin-bottom:1rem">';
echo '<h2 style="margin:0 0 .5rem">جدول ماهانه حضور — ' . p360hr_h($monthName . ' ' . (string)$selY) . '</h2>';
echo '<p><a class="m360-btn" href="?jy=' . $prevY . '&jm=' . $prevM . '">ماه قبل</a> ';
echo '<a class="m360-btn" href="?jy=' . $nextY . '&jm=' . $nextM . '">ماه بعد</a></p>';
echo '<p class="m360-otp-note">هر روز سه درخواست مستقل دارد. ثبت مستقیم حضور نهایی یا تأیید درخواست خود مجاز نیست. فرایند اصلاح تردد هنوز به گردش تأیید متصل نشده است.</p>';
echo '</div>';

echo '<div style="overflow:auto">';
echo '<table class="m360-table"><thead><tr>';
echo '<th>روز</th><th>تاریخ</th><th>شیفت برنامه‌ای</th><th>ورود ثبت‌شده</th><th>خروج ثبت‌شده</th><th>مدت کارکرد</th><th>وضعیت حضور</th><th>وضعیت درخواست</th><th>اقدامات روزانه</th>';
echo '</tr></thead><tbody>';

for ($d = 1; $d <= $daysInMonth; $d++) {
    [$gy, $gm, $gd] = p360_jalali_to_gregorian($selY, $selM, $d);
    $dow = (int)date('w', mktime(12, 0, 0, $gm, $gd, $gy)); // 0=Sun
    $jalaliDow = ($dow + 1) % 7; // approximate Sat=0 map: Sat=6 in PHP... use (dow+1)%7 with Sat=6→0
    // PHP w: 0 Sun .. 6 Sat. Iranian week Sat-first: Sat=0 → (dow+1)%7 when Sat(6)->0? (6+1)%7=0 yes; Sun(0)->1.
    $dayName = $weekdays[$jalaliDow];
    $in = $punchesByDay[$d]['in'] ?? '';
    $out = $punchesByDay[$d]['out'] ?? '';
    $worked = '—';
    if ($in !== '' && $out !== '') {
        $mins = (strtotime($out) - strtotime($in)) / 60;
        if ($mins > 0) {
            $worked = sprintf('%d:%02d', intdiv((int)$mins, 60), ((int)$mins) % 60);
        }
    }
    $status = 'ثبت‌نشده';
    if ($in !== '' && $out !== '') {
        $status = 'کامل';
    } elseif ($in !== '' || $out !== '') {
        $status = 'ناقص';
    }
    if ($jalaliDow === 6) {
        $status = ($in !== '' || $out !== '') ? $status . ' (جمعه)' : 'جمعه / تعطیل برنامه‌ای';
    }

    $dateFa = sprintf('%04d/%02d/%02d', $selY, $selM, $d);
    echo '<tr>';
    echo '<td>' . p360hr_h($dayName) . '</td>';
    echo '<td class="m360-num">' . p360hr_h($dateFa) . '</td>';
    echo '<td>' . ($jalaliDow === 6 ? 'تعطیل برنامه‌ای' : 'شیفت پایه') . '</td>';
    echo '<td class="m360-num">' . p360hr_h($in !== '' ? $in : '—') . '</td>';
    echo '<td class="m360-num">' . p360hr_h($out !== '' ? $out : '—') . '</td>';
    echo '<td class="m360-num">' . p360hr_h($worked) . '</td>';
    echo '<td>' . p360hr_h($status) . '</td>';
    echo '<td>—</td>';
    echo '<td style="white-space:nowrap">';
    echo '<button type="button" class="m360-btn" disabled title="NOT_YET_ENFORCED">درخواست مرخصی</button> ';
    echo '<button type="button" class="m360-btn" disabled title="NOT_YET_ENFORCED">درخواست اضافه‌کاری</button> ';
    echo '<button type="button" class="m360-btn" disabled title="فرایند اصلاح تردد هنوز به گردش تأیید متصل نشده است.">درخواست اصلاح تردد</button>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

echo '<h2>قوانین شیفت پایه</h2>';
echo '<table class="m360-table"><thead><tr><th>دامنه</th><th>شروع</th><th>پایان</th><th>استراحت</th><th>دقایق برنامه‌ای</th><th>توضیح</th></tr></thead><tbody>';
$rs = @odbc_exec($conn, 'SELECT day_scope, start_time, end_time, break_minutes, break_label_fa, scheduled_minutes, friday_closed, requires_approval FROM dbo.p360_hr_shift_rules WHERE is_active=1');
if ($rs) {
    while ($r = odbc_fetch_array($rs)) {
        $note = ((int)($r['friday_closed'] ?? 0) === 1) ? 'جمعه تعطیل؛ حضور نیازمند تأیید' : ((string)($r['break_label_fa'] ?? ''));
        echo '<tr><td>' . p360hr_h((string)($r['day_scope'] ?? '')) . '</td><td>' . p360hr_h((string)($r['start_time'] ?? '')) . '</td><td>' . p360hr_h((string)($r['end_time'] ?? '')) . '</td><td>' . p360hr_h((string)($r['break_minutes'] ?? '')) . '</td><td>' . p360hr_h((string)($r['scheduled_minutes'] ?? '')) . '</td><td>' . p360hr_h($note) . '</td></tr>';
    }
}
echo '</tbody></table>';
echo '<p class="m360-otp-note">حضور روز جمعه به‌صورت خودکار قابل‌پرداخت نیست و نیازمند درخواست «تأیید حضور روز جمعه» است.</p>';
p360hr_layout_end();
