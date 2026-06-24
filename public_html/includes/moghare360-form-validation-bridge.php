<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Form Validation Bridge (Wave 1B)
 *
 * Reusable helpers for submit pages and Critical Forms v2.
 * No database, auth, or config dependency in core validators.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-validation-engine.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'moghare360-critical-form-v2-rules.php';

/**
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_validate_form_payload(string $formKey, array $payload): array
{
    if (!in_array($formKey, moghare360_critical_form_keys(), true)) {
        return [
            'ok' => false,
            'errors' => [
                [
                    'field' => '_form',
                    'rule' => 'unknown_form_key',
                    'message' => 'کلید فرم نامعتبر است.',
                ],
            ],
            'clean' => [],
        ];
    }

    $rules = moghare360_critical_form_rules($formKey);

    if ($rules === []) {
        return [
            'ok' => false,
            'errors' => [
                [
                    'field' => '_form',
                    'rule' => 'missing_rules',
                    'message' => 'قوانین اعتبارسنجی برای این فرم تعریف نشده است.',
                ],
            ],
            'clean' => [],
        ];
    }

    return moghare360_validation_ok($payload, $rules);
}

/**
 * @param array{ok?: bool, errors?: list<array{field: string, rule: string, message: string}>} $result
 */
function moghare360_validation_has_failed(array $result): bool
{
    return empty($result['ok']);
}

/**
 * @param array{ok?: bool, errors?: list<array{field: string, rule: string, message: string}>} $result
 */
function moghare360_validation_error_summary(array $result): string
{
    if (!moghare360_validation_has_failed($result)) {
        return '';
    }

    $messages = [];

    foreach ($result['errors'] ?? [] as $error) {
        $message = trim((string)($error['message'] ?? ''));

        if ($message === '') {
            continue;
        }

        $messages[] = $message;
    }

    if ($messages === []) {
        return 'اعتبارسنجی ناموفق بود.';
    }

    return implode(' ', $messages);
}

/**
 * @param array{ok?: bool, errors?: list<array{field: string, rule: string, message: string}>} $result
 */
function moghare360_validation_errors_as_html(array $result): string
{
    if (!moghare360_validation_has_failed($result)) {
        return '';
    }

    $items = '';

    foreach ($result['errors'] ?? [] as $error) {
        $field = htmlspecialchars((string)($error['field'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $message = htmlspecialchars((string)($error['message'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($message === '') {
            continue;
        }

        $items .= '<li><strong>' . $field . ':</strong> ' . $message . '</li>';
    }

    if ($items === '') {
        return '<p>اعتبارسنجی ناموفق بود.</p>';
    }

    return '<ul class="moghare360-validation-errors" dir="rtl">' . $items . '</ul>';
}

/**
 * @param array{ok?: bool, errors?: list<array{field: string, rule: string, message: string}>, clean?: array<string, mixed>} $result
 * @param array<string, mixed> $oldInput
 */
function moghare360_validation_redirect_with_errors(string $returnUrl, array $result, array $oldInput = []): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['moghare360_validation_errors'] = $result['errors'] ?? [];
    $_SESSION['moghare360_validation_error_summary'] = moghare360_validation_error_summary($result);
    $_SESSION['moghare360_validation_old_input'] = $oldInput;

    header('Location: ' . $returnUrl);
    exit;
}

/**
 * @return list<array{field: string, rule: string, message: string}>
 */
function moghare360_validation_pull_session_errors(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }

    $errors = $_SESSION['moghare360_validation_errors'] ?? [];
    unset($_SESSION['moghare360_validation_errors'], $_SESSION['moghare360_validation_error_summary']);

    return is_array($errors) ? $errors : [];
}

/**
 * @return array<string, mixed>
 */
function moghare360_validation_pull_session_old_input(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }

    $oldInput = $_SESSION['moghare360_validation_old_input'] ?? [];
    unset($_SESSION['moghare360_validation_old_input']);

    return is_array($oldInput) ? $oldInput : [];
}
