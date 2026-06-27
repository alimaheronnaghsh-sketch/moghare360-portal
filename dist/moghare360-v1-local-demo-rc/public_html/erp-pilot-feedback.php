<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-pilot-helper.php';

$c = false;
$scenarios = [];
$recentFeedback = [];
$scenarioId = isset($_GET['scenario_id']) ? (int)$_GET['scenario_id'] : 0;
$flash = isset($_GET['ok']) ? pilot_flash((string)$_GET['ok']) : '';

try {
    $c = pilot_db();
    if ($c === false) {
        throw new RuntimeException('db');
    }
    pilot_require_auth($c, 'pilot.view');
    $scenarios = pilot_get_scenarios($c, 20);
    if (pilot_table_exists($c, 'erp_soft_run_pilot_feedback')) {
        $recentFeedback = pilot_fetch_rows(
            $c,
            'SELECT TOP 10 feedback_id, scenario_id, feedback_role, page_or_module, issue_type, severity, feedback_note, feedback_status, created_at
             FROM dbo.erp_soft_run_pilot_feedback ORDER BY feedback_id DESC'
        );
    }
} catch (Throwable) {
    pilot_error('بازخورد Pilot', 'صفحه بازخورد قابل بارگذاری نیست.');
} finally {
    if ($c !== false) {
        @odbc_close($c);
    }
}

pilot_render_head('بازخورد Pilot');
echo '<div class="p12pl-hero"><h1>بازخورد Pilot</h1><p>ثبت مشکلات، UX و نیاز آموزشی — بدون تغییر production</p></div>';

if ($flash !== '') {
    echo '<div class="p12pl-flash-ok">' . pilot_h($flash) . '</div>';
}

echo '<div class="p1cc-card"><form method="post" action="submit-pilot-feedback.php" class="p12pl-form-grid">';
echo pilot_csrf_input(PILOT_CSRF_FEEDBACK_CREATE);
echo '<div class="p12pl-form-row"><label>سناریو (اختیاری)</label><select name="scenario_id"><option value="">—</option>';
foreach ($scenarios as $sc) {
    $sid = (string)($sc['scenario_id'] ?? '');
    $sel = ($scenarioId > 0 && (int)$sid === $scenarioId) ? ' selected' : '';
    echo '<option value="' . pilot_h($sid) . '"' . $sel . '>' . pilot_h($sc['scenario_code'] ?? '') . ' — ' . pilot_h($sc['customer_name'] ?? '') . '</option>';
}
echo '</select></div>';
echo '<div class="p12pl-form-row"><label>نقش *</label><select name="feedback_role" required>';
foreach (['owner', 'reception', 'technician', 'warehouse', 'finance', 'crm', 'hr', 'manager'] as $role) {
    echo '<option value="' . pilot_h($role) . '">' . pilot_h($role) . '</option>';
}
echo '</select></div>';
echo '<div class="p12pl-form-row"><label>صفحه / ماژول</label><input type="text" name="page_or_module" maxlength="200"></div>';
echo '<div class="p12pl-form-row"><label>نوع مسئله *</label><select name="issue_type" required>';
foreach (['bug', 'ux', 'missing_flow', 'data_problem', 'training_need', 'other'] as $type) {
    echo '<option value="' . pilot_h($type) . '">' . pilot_h($type) . '</option>';
}
echo '</select></div>';
echo '<div class="p12pl-form-row"><label>شدت *</label><select name="severity" required>';
foreach (['low', 'medium', 'high', 'blocker'] as $sev) {
    echo '<option value="' . pilot_h($sev) . '">' . pilot_h($sev) . '</option>';
}
echo '</select></div>';
echo '<div class="p12pl-form-row"><label>توضیح بازخورد *</label><textarea name="feedback_note" required maxlength="2000"></textarea></div>';
echo '<div class="p12pl-form-row"><label>پیشنهاد اصلاح</label><textarea name="suggested_fix" maxlength="2000"></textarea></div>';
echo '<p><button type="submit" class="p1cc-btn p1cc-btn-primary">ثبت بازخورد</button></p>';
echo '</form></div>';

if ($recentFeedback !== []) {
    echo '<div class="p1cc-card"><h2 class="p12pl-section-title">آخرین بازخوردها</h2><table class="p1cc-table"><thead><tr>';
    echo '<th>نقش</th><th>نوع</th><th>شدت</th><th>یادداشت</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($recentFeedback as $fb) {
        echo '<tr><td>' . pilot_h($fb['feedback_role'] ?? '') . '</td>';
        echo '<td>' . pilot_h($fb['issue_type'] ?? '') . '</td>';
        echo '<td><span class="p12pl-badge ' . pilot_severity_badge((string)($fb['severity'] ?? 'low')) . '">' . pilot_h($fb['severity'] ?? '') . '</span></td>';
        echo '<td>' . pilot_h(mb_substr((string)($fb['feedback_note'] ?? ''), 0, 80)) . '</td>';
        echo '<td>' . pilot_h($fb['feedback_status'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

pilot_render_foot();
