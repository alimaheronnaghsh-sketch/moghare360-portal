<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Diagnostic File Binding Helper (Wave 2C)
 *
 * Controlled diagnostic PDF/image binding · separate from camera photo capture.
 * Uses dbo.erp_jobcard_media + dbo.erp_jobcard_media_history when available.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_DIAGNOSTIC_MAX_BYTES = 10_485_760; // 10 MB

const MOGHARE360_DIAGNOSTIC_MEDIA_TABLE = 'erp_jobcard_media';
const MOGHARE360_DIAGNOSTIC_HISTORY_TABLE = 'erp_jobcard_media_history';

const MOGHARE360_DIAGNOSTIC_SOURCE_NOTE =
    'Diagnostic binding uses media metadata table source constraint currently locked to CAMERA_ONLY; semantic diagnostic type is represented by media_stage and media_type.';

/**
 * @return list<string>
 */
function moghare360_diagnostic_allowed_stages(): array
{
    return [
        'diagnostic_initial',
        'diagnostic_secondary',
        'diagnostic_final',
    ];
}

/**
 * @return list<string>
 */
function moghare360_diagnostic_allowed_types(): array
{
    return [
        'scanner_report',
        'fault_code_report',
        'live_data_report',
        'calibration_report',
        'test_drive_report',
        'other',
    ];
}

/**
 * @return array<string, string>
 */
