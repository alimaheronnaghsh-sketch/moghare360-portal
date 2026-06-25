<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Camera-only Media Helper (Wave 2A)
 *
 * Pure PHP · no auth · no config · no SQL · local storage only.
 */

const MOGHARE360_CAMERA_MEDIA_MAX_BYTES = 5_242_880; // 5 MB

/**
 * @return list<string>
 */
function moghare360_camera_media_allowed_stages(): array
{
    return [
        'input',
        'output',
        'diagnostic_initial',
        'diagnostic_secondary',
        'diagnostic_final',
    ];
}

/**
 * @return list<string>
 */
function moghare360_camera_media_allowed_types(): array
{
    return [
        'front',
        'rear',
        'right',
        'left',
        'dashboard',
        'odometer',
        'damage',
        'part',
        'diagnostic',
        'other',
    ];
}

function moghare360_camera_media_storage_root(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'jobcard-media';
}

/**
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_validate_camera_media_payload(array $payload): array
{
    $errors = [];
    $clean = [];

    if (!empty($payload['_files']) || !empty($payload['file_upload'])) {
        $errors[] = [
            'field' => '_upload',
            'rule' => 'upload_bypass_forbidden',
            'message' => 'آپلود فایل مجاز نیست — فقط دوربین مستقیم.',
        ];
    }

    $jobcardIdRaw = trim((string)($payload['jobcard_id'] ?? ''));
    if ($jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1) {
        $errors[] = [
            'field' => 'jobcard_id',
            'rule' => 'positive_number',
            'message' => 'شناسه کارت کار معتبر نیست.',
        ];
    } else {
        $clean['jobcard_id'] = (int)$jobcardIdRaw;
    }

    $stage = strtolower(trim((string)($payload['media_stage'] ?? '')));
    if (!in_array($stage, moghare360_camera_media_allowed_stages(), true)) {
        $errors[] = [
            'field' => 'media_stage',
            'rule' => 'allowed_value',
            'message' => 'مرحله رسانه نامعتبر است.',
        ];
    } else {
        $clean['media_stage'] = $stage;
    }

    $type = strtolower(trim((string)($payload['media_type'] ?? '')));
    if (!in_array($type, moghare360_camera_media_allowed_types(), true)) {
        $errors[] = [
            'field' => 'media_type',
            'rule' => 'allowed_value',
            'message' => 'نوع رسانه نامعتبر است.',
        ];
    } else {
        $clean['media_type'] = $type;
    }

    $cameraData = trim((string)($payload['camera_data'] ?? ''));

    if ($cameraData === '') {
        $errors[] = [
            'field' => 'camera_data',
            'rule' => 'required',
            'message' => 'داده دوربین خالی است.',
        ];
    } elseif (preg_match('#^https?://#i', $cameraData) === 1) {
        $errors[] = [
            'field' => 'camera_data',
            'rule' => 'external_url_forbidden',
            'message' => 'آدرس خارجی برای رسانه مجاز نیست.',
        ];
    } else {
        $decoded = moghare360_camera_media_decode_base64_image($cameraData);

        if ($decoded === null) {
            $errors[] = [
                'field' => 'camera_data',
                'rule' => 'invalid_image',
                'message' => 'فرمت تصویر دوربین معتبر نیست.',
            ];
        } elseif (strlen($decoded['binary']) > MOGHARE360_CAMERA_MEDIA_MAX_BYTES) {
            $errors[] = [
                'field' => 'camera_data',
                'rule' => 'max_size',
                'message' => 'حجم تصویر بیش از حد مجاز است.',
            ];
        } else {
            $clean['mime'] = $decoded['mime'];
            $clean['extension'] = $decoded['extension'];
            $clean['binary'] = $decoded['binary'];
        }
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'clean' => $clean,
    ];
}

/**
 * @return array{mime: string, extension: string, binary: string}|null
 */
function moghare360_camera_media_decode_base64_image(string $cameraData): ?array
{
    $mime = 'image/jpeg';
    $payload = $cameraData;

    if (preg_match('#^data:(image/(?:jpeg|jpg|png|webp));base64,(.+)$#i', $cameraData, $matches) === 1) {
        $mime = strtolower($matches[1]);
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }
        $payload = $matches[2];
    }

    if (preg_match('#^https?://#i', $payload) === 1) {
        return null;
    }

    $payload = preg_replace('/\s+/', '', $payload) ?? $payload;
    $binary = base64_decode($payload, true);

    if ($binary === false || $binary === '') {
        return null;
    }

    if (!moghare360_camera_media_is_image_binary($binary)) {
        return null;
    }

    $extension = match ($mime) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };

    return [
        'mime' => $mime,
        'extension' => $extension,
        'binary' => $binary,
    ];
}

