<?php
/**
 * MOGHARE360 ERP — Phase 7 HR Internal Admin CLI Test
 */

declare(strict_types=1);

const P7HR_TABLES = [
    'erp_hr_employees',
    'erp_hr_employment_contracts',
    'erp_hr_attendance_records',
    'erp_hr_payroll_previews',
    'erp_hr_training_records',
    'erp_hr_disciplinary_records',
    'erp_hr_history',
];

const P7HR_PHP = [
    'public_html/includes/erp-hr-helper.php',
    'public_html/erp-hr-dashboard.php',
    'public_html/erp-employee-create.php',
    'public_html/submit-employee-create.php',
    'public_html/erp-employee-profile.php',
    'public_html/erp-employment-contract.php',
    'public_html/submit-employment-contract.php',
    'public_html/erp-attendance-entry.php',
    'public_html/submit-attendance-entry.php',
    'public_html/erp-payroll-preview.php',
    'public_html/erp-hr-training-discipline.php',
    'public_html/submit-payroll-preview.php',
    'public_html/submit-hr-training-record.php',
    'public_html/submit-hr-disciplinary-record.php',
];

const P7HR_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p7hr_root(): string { return dirname(__DIR__); }
function p7hr_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p7hr_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

require_once p7hr_root() . '/public_html/includes/erp-hr-helper.php';

echo 'PHASE 7 HR INTERNAL ADMIN TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];
$c = hr_db();
if ($c === false) { $ok = false; $fail[] = 'db'; p7hr_line('Database connection', 'FAILED'); }
else { p7hr_line('Database connection', 'PASSED'); }

if ($c !== false) {
    foreach (P7HR_TABLES as $t) {
        $ex = hr_table_exists($c, $t);
        p7hr_line('Table dbo.' . $t, $ex ? 'PASSED' : 'FAILED');
        if (!$ex) { $ok = false; $fail[] = $t; continue; }
        $cnt = hr_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $t);
        p7hr_line('SELECT dbo.' . $t, $cnt !== null ? 'PASSED (' . $cnt . ' rows)' : 'FAILED');
        if ($cnt === null) { $ok = false; $fail[] = 'sel ' . $t; }
    }
    @odbc_close($c);
}

$css = p7hr_root() . '/public_html/assets/moghare360-ui/moghare360-hr-system.css';
p7hr_line('CSS moghare360-hr-system.css', is_file($css) ? 'PASSED' : 'FAILED');
if (!is_file($css)) { $ok = false; $fail[] = 'css'; }

foreach (P7HR_PHP as $rel) {
    $fp = p7hr_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p7hr_line('File ' . basename($rel), 'FAILED'); continue; }
    p7hr_line('File ' . basename($rel), 'PASSED');
    $out = []; $ec = 0;
    exec(p7hr_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p7hr_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

foreach (P7HR_FORBIDDEN as $rel) {
    $fp = p7hr_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p7hr_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p7hr_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p7hr_line('Forbidden ' . $rel, $mod ? 'WARNING MODIFIED' : 'PASSED (unchanged)');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 7 HR INTERNAL ADMIN TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
