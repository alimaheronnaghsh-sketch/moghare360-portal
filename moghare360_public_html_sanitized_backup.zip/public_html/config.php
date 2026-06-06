<?php
declare(strict_types=1);

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $length = strlen($needle);
        return substr($haystack, -$length) === $needle;
    }
}
$debug = false;

$dbHost = 'localhost';
$dbName = 'moghareh_portal';
$dbUser = 'moghareh_Amir';
$dbPass = 'Amir@1985/1364';

$adminPassword = 'Maher@1985/1364';
$syncApiToken = 'MOGHARE360_SYNC_2026_7fA9xQ_ChangeMe';


$useFakeOtp = false;
$otpExpireMinutes = 5;
$otpLine = '+983000505';
$receptionLine = '+983000505';
$partsApprovalLine = '+983000505';
$surveyLine = '1+983000505';

$ippanelApiKey = 'YTFkOGZiYzgtZTM0MC00MjAxLTkxODUtNjkxZjhkMmRiYTY2MTEzNjFmMTBhYTRlMGNjZmQ5OThiOTM4YzMzMWNmZTA=';
$ippanelSender = '+983000505';
$ippanelPatternCode = 'sf1pcf8nqvxt2p3'; // کد واقعی Pattern از پنل IPPanel (مثال)
$ippanelOtpVariableName = 'OTP';

if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function getPdo(): PDO
{
    global $dbHost, $dbName, $dbUser, $dbPass;
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function currentIp(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function normalizeMobile(string $mobile): string
{
    $mobile = trim($mobile);
    $mobile = str_replace([' ', '-', '(', ')'], '', $mobile);
    if (str_starts_with($mobile, '+98')) {
        $mobile = '0' . substr($mobile, 3);
    } elseif (str_starts_with($mobile, '98') && strlen($mobile) === 12) {
        $mobile = '0' . substr($mobile, 2);
    }
    return $mobile;
}

function isValidMobile(string $mobile): bool
{
    return (bool)preg_match('/^09[0-9]{9}$/', normalizeMobile($mobile));
}

function isPersianLettersAndSpace(string $value): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }
    return (bool)preg_match('/^[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{200C}\s]+$/u', $value);
}

function normalizeDateOrNull(?string $date): ?string
{
    $date = trim((string)$date);
    if ($date === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return ($dt && $dt->format('Y-m-d') === $date) ? $date : null;
}

function getTableColumns(string $table): array
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return [];
    }
    $stmt = getPdo()->query("SHOW COLUMNS FROM `{$table}`");
    return array_map(static fn(array $row): string => (string)$row['Field'], $stmt->fetchAll());
}

function showErrorPage($message, $debugMessage = null): void
{
    global $debug;
    http_response_code(500);
    $safeMessage = e((string)$message);
    $safeDebug = $debug && $debugMessage ? '<pre class="debug-box">' . e((string)$debugMessage) . '</pre>' : '';
    echo "<!doctype html><html lang=\"fa\" dir=\"rtl\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>خطای سیستم</title><link rel=\"stylesheet\" href=\"assets/style.css\"></head><body><main class=\"auth-wrap\"><section class=\"card\"><h1>خطای موقت سیستم</h1><p>{$safeMessage}</p><p class=\"muted\">لطفا چند دقیقه بعد دوباره تلاش کنید یا با مدیر سیستم تماس بگیرید.</p>{$safeDebug}<a class=\"btn primary\" href=\"index.php\">بازگشت به صفحه اصلی</a></section></main></body></html>";
    exit;
}

function csrfToken(): string
{
    ensureSessionStarted();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function checkCsrf(): void
{
    ensureSessionStarted();
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        showErrorPage('اعتبار فرم منقضی شده است. لطفا صفحه را دوباره باز کنید.');
    }
}

function setVerifiedCustomerMobile($mobile): void
{
    ensureSessionStarted();
    $_SESSION['customer_mobile'] = normalizeMobile((string)$mobile);
}

function getVerifiedCustomerMobile(): ?string
{
    ensureSessionStarted();
    $mobile = (string)($_SESSION['customer_mobile'] ?? '');
    return isValidMobile($mobile) ? normalizeMobile($mobile) : null;
}

function requireCustomerLogin(): string
{
    $mobile = getVerifiedCustomerMobile();
    if (!$mobile) {
        redirect('customer-login.php');
    }
    return $mobile;
}

function customerLogout(): void
{
    ensureSessionStarted();
    unset($_SESSION['customer_mobile']);
}

