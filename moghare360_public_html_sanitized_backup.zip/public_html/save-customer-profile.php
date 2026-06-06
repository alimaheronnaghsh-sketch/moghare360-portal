<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensureSessionStarted();

function profileRedirectMode(string $mode): string
{
    return in_array($mode, ['complete', 'edit', 'dashboard'], true) ? $mode : 'complete';
}

function buildProfileBackUrl(string $mode): string
{
    return 'customer-profile.php?mode=' . urlencode(profileRedirectMode($mode));
}

function ensureUploadDirectory(string $relativePath): string
{
    $relativePath = trim($relativePath, '/');
    $absolute = __DIR__ . '/' . $relativePath;
    if (!is_dir($absolute) && !mkdir($absolute, 0755, true) && !is_dir($absolute)) {
        throw new RuntimeException('امکان ساخت پوشه آپلود وجود ندارد.');
    }
    $htaccess = $absolute . '/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
    }
    return $absolute;
}

function handleProfilePhotoUpload(string $fieldName, ?string $currentPath = null): ?string
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
        throw new RuntimeException('آپلود تصویر پروفایل انجام نشد.');
    }
    if ((int)$file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('حجم تصویر پروفایل باید حداکثر ۲ مگابایت باشد.');
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('فرمت تصویر پروفایل باید jpg، jpeg، png یا webp باشد.');
    }

    $info = @getimagesize((string)$file['tmp_name']);
    if (!$info || !in_array((string)$info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('فایل انتخاب‌شده تصویر معتبر نیست.');
    }

    $uploadDir = ensureUploadDirectory('uploads/customer-profiles');
    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        throw new RuntimeException('ذخیره تصویر پروفایل انجام نشد.');
    }
    return 'uploads/customer-profiles/' . $filename;
}

