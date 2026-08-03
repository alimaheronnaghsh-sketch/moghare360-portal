<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1.5 — Contract OTP + digital signature helper.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-intake-contract-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-otp-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-customer-cartable-helper.php';

const M360_CONTRACT_OTP_TTL = 120;
const M360_CONTRACT_OTP_RESEND = 60;
const M360_CONTRACT_CONSENT_TEXT_FA = 'متن قرارداد را کامل مطالعه کردم و مفاد آن را می‌پذیرم.';
const M360_CONTRACT_OTP_PURPOSE = 'CONTRACT_CONFIRMATION';
const M360_CONTRACT_TASK_CONTEXT_SESSION = 'm360_contract_task_context';
const M360_CONTRACT_ENTRY_TASK = 'TASK_CARTABLE';
const M360_CONTRACT_ENTRY_TOKEN = 'SECURE_TOKEN';
const M360_CONTRACT_CUSTOMER_CSRF_PURPOSE = 'intake_contract_customer_review';

function m360_contract_customer_csrf_token(): string
{
    if (!function_exists('erp_csrf_get_or_create_token')) {
        customer_core_require_helper('erp-csrf.php');
    }

    return erp_csrf_get_or_create_token(M360_CONTRACT_CUSTOMER_CSRF_PURPOSE);
}

function m360_contract_customer_csrf_input_html(): string
{
    $token = m360_contract_customer_csrf_token();

    return '<input type="hidden" name="erp_csrf_token" value="'
        . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
}

function m360_contract_customer_csrf_is_valid(?string $token): bool
{
    if (!function_exists('erp_csrf_validate_token')) {
        customer_core_require_helper('erp-csrf.php');
    }

    return erp_csrf_validate_token(M360_CONTRACT_CUSTOMER_CSRF_PURPOSE, trim((string)($token ?? '')));
}
/**
 * @return array{
 *   task_id:int,
 *   contract_id:int,
 *   online_request_id:int,
 *   customer_id:int,
 *   mobile:string,
 *   contract_body_hash:string,
 *   issued_at:int
 * }|null
 */
function m360_contract_get_task_context(): ?array
{
    m360_contract_sig_session_start();
    $bag = $_SESSION[M360_CONTRACT_TASK_CONTEXT_SESSION] ?? null;
    if (!is_array($bag)) {
        return null;
    }
    $issuedAt = (int)($bag['issued_at'] ?? 0);
    if ($issuedAt < 1 || (time() - $issuedAt) > 7200) {
        unset($_SESSION[M360_CONTRACT_TASK_CONTEXT_SESSION]);

        return null;
    }

    return $bag;
}

/**
 * @param array{
 *   task_id:int,
 *   contract_id:int,
 *   online_request_id:int,
 *   customer_id:int,
 *   mobile:string,
 *   contract_body_hash:string
 * } $context
 */
function m360_contract_store_task_context(array $context): void
{
    m360_contract_sig_session_start();
    $_SESSION[M360_CONTRACT_TASK_CONTEXT_SESSION] = [
        'task_id' => (int)($context['task_id'] ?? 0),
        'contract_id' => (int)($context['contract_id'] ?? 0),
        'online_request_id' => (int)($context['online_request_id'] ?? 0),
        'customer_id' => (int)($context['customer_id'] ?? 0),
        'mobile' => m360_cartable_normalize_mobile((string)($context['mobile'] ?? '')),
        'contract_body_hash' => trim((string)($context['contract_body_hash'] ?? '')),
        'issued_at' => time(),
    ];
}

function m360_contract_clear_task_context(): void
{
    m360_contract_sig_session_start();
    unset($_SESSION[M360_CONTRACT_TASK_CONTEXT_SESSION]);
}

/**
 * @param array{token?:string,task_id?:int} $input
 * @return array{
 *   ok:bool,
 *   message:string,
 *   http_status:int,
 *   entry_mode:string,
 *   contract:?array,
 *   task:?array,
 *   request:?array,
 *   payload:array,
 *   raw_token:string,
 *   task_id:int,
 *   read_only:bool,
 *   redirect_login:bool
 * }
 */
