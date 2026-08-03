<?php
declare(strict_types=1);

/**
 * Workshop service sales lines — technician no-price entry + hall-manager IRR pricing.
 * Distinct from erp_service_operations (operational execution).
 */

require_once __DIR__ . '/m360-workshop-access-enforcement.php';
require_once __DIR__ . '/m360-workshop-work-item-helper.php';

/** @var list<string> */
const M360_WS_SL_CATEGORIES = [
    'PERIODIC_SERVICE',
    'ENGINE',
    'TRANSMISSION',
    'SUSPENSION',
    'ELECTRICAL',
    'OPTIONS',
    'PREPURCHASE_INSPECTION',
];

/** @var array<string,string> */
const M360_WS_SL_CATEGORY_LABELS_FA = [
    'PERIODIC_SERVICE' => 'سرویس‌های دوره‌ای',
    'ENGINE' => 'خدمات موتور',
    'TRANSMISSION' => 'خدمات گیربکس',
    'SUSPENSION' => 'خدمات زیروبند و تعلیق',
    'ELECTRICAL' => 'خدمات برق',
    'OPTIONS' => 'خدمات آپشن',
    'PREPURCHASE_INSPECTION' => 'کارشناسی خرید/فروش',
];

/** @var array<string,list<string>> operational family → allowed sales categories */
const M360_WS_SL_FAMILY_CATEGORY_MAP = [
    'PERIODIC_SERVICE' => ['PERIODIC_SERVICE'],
    'MECHANICAL' => ['ENGINE', 'TRANSMISSION', 'SUSPENSION'],
    'ELECTRICAL_OPTIONS' => ['ELECTRICAL', 'OPTIONS'],
    'INSPECTION' => ['PREPURCHASE_INSPECTION'],
];

/** @var list<string> */
const M360_WS_SL_STATUSES = [
    'DRAFT', 'SUBMITTED', 'RETURNED', 'TECHNICALLY_APPROVED', 'PRICING_PENDING',
    'PRICED', 'READY_FOR_INVOICE', 'INVOICED', 'VOIDED',
];

function m360_ws_sl_category_label_fa(string $code): string
{
    $code = strtoupper(trim($code));
    return M360_WS_SL_CATEGORY_LABELS_FA[$code] ?? $code;
}

function m360_ws_sl_display_title(string $category, string $title): string
{
    return m360_ws_sl_category_label_fa($category) . ': ' . trim($title);
}

/**
 * Normalize money input to positive BIGINT IRR or null on failure.
 * Accepts Persian/English digits and thousand separators (UI-only).
 *
 * @return array{ok:bool,value:?int,message:string}
 */
function m360_ws_sl_parse_price_irr(mixed $raw): array
{
    if ($raw === null || $raw === '') {
        return ['ok' => false, 'value' => null, 'message' => 'مبلغ الزامی است.'];
    }
    $s = trim((string)$raw);
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $s = str_replace($persian, $latin, $s);
    $s = str_replace([' ', ',', '،', '٬', '﷼', 'ریال'], '', $s);
    if ($s === '' || !preg_match('/^-?\d+$/', $s)) {
        return ['ok' => false, 'value' => null, 'message' => 'مبلغ باید عدد صحیح ریال باشد.'];
    }
    if (str_starts_with($s, '-')) {
        return ['ok' => false, 'value' => null, 'message' => 'مبلغ منفی مجاز نیست.'];
    }
    if ($s === '0') {
        return ['ok' => false, 'value' => null, 'message' => 'مبلغ صفر مجاز نیست.'];
    }
    if (strlen($s) > 18) {
        return ['ok' => false, 'value' => null, 'message' => 'مبلغ از سقف مجاز بیشتر است.'];
    }
    // Keep as string-safe int within PHP int on 64-bit; reject overflow vs BIGINT max roughly.
    if (strlen($s) === 19 && $s > '9223372036854775807') {
        return ['ok' => false, 'value' => null, 'message' => 'مبلغ از سقف BIGINT بیشتر است.'];
    }
    return ['ok' => true, 'value' => (int)$s, 'message' => ''];
}

function m360_ws_sl_format_price_irr(?int $amount): string
{
    if ($amount === null || $amount <= 0) {
        return '—';
    }
    $formatted = number_format($amount, 0, '', ',');
    $persian = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($persian, $fa, $formatted) . ' ریال';
}

