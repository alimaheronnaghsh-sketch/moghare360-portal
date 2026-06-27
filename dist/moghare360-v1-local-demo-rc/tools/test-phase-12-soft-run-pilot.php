<?php
/**
 * MOGHARE360 ERP — Phase 12 Soft Run Pilot CLI Test
 */

declare(strict_types=1);

const P12PL_TABLES = [
    'erp_soft_run_pilots',
    'erp_soft_run_pilot_scenarios',
    'erp_soft_run_pilot_flow_snapshots',
    'erp_soft_run_pilot_feedback',
    'erp_soft_run_pilot_history',
];

const P12PL_SEED_PILOT = 'PILOT-LOCAL-RC1';

const P12PL_PHP = [
    'public_html/includes/moghare360-pilot-helper.php',
    'public_html/erp-soft-run-pilot-center.php',
    'public_html/erp-pilot-scenario-builder.php',
    'public_html/submit-pilot-scenario.php',
    'public_html/erp-pilot-flow-viewer.php',
    'public_html/erp-pilot-data-checklist.php',
    'public_html/erp-pilot-feedback.php',
    'public_html/submit-pilot-feedback.php',
    'public_html/erp-soft-run-pilot-report.php',
];

const P12PL_MISSION = [
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_00_INDEX.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_01_SCOPE.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_02_PILOT_SCENARIO_MODEL.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_03_REAL_DATA_USAGE_POLICY.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_04_PILOT_FEEDBACK_MODEL.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_05_PILOT_EXIT_CRITERIA.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_90_TEST_RESULT.md',
    'docs/missions/phase_12_soft_run_pilot/PHASE_12_99_SIGNOFF.md',
];

const P12PL_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p12pl_root(): string { return dirname(__DIR__); }
function p12pl_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p12pl_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

require_once p12pl_root() . '/public_html/includes/moghare360-pilot-helper.php';

$_POST = [];
$_SESSION = [];
$rtToken = pilot_csrf_token('pilot_scenario_create');
$_POST['pilot_csrf_token'] = $rtToken;
$_POST['pilot_csrf_action'] = 'pilot_scenario_create';
$csrfRoundtripOk = pilot_csrf_validate('pilot_scenario_create');

echo 'PHASE 12 SOFT RUN PILOT TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;
$ok = true; $fail = [];
p12pl_line('Pilot CSRF roundtrip validate', $csrfRoundtripOk ? 'PASSED' : 'FAILED');
if (!$csrfRoundtripOk) { $ok = false; $fail[] = 'csrf_roundtrip'; }

$c = pilot_db();
if ($c === false) { $ok = false; $fail[] = 'db'; p12pl_line('Database connection', 'FAILED'); }
else { p12pl_line('Database connection', 'PASSED'); }

if ($c !== false) {
    foreach (P12PL_TABLES as $t) {
        $ex = pilot_table_exists($c, $t);
        p12pl_line('Table dbo.' . $t, $ex ? 'PASSED' : 'FAILED');
        if (!$ex) { $ok = false; $fail[] = $t; continue; }
        $cnt = pilot_scalar($c, 'SELECT COUNT(*) FROM dbo.' . $t);
        p12pl_line('SELECT dbo.' . $t, $cnt !== null ? 'PASSED (' . $cnt . ' rows)' : 'FAILED');
    }
    if (pilot_table_exists($c, 'erp_soft_run_pilots')) {
        $seed = pilot_scalar($c, 'SELECT COUNT(*) FROM dbo.erp_soft_run_pilots WHERE pilot_code=?', [P12PL_SEED_PILOT]);
        p12pl_line('Seed pilot ' . P12PL_SEED_PILOT, ($seed !== null && (int)$seed > 0) ? 'PASSED' : 'FAILED');
        if ($seed === null || (int)$seed < 1) { $ok = false; $fail[] = 'seed'; }
    }
    @odbc_close($c);
}

$css = p12pl_root() . '/public_html/assets/moghare360-ui/moghare360-pilot.css';
p12pl_line('CSS moghare360-pilot.css', is_file($css) ? 'PASSED' : 'FAILED');
if (!is_file($css)) { $ok = false; $fail[] = 'css'; }

$helperSrc = is_file(p12pl_root() . '/public_html/includes/moghare360-pilot-helper.php')
    ? (string)file_get_contents(p12pl_root() . '/public_html/includes/moghare360-pilot-helper.php') : '';