function moghare360_diagnostic_allowed_mime_map(): array
{
    return [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
}

/**
 * @return list<string>
 */
function moghare360_diagnostic_blocked_extensions(): array
{
    return [
        'exe', 'bat', 'cmd', 'com', 'msi', 'scr', 'ps1', 'vbs', 'js', 'jar',
        'zip', 'rar', '7z', 'tar', 'gz', 'docm', 'xlsm', 'pptm', 'php', 'phtml',
        'sh', 'dll', 'apk',
    ];
}

function moghare360_diagnostic_storage_root(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'jobcard-diagnostic';
}

/**
 * @return array{ok: bool, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_validate_diagnostic_file_payload(array $payload, array $file): array
{
    $errors = [];
    $clean = [];

    $externalUrl = trim((string)($payload['external_url'] ?? $payload['file_url'] ?? ''));
    if ($externalUrl !== '') {
        $errors[] = [
            'field' => 'external_url',
            'rule' => 'external_url_forbidden',
            'message' => 'آدرس خارجی برای فایل تشخیصی مجاز نیست.',
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

    $stage = strtolower(trim((string)($payload['diagnostic_stage'] ?? '')));
    if (!in_array($stage, moghare360_diagnostic_allowed_stages(), true)) {
        $errors[] = [
            'field' => 'diagnostic_stage',
            'rule' => 'allowed_value',
            'message' => 'مرحله تشخیصی نامعتبر است.',
        ];
    } else {
        $clean['diagnostic_stage'] = $stage;
    }

    $type = strtolower(trim((string)($payload['diagnostic_type'] ?? '')));
    if (!in_array($type, moghare360_diagnostic_allowed_types(), true)) {
        $errors[] = [
            'field' => 'diagnostic_type',
            'rule' => 'allowed_value',
            'message' => 'نوع گزارش تشخیصی نامعتبر است.',
        ];
    } else {
        $clean['diagnostic_type'] = $type;
    }

    $fileError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($fileError === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'required',
            'message' => 'فایل تشخیصی انتخاب نشده است.',
        ];

        return ['ok' => false, 'errors' => $errors, 'clean' => $clean];
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'upload_error',
            'message' => 'بارگذاری فایل تشخیصی ناموفق بود.',
        ];

        return ['ok' => false, 'errors' => $errors, 'clean' => $clean];
    }

    $originalName = trim((string)($file['name'] ?? ''));
    $tmpPath = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($originalName === '' || $tmpPath === '' || !is_uploaded_file($tmpPath)) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'invalid_upload',
            'message' => 'فایل بارگذاری‌شده معتبر نیست.',
        ];

        return ['ok' => false, 'errors' => $errors, 'clean' => $clean];
    }

    if (preg_match('#^https?://#i', $originalName) === 1) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'external_url_forbidden',
            'message' => 'آدرس خارجی برای فایل تشخیصی مجاز نیست.',
        ];
    }

    if (str_contains($originalName, '..') || str_contains($originalName, '/') || str_contains($originalName, '\\')) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'path_traversal_forbidden',
            'message' => 'نام فایل نامعتبر است.',
        ];
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !array_key_exists($extension, moghare360_diagnostic_allowed_mime_map())) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'extension_not_allowed',
            'message' => 'پسوند فایل تشخیصی مجاز نیست.',
        ];
    }

    if (in_array($extension, moghare360_diagnostic_blocked_extensions(), true)) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'unsafe_extension',
            'message' => 'نوع فایل ناامن مجاز نیست.',
        ];
    }

    if ($size < 1 || $size > MOGHARE360_DIAGNOSTIC_MAX_BYTES) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'file_size',
            'message' => 'حجم فایل تشخیصی خارج از محدوده مجاز است.',
        ];
    }

    $detectedMime = moghare360_diagnostic_detect_mime($tmpPath, $extension);
    $expectedMime = moghare360_diagnostic_allowed_mime_map()[$extension] ?? '';

    if ($detectedMime === null || $expectedMime === '' || $detectedMime !== $expectedMime) {
        $errors[] = [
            'field' => 'diagnostic_file',
            'rule' => 'mime_mismatch',
            'message' => 'نوع MIME فایل تشخیصی معتبر نیست.',
        ];
    }

    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors, 'clean' => $clean];
    }

    $clean['original_file_name'] = basename($originalName);
    $clean['extension'] = $extension;
    $clean['mime_type'] = $detectedMime;
    $clean['file_size'] = $size;
    $clean['tmp_path'] = $tmpPath;

    return ['ok' => true, 'errors' => [], 'clean' => $clean];
}

function moghare360_diagnostic_detect_mime(string $path, string $extension): ?string
{
    $mime = null;

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $path);
            finfo_close($finfo);
            if (is_string($detected) && $detected !== '') {
                $mime = strtolower($detected);
            }
        }
    }

    if ($mime === null && function_exists('mime_content_type')) {
        $detected = mime_content_type($path);
        if (is_string($detected) && $detected !== '') {
            $mime = strtolower($detected);
        }
    }

    if ($mime === 'image/jpg') {
        $mime = 'image/jpeg';
    }

    $allowed = moghare360_diagnostic_allowed_mime_map()[$extension] ?? null;

    if ($allowed === null) {
        return null;
    }

    if ($mime === null) {
        return $allowed;
    }

    if ($mime === $allowed) {
        return $mime;
    }

    if ($extension === 'pdf' && str_contains($mime, 'pdf')) {
        return 'application/pdf';
    }

    return null;
}

function moghare360_diagnostic_build_filename(int $jobcardId, string $stage, string $type, string $extension): string
{
    $safeStage = preg_replace('/[^a-z0-9_]+/', '_', strtolower($stage)) ?? 'stage';
    $safeType = preg_replace('/[^a-z0-9_]+/', '_', strtolower($type)) ?? 'type';
    $timestamp = date('Ymd-His');

    return 'jd' . $jobcardId . '_' . $safeStage . '_' . $safeType . '_' . $timestamp . '.' . $extension;
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $file
 * @return array{ok: bool, file_path: string|null, relative_path: string|null, message: string, errors: list<array{field: string, rule: string, message: string}>, clean: array<string, mixed>}
 */
function moghare360_diagnostic_save_file(array $payload, array $file): array
{
    $validation = moghare360_validate_diagnostic_file_payload($payload, $file);

    if (!$validation['ok']) {
        return [
            'ok' => false,
            'file_path' => null,
            'relative_path' => null,
            'message' => 'اعتبارسنجی فایل تشخیصی ناموفق بود.',
            'errors' => $validation['errors'],
            'clean' => $validation['clean'],
        ];
    }

    $clean = $validation['clean'];
    $jobcardId = (int)$clean['jobcard_id'];
    $stage = (string)$clean['diagnostic_stage'];
    $type = (string)$clean['diagnostic_type'];
    $extension = (string)$clean['extension'];
    $tmpPath = (string)$clean['tmp_path'];

    $root = moghare360_diagnostic_storage_root();
    $jobcardDir = $root . DIRECTORY_SEPARATOR . (string)$jobcardId;

    if (!is_dir($jobcardDir) && !mkdir($jobcardDir, 0755, true) && !is_dir($jobcardDir)) {
        return [
            'ok' => false,
            'file_path' => null,
            'relative_path' => null,
            'message' => 'ایجاد پوشه ذخیره‌سازی تشخیصی ناموفق بود.',
            'errors' => [[
                'field' => '_storage',
                'rule' => 'mkdir_failed',
                'message' => 'ایجاد پوشه ذخیره‌سازی تشخیصی ناموفق بود.',
            ]],
            'clean' => $clean,
        ];
    }

    $filename = moghare360_diagnostic_build_filename($jobcardId, $stage, $type, $extension);
    $fullPath = $jobcardDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $fullPath)) {
        return [
            'ok' => false,
            'file_path' => null,
            'relative_path' => null,
            'message' => 'ذخیره فایل تشخیصی محلی ناموفق بود.',
            'errors' => [[
                'field' => '_storage',
                'rule' => 'write_failed',
                'message' => 'ذخیره فایل تشخیصی محلی ناموفق بود.',
            ]],
            'clean' => $clean,
        ];
    }

    $relativePath = 'storage/jobcard-diagnostic/' . $jobcardId . '/' . $filename;

    return [
        'ok' => true,
        'file_path' => $fullPath,
        'relative_path' => $relativePath,
        'message' => 'فایل تشخیصی با موفقیت در ذخیره‌سازی محلی ثبت شد.',
        'errors' => [],
        'clean' => array_merge($clean, [
            'relative_path' => $relativePath,
            'file_path' => $fullPath,
        ]),
    ];
}