function m360_ws_sl_category_allowed_for_family(string $family, string $category): bool
{
    $family = strtoupper(trim($family));
    $category = strtoupper(trim($category));
    $allowed = M360_WS_SL_FAMILY_CATEGORY_MAP[$family] ?? [];
    return in_array($category, $allowed, true);
}

function m360_ws_sl_fetch($conn, int $serviceLineId): ?array
{
    if ($serviceLineId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_workshop_service_lines WHERE service_line_id=?',
        [$serviceLineId]
    );
    return $rows[0] ?? null;
}

/** @return list<array<string,mixed>> */
function m360_ws_sl_list_for_jobcard($conn, int $jobcardId): array
{
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.erp_workshop_service_lines WHERE jobcard_id=? ORDER BY service_line_id DESC',
        [$jobcardId]
    );
}

/** @return list<array<string,mixed>> */
function m360_ws_sl_list_submitted_queue($conn, int $companyId, bool $isOwner, int $limit = 100): array
{
    $limit = max(1, min(200, $limit));
    $sql = "SELECT TOP {$limit} sl.*
            FROM dbo.erp_workshop_service_lines sl
            WHERE sl.status IN (N'SUBMITTED', N'TECHNICALLY_APPROVED', N'PRICING_PENDING', N'PRICED')";
    $params = [];
    if (!$isOwner) {
        $sql .= ' AND sl.company_id=?';
        $params[] = $companyId;
    }
    $sql .= ' ORDER BY sl.submitted_at ASC, sl.service_line_id ASC';
    return customer_core_fetch_rows($conn, $sql, $params);
}

function m360_ws_sl_history_add(
    $conn,
    int $serviceLineId,
    int $companyId,
    int $jobcardId,
    int $workItemId,
    string $event,
    ?string $oldStatus,
    ?string $newStatus,
    ?int $oldPrice,
    ?int $newPrice,
    string $note,
    int $actorId
): void {
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.erp_workshop_service_line_history
            (service_line_id, company_id, jobcard_id, work_item_id, event_name, old_status, new_status,
             old_price_irr, new_price_irr, event_note, actor_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $serviceLineId, $companyId, $jobcardId, $workItemId, $event,
            $oldStatus, $newStatus, $oldPrice, $newPrice,
            $note !== '' ? $note : null, $actorId,
        ]
    );
}

/**
 * Strip price fields from array for technician-safe responses.
 *
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function m360_ws_sl_public_row(array $row, bool $canViewPrice): array
{
    $out = $row;
    if (!$canViewPrice) {
        unset($out['price_irr'], $out['priced_by_user_id'], $out['priced_at'], $out['price_change_reason']);
    }
    $out['display_title'] = m360_ws_sl_display_title(
        (string)($row['sales_category'] ?? ''),
        (string)($row['service_title'] ?? '')
    );
    if ($canViewPrice && isset($row['price_irr']) && $row['price_irr'] !== null) {
        $out['price_irr_display'] = m360_ws_sl_format_price_irr((int)$row['price_irr']);
    }
    return $out;
}

function m360_ws_sl_assert_work_item($conn, int $companyId, int $jobcardId, int $workItemId): ?array
{
    if ($workItemId < 1 || $jobcardId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_workshop_work_items WHERE work_item_id=? AND jobcard_id=? AND company_id=?',
        [$workItemId, $jobcardId, $companyId]
    );
    return $rows[0] ?? null;
}

function m360_ws_sl_actor_assigned_to_work_item($conn, int $workItemId, int $actorUserId, bool $isOwner): bool
{
    if ($isOwner || $actorUserId < 1) {
        return $isOwner;
    }
    $wi = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 assigned_technician_user_id FROM dbo.erp_workshop_work_items WHERE work_item_id=?',
        [$workItemId]
    );
    if ($wi !== [] && (int)($wi[0]['assigned_technician_user_id'] ?? 0) === $actorUserId) {
        return true;
    }
    if (customer_core_table_exists($conn, 'erp_jobcard_assignments')) {
        $a = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 1 assignment_id FROM dbo.erp_jobcard_assignments
             WHERE work_item_id=? AND assigned_to_user_id=? AND ISNULL(is_active,1)=1",
            [$workItemId, $actorUserId]
        );
        return $a !== [];
    }
    return false;
}

/**
 * Additional-service customer approval gate (reuse estimate approval; no OTP change).
 */
