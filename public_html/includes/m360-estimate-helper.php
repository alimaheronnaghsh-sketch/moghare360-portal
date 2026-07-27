<?php
declare(strict_types=1);

/**
 * MOGHARE360 P4 — Estimate helper (ERP board, detail, actions).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-technician-workflow-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-parts-finance-gate-helper.php';

const M360_ESTIMATE_CSRF = 'estimate_p4';
const M360_ESTIMATE_TABLE = 'erp_estimates';
const M360_ESTIMATE_ITEM_TABLE = 'erp_estimate_items';
const M360_ESTIMATE_EVENT_TABLE = 'erp_estimate_events';
const M360_ESTIMATE_VERSION_TABLE = 'erp_estimate_versions';
const M360_ESTIMATE_VERSION_ITEM_TABLE = 'erp_estimate_version_items';
const M360_ESTIMATE_HISTORY = 'erp_jobcard_change_history';
const M360_ESTIMATE_TOKEN_TTL = 259200;

const M360_EST_VERSION_STATUS_ISSUED = 'ISSUED';
const M360_EST_VERSION_STATUS_VIEWED = 'VIEWED';
const M360_EST_VERSION_STATUS_ACCEPTED = 'ACCEPTED';
const M360_EST_VERSION_STATUS_REJECTED = 'REJECTED';
const M360_EST_VERSION_STATUS_SUPERSEDED = 'SUPERSEDED';
const M360_EST_VERSION_STATUS_CANCELLED = 'CANCELLED';

const M360_EST_ISSUE_REASON_INITIAL = 'INITIAL';
const M360_EST_ISSUE_REASON_REVISION = 'REVISION';

const M360_EST_STATUS_DRAFT = 'DRAFT';
const M360_EST_STATUS_INTERNAL_REVIEW = 'INTERNAL_REVIEW';
const M360_EST_STATUS_SENT = 'SENT_TO_CUSTOMER';
const M360_EST_STATUS_VIEWED = 'CUSTOMER_VIEWED';
const M360_EST_STATUS_APPROVED = 'CUSTOMER_APPROVED';
const M360_EST_STATUS_REJECTED = 'CUSTOMER_REJECTED';
const M360_EST_STATUS_REVISION = 'REVISION_REQUIRED';
const M360_EST_STATUS_PARTS_PENDING = 'PARTS_GATE_PENDING';
const M360_EST_STATUS_PARTS_CLEARED = 'PARTS_GATE_CLEARED';
const M360_EST_STATUS_FIN_PENDING = 'FINANCE_GATE_PENDING';
const M360_EST_STATUS_FIN_CLEARED = 'FINANCE_GATE_CLEARED';
const M360_EST_STATUS_APPROVED_WORK = 'APPROVED_FOR_WORK';
const M360_EST_STATUS_CANCELLED = 'CANCELLED';

/** @var array<string, string> */
const M360_EST_STATUS_LABELS_FA = [
    M360_EST_STATUS_DRAFT => 'پیش‌نویس',
    M360_EST_STATUS_INTERNAL_REVIEW => 'بازبینی داخلی',
    M360_EST_STATUS_SENT => 'ارسال به مشتری',
    M360_EST_STATUS_VIEWED => 'مشاهده شده',
    M360_EST_STATUS_APPROVED => 'تأیید مشتری',
    M360_EST_STATUS_REJECTED => 'رد مشتری',
    M360_EST_STATUS_REVISION => 'نیاز به اصلاح',
    M360_EST_STATUS_PARTS_PENDING => 'انتظار قطعه',
    M360_EST_STATUS_PARTS_CLEARED => 'قطعه آماده',
    M360_EST_STATUS_FIN_PENDING => 'انتظار مالی',
    M360_EST_STATUS_FIN_CLEARED => 'مجوز مالی',
    M360_EST_STATUS_APPROVED_WORK => 'مجاز برای ادامه کار',
    M360_EST_STATUS_CANCELLED => 'لغو شده',
];

/** @var list<string> */
const M360_EST_ITEM_TYPES = ['LABOR', 'SERVICE', 'PART', 'OUTSOURCE', 'DIAGNOSIS', 'OTHER'];

function m360_estimate_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_estimate_csrf_input(): string
{
    $token = erp_csrf_get_or_create_token(M360_ESTIMATE_CSRF);

    return '<input type="hidden" name="erp_csrf_token" value="' . m360_estimate_h($token) . '">';
}

function m360_estimate_require_staff(): void
{
    erp_auth_context_start();
    if (erp_auth_current_user_id() === null || erp_auth_current_user_id() <= 0) {
        header('Location: staff-login.php');
        exit;
    }
}

function m360_estimate_status_label(string $s): string
{
    return M360_EST_STATUS_LABELS_FA[strtoupper(trim($s))] ?? $s;
}

/** @return list<string> */
function m360_estimate_board_filters(): array
{
    return [
        'WAITING_FOR_APPROVAL',
        M360_EST_STATUS_DRAFT,
        M360_EST_STATUS_SENT,
        M360_EST_STATUS_APPROVED,
        M360_EST_STATUS_REJECTED,
        M360_EST_STATUS_PARTS_PENDING,
        M360_EST_STATUS_PARTS_CLEARED,
        M360_EST_STATUS_FIN_PENDING,
        M360_EST_STATUS_FIN_CLEARED,
        M360_EST_STATUS_APPROVED_WORK,
    ];
}

function m360_estimate_hash(string $raw): string
{
    return hash('sha256', $raw);
}

/**
 * @param array<string, mixed> $row
 */
function m360_estimate_is_p3_ready(array $row): bool
{
    $tech = strtoupper(trim((string)($row['technical_status'] ?? '')));
    return $tech === M360_TECH_STATUS_WAITING_APPROVAL
        || strtoupper((string)($row['estimate_status'] ?? '')) !== '';
}

/** @return array{ok:bool,message:string} */
function m360_estimate_assert_upstream_gates(int $jobcardId, ?array $jobcardRow = null): array
{
    if (!function_exists('m360_contract_can_continue_to_p2')) {
        return ['ok' => false, 'message' => 'P1.5 Gate missing — عملیات متوقف شد.'];
    }
    if ($jobcardId < 1) {
        return ['ok' => false, 'message' => 'شناسه کارت کار نامعتبر است.'];
    }
    if (!m360_contract_can_continue_to_p2($jobcardId)) {
        return ['ok' => false, 'message' => 'قرارداد پذیرش معتبر نیست.'];
    }
    $conn = customer_core_db();
    if ($jobcardRow === null && $conn !== false) {
        $jobcardRow = m360_estimate_fetch_jobcard($conn, $jobcardId);
    }
    if ($jobcardRow === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }
    if (!m360_estimate_is_p3_ready($jobcardRow)) {
        return ['ok' => false, 'message' => 'پرونده هنوز در وضعیت انتظار تأیید برآورد (P3) نیست.'];
    }
    return ['ok' => true, 'message' => ''];
}