function m360_contract_resolve_customer_context($conn, array $input): array
{
    $empty = [
        'ok' => false,
        'message' => 'مأموریت قرارداد مشتری یافت نشد یا منقضی شده است.',
        'http_status' => 403,
        'entry_mode' => '',
        'contract' => null,
        'task' => null,
        'request' => null,
        'payload' => [],
        'raw_token' => '',
        'task_id' => 0,
        'read_only' => false,
        'redirect_login' => false,
    ];
    if (!is_resource($conn)) {
        return array_merge($empty, ['message' => 'خطا در اتصال به سامانه.', 'http_status' => 503]);
    }

    $taskId = (int)($input['task_id'] ?? 0);
    $rawToken = trim((string)($input['token'] ?? ''));

    if ($taskId > 0) {
        if (!function_exists('m360_rw_customer_profile_resolve_verified_session_mobile')) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
        }
        $session = m360_rw_customer_profile_resolve_verified_session_mobile();
        if (!$session['ok']) {
            return array_merge($empty, [
                'message' => 'ابتدا احراز هویت موبایل مشتری باید تکمیل شود.',
                'http_status' => 401,
                'redirect_login' => true,
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
            ]);
        }
        $sessionMobile = m360_cartable_normalize_mobile($session['mobile']);
        $task = m360_cartable_fetch_task_by_id($conn, $taskId);
        if ($task === null || (string)($task['task_type'] ?? '') !== M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE) {
            return array_merge($empty, [
                'message' => 'وظیفه قرارداد معتبر یافت نشد.',
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
            ]);
        }
        $taskStatus = strtoupper((string)($task['status'] ?? ''));
        $isActive = (int)($task['is_active'] ?? 0) === 1;
        $contractId = (int)($task['contract_id'] ?? 0);
        if ($contractId < 1) {
            return array_merge($empty, [
                'message' => 'قرارداد مرتبط با وظیفه یافت نشد.',
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
                'task' => $task,
            ]);
        }
        $contract = m360_intake_contract_fetch_by_id($conn, $contractId);
        if ($contract === null) {
            return array_merge($empty, [
                'message' => 'قرارداد یافت نشد.',
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
                'task' => $task,
            ]);
        }
        $contractStatus = strtoupper((string)($contract['contract_status'] ?? ''));
        if (in_array($contractStatus, [M360_CONTRACT_STATUS_CANCELLED, M360_CONTRACT_STATUS_EXPIRED], true)) {
            return array_merge($empty, [
                'message' => 'این قرارداد دیگر قابل اقدام نیست.',
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
                'task' => $task,
                'contract' => $contract,
            ]);
        }
        $signed = m360_intake_contract_is_signed($contract);
        if (!$signed) {
            if (!$isActive || !in_array($taskStatus, [M360_CARTABLE_STATUS_PENDING, M360_CARTABLE_STATUS_OPENED], true)) {
                return array_merge($empty, [
                    'message' => 'این وظیفه دیگر فعال نیست.',
                    'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                    'task_id' => $taskId,
                    'task' => $task,
                    'contract' => $contract,
                ]);
            }
        }
        $customerId = (int)($task['customer_id'] ?? 0);
        if ($customerId < 1) {
            $customerId = (int)($contract['customer_id'] ?? 0);
        }
        if (!m360_cartable_task_belongs_to_customer($task, $customerId > 0 ? $customerId : null, $sessionMobile)) {
            return array_merge($empty, [
                'message' => 'دسترسی به این وظیفه مجاز نیست.',
                'http_status' => 403,
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
            ]);
        }
        $canonicalMobile = m360_contract_resolve_canonical_customer_mobile($conn, $contract);
        if (!$canonicalMobile['ok']) {
            return array_merge($empty, [
                'message' => $canonicalMobile['message'],
                'http_status' => 403,
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
            ]);
        }
        if ($sessionMobile === '' || !hash_equals($sessionMobile, $canonicalMobile['mobile'])) {
            return array_merge($empty, [
                'message' => 'هویت موبایل تأییدشده با شماره تأیید پیامکی قرارداد مطابقت ندارد.',
                'http_status' => 403,
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
            ]);
        }
        $requestId = (int)($task['online_request_id'] ?? ($contract['online_request_id'] ?? 0));
        $request = $requestId > 0 && function_exists('m360_online_req_fetch_by_id')
            ? m360_online_req_fetch_by_id($conn, $requestId)
            : null;
        if ($requestId > 0 && is_array($request)) {
            $requestCustomerId = (int)($request['customer_id'] ?? 0);
            if ($requestCustomerId > 0 && $customerId > 0 && $requestCustomerId !== $customerId) {
                return array_merge($empty, [
                    'message' => 'مالکیت پرونده با قرارداد مطابقت ندارد.',
                    'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                    'task_id' => $taskId,
                ]);
            }
        }
        $tokenHash = trim((string)($task['action_token_hash'] ?? ''));
        $contractTokenHash = trim((string)($contract['secure_token_hash'] ?? ''));
        if ($tokenHash !== '' && $contractTokenHash !== '' && !hash_equals($contractTokenHash, $tokenHash)) {
            return array_merge($empty, [
                'message' => 'ارجاع امنیتی وظیفه با قرارداد مطابقت ندارد.',
                'entry_mode' => M360_CONTRACT_ENTRY_TASK,
                'task_id' => $taskId,
            ]);
        }
        $payload = [];
        if (is_array($request) && function_exists('m360_online_req_parse_payload')) {
            $payload = m360_online_req_parse_payload($request['request_payload_json'] ?? null);
            if (function_exists('m360_rw_intake_payload_for_recovery')) {
                $payload = m360_rw_intake_payload_for_recovery($payload);
            }
        }
        m360_contract_store_task_context([
            'task_id' => $taskId,
            'contract_id' => $contractId,
            'online_request_id' => $requestId,
            'customer_id' => $customerId,
            'mobile' => $sessionMobile,
            'contract_body_hash' => (string)($contract['contract_body_hash'] ?? ''),
        ]);

        return [
            'ok' => true,
            'message' => '',
            'http_status' => 200,
            'entry_mode' => M360_CONTRACT_ENTRY_TASK,
            'contract' => $contract,
            'task' => $task,
            'request' => $request,
            'payload' => is_array($payload) ? $payload : [],
            'raw_token' => '',
            'task_id' => $taskId,
            'read_only' => $signed,
            'redirect_login' => false,
        ];
    }

    if ($rawToken === '') {
        return $empty;
    }

    $resolved = m360_contract_resolve_token($rawToken);
    if (!$resolved['ok'] || !is_array($resolved['contract'])) {
        return array_merge($empty, [
            'message' => (string)$resolved['message'],
            'entry_mode' => M360_CONTRACT_ENTRY_TOKEN,
            'raw_token' => $rawToken,
        ]);
    }
    $contract = $resolved['contract'];
    $requestId = 0;
    if (function_exists('m360_rw_intake_contract_parse_review_token')) {
        $requestId = m360_rw_intake_contract_parse_review_token($rawToken);
    }
    if ($requestId < 1) {
        $requestId = (int)($contract['online_request_id'] ?? 0);
    }
    $request = null;
    $payload = [];
    if ($requestId > 0 && function_exists('m360_online_req_fetch_by_id')) {
        $request = m360_online_req_fetch_by_id($conn, $requestId);
        if ($request === null) {
            return array_merge($empty, [
                'message' => 'مأموریت قرارداد مشتری یافت نشد یا منقضی شده است.',
                'entry_mode' => M360_CONTRACT_ENTRY_TOKEN,
                'raw_token' => $rawToken,
            ]);
        }
        $payload = function_exists('m360_online_req_parse_payload')
            ? m360_online_req_parse_payload($request['request_payload_json'] ?? null)
            : [];
        if (function_exists('m360_rw_intake_payload_for_recovery')) {
            $payload = m360_rw_intake_payload_for_recovery($payload);
        }
        if (function_exists('m360_rw_intake_contract_validate_review_token')) {
            $tokenCheck = m360_rw_intake_contract_validate_review_token($payload, $requestId, $rawToken);
            if (!$tokenCheck['ok']) {
                // Payload review TTL can expire while DB contract remains unsigned + cartable-active.
                // Refresh payload/cartable expiry only for unsigned contracts (never fake-sign).
                $unsigned = !m360_intake_contract_is_signed($contract);
                $statusUpper = strtoupper((string)($contract['contract_status'] ?? ''));
                $refreshableStatus = in_array($statusUpper, [
                    M360_CONTRACT_STATUS_SENT,
                    M360_CONTRACT_STATUS_VIEWED,
                    M360_CONTRACT_STATUS_OTP_SENT,
                    M360_CONTRACT_STATUS_GENERATED,
                    M360_CONTRACT_STATUS_EXPIRED,
                ], true);
                $hasActiveCartableToken = false;
                $contractIdForCartable = (int)($contract['contract_id'] ?? 0);
                if ($unsigned && $contractIdForCartable > 0 && function_exists('m360_cartable_tables_available') && m360_cartable_tables_available($conn)) {
                    $activeTaskToken = m360_cartable_find_active_by_source(
                        $conn,
                        M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
                        M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
                        (string)$contractIdForCartable,
                        M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE
                    );
                    $hasActiveCartableToken = $activeTaskToken !== null;
                }
                $onlyExpired = !empty($tokenCheck['expired']);
                if ($unsigned && $onlyExpired && ($hasActiveCartableToken || $refreshableStatus)
                    && function_exists('m360_rw_intake_ensure_nested')
                    && function_exists('m360_rw_intake_persist_payload')
                ) {
                    $newExpiresIso = gmdate('c', time() + M360_CONTRACT_TOKEN_TTL_SECONDS);
                    $newExpiresSql = gmdate('Y-m-d H:i:s', time() + M360_CONTRACT_TOKEN_TTL_SECONDS);
                    $payload = m360_rw_intake_ensure_nested($payload);
                    if (!isset($payload['reception_intake']) || !is_array($payload['reception_intake'])) {
                        $payload['reception_intake'] = [];
                    }
                    if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
                        $payload['reception_intake']['contract'] = [];
                    }
                    if (!isset($payload['reception_intake']['customer_cartable']) || !is_array($payload['reception_intake']['customer_cartable'])) {
                        $payload['reception_intake']['customer_cartable'] = [];
                    }
                    if (!isset($payload['reception_intake']['customer_cartable']['contract_task']) || !is_array($payload['reception_intake']['customer_cartable']['contract_task'])) {
                        $payload['reception_intake']['customer_cartable']['contract_task'] = [];
                    }
                    $payload['reception_intake']['contract']['review_token_expires_at'] = $newExpiresIso;
                    $payload['reception_intake']['customer_cartable']['contract_task']['access_token_expires_at'] = $newExpiresIso;
                    m360_rw_intake_persist_payload($conn, $requestId, $payload, []);
                    if ($contractIdForCartable > 0) {
                        customer_core_execute(
                            $conn,
                            'UPDATE dbo.erp_customer_cartable_tasks
                             SET action_expires_at = ?, updated_at = SYSUTCDATETIME()
                             WHERE contract_id = ? AND task_type = ? AND is_active = 1
                               AND status IN (?, ?)',
                            [
                                $newExpiresSql,
                                $contractIdForCartable,
                                M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE,
                                M360_CARTABLE_STATUS_PENDING,
                                M360_CARTABLE_STATUS_OPENED,
                            ]
                        );
                    }
                    m360_intake_contract_record_event(
                        $conn,
                        $contractIdForCartable,
                        'CONTRACT_PAYLOAD_TOKEN_TTL_REFRESHED',
                        'unsigned_expired_payload_token',
                        null
                    );
                } else {
                    return array_merge($empty, [
                        'message' => (string)($tokenCheck['error'] ?? $empty['message']),
                        'entry_mode' => M360_CONTRACT_ENTRY_TOKEN,
                        'raw_token' => $rawToken,
                    ]);
                }
            }
        }
    }

    if (!function_exists('m360_rw_customer_profile_resolve_verified_session_mobile')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
    }
    $session = m360_rw_customer_profile_resolve_verified_session_mobile();
    if ($session['ok']) {
        $sessionMobile = m360_cartable_normalize_mobile($session['mobile']);
        $canonicalMobile = m360_contract_resolve_canonical_customer_mobile($conn, $contract);
        if (!$canonicalMobile['ok']) {
            return array_merge($empty, [
                'message' => $canonicalMobile['message'],
                'http_status' => 403,
                'entry_mode' => M360_CONTRACT_ENTRY_TOKEN,
                'raw_token' => $rawToken,
                'contract' => $contract,
            ]);
        }
        if ($sessionMobile !== '' && !hash_equals($sessionMobile, $canonicalMobile['mobile'])) {
            return array_merge($empty, [
                'message' => 'هویت موبایل تأییدشده با شماره تأیید پیامکی قرارداد مطابقت ندارد.',
                'http_status' => 403,
                'entry_mode' => M360_CONTRACT_ENTRY_TOKEN,
                'raw_token' => $rawToken,
                'contract' => $contract,
            ]);
        }
    }

    return [
        'ok' => true,
        'message' => '',
        'http_status' => 200,
        'entry_mode' => M360_CONTRACT_ENTRY_TOKEN,
        'contract' => $contract,
        'task' => null,
        'request' => $request,
        'payload' => is_array($payload) ? $payload : [],
        'raw_token' => $rawToken,
        'task_id' => 0,
        'read_only' => m360_intake_contract_is_signed($contract),
        'redirect_login' => false,
    ];
}

