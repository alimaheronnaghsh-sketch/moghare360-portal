<?php
declare(strict_types=1);

/**
 * MOGHARE360 P6 — Final inspection checklist.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const M360_QC_ITEM_TABLE = 'erp_qc_check_items';
const M360_QC_ITEM_PASS = 'PASS';
const M360_QC_ITEM_FAIL = 'FAIL';
const M360_QC_ITEM_NA = 'NOT_APPLICABLE';
const M360_QC_ITEM_PENDING = 'PENDING';

/** @return list<array{key:string,title:string,required:bool}> */
function m360_final_inspection_checklist_template(): array
{
    return [
        ['key' => 'SERVICE_JOBCARD_MATCH', 'title' => 'تطابق خدمات انجام‌شده با JobCard', 'required' => true],
        ['key' => 'SERVICE_ESTIMATE_MATCH', 'title' => 'تطابق خدمات انجام‌شده با estimate تأییدشده', 'required' => true],
        ['key' => 'PARTS_CONSUMED_REVIEW', 'title' => 'بررسی قطعات مصرف‌شده', 'required' => true],
        ['key' => 'LEAK_INSPECTION', 'title' => 'بررسی نشتی‌ها', 'required' => true],
        ['key' => 'DIAG_AFTER_WORK', 'title' => 'بررسی خطاهای دیاگ پس از کار', 'required' => true],
        ['key' => 'ENGINE_GEAR_ELECTRIC', 'title' => 'بررسی وضعیت موتور/گیربکس/برق مرتبط با خدمت', 'required' => true],
        ['key' => 'EXTERIOR_CONDITION', 'title' => 'بررسی ظاهری خودرو', 'required' => true],
        ['key' => 'BELONGINGS_INTAKE', 'title' => 'بررسی اقلام همراه و داخل خودرو طبق پذیرش', 'required' => true],
        ['key' => 'KM_FUEL_EXIT', 'title' => 'بررسی کیلومتر و سطح سوخت در خروج', 'required' => true],
        ['key' => 'FUNCTIONAL_TEST', 'title' => 'تست عملکردی درجا', 'required' => true],
        ['key' => 'ROAD_TEST', 'title' => 'تست رانندگی اگر مجاز بوده', 'required' => false],
        ['key' => 'REMAINING_DEFECTS', 'title' => 'ثبت ایرادات باقی‌مانده', 'required' => true],
        ['key' => 'FOLLOWUP_RECOMMENDATIONS', 'title' => 'ثبت توصیه‌های بعدی', 'required' => true],
        ['key' => 'DELIVERY_PREP_CONFIRM', 'title' => 'تأیید آماده‌سازی برای تحویل', 'required' => true],
    ];
}

function m360_final_inspection_seed_items($conn, int $qcCheckId, int $jobcardId): void
{
    if (!is_resource($conn) || $qcCheckId < 1 || !customer_core_table_exists($conn, M360_QC_ITEM_TABLE)) {
        return;
    }
    $existing = (int)(customer_core_scalar(
        $conn,
        'SELECT COUNT(*) FROM dbo.' . M360_QC_ITEM_TABLE . ' WHERE qc_check_id = ?',
        [$qcCheckId]
    ) ?? 0);
    if ($existing > 0) {
        return;
    }
    foreach (m360_final_inspection_checklist_template() as $item) {
        customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_QC_ITEM_TABLE . ' (qc_check_id, jobcard_id, item_key, item_title, item_result) VALUES (?, ?, ?, ?, ?)',
            [$qcCheckId, $jobcardId, $item['key'], $item['title'], M360_QC_ITEM_PENDING]
        );
    }
}