function m360_ws_sl_additional_approval_ok($conn, int $jobcardId): bool
{
    if (!customer_core_table_exists($conn, 'erp_estimates')) {
        return false;
    }
    $est = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 estimate_id, estimate_status FROM dbo.erp_estimates
         WHERE jobcard_id=? AND estimate_status IN (N'CUSTOMER_APPROVED', N'APPROVED_FOR_WORK')
         ORDER BY estimate_id DESC",
        [$jobcardId]
    );
    if ($est === []) {
        return false;
    }
    if (!customer_core_table_exists($conn, 'erp_estimate_approvals')) {
        // Status alone is acceptable when approvals table missing.
        return true;
    }
    $estimateId = (int)$est[0]['estimate_id'];
    $appr = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 approval_id FROM dbo.erp_estimate_approvals
         WHERE estimate_id=? OR jobcard_id=?
         ORDER BY approval_id DESC",
        [$estimateId, $jobcardId]
    );
    // If approvals exist for this JC/estimate, treat as confirmed; else rely on estimate_status.
    return true;
}

/**
 * @param array<string,mixed> $input
 * @return array{ok:bool,message:string,service_line_id:int}
 */
function m360_ws_sl_create_or_update_draft(
    $conn,
    int $companyId,
    int $jobcardId,
    int $workItemId,
    array $input,
    int $actorId,
    bool $isOwner
): array {
    m360_ws_reject_injected_prices($input);
    if (array_key_exists('price_irr', $input) && $input['price_irr'] !== null && $input['price_irr'] !== '') {
        m360_am_forbidden('ثبت مبلغ با مجوز تکنسین مجاز نیست.');
    }

    $wi = m360_ws_sl_assert_work_item($conn, $companyId, $jobcardId, $workItemId);
    if ($wi === null) {
        return ['ok' => false, 'message' => 'آیتم کاری با پرونده/شرکت مطابقت ندارد.', 'service_line_id' => 0];
    }
    if (!m360_ws_sl_actor_assigned_to_work_item($conn, $workItemId, $actorId, $isOwner)) {
        return ['ok' => false, 'message' => 'فقط تکنسین تخصیص‌یافته می‌تواند خط خدمت این آیتم را ثبت کند.', 'service_line_id' => 0];
    }

    $category = strtoupper(trim((string)($input['sales_category'] ?? '')));
    $title = trim((string)($input['service_title'] ?? ''));
    $desc = trim((string)($input['service_description'] ?? ''));
    $minutes = (int)($input['actual_minutes'] ?? 0);
    $scope = strtoupper(trim((string)($input['agreement_scope'] ?? 'WITHIN_AGREEMENT')));
    if (!in_array($scope, ['WITHIN_AGREEMENT', 'ADDITIONAL'], true)) {
        $scope = 'WITHIN_AGREEMENT';
    }
    if ($title === '') {
        return ['ok' => false, 'message' => 'عنوان خدمت الزامی است.', 'service_line_id' => 0];
    }
    if (!in_array($category, M360_WS_SL_CATEGORIES, true)) {
        return ['ok' => false, 'message' => 'سرفصل فروش نامعتبر است.', 'service_line_id' => 0];
    }
    if (!m360_ws_sl_category_allowed_for_family((string)$wi['service_family'], $category)) {
        return ['ok' => false, 'message' => 'سرفصل فروش با خانواده عملیاتی آیتم کاری سازگار نیست.', 'service_line_id' => 0];
    }
    if ($minutes < 0) {
        return ['ok' => false, 'message' => 'مدت مصرفی نامعتبر است.', 'service_line_id' => 0];
    }

    $lineId = (int)($input['service_line_id'] ?? 0);
    if ($lineId > 0) {
        $existing = m360_ws_sl_fetch($conn, $lineId);
        if ($existing === null || (int)$existing['jobcard_id'] !== $jobcardId) {
            return ['ok' => false, 'message' => 'خط خدمت یافت نشد.', 'service_line_id' => 0];
        }
        $st = strtoupper((string)$existing['status']);
        if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
            return ['ok' => false, 'message' => 'فقط پیش‌نویس یا برگشتی قابل ویرایش است.', 'service_line_id' => $lineId];
        }
        if ((int)$existing['created_by_user_id'] !== $actorId && !$isOwner) {
            return ['ok' => false, 'message' => 'فقط ایجادکننده می‌تواند این پیش‌نویس را ویرایش کند.', 'service_line_id' => $lineId];
        }
        customer_core_execute(
            $conn,
            "UPDATE dbo.erp_workshop_service_lines SET
                sales_category=?, service_title=?, service_description=?, actual_minutes=?,
                agreement_scope=?, status=N'DRAFT', updated_by_user_id=?, updated_at=SYSUTCDATETIME(),
                return_reason=NULL
             WHERE service_line_id=?",
            [
                $category, mb_substr($title, 0, 300), $desc !== '' ? $desc : null, $minutes,
                $scope, $actorId, $lineId,
            ]
        );
        m360_ws_sl_history_add(
            $conn, $lineId, $companyId, $jobcardId, $workItemId,
            'SERVICE_LINE_UPDATED', $st, 'DRAFT', null, null, 'ذخیره پیش‌نویس', $actorId
        );
        return ['ok' => true, 'message' => 'پیش‌نویس خط خدمت ذخیره شد.', 'service_line_id' => $lineId];
    }

    $ok = customer_core_execute(
        $conn,
        "INSERT INTO dbo.erp_workshop_service_lines
            (company_id, jobcard_id, work_item_id, sales_category, service_title, service_description,
             actual_minutes, agreement_scope, status, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, N'DRAFT', ?)",
        [
            $companyId, $jobcardId, $workItemId, $category, mb_substr($title, 0, 300),
            $desc !== '' ? $desc : null, $minutes, $scope, $actorId,
        ]
    );
    if ($ok === false) {
        return ['ok' => false, 'message' => 'ایجاد خط خدمت ناموفق بود.', 'service_line_id' => 0];
    }
    $id = (int)(customer_core_scalar(
        $conn,
        'SELECT TOP 1 service_line_id FROM dbo.erp_workshop_service_lines
         WHERE jobcard_id=? AND created_by_user_id=? ORDER BY service_line_id DESC',
        [$jobcardId, $actorId]
    ) ?? 0);
    if ($id > 0) {
        m360_ws_sl_history_add(
            $conn, $id, $companyId, $jobcardId, $workItemId,
            'SERVICE_LINE_CREATED', null, 'DRAFT', null, null, 'ایجاد خط خدمت', $actorId
        );
    }
    return ['ok' => $id > 0, 'message' => $id > 0 ? 'خط خدمت ایجاد شد.' : 'شناسه خط خدمت دریافت نشد.', 'service_line_id' => $id];
}