function m360_contract_workflow_review_completed(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['review_completed_at'] ?? '')) !== '';
}

function m360_contract_workflow_consent_accepted(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['consent_at'] ?? '')) !== '';
}

function m360_contract_workflow_signature_draft_ready(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['signature_draft_hash'] ?? '')) !== '';
}

function m360_contract_workflow_signature_confirmed(array $contractRow): bool
{
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);

    return trim((string)($workflow['signature_confirmed_at'] ?? '')) !== '';
}

function m360_contract_mask_mobile(string $mobile): string
{
    $digits = preg_replace('/\D+/', '', $mobile) ?? '';
    if (strlen($digits) < 8) {
        return '***';
    }

    return substr($digits, 0, 4) . '***' . substr($digits, -4);
}

/**
 * @return array{ok:bool,mobile:string,source:string,message:string}
 */
function m360_contract_resolve_canonical_customer_mobile($conn, array $contractRow): array
{
    if (!is_resource($conn)) {
        $conn = customer_core_db();
    }
    if (!is_resource($conn)) {
        return ['ok' => false, 'mobile' => '', 'source' => '', 'message' => 'خطا در اتصال به سامانه.'];
    }

    $customerId = (int)($contractRow['customer_id'] ?? 0);
    $requestId = (int)($contractRow['online_request_id'] ?? 0);
    if ($customerId < 1 && $requestId > 0 && function_exists('m360_online_req_fetch_by_id')) {
        $request = m360_online_req_fetch_by_id($conn, $requestId);
        $customerId = is_array($request) ? (int)($request['customer_id'] ?? 0) : 0;
    }
    if ($customerId < 1) {
        return ['ok' => false, 'mobile' => '', 'source' => '', 'message' => 'شناسه مشتری قرارداد معتبر نیست.'];
    }

    if (customer_core_table_exists($conn, 'erp_customer_phones')) {
        $phones = customer_core_fetch_rows(
            $conn,
            "SELECT phone_number, is_primary, do_not_contact, lifecycle_state
             FROM dbo.erp_customer_phones
             WHERE customer_id = ?
               AND phone_type = N'MOBILE'
               AND lifecycle_state = N'ACTIVE'
               AND ISNULL(do_not_contact, 0) = 0
             ORDER BY is_primary DESC, phone_id ASC",
            [$customerId]
        );
        foreach ($phones as $phone) {
            $mobile = m360_cartable_normalize_mobile((string)($phone['phone_number'] ?? ''));
            if (preg_match('/^09\d{9}$/', $mobile) === 1) {
                return [
                    'ok' => true,
                    'mobile' => $mobile,
                    'source' => 'erp_customer_phones',
                    'message' => '',
                ];
            }
        }
    }

    $primary = customer_core_scalar(
        $conn,
        "SELECT TOP 1 primary_mobile FROM dbo.erp_customers WHERE customer_id = ? AND lifecycle_state = N'ACTIVE'",
        [$customerId]
    );
    $mobile = m360_cartable_normalize_mobile((string)($primary ?? ''));
    if (preg_match('/^09\d{9}$/', $mobile) === 1) {
        return [
            'ok' => true,
            'mobile' => $mobile,
            'source' => 'erp_customers.primary_mobile',
            'message' => '',
        ];
    }

    return ['ok' => false, 'mobile' => '', 'source' => '', 'message' => 'شماره موبایل فعال و قابل استفاده برای مشتری قرارداد یافت نشد.'];
}