function m360_estimate_record_event($conn, int $jobcardId, string $eventName, ?int $estimateId = null, ?string $note = null, ?int $userId = null, ?int $estimateVersionId = null): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_EVENT_TABLE)) {
        return;
    }
    if ($estimateVersionId !== null && $estimateVersionId > 0 && customer_core_column_exists($conn, M360_ESTIMATE_EVENT_TABLE, 'estimate_version_id')) {
        customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_ESTIMATE_EVENT_TABLE . ' (estimate_id, estimate_version_id, jobcard_id, event_name, event_note, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$estimateId, $estimateVersionId, $jobcardId, $eventName, $note, $userId]
        );

        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_ESTIMATE_EVENT_TABLE . ' (estimate_id, jobcard_id, event_name, event_note, created_by_user_id) VALUES (?, ?, ?, ?, ?)',
        [$estimateId, $jobcardId, $eventName, $note, $userId]
    );
}

function m360_estimate_version_tables_available($conn): bool
{
    return is_resource($conn)
        && customer_core_table_exists($conn, M360_ESTIMATE_VERSION_TABLE)
        && customer_core_table_exists($conn, M360_ESTIMATE_VERSION_ITEM_TABLE);
}

function m360_estimate_tx_begin($conn): bool
{
    return is_resource($conn) && @odbc_autocommit($conn, false);
}

function m360_estimate_tx_commit($conn): void
{
    if (!is_resource($conn)) {
        return;
    }
    @odbc_commit($conn);
    @odbc_autocommit($conn, true);
}

