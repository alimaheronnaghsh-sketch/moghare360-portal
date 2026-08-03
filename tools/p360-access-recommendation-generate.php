<?php
declare(strict_types=1);

/**
 * Create recommendation tables + generate Owner preview for 25 personnel.
 * CLI-only. Does NOT assign roles or overrides.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI-only\n";
    exit(1);
}

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/public_html/includes/m360-access-recommendation-helper.php';

$conn = m360_am_db();
if ($conn === false) {
    fwrite(STDERR, "DB connect failed\n");
    exit(1);
}

$rolesBefore = m360_am_fetch_all($conn, 'SELECT COUNT(*) AS c FROM dbo.core_user_roles');
$ovBefore = m360_am_fetch_all($conn, 'SELECT COUNT(*) AS c FROM dbo.core_user_permission_overrides WHERE revoked_at IS NULL');
$wsBefore = m360_am_fetch_all($conn, "SELECT COUNT(*) AS c FROM dbo.core_permissions WHERE module_key=N'workshop' AND enforcement_state=N'ENFORCED'");

echo 'BEFORE_ROLES=' . (int)($rolesBefore[0]['c'] ?? 0) . PHP_EOL;
echo 'BEFORE_OVERRIDES=' . (int)($ovBefore[0]['c'] ?? 0) . PHP_EOL;
echo 'BEFORE_WS_ENF=' . (int)($wsBefore[0]['c'] ?? 0) . PHP_EOL;

m360_rec_ensure_tables($conn);
$recs = m360_rec_build_all($conn);
echo 'BUILT=' . count($recs) . PHP_EOL;

$pkgCount = count(m360_access_package_definitions());
echo 'PACKAGES=' . $pkgCount . PHP_EOL;

$persist = m360_rec_persist_all($conn, $recs, 0);
echo 'PERSISTED=' . (int)$persist['saved'] . PHP_EOL;

$preview = $repoRoot . '/tools/_generated/personnel_access_recommendations_preview.tsv';
m360_rec_write_preview_tsv($recs, $preview);
echo 'PREVIEW=' . $preview . PHP_EOL;

$jsonPath = $repoRoot . '/tools/_generated/personnel_access_recommendations_preview.json';
file_put_contents($jsonPath, json_encode([
    'generated_at' => gmdate('c'),
    'formula' => 'LOCKED_PROFILE + PRIMARY + SECONDARY + SCOPE + AUTHORITY + SENSITIVE_GATES + SOD + OWNER_EXCEPTION',
    'count' => count($recs),
    'recommendations' => $recs,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo 'JSON=' . $jsonPath . PHP_EOL;

$sumEnf = 0;
$sumPend = 0;
$sumSens = 0;
$sumConf = 0;
$mehrdad = null;
$bizOwner = null;
$sysOwner = null;
foreach ($recs as $r) {
    $sumEnf += (int)($r['counts']['enforceable'] ?? 0);
    $sumPend += (int)($r['counts']['pending'] ?? 0);
    $sumSens += (int)($r['counts']['sensitive'] ?? 0);
    $sumConf += (int)($r['counts']['conflict'] ?? 0);
    if (($r['employee_code'] ?? '') === 'M360-1023') {
        $mehrdad = $r;
    }
    if (($r['employee_code'] ?? '') === 'M360-1007') {
        $bizOwner = $r;
    }
    if (($r['employee_code'] ?? '') === 'M360-100001') {
        $sysOwner = $r;
    }
}
echo "SUM_ENF={$sumEnf}\nSUM_PEND={$sumPend}\nSUM_SENS={$sumSens}\nSUM_CONF={$sumConf}\n";
echo 'MEHRDAD_CONFLICTS=' . count($mehrdad['conflicts'] ?? []) . PHP_EOL;
echo 'BIZ_OWNER_SYS=' . (!empty($bizOwner['is_system_owner']) ? 'yes' : 'no') . PHP_EOL;
echo 'SYS_OWNER=' . (!empty($sysOwner['is_system_owner']) ? 'yes' : 'no') . PHP_EOL;

$rolesAfter = m360_am_fetch_all($conn, 'SELECT COUNT(*) AS c FROM dbo.core_user_roles');
$ovAfter = m360_am_fetch_all($conn, 'SELECT COUNT(*) AS c FROM dbo.core_user_permission_overrides WHERE revoked_at IS NULL');
$wsAfter = m360_am_fetch_all($conn, "SELECT COUNT(*) AS c FROM dbo.core_permissions WHERE module_key=N'workshop' AND enforcement_state=N'ENFORCED'");
echo 'AFTER_ROLES=' . (int)($rolesAfter[0]['c'] ?? 0) . PHP_EOL;
echo 'AFTER_OVERRIDES=' . (int)($ovAfter[0]['c'] ?? 0) . PHP_EOL;
echo 'AFTER_WS_ENF=' . (int)($wsAfter[0]['c'] ?? 0) . PHP_EOL;

$ok = ((int)($rolesBefore[0]['c'] ?? 0) === (int)($rolesAfter[0]['c'] ?? 0))
    && ((int)($ovBefore[0]['c'] ?? 0) === (int)($ovAfter[0]['c'] ?? 0))
    && ((int)($wsBefore[0]['c'] ?? 0) === 37)
    && ((int)($wsAfter[0]['c'] ?? 0) === 37)
    && count($recs) === 25
    && count($mehrdad['conflicts'] ?? []) >= 1
    && empty($bizOwner['is_system_owner'])
    && !empty($sysOwner['is_system_owner']);

echo $ok ? "GENERATE_OK\n" : "GENERATE_FAIL\n";
exit($ok ? 0 : 1);
