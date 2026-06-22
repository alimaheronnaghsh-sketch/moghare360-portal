<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Phase 1 Submit Customer Contract (controlled write)
 */

require_once __DIR__ . '/includes/erp-customer-core-helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    customer_core_render_error_page('خطا', 'فقط درخواست POST مجاز است.');
}

erp_csrf_require_valid('customer_core_contract', $_POST['erp_csrf_token'] ?? null);

$customerIdRaw = customer_core_post_string('customer_id');
$intakeIdRaw = customer_core_post_string('intake_id');
$contractType = customer_core_post_string('contract_type');
$authorizationMode = customer_core_post_string('authorization_mode');
$thresholdRaw = customer_core_post_string('approval_threshold_amount');
$requiresOp = customer_core_post_string('requires_operation_approval') === '0' ? 0 : 1;
$requiresParts = customer_core_post_string('requires_parts_approval') === '0' ? 0 : 1;
$termsSummary = customer_core_post_string('terms_summary');
$initialStatus = customer_core_post_string('initial_status');

$allowedContractTypes = ['PAY_PER_SERVICE', 'OPEN_AUTHORIZATION', 'LIMITED_AUTHORIZATION', 'CORPORATE_FLEET'];
$allowedAuthModes = ['NO_PREAUTH', 'PREAUTH_LIMITED', 'PREAUTH_OPEN', 'APPROVAL_REQUIRED'];
$allowedInitialStatuses = ['DRAFT', 'ACCEPTED'];

$errors = [];

if (!in_array($contractType, $allowedContractTypes, true)) {
    $errors[] = 'نوع قرارداد نامعتبر است.';
}

if (!in_array($authorizationMode, $allowedAuthModes, true)) {
    $errors[] = 'حالت مجوزدهی نامعتبر است.';
}

if (!in_array($initialStatus, $allowedInitialStatuses, true)) {
    $errors[] = 'وضعیت اولیه نامعتبر است.';
}

$customerId = $customerIdRaw !== '' && ctype_digit($customerIdRaw) ? (int)$customerIdRaw : null;
$intakeId = $intakeIdRaw !== '' && ctype_digit($intakeIdRaw) ? (int)$intakeIdRaw : null;
$threshold = null;

if ($thresholdRaw !== '') {
    if (!is_numeric($thresholdRaw)) {
        $errors[] = 'سقف تأیید باید عدد باشد.';
    } else {
        $threshold = (float)$thresholdRaw;
    }
}

if ($errors !== []) {
    customer_core_render_error_page('خطای اعتبارسنجی', implode(' ', $errors));
}

$connection = false;

try {
    $connection = customer_core_db();

    if ($connection === false) {
        throw new RuntimeException('اتصال به پایگاه داده برقرار نشد.');
    }

    customer_core_require_auth_and_guard($connection, 'customer.core.contract.create');

    if (!customer_core_table_exists($connection, 'erp_customer_contracts')) {
        throw new RuntimeException('جدول erp_customer_contracts یافت نشد.');
    }

    $contractCode = customer_core_generate_contract_code();
    $createdBy = customer_core_safe_current_user();
    $contractStatus = $initialStatus === 'ACCEPTED' ? 'ACCEPTED' : 'DRAFT';
    $acceptedBy = $initialStatus === 'ACCEPTED' ? $createdBy : null;

    if (!@odbc_autocommit($connection, false)) {
        throw new RuntimeException('ثبت قرارداد انجام نشد.');
    }

    if ($initialStatus === 'ACCEPTED') {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_customer_contracts (
                customer_id,
                intake_id,
                contract_code,
                contract_type,
                authorization_mode,
                approval_threshold_amount,
                requires_operation_approval,
                requires_parts_approval,
                terms_summary,
                status,
                accepted_at,
                accepted_by,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSUTCDATETIME(), ?, ?)',
            [
                $customerId,
                $intakeId,
                $contractCode,
                $contractType,
                $authorizationMode,
                $threshold,
                $requiresOp,
                $requiresParts,
                $termsSummary !== '' ? $termsSummary : null,
                $contractStatus,
                $acceptedBy,
                $createdBy,
            ]
        );
    } else {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_customer_contracts (
                customer_id,
                intake_id,
                contract_code,
                contract_type,
                authorization_mode,
                approval_threshold_amount,
                requires_operation_approval,
                requires_parts_approval,
                terms_summary,
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $customerId,
                $intakeId,
                $contractCode,
                $contractType,
                $authorizationMode,
                $threshold,
                $requiresOp,
                $requiresParts,
                $termsSummary !== '' ? $termsSummary : null,
                $contractStatus,
                $createdBy,
            ]
        );
    }

    if ($insertOk === false) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت قرارداد انجام نشد.');
    }

    $contractId = customer_core_scope_identity($connection);

    if ($contractId === null) {
        @odbc_rollback($connection);
        throw new RuntimeException('شناسه قرارداد دریافت نشد.');
    }

    if ($initialStatus === 'ACCEPTED') {
        if (!customer_core_table_exists($connection, 'erp_customer_contract_acceptances')) {
            @odbc_rollback($connection);
            throw new RuntimeException('جدول erp_customer_contract_acceptances یافت نشد.');
        }

        $acceptOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_customer_contract_acceptances (
                contract_id,
                acceptance_type,
                accepted_by,
                acceptance_note,
                source_ip,
                user_agent
            ) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $contractId,
                'INTERNAL_CONTROLLED',
                $createdBy,
                'پذیرش داخلی کنترل‌شده هنگام ایجاد قرارداد',
                customer_core_client_ip(),
                customer_core_user_agent(),
            ]
        );

        if ($acceptOk === false) {
            @odbc_rollback($connection);
            throw new RuntimeException('ثبت پذیرش قرارداد انجام نشد.');
        }
    }

    if (!customer_core_insert_history(
        $connection,
        'erp_customer_contracts',
        $contractId,
        'CONTRACT_CREATE',
        'ایجاد قرارداد — کد: ' . $contractCode . ' — وضعیت: ' . $contractStatus,
        null,
        json_encode([
            'contract_code' => $contractCode,
            'contract_type' => $contractType,
            'status' => $contractStatus,
        ], JSON_UNESCAPED_UNICODE)
    )) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت تاریخچه انجام نشد.');
    }

    if (!@odbc_commit($connection)) {
        @odbc_rollback($connection);
        throw new RuntimeException('ثبت قرارداد انجام نشد.');
    }

    @odbc_autocommit($connection, true);
    customer_core_redirect('erp-customer-core-dashboard.php?phase1=contract_ok');
} catch (Throwable) {
    if ($connection !== false) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
    }

    customer_core_render_error_page('خطا در ثبت', 'ثبت قرارداد انجام نشد. لطفاً دوباره تلاش کنید.');
} finally {
    if ($connection !== false) {
        @odbc_close($connection);
    }
}
