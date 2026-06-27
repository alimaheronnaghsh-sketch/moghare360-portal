<?php
declare(strict_types=1);

/**
 * MOGHARE360 P8 — Bottleneck monitor (read-only stage pressure).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';

/** @return list<string> */
function m360_bottleneck_stages(): array
{
    return [
        M360_MGMT_STAGE_ONLINE,
        M360_MGMT_STAGE_CONTRACT,
        M360_MGMT_STAGE_RECEPTION,
        M360_MGMT_STAGE_TECHNICAL,
        M360_MGMT_STAGE_ESTIMATE,
        M360_MGMT_STAGE_PARTS,
        M360_MGMT_STAGE_FINANCE,
        M360_MGMT_STAGE_WORK,
        M360_MGMT_STAGE_QC,
        M360_MGMT_STAGE_DELIVERY_READY,
        M360_MGMT_STAGE_FINAL_INVOICE,
        M360_MGMT_STAGE_SETTLEMENT,
        M360_MGMT_STAGE_CUSTOMER_DELIVERY,
        M360_MGMT_STAGE_CLOSED,
    ];
}

/**
 * @return array<string, array<string, mixed>>
 */
function m360_bottleneck_summary($conn): array
{
    $rows = m360_mgmt_fetch_pipeline($conn, 500);
    $summary = [];

    foreach (m360_bottleneck_stages() as $stage) {
        $summary[$stage] = [
            'stage' => $stage,
            'label_fa' => M360_MGMT_STAGE_LABELS_FA[$stage] ?? $stage,
            'count' => 0,
            'avg_age_hours' => 0.0,
            'oldest_jobcard_id' => 0,
            'oldest_age_hours' => 0.0,
            'critical' => false,
            'stuck_rows' => [],
        ];
    }

    foreach ($rows as $row) {
        $stage = (string)($row['current_stage'] ?? M360_MGMT_STAGE_RECEPTION);
        if (!isset($summary[$stage])) {
            continue;
        }
        $summary[$stage]['count']++;
        $age = (float)($row['age_hours'] ?? 0);
        $summary[$stage]['avg_age_hours'] += $age;
        if ($age >= (float)($summary[$stage]['oldest_age_hours'])) {
            $summary[$stage]['oldest_age_hours'] = $age;
            $summary[$stage]['oldest_jobcard_id'] = (int)($row['jobcard_id'] ?? 0);
        }
        if (!m360_mgmt_is_closed($row) && ($age >= 48 || ($row['risk_flags'] ?? []) !== [])) {
            $summary[$stage]['stuck_rows'][] = $row;
        }
    }

    $maxCount = 0;
    $maxStage = '';
    foreach ($summary as $stage => &$data) {
        if ($data['count'] > 0) {
            $data['avg_age_hours'] = round($data['avg_age_hours'] / $data['count'], 1);
        }
        $data['critical'] = $data['count'] >= 5 && $data['avg_age_hours'] >= 48;
        if ($data['count'] > $maxCount && $stage !== M360_MGMT_STAGE_CLOSED) {
            $maxCount = $data['count'];
            $maxStage = $stage;
        }
        $data['stuck_rows'] = array_slice($data['stuck_rows'], 0, 10);
    }
    unset($data);

    return [
        'stages' => $summary,
        'highest_pressure_stage' => $maxStage,
        'highest_pressure_count' => $maxCount,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_bottleneck_stuck_list($conn, int $limit = 40): array
{
    $rows = m360_mgmt_fetch_pipeline($conn, 500);
    $stuck = [];
    foreach ($rows as $row) {
        if (m360_mgmt_is_closed($row)) {
            continue;
        }
        $age = (float)($row['age_hours'] ?? 0);
        if ($age >= 24 || ($row['risk_flags'] ?? []) !== []) {
            $stuck[] = $row;
        }
    }
    usort($stuck, static fn(array $a, array $b): int => ((float)($b['age_hours'] ?? 0) <=> (float)($a['age_hours'] ?? 0)));
    return array_slice($stuck, 0, max(1, min(100, $limit)));
}
