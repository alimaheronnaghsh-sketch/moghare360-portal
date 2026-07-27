<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr02b_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$page = (string)file_get_contents($root . '/public_html/customer-request.php');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');

$steps = [
    'm360_step_mobile',
    'm360_section_profile',
    'm360_section_vehicle',
    'm360_section_request',
    'm360_section_visit',
    'm360_section_contract',
    'm360_section_submit',
];

$results = [];
foreach ($steps as $step) {
    $results[] = pr02b_pass('step section exists: ' . $step, str_contains($page, 'id="' . $step . '"'));
}
$results[] = pr02b_pass('wizard progress nav', str_contains($page, 'm360_wizard_progress'));
$results[] = pr02b_pass('contract placeholder text', str_contains($page, 'قرارداد و تأیید نهایی در مرحله بعدی فعال می‌شود'));
$results[] = pr02b_pass('tracking success panel', str_contains($page, 'm360_created_request_id'));
$results[] = pr02b_pass('JS goToWizardStep', str_contains($js, 'goToWizardStep'));
$results[] = pr02b_pass('JS one step visible pattern', str_contains($js, 'hideAllWizardSections'));
$results[] = pr02b_pass('tomari removed - no show all sections after OTP', !str_contains($js, "showSection('m360_section_vehicle', true);\n        showSection('m360_section_request', true);"));
$results[] = pr02b_pass('no staff routing fields', !str_contains($page, 'source_channel') || !str_contains($page, 'name="source_channel"'));
$results[] = pr02b_pass('RTL luxury wizard nav CSS', str_contains((string)file_get_contents($root . '/public_html/assets/css/moghare360-v1-luxury-ui.css'), 'm360-customer-wizard-progress'));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B customer step flow: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