/** @return array{ok:bool,message:string} */
function m360_ws_sl_submit($conn, int $serviceLineId, int $actorId, bool $isOwner): array
{
    $r = m360_ws_sl_fetch($conn, $serviceLineId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'خط خدمت یافت نشد.'];
    }
    $st = strtoupper((string)$r['status']);
    if (!in_array($st, ['DRAFT', 'RETURNED'], true)) {
        return ['ok' => false, 'message' => 'فقط پیش‌نویس یا برگشتی قابل ارسال است.'];
    }
    if ((int)$r['created_by_user_id'] !== $actorId && !$isOwner) {
        return ['ok' => false, 'message' => 'فقط ایجادکننده می‌تواند ارسال کند.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_service_lines
         SET status=N'SUBMITTED', submitted_by_user_id=?, submitted_at=SYSUTCDATETIME(),
             updated_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE service_line_id=?",
        [$actorId, $actorId, $serviceLineId]
    );
    m360_ws_sl_history_add(
        $conn, $serviceLineId, (int)$r['company_id'], (int)$r['jobcard_id'], (int)$r['work_item_id'],
        'SERVICE_LINE_SUBMITTED', $st, 'SUBMITTED', null, null, 'ارسال برای بررسی فنی', $actorId
    );
    return ['ok' => true, 'message' => 'خط خدمت ارسال شد.'];
}