/**
 * @param array<string, mixed> $savedFileResult
 * @return array{ok: bool, media_id: int|string|null, history_id: int|string|null, message: string, error: string, notes: list<string>}
 */
function moghare360_diagnostic_register_metadata(array $savedFileResult): array
{
    $notes = [MOGHARE360_DIAGNOSTIC_SOURCE_NOTE];

    if (($savedFileResult['ok'] ?? false) !== true) {
        return [
            'ok' => false,
            'media_id' => null,
            'history_id' => null,
            'message' => 'ثبت متادیتای تشخیصی بدون ذخیره فایل مجاز نیست.',
            'error' => 'file_save_required',
            'notes' => $notes,
        ];
    }

    $clean = (array)($savedFileResult['clean'] ?? []);
    $jobcardId = (int)($clean['jobcard_id'] ?? 0);
    $stage = (string)($clean['diagnostic_stage'] ?? '');
    $diagnosticType = (string)($clean['diagnostic_type'] ?? '');
    $relativePath = (string)($savedFileResult['relative_path'] ?? '');
    $filePath = (string)($savedFileResult['file_path'] ?? '');
    $mimeType = (string)($clean['mime_type'] ?? '');
    $fileSize = (int)($clean['file_size'] ?? 0);
    $originalName = (string)($clean['original_file_name'] ?? '');

    $connection = customer_core_db();

    if ($connection === false) {
        return [
            'ok' => false,
            'media_id' => null,
            'history_id' => null,
            'message' => 'اتصال DB برای ثبت متادیتای تشخیصی برقرار نشد.',
            'error' => 'db_connection_failed',
            'notes' => $notes,
        ];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_DIAGNOSTIC_MEDIA_TABLE)) {
        return [
            'ok' => false,
            'media_id' => null,
            'history_id' => null,
            'message' => 'جدول erp_jobcard_media یافت نشد.',
            'error' => 'media_table_missing',
            'notes' => $notes,
        ];
    }

    $jobcardCount = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    ) ?? 0);

    if ($jobcardCount < 1) {
        return [
            'ok' => false,
            'media_id' => null,
            'history_id' => null,
            'message' => 'کارت کار در erp_jobcards یافت نشد.',
            'error' => 'jobcard_not_found',
            'notes' => $notes,
        ];
    }

    $checksum = is_file($filePath) ? hash_file('sha256', $filePath) : false;
    $notesText = 'diagnostic_type:' . $diagnosticType;

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'media_id' => null,
            'history_id' => null,
            'message' => 'شروع تراکنش متادیتای تشخیصی ناموفق بود.',
            'error' => 'transaction_start_failed',
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.erp_jobcard_media (
                jobcard_id,
                media_stage,
                media_type,
                relative_path,
                file_path,
                original_file_name,
                mime_type,
                file_size,
                checksum_sha256,
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $jobcardId,
                $stage,
                'diagnostic',
                $relativePath,
                $filePath,
                $originalName !== '' ? $originalName : null,
                $mimeType,
                $fileSize,
                $checksum !== false ? $checksum : null,
                $notesText,
            ]
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج متادیتای تشخیصی در erp_jobcard_media ناموفق بود.');
        }

        $mediaId = customer_core_scope_identity($connection);

        if ($mediaId === null || (int)$mediaId < 1) {
            $mediaId = customer_core_scalar(
                $connection,
                'SELECT TOP 1 media_id FROM dbo.erp_jobcard_media WHERE jobcard_id = ? ORDER BY media_id DESC',
                [$jobcardId]
            );
        }

        $historyId = null;

        if (customer_core_table_exists($connection, MOGHARE360_DIAGNOSTIC_HISTORY_TABLE)) {
            $eventNotes = 'stage:' . $stage
                . ' | diagnostic_type:' . $diagnosticType
                . ' | relative_path:' . $relativePath;

            $historyOk = customer_core_execute(
                $connection,
                'INSERT INTO dbo.erp_jobcard_media_history (
                    media_id,
                    jobcard_id,
                    event_code,
                    event_title,
                    event_notes
                ) VALUES (?, ?, ?, ?, ?)',
                [
                    $mediaId,
                    $jobcardId,
                    'DIAGNOSTIC_FILE_REGISTERED',
                    'Diagnostic file registered',
                    $eventNotes,
                ]
            );

            if ($historyOk === false) {
                throw new RuntimeException('درج تاریخچه تشخیصی در erp_jobcard_media_history ناموفق بود.');
            }

            $historyId = customer_core_scope_identity($connection);
            $notes[] = 'erp_jobcard_media_history written';
        } else {
            $notes[] = 'erp_jobcard_media_history table not available';
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش متادیتای تشخیصی ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'media_id' => $mediaId,
            'history_id' => $historyId,
            'message' => 'متادیتای فایل تشخیصی در DB ثبت شد.',
            'error' => '',
            'notes' => $notes,
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'media_id' => null,
            'history_id' => null,
            'message' => 'ثبت متادیتای فایل تشخیصی ناموفق بود.',
            'error' => $exception->getMessage(),
            'notes' => $notes,
        ];
    }
}

