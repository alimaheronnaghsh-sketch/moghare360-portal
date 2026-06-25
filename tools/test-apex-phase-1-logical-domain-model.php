<?php
declare(strict_types=1);

/**
 * ApexMahinERP — Phase 1 Logical Domain Model CLI Test
 */

$root = dirname(__DIR__);
$phase1Dir = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'apex_architecture'
    . DIRECTORY_SEPARATOR . 'phase_1_logical_domain_model';

$requiredDocs = [
    'APEX_10_LOGICAL_DOMAIN_MODEL_OVERVIEW.md',
    'APEX_11_DOMAIN_MODEL_ORGANIZATION.md',
    'APEX_12_DOMAIN_MODEL_IDENTITY_ACCESS.md',
    'APEX_13_DOMAIN_MODEL_FINANCE.md',
    'APEX_14_DOMAIN_MODEL_PROCUREMENT.md',
    'APEX_15_DOMAIN_MODEL_INVENTORY.md',
    'APEX_16_DOMAIN_MODEL_CRM_MARKETING.md',
    'APEX_17_DOMAIN_MODEL_HR.md',
    'APEX_18_DOMAIN_MODEL_JOB_TECHNICAL_INTELLIGENCE.md',
    'APEX_19_CROSS_DOMAIN_INTERACTION_MAP.md',
    'APEX_20_SERVICE_BOUNDARY_PREVIEW.md',
    'APEX_21_LOGICAL_MODEL_REVIEW_NOTES.md',
    'APEX_90_PHASE_1_RESULT.md',
    'APEX_99_PHASE_1_SIGNOFF.md',
    'README.md',
];

$results = [];

foreach ($requiredDocs as $docName) {
    $results[] = [
        'name' => 'Doc exists: ' . $docName,
        'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . $docName),
    ];
}

$results[] = [
    'name' => 'Overview exists',
    'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_10_LOGICAL_DOMAIN_MODEL_OVERVIEW.md'),
];

$domainDocs = [
    'APEX_11_DOMAIN_MODEL_ORGANIZATION.md',
    'APEX_12_DOMAIN_MODEL_IDENTITY_ACCESS.md',
    'APEX_13_DOMAIN_MODEL_FINANCE.md',
    'APEX_14_DOMAIN_MODEL_PROCUREMENT.md',
    'APEX_15_DOMAIN_MODEL_INVENTORY.md',
    'APEX_16_DOMAIN_MODEL_CRM_MARKETING.md',
    'APEX_17_DOMAIN_MODEL_HR.md',
    'APEX_18_DOMAIN_MODEL_JOB_TECHNICAL_INTELLIGENCE.md',
];
$allDomainsExist = true;
foreach ($domainDocs as $domainDoc) {
    if (!is_file($phase1Dir . DIRECTORY_SEPARATOR . $domainDoc)) {
        $allDomainsExist = false;
        break;
    }
}
$results[] = ['name' => 'All 8 domain model docs exist', 'pass' => $allDomainsExist];

$results[] = [
    'name' => 'Cross-domain interaction map exists',
    'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_19_CROSS_DOMAIN_INTERACTION_MAP.md'),
];

$results[] = [
    'name' => 'Service boundary preview exists',
    'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_20_SERVICE_BOUNDARY_PREVIEW.md'),
];

$results[] = [
    'name' => 'Review notes exist',
    'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_21_LOGICAL_MODEL_REVIEW_NOTES.md'),
];

$results[] = [
    'name' => 'Result/signoff docs exist',
    'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_90_PHASE_1_RESULT.md')
        && is_file($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_99_PHASE_1_SIGNOFF.md'),
];

$results[] = [
    'name' => 'README exists',
    'pass' => is_file($phase1Dir . DIRECTORY_SEPARATOR . 'README.md'),
];