/**
 * @return array{ok:bool,mobile:string,message:string,source?:string}
 */
function m360_contract_require_verified_session_mobile_for_contract(array $contractRow): array
{
    if (!function_exists('m360_rw_customer_profile_resolve_verified_session_mobile')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
    }
    $resolved = m360_rw_customer_profile_resolve_verified_session_mobile();
    if (!$resolved['ok']) {
        return ['ok' => false, 'mobile' => '', 'message' => 'ابتدا احراز هویت موبایل مشتری باید تکمیل شود.'];
    }
    $sessionMobile = m360_cartable_normalize_mobile($resolved['mobile']);
    $canonical = m360_contract_resolve_canonical_customer_mobile(customer_core_db(), $contractRow);
    if (!$canonical['ok']) {
        return ['ok' => false, 'mobile' => '', 'message' => $canonical['message'], 'source' => $canonical['source']];
    }
    if ($sessionMobile === '' || !hash_equals($sessionMobile, $canonical['mobile'])) {
        return ['ok' => false, 'mobile' => '', 'message' => 'هویت موبایل تأییدشده با شماره تأیید پیامکی قرارداد مطابقت ندارد.', 'source' => $canonical['source']];
    }

    return ['ok' => true, 'mobile' => $canonical['mobile'], 'message' => '', 'source' => $canonical['source']];
}

/** @return array{ok:bool,message:string} */
function m360_contract_mark_review_completed(array $contractRow): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $patch = ['review_completed_at' => gmdate('c')];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if ($result['ok']) {
        m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_REVIEW_COMPLETED', null, null);
        customer_core_execute(
            $conn,
            'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET viewed_at = COALESCE(viewed_at, SYSUTCDATETIME()), contract_status = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ? AND contract_status IN (?, ?)',
            [M360_CONTRACT_STATUS_VIEWED, $contractId, M360_CONTRACT_STATUS_SENT, M360_CONTRACT_STATUS_GENERATED]
        );
    }

    return $result;
}

/** @return array{ok:bool,message:string} */
function m360_contract_mark_consent_accepted(array $contractRow): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید متن قرارداد را تا انتها مطالعه کنید.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $patch = [
        'consent_at' => gmdate('c'),
        'consent_text' => M360_CONTRACT_CONSENT_TEXT_FA,
    ];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if ($result['ok']) {
        m360_intake_contract_record_event($conn, $contractId, 'CUSTOMER_CONSENT_ACCEPTED', M360_CONTRACT_CONSENT_TEXT_FA, null);
    }

    return $result;
}

/** @return array{ok:bool,message:string} */
function m360_contract_confirm_review_and_consent(array $contractRow): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        $review = m360_contract_mark_review_completed($contractRow);
        if (!$review['ok']) {
            return $review;
        }
        $contractRow = m360_intake_contract_fetch_by_id(customer_core_db(), (int)($contractRow['contract_id'] ?? 0)) ?? $contractRow;
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return m360_contract_mark_consent_accepted($contractRow);
    }

    return ['ok' => true, 'message' => 'مطالعه و پذیرش قرارداد ثبت شد.'];
}

/**
 * Customer returns contract for correction without signing.
 * Uses existing cartable event + request payload flags (no schema change).
 *
 * @param resource|false $conn
 * @param array<string, mixed> $contractRow
 * @param array<string, mixed>|null $request
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string,payload:array<string,mixed>}
 */
function m360_contract_request_correction($conn, array $contractRow, ?array $request, array $payload, string $note = '', int $taskId = 0): array
{
    if (!is_resource($conn)) {
        return ['ok' => false, 'message' => 'اتصال به پایگاه داده برقرار نشد.', 'payload' => $payload];
    }
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد امضاشده قابل برگشت برای اصلاح نیست.', 'payload' => $payload];
    }

    $payload = m360_rw_intake_ensure_nested($payload);
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $note = trim(mb_substr($note, 0, 500));
    if (!isset($payload['reception_intake']['contract']) || !is_array($payload['reception_intake']['contract'])) {
        $payload['reception_intake']['contract'] = [];
    }
    $payload['reception_intake']['contract']['customer_correction_requested'] = true;
    $payload['reception_intake']['contract']['customer_correction_status'] = 'RETURNED_FOR_CORRECTION';
    $payload['reception_intake']['contract']['customer_correction_at'] = $now;
    $payload['reception_intake']['contract']['customer_correction_note'] = $note !== ''
        ? $note
        : 'مشتری اعلام کرد اطلاعات قرارداد نیاز به اصلاح دارد.';
    $payload['reception_intake']['contract']['status'] = 'RETURNED_FOR_CORRECTION';
    $payload['contract_status'] = 'RETURNED_FOR_CORRECTION';

    if (!isset($payload['reception_intake']['customer_cartable']) || !is_array($payload['reception_intake']['customer_cartable'])) {
        $payload['reception_intake']['customer_cartable'] = [];
    }
    $task = m360_rw_intake_contract_cartable_task($payload);
    $payload['reception_intake']['customer_cartable']['contract_task'] = array_merge($task, [
        'status' => 'RETURNED_FOR_CORRECTION',
        'returned_at' => $now,
        'is_active' => false,
    ]);

    if ($taskId < 1) {
        $taskId = (int)($task['task_id'] ?? 0);
    }
    if ($taskId > 0 && function_exists('m360_cartable_cancel_task')) {
        m360_cartable_cancel_task(
            $conn,
            $taskId,
            'CUSTOMER',
            null,
            [
                'reason' => 'customer_requested_correction',
                'note' => $payload['reception_intake']['contract']['customer_correction_note'],
            ]
        );
        if (defined('M360_CARTABLE_EVENT_CUSTOMER_CORRECTION_REQUESTED')) {
            m360_cartable_append_event(
                $conn,
                $taskId,
                M360_CARTABLE_EVENT_CUSTOMER_CORRECTION_REQUESTED,
                'CUSTOMER',
                null,
                (string)($task['status'] ?? M360_CARTABLE_STATUS_OPENED),
                M360_CARTABLE_STATUS_CANCELLED,
                ['note' => $payload['reception_intake']['contract']['customer_correction_note']]
            );
        }
    }

    $contractId = (int)($contractRow['contract_id'] ?? 0);
    if ($contractId > 0) {
        m360_intake_contract_record_event(
            $conn,
            $contractId,
            'CUSTOMER_CORRECTION_REQUESTED',
            $payload['reception_intake']['contract']['customer_correction_note'],
            null
        );
    }

    $requestId = (int)($request['online_request_id'] ?? $contractRow['online_request_id'] ?? 0);
    if ($requestId > 0) {
        $persist = m360_rw_intake_persist_payload($conn, $requestId, $payload, []);
        if (!$persist['ok']) {
            return ['ok' => false, 'message' => (string)($persist['message'] ?? 'ذخیره وضعیت اصلاح ناموفق بود.'), 'payload' => $payload];
        }
    }

    return [
        'ok' => true,
        'message' => 'درخواست اصلاح ثبت شد. پذیرش پرونده را بازبینی می‌کند.',
        'payload' => $payload,
    ];
}

