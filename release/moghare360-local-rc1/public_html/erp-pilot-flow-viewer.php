<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

$c = false;
$scenarioId = isset($_GET['scenario_id']) ? (int)$_GET['scenario_id'] : 0;
$scenario = null;
$flow = null;
$scenarios = [];
$flash = isset($_GET['ok']) ? pilot_flash((string)$_GET['ok']) : '';
if (isset($_GET['dup']) && $_GET['dup'] === '1') {
    $flash = pilot_flash('scenario_dup');
}

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('db');
    }
    pilot_require_auth($c, 'pilot.view');
    if ($scenarioId > 0) {
        $scenario = pilot_get_scenario($c, $scenarioId);
        $flow = pilot_get_latest_flow_snapshot($c, $scenarioId);
    } else {
        $scenarios = pilot_get_scenarios($c, 15);
    }
} catch (Throwable) {
    pilot_error('نمایش جریان', 'صفحه Flow Viewer قابل بارگذاری نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

pilot_render_head('نمایش جریان Pilot');
echo '<div class="p12pl-hero"><h1>نمایش جریان Pilot</h1><p>Pilot Flow — read-only</p></div>';

echo '<div class="p12pl-boundary-box"><ul>';
foreach (['Internal Pilot Only', 'No Production Write', 'No Public Portal'] as $b) {
    echo '<li>' . pilot_h($b) . '</li>';
}
echo '</ul></div>';

if ($flash !== '') {
    $cls = str_contains($flash, 'هشدار') ? 'p12pl-flash-warn' : 'p12pl-flash-ok';
    echo '<div class="' . $cls . '">' . pilot_h($flash) . '</div>';
}

if ($scenario === null && $scenarioId > 0) {
    echo '<div class="p1cc-card p1cc-error"><p>سناریو یافت نشد.</p></div>';
} elseif ($scenario !== null) {
    echo '<div class="p1cc-card"><h2 class="p12pl-section-title">سناریو: ' . pilot_h($scenario['scenario_code'] ?? '') . '</h2>';
    echo '<table class="p1cc-table"><tbody>';
    $fields = [
        'customer_name' => 'مشتری', 'mobile' => 'موبایل', 'vehicle_plate' => 'پلاک',
        'vehicle_brand_model' => 'خودرو', 'contract_type' => 'قرارداد', 'authorization_mode' => 'مجوز',
        'jobcard_service_description' => 'خدمت', 'payment_preview_amount' => 'مبلغ preview',
    ];
    foreach ($fields as $k => $label) {
        $val = $scenario[$k] ?? '';
        if ($k === 'payment_preview_amount') {
            $val = pilot_format_amount((string)$val);
        }
        echo '<tr><td>' . pilot_h($label) . '</td><td>' . pilot_h((string)$val) . '</td></tr>';
    }
    echo '</tbody></table></div>';

    echo '<div class="p1cc-card"><h2 class="p12pl-section-title">جریان Pilot</h2><ol class="p12pl-flow-timeline">';
    $i = 1;
    foreach (pilot_flow_steps() as $step) {
        $key = (string)$step['key'];
        $st = (string)($flow[$key] ?? 'PENDING');
        echo '<li class="p12pl-flow-step"><span class="p12pl-flow-num m360-num">' . $i . '</span><div>';
        echo '<strong>' . pilot_h((string)$step['title_fa']) . '</strong> (' . pilot_h((string)$step['title']) . ') ';
        echo '<span class="p12pl-badge ' . pilot_badge_class($st) . '">' . pilot_h($st) . '</span></div></li>';
        $i++;
    }
    echo '</ol>';
    if ($flow !== null) {
        echo '<p>Flow Decision: <span class="p12pl-badge ' . pilot_badge_class((string)($flow['flow_decision'] ?? 'PENDING')) . '">' . pilot_h((string)($flow['flow_decision'] ?? 'PENDING')) . '</span></p>';
        if (trim((string)($flow['flow_note'] ?? '')) !== '') {
            echo '<p class="p12pl-warning-box">' . pilot_h((string)$flow['flow_note']) . '</p>';
        }
    }
    echo '</div>';
    echo '<p><a class="p1cc-btn" href="erp-pilot-data-checklist.php?scenario_id=' . pilot_h((string)$scenarioId) . '">چک‌لیست داده</a> ';
    echo '<a class="p1cc-btn" href="erp-pilot-feedback.php?scenario_id=' . pilot_h((string)$scenarioId) . '">ثبت بازخورد</a></p>';
} else {
    echo '<div class="p1cc-card"><h2 class="p12pl-section-title">لیست سناریوها</h2>';
    if ($scenarios === []) {
        echo '<p>هنوز سناریویی ثبت نشده. <a href="erp-pilot-scenario-builder.php">ساخت سناریو</a></p>';
    } else {
        echo '<table class="p1cc-table"><thead><tr><th>کد</th><th>مشتری</th><th>وضعیت</th><th>مشاهده</th></tr></thead><tbody>';
        foreach ($scenarios as $sc) {
            echo '<tr><td>' . pilot_h($sc['scenario_code'] ?? '') . '</td>';
            echo '<td>' . pilot_h($sc['customer_name'] ?? '') . '</td>';
            echo '<td><span class="p12pl-badge ' . pilot_badge_class((string)($sc['scenario_status'] ?? '')) . '">' . pilot_h($sc['scenario_status'] ?? '') . '</span></td>';
            echo '<td><a href="?scenario_id=' . pilot_h($sc['scenario_id'] ?? '') . '">مشاهده</a></td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

pilot_render_foot();