function m360_estimate_tx_rollback($conn): void
{
    if (!is_resource($conn)) {
        return;
    }
    @odbc_rollback($conn);
    @odbc_autocommit($conn, true);
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch_version($conn, int $estimateVersionId): ?array
{
    if (!is_resource($conn) || $estimateVersionId < 1 || !m360_estimate_version_tables_available($conn)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . ' WHERE estimate_version_id = ?',
        [$estimateVersionId]
    );

    return $rows[0] ?? null;
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch_version_by_token_hash($conn, string $tokenHash): ?array
{
    if (!is_resource($conn) || $tokenHash === '' || !m360_estimate_version_tables_available($conn)) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . ' WHERE secure_token_hash = ? ORDER BY estimate_version_id DESC',
        [$tokenHash]
    );

    return $rows[0] ?? null;
}

/** @return list<array<string, mixed>> */
function m360_estimate_list_version_items($conn, int $estimateVersionId): array
{
    if (!is_resource($conn) || $estimateVersionId < 1 || !m360_estimate_version_tables_available($conn)) {
        return [];
    }

    return customer_core_fetch_rows(
        $conn,
        'SELECT * FROM dbo.' . M360_ESTIMATE_VERSION_ITEM_TABLE . ' WHERE estimate_version_id = ? ORDER BY line_number ASC, estimate_version_item_id ASC',
        [$estimateVersionId]
    );
}

/**
 * @param list<array<string, mixed>> $lines
 */
function m360_estimate_build_content_hash(int $estimateId, int $versionNumber, array $headerAmounts, array $lines): string
{
    $parts = [
        'estimate_id=' . $estimateId,
        'version_number=' . $versionNumber,
        'subtotal=' . sprintf('%.2f', (float)($headerAmounts['subtotal_amount'] ?? 0)),
        'discount=' . sprintf('%.2f', (float)($headerAmounts['discount_amount'] ?? 0)),
        'tax=' . sprintf('%.2f', (float)($headerAmounts['tax_amount'] ?? 0)),
        'total=' . sprintf('%.2f', (float)($headerAmounts['total_amount'] ?? 0)),
    ];
    foreach ($lines as $line) {
        $parts[] = implode('|', [
            (int)($line['line_number'] ?? 0),
            strtoupper(trim((string)($line['item_type'] ?? ''))),
            trim((string)($line['item_title'] ?? '')),
            sprintf('%.2f', (float)($line['quantity'] ?? 0)),
            sprintf('%.2f', (float)($line['unit_price'] ?? 0)),
            sprintf('%.2f', (float)($line['line_total'] ?? 0)),
        ]);
    }

    return hash('sha256', implode("\n", $parts));
}

/**
 * @return array{ok:bool,message:string,estimate_version_id?:int,raw_token?:string,customer_url?:string,task_id?:int}
 */
function m360_estimate_issue_to_customer($conn, int $estimateId, int $jobcardId, int $userId, string $issueReason = M360_EST_ISSUE_REASON_INITIAL): array
{
    if (!is_resource($conn) || !m360_estimate_version_tables_available($conn)) {
        return ['ok' => false, 'message' => 'نسخه‌بندی برآورد فعال نیست.'];
    }
    $est = m360_estimate_fetch($conn, $estimateId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
    }
    $items = m360_estimate_list_items($conn, $estimateId);
    if ($items === []) {
        return ['ok' => false, 'message' => 'حداقل یک آیتم برای ارسال لازم است.'];
    }
    m360_estimate_calculate_totals($conn, $estimateId);
    $est = m360_estimate_fetch($conn, $estimateId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
    }

    $jc = m360_estimate_fetch_jobcard($conn, $jobcardId);
    if ($jc === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    if (!m360_estimate_tx_begin($conn)) {
        return ['ok' => false, 'message' => 'شروع تراکنش ناموفق بود.'];
    }

    try {
        $nextVersion = (int)(customer_core_scalar(
            $conn,
            'SELECT ISNULL(MAX(version_number), 0) + 1 FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . ' WHERE estimate_id = ?',
            [$estimateId]
        ) ?? 1);
        if ($nextVersion < 1) {
            $nextVersion = 1;
        }

        $activeRows = customer_core_fetch_rows(
            $conn,
            "SELECT estimate_version_id, version_status FROM dbo." . M360_ESTIMATE_VERSION_TABLE . "
             WHERE estimate_id = ? AND version_status IN (?, ?)",
            [$estimateId, M360_EST_VERSION_STATUS_ISSUED, M360_EST_VERSION_STATUS_VIEWED]
        );
        foreach ($activeRows as $activeRow) {
            $activeVersionId = (int)($activeRow['estimate_version_id'] ?? 0);
            if ($activeVersionId < 1) {
                continue;
            }
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_ESTIMATE_VERSION_TABLE . ' SET version_status = ?, decided_at = COALESCE(decided_at, SYSUTCDATETIME()) WHERE estimate_version_id = ? AND version_status IN (?, ?)',
                [M360_EST_VERSION_STATUS_SUPERSEDED, $activeVersionId, M360_EST_VERSION_STATUS_ISSUED, M360_EST_VERSION_STATUS_VIEWED]
            );
            if (!function_exists('m360_cartable_cancel_task_for_estimate_version')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';
            }
            if (function_exists('m360_cartable_cancel_task_for_estimate_version')) {
                m360_cartable_cancel_task_for_estimate_version($conn, $activeVersionId, 'SYSTEM', 'estimate_supersede');
            }
        }

        $snapshotLines = [];
        $lineNumber = 0;
        foreach ($items as $item) {
            $lineNumber++;
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $lineTotal = round((float)($item['line_total'] ?? ($qty * $price)), 2);
            $snapshotLines[] = [
                'line_number' => $lineNumber,
                'source_estimate_item_id' => (int)($item['estimate_item_id'] ?? 0),
                'item_type' => strtoupper(trim((string)($item['item_type'] ?? 'SERVICE'))),
                'item_title' => trim((string)($item['item_title'] ?? '')),
                'item_description' => trim((string)($item['item_description'] ?? '')),
                'quantity' => $qty,
                'unit_name' => 'عدد',
                'unit_price' => $price,
                'line_discount' => 0.0,
                'line_total' => $lineTotal,
                'item_reference' => (string)($item['estimate_item_id'] ?? ''),
            ];
        }

        $headerAmounts = [
            'subtotal_amount' => (float)($est['subtotal_amount'] ?? 0),
            'discount_amount' => (float)($est['discount_amount'] ?? 0),
            'tax_amount' => (float)($est['tax_amount'] ?? 0),
            'total_amount' => (float)($est['total_amount'] ?? 0),
        ];
        $contentHash = m360_estimate_build_content_hash($estimateId, $nextVersion, $headerAmounts, $snapshotLines);
        $tok = m360_estimate_generate_token();
        $customerId = (int)($est['customer_id'] ?? 0);
        if ($customerId < 1) {
            $customerId = (int)($jc['customer_id'] ?? 0);
        }
        $onlineRequestId = (int)($jc['online_request_id'] ?? 0);
        $reason = strtoupper(trim($issueReason)) === M360_EST_ISSUE_REASON_REVISION
            ? M360_EST_ISSUE_REASON_REVISION
            : M360_EST_ISSUE_REASON_INITIAL;

        $insertOk = customer_core_execute(
            $conn,
            'INSERT INTO dbo.' . M360_ESTIMATE_VERSION_TABLE . ' (
                estimate_id, version_number, jobcard_id, customer_id, online_request_id,
                subtotal_amount, discount_amount, tax_amount, total_amount, currency_code,
                issue_reason, version_status, content_hash, secure_token_hash, secure_token_expires_at,
                issued_at, created_by_actor_type, created_by_actor_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME(), ?, ?)',
            [
                $estimateId,
                $nextVersion,
                $jobcardId,
                $customerId > 0 ? $customerId : null,
                $onlineRequestId > 0 ? $onlineRequestId : null,
                $headerAmounts['subtotal_amount'],
                $headerAmounts['discount_amount'],
                $headerAmounts['tax_amount'],
                $headerAmounts['total_amount'],
                'IRR',
                $reason,
                M360_EST_VERSION_STATUS_ISSUED,
                $contentHash,
                $tok['hash'],
                $tok['expires_at'],
                'STAFF',
                (string)$userId,
            ]
        );
        if ($insertOk === false) {
            throw new RuntimeException('version_insert_failed');
        }

        $versionId = (int)(customer_core_scope_identity($conn) ?? 0);
        if ($versionId < 1) {
            $versionRow = customer_core_fetch_rows(
                $conn,
                'SELECT TOP 1 estimate_version_id FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . ' WHERE estimate_id = ? AND version_number = ? ORDER BY estimate_version_id DESC',
                [$estimateId, $nextVersion]
            );
            $versionId = (int)($versionRow[0]['estimate_version_id'] ?? 0);
        }
        if ($versionId < 1) {
            throw new RuntimeException('version_identity_failed');
        }

        foreach ($snapshotLines as $line) {
            $ok = customer_core_execute(
                $conn,
                'INSERT INTO dbo.' . M360_ESTIMATE_VERSION_ITEM_TABLE . ' (
                    estimate_version_id, source_estimate_item_id, line_number, item_type, item_title, item_description,
                    quantity, unit_name, unit_price, line_discount, line_total, item_reference
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $versionId,
                    (int)($line['source_estimate_item_id'] ?? 0) > 0 ? (int)$line['source_estimate_item_id'] : null,
                    (int)$line['line_number'],
                    (string)$line['item_type'],
                    mb_substr((string)$line['item_title'], 0, 300),
                    ($line['item_description'] ?? '') !== '' ? (string)$line['item_description'] : null,
                    (float)$line['quantity'],
                    (string)$line['unit_name'],
                    (float)$line['unit_price'],
                    (float)$line['line_discount'],
                    (float)$line['line_total'],
                    (string)$line['item_reference'],
                ]
            );
            if ($ok === false) {
                throw new RuntimeException('version_item_insert_failed');
            }
        }

        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, estimate_version = ?, secure_token_hash = ?, secure_token_expires_at = ?, sent_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE estimate_id = ?',
            [M360_EST_STATUS_SENT, $nextVersion, $tok['hash'], $tok['expires_at'], $estimateId]
        );
        if (customer_core_column_exists($conn, 'erp_jobcards', 'estimate_status')) {
            customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET estimate_status = N\'ESTIMATE_SENT\', updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$jobcardId]);
        }

        if (!function_exists('m360_cartable_sync_estimate_approval_task')) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';
        }
        $taskResult = m360_cartable_sync_estimate_approval_task($conn, $versionId, $est, $jc, $tok['hash'], $tok['expires_at']);
        if (!$taskResult['ok']) {
            throw new RuntimeException('cartable_sync_failed:' . (string)($taskResult['message'] ?? ''));
        }

        m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_VERSION_ISSUED', $estimateId, 'version=' . (string)$nextVersion, $userId, $versionId);
        m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_SENT_TO_CUSTOMER', $estimateId, null, $userId, $versionId);
        m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_ESTIMATE_SENT', null, 'ESTIMATE_SENT', 'Estimate sent to customer', $userId);

        m360_estimate_tx_commit($conn);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['m360_estimate_last_link_' . $estimateId] = m360_estimate_customer_url($tok['raw']);
        $_SESSION['m360_estimate_version_token_' . $versionId] = $tok['raw'];

        return [
            'ok' => true,
            'message' => 'برآورد برای مشتری ارسال شد. لینک: ' . m360_estimate_customer_url($tok['raw']),
            'estimate_version_id' => $versionId,
            'raw_token' => $tok['raw'],
            'customer_url' => m360_estimate_customer_url($tok['raw']),
            'task_id' => (int)($taskResult['task_id'] ?? 0),
        ];
    } catch (Throwable $e) {
        m360_estimate_tx_rollback($conn);

        return ['ok' => false, 'message' => 'صدور نسخه برآورد ناموفق بود.'];
    }
}