$phase1Sql = glob($phase1Dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$results[] = [
    'name' => 'No SQL files in Phase 1 docs folder',
    'pass' => $phase1Sql === [],
];

$results[] = [
    'name' => 'Phase 1 does not require public_html runtime files',
    'pass' => !str_contains(
        (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'README.md'),
        'require_once'
    ),
];

$allContent = '';
foreach ($requiredDocs as $docName) {
    $path = $phase1Dir . DIRECTORY_SEPARATOR . $docName;
    if (is_file($path)) {
        $allContent .= (string)file_get_contents($path) . "\n";
    }
}

$results[] = [
    'name' => 'Docs contain logical-only statements',
    'pass' => str_contains($allContent, 'Logical only')
        && str_contains($allContent, 'Not physical schema'),
];

$results[] = [
    'name' => 'Docs state no SQL',
    'pass' => str_contains($allContent, 'No SQL'),
];

$ddlKeywords = ['CREATE TABLE', 'ALTER TABLE', 'DROP TABLE'];
$ddlFound = false;
foreach ($ddlKeywords as $keyword) {
    if (str_contains($allContent, $keyword)) {
        $ddlFound = true;
        break;
    }
}
$results[] = [
    'name' => 'Docs do not contain CREATE/ALTER/DROP TABLE',
    'pass' => !$ddlFound,
];

$physicalColumnPatterns = [
    '/\bvarchar\s*\(/i',
    '/\bnvarchar\s*\(/i',
    '/\bint\s+primary\s+key\b/i',
    '/\bnot\s+null\b/i',
    '/\bforeign\s+key\s+references\b/i',
];
$physicalColumnFound = false;
foreach ($physicalColumnPatterns as $pattern) {
    if (preg_match($pattern, $allContent) === 1) {
        $physicalColumnFound = true;
        break;
    }
}
$results[] = [
    'name' => 'Docs do not define physical columns',
    'pass' => !$physicalColumnFound,
];

$results[] = [
    'name' => 'Docs state service/API boundary',
    'pass' => str_contains($allContent, 'service/API')
        || str_contains($allContent, 'service/API boundary'),
];

$results[] = [
    'name' => 'Docs state no direct cross-domain table access',
    'pass' => str_contains($allContent, 'No direct cross-domain table access')
        || str_contains($allContent, 'no direct cross-domain table access'),
];

$results[] = [
    'name' => 'Docs state no DB change',
    'pass' => str_contains($allContent, 'No DB change'),
];

$results[] = [
    'name' => 'Docs state no runtime change',
    'pass' => str_contains($allContent, 'No runtime change'),
];

$results[] = [
    'name' => 'Docs state Cursor did not decide next roadmap step',
    'pass' => substr_count($allContent, 'Cursor did not decide the next roadmap step') >= 8,
];

$overview = (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_10_LOGICAL_DOMAIN_MODEL_OVERVIEW.md');
$results[] = [
    'name' => 'Overview states Clean Restart Step 2',
    'pass' => str_contains($overview, 'Step 2')
        && str_contains($overview, 'Design Logical Domain Model Diagram'),
];

$crossDomain = (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_19_CROSS_DOMAIN_INTERACTION_MAP.md');
$results[] = [
    'name' => 'Cross-domain map includes required interaction examples',
    'pass' => str_contains($crossDomain, 'inventory reservation')
        && str_contains($crossDomain, 'prepayment')
        && str_contains($crossDomain, 'GRN')
        && str_contains($crossDomain, 'technician skill'),
];

$servicePreview = (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_20_SERVICE_BOUNDARY_PREVIEW.md');
$requiredServices = [
    'OrganizationService',
    'IdentityAccessService',
    'FinanceService',
    'ProcurementService',
    'InventoryService',
    'CRMService',
    'HRService',
    'JobTechnicalService',
    'TechnicalIntelligenceService',
];
$servicesPass = true;
foreach ($requiredServices as $service) {
    if (!str_contains($servicePreview, $service)) {
        $servicesPass = false;
        break;
    }
}
$results[] = ['name' => 'Service preview lists all required services', 'pass' => $servicesPass];

$jobModel = (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_18_DOMAIN_MODEL_JOB_TECHNICAL_INTELLIGENCE.md');
$results[] = [
    'name' => 'Job domain includes gates and technical intelligence MVP',
    'pass' => str_contains($jobModel, 'PrepaymentGate')
        && str_contains($jobModel, 'DeliveryGate')
        && str_contains($jobModel, 'Frequency-based'),
];

$reviewNotes = (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_21_LOGICAL_MODEL_REVIEW_NOTES.md');
$results[] = [
    'name' => 'Review notes block physical schema until ownership approved',
    'pass' => str_contains($reviewNotes, 'No physical schema')
        && str_contains($reviewNotes, 'ownership matrix'),
];

$signoff = (string)@file_get_contents($phase1Dir . DIRECTORY_SEPARATOR . 'APEX_99_PHASE_1_SIGNOFF.md');
$results[] = [
    'name' => 'Signoff pending user review',
    'pass' => str_contains($signoff, 'PENDING USER REVIEW'),
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
    fwrite(STDERR, 'APEX PHASE 1 LOGICAL DOMAIN MODEL TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'APEX PHASE 1 LOGICAL DOMAIN MODEL TEST PASSED' . PHP_EOL;
exit(0);