/** @return array{ok:bool,message:string} */
function m360_contract_save_signature_draft(array $contractRow, string $signatureImageData): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید پذیرش صریح متن قرارداد را ثبت کنید.'];
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        return ['ok' => false, 'message' => 'امضای مستقیم روی صفحه الزامی است.'];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $sigHash = m360_intake_contract_hash($signatureImageData);
    $patch = [
        'signature_draft_hash' => $sigHash,
        'signature_draft_at' => gmdate('c'),
    ];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if ($result['ok']) {
        m360_contract_sig_session_start();
        $_SESSION['m360_contract_sig_draft_' . $contractId] = $signatureImageData;
        m360_intake_contract_record_event($conn, $contractId, 'DIRECT_SIGNATURE_CAPTURED', 'hash=' . substr($sigHash, 0, 16), null);
    }

    return $result;
}

/**
 * @return array{ok:bool,message:string,signature_hash?:string}
 */
function m360_contract_confirm_signature_locked(array $contractRow, string $signatureImageData): array
{
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'قرارداد قبلاً تأیید شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید مطالعه کامل قرارداد ثبت شود.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید پذیرش صریح متن قرارداد ثبت شود.'];
    }
    if (m360_contract_workflow_signature_confirmed($contractRow)) {
        return ['ok' => true, 'message' => 'امضای شما قبلاً ثبت و قفل شده است.'];
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        return ['ok' => false, 'message' => 'امضای مستقیم روی صفحه الزامی است.'];
    }
    if (!str_starts_with($signatureImageData, 'data:image/')) {
        return ['ok' => false, 'message' => 'فرمت امضا معتبر نیست.'];
    }

    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $snapshot = m360_intake_contract_snapshot_from_row($contractRow);
    $bodyHash = (string)($contractRow['contract_body_hash'] ?? '');
    if ($bodyHash === '') {
        $html = m360_contract_render_html($snapshot, true);
        $bodyHash = m360_intake_contract_hash($html);
    }
    $sigHash = m360_intake_contract_hash($signatureImageData);
    $now = gmdate('c');
    $patch = [
        'signature_draft_hash' => $sigHash,
        'signature_draft_at' => $now,
        'signature_confirmed_at' => $now,
        'signature_confirmed_hash' => $sigHash,
        'signature_contract_body_hash' => $bodyHash,
        'signature_contract_version' => M360_CONTRACT_VERSION,
    ];
    $result = m360_intake_contract_patch_workflow_meta($conn, $contractId, $patch);
    if (!$result['ok']) {
        return $result;
    }
    m360_contract_sig_session_start();
    $_SESSION['m360_contract_sig_draft_' . $contractId] = $signatureImageData;
    m360_intake_contract_record_event($conn, $contractId, 'SIGNATURE_CONFIRMED_AND_LOCKED', 'hash=' . substr($sigHash, 0, 16), null);
    if (!function_exists('m360_vault_store_signature_image')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-document-vault-helper.php';
    }
    if (m360_vault_table_exists($conn)) {
        $sigVault = m360_vault_store_signature_image(
            $conn,
            $contractId,
            (int)($contractRow['online_request_id'] ?? 0),
            (int)($contractRow['customer_id'] ?? 0),
            $signatureImageData,
            null
        );
        if ($sigVault['ok'] && $sigVault['blob_id'] > 0) {
            m360_intake_contract_patch_workflow_meta($conn, $contractId, [
                'signature_vault_blob_id' => (string)$sigVault['blob_id'],
                'signature_vault_sha256' => (string)$sigVault['sha256'],
            ]);
        }
    }

    return ['ok' => true, 'message' => 'امضای شما ثبت و قفل شد.', 'signature_hash' => $sigHash];
}

function m360_contract_sig_session_key(int $contractId): string
{
    return 'm360_contract_otp_' . $contractId;
}

function m360_contract_sig_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        m360_otp_session_start();
    }
}

function m360_contract_clear_pre_signature_session_state(int $contractId): void
{
    if ($contractId < 1) {
        return;
    }
    m360_contract_sig_session_start();
    unset($_SESSION[m360_contract_sig_session_key($contractId)], $_SESSION['m360_contract_sig_draft_' . $contractId]);
}

/**
 * @return array{workflow_state:string,review_done:bool,consent_done:bool,signature_locked:bool}
 */
function m360_contract_workflow_state_summary(array $contractRow): array
{
    $reviewDone = m360_contract_workflow_review_completed($contractRow);
    $consentDone = m360_contract_workflow_consent_accepted($contractRow);
    $signatureLocked = m360_contract_workflow_signature_confirmed($contractRow);
    $state = 'CONTRACT_OPEN';
    if ($signatureLocked) {
        $state = 'SIGNATURE_PERSISTED_AND_LOCKED';
    } elseif ($reviewDone && $consentDone) {
        $state = 'REVIEW_AND_CONSENT_CONFIRMED';
    } elseif ($reviewDone) {
        $state = 'REVIEW_CHECKED';
    }

    return [
        'workflow_state' => $state,
        'review_done' => $reviewDone,
        'consent_done' => $consentDone,
        'signature_locked' => $signatureLocked,
    ];
}

/**
 * @param array{ok:bool,message:string} $result
 * @param array{ok:bool,message:string,test_mode?:bool}|null $otpResult
 * @return array<string, mixed>
 */