function m360_estimate_jobcard_history($conn, int $jobcardId, string $type, ?string $prev, ?string $new, string $summary, int $userId): void
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_HISTORY)) {
        return;
    }
    customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_ESTIMATE_HISTORY . ' (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$jobcardId, $type, $prev, $new, $summary, $userId]
    );
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $sql = 'SELECT TOP 1 j.*, c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
                   v.plate_number, v.brand, v.model
            FROM dbo.erp_jobcards j
            LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
            LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
            WHERE j.jobcard_id = ?';
    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, [$jobcardId])) {
        return null;
    }
    $row = odbc_fetch_array($stmt);
    return $row === false ? null : $row;
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch($conn, int $estimateId): ?array
{
    if (!is_resource($conn) || $estimateId < 1 || !customer_core_table_exists($conn, M360_ESTIMATE_TABLE)) {
        return null;
    }
    $rows = customer_core_fetch_rows($conn, 'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_TABLE . ' WHERE estimate_id = ?', [$estimateId]);
    return $rows[0] ?? null;
}

/** @return array<string, mixed>|null */
function m360_estimate_fetch_active_for_jobcard($conn, int $jobcardId): ?array
{
    if (!is_resource($conn) || $jobcardId < 1) {
        return null;
    }
    $rows = customer_core_fetch_rows(
        $conn,
        "SELECT TOP 1 * FROM dbo." . M360_ESTIMATE_TABLE . " WHERE jobcard_id = ? AND estimate_status <> N'CANCELLED' ORDER BY estimate_id DESC",
        [$jobcardId]
    );
    return $rows[0] ?? null;
}

/** @return list<array<string, mixed>> */
function m360_estimate_list_items($conn, int $estimateId): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_ITEM_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        "SELECT * FROM dbo." . M360_ESTIMATE_ITEM_TABLE . " WHERE estimate_id = ? AND item_status <> N'REMOVED' ORDER BY estimate_item_id",
        [$estimateId]
    );
}

/** @return list<array<string, mixed>> */
function m360_estimate_list_events($conn, int $estimateId, int $limit = 50): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_EVENT_TABLE)) {
        return [];
    }
    return customer_core_fetch_rows(
        $conn,
        'SELECT TOP ' . max(1, min(100, $limit)) . ' * FROM dbo.' . M360_ESTIMATE_EVENT_TABLE . ' WHERE estimate_id = ? ORDER BY created_at DESC, event_id DESC',
        [$estimateId]
    );
}

/**
 * @return list<array<string, mixed>>
 */
function m360_estimate_board_list($conn, ?string $filter = null, int $limit = 150): array
{
    if (!is_resource($conn) || !customer_core_table_exists($conn, M360_ESTIMATE_TABLE)) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $params = [];
    $where = "(j.technical_status = N'WAITING_FOR_APPROVAL' OR e.estimate_id IS NOT NULL)";

    $filter = strtoupper(trim((string)$filter));
    if ($filter === 'WAITING_FOR_APPROVAL') {
        $where .= " AND j.technical_status = N'WAITING_FOR_APPROVAL' AND (e.estimate_id IS NULL OR e.estimate_status = N'DRAFT')";
    } elseif ($filter !== '' && $filter !== 'ALL') {
        $where .= ' AND e.estimate_status = ?';
        $params[] = $filter;
    }

    $sql = 'SELECT TOP ' . $limit . '
            j.jobcard_id, j.jobcard_number, j.technical_status, j.estimate_status AS jobcard_estimate_status,
            j.parts_gate_status AS jc_parts_gate, j.finance_gate_status AS jc_finance_gate,
            j.diagnosis_summary, j.created_at AS jobcard_created_at,
            e.estimate_id, e.estimate_status, e.total_amount, e.advance_required_amount,
            e.parts_gate_status, e.finance_gate_status, e.approved_at,
            c.full_name AS customer_name, c.primary_mobile AS customer_mobile,
            v.plate_number, v.brand, v.model
        FROM dbo.erp_jobcards j
        LEFT JOIN dbo.erp_estimates e ON e.estimate_id = j.current_estimate_id
            OR (j.current_estimate_id IS NULL AND e.estimate_id = (
                SELECT TOP 1 estimate_id FROM dbo.erp_estimates ex WHERE ex.jobcard_id = j.jobcard_id AND ex.estimate_status <> N\'CANCELLED\' ORDER BY estimate_id DESC
            ))
        LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
        LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
        WHERE ' . $where . '
        ORDER BY j.jobcard_id DESC';

    $stmt = @odbc_prepare($conn, $sql);
    if ($stmt === false || !@odbc_execute($stmt, $params)) {
        return [];
    }
    $rows = [];
    while (($row = odbc_fetch_array($stmt)) !== false) {
        $row['estimate_status_label'] = m360_estimate_status_label((string)($row['estimate_status'] ?? 'WAITING_FOR_APPROVAL'));
        $vehicle = trim(trim((string)($row['brand'] ?? '')) . ' ' . trim((string)($row['model'] ?? '')));
        $row['vehicle_label'] = $vehicle !== '' ? $vehicle : '-';
        $rows[] = $row;
    }
    return $rows;
}

/** @return array{ok:bool,message:string,estimate_id:?int} */
function m360_estimate_calculate_totals($conn, int $estimateId): array
{
    $est = m360_estimate_fetch($conn, $estimateId);
    if ($est === null) {
        return ['ok' => false, 'message' => 'برآورد یافت نشد.', 'estimate_id' => null];
    }
    $items = m360_estimate_list_items($conn, $estimateId);
    $subtotal = 0.0;
    $partsRequired = false;
    foreach ($items as $it) {
        $line = (float)($it['line_total'] ?? 0);
        $subtotal += $line;
        if (strtoupper((string)($it['item_type'] ?? '')) === 'PART') {
            $partsRequired = true;
        }
    }
    $discount = (float)($est['discount_amount'] ?? 0);
    $tax = (float)($est['tax_amount'] ?? 0);
    $total = max(0, $subtotal - $discount + $tax);
    $advance = m360_finance_calculate_advance($total);

    $partsEval = m360_parts_gate_evaluate($conn, $estimateId);
    $partsGate = $partsEval['parts_gate_status'];
    if (!$partsRequired) {
        $partsGate = M360_GATE_PARTS_NOT_REQUIRED;
    }

    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET subtotal_amount = ?, total_amount = ?, advance_required_amount = ?, parts_required = ?, parts_gate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?',
        [$subtotal, $total, $advance, $partsRequired ? 1 : 0, $partsGate, $estimateId]
    );

    return ['ok' => true, 'message' => 'جمع‌بندی محاسبه شد.', 'estimate_id' => $estimateId];
}

/** @return array{raw:string,hash:string,expires_at:string} */
function m360_estimate_generate_token(): array
{
    $raw = bin2hex(random_bytes(32));
    return [
        'raw' => $raw,
        'hash' => m360_estimate_hash($raw),
        'expires_at' => gmdate('Y-m-d H:i:s', time() + M360_ESTIMATE_TOKEN_TTL),
    ];
}

function m360_estimate_customer_url(string $rawToken): string
{
    return m360_estimate_public_url('customer-estimate-approval.php?token=' . rawurlencode($rawToken));
}

function m360_estimate_public_url(string $path): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $base = ($https ? 'https' : 'http') . '://' . $host;
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($dir === '.' || $dir === '/') {
        $dir = '';
    }

    return $base . $dir . '/' . ltrim($path, '/');
}

function m360_estimate_customer_profile_url(): string
{
    return m360_estimate_public_url('customer-profile.php');
}

function m360_estimate_normalize_mobile(string $phone): string
{
    if (!function_exists('m360_cartable_normalize_mobile')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';
    }
    if (function_exists('m360_cartable_normalize_mobile')) {
        return m360_cartable_normalize_mobile($phone);
    }

    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '98') && strlen($digits) === 12) {
        $digits = '0' . substr($digits, 2);
    }

    return preg_match('/^09\d{9}$/', $digits) === 1 ? $digits : '';
}