function moghare360_camera_media_is_image_binary(string $binary): bool
{
    if (str_starts_with($binary, "\xFF\xD8\xFF")) {
        return true;
    }

    if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
        return true;
    }

    if (str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
        return true;
    }

    return false;
}

function moghare360_camera_media_build_filename(int $jobcardId, string $stage, string $type, string $extension = 'jpg'): string
{
    $safeStage = preg_replace('/[^a-z0-9_]+/', '_', strtolower($stage)) ?? 'stage';
    $safeType = preg_replace('/[^a-z0-9_]+/', '_', strtolower($type)) ?? 'type';
    $timestamp = date('Ymd-His');

    return 'jc' . $jobcardId . '_' . $safeStage . '_' . $safeType . '_' . $timestamp . '.' . $extension;
}

/**
 * @param array<string, mixed> $payload Raw POST payload
 * @return array{ok: bool, file_path: string|null, relative_path: string|null, message: string, errors: list<array{field: string, rule: string, message: string}>}
 */
function moghare360_camera_media_save_base64(array $payload): array
{
    $validation = moghare360_validate_camera_media_payload($payload);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'file_path' => null,
            'relative_path' => null,
            'message' => 'اعتبارسنجی رسانه ناموفق بود.',
            'errors' => $validation['errors'],
        ];
    }

    $clean = $validation['clean'];
    $jobcardId = (int)$clean['jobcard_id'];
    $stage = (string)$clean['media_stage'];
    $type = (string)$clean['media_type'];
    $extension = (string)$clean['extension'];
    $binary = (string)$clean['binary'];

    $root = moghare360_camera_media_storage_root();
    $jobcardDir = $root . DIRECTORY_SEPARATOR . (string)$jobcardId;

    if (!is_dir($jobcardDir) && !mkdir($jobcardDir, 0755, true) && !is_dir($jobcardDir)) {
        return [
            'ok' => false,
            'file_path' => null,
            'relative_path' => null,
            'message' => 'ایجاد پوشه ذخیره‌سازی محلی ناموفق بود.',
            'errors' => [[
                'field' => '_storage',
                'rule' => 'mkdir_failed',
                'message' => 'ایجاد پوشه ذخیره‌سازی محلی ناموفق بود.',
            ]],
        ];
    }

    $filename = moghare360_camera_media_build_filename($jobcardId, $stage, $type, $extension);
    $fullPath = $jobcardDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($fullPath, $binary) === false) {
        return [
            'ok' => false,
            'file_path' => null,
            'relative_path' => null,
            'message' => 'ذخیره فایل محلی ناموفق بود.',
            'errors' => [[
                'field' => '_storage',
                'rule' => 'write_failed',
                'message' => 'ذخیره فایل محلی ناموفق بود.',
            ]],
        ];
    }

    $relativePath = 'storage/jobcard-media/' . $jobcardId . '/' . $filename;

    return [
        'ok' => true,
        'file_path' => $fullPath,
        'relative_path' => $relativePath,
        'message' => 'تصویر دوربین با موفقیت در ذخیره‌سازی محلی ثبت شد.',
        'errors' => [],
    ];
}

/**
 * @return list<string> Relative paths under storage/jobcard-media/{jobcardId}/
 */
function moghare360_camera_media_list_jobcard_files(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [];
    }

    $root = realpath(moghare360_camera_media_storage_root());
    if ($root === false) {
        return [];
    }

    $targetDir = $root . DIRECTORY_SEPARATOR . (string)$jobcardId;
    $resolvedTarget = realpath($targetDir);

    if ($resolvedTarget === false || !is_dir($resolvedTarget)) {
        return [];
    }

    if (!str_starts_with($resolvedTarget, $root)) {
        return [];
    }

    $files = [];
    $entries = scandir($resolvedTarget);

    if ($entries === false) {
        return [];
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $full = $resolvedTarget . DIRECTORY_SEPARATOR . $entry;

        if (!is_file($full)) {
            continue;
        }

        if (!moghare360_camera_media_is_image_binary((string)file_get_contents($full))) {
            continue;
        }

        $files[] = 'storage/jobcard-media/' . $jobcardId . '/' . $entry;
    }

    sort($files);

    return $files;
}

/**
 * @return list<array{field: string, rule: string, message: string}>
 */
function moghare360_camera_media_error_summary_messages(array $errors): array
{
    $messages = [];

    foreach ($errors as $error) {
        $message = trim((string)($error['message'] ?? ''));
        if ($message !== '') {
            $messages[] = $message;
        }
    }

    return $messages;
}
