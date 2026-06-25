<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Media Metadata DB Binding Helper (Wave 2B)
 *
 * Uses existing local-safe DB connection pattern. No config.php changes.
 * Writes metadata only when a safe existing table + columns are confirmed.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'erp-customer-core-helper.php';

const MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED = 'BLOCKED_SAFE_MEDIA_SCHEMA_NOT_CONFIRMED';
const MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_ACTIVATED = 'DB_METADATA_WRITE_ACTIVATED_FOR_JOBCARD_MEDIA';

const MOGHARE360_JOBCARD_MEDIA_METADATA_JOBCARD_TABLE = 'erp_jobcards';
const MOGHARE360_JOBCARD_MEDIA_METADATA_AUDIT_TABLE = 'erp_jobcard_change_history';

/**
 * SQL Server ERP candidates only — portal/MySQL tables are excluded.
 *
 * @return list<string>
 */
function moghare360_jobcard_media_metadata_candidate_tables(): array
{
    return [
        'erp_jobcard_media',
        'erp_jobcard_files',
        'erp_media_files',
        'erp_jobcard_attachments',
        'erp_jobcard_media_history',
    ];
}

/**
 * @return list<string>
 */
function moghare360_jobcard_media_metadata_path_column_candidates(): array
{
    return ['relative_path', 'file_path', 'storage_path'];
}

/**
 * @return list<string>
 */
function moghare360_jobcard_media_metadata_stage_column_candidates(): array
{
    return ['media_stage', 'attachment_stage', 'capture_stage'];
}

/**
 * @return list<string>
 */
function moghare360_jobcard_media_metadata_type_column_candidates(): array
{
    return ['media_type', 'attachment_type', 'photo_type', 'capture_type'];
}

/**
 * @return list<string>
 */
function moghare360_jobcard_media_metadata_id_column_candidates(): array
{
    return ['media_id', 'jobcard_media_id', 'file_id', 'attachment_id', 'photo_record_id', 'id'];
}

/**
 * @return array{activated: bool, status: string, table: string|null, reason: string, columns: array<string, string>}
 */
function moghare360_jobcard_media_metadata_schema_status($connection): array
{
    if ($connection === false) {
        return [
            'activated' => false,
            'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
            'table' => null,
            'reason' => 'اتصال به پایگاه داده برقرار نشد.',
            'columns' => [],
        ];
    }

    foreach (moghare360_jobcard_media_metadata_candidate_tables() as $tableName) {
        if (!customer_core_table_exists($connection, $tableName)) {
            continue;
        }

        $columns = moghare360_jobcard_media_metadata_resolve_columns($connection, $tableName);

        if ($columns !== null) {
            return [
                'activated' => true,
                'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_ACTIVATED,
                'table' => $tableName,
                'reason' => '',
                'columns' => $columns,
            ];
        }
    }

    return [
        'activated' => false,
        'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
        'table' => null,
        'reason' => 'جدول متادیتای رسانه کارت کار با ستون‌های ایمن یافت نشد.',
        'columns' => [],
    ];
}

/**
 * @return array<string, string>|null
 */