function verifyStaffLogin(): bool
{
    ensureSessionStarted();
    return !empty($_SESSION['staff_user']) && is_array($_SESSION['staff_user']);
}

function requireStaffLogin(): array
{
    if (!verifyStaffLogin()) {
        redirect('staff-login.php');
    }
    return currentStaffUser();
}

function currentStaffUser(): array
{
    ensureSessionStarted();
    return is_array($_SESSION['staff_user'] ?? null) ? $_SESSION['staff_user'] : [];
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function isMasterAdmin(array $staff = null): bool
{
    $staff = $staff ?? currentStaffUser();
    return !empty($staff['is_master_admin']) || (($staff['role_name'] ?? '') === 'مدیر سیستم');
}

function renderHeader(string $title, string $subtitle = '', bool $showNav = true): void
{
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . ' | MOGHARE360</title><link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<header class="site-header"><a class="brand" href="index.php"><span class="logo-mark">M</span><span><strong>MOGHARE360</strong><small>Mahin Paradigm</small></span></a>';
    if ($showNav) {
        echo '<nav><a href="customer-login.php">مشتریان</a><a href="staff-login.php">پرسنل</a><a href="admin-login.php">ادمین</a></nav>';
    }
    echo '</header>';
    if ($subtitle !== '') {
        echo '<section class="page-hero"><p class="eyebrow">' . e($subtitle) . '</p><h1>' . e($title) . '</h1></section>';
    }
}

function renderFooter(): void
{
    echo '<footer class="site-footer">پرتال مقاره موتورز 360 درجه | طراحی، اجرا و توسعه توسط شرکت فنی مهندسی ماهین پارادایم</footer><script src="assets/app.js"></script></body></html>';
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function flash(string $message, string $type = 'ok'): void
{
    ensureSessionStarted();
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function renderFlashes(): void
{
    ensureSessionStarted();
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    foreach ($items as $item) {
        echo '<div class="notice ' . e((string)$item['type']) . '">' . e((string)$item['message']) . '</div>';
    }
}

function getCustomerByMobile(string $mobile): ?array
{
    $stmt = getPdo()->prepare('SELECT * FROM portal_customers_staging WHERE mobile = ? LIMIT 1');
    $stmt->execute([normalizeMobile($mobile)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getServiceRequestsByMobile(string $mobile): array
{
    $stmt = getPdo()->prepare('SELECT * FROM portal_service_requests_staging WHERE mobile = ? ORDER BY id DESC');
    $stmt->execute([normalizeMobile($mobile)]);
    return $stmt->fetchAll();
}

function fallbackVehicleLookups(): array
{
    $rows = [];
    $data = [
        'BMW' => ['F10', 'F25', 'F30', 'E60', 'G12', 'G30'],
        'Mercedes-Benz' => ['C Class', 'E Class', 'S Class', 'GLC', 'GLE'],
        'Porsche' => ['Cayenne', 'Macan', 'Panamera'],
        'Volkswagen' => ['Tiguan', 'Touareg'],
        'Volvo' => ['XC90', 'XC60'],
        'Audi' => ['A4', 'A6', 'Q5', 'Q7'],
        'Other' => ['سایر'],
    ];
    foreach ($data as $brand => $models) {
        foreach ($models as $model) {
            $rows[] = ['brand' => $brand, 'model' => $model, 'vehicle_type' => 'سایر'];
        }
    }
    return $rows;
}

function getVehicleLookups(): array
{
    try {
        $stmt = getPdo()->query('SELECT brand, model, vehicle_type FROM vehicle_lookups WHERE is_active = 1 ORDER BY brand, model');
        $rows = $stmt->fetchAll();
        return $rows ?: fallbackVehicleLookups();
    } catch (Throwable $e) {
        return fallbackVehicleLookups();
    }
}

function vehicleTypeOptions(): array
{
    return ['سواری', 'شاسی بلند', 'کوپه', 'سدان', 'هاچ‌بک', 'وانت', 'سایر'];
}

function initialLetter(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'M';
    }
    return function_exists('mb_substr') ? mb_substr($value, 0, 1, 'UTF-8') : substr($value, 0, 1);
}

function validPurpose(string $purpose): bool
{
    return in_array($purpose, ['customer_login'], true);
}

function ippanelDebugLog(string $event, array $context = []): void
{
    $line = [
        'time' => date('c'),
        'event' => $event,
        'context' => $context,
    ];
    $logPath = __DIR__ . '/ippanel-debug.log';
    @file_put_contents(
        $logPath,
        json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function toIppanelRecipient(string $mobile): string
{
    $mobile = normalizeMobile($mobile);
    if (str_starts_with($mobile, '09') && strlen($mobile) === 11) {
        return '+98' . substr($mobile, 1);
    }
    return $mobile;
}

function sendIppanelOtp(string $mobile, string $otp): array
{
    global $ippanelApiKey, $ippanelSender, $ippanelPatternCode, $ippanelOtpVariableName;

    $result = [
        'ok' => false,
        'http_status' => 0,
        'response_body' => null,
        'decoded_response' => null,
        'error' => null,
    ];

    if ($ippanelApiKey === 'CHANGE_ME_IPPANEL_API_KEY' || $ippanelPatternCode === 'CHANGE_ME_PATTERN_CODE') {
        $result['error'] = 'IPPANEL_NOT_CONFIGURED';
        ippanelDebugLog('otp_sms_skip', [
            'reason' => $result['error'],
            'mobile' => $mobile,
        ]);
        return $result;
    }

    if (!function_exists('curl_init')) {
        $result['error'] = 'CURL_NOT_AVAILABLE';
        ippanelDebugLog('otp_sms_error', [
            'reason' => $result['error'],
            'mobile' => $mobile,
        ]);
        return $result;
    }

    $recipient = toIppanelRecipient($mobile);
    $payload = [
        'sending_type' => 'pattern',
        'from_number' => $ippanelSender ?: '100033605070',
        'code' => $ippanelPatternCode,
        'recipients' => [$recipient],
        'params' => [$ippanelOtpVariableName => $otp],
    ];

    $ch = curl_init('https://edge.ippanel.com/v1/api/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . $ippanelApiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
    ]);

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result['http_status'] = $status;
    $result['response_body'] = $responseBody === false ? null : (string)$responseBody;
    $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
    $result['decoded_response'] = is_array($decoded) ? $decoded : null;

    if ($responseBody === false) {
        $result['error'] = $curlError ?: 'CURL_EXEC_FAILED';
        ippanelDebugLog('otp_sms_error', [
            'reason' => $result['error'],
            'mobile' => $mobile,
            'recipient' => $recipient,
        ]);
        return $result;
    }

    $result['ok'] = ($status >= 200 && $status < 300);
    if (!$result['ok']) {
        $result['error'] = 'HTTP_' . $status;
    }

    ippanelDebugLog('otp_sms_result', [
        'ok' => $result['ok'],
        'mobile' => $mobile,
        'recipient' => $recipient,
        'http_status' => $status,
        'response' => $result['decoded_response'] ?? $result['response_body'],
    ]);

    return $result;
}
if (!function_exists('isPersianWordsOnly')) {
    function isPersianWordsOnly(string $value): bool
    {
        return isPersianLettersAndSpace($value);
    }
}

if (!function_exists('isValidNationalCode')) {
    function isValidNationalCode(string $value): bool
    {
        return (bool)preg_match('/^[0-9]{10}$/', trim($value));
    }
}

if (!function_exists('vehicleBrandOptions')) {
    function vehicleBrandOptions(): array
    {
        return [
            'Mercedes-Benz' => 'Mercedes-Benz / بنز',
            'BMW' => 'BMW / ب ام و',
            'Porsche' => 'Porsche / پورشه',
            'Audi' => 'Audi',
            'Volkswagen' => 'Volkswagen',
            'Volvo' => 'Volvo',
            'Other' => 'سایر',
        ];
    }
}

if (!function_exists('vehicleModelCatalog')) {
    function vehicleModelCatalog(): array
    {
        return [
            'Mercedes-Benz' => ['E200', 'E250', 'E300', 'C200', 'C300', 'C350', 'S500', 'GLC', 'GLE', 'سایر'],
            'BMW' => ['F10', 'F25', 'F30', 'E60', 'G12', 'G30', 'X3', 'X5', 'سایر'],
            'Porsche' => ['Cayenne', 'Macan', 'Panamera', '911', 'سایر'],
            'Audi' => ['A4', 'A6', 'Q5', 'Q7', 'سایر'],
            'Volkswagen' => ['Tiguan', 'Touareg', 'Passat', 'Golf', 'سایر'],
            'Volvo' => ['XC90', 'XC60', 'S60', 'S90', 'سایر'],
            'Other' => ['سایر'],
        ];
    }
}

if (!function_exists('serviceTypeOptions')) {
    function serviceTypeOptions(): array
    {
        return [
            'سرویس‌های دوره‌ای',
            'آپشن و ارتقا',
            'کارشناسی خرید/فروش',
            'کارشناسی و عیب‌یابی',
        ];
    }
}

if (!function_exists('persianMonthNames')) {
    function persianMonthNames(): array
    {
        return [
            'فروردین',
            'اردیبهشت',
            'خرداد',
            'تیر',
            'مرداد',
            'شهریور',
            'مهر',
            'آبان',
            'آذر',
            'دی',
            'بهمن',
            'اسفند',
        ];
    }
}

if (!function_exists('currentJalaliYear')) {
    function currentJalaliYear(): int
    {
        return (int)date('Y') - 621;
    }
}

if (!function_exists('makeBirthDateJalali')) {
    function makeBirthDateJalali(string $day, string $month, string $year): string
    {
        $dayNumber = (int)$day;
        $yearNumber = (int)$year;

        if ($dayNumber < 1 || $dayNumber > 31 || $yearNumber < 1300 || $yearNumber > currentJalaliYear()) {
            return '';
        }

        if (!in_array($month, persianMonthNames(), true)) {
            return '';
        }

        return sprintf('%04d/%02d/%s', $yearNumber, $dayNumber, $month);
    }
}

if (!function_exists('generateCustomerTrackingCode')) {
    function generateCustomerTrackingCode(): string
    {
        return 'MOGHAREH-CUS-' . date('Ymd') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('ensureUploadDirectory')) {
    function ensureUploadDirectory(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/');
        $absolute = __DIR__ . '/' . $relativePath;

        if (!is_dir($absolute)) {
            mkdir($absolute, 0755, true);
        }

        $htaccess = $absolute . '/.htaccess';

        if (!is_file($htaccess)) {
            file_put_contents(
                $htaccess,
                "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n"
            );
        }

        return $absolute;
    }
}

if (!function_exists('handleImageUpload')) {
    function handleImageUpload(string $fieldName, string $directory, ?string $currentPath = null): ?string
    {
        if (
            empty($_FILES[$fieldName]) ||
            !is_array($_FILES[$fieldName]) ||
            (int)$_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return $currentPath;
        }

        $file = $_FILES[$fieldName];

        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('آپلود عکس انجام نشد.');
        }

        if ((int)$file['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('حجم عکس باید حداکثر ۲ مگابایت باشد.');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new RuntimeException('فرمت عکس باید jpg، jpeg، png یا webp باشد.');
        }

        $info = @getimagesize((string)$file['tmp_name']);

        if (!$info || !in_array((string)$info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('فایل انتخاب‌شده عکس معتبر نیست.');
        }

        $absoluteDirectory = ensureUploadDirectory($directory);
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $absoluteDirectory . '/' . $filename;

        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            throw new RuntimeException('ذخیره عکس انجام نشد.');
        }

        return trim($directory, '/') . '/' . $filename;
    }
}

if (!function_exists('sendWelcomeSms')) {
    function sendWelcomeSms(string $mobile, string $fullName): string
    {
        global $useFakeOtp, $ippanelApiKey, $ippanelSender;

        if ($useFakeOtp) {
            return 'پیام خوش‌آمدگویی در حالت تست ارسال نشد.';
        }

        if (
            $ippanelApiKey === '' ||
            $ippanelApiKey === 'CHANGE_ME_IPPANEL_API_KEY' ||
            !function_exists('curl_init')
        ) {
            return 'پیام خوش‌آمدگویی به دلیل تکمیل نبودن تنظیمات پیامک ارسال نشد.';
        }

        $message = "مشتری گرامی {$fullName}، به پرتال مقاره موتورز 360 خوش آمدید.";

        $payload = [
            'sending_type' => 'webservice',
            'from_number' => $ippanelSender ?: '100033605070',
            'params' => [
                'recipients' => [
                    toIppanelRecipient($mobile),
                ],
                'message' => $message,
            ],
        ];

        $ch = curl_init('https://edge.ippanel.com/v1/api/send');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $ippanelApiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $decoded = is_string($response) ? json_decode($response, true) : null;

        return $status >= 200
            && $status < 300
            && (($decoded['meta']['status'] ?? false) === true)
            ? 'پیام خوش‌آمدگویی ارسال شد.'
            : 'ذخیره انجام شد، اما پیام خوش‌آمدگویی ارسال نشد.';
    }
}