function m360_contract_review_ajax_payload(
    array $result,
    ?array $otpResult,
    array $contractRow,
    string $action
): array {
    $summary = m360_contract_workflow_state_summary($contractRow);
    $binding = m360_contract_require_verified_session_mobile_for_contract($contractRow);
    $signatureLocked = !empty($summary['signature_locked']);
    $otpSendStatus = 'not_requested';
    $errorCode = null;
    if ($action === 'confirm_signature' && !empty($result['ok'])) {
        if ($otpResult === null) {
            $otpSendStatus = 'skipped';
        } elseif (!empty($otpResult['ok'])) {
            $otpSendStatus = !empty($otpResult['test_mode']) ? 'mock_sent' : 'sent';
        } else {
            $otpSendStatus = 'failed';
            $errorCode = 'otp_send_failed';
        }
    } elseif (empty($result['ok'])) {
        $errorCode = $action === 'confirm_signature' ? 'signature_confirm_failed' : 'review_confirm_failed';
    }

    return [
        'ok' => !empty($result['ok']),
        'message' => (string)($result['message'] ?? ''),
        'workflow_state' => (string)$summary['workflow_state'],
        'signature_locked' => $signatureLocked,
        'otp_send_status' => $otpSendStatus,
        'masked_destination' => $binding['ok'] ? m360_contract_mask_mobile($binding['mobile']) : '',
        'error_code' => $errorCode,
        'otp' => $otpResult,
        'signature_confirmed' => $action === 'confirm_signature' && $signatureLocked,
    ];
}

/** @return array{ok:bool,message:string,test_mode?:bool} */
function m360_contract_send_otp(array $contractRow): array
{
    m360_contract_sig_session_start();
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    if ($contractId < 1) {
        return ['ok' => false, 'message' => 'اطلاعات قرارداد معتبر نیست.'];
    }
    $binding = m360_contract_require_verified_session_mobile_for_contract($contractRow);
    if (!$binding['ok']) {
        return ['ok' => false, 'message' => $binding['message']];
    }
    $mobile = $binding['mobile'];
    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'این قرارداد قبلاً امضا شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید مطالعه کامل قرارداد ثبت شود.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید پذیرش صریح متن قرارداد ثبت شود.'];
    }
    if (!m360_contract_workflow_signature_confirmed($contractRow)) {
        return ['ok' => false, 'message' => 'ابتدا باید امضا تأیید و قفل شود.'];
    }

    $key = m360_contract_sig_session_key($contractId);
    $last = (int)($_SESSION[$key]['last_sent_at'] ?? 0);
    if ($last > 0 && (time() - $last) < M360_CONTRACT_OTP_RESEND) {
        $wait = M360_CONTRACT_OTP_RESEND - (time() - $last);
        return ['ok' => false, 'message' => 'لطفاً ' . $wait . ' ثانیه دیگر تلاش کنید.'];
    }

    if (m360_otp_sms_configured()) {
        $code = (string)random_int(100000, 999999);
        $sms = m360_otp_send_sms($mobile, $code);
        if (!$sms['ok']) {
            return $sms;
        }
        $_SESSION[$key] = [
            'mobile' => $mobile,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + M360_CONTRACT_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
        ];
        $conn = customer_core_db();
        if ($conn !== false) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, updated_at = SYSUTCDATETIME() WHERE contract_id = ? AND contract_status <> ?',
                [M360_CONTRACT_STATUS_OTP_SENT, $contractId, M360_CONTRACT_STATUS_SIGNED]
            );
            m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_OTP_SENT', M360_CONTRACT_OTP_PURPOSE, null);
        }
        return ['ok' => true, 'message' => 'کد تأیید برای شما ارسال شد.'];
    }

    if (m360_otp_can_use_dev_code()) {
        $code = m360_otp_get_dev_code();
        $_SESSION[$key] = [
            'mobile' => $mobile,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + M360_CONTRACT_OTP_TTL,
            'last_sent_at' => time(),
            'verified' => false,
        ];
        return ['ok' => true, 'message' => m360_otp_dev_fallback_message(), 'test_mode' => true];
    }

    return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
}

function m360_contract_otp_verified(int $contractId, string $mobile): bool
{
    m360_contract_sig_session_start();
    $key = m360_contract_sig_session_key($contractId);
    $bag = $_SESSION[$key] ?? null;
    if (!is_array($bag)) {
        return false;
    }
    if (trim((string)($bag['mobile'] ?? '')) !== $mobile) {
        return false;
    }
    if (empty($bag['verified'])) {
        return false;
    }
    if (!empty($bag['used_at']) && (string)($bag['hash'] ?? '') === '') {
        // Verified once; still valid for the signing session until expires_at.
    }
    if ((int)($bag['expires_at'] ?? 0) < time()) {
        return false;
    }
    return true;
}

/** @return array{ok:bool,message:string} */
function m360_contract_verify_otp(int $contractId, string $mobile, string $otp): array
{
    m360_contract_sig_session_start();
    $key = m360_contract_sig_session_key($contractId);
    $bag = $_SESSION[$key] ?? null;
    if (!is_array($bag)) {
        return ['ok' => false, 'message' => 'ابتدا کد تأیید را درخواست کنید.'];
    }
    if (trim((string)($bag['mobile'] ?? '')) !== $mobile) {
        return ['ok' => false, 'message' => 'شماره موبایل مطابقت ندارد.'];
    }
    if (!empty($bag['used_at'])) {
        return ['ok' => false, 'message' => 'این کد تأیید قبلاً استفاده شده است. کد جدید درخواست کنید.'];
    }
    if ((int)($bag['expires_at'] ?? 0) < time()) {
        unset($_SESSION[$key]);
        return ['ok' => false, 'message' => 'کد تأیید منقضی شده است.'];
    }
    $digits = preg_replace('/\D+/', '', $otp) ?? '';
    if (strlen($digits) !== 6) {
        return ['ok' => false, 'message' => 'کد تأیید باید ۶ رقم باشد.'];
    }
    if (!password_verify($digits, (string)($bag['hash'] ?? ''))) {
        $attempts = (int)($bag['attempts'] ?? 0) + 1;
        $_SESSION[$key]['attempts'] = $attempts;
        if ($attempts >= 5) {
            unset($_SESSION[$key]);
            return ['ok' => false, 'message' => 'تعداد تلاش‌های مجاز تمام شد. لطفاً کد جدید درخواست کنید.'];
        }
        return ['ok' => false, 'message' => 'کد تأیید نادرست است.'];
    }
    $_SESSION[$key]['verified'] = true;
    $_SESSION[$key]['used_at'] = time();
    // Invalidate reusable secret after successful verification.
    $_SESSION[$key]['hash'] = '';
    return ['ok' => true, 'message' => 'کد تأیید شد.'];
}

