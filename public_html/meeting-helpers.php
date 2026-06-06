<?php
declare(strict_types=1);

if (!function_exists('isPersianWordsOnly')) {
    function isPersianWordsOnly(string $value): bool
    {
        $value = trim($value);
        return $value !== '' && (bool)preg_match('/^[آابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیءئؤۀة\s]+$/u', $value);
    }
}

if (!function_exists('isValidNationalCode')) {
    function isValidNationalCode(string $value): bool
    {
        return (bool)preg_match('/^[0-9]{10}$/', trim($value));
    }
}

if (!function_exists('initialLetter')) {
    function initialLetter(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'M';
        }
        return function_exists('mb_substr') ? mb_substr($value, 0, 1, 'UTF-8') : substr($value, 0, 1);
    }
}

if (!function_exists('persianMonthNames')) {
    function persianMonthNames(): array
    {
        return ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
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
            file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
        }
        return $absolute;
    }
}

if (!function_exists('handleImageUpload')) {
    function handleImageUpload(string $fieldName, string $directory, ?string $currentPath = null): ?string
    {
        if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]) || (int)$_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
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
        return ['سرویس‌های دوره‌ای', 'آپشن و ارتقا', 'کارشناسی خرید/فروش', 'کارشناسی و عیب‌یابی'];
    }
}

if (!function_exists('meetingCanEditInventory')) {
    function meetingCanEditInventory(array $staff): bool
    {
        $role = (string)($staff['role_name'] ?? '');
        return !empty($staff['is_master_admin']) || str_contains($role, 'مالک') || str_contains($role, 'انبار');
    }
}

if (!function_exists('meetingIsViewOnly')) {
    function meetingIsViewOnly(array $staff): bool
    {
        return str_contains((string)($staff['role_name'] ?? ''), 'فقط مشاهده');
    }
}

if (!function_exists('meetingCanAccessStaffModule')) {
    function meetingCanAccessStaffModule(array $staff, string $module): bool
    {
        $role = (string)($staff['role_name'] ?? '');
        $username = (string)($staff['username'] ?? '');

        if (!empty($staff['is_master_admin']) || str_contains($role, 'مالک')) {
            return true;
        }
        if (meetingIsViewOnly($staff)) {
            return true;
        }
        if (str_contains($role, 'ثبت اطلاعات')) {
            return in_array($module, ['personal', 'reception'], true);
        }
        if (str_contains($role, 'انبار') || $username === 'warehouse_price') {
            return in_array($module, ['personal', 'inventory', 'domestic_purchase'], true);
        }
        return true;
    }
}

if (!function_exists('meetingDecimalOrNull')) {
    function meetingDecimalOrNull(string $value): ?string
    {
        $value = trim(str_replace(',', '', $value));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('فیلدهای عددی باید فقط با عدد انگلیسی وارد شوند.');
        }
        return $value;
    }
}

if (!function_exists('inventoryMainCards')) {
    function inventoryMainCards(): array
    {
        return [
            ['داشبورد انبار', 'staff-inventory.php', 'نمای شاخص‌ها، مسیرهای عملیاتی و وضعیت کلی انبار', 'DB'],
            ['ثبت کالای جدید', 'staff-inventory-new.php', 'ثبت قطعه، کد فنی، موجودی، قیمت و عکس کالا', 'NW'],
            ['جستجوی کالا', 'staff-inventory-search.php', 'جستجوی نام کالا، کد OEM، کد داخلی و برند', 'SR'],
            ['گزارش انبار', 'staff-inventory-reports.php', 'گزارش‌های کم‌موجودی، گردش، تأمین و ارزش ریالی', 'RP'],
            ['ثبت ورود کالا', 'staff-inventory-inbound.php', 'ثبت خرید، رسید انبار و ورود موجودی', 'IN'],
            ['ثبت خروج کالا', 'staff-inventory-outbound.php', 'ثبت مصرف، خروج و اتصال به پذیرش', 'OU'],
            ['انبارگردانی', 'staff-inventory-counting.php', 'شمارش دوره‌ای و کنترل مغایرت موجودی', 'CT'],
            ['ارزش ریالی انبار', 'staff-inventory-valuation.php', 'جمع ریالی موجودی و کنترل قیمت خرید/فروش', 'VL'],
        ];
    }
}

if (!function_exists('sendWelcomeSms')) {
    function sendWelcomeSms(string $mobile, string $fullName): string
    {
        global $useFakeOtp, $ippanelApiKey, $ippanelSender;
        if (!empty($useFakeOtp)) {
            return 'پیام خوش‌آمدگویی در حالت تست ارسال نشد.';
        }
        if (($ippanelApiKey ?? '') === 'CHANGE_ME_IPPANEL_API_KEY' || !function_exists('curl_init')) {
            return 'پیام خوش‌آمدگویی به دلیل تکمیل نبودن تنظیمات پیامک ارسال نشد.';
        }
        $message = "مشتری گرامی {$fullName}، به پرتال مقاره موتورز 360 خوش آمدید.";
        $payload = ['sender' => $ippanelSender ?: '100033605070', 'receptor' => $mobile, 'message' => $message];
        $ch = curl_init('https://api2.ippanel.com/api/v1/sms/send/webservice/single');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'apikey: ' . $ippanelApiKey],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 12,
        ]);
        $result = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($result !== false && $status >= 200 && $status < 300)
            ? 'پیام خوش‌آمدگویی ارسال شد.'
            : 'ذخیره انجام شد، اما پیام خوش‌آمدگویی ارسال نشد.';
    }
}
