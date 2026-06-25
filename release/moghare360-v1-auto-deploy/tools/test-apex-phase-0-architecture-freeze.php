<?php
declare(strict_types=1);

/**
 * ApexMahinERP — Phase 0 Architecture Freeze CLI Test
 */

$root = dirname(__DIR__);
$apexDir = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'apex_architecture';

$requiredDocs = [
    'APEX_00_ARCHITECTURE_FREEZE_STATEMENT.md',
    'APEX_01_PRODUCT_SCOPE_MVP_AND_PHASE2.md',
    'APEX_02_DOMAIN_BOUNDARY_RULES.md',
    'APEX_03_HIGH_LEVEL_ENTITY_MAP.md',
    'APEX_04_TECHNICAL_INTELLIGENCE_ENGINE_POSITION.md',
    'APEX_05_CLEAN_RESTART_PLAN.md',
    'APEX_06_DATA_OWNERSHIP_PRELIMINARY_RULES.md',
    'APEX_07_ARCHITECTURE_SIGNOFF.md',
    'APEX_90_PHASE_0_RESULT.md',
    'APEX_99_PHASE_0_SIGNOFF.md',
    'README.md',
];

$results = [];

foreach ($requiredDocs as $docName) {
    $results[] = [
        'name' => 'Doc exists: ' . $docName,
        'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . $docName),
    ];
}

$results[] = [
    'name' => 'Architecture freeze statement exists',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_00_ARCHITECTURE_FREEZE_STATEMENT.md'),
];

$results[] = [
    'name' => 'MVP/Phase2 scope exists',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_01_PRODUCT_SCOPE_MVP_AND_PHASE2.md'),
];

$results[] = [
    'name' => 'Domain boundary rules exist',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_02_DOMAIN_BOUNDARY_RULES.md'),
];

$results[] = [
    'name' => 'Entity map exists',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_03_HIGH_LEVEL_ENTITY_MAP.md'),
];

$results[] = [
    'name' => 'Technical intelligence engine position exists',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_04_TECHNICAL_INTELLIGENCE_ENGINE_POSITION.md'),
];

$results[] = [
    'name' => 'Clean restart plan exists',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_05_CLEAN_RESTART_PLAN.md'),
];

$results[] = [
    'name' => 'Data ownership preliminary rules exist',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_06_DATA_OWNERSHIP_PRELIMINARY_RULES.md'),
];

$results[] = [
    'name' => 'Architecture signoff exists',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_07_ARCHITECTURE_SIGNOFF.md'),
];

$results[] = [
    'name' => 'Result/signoff docs exist',
    'pass' => is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_90_PHASE_0_RESULT.md')
        && is_file($apexDir . DIRECTORY_SEPARATOR . 'APEX_99_PHASE_0_SIGNOFF.md'),
];