function generateCustomerTrackingCode(): string
{
    return 'MOGHAREH-CUS-' . date('Ymd') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('customer-profile.php');
    }
    checkCsrf();

    $mobile = requireCustomerLogin();
    $returnMode = profileRedirectMode((string)($_POST['return_mode'] ?? 'complete'));
    $backUrl = buildProfileBackUrl($returnMode);

    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $nationalCode = trim((string)($_POST['national_code'] ?? ''));
    $postalAddress = trim((string)($_POST['postal_address'] ?? ''));
    $jobTitle = trim((string)($_POST['job_title'] ?? ''));
    $birthYear = trim((string)($_POST['birth_year'] ?? ''));
    $birthMonth = trim((string)($_POST['birth_month'] ?? ''));
    $birthDay = trim((string)($_POST['birth_day'] ?? ''));

    $old = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'national_code' => $nationalCode,
        'postal_address' => $postalAddress,
        'job_title' => $jobTitle,
    ];
    $errors = [];

    if (!isPersianLettersAndSpace($firstName)) {
        $errors['first_name'] = 'نام باید فقط شامل حروف فارسی باشد.';
    }
    if (!isPersianLettersAndSpace($lastName)) {
        $errors['last_name'] = 'نام خانوادگی باید فقط شامل حروف فارسی باشد.';
    }
    if (preg_match('/^[0-9]{10}$/', $nationalCode) !== 1) {
        $errors['national_code'] = 'کد ملی باید دقیقاً ۱۰ رقم باشد.';
    }
    if ($postalAddress === '') {
        $errors['postal_address'] = 'آدرس پستی الزامی است.';
    }
    if ($jobTitle !== '' && !isPersianLettersAndSpace($jobTitle)) {
        $errors['job_title'] = 'عنوان شغل باید فقط با حروف فارسی وارد شود.';
    }
    if ($jobTitle === '') {
        $errors['job_title'] = 'عنوان شغل الزامی است.';
    }

    if (
        preg_match('/^[0-9]{4}$/', $birthYear) !== 1
        || preg_match('/^[0-9]{1,2}$/', $birthMonth) !== 1
        || preg_match('/^[0-9]{1,2}$/', $birthDay) !== 1
    ) {
        $errors['birth_date_jalali'] = 'تاریخ تولد را کامل و صحیح انتخاب کنید.';
    } else {
        $monthInt = (int)$birthMonth;
        $dayInt = (int)$birthDay;
        if ($monthInt < 1 || $monthInt > 12 || $dayInt < 1 || $dayInt > 31) {
            $errors['birth_date_jalali'] = 'روز یا ماه تولد معتبر نیست.';
        }
    }

    if ($errors) {
        $_SESSION['customer_profile_old'] = $old;
        $_SESSION['customer_profile_errors'] = $errors;
        redirect($backUrl);
    }

    $birthDateJalali = sprintf('%04d/%02d/%02d', (int)$birthYear, (int)$birthMonth, (int)$birthDay);
    $fullName = trim($firstName . ' ' . $lastName);

    $pdo = getPdo();
    $columns = getTableColumns('portal_customers_staging');
    if (!$columns) {
        throw new RuntimeException('جدول portal_customers_staging یافت نشد.');
    }

    $existing = getCustomerByMobile($mobile);
    $existingPhotoPath = trim((string)($existing['profile_photo_path'] ?? ''));
    $profilePhotoPath = handleProfilePhotoUpload('profile_photo', $existingPhotoPath !== '' ? $existingPhotoPath : null);

    $payload = [];
    if (in_array('first_name', $columns, true)) {
        $payload['first_name'] = $firstName;
    }
    if (in_array('last_name', $columns, true)) {
        $payload['last_name'] = $lastName;
    }
    if (in_array('full_name', $columns, true)) {
        $payload['full_name'] = $fullName;
    }
    if (in_array('national_code', $columns, true)) {
        $payload['national_code'] = $nationalCode;
    }
    if (in_array('postal_address', $columns, true)) {
        $payload['postal_address'] = $postalAddress;
    }
    if (in_array('job_title', $columns, true)) {
        $payload['job_title'] = $jobTitle;
    }
    if (in_array('birth_date_jalali', $columns, true)) {
        $payload['birth_date_jalali'] = $birthDateJalali;
    }
    if (in_array('profile_photo_path', $columns, true)) {
        $payload['profile_photo_path'] = $profilePhotoPath;
    }

    $isFirstCompletion = !is_array($existing) || trim((string)($existing['profile_completed_at'] ?? '')) === '';

    if (is_array($existing)) {
        $setParts = [];
        $params = [];
        foreach ($payload as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $params[] = $value;
        }
        if (in_array('sync_status', $columns, true)) {
            $setParts[] = "`sync_status` = 'Pending'";
        }
        if (in_array('sync_error', $columns, true)) {
            $setParts[] = '`sync_error` = NULL';
        }
        if (in_array('updated_at', $columns, true)) {
            $setParts[] = '`updated_at` = NOW()';
        }
        if (in_array('profile_completed_at', $columns, true)) {
            $setParts[] = '`profile_completed_at` = COALESCE(`profile_completed_at`, NOW())';
        }
        if (in_array('customer_tracking_code', $columns, true) && trim((string)($existing['customer_tracking_code'] ?? '')) === '') {
            $setParts[] = '`customer_tracking_code` = ?';
            $params[] = generateCustomerTrackingCode();
        }

        $params[] = $mobile;
        $sql = 'UPDATE portal_customers_staging SET ' . implode(', ', $setParts) . ' WHERE mobile = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() < 0) {
            throw new RuntimeException('به‌روزرسانی اطلاعات مشتری انجام نشد.');
        }
    } else {
        $insertCols = ['mobile'];
        $insertMarks = ['?'];
        $params = [$mobile];
        foreach ($payload as $column => $value) {
            $insertCols[] = $column;
            $insertMarks[] = '?';
            $params[] = $value;
        }
        if (in_array('profile_completed_at', $columns, true)) {
            $insertCols[] = 'profile_completed_at';
            $insertMarks[] = 'NOW()';
        }
        if (in_array('sync_status', $columns, true)) {
            $insertCols[] = 'sync_status';
            $insertMarks[] = "'Pending'";
        }
        if (in_array('sync_error', $columns, true)) {
            $insertCols[] = 'sync_error';
            $insertMarks[] = 'NULL';
        }
        if (in_array('created_at', $columns, true)) {
            $insertCols[] = 'created_at';
            $insertMarks[] = 'NOW()';
        }
        if (in_array('updated_at', $columns, true)) {
            $insertCols[] = 'updated_at';
            $insertMarks[] = 'NOW()';
        }
        if (in_array('customer_tracking_code', $columns, true)) {
            $insertCols[] = 'customer_tracking_code';
            $insertMarks[] = '?';
            $params[] = generateCustomerTrackingCode();
        }

        $sql = 'INSERT INTO portal_customers_staging (`' . implode('`,`', $insertCols) . '`) VALUES (' . implode(',', $insertMarks) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ((int)$pdo->lastInsertId() <= 0) {
            throw new RuntimeException('ثبت اطلاعات مشتری انجام نشد.');
        }
    }

    flash('اطلاعات مشتری با موفقیت ذخیره شد.');
    if ($isFirstCompletion) {
        redirect('customer-service-request.php');
    }
    redirect('customer-profile.php?mode=dashboard');
} catch (Throwable $e) {
    showErrorPage('خطا در ذخیره پروفایل مشتری.', $e->getMessage());
}