function moghare360_jobcard_media_metadata_resolve_columns($connection, string $tableName): ?array
{
    if (!customer_core_column_exists($connection, $tableName, 'jobcard_id')) {
        return null;
    }

    $pathColumn = moghare360_jobcard_media_metadata_first_existing_column(
        $connection,
        $tableName,
        moghare360_jobcard_media_metadata_path_column_candidates()
    );

    $stageColumn = moghare360_jobcard_media_metadata_first_existing_column(
        $connection,
        $tableName,
        moghare360_jobcard_media_metadata_stage_column_candidates()
    );

    $typeColumn = moghare360_jobcard_media_metadata_first_existing_column(
        $connection,
        $tableName,
        moghare360_jobcard_media_metadata_type_column_candidates()
    );

    if ($pathColumn === null || $stageColumn === null || $typeColumn === null) {
        return null;
    }

    $idColumn = moghare360_jobcard_media_metadata_first_existing_column(
        $connection,
        $tableName,
        moghare360_jobcard_media_metadata_id_column_candidates()
    );

    $resolved = [
        'jobcard_id' => 'jobcard_id',
        'path' => $pathColumn,
        'stage' => $stageColumn,
        'type' => $typeColumn,
    ];

    if ($idColumn !== null) {
        $resolved['id'] = $idColumn;
    }

    foreach (['mime_type', 'file_mime', 'content_type'] as $mimeColumn) {
        if (customer_core_column_exists($connection, $tableName, $mimeColumn)) {
            $resolved['mime'] = $mimeColumn;
            break;
        }
    }

    foreach (['file_size', 'media_size', 'size_bytes'] as $sizeColumn) {
        if (customer_core_column_exists($connection, $tableName, $sizeColumn)) {
            $resolved['size'] = $sizeColumn;
            break;
        }
    }

    foreach (['file_hash', 'checksum', 'content_hash', 'sha256_hash'] as $hashColumn) {
        if (customer_core_column_exists($connection, $tableName, $hashColumn)) {
            $resolved['hash'] = $hashColumn;
            break;
        }
    }

    foreach (['created_at', 'registered_at', 'captured_at'] as $createdColumn) {
        if (customer_core_column_exists($connection, $tableName, $createdColumn)) {
            $resolved['created_at'] = $createdColumn;
            break;
        }
    }

    foreach (['created_by_user_id', 'created_by', 'registered_by_user_id', 'captured_by_user_id'] as $actorColumn) {
        if (customer_core_column_exists($connection, $tableName, $actorColumn)) {
            $resolved['created_by'] = $actorColumn;
            break;
        }
    }

    return $resolved;
}

/**
 * @param list<string> $candidates
 */
function moghare360_jobcard_media_metadata_first_existing_column($connection, string $tableName, array $candidates): ?string
{
    foreach ($candidates as $columnName) {
        if (customer_core_column_exists($connection, $tableName, $columnName)) {
            return $columnName;
        }
    }

    return null;
}

/**
 * @return array{ok: bool, exists: bool, notes: list<string>}
 */
function moghare360_jobcard_media_metadata_validate_jobcard_exists($connection, int $jobcardId): array
{
    $notes = [];

    if ($connection === false) {
        return ['ok' => false, 'exists' => false, 'notes' => ['JobCard reference validation pending — DB connection unavailable.']];
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_MEDIA_METADATA_JOBCARD_TABLE)) {
        return ['ok' => false, 'exists' => false, 'notes' => ['JobCard reference validation pending — erp_jobcards not confirmed.']];
    }

    if (!customer_core_column_exists($connection, MOGHARE360_JOBCARD_MEDIA_METADATA_JOBCARD_TABLE, 'jobcard_id')) {
        return ['ok' => false, 'exists' => false, 'notes' => ['JobCard reference validation pending — jobcard_id column not confirmed.']];
    }

    $count = (int)(customer_core_scalar(
        $connection,
        'SELECT COUNT(*) FROM dbo.erp_jobcards WHERE jobcard_id = ?',
        [$jobcardId]
    ) ?? 0);

    if ($count < 1) {
        return ['ok' => false, 'exists' => false, 'notes' => ['کارت کار با این شناسه در erp_jobcards یافت نشد.']];
    }

    $notes[] = 'JobCard reference validated against erp_jobcards.';

    return ['ok' => true, 'exists' => true, 'notes' => $notes];
}

/**
 * @param array<string, mixed> $record
 * @return array{ok: bool, media_id: int|string|null, message: string, error: string, notes: list<string>}
 */