/** @return list<array<string, mixed>> */
function m360_final_inspection_list_items($conn, int $qcCheckId): array
{
    if (!is_resource($conn) || $qcCheckId < 1 || !customer_core_table_exists($conn, M360_QC_ITEM_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.' . M360_QC_ITEM_TABLE . ' WHERE qc_check_id = ? ORDER BY qc_item_id',
        [$qcCheckId]
    );
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_final_inspection_save_item($conn, int $qcItemId, string $result, ?string $note, int $userId): array
{
    if (!is_resource($conn) || $qcItemId < 1) {
        return ['ok' => false, 'message' => 'آیتم چک‌لیست نامعتبر است.'];
    }
    $result = strtoupper(trim($result));
    if (!in_array($result, [M360_QC_ITEM_PASS, M360_QC_ITEM_FAIL, M360_QC_ITEM_NA, M360_QC_ITEM_PENDING], true)) {
        return ['ok' => false, 'message' => 'نتیجه آیتم نامعتبر است.'];
    }
    if (!customer_core_table_exists($conn, M360_QC_ITEM_TABLE)) {
        return ['ok' => false, 'message' => 'جدول چک‌لیست QC یافت نشد.'];
    }
    $ok = customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_QC_ITEM_TABLE . ' SET item_result = ?, item_note = ?, checked_at = SYSUTCDATETIME(), checked_by_user_id = ? WHERE qc_item_id = ?',
        [$result, $note, $userId, $qcItemId]
    );
    return $ok === false
        ? ['ok' => false, 'message' => 'ذخیره آیتم چک‌لیست ناموفق بود.']
        : ['ok' => true, 'message' => 'آیتم چک‌لیست ذخیره شد.'];
}

function m360_final_inspection_has_active_fail($conn, int $qcCheckId): bool
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_ITEM_TABLE)) {
        return false;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT COUNT(*) AS c FROM dbo." . M360_QC_ITEM_TABLE . " WHERE qc_check_id = ? AND item_result = N'FAIL'",
        [$qcCheckId]
    );
    return (int)($rows[0]['c'] ?? 0) > 0;
}

function m360_final_inspection_checklist_complete($conn, int $qcCheckId): bool
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_QC_ITEM_TABLE)) {
        return false;
    }
    $requiredKeys = [];
    foreach (m360_final_inspection_checklist_template() as $item) {
        if ($item['required']) {
            $requiredKeys[] = $item['key'];
        }
    }
    if ($requiredKeys === []) {
        return true;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT item_key, item_result FROM dbo.' . M360_QC_ITEM_TABLE . ' WHERE qc_check_id = ?',
        [$qcCheckId]
    );
    $byKey = [];
    foreach ($rows as $row) {
        $byKey[(string)$row['item_key']] = strtoupper((string)($row['item_result'] ?? ''));
    }
    foreach ($requiredKeys as $key) {
        $r = $byKey[$key] ?? M360_QC_ITEM_PENDING;
        if (!in_array($r, [M360_QC_ITEM_PASS, M360_QC_ITEM_NA], true)) {
            return false;
        }
    }
    return true;
}

/**
 * @return array{ok:bool,message:string}
 */
function m360_final_inspection_validate_pass($conn, int $qcCheckId, string $finalNotes): array
{
    if (trim($finalNotes) === '') {
        return ['ok' => false, 'message' => 'یادداشت بازبینی نهایی الزامی است.'];
    }
    if (m360_final_inspection_has_active_fail($conn, $qcCheckId)) {
        return ['ok' => false, 'message' => 'آیتم FAIL فعال وجود دارد — QC نمی‌تواند Pass شود.'];
    }
    if (!m360_final_inspection_checklist_complete($conn, $qcCheckId)) {
        return ['ok' => false, 'message' => 'چک‌لیست QC کامل نشده است.'];
    }
    return ['ok' => true, 'message' => ''];
}

function m360_final_inspection_result_label(string $r): string
{
    return match (strtoupper(trim($r))) {
        M360_QC_ITEM_PASS => 'قبول',
        M360_QC_ITEM_FAIL => 'رد',
        M360_QC_ITEM_NA => 'نامربوط',
        default => 'در انتظار',
    };
}
