<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP CSRF Helper
 *
 * Phase 2 controlled prototype helper.
 *
 * This file provides isolated session-based CSRF token creation and validation.
 * It does not replace login.
 * It does not connect to database.
 * It does not modify users, roles, permissions, tenants, workflow state, or database schema.
 * It does not perform database writes.
 */

if (!function_exists('erp_csrf_start_session_if_needed')) {
    function erp_csrf_start_session_if_needed(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('erp_csrf_create_token')) {
    function erp_csrf_create_token(string $form_key): string
    {
        erp_csrf_start_session_if_needed();

        $form_key = trim($form_key);

        if ($form_key === '') {
            throw new RuntimeException('CSRF form key is required.');
        }

        if (!isset($_SESSION['erp_csrf_tokens']) || !is_array($_SESSION['erp_csrf_tokens'])) {
            $_SESSION['erp_csrf_tokens'] = [];
        }

        $token = bin2hex(random_bytes(32));

        $_SESSION['erp_csrf_tokens'][$form_key] = $token;

        return $token;
    }
}

if (!function_exists('erp_csrf_validate_token')) {
    function erp_csrf_validate_token(string $form_key, string $token): bool
    {
        erp_csrf_start_session_if_needed();

        $form_key = trim($form_key);
        $token = trim($token);

        if ($form_key === '' || $token === '') {
            return false;
        }

        if (
            !isset($_SESSION['erp_csrf_tokens'])
            || !is_array($_SESSION['erp_csrf_tokens'])
            || !isset($_SESSION['erp_csrf_tokens'][$form_key])
            || !is_string($_SESSION['erp_csrf_tokens'][$form_key])
        ) {
            return false;
        }

        return hash_equals($_SESSION['erp_csrf_tokens'][$form_key], $token);
    }
}

if (!function_exists('erp_csrf_require_valid_token')) {
    function erp_csrf_require_valid_token(string $form_key, string $token): void
    {
        $form_key = trim($form_key);
        $token = trim($token);

        if ($form_key === '') {
            throw new RuntimeException('CSRF form key is required.');
        }

        if ($token === '') {
            throw new RuntimeException('CSRF token is required.');
        }

        if (!erp_csrf_validate_token($form_key, $token)) {
            throw new RuntimeException('Invalid CSRF token.');
        }
    }
}