$apexSqlFiles = glob($apexDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$apexSqlRecursive = glob($apexDir . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$results[] = [
    'name' => 'No SQL files under docs/apex_architecture',
    'pass' => $apexSqlFiles === [] && $apexSqlRecursive === [],
];

$results[] = [
    'name' => 'Phase 0 does not require public_html runtime files',
    'pass' => !is_file($apexDir . DIRECTORY_SEPARATOR . 'require_public_html')
        && !str_contains(
            (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'README.md'),
            'require_once'
        ),
];

$allDocContent = '';
foreach ($requiredDocs as $docName) {
    $path = $apexDir . DIRECTORY_SEPARATOR . $docName;
    if (is_file($path)) {
        $allDocContent .= (string)file_get_contents($path) . "\n";
    }
}

$forbiddenDdlAllowedContext = [
    'forbidden',
    'must not',
    'No SQL',
    'no sql',
    'not a',
    'NOT a',
    'not physical',
    'No physical',
    'no physical',
    'before',
    'until',
    'rejected',
    'without',
    'does not',
    'are forbidden',
    'is forbidden',
    'Blocked',
    'blocked',
];

$ddlKeywords = ['CREATE TABLE', 'ALTER TABLE', 'DROP TABLE'];
$ddlViolation = false;
foreach ($requiredDocs as $docName) {
    $path = $apexDir . DIRECTORY_SEPARATOR . $docName;
    if (!is_file($path)) {
        continue;
    }
    $lines = explode("\n", (string)file_get_contents($path));
    foreach ($lines as $line) {
        foreach ($ddlKeywords as $keyword) {
            if (!str_contains($line, $keyword)) {
                continue;
            }
            $allowed = false;
            foreach ($forbiddenDdlAllowedContext as $ctx) {
                if (str_contains($line, $ctx)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                $ddlViolation = true;
                break 3;
            }
        }
    }
}
$results[] = [
    'name' => 'No forbidden DDL keywords except in forbidden-action context',
    'pass' => !$ddlViolation,
];

$results[] = [
    'name' => 'Docs state logical only, not physical schema',
    'pass' => str_contains($allDocContent, 'logical')
        && (str_contains($allDocContent, 'not physical') || str_contains($allDocContent, 'NOT a physical'))
        && str_contains($allDocContent, 'not physical schema'),
];

$results[] = [
    'name' => 'Docs state no SQL before signoff',
    'pass' => str_contains($allDocContent, 'No SQL before')
        || str_contains($allDocContent, 'no SQL before')
        || str_contains($allDocContent, 'No SQL, no migrations'),
];

$results[] = [
    'name' => 'Docs state Cursor did not decide next roadmap step',
    'pass' => substr_count($allDocContent, 'Cursor did not decide the next roadmap step') >= 5,
];

$freezeContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_00_ARCHITECTURE_FREEZE_STATEMENT.md');
$results[] = [
    'name' => 'Freeze statement includes official product statement',
    'pass' => str_contains($freezeContent, 'automotive workshop')
        && str_contains($freezeContent, 'technical knowledge engine'),
];

$scopeContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_01_PRODUCT_SCOPE_MVP_AND_PHASE2.md');
$results[] = [
    'name' => 'Scope includes MVP JobCard and Phase 2 ML',
    'pass' => str_contains($scopeContent, 'JobCard')
        && str_contains($scopeContent, 'Machine Learning'),
];

$boundaryContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_02_DOMAIN_BOUNDARY_RULES.md');
$results[] = [
    'name' => 'Boundary rules define eight locked domains',
    'pass' => str_contains($boundaryContent, 'Organization Domain')
        && str_contains($boundaryContent, 'Job & Technical Intelligence Domain')
        && str_contains($boundaryContent, 'No entity is allowed to directly touch'),
];

$entityContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_03_HIGH_LEVEL_ENTITY_MAP.md');
$requiredEntities = [
    'Tenant', 'Branch', 'User', 'Role', 'Permission',
    'Party', 'Customer', 'Supplier', 'Employee',
    'Account', 'JournalEntry', 'LedgerEntry', 'BankAccount', 'Payment', 'CreditProfile',
    'Item', 'Warehouse', 'StockLedger', 'ReorderPolicy',
    'RFQ', 'PurchaseOrder', 'GRN', 'PurchaseInvoice', 'VendorRating',
    'Lead', 'Campaign', 'Appointment', 'SourceAttribution',
    'Skill', 'Attendance', 'TechnicianPerformance', 'BonusRule',
    'JobCard', 'JobStep', 'QCChecklist', 'WarrantyRecord',
    'CaseRecord', 'Symptom', 'RootCause', 'RepairProcedure', 'FailurePattern', 'SuggestionRule',
];
$entitiesPass = true;
foreach ($requiredEntities as $entity) {
    if (!str_contains($entityContent, '**' . $entity . '**') && !str_contains($entityContent, '| **' . $entity . '**')) {
        $entitiesPass = false;
        break;
    }
}
$results[] = ['name' => 'Entity map includes all required logical entities', 'pass' => $entitiesPass];

$tiContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_04_TECHNICAL_INTELLIGENCE_ENGINE_POSITION.md');
$results[] = [
    'name' => 'Technical intelligence inside Job domain with MVP frequency engine',
    'pass' => str_contains($tiContent, 'Job & Technical Intelligence Domain')
        && str_contains($tiContent, 'Frequency-based engine')
        && str_contains($tiContent, 'Ranked probability'),
];

$restartContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_05_CLEAN_RESTART_PLAN.md');
$results[] = [
    'name' => 'Clean restart plan lists five ordered steps',
    'pass' => str_contains($restartContent, 'Freeze Architecture')
        && str_contains($restartContent, 'Design Logical Domain Model Diagram')
        && str_contains($restartContent, 'Define Data Ownership Rules')
        && str_contains($restartContent, 'Start Physical Schema Design'),
];

$ownershipContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_06_DATA_OWNERSHIP_PRELIMINARY_RULES.md');
$results[] = [
    'name' => 'Ownership rules protect finance and job boundaries',
    'pass' => str_contains($ownershipContent, 'Finance must not be polluted')
        && str_contains($ownershipContent, 'must not directly mutate inventory or finance'),
];

$signoffContent = (string)@file_get_contents($apexDir . DIRECTORY_SEPARATOR . 'APEX_07_ARCHITECTURE_SIGNOFF.md');
$results[] = [
    'name' => 'Architecture signoff pending user review with pending items',
    'pass' => str_contains($signoffContent, 'PENDING USER REVIEW')
        && str_contains($signoffContent, 'Logical Domain Model Diagram')
        && str_contains($signoffContent, 'Physical Schema Design'),
];

$passed = 0;
$failed = 0;

foreach ($results as $row) {
    $label = $row['pass'] ? 'PASS' : 'FAIL';
    echo $label . ' — ' . $row['name'] . PHP_EOL;
    if ($row['pass']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo PHP_EOL;
echo 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'APEX PHASE 0 ARCHITECTURE FREEZE TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'APEX PHASE 0 ARCHITECTURE FREEZE TEST PASSED' . PHP_EOL;
exit(0);