function moghare360_jobcard_media_metadata_bind(array $record): array
{
    $notes = [];
    $connection = customer_core_db();
    $schema = moghare360_jobcard_media_metadata_schema_status($connection);

    if (!$schema['activated'] || $schema['table'] === null || $schema['columns'] === []) {
        return [
            'ok' => false,
            'media_id' => null,
            'message' => 'ثبت متادیتای DB به‌صورت ایمن مسدود است.',
            'error' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
            'notes' => array_merge($notes, [
                $schema['reason'] !== '' ? $schema['reason'] : 'جدول متادیتای رسانه کارت کار تأیید نشد.',
                'Audit write pending safe audit target confirmation',
            ]),
        ];
    }

    $jobcardId = (int)($record['jobcard_id'] ?? 0);
    $mediaStage = trim((string)($record['media_stage'] ?? ''));
    $mediaType = trim((string)($record['media_type'] ?? ''));
    $relativePath = trim((string)($record['relative_path'] ?? ''));
    $mimeType = trim((string)($record['mime_type'] ?? ''));
    $fileSize = (int)($record['file_size'] ?? 0);
    $filePath = trim((string)($record['file_path'] ?? ''));

    if ($jobcardId < 1 || $mediaStage === '' || $mediaType === '' || $relativePath === '') {
        return [
            'ok' => false,
            'media_id' => null,
            'message' => 'رکورد متادیتای رسانه ناقص است.',
            'error' => 'invalid_metadata_record',
            'notes' => $notes,
        ];
    }

    $jobcardCheck = moghare360_jobcard_media_metadata_validate_jobcard_exists($connection, $jobcardId);
    $notes = array_merge($notes, $jobcardCheck['notes']);

    if (!$jobcardCheck['ok']) {
        return [
            'ok' => false,
            'media_id' => null,
            'message' => 'ثبت متادیتا به‌دلیل عدم تأیید مرجع کارت کار مسدود شد.',
            'error' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
            'notes' => $notes,
        ];
    }

    $tableName = (string)$schema['table'];
    $columns = $schema['columns'];
    $pathValue = $columns['path'] === 'relative_path' ? $relativePath : ($filePath !== '' ? $filePath : $relativePath);

    $insertColumns = ['jobcard_id', $columns['stage'], $columns['type'], $columns['path']];
    $insertValues = [$jobcardId, $mediaStage, $mediaType, $pathValue];

    if (isset($columns['mime']) && $mimeType !== '') {
        $insertColumns[] = $columns['mime'];
        $insertValues[] = $mimeType;
    }

    if (isset($columns['size']) && $fileSize > 0) {
        $insertColumns[] = $columns['size'];
        $insertValues[] = $fileSize;
    }

    if (isset($columns['hash']) && is_file($filePath)) {
        $hash = hash_file('sha256', $filePath);
        if ($hash !== false) {
            $insertColumns[] = $columns['hash'];
            $insertValues[] = $hash;
        }
    }

    if (isset($columns['created_by'])) {
        $insertColumns[] = $columns['created_by'];
        $insertValues[] = defined('ERP_PHASE1_PLATFORM_OWNER_ID') ? ERP_PHASE1_PLATFORM_OWNER_ID : 10001;
    }

    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $columnList = implode(', ', $insertColumns);

    if (!@odbc_autocommit($connection, false)) {
        return [
            'ok' => false,
            'media_id' => null,
            'message' => 'شروع تراکنش متادیتا ناموفق بود.',
            'error' => 'transaction_start_failed',
            'notes' => $notes,
        ];
    }

    try {
        $insertOk = customer_core_execute(
            $connection,
            'INSERT INTO dbo.' . $tableName . ' (' . $columnList . ') VALUES (' . $placeholders . ')',
            $insertValues
        );

        if ($insertOk === false) {
            throw new RuntimeException('درج متادیتای رسانه ناموفق بود.');
        }

        $mediaId = customer_core_scope_identity($connection);

        if (($mediaId === null || (int)$mediaId < 1) && isset($columns['id'])) {
            $mediaId = customer_core_scalar(
                $connection,
                'SELECT TOP 1 ' . $columns['id'] . ' FROM dbo.' . $tableName . ' WHERE jobcard_id = ? ORDER BY ' . $columns['id'] . ' DESC',
                [$jobcardId]
            );
        }

        $auditWritten = moghare360_jobcard_media_metadata_write_audit(
            $connection,
            $jobcardId,
            $mediaStage,
            $mediaType,
            $relativePath,
            $mediaId
        );

        if ($auditWritten) {
            $notes[] = 'erp_jobcard_change_history media_registered audit written';
        } else {
            $notes[] = 'Audit write pending safe audit target confirmation';
        }

        if (!@odbc_commit($connection)) {
            throw new RuntimeException('ثبت نهایی تراکنش متادیتا ناموفق بود.');
        }

        @odbc_autocommit($connection, true);

        return [
            'ok' => true,
            'media_id' => $mediaId,
            'message' => 'متادیتای رسانه کارت کار در DB ثبت شد.',
            'error' => '',
            'notes' => array_merge($notes, [MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_ACTIVATED]),
        ];
    } catch (Throwable $exception) {
        @odbc_rollback($connection);
        @odbc_autocommit($connection, true);

        return [
            'ok' => false,
            'media_id' => null,
            'message' => 'ثبت متادیتای رسانه ناموفق بود.',
            'error' => $exception->getMessage(),
            'notes' => $notes,
        ];
    }
}

