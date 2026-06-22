<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 3 Service Approval Request Board
 */

require_once __DIR__ . '/includes/erp-rule-engine.php';

$filterStatus = strtoupper(rule_engine_get_string('status'));
$flashKey = rule_engine_get_string('phase3');
$flashMessage = match ($flashKey) {
    'approval_ok' => 'تصمیم تأیید با موفقیت ثبت شد.',
    default => '',
};

if ($filterStatus !== '' && !in_array($filterStatus, ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'], true)) {
    $filterStatus = '';
}

$connection = false;
$errorMessage = '';
$requests = [];

try {
    $connection = rule_engine_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    rule_engine_require_auth_and_guard($connection, 'rule.engine.approval.view');

    if (rule_engine_table_exists($connection, 'erp_service_approval_requests')) {
        $sql = 'SELECT TOP 50 approval_request_id, decision_id, operation_case_id, service_step_id,
                       contract_id, customer_id, approval_type, approval_status, requested_amount,
                       approval_reason, created_at
                FROM dbo.erp_service_approval_requests WHERE 1=1';
        $params = [];

        if ($filterStatus !== '') {
            $sql .= ' AND approval_status = ?';
            $params[] = $filterStatus;
        }

        $sql .= ' ORDER BY approval_request_id DESC';
        $requests = rule_engine_fetch_rows($connection, $sql, $params);
    }
} catch (Throwable) {
    $errorMessage = 'نمایش درخواست‌های تأیید با خطا مواجه شد.';
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}

rule_engine_render_head('درخواست‌های تأیید سرویس', false);

echo '<div class="p3re-hero">';
echo '<h1>درخواست‌های تأیید سرویس</h1>';
echo '<p>تأیید / رد کنترل‌شده داخلی — خارج از قرارداد، سقف مجوز، خرید قطعه</p>';
echo '</div>';

if ($flashMessage !== '') {
    echo '<div class="p1cc-flash">' . rule_engine_h($flashMessage) . '</div>';
}

echo '<div class="p1cc-card"><form method="get" class="p2oe-form-inline">';
echo '<div class="p1cc-form-group"><label class="p1cc-label">وضعیت</label><select class="p1cc-select" name="status">';
echo '<option value="">همه</option>';
foreach (['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'] as $st) {
    $sel = $filterStatus === $st ? ' selected' : '';
    echo '<option value="' . rule_engine_h($st) . '"' . $sel . '>' . rule_engine_h($st) . '</option>';
}
echo '</select></div><button class="p1cc-btn p1cc-btn-primary" type="submit">فیلتر</button></form></div>';

if ($errorMessage !== '') {
    echo '<div class="p1cc-card p1cc-error"><p>' . rule_engine_h($errorMessage) . '</p></div>';
} elseif ($requests === []) {
    echo '<div class="p1cc-card"><p class="p1cc-hint">درخواست تأییدی یافت نشد.</p></div>';
} else {
    foreach ($requests as $req) {
        $reqId = $req['approval_request_id'] ?? '';
        echo '<div class="p1cc-card">';
        echo '<div class="p2oe-meta-grid">';
        echo '<div class="p2oe-meta-item"><span>شناسه</span>' . rule_engine_h($reqId) . '</div>';
        echo '<div class="p2oe-meta-item"><span>نوع</span>' . rule_engine_h($req['approval_type'] ?? '') . '</div>';
        echo '<div class="p2oe-meta-item"><span>وضعیت</span><span class="p1cc-badge ' . rule_engine_badge_class($req['approval_status'] ?? '') . '">' . rule_engine_h($req['approval_status'] ?? '') . '</span></div>';
        echo '<div class="p2oe-meta-item"><span>پرونده عملیات</span>' . rule_engine_h($req['operation_case_id'] !== '' ? $req['operation_case_id'] : '—') . '</div>';
        echo '<div class="p2oe-meta-item"><span>مبلغ</span><span class="m360-num">' . rule_engine_h($req['requested_amount'] !== '' ? $req['requested_amount'] : '—') . '</span></div>';
        echo '</div>';

        if (($req['approval_reason'] ?? '') !== '') {
            echo '<p class="p1cc-hint" style="margin-top:0.5rem">' . rule_engine_h($req['approval_reason']) . '</p>';
        }

        if (($req['approval_status'] ?? '') === 'PENDING') {
            echo '<form method="post" action="submit-service-approval-request.php" style="margin-top:0.75rem" class="p2oe-form-inline">';
            echo erp_csrf_input('rule_approval_decide');
            echo '<input type="hidden" name="approval_request_id" value="' . rule_engine_h($reqId) . '">';
            echo '<div class="p1cc-form-group"><label class="p1cc-label">تصمیم</label><select class="p1cc-select" name="approval_status" required>';
            echo '<option value="APPROVED">APPROVED — تأیید</option>';
            echo '<option value="REJECTED">REJECTED — رد</option>';
            echo '<option value="CANCELLED">CANCELLED — لغو</option>';
            echo '</select></div>';
            echo '<div class="p1cc-form-group"><label class="p1cc-label">یادداشت داخلی</label><input class="p1cc-input" type="text" name="internal_note" maxlength="1500"></div>';
            echo '<button class="p1cc-btn p1cc-btn-primary" type="submit">ثبت تصمیم</button>';
            echo '</form>';
        }

        echo '</div>';
    }
}

rule_engine_render_foot();
