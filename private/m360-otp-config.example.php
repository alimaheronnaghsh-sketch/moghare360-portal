<?php
declare(strict_types=1);

/**
 * MOGHARE360 OTP provider config — EXAMPLE ONLY (placeholders, safe to commit).
 * Copy to private/m360-otp-config.php (gitignored) and set real values on host.
 *
 * Pattern text in IPPanel panel may use %OTP% — API params key must be OTP (not %OTP%).
 */

return [
    'useFakeOtp' => false,
    'M360_OTP_TEST_MODE' => false,
    'M360_OTP_TEST_CODE' => '',
    'otpExpireMinutes' => 5,

    'otpLine' => '100033605070',
    'receptionLine' => '100044121',
    'partsApprovalLine' => '10003388900',
    'surveyLine' => '10004000757',

    'M360_SMS_PROVIDER' => 'ippanel',
    'M360_SMS_API_KEY' => 'YOUR_REAL_IPPANEL_API_KEY',
    'M360_SMS_SENDER' => 'YOUR_APPROVED_SENDER_NUMBER',
    'M360_SMS_PATTERN_CODE' => 'YOUR_REAL_PATTERN_CODE',
    'M360_SMS_PATTERN_VARIABLE' => 'OTP',

    // Backward-compatible aliases:
    'IPPANEL_API_KEY' => 'YOUR_REAL_IPPANEL_API_KEY',
    'IPPANEL_SENDER' => 'YOUR_APPROVED_SENDER_NUMBER',
    'IPPANEL_PATTERN_CODE' => 'YOUR_REAL_PATTERN_CODE',
    'IPPANEL_OTP_VARIABLE' => 'OTP',
    'ippanelApiKey' => 'YOUR_REAL_IPPANEL_API_KEY',
    'ippanelSender' => 'YOUR_APPROVED_SENDER_NUMBER',
    'ippanelPatternCode' => 'YOUR_REAL_PATTERN_CODE',
    'ippanelOtpVariableName' => 'OTP',

    'ALLOW_DEBUG_OUTPUT' => false,
    'SHOW_TECHNICAL_ERRORS_TO_USER' => false,
    'LOG_API_ERRORS' => true,
];
