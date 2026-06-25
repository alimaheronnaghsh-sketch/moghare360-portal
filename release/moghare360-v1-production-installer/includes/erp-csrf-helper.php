<?php
/**
 * MOGHARE360 ERP CSRF Helper
 *
 * Phase 1A CSRF helper for future write-enabled ERP forms.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/erp-auth-helper.php';

function erp_csrf_start(): void
{
    erp_auth_start_session();

    if (!isset($_SESSION['erp_csrf_tokens']) || !is_array($_SESSION['erp_csrf_tokens'])) {
        $_SESSION['erp_csrf_tokens'] = [];
    }
}

function erp_csrf_normalize_purpose(string $purpose): ?string
{
    $cleanPurpose = trim($purpose);

    if ($cleanPurpose === '') {
        return null;
    }

    return $cleanPurpose;
}

function erp_csrf_generate(string $purpose): string
{
    erp_csrf_start();

    $cleanPurpose = erp_csrf_normalize_purpose($purpose);

    if ($cleanPurpose === null) {
        return '';
    }

    $token = bin2hex(random_bytes(32));

    $_SESSION['erp_csrf_tokens'][$cleanPurpose] = $token;

    return $token;
}

function erp_csrf_input(string $purpose): string
{
    $token = erp_csrf_generate($purpose);

    return '<input type="hidden" name="erp_csrf_token" value="' .
        htmlspecialchars($token, ENT_QUOTES, 'UTF-8') .
        '">';
}

function erp_csrf_validate(string $purpose, ?string $token): bool
{
    erp_csrf_start();

    $cleanPurpose = erp_csrf_normalize_purpose($purpose);

    if ($cleanPurpose === null) {
        return false;
    }

    if ($token === null || trim($token) === '') {
        return false;
    }

    if (!isset($_SESSION['erp_csrf_tokens'][$cleanPurpose])) {
        return false;
    }

    $storedToken = $_SESSION['erp_csrf_tokens'][$cleanPurpose];

    if (!is_string($storedToken) || $storedToken === '') {
        return false;
    }

    return hash_equals($storedToken, $token);
}

function erp_csrf_require_valid(string $purpose, ?string $token): void
{
    if (!erp_csrf_validate($purpose, $token)) {
        erp_csrf_access_denied();
    }
}

function erp_csrf_clear(string $purpose): void
{
    erp_csrf_start();

    $cleanPurpose = erp_csrf_normalize_purpose($purpose);

    if ($cleanPurpose === null) {
        return;
    }

    unset($_SESSION['erp_csrf_tokens'][$cleanPurpose]);
}

function erp_csrf_access_denied(): void
{
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ERP security validation failed.';
    exit;
}
