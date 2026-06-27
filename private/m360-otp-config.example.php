<?php
declare(strict_types=1);

/**
 * MOGHARE360 OTP provider config — EXAMPLE ONLY (placeholders, safe to commit).
 * Copy to private/m360-otp-config.php (gitignored) and set real values on host.
 *
 * Provider pattern text uses %OTP% — pass variable name OTP in API payload (not %OTP%).
 */

return [
    'useFakeOtp' => false,
    'otpExpireMinutes' => 5,

    'otpLine' => '100033605070',
    'receptionLine' => '100044121',
    'partsApprovalLine' => '10003388900',
    'surveyLine' => '10004000757',

    'M360_SMS_PROVIDER' => 'ippanel',
    'ippanelApiKey' => 'YOUR_REAL_IPPANEL_API_KEY',
    'ippanelSender' => 'YOUR_APPROVED_SENDER_NUMBER',
    'ippanelPatternCode' => 'YOUR_REAL_PATTERN_CODE',
    'ippanelOtpVariableName' => 'OTP',
];