/** @return array{ok:bool,message:string,test_mode?:bool} */
function m360_contract_send_link_sms(string $mobile, string $url): array
{
    if (!m360_otp_sms_configured()) {
        if (m360_otp_can_use_dev_code()) {
            return ['ok' => true, 'message' => 'پیامک در محیط توسعه ارسال نشد. لینک را دستی ارسال کنید.', 'test_mode' => true];
        }
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
    }
    $message = 'مقاره۳۶۰: لینک قرارداد پذیرش خودرو: ' . $url;
    return m360_contract_send_plain_sms($mobile, $message);
}

/** @return array{ok:bool,message:string} */
function m360_contract_send_plain_sms(string $mobile, string $message): array
{
    if (!m360_otp_sms_configured()) {
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_INACTIVE];
    }
    $s = m360_otp_sms_settings();
    if ($s['provider'] !== 'ippanel') {
        return ['ok' => false, 'message' => M360_OTP_MSG_SMS_FAILED];
    }
    $payload = [
        'sending_type' => 'webservice',
        'from_number' => m360_otp_ippanel_from_number((string)$s['sender']),
        'params' => [
            'recipients' => [m360_otp_ippanel_recipient($mobile)],
            'message' => $message,
        ],
    ];
    return m360_otp_ippanel_send($payload, (string)$s['api_key']);
}

/**
 * @param array<string, mixed> $contractRow
 * @return array{ok:bool,message:string}
 */
function m360_contract_complete_signature(
    array $contractRow,
    string $rawToken,
    string $signatureImageData,
    bool $readConfirmed,
    bool $infoConfirmed,
    bool $otpTermsConfirmed,
    string $otpCode
): array {
    $contractId = (int)($contractRow['contract_id'] ?? 0);
    $binding = m360_contract_require_verified_session_mobile_for_contract($contractRow);
    if (!$binding['ok']) {
        return ['ok' => false, 'message' => $binding['message']];
    }
    $mobile = $binding['mobile'];

    if (m360_intake_contract_is_signed($contractRow)) {
        return ['ok' => false, 'message' => 'این قرارداد قبلاً امضا شده است.'];
    }
    if (!m360_contract_workflow_review_completed($contractRow)) {
        return ['ok' => false, 'message' => 'مطالعه کامل قرارداد ثبت نشده است.'];
    }
    if (!m360_contract_workflow_consent_accepted($contractRow)) {
        return ['ok' => false, 'message' => 'پذیرش صریح متن قرارداد ثبت نشده است.'];
    }
    if (!m360_contract_workflow_signature_confirmed($contractRow)) {
        return ['ok' => false, 'message' => 'امضا باید ابتدا تأیید و قفل شود.'];
    }
    if (!$readConfirmed) {
        return ['ok' => false, 'message' => 'تأیید مطالعه قرارداد الزامی است.'];
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        m360_contract_sig_session_start();
        $sessionDraft = (string)($_SESSION['m360_contract_sig_draft_' . $contractId] ?? '');
        if ($sessionDraft !== '' && strlen($sessionDraft) >= 100) {
            $signatureImageData = $sessionDraft;
        }
    }
    if ($signatureImageData === '' || strlen($signatureImageData) < 100) {
        return ['ok' => false, 'message' => 'امضا روی صفحه الزامی است.'];
    }
    $workflow = m360_intake_contract_get_workflow_meta($contractRow);
    $draftHash = trim((string)($workflow['signature_confirmed_hash'] ?? ($workflow['signature_draft_hash'] ?? '')));
    $bodyHashAtConfirm = trim((string)($workflow['signature_contract_body_hash'] ?? ''));
    $sigHash = m360_intake_contract_hash($signatureImageData);
    if ($draftHash === '' || !hash_equals($draftHash, $sigHash)) {
        return ['ok' => false, 'message' => 'امضای ثبت‌شده با امضای تأییدشده مطابقت ندارد.'];
    }
    if ($bodyHashAtConfirm !== '' && $bodyHashAtConfirm !== (string)($contractRow['contract_body_hash'] ?? '')) {
        return ['ok' => false, 'message' => 'نسخه قرارداد پس از قفل امضا تغییر کرده است.'];
    }

    $otpCheck = m360_contract_verify_otp($contractId, $mobile, $otpCode);
    if (!$otpCheck['ok']) {
        return $otpCheck;
    }

    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.'];
    }

    $snapshot = m360_intake_contract_snapshot_from_row($contractRow);
    $html = m360_contract_render_html($snapshot, true);
    $signedHash = m360_intake_contract_hash($html . $signatureImageData . $mobile . gmdate('c'));
    $sigHash = m360_intake_contract_hash($signatureImageData);
    $ip = customer_core_client_ip();
    $ua = customer_core_user_agent();

    $sigOk = customer_core_execute(
        $conn,
        'INSERT INTO dbo.' . M360_CONTRACT_SIG_TABLE . ' (
            contract_id, mobile, otp_verified, otp_verified_at, signature_image_data,
            signature_hash, signer_ip, signer_user_agent, signed_contract_hash, signed_at
        ) VALUES (?, ?, 1, SYSUTCDATETIME(), ?, ?, ?, ?, ?, SYSUTCDATETIME())',
        [$contractId, $mobile, $signatureImageData, $sigHash, $ip, $ua, $signedHash]
    );
    if ($sigOk === false) {
        return ['ok' => false, 'message' => 'ثبت امضا ناموفق بود.'];
    }

    // New signatures must land in SQL vault before contract is marked SIGNED.
    if (!function_exists('m360_vault_store_signature_image')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-document-vault-helper.php';
    }
    if (m360_vault_table_exists($conn)) {
        $sigVault = m360_vault_store_signature_image(
            $conn,
            $contractId,
            (int)($contractRow['online_request_id'] ?? 0),
            (int)($contractRow['customer_id'] ?? 0),
            $signatureImageData,
            null
        );
        if (empty($sigVault['ok']) || (int)($sigVault['blob_id'] ?? 0) < 1) {
            return ['ok' => false, 'message' => 'ذخیره تصویر امضا در مخزن اسناد ناموفق بود. لطفاً دوباره تلاش کنید.'];
        }
        m360_intake_contract_patch_workflow_meta($conn, $contractId, [
            'signature_vault_blob_id' => (string)$sigVault['blob_id'],
            'signature_vault_sha256' => (string)($sigVault['sha256'] ?? ''),
            'signature_vault_at' => gmdate('c'),
        ]);
    }

    customer_core_execute(
        $conn,
        'UPDATE dbo.' . M360_CONTRACT_TABLE . ' SET contract_status = ?, signed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME(), contract_body_hash = ? WHERE contract_id = ?',
        [M360_CONTRACT_STATUS_SIGNED, $signedHash, $contractId]
    );

    $jobcardId = (int)($contractRow['jobcard_id'] ?? 0);
    if ($jobcardId > 0) {
        if (customer_core_column_exists($conn, 'erp_jobcards', 'contract_status')) {
            customer_core_execute(
                $conn,
                'UPDATE dbo.erp_jobcards SET contract_status = ?, intake_contract_id = ?, contract_signed_at = SYSUTCDATETIME() WHERE jobcard_id = ?',
                ['SIGNED', $contractId, $jobcardId]
            );
        }
        if (customer_core_table_exists($conn, 'erp_jobcard_change_history')) {
            customer_core_execute(
                $conn,
                'INSERT INTO dbo.erp_jobcard_change_history (jobcard_id, change_type, previous_status, new_status, change_summary, changed_by_user_id)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$jobcardId, 'JOBCARD_INTAKE_CONTRACT_SIGNED', null, 'SIGNED', 'P1.5 intake contract signed', ERP_PHASE1_PLATFORM_OWNER_ID]
            );
        }
    }

    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_OTP_VERIFIED', M360_CONTRACT_OTP_PURPOSE, null);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_CONFIRMED', null, null);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_LOCKED', null, null);
    m360_intake_contract_record_event($conn, $contractId, 'CONTRACT_SIGNED', 'signed_hash=' . substr($signedHash, 0, 16), null);

    $freshContract = m360_intake_contract_fetch_by_id($conn, $contractId);
    if (is_array($freshContract)) {
        m360_intake_contract_ensure_pdf($conn, $freshContract, 'customer', null, false);
    }

    if (m360_cartable_tables_available($conn)) {
        $activeTask = m360_cartable_find_active_by_source(
            $conn,
            M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
            M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
            (string)$contractId,
            M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE
        );
        if ($activeTask !== null) {
            m360_cartable_complete_task(
                $conn,
                (int)$activeTask['task_id'],
                'CUSTOMER',
                m360_cartable_normalize_mobile($mobile),
                'CUSTOMER_PORTAL',
                ['contract_id' => $contractId, 'online_request_id' => (int)($contractRow['online_request_id'] ?? 0)]
            );
        }
    }
    if (function_exists('m360_rw_intake_sync_cartable_from_signed_contract')) {
        m360_rw_intake_sync_cartable_from_signed_contract($conn, $contractRow);
    }
    unset($_SESSION[m360_contract_sig_session_key($contractId)]);
    unset($_SESSION['m360_contract_sig_draft_' . $contractId]);

    return ['ok' => true, 'message' => 'قرارداد با موفقیت امضا و تأیید شد.'];
}

