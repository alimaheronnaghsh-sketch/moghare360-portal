<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-staff-home-helper.php';

function p117c_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

function p117c_workbench_has_file(string $role, string $file): bool
{
    foreach (m360_staff_home_workbench_items($role) as $item) {
        if ((string)($item['file'] ?? '') === $file) {
            return true;
        }
    }
    return false;
}

$coverage = $root . '/docs/audit/MOGHARE360_P11_7_ONE_DAY_RUN_WORKBENCH_COVERAGE.md';
$results = [];

$results[] = p117c_pass('coverage doc exists', is_file($coverage));

$matrix = [
    ['RECEPTION', 'erp-reception-online-requests.php', 'receive'],
    ['RECEPTION', 'erp-reception-jobcards.php', 'jobcard'],
    ['RECEPTION', 'erp-intake-contracts.php', 'contract'],
    ['SERVICE_MANAGER', 'erp-technical-board.php', 'assign board'],
    ['TECHNICIAN', 'erp-technical-board.php', 'diagnosis'],
    ['TECHNICIAN', 'erp-work-execution-board.php', 'execution'],
    ['PARTS', 'erp-part-reserve.php', 'reserve'],
    ['PARTS', 'erp-jobcard-part-use.php', 'part use'],
    ['FINANCE', 'erp-estimate-board.php', 'estimate'],
    ['FINANCE', 'erp-payment-tracking.php', 'payment'],
    ['FINANCE', 'erp-final-invoice-board.php', 'invoice'],
    ['QC', 'erp-qc-board.php', 'qc'],
    ['QC', 'erp-delivery-control.php', 'delivery'],
];

foreach ($matrix as [$role, $file, $label]) {
    $results[] = p117c_pass("One-Day Run $label in $role workbench", p117c_workbench_has_file($role, $file));
}

$pass = 0; $fail = 0;
echo "# P11.7 One-Day Run Coverage Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