/**
 * @param int|string|null $mediaId
 */
function moghare360_jobcard_media_metadata_write_audit(
    $connection,
    int $jobcardId,
    string $mediaStage,
    string $mediaType,
    string $relativePath,
    $mediaId
): bool {
    if ($connection === false) {
        return false;
    }

    if (!customer_core_table_exists($connection, MOGHARE360_JOBCARD_MEDIA_METADATA_AUDIT_TABLE)) {
        return false;
    }

    $required = ['jobcard_id', 'change_type', 'change_summary', 'changed_by_user_id'];
    foreach ($required as $column) {
        if (!customer_core_column_exists($connection, MOGHARE360_JOBCARD_MEDIA_METADATA_AUDIT_TABLE, $column)) {
            return false;
        }
    }

    $summary = 'media_registered | stage:' . $mediaStage
        . ' | type:' . $mediaType
        . ' | path:' . $relativePath;

    if ($mediaId !== null && (string)$mediaId !== '') {
        $summary .= ' | media_id:' . (string)$mediaId;
    }

    $userId = defined('ERP_PHASE1_PLATFORM_OWNER_ID') ? ERP_PHASE1_PLATFORM_OWNER_ID : 10001;

    return customer_core_execute(
        $connection,
        'INSERT INTO dbo.erp_jobcard_change_history (
            jobcard_id,
            change_type,
            previous_status,
            new_status,
            change_summary,
            changed_by_user_id
        ) VALUES (?, ?, ?, ?, ?, ?)',
        [
            $jobcardId,
            'MEDIA_REGISTERED',
            null,
            null,
            $summary,
            $userId,
        ]
    ) !== false;
}

/**
 * @return array{activated: bool, status: string, records: list<array<string, string>>, notes: list<string>}
 */
function moghare360_jobcard_media_metadata_list_for_jobcard(int $jobcardId): array
{
    if ($jobcardId < 1) {
        return [
            'activated' => false,
            'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
            'records' => [],
            'notes' => ['شناسه کارت کار نامعتبر است.'],
        ];
    }

    $connection = customer_core_db();
    $schema = moghare360_jobcard_media_metadata_schema_status($connection);

    if (!$schema['activated'] || $schema['table'] === null || $schema['columns'] === []) {
        return [
            'activated' => false,
            'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
            'records' => [],
            'notes' => [
                'Metadata DB preview pending safe schema confirmation',
                $schema['reason'] !== '' ? $schema['reason'] : 'جدول متادیتای رسانه کارت کار تأیید نشد.',
            ],
        ];
    }

    $tableName = (string)$schema['table'];
    $columns = $schema['columns'];

    $selectParts = [
        $columns['stage'] . ' AS media_stage',
        $columns['type'] . ' AS media_type',
        $columns['path'] . ' AS file_path',
    ];

    if (isset($columns['id'])) {
        array_unshift($selectParts, $columns['id'] . ' AS media_id');
    }

    if (isset($columns['mime'])) {
        $selectParts[] = $columns['mime'] . ' AS mime_type';
    }

    if (isset($columns['size'])) {
        $selectParts[] = $columns['size'] . ' AS file_size';
    }

    if (isset($columns['created_at'])) {
        $selectParts[] = $columns['created_at'] . ' AS created_at';
    }

    $sql = 'SELECT ' . implode(', ', $selectParts)
        . ' FROM dbo.' . $tableName
        . ' WHERE jobcard_id = ?'
        . ' ORDER BY ' . ($columns['id'] ?? $columns['stage']) . ' DESC';

    $rows = customer_core_fetch_rows($connection, $sql, [$jobcardId]);

    return [
        'activated' => true,
        'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_ACTIVATED,
        'records' => $rows,
        'notes' => [],
    ];
}

/**
 * @return array{activated: bool, status: string, table: string|null, reason: string}
 */
function moghare360_jobcard_media_metadata_binding_status(): array
{
    $connection = customer_core_db();
    $schema = moghare360_jobcard_media_metadata_schema_status($connection);

    return [
        'activated' => $schema['activated'],
        'status' => $schema['status'],
        'table' => $schema['table'],
        'reason' => $schema['reason'],
    ];
}