/** @return array{ok:bool,message:string,contract:?array} */
function m360_contract_resolve_token(string $rawToken): array
{
    $rawToken = trim($rawToken);
    if ($rawToken === '' || strlen($rawToken) < 32) {
        return ['ok' => false, 'message' => 'لینک قرارداد معتبر نیست.', 'contract' => null];
    }
    $conn = customer_core_db();
    if ($conn === false) {
        return ['ok' => false, 'message' => 'خطا در اتصال به سامانه.', 'contract' => null];
    }
    $row = m360_intake_contract_fetch_by_token_hash($conn, m360_intake_contract_hash($rawToken));
    if ($row === null) {
        return ['ok' => false, 'message' => 'قرارداد یافت نشد.', 'contract' => null];
    }
    $contractId = (int)($row['contract_id'] ?? 0);
    if (m360_intake_contract_is_signed($row)) {
        return ['ok' => true, 'message' => '', 'contract' => $row];
    }
    if ((string)($row['contract_status'] ?? '') === M360_CONTRACT_STATUS_CANCELLED) {
        return ['ok' => false, 'message' => 'این قرارداد لغو شده است.', 'contract' => null];
    }

    // Unsigned contracts with an active cartable signature task must remain signable.
    // Refresh token TTL instead of permanently marking EXPIRED (blocks Owner UAT recapture).
    $hasActiveCartable = false;
    if ($contractId > 0 && function_exists('m360_cartable_tables_available') && m360_cartable_tables_available($conn)) {
        $activeTask = m360_cartable_find_active_by_source(
            $conn,
            M360_CARTABLE_SOURCE_MODULE_INTAKE_CONTRACT,
            M360_CARTABLE_SOURCE_ENTITY_TYPE_INTAKE_CONTRACT,
            (string)$contractId,
            M360_CARTABLE_TASK_TYPE_CONTRACT_SIGNATURE
        );
        $hasActiveCartable = $activeTask !== null;
    }

    if (!m360_intake_contract_token_valid($row)) {
        if ($hasActiveCartable || in_array(strtoupper((string)($row['contract_status'] ?? '')), [
            M360_CONTRACT_STATUS_SENT,
            M360_CONTRACT_STATUS_VIEWED,
            M360_CONTRACT_STATUS_OTP_SENT,
            M360_CONTRACT_STATUS_GENERATED,
            M360_CONTRACT_STATUS_EXPIRED,
        ], true)) {
            $newExpires = gmdate('Y-m-d H:i:s', time() + M360_CONTRACT_TOKEN_TTL_SECONDS);
            $restoreStatus = strtoupper((string)($row['contract_status'] ?? '')) === M360_CONTRACT_STATUS_EXPIRED
                ? M360_CONTRACT_STATUS_VIEWED
                : (string)($row['contract_status'] ?? M360_CONTRACT_STATUS_VIEWED);
            if ($restoreStatus === M360_CONTRACT_STATUS_EXPIRED) {
                $restoreStatus = M360_CONTRACT_STATUS_VIEWED;
            }
            customer_core_execute(
                $conn,
                'UPDATE dbo.' . M360_CONTRACT_TABLE . '
                 SET secure_token_expires_at = ?, contract_status = ?, updated_at = SYSUTCDATETIME()
                 WHERE contract_id = ? AND signed_at IS NULL
                   AND contract_status NOT IN (?, ?)',
                [$newExpires, $restoreStatus, $contractId, M360_CONTRACT_STATUS_SIGNED, M360_CONTRACT_STATUS_OVERRIDDEN]
            );
            $row = m360_intake_contract_fetch_by_id($conn, $contractId) ?? $row;
            $row['secure_token_expires_at'] = $newExpires;
            $row['contract_status'] = $restoreStatus;
            m360_intake_contract_record_event(
                $conn,
                $contractId,
                'CONTRACT_TOKEN_TTL_REFRESHED',
                'unsigned_active_cartable_or_viewed',
                null
            );

            return ['ok' => true, 'message' => '', 'contract' => $row];
        }

        return ['ok' => false, 'message' => 'لینک قرارداد منقضی شده است. لطفاً با پذیرش تماس بگیرید.', 'contract' => null];
    }

    return ['ok' => true, 'message' => '', 'contract' => $row];
}