/** @return array{ok:bool,message:string} */
function m360_ws_sl_return($conn, int $serviceLineId, int $actorId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل برگشت الزامی است.'];
    }
    $r = m360_ws_sl_fetch($conn, $serviceLineId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'خط خدمت یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط خطوط ارسال‌شده قابل برگشت هستند.'];
    }
    $creator = (int)$r['created_by_user_id'];
    $submitter = (int)($r['submitted_by_user_id'] ?? 0);
    if ($actorId === $creator || $actorId === $submitter) {
        return ['ok' => false, 'message' => 'ایجادکننده/ارسال‌کننده نمی‌تواند بررسی فنی خود را انجام دهد (maker-checker).'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_service_lines
         SET status=N'RETURNED', return_reason=?, technical_reviewed_by_user_id=?, technical_reviewed_at=SYSUTCDATETIME(),
             updated_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE service_line_id=?",
        [$reason, $actorId, $actorId, $serviceLineId]
    );
    m360_ws_sl_history_add(
        $conn, $serviceLineId, (int)$r['company_id'], (int)$r['jobcard_id'], (int)$r['work_item_id'],
        'SERVICE_LINE_RETURNED', 'SUBMITTED', 'RETURNED', null, null, $reason, $actorId
    );
    return ['ok' => true, 'message' => 'خط خدمت برای اصلاح برگشت داده شد.'];
}

/** @return array{ok:bool,message:string} */
function m360_ws_sl_technical_approve($conn, int $serviceLineId, int $actorId): array
{
    $r = m360_ws_sl_fetch($conn, $serviceLineId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'خط خدمت یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'SUBMITTED') {
        return ['ok' => false, 'message' => 'فقط خطوط ارسال‌شده قابل تأیید فنی هستند.'];
    }
    $creator = (int)$r['created_by_user_id'];
    $submitter = (int)($r['submitted_by_user_id'] ?? 0);
    if ($actorId === $creator || $actorId === $submitter) {
        return ['ok' => false, 'message' => 'ایجادکننده/ارسال‌کننده نمی‌تواند تأیید فنی کند (maker-checker).'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_service_lines
         SET status=N'PRICING_PENDING',
             technical_reviewed_by_user_id=?, technical_reviewed_at=SYSUTCDATETIME(),
             updated_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE service_line_id=?",
        [$actorId, $actorId, $serviceLineId]
    );
    // Record technical approve then pricing pending as two logical events.
    m360_ws_sl_history_add(
        $conn, $serviceLineId, (int)$r['company_id'], (int)$r['jobcard_id'], (int)$r['work_item_id'],
        'SERVICE_LINE_TECHNICALLY_APPROVED', 'SUBMITTED', 'TECHNICALLY_APPROVED', null, null, 'تأیید فنی', $actorId
    );
    m360_ws_sl_history_add(
        $conn, $serviceLineId, (int)$r['company_id'], (int)$r['jobcard_id'], (int)$r['work_item_id'],
        'SERVICE_LINE_UPDATED', 'TECHNICALLY_APPROVED', 'PRICING_PENDING', null, null, 'آماده قیمت‌گذاری', $actorId
    );
    return ['ok' => true, 'message' => 'تأیید فنی انجام شد؛ آماده قیمت‌گذاری است.'];
}

/** @return array{ok:bool,message:string} */
function m360_ws_sl_set_price($conn, int $serviceLineId, mixed $rawPrice, int $actorId, string $reason = ''): array
{
    $parsed = m360_ws_sl_parse_price_irr($rawPrice);
    if (!$parsed['ok']) {
        return ['ok' => false, 'message' => $parsed['message']];
    }
    $newPrice = (int)$parsed['value'];
    $r = m360_ws_sl_fetch($conn, $serviceLineId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'خط خدمت یافت نشد.'];
    }
    $st = strtoupper((string)$r['status']);
    if (!in_array($st, ['PRICING_PENDING', 'PRICED', 'TECHNICALLY_APPROVED'], true)) {
        return ['ok' => false, 'message' => 'وضعیت فعلی برای قیمت‌گذاری مجاز نیست.'];
    }
    if (in_array($st, ['READY_FOR_INVOICE', 'INVOICED', 'VOIDED'], true)) {
        return ['ok' => false, 'message' => 'پس از آماده‌سازی فاکتور، تغییر مستقیم مبلغ مجاز نیست.'];
    }
    $oldRaw = $r['price_irr'] ?? null;
    $oldPrice = ($oldRaw === null || $oldRaw === '' || (int)$oldRaw <= 0) ? null : (int)$oldRaw;
    if ($oldPrice !== null && $oldPrice !== $newPrice) {
        $reason = trim($reason);
        if ($reason === '') {
            return ['ok' => false, 'message' => 'تغییر مبلغ نیازمند دلیل است.'];
        }
    }
    $event = $oldPrice === null ? 'SERVICE_LINE_PRICE_SET' : 'SERVICE_LINE_PRICE_CHANGED';
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_service_lines
         SET price_irr=?, status=N'PRICED', priced_by_user_id=?, priced_at=SYSUTCDATETIME(),
             price_change_reason=?, updated_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE service_line_id=?",
        [$newPrice, $actorId, $reason !== '' ? $reason : null, $actorId, $serviceLineId]
    );
    m360_ws_sl_history_add(
        $conn, $serviceLineId, (int)$r['company_id'], (int)$r['jobcard_id'], (int)$r['work_item_id'],
        $event, $st, 'PRICED', $oldPrice, $newPrice, $reason !== '' ? $reason : 'ثبت مبلغ', $actorId
    );
    return ['ok' => true, 'message' => 'مبلغ خدمت ثبت شد.'];
}

