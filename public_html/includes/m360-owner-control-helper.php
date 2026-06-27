<?php
declare(strict_types=1);

/**
 * MOGHARE360 P8 — Owner control center (read-only risk lists).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';

/** @return list<string> */
function m360_owner_control_sections(): array
{
    return [
        'high_risk',
        'overdue',
        'unpaid',
        'delivery_ready_unpaid',
        'qc_failed',
        'manager_release',
        'status_conflict',
        'inactive',
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_owner_control_list($conn, string $section, int $limit = 50): array
{
    $rows = m360_mgmt_fetch_pipeline($conn, 500);
    $limit = max(1, min(100, $limit));
    $filtered = [];

    foreach ($rows as $row) {
        if (m360_owner_row_matches_section($row, $section)) {
            $filtered[] = $row;
        }
    }

    usort($filtered, static fn(array $a, array $b): int => ((float)($b['age_hours'] ?? 0) <=> (float)($a['age_hours'] ?? 0)));
    return array_slice($filtered, 0, $limit);
}

/**
 * @param array<string, mixed> $row
 */
function m360_owner_row_matches_section(array $row, string $section): bool
{
    if (m360_mgmt_is_closed($row) && !in_array($section, ['status_conflict', 'inactive'], true)) {
        return false;
    }

    $flags = $row['risk_flags'] ?? [];
    return match ($section) {
        'high_risk' => $flags !== [],
        'overdue' => !empty($row['is_overdue_24']) || !empty($row['is_overdue_48']) || !empty($row['is_overdue_72']),
        'unpaid' => (float)($row['settlement_remaining_amount'] ?? $row['remaining_amount'] ?? 0) > 0
            && strtoupper(trim((string)($row['settlement_status'] ?? ''))) !== 'SETTLED',
        'delivery_ready_unpaid' => in_array('DELIVERY_READY_UNPAID', $flags, true),
        'qc_failed' => in_array('QC_FAILED', $flags, true) || in_array('REWORK_REQUIRED', $flags, true),
        'manager_release' => in_array('MANAGER_RELEASE', $flags, true),
        'status_conflict' => m360_mgmt_status_conflicts($row) !== [],
        'inactive' => !m360_mgmt_is_closed($row) && (float)($row['age_hours'] ?? 0) >= 72,
        default => false,
    };
}

/** @var array<string, string> */
const M360_OWNER_SECTION_LABELS_FA = [
    'high_risk' => 'پرونده‌های پرریسک',
    'overdue' => 'پرونده‌های معوق',
    'unpaid' => 'پرونده‌های دارای مانده',
    'delivery_ready_unpaid' => 'آماده تحویل — تسویه نشده',
    'qc_failed' => 'QC ناموفق / Rework',
    'manager_release' => 'مجوز خروج مدیر',
    'status_conflict' => 'ناسازگاری وضعیت',
    'inactive' => 'بدون فعالیت اخیر',
];

/**
 * @return array<string, int>
 */
function m360_owner_control_counts($conn): array
{
    $counts = [];
    foreach (m360_owner_control_sections() as $section) {
        $counts[$section] = count(m360_owner_control_list($conn, $section, 500));
    }
    return $counts;
}
