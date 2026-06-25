<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

$c = false;
$scenarioId = isset($_GET['scenario_id']) ? (int)$_GET['scenario_id'] : 0;
$scenario = null;
$flow = null;
$checklist = [];
$scenarios = [];

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('db');
    }
    pilot_require_auth($c, 'pilot.view');
    if ($scenarioId > 0) {
        $scenario = pilot_get_scenario($c, $scenarioId);
        if ($scenario !== null) {
            $flow = pilot_get_latest_flow_snapshot($c, $scenarioId);
            $checklist = pilot_data_checklist($scenario, $flow);
        }
    } else {
        $scenarios = pilot_get_scenarios($c, 10);
        if ($scenarios !== []) {
            $scenarioId = (int)($scenarios[0]['scenario_id'] ?? 0);
            $scenario = pilot_get_scenario($c, $scenarioId);
            if ($scenario !== null) {
                $flow = pilot_get_latest_flow_snapshot($c, $scenarioId);
                $checklist = pilot_data_checklist($scenario, $flow);
            }
        }
    }
} catch (Throwable) {
    pilot_error('چک‌لیست داده', 'چک‌لیست داده Pilot قابل بارگذاری نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

pilot_render_head('چک‌لیست داده Pilot');
echo '<div class="p12pl-hero"><h1>چک‌لیست داده Pilot</h1><p>بررسی read-only آمادگی داده سناریو</p></div>';

if ($scenario === null) {
    echo '<div class="p1cc-card"><p>سناریویی برای بررسی وجود ندارد. <a href="erp-pilot-scenario-builder.php">ساخت سناریو</a></p></div>';
} else {
    echo '<div class="p1cc-card"><p>سناریو: <strong>' . pilot_h($scenario['scenario_code'] ?? '') . '</strong> — ' . pilot_h($scenario['customer_name'] ?? '') . '</p>';
    if (count($scenarios) > 1 || $scenarioId > 0) {
        echo '<p><a class="p1cc-btn" href="erp-pilot-scenario-builder.php">سناریوی جدید</a></p>';
    }
    echo '<table class="p1cc-table"><thead><tr><th>مورد</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($checklist as $item) {
        $st = (string)($item['status'] ?? 'PENDING');
        echo '<tr><td>' . pilot_h((string)($item['label'] ?? '')) . '</td>';
        echo '<td><span class="p12pl-badge ' . pilot_badge_class($st) . '">' . pilot_h($st) . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

if ($scenarios !== [] && $scenarioId === 0) {
    echo '<div class="p1cc-card"><h2 class="p12pl-section-title">سایر سناریوها</h2><ul>';
    foreach ($scenarios as $sc) {
        echo '<li><a href="?scenario_id=' . pilot_h($sc['scenario_id'] ?? '') . '">' . pilot_h($sc['scenario_code'] ?? '') . '</a> — ' . pilot_h($sc['customer_name'] ?? '') . '</li>';
    }
    echo '</ul></div>';
}

pilot_render_foot();