/** @return array{ok:bool,message:string} */
function m360_ws_sl_mark_ready_for_invoice($conn, int $serviceLineId, int $actorId): array
{
    $r = m360_ws_sl_fetch($conn, $serviceLineId);
    if ($r === null) {
        return ['ok' => false, 'message' => 'خط خدمت یافت نشد.'];
    }
    if (strtoupper((string)$r['status']) !== 'PRICED') {
        return ['ok' => false, 'message' => 'فقط خط قیمت‌گذاری‌شده قابل آماده‌سازی فاکتور است.'];
    }
    if (!isset($r['price_irr']) || (int)$r['price_irr'] <= 0) {
        return ['ok' => false, 'message' => 'مبلغ معتبر ثبت نشده است.'];
    }
    $scope = strtoupper((string)($r['agreement_scope'] ?? 'WITHIN_AGREEMENT'));
    if ($scope === 'ADDITIONAL' && !m360_ws_sl_additional_approval_ok($conn, (int)$r['jobcard_id'])) {
        return ['ok' => false, 'message' => 'خدمت اضافی بدون تأیید مشتری/برآورد قابل آماده‌سازی فاکتور نیست.'];
    }
    customer_core_execute(
        $conn,
        "UPDATE dbo.erp_workshop_service_lines
         SET status=N'READY_FOR_INVOICE', ready_for_invoice_at=SYSUTCDATETIME(),
             ready_for_invoice_by_user_id=?, updated_by_user_id=?, updated_at=SYSUTCDATETIME()
         WHERE service_line_id=?",
        [$actorId, $actorId, $serviceLineId]
    );
    m360_ws_sl_history_add(
        $conn, $serviceLineId, (int)$r['company_id'], (int)$r['jobcard_id'], (int)$r['work_item_id'],
        'SERVICE_LINE_READY_FOR_INVOICE', 'PRICED', 'READY_FOR_INVOICE',
        (int)$r['price_irr'], (int)$r['price_irr'], 'آماده‌سازی فاکتور', $actorId
    );
    return ['ok' => true, 'message' => 'خط خدمت آماده فاکتور شد.'];
}

/**
 * Server-side services subtotal for authorized viewers.
 *
 * @return array{subtotal_irr:int,line_count:int,priced_count:int,pending_additional:int}
 */
function m360_ws_sl_summary_totals($conn, int $jobcardId): array
{
    $rows = m360_ws_sl_list_for_jobcard($conn, $jobcardId);
    $sub = 0;
    $priced = 0;
    $pendingAdd = 0;
    foreach ($rows as $r) {
        $st = strtoupper((string)($r['status'] ?? ''));
        if (isset($r['price_irr']) && $r['price_irr'] !== null && (int)$r['price_irr'] > 0
            && in_array($st, ['PRICED', 'READY_FOR_INVOICE', 'INVOICED'], true)) {
            $sub += (int)$r['price_irr'];
            $priced++;
        }
        if (strtoupper((string)($r['agreement_scope'] ?? '')) === 'ADDITIONAL'
            && !in_array($st, ['READY_FOR_INVOICE', 'INVOICED', 'VOIDED'], true)) {
            $pendingAdd++;
        }
    }
    return [
        'subtotal_irr' => $sub,
        'line_count' => count($rows),
        'priced_count' => $priced,
        'pending_additional' => $pendingAdd,
    ];
}