$builder = p12pl_root() . '/public_html/erp-pilot-scenario-builder.php';
$builderSrc = is_file($builder) ? (string)file_get_contents($builder) : '';
$submitSrc = is_file(p12pl_root() . '/public_html/submit-pilot-scenario.php')
    ? (string)file_get_contents(p12pl_root() . '/public_html/submit-pilot-scenario.php') : '';

$helperChecks = [
    'pilot_session_start' => 'function pilot_session_start',
    'pilot_csrf_token' => 'function pilot_csrf_token',
    'pilot_csrf_input' => 'function pilot_csrf_input',
    'pilot_csrf_validate' => 'function pilot_csrf_validate',
];
foreach ($helperChecks as $label => $needle) {
    $pass = str_contains($helperSrc, $needle);
    p12pl_line('Helper ' . $label, $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = 'helper_' . $label; }
}

p12pl_line('Builder pilot_csrf_input call', str_contains($builderSrc, 'pilot_csrf_input($csrfAction)') ? 'PASSED' : 'FAILED');
if (!str_contains($builderSrc, 'pilot_csrf_input($csrfAction)')) { $ok = false; $fail[] = 'builder_csrf_input'; }
p12pl_line('Builder echo csrf input', str_contains($builderSrc, 'echo $csrfInput') ? 'PASSED' : 'FAILED');
if (!str_contains($builderSrc, 'echo $csrfInput')) { $ok = false; $fail[] = 'builder_csrf_echo'; }
p12pl_line('Helper pilot_csrf_token field', str_contains($helperSrc, 'name="pilot_csrf_token"') ? 'PASSED' : 'FAILED');
if (!str_contains($helperSrc, 'name="pilot_csrf_token"')) { $ok = false; $fail[] = 'helper_token_field'; }
p12pl_line('Helper pilot_csrf_action field', str_contains($helperSrc, 'name="pilot_csrf_action"') ? 'PASSED' : 'FAILED');
if (!str_contains($helperSrc, 'name="pilot_csrf_action"')) { $ok = false; $fail[] = 'helper_action_field'; }
p12pl_line('Submit uses pilot_csrf_validate', str_contains($submitSrc, 'pilot_csrf_validate_detail') ? 'PASSED' : 'FAILED');
if (!str_contains($submitSrc, 'pilot_csrf_validate_detail')) { $ok = false; $fail[] = 'submit_validate'; }
p12pl_line('Submit no erp_csrf_require_valid', !str_contains($submitSrc, 'erp_csrf_require_valid') ? 'PASSED' : 'FAILED');
if (str_contains($submitSrc, 'erp_csrf_require_valid')) { $ok = false; $fail[] = 'submit_erp_csrf'; }

foreach (P12PL_PHP as $rel) {
    $fp = p12pl_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { $ok = false; $fail[] = $rel; p12pl_line('File ' . basename($rel), 'FAILED'); continue; }
    p12pl_line('File ' . basename($rel), 'PASSED');
    $out = []; $ec = 0;
    exec(p12pl_php() . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $ec);
    p12pl_line('PHP syntax ' . basename($rel), $ec === 0 ? 'PASSED' : 'FAILED');
    if ($ec !== 0) { $ok = false; $fail[] = 'syntax ' . $rel; }
}

echo 'Mission docs:' . PHP_EOL;
foreach (P12PL_MISSION as $rel) {
    $fp = p12pl_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p12pl_line(basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

foreach (P12PL_FORBIDDEN as $rel) {
    $fp = p12pl_root() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) { p12pl_line('Forbidden ' . $rel, 'SKIP'); continue; }
    $gc = [];
    exec('git -C ' . escapeshellarg(p12pl_root()) . ' status --short -- ' . escapeshellarg($rel) . ' 2>&1', $gc);
    $mod = $gc !== [] && trim(implode('', $gc)) !== '';
    p12pl_line('Forbidden ' . $rel, $mod ? 'FAILED (modified)' : 'OK');
    if ($mod) { $ok = false; $fail[] = 'forbidden ' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL;
echo $ok ? 'RESULT: PASSED' . PHP_EOL : 'RESULT: FAILED' . PHP_EOL;
if (!$ok) echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
echo 'PHASE 12 SOFT RUN PILOT TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