function m360_estimate_mask_mobile(string $mobile): string
{
    $digits = preg_replace('/\D+/', '', $mobile) ?? '';
    if (strlen($digits) < 8) {
        return '***';
    }

    return substr($digits, 0, 4) . '***' . substr($digits, -4);
}

/**
 * @param array<int, mixed> $context
 */
function m360_estimate_mobile_is_synthetic(string $mobile, array $context = []): bool
{
    if (preg_match('/^09\d{9}$/', $mobile) !== 1) {
        return true;
    }

    $blocked = ['09000000000', '09100000000', '09110000000', '09111111111', '09120000000', '09121111111', '09121234567', '09123456789', '09999999999'];
    if (in_array($mobile, $blocked, true) || preg_match('/^09(\d)\1{8}$/', $mobile) === 1) {
        return true;
    }

    $contextText = strtoupper((string)json_encode($context, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return preg_match('/\b(TEST|FAKE|DEMO|SYNTHETIC|AUTO-UAT|MOCK)\b/u', $contextText) === 1;
}

/**
 * @param array<string, mixed> $est
 * @param array<string, mixed> $jc
 * @return array{ok:bool,mobile:string,masked:string,source:string,verified:bool,real_format:bool,synthetic:bool,message:string}
 */
function m360_estimate_resolve_customer_mobile($conn, array $est, array $jc): array
{
    $empty = ['ok' => false, 'mobile' => '', 'masked' => '', 'source' => '', 'verified' => false, 'real_format' => false, 'synthetic' => false, 'message' => 'missing_mobile'];
    if (!is_resource($conn)) {
        return $empty;
    }

    $customerId = (int)($est['customer_id'] ?? 0);
    if ($customerId < 1) {
        $customerId = (int)($jc['customer_id'] ?? 0);
    }
    $onlineRequestId = (int)($jc['online_request_id'] ?? 0);
    $candidates = [];

    if ($customerId > 0 && customer_core_table_exists($conn, 'erp_customer_phones')) {
        $phones = customer_core_fetch_rows(
            $conn,
            "SELECT TOP 5 phone_number, is_verified, is_primary
             FROM dbo.erp_customer_phones
             WHERE customer_id = ?
               AND phone_type = N'MOBILE'
               AND lifecycle_state = N'ACTIVE'
               AND ISNULL(do_not_contact, 0) = 0
             ORDER BY is_primary DESC, is_verified DESC, phone_id DESC",
            [$customerId]
        );
        foreach ($phones as $phone) {
            $candidates[] = [
                'source' => 'erp_customer_phones',
                'raw' => (string)($phone['phone_number'] ?? ''),
                'verified' => (int)($phone['is_verified'] ?? 0) === 1,
            ];
        }
    }

    if ($customerId > 0 && customer_core_table_exists($conn, 'erp_customers')) {
        $select = [];
        $select[] = customer_core_column_exists($conn, 'erp_customers', 'primary_mobile') ? 'primary_mobile' : "CAST(N'' AS NVARCHAR(30)) AS primary_mobile";
        $select[] = customer_core_column_exists($conn, 'erp_customers', 'secondary_mobile') ? 'secondary_mobile' : "CAST(N'' AS NVARCHAR(30)) AS secondary_mobile";
        $customerRows = customer_core_fetch_rows($conn, 'SELECT TOP 1 ' . implode(', ', $select) . ' FROM dbo.erp_customers WHERE customer_id = ?', [$customerId]);
        $customer = $customerRows[0] ?? [];
        $candidates[] = ['source' => 'erp_customers.primary_mobile', 'raw' => (string)($customer['primary_mobile'] ?? ''), 'verified' => false];
        $candidates[] = ['source' => 'erp_customers.secondary_mobile', 'raw' => (string)($customer['secondary_mobile'] ?? ''), 'verified' => false];
    }

    if ($onlineRequestId > 0 && customer_core_table_exists($conn, 'erp_customer_online_requests')) {
        $select = [];
        $select[] = customer_core_column_exists($conn, 'erp_customer_online_requests', 'mobile') ? 'mobile' : "CAST(N'' AS NVARCHAR(30)) AS mobile";
        $select[] = customer_core_column_exists($conn, 'erp_customer_online_requests', 'otp_verified') ? 'otp_verified' : 'CAST(0 AS BIT) AS otp_verified';
        $requestRows = customer_core_fetch_rows($conn, 'SELECT TOP 1 ' . implode(', ', $select) . ' FROM dbo.erp_customer_online_requests WHERE online_request_id = ?', [$onlineRequestId]);
        $request = $requestRows[0] ?? [];
        $candidates[] = [
            'source' => 'erp_customer_online_requests.mobile',
            'raw' => (string)($request['mobile'] ?? ''),
            'verified' => (int)($request['otp_verified'] ?? 0) === 1,
        ];
    }

    $candidates[] = ['source' => 'erp_jobcards.customer_mobile', 'raw' => (string)($jc['customer_mobile'] ?? ''), 'verified' => false];

    foreach ($candidates as $candidate) {
        $mobile = m360_estimate_normalize_mobile((string)($candidate['raw'] ?? ''));
        if ($mobile === '') {
            continue;
        }
        $synthetic = m360_estimate_mobile_is_synthetic($mobile, [$est, $jc, $candidate['source'] ?? '']);

        return [
            'ok' => true,
            'mobile' => $mobile,
            'masked' => m360_estimate_mask_mobile($mobile),
            'source' => (string)($candidate['source'] ?? ''),
            'verified' => (bool)($candidate['verified'] ?? false),
            'real_format' => true,
            'synthetic' => $synthetic,
            'message' => '',
        ];
    }

    return $empty;
}

/** @return array<string, mixed>|null */
function m360_estimate_find_active_customer_task($conn, int $estimateId, int $customerId = 0, int $jobcardId = 0): ?array
{
    if (!is_resource($conn) || $estimateId < 1 || !customer_core_table_exists($conn, 'erp_customer_cartable_tasks')) {
        return null;
    }

    $where = ['estimate_id = ?', "task_type = N'ESTIMATE_APPROVAL'", 'is_active = 1', "status IN (N'PENDING', N'OPENED')"];
    $params = [$estimateId];
    if ($customerId > 0) {
        $where[] = '(customer_id = ? OR customer_id IS NULL)';
        $params[] = $customerId;
    }
    if ($jobcardId > 0) {
        $where[] = '(jobcard_id = ? OR jobcard_id IS NULL)';
        $params[] = $jobcardId;
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.erp_customer_cartable_tasks WHERE ' . implode(' AND ', $where) . ' ORDER BY task_id DESC',
        $params
    );

    return $rows[0] ?? null;
}

/** @return array<string, mixed>|null */
function m360_estimate_find_latest_active_version($conn, int $estimateId): ?array
{
    if (!is_resource($conn) || $estimateId < 1 || !m360_estimate_version_tables_available($conn)) {
        return null;
    }

    $rows = customer_core_fetch_rows(
        $conn,
        'SELECT TOP 1 * FROM dbo.' . M360_ESTIMATE_VERSION_TABLE . ' WHERE estimate_id = ? AND version_status IN (?, ?) ORDER BY estimate_version_id DESC',
        [$estimateId, M360_EST_VERSION_STATUS_ISSUED, M360_EST_VERSION_STATUS_VIEWED]
    );

    return $rows[0] ?? null;
}

/**
 * @param array<string, mixed> $est
 * @param array<string, mixed> $jc
 * @param array<string, mixed> $mobileInfo
 * @return array{ok:bool,task_id:int,created_new:bool,estimate_version_id:int,message:string}
 */
function m360_estimate_reuse_or_sync_customer_task($conn, array $est, array $jc, array $mobileInfo): array
{
    $estimateId = (int)($est['estimate_id'] ?? 0);
    $jobcardId = (int)($jc['jobcard_id'] ?? 0);
    $customerId = (int)($est['customer_id'] ?? 0);
    if ($customerId < 1) {
        $customerId = (int)($jc['customer_id'] ?? 0);
    }
    $existing = m360_estimate_find_active_customer_task($conn, $estimateId, $customerId, $jobcardId);
    if ($existing !== null) {
        return [
            'ok' => true,
            'task_id' => (int)($existing['task_id'] ?? 0),
            'created_new' => false,
            'estimate_version_id' => (int)($existing['source_entity_id'] ?? 0),
            'message' => '',
        ];
    }

    $version = m360_estimate_find_latest_active_version($conn, $estimateId);
    if ($version === null) {
        return ['ok' => false, 'task_id' => 0, 'created_new' => false, 'estimate_version_id' => 0, 'message' => 'missing_active_version'];
    }

    if (!function_exists('m360_cartable_sync_estimate_approval_task')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';
    }

    $jobcardForTask = $jc;
    if ((string)($mobileInfo['mobile'] ?? '') !== '') {
        $jobcardForTask['customer_mobile'] = (string)$mobileInfo['mobile'];
    }

    $task = m360_cartable_sync_estimate_approval_task(
        $conn,
        (int)$version['estimate_version_id'],
        $est,
        $jobcardForTask,
        (string)($version['secure_token_hash'] ?? ''),
        (string)($version['secure_token_expires_at'] ?? '')
    );
    if (!$task['ok']) {
        return ['ok' => false, 'task_id' => 0, 'created_new' => false, 'estimate_version_id' => (int)$version['estimate_version_id'], 'message' => (string)($task['message'] ?? 'task_sync_failed')];
    }

    return [
        'ok' => true,
        'task_id' => (int)($task['task_id'] ?? 0),
        'created_new' => (bool)($task['created_new'] ?? false),
        'estimate_version_id' => (int)$version['estimate_version_id'],
        'message' => '',
    ];
}

/**
 * @param array<string, mixed> $mobileInfo
 * @return array{ok:bool,status:string,error_code:string,message:string}
 */
function m360_estimate_send_customer_cartable_sms(array $mobileInfo, string $profileUrl): array
{
    $mobile = (string)($mobileInfo['mobile'] ?? '');
    if ($mobile === '' || empty($mobileInfo['real_format'])) {
        return ['ok' => false, 'status' => 'invalid_mobile', 'error_code' => 'INVALID_MOBILE', 'message' => 'invalid_mobile'];
    }
    if (!empty($mobileInfo['synthetic'])) {
        return ['ok' => false, 'status' => 'fake_mobile', 'error_code' => 'FAKE_MOBILE', 'message' => 'fake_mobile'];
    }

    require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
    if (!m360_otp_sms_configured()) {
        return ['ok' => false, 'status' => 'sms_not_configured', 'error_code' => 'CONFIG_NOT_FOUND', 'message' => 'sms_not_configured'];
    }

    $settings = m360_otp_sms_settings();
    if ((string)($settings['provider'] ?? '') !== 'ippanel' || !function_exists('m360_otp_ippanel_webservice_payload') || !function_exists('m360_otp_ippanel_send')) {
        return ['ok' => false, 'status' => 'sms_not_configured', 'error_code' => 'UNSUPPORTED_PROVIDER', 'message' => 'sms_not_configured'];
    }

    $message = 'مشتری گرامی، برآورد هزینه پرونده خودروی شما آماده بررسی است. برای مشاهده و تأیید، وارد کارتابل مشتری شوید: ' . $profileUrl;
    $payload = m360_otp_ippanel_webservice_payload($mobile, $message, $settings);
    $sent = m360_otp_ippanel_send($payload, (string)$settings['api_key']);
    if (!empty($sent['ok'])) {
        return ['ok' => true, 'status' => 'sent', 'error_code' => '', 'message' => 'sent'];
    }

    return [
        'ok' => false,
        'status' => 'provider_error',
        'error_code' => (string)($sent['error_code'] ?? 'UNKNOWN'),
        'message' => 'provider_error',
    ];
}

/**
 * @param array<string, mixed> $est
 * @param array<string, mixed> $jc
 * @return array{ok:bool,message:string,estimate_version_id?:int,task_id?:int,customer_url?:string,sms_status?:string,mobile_masked?:string,mobile_source?:string,mobile_verified?:bool,created_new_task?:bool,error_code?:string}
 */
function m360_estimate_send_existing_or_notify_customer($conn, array $est, array $jc, int $userId): array
{
    $estimateId = (int)($est['estimate_id'] ?? 0);
    $jobcardId = (int)($jc['jobcard_id'] ?? 0);
    $mobileInfo = m360_estimate_resolve_customer_mobile($conn, $est, $jc);
    if (!$mobileInfo['ok']) {
        return ['ok' => false, 'message' => 'شماره موبایل معتبر برای مشتری پیدا نشد. ابتدا شماره موبایل مشتری را اصلاح کنید.', 'sms_status' => 'invalid_mobile', 'error_code' => 'INVALID_MOBILE'];
    }

    $task = m360_estimate_reuse_or_sync_customer_task($conn, $est, $jc, $mobileInfo);
    if (!$task['ok']) {
        if ($task['message'] === 'missing_active_version') {
            $issueReason = strtoupper((string)($est['estimate_status'] ?? '')) === M360_EST_STATUS_REVISION
                ? M360_EST_ISSUE_REASON_REVISION
                : M360_EST_ISSUE_REASON_INITIAL;
            $issued = m360_estimate_issue_to_customer($conn, $estimateId, $jobcardId, $userId, $issueReason);
            if (!$issued['ok']) {
                return ['ok' => false, 'message' => (string)($issued['message'] ?? 'Estimate issue failed.'), 'sms_status' => 'not_attempted'];
            }
            $task = [
                'ok' => true,
                'task_id' => (int)($issued['task_id'] ?? 0),
                'created_new' => true,
                'estimate_version_id' => (int)($issued['estimate_version_id'] ?? 0),
                'message' => '',
            ];
        } else {
            return ['ok' => false, 'message' => 'وظیفه کارتابل مشتری قابل ایجاد یا استفاده مجدد نبود.', 'sms_status' => 'not_attempted', 'error_code' => (string)$task['message']];
        }
    }

    $profileUrl = m360_estimate_customer_profile_url();
    $sms = m360_estimate_send_customer_cartable_sms($mobileInfo, $profileUrl);
    $eventName = !empty($sms['ok']) ? 'ESTIMATE_CUSTOMER_CARTABLE_SMS_SENT' : 'ESTIMATE_CUSTOMER_CARTABLE_SMS_NOT_SENT';
    $eventNote = 'task_id=' . (string)$task['task_id']
        . '; mobile=' . (string)$mobileInfo['masked']
        . '; source=' . (string)$mobileInfo['source']
        . '; sms_status=' . (string)$sms['status']
        . (((string)$sms['error_code'] !== '') ? '; error=' . (string)$sms['error_code'] : '');
    m360_estimate_record_event($conn, $jobcardId, $eventName, $estimateId, $eventNote, $userId, (int)$task['estimate_version_id']);

    if (!empty($sms['ok'])) {
        $message = (bool)$task['created_new']
            ? 'ارسال شد؛ لینک بررسی برآورد به شماره ' . (string)$mobileInfo['masked'] . ' ارسال شد و در کارتابل مشتری قرار گرفت.'
            : 'این برآورد قبلاً در کارتابل مشتری فعال بوده است؛ پیامک مجدداً ارسال شد.';
    } elseif ($sms['status'] === 'fake_mobile') {
        $message = 'برآورد در کارتابل مشتری فعال است، اما شماره مشتری تستی/غیرواقعی است و پیامک واقعی ارسال نشد.';
    } elseif ($sms['status'] === 'sms_not_configured') {
        $message = 'برآورد در کارتابل مشتری فعال است، اما پیامک واقعی ارسال نشد؛ تنظیمات پیامک فعال نیست.';
    } elseif ($sms['status'] === 'provider_error') {
        $message = 'برآورد در کارتابل مشتری فعال است، اما ارسال پیامک خطا داد. خطای امن ثبت شد.';
    } else {
        $message = 'شماره موبایل معتبر برای مشتری پیدا نشد. ابتدا شماره موبایل مشتری را اصلاح کنید.';
    }

    return [
        'ok' => true,
        'message' => $message,
        'estimate_version_id' => (int)$task['estimate_version_id'],
        'task_id' => (int)$task['task_id'],
        'customer_url' => $profileUrl,
        'sms_status' => (string)$sms['status'],
        'mobile_masked' => (string)$mobileInfo['masked'],
        'mobile_source' => (string)$mobileInfo['source'],
        'mobile_verified' => (bool)$mobileInfo['verified'],
        'created_new_task' => (bool)$task['created_new'],
        'error_code' => (string)$sms['error_code'],
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function m360_estimate_apply_action($conn, string $action, int $userId, ?int $estimateId = null, ?int $jobcardId = null, array $payload = []): array
{
    if (!is_resource($conn)) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.'];
    }

    $action = strtolower(trim($action));
    if ($estimateId !== null && $estimateId > 0) {
        $est = m360_estimate_fetch($conn, $estimateId);
        if ($est === null) {
            return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
        }
        $jobcardId = (int)($est['jobcard_id'] ?? 0);
    } elseif ($jobcardId !== null && $jobcardId > 0) {
        $est = m360_estimate_fetch_active_for_jobcard($conn, $jobcardId);
        $estimateId = $est !== null ? (int)$est['estimate_id'] : 0;
    } else {
        return ['ok' => false, 'message' => 'شناسه برآورد یا کارت کار الزامی است.'];
    }

    $jc = m360_estimate_fetch_jobcard($conn, (int)$jobcardId);
    if ($jc === null) {
        return ['ok' => false, 'message' => 'کارت کار یافت نشد.'];
    }

    $gate = m360_estimate_assert_upstream_gates((int)$jobcardId, $jc);
    if (!$gate['ok'] && !in_array($action, ['create_draft'], true)) {
        return $gate;
    }

    switch ($action) {
        case 'create_draft':
            if ($est !== null && !in_array(strtoupper((string)$est['estimate_status']), [M360_EST_STATUS_CANCELLED], true)) {
                return ['ok' => true, 'message' => 'برآورد فعال از قبل وجود دارد.',];
            }
            $gate = m360_estimate_assert_upstream_gates((int)$jobcardId, $jc);
            if (!$gate['ok']) {
                return $gate;
            }
            $title = 'برآورد کارت کار ' . (string)($jc['jobcard_number'] ?? $jobcardId);
            $ok = customer_core_execute(
                $conn,
                'INSERT INTO dbo.' . M360_ESTIMATE_TABLE . ' (jobcard_id, customer_id, vehicle_id, estimate_title, estimate_status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $jobcardId,
                    (int)($jc['customer_id'] ?? 0) ?: null,
                    (int)($jc['vehicle_id'] ?? 0) ?: null,
                    $title,
                    M360_EST_STATUS_DRAFT,
                    $userId,
                ]
            );
            if ($ok === false) {
                return ['ok' => false, 'message' => 'ایجاد برآورد ناموفق بود.'];
            }
            $newId = (int)(customer_core_scalar(
                $conn,
                'SELECT TOP 1 estimate_id FROM dbo.' . M360_ESTIMATE_TABLE . ' WHERE jobcard_id = ? ORDER BY estimate_id DESC',
                [$jobcardId]
            ) ?? 0);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'current_estimate_id')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET current_estimate_id = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$newId, M360_EST_STATUS_DRAFT, $jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_DRAFT_CREATED', $newId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_ESTIMATE_CREATED', null, M360_EST_STATUS_DRAFT, 'Draft estimate created', $userId);
            return ['ok' => true, 'message' => 'پیش‌نویس برآورد ایجاد شد.'];

        case 'add_item':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'ابتدا برآورد ایجاد کنید.'];
            }
            if (!in_array(strtoupper((string)$est['estimate_status']), [M360_EST_STATUS_DRAFT, M360_EST_STATUS_INTERNAL_REVIEW, M360_EST_STATUS_REVISION], true)) {
                return ['ok' => false, 'message' => 'پس از ارسال به مشتری، افزودن آیتم مستقیم مجاز نیست.'];
            }
            $type = strtoupper(trim((string)($payload['item_type'] ?? 'SERVICE')));
            if (!in_array($type, M360_EST_ITEM_TYPES, true)) {
                $type = 'SERVICE';
            }
            $title = trim((string)($payload['item_title'] ?? ''));
            if ($title === '') {
                return ['ok' => false, 'message' => 'عنوان آیتم الزامی است.'];
            }
            $qty = max(0.01, (float)($payload['quantity'] ?? 1));
            $price = max(0, (float)($payload['unit_price'] ?? 0));
            $line = round($qty * $price, 2);
            $partReq = $type === 'PART' ? 1 : 0;
            customer_core_execute(
                $conn,
                'INSERT INTO dbo.' . M360_ESTIMATE_ITEM_TABLE . ' (estimate_id, jobcard_id, item_type, item_title, item_description, quantity, unit_price, line_total, part_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$estimateId, $jobcardId, $type, mb_substr($title, 0, 300), trim((string)($payload['item_description'] ?? '')) ?: null, $qty, $price, $line, $partReq]
            );
            m360_estimate_calculate_totals($conn, $estimateId);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_ITEM_ADDED', $estimateId, $title, $userId);
            return ['ok' => true, 'message' => 'آیتم اضافه شد.'];

        case 'remove_draft_item':
            $itemId = (int)($payload['estimate_item_id'] ?? 0);
            if ($estimateId < 1 || $itemId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'آیتم نامعتبر است.'];
            }
            if (!in_array(strtoupper((string)$est['estimate_status']), [M360_EST_STATUS_DRAFT, M360_EST_STATUS_INTERNAL_REVIEW], true)) {
                return ['ok' => false, 'message' => 'حذف آیتم پس از ارسال مجاز نیست.'];
            }
            customer_core_execute($conn, "UPDATE dbo." . M360_ESTIMATE_ITEM_TABLE . " SET item_status = N'REMOVED' WHERE estimate_item_id = ? AND estimate_id = ?", [$itemId, $estimateId]);
            m360_estimate_calculate_totals($conn, $estimateId);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_ITEM_REMOVED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'آیتم حذف شد.'];

        case 'calculate_totals':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $r = m360_estimate_calculate_totals($conn, $estimateId);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_TOTALS_CALCULATED', $estimateId, null, $userId);
            return ['ok' => $r['ok'], 'message' => $r['message']];

        case 'internal_review':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_INTERNAL_REVIEW, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_INTERNAL_REVIEW', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'بازبینی داخلی ثبت شد.'];

        case 'send_to_customer':
            if ($estimateId > 0 && $est !== null && m360_estimate_version_tables_available($conn)) {
                return m360_estimate_send_existing_or_notify_customer($conn, $est, $jc, $userId);
            }
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            if (!m360_estimate_version_tables_available($conn)) {
                return ['ok' => false, 'message' => 'نسخه‌بندی برآورد فعال نیست.'];
            }
            $issueReason = strtoupper((string)($est['estimate_status'] ?? '')) === M360_EST_STATUS_REVISION
                ? M360_EST_ISSUE_REASON_REVISION
                : M360_EST_ISSUE_REASON_INITIAL;
            $issued = m360_estimate_issue_to_customer($conn, $estimateId, $jobcardId, $userId, $issueReason);
            if (!$issued['ok']) {
                return ['ok' => false, 'message' => (string)($issued['message'] ?? 'صدور برآورد ناموفق بود.')];
            }

            return [
                'ok' => true,
                'message' => (string)($issued['message'] ?? 'برآورد برای مشتری ارسال شد.'),
                'estimate_version_id' => (int)($issued['estimate_version_id'] ?? 0),
                'task_id' => (int)($issued['task_id'] ?? 0),
                'customer_url' => (string)($issued['customer_url'] ?? ''),
            ];

        case 'mark_parts_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_required = 1, parts_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_PARTS_PENDING, M360_EST_STATUS_PARTS_PENDING, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_PENDING', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'نیاز قطعه ثبت شد.'];

        case 'mark_parts_not_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_required = 0, parts_gate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_PARTS_NOT_REQUIRED, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_NOT_REQUIRED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'قطعه لازم نیست.'];

        case 'clear_parts_gate':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $pe = m360_parts_gate_evaluate($conn, $estimateId);
            if ($pe['parts_gate_status'] === M360_GATE_PARTS_PENDING && (bool)($est['parts_required'] ?? false)) {
                m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_PENDING_PURCHASE_REQUIRED', $estimateId, $pe['message'], $userId);
                return ['ok' => false, 'message' => 'قطعات هنوز آماده نیستند.'];
            }
            $status = $pe['parts_gate_status'];
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET parts_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [$status, M360_EST_STATUS_PARTS_CLEARED, $estimateId]);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'parts_gate_status')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET parts_gate_status = ? WHERE jobcard_id = ?', [$status, $jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_PARTS_GATE_CLEARED', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_PARTS_GATE_CLEARED', null, $status, 'Parts gate cleared', $userId);
            return ['ok' => true, 'message' => 'گیت قطعه باز شد.'];

        case 'mark_finance_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            m360_estimate_calculate_totals($conn, $estimateId);
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET finance_required = 1, finance_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_FINANCE_PENDING, M360_EST_STATUS_FIN_PENDING, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_PENDING', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'نیاز مالی ثبت شد.'];

        case 'mark_finance_not_required':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET finance_required = 0, finance_gate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_GATE_FINANCE_NOT_REQUIRED, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_NOT_REQUIRED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'علی‌الحساب لازم نیست.'];

        case 'clear_finance_gate':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $fe = m360_finance_gate_evaluate($conn, $estimateId);
            if ($fe['finance_gate_status'] === M360_GATE_FINANCE_PENDING && (bool)($est['finance_required'] ?? true)) {
                m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_BLOCKED', $estimateId, $fe['message'], $userId);
                return ['ok' => false, 'message' => 'مجوز مالی هنوز کامل نیست. علی‌الحساب: ' . number_format($fe['advance_required']) . ' تومان'];
            }
            $status = $fe['finance_gate_status'];
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET finance_gate_status = ?, estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [$status, M360_EST_STATUS_FIN_CLEARED, $estimateId]);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'finance_gate_status')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET finance_gate_status = ? WHERE jobcard_id = ?', [$status, $jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_FINANCE_GATE_CLEARED', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_FINANCE_GATE_CLEARED', null, $status, 'Finance gate cleared', $userId);
            return ['ok' => true, 'message' => 'گیت مالی باز شد.'];

        case 'approve_for_work':
            if ($estimateId < 1 || $est === null) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            $est = m360_estimate_fetch($conn, $estimateId);
            $can = m360_gates_can_approve_for_work($conn, $est);
            if (!$can['ok']) {
                m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_APPROVED_FOR_WORK_BLOCKED_GATE', $estimateId, 'Gates not cleared', $userId);
                return ['ok' => false, 'message' => 'ادامه کار مجاز نیست — تأیید مشتری، قطعه یا مالی کامل نشده است.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_APPROVED_WORK, $estimateId]);
            if (customer_core_column_exists($conn, 'erp_jobcards', 'approved_for_work_at')) {
                customer_core_execute($conn, 'UPDATE dbo.erp_jobcards SET estimate_status = N\'APPROVED_FOR_WORK\', approved_for_work_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME() WHERE jobcard_id = ?', [$jobcardId]);
            }
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_APPROVED_FOR_WORK', $estimateId, null, $userId);
            m360_estimate_jobcard_history($conn, $jobcardId, 'JOBCARD_APPROVED_FOR_WORK', null, 'APPROVED_FOR_WORK', 'Approved for work', $userId);
            return ['ok' => true, 'message' => 'پرونده برای ادامه کار مجاز شد.'];

        case 'cancel':
            if ($estimateId < 1) {
                return ['ok' => false, 'message' => 'برآورد یافت نشد.'];
            }
            customer_core_execute($conn, 'UPDATE dbo.' . M360_ESTIMATE_TABLE . ' SET estimate_status = ?, updated_at = SYSUTCDATETIME() WHERE estimate_id = ?', [M360_EST_STATUS_CANCELLED, $estimateId]);
            m360_estimate_record_event($conn, $jobcardId, 'ESTIMATE_CANCELLED', $estimateId, null, $userId);
            return ['ok' => true, 'message' => 'برآورد لغو شد.'];

        default:
            return ['ok' => false, 'message' => 'عملیات نامعتبر است.'];
    }
}