/**
 * @return list<string>
 */
function moghare360_diagnostic_list_local_files(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [];
    }

    $root = realpath(moghare360_diagnostic_storage_root());
    if ($root === false) {
        return [];
    }

    $targetDir = $root . DIRECTORY_SEPARATOR . (string)$jobcardId;
    $resolvedTarget = realpath($targetDir);

    if ($resolvedTarget === false || !is_dir($resolvedTarget) || !str_starts_with($resolvedTarget, $root)) {
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

        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (!array_key_exists($extension, moghare360_diagnostic_allowed_mime_map())) {
            continue;
        }

        $files[] = 'storage/jobcard-diagnostic/' . $jobcardId . '/' . $entry;
    }

    sort($files);

    return $files;
}

/**
 * @return array{ok: bool, records: list<array<string, string>>, notes: list<string>}
 */
function moghare360_diagnostic_list_metadata(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return ['ok' => false, 'records' => [], 'notes' => ['شناسه کارت کار نامعتبر است.']];
    }

    $connection = customer_core_db();
    if ($connection === false || !customer_core_table_exists($connection, MOGHARE360_DIAGNOSTIC_MEDIA_TABLE)) {
        return ['ok' => false, 'records' => [], 'notes' => ['جدول erp_jobcard_media در دسترس نیست.']];
    }

    $stages = moghare360_diagnostic_allowed_stages();
    $placeholders = implode(', ', array_fill(0, count($stages), '?'));
    $params = array_merge([$jobcardId], $stages);

    $rows = customer_core_fetch_rows(
        $connection,
        'SELECT media_id, media_stage, media_type, relative_path, mime_type, file_size, created_at, notes
         FROM dbo.erp_jobcard_media
         WHERE jobcard_id = ?
           AND media_type = ?
           AND media_stage IN (' . $placeholders . ')
         ORDER BY media_id DESC',
        array_merge([$jobcardId, 'diagnostic'], $stages)
    );

    return ['ok' => true, 'records' => $rows, 'notes' => []];
}

/**
 * @return list<array{field: string, rule: string, message: string}>
 */
function moghare360_diagnostic_error_messages(array $errors): array
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
