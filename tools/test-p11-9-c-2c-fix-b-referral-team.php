<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixb_ref_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');
$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');

$teams = m360_rw_intake_referral_teams();
$results[] = fixb_ref_pass('team 1 option', isset($teams['team_1']) && $teams['team_1'] === 'تیم ۱');
$results[] = fixb_ref_pass('team 2 option', isset($teams['team_2']));
$results[] = fixb_ref_pass('team 3 option', isset($teams['team_3']));
$results[] = fixb_ref_pass('electrical team', isset($teams['team_electrical']) && str_contains($teams['team_electrical'], 'برق'));
$results[] = fixb_ref_pass('mechanical team', isset($teams['team_mechanical']) && str_contains($teams['team_mechanical'], 'مکانیک'));

$results[] = fixb_ref_pass('referral section in intake', str_contains($intakeSrc, 'ارجاع کارشناسی / تیم مسئول'));
$results[] = fixb_ref_pass('save_referral_team action', in_array('save_referral_team', m360_rw_intake_allowed_actions(), true));

$applied = m360_rw_intake_apply_action([], 'save_referral_team', [
    'referral_team_id' => 'team_2',
    'referral_type' => 'expert_review',
    'referral_note' => 'بررسی اولیه',
]);
$results[] = fixb_ref_pass('referral stored in payload', $applied['ok']);
$results[] = fixb_ref_pass('referral_team_label stored', ($applied['payload']['reception_intake']['referral']['referral_team_label'] ?? '') === 'تیم ۲');
$results[] = fixb_ref_pass('no JobCard team assignment', !str_contains($helperSrc, 'assigned_team_id') || !preg_match('/save_referral_team[\s\S]*assigned_team_id/', $helperSrc));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B Referral Team Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
