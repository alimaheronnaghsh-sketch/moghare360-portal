<?php
declare(strict_types=1);

/**
 * Access package recommendation engine — advisory only.
 * Never participates in effective-permission resolution.
 * Never writes core_user_roles / core_user_permission_overrides.
 */

require_once __DIR__ . '/m360-access-package-catalog.php';
require_once __DIR__ . '/m360-access-matrix-helper.php';

function m360_rec_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return array<string, array{enforcement_state:string,is_assignable:int,title_fa:string,permission_id:int}> */
function m360_rec_permission_index($conn): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $rows = m360_am_fetch_all(
        $conn,
        'SELECT permission_id, permission_key, permission_label, enforcement_state, is_assignable
         FROM dbo.core_permissions WHERE is_active=1'
    );
    $cache = [];
    foreach ($rows as $r) {
        $k = (string)$r['permission_key'];
        $cache[$k] = [
            'permission_id' => (int)$r['permission_id'],
            'title_fa' => (string)($r['permission_label'] ?? $k),
            'enforcement_state' => strtoupper((string)($r['enforcement_state'] ?? 'NOT_YET_ENFORCED')),
            'is_assignable' => (int)($r['is_assignable'] ?? 0),
        ];
    }
    return $cache;
}

/**
 * Segregation-of-duties conflict rules.
 *
 * @param list<string> $packageKeys
 * @return list<array{code:string,title_fa:string,detail_fa:string,safeguard_fa:string,owner_exception_required:bool}>
 */
function m360_rec_sod_warnings(array $packageKeys): array
{
    $set = array_fill_keys($packageKeys, true);
    $out = [];
    $add = static function (array &$out, string $code, string $title, string $detail, bool $ownerEx = true) use ($set): void {
        $out[] = [
            'code' => $code,
            'title_fa' => $title,
            'detail_fa' => $detail,
            'safeguard_fa' => 'maker-checker سمت سرور فعال می‌ماند؛ این هشدار توصیه را حذف نمی‌کند.',
            'owner_exception_required' => $ownerEx,
        ];
    };

    if (!empty($set['RECEPTION_OPERATOR']) && !empty($set['WORKSHOP_QC']) && !empty($set['DELIVERY_OPERATOR'])) {
        $add($out, 'RECEPTION_QC_DELIVERY', 'تعارض پذیرش + کنترل کیفیت + ترخیص',
            'یک نفر همزمان پیشنهاد پذیرش، QC و تحویل دارد. گردش باید در سطح تراکنش maker-checker بماند و استثنای مالک لازم است.');
    }
    if (!empty($set['FINANCE_ADMIN_MANAGER']) || !empty($set['FINANCE_ADMIN_SUPERVISOR'])) {
        if (!empty($set['FINANCE_ADMIN_OPERATOR']) || !empty($set['FINANCE_ADMIN_MANAGER'])) {
            $add($out, 'FINANCE_CREATE_APPROVE', 'ثبت مالی + تأیید همان جریان',
                'بسته مالی ممکن است ثبت و تأیید را در یک نقش ترکیب کند. تأیید تراکنش خود باید توسط maker-checker مسدود شود.');
        }
    }
    if (!empty($set['FINANCE_ADMIN_MANAGER'])) {
        $add($out, 'PAYROLL_CALC_APPROVE', 'محاسبه/بازبینی حقوق + تأیید نهایی',
            'مدیر مالی ممکن است بازبینی و تأیید حقوق را با هم داشته باشد — تأیید نهایی خود باید جدا شود.', true);
    }
    if (!empty($set['PURCHASE_WAREHOUSE_MANAGER'])) {
        $add($out, 'PURCHASE_WAREHOUSE_OVERLAP', 'تأیید خرید + صدور انبار',
            'مدیر خرید و انبار ممکن است تأیید خرید و صدور موجودی را هم‌زمان داشته باشد — تفکیک تراکنشی و استثنای مالک لازم است.');
    }
    if (!empty($set['INTERNAL_MANAGER_HALL_MANAGER'])) {
        $add($out, 'REPORT_CREATE_APPROVE_RISK', 'خطر تأیید گزارش توسط مدیر سالن',
            'مدیر سالن گزارش‌ها را تأیید می‌کند؛ نباید گزارش‌ساز و تأییدکننده همان رکورد باشد (maker-checker).', false);
    }
    if (!empty($set['WAREHOUSE_SUPERVISOR']) || !empty($set['SENIOR_MECHANIC'])) {
        if (!empty($set['WAREHOUSE_SUPERVISOR'])) {
            $add($out, 'CONSUMABLE_REQUEST_REVERSE', 'مصرف داخلی + تأیید ابطال',
                'سرپرست انبار می‌تواند تأیید مصرف و مسیر ابطال را نزدیک کند — maker-checker ابطال الزامی است.', false);
        }
    }
    if (!empty($set['SYSTEM_PRODUCT_OWNER'])) {
        $add($out, 'USER_ADMIN_ACCESS_APPROVE', 'مدیریت کاربر + تأیید دسترسی',
            'مالک سامانه مدیریت کاربر و ماتریس را دارد — این اختیار ذاتی است و از طریق بسته عادی واگذار نمی‌شود.', false);
    }
    if (!empty($set['CUSTOMER_PHONE_CAMPAIGN']) && (!empty($set['CUSTOMER_PROFILE_AGENT']) || !empty($set['DIGITAL_COMMUNICATIONS']))) {
        $add($out, 'PHONE_CAMPAIGN_WITH_PROFILE', 'پروفایل مشتری + تبلیغات پیامکی',
            'دسترسی تبلیغات پیامکی باید از پروفایل مشتری جدا و فقط با تأیید صریح مالک فعال شود.');
    }

    return $out;
}

/**
 * Build recommendation payload for one personnel row (no DB write).
 *
 * @param array<string,mixed> $person from m360_am_personnel_rows
 * @return array<string,mixed>
 */
function m360_rec_build_for_person($conn, array $person): array
{
    $code = (string)($person['employee_code'] ?? '');
    $map = m360_access_personnel_package_map();
    $pkgs = m360_access_package_definitions();
    $idx = m360_rec_permission_index($conn);
    $spec = $map[$code] ?? null;

    if ($spec === null) {
        return [
            'employee_code' => $code,
            'user_id' => (int)($person['user_id'] ?? 0),
            'full_name' => (string)($person['full_name'] ?? ''),
            'unit_name' => (string)($person['unit_name'] ?? ''),
            'job_title' => (string)($person['job_title'] ?? ''),
            'status' => 'OWNER_REVIEW_REQUIRED',
            'ui_flags' => ['نیازمند بررسی'],
            'primary_packages' => [],
            'secondary_packages' => [],
            'reason_fa' => 'نگاشت Owner برای این پرسنل تعریف نشده است.',
            'items' => [],
            'conflicts' => [],
            'counts' => ['enforceable' => 0, 'pending' => 0, 'sensitive' => 0, 'conflict' => 0],
            'locked_profile' => true,
            'is_system_owner' => (int)($person['is_system_owner'] ?? 0) === 1,
            'formula' => 'LOCKED_PROFILE + PRIMARY + SECONDARY + SCOPE + AUTHORITY + SENSITIVE_GATES + SOD + OWNER_EXCEPTION',
        ];
    }

    $primary = $spec['primary'];
    $secondary = $spec['secondary'];
    $allPkg = array_values(array_unique(array_merge($primary, $secondary)));
    $conflicts = m360_rec_sod_warnings($allPkg);

    // Sensitive pending packages listed as codes or permission keys
    $sensitivePending = [];
    foreach ($spec['sensitive_pending'] as $sp) {
        if (isset($pkgs[$sp])) {
            $sensitivePending[] = $sp;
        }
    }

    $items = [];
    $seen = [];

    // Layer A — locked profile
    foreach (m360_access_package_locked_profile_keys() as $pk) {
        $meta = $idx[$pk] ?? null;
        $enf = $meta['enforcement_state'] ?? 'NOT_YET_ENFORCED';
        $items[] = [
            'permission_key' => $pk,
            'title_fa' => $meta['title_fa'] ?? $pk,
            'package_key' => 'LOCKED_PERSONNEL_PROFILE',
            'package_title_fa' => 'پروفایل پرسنلی',
            'layer' => 'A',
            'bucket' => $enf === 'ENFORCED' ? 'ENFORCEABLE_NOW' : 'PENDING_SOFTWARE',
            'enforcement_state' => $enf,
            'is_sensitive' => 0,
            'is_assignable_now' => 0,
            'reason_fa' => 'لایه قفل‌شده پروفایل پرسنلی — فقط رکورد خود؛ غیرقابل حذف',
        ];
        $seen[$pk] = true;
    }

    $emitPkg = static function (string $pkgKey, string $layer) use (&$items, &$seen, $pkgs, $idx, $sensitivePending): void {
        $pkg = $pkgs[$pkgKey] ?? null;
        if ($pkg === null) {
            return;
        }
        $sensitiveSet = array_fill_keys($pkg['sensitive'], true);
        foreach ($pkg['permissions'] as $pk) {
            if (isset($seen[$pk])) {
                continue;
            }
            $meta = $idx[$pk] ?? null;
            $enf = $meta ? $meta['enforcement_state'] : 'NOT_YET_ENFORCED';
            if ($enf === 'LEGACY_QUARANTINED') {
                continue;
            }
            $isSens = isset($sensitiveSet[$pk]) ? 1 : 0;
            // Campaign package always sensitive / not auto-applicable
            if ($pkgKey === 'CUSTOMER_PHONE_CAMPAIGN' || in_array($pkgKey, $sensitivePending, true)) {
                $isSens = 1;
            }
            $bucket = ($enf === 'ENFORCED' && $isSens === 0 && !empty($pkg['assignable_to_users']))
                ? 'ENFORCEABLE_NOW'
                : (($enf === 'ENFORCED' && $isSens === 1) ? 'SENSITIVE_OWNER_GATE' : 'PENDING_SOFTWARE');
            if ($pkgKey === 'SYSTEM_PRODUCT_OWNER') {
                $bucket = 'SENSITIVE_OWNER_GATE';
            }
            $items[] = [
                'permission_key' => $pk,
                'title_fa' => $meta['title_fa'] ?? $pk,
                'package_key' => $pkgKey,
                'package_title_fa' => $pkg['title_fa'],
                'layer' => $layer,
                'bucket' => $bucket,
                'enforcement_state' => $enf,
                'is_sensitive' => $isSens,
                'is_assignable_now' => 0,
                'reason_fa' => $pkg['purpose_fa'] . ' | محدوده: ' . $pkg['scope'] . ' | اختیار: ' . $pkg['authority'],
            ];
            $seen[$pk] = true;
        }
    };

    foreach ($primary as $pk) {
        $emitPkg($pk, 'B');
    }
    foreach ($secondary as $pk) {
        $emitPkg($pk, 'C');
    }

    // Explicit sensitive pending packages (e.g. phone campaign) as Owner gate items
    foreach ($sensitivePending as $spk) {
        if (in_array($spk, $allPkg, true)) {
            continue;
        }
        $emitPkg($spk, 'F');
        // mark as not in primary — already emitted
    }

    $enfCount = 0;
    $pendCount = 0;
    $sensCount = 0;
    foreach ($items as $it) {
        if ($it['bucket'] === 'ENFORCEABLE_NOW') {
            $enfCount++;
        }
        if ($it['bucket'] === 'PENDING_SOFTWARE') {
            $pendCount++;
        }
        if ((int)$it['is_sensitive'] === 1 || $it['bucket'] === 'SENSITIVE_OWNER_GATE') {
            $sensCount++;
        }
    }

    $status = 'GENERATED';
    $flags = ['پیشنهاد آماده'];
    if ($conflicts !== []) {
        $status = 'CONFLICT_REVIEW_REQUIRED';
        $flags = ['تعارض وظایف', 'نیازمند بررسی'];
    } elseif ($sensCount > 0 || $sensitivePending !== []) {
        $status = 'OWNER_REVIEW_REQUIRED';
        $flags = ['نیازمند بررسی', 'دسترسی‌های حساس'];
    }
    if ($pendCount > 0) {
        $flags[] = 'دسترسی‌های در انتظار تکمیل';
        $flags = array_values(array_unique($flags));
    }
    if ((int)($person['is_system_owner'] ?? 0) === 1 || $code === 'M360-100001') {
        $status = 'GENERATED';
        $flags = ['پیشنهاد آماده', 'مالک سامانه (ذاتی)'];
    }

    $primaryMeta = [];
    foreach ($primary as $k) {
        $primaryMeta[] = [
            'package_key' => $k,
            'title_fa' => $pkgs[$k]['title_fa'] ?? $k,
            'scope' => $pkgs[$k]['scope'] ?? '',
            'authority' => $pkgs[$k]['authority'] ?? '',
        ];
    }
    $secondaryMeta = [];
    foreach ($secondary as $k) {
        $secondaryMeta[] = [
            'package_key' => $k,
            'title_fa' => $pkgs[$k]['title_fa'] ?? $k,
            'scope' => $pkgs[$k]['scope'] ?? '',
            'authority' => $pkgs[$k]['authority'] ?? '',
        ];
    }

    return [
        'employee_code' => $code,
        'user_id' => (int)($person['user_id'] ?? 0),
        'full_name' => (string)($person['full_name'] ?? ''),
        'unit_name' => (string)($person['unit_name'] ?? ''),
        'job_title' => (string)($person['job_title'] ?? ''),
        'status' => $status,
        'ui_flags' => $flags,
        'primary_packages' => $primaryMeta,
        'secondary_packages' => $secondaryMeta,
        'reason_fa' => (string)$spec['reason_fa'],
        'items' => $items,
        'conflicts' => $conflicts,
        'counts' => [
            'enforceable' => $enfCount,
            'pending' => $pendCount,
            'sensitive' => $sensCount,
            'conflict' => count($conflicts),
        ],
        'locked_profile' => true,
        'is_system_owner' => (int)($person['is_system_owner'] ?? 0) === 1 || $code === 'M360-100001',
        'business_owner_not_system_owner' => $code === 'M360-1007',
        'formula' => 'LOCKED_PROFILE + PRIMARY + SECONDARY + SCOPE + AUTHORITY + SENSITIVE_GATES + SOD + OWNER_EXCEPTION',
        'phone_campaign_separate' => in_array('CUSTOMER_PHONE_CAMPAIGN', $sensitivePending, true)
            || in_array('CUSTOMER_PHONE_CAMPAIGN', $allPkg, true),
    ];
}

/** @return list<array<string,mixed>> */
function m360_rec_build_all($conn): array
{
    $people = m360_am_personnel_rows($conn, []);
    $out = [];
    foreach ($people as $p) {
        $out[] = m360_rec_build_for_person($conn, $p);
    }
    return $out;
}

function m360_rec_ensure_tables($conn): void
{
    $exists = m360_am_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='core_access_package_definitions'");
    if ($exists === null) {
        m360_am_exec($conn, "CREATE TABLE dbo.core_access_package_definitions (
            package_id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
            package_key nvarchar(80) NOT NULL,
            title_fa nvarchar(200) NOT NULL,
            purpose_fa nvarchar(600) NULL,
            scope_code nvarchar(40) NOT NULL,
            authority_level nvarchar(40) NOT NULL,
            assignable_to_users bit NOT NULL CONSTRAINT DF_capd_assign DEFAULT 1,
            is_active bit NOT NULL CONSTRAINT DF_capd_active DEFAULT 1,
            updated_at datetime2 NOT NULL CONSTRAINT DF_capd_upd DEFAULT SYSUTCDATETIME(),
            CONSTRAINT UQ_capd_key UNIQUE (package_key)
        )");
    }
    $exists = m360_am_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='core_access_package_permissions'");
    if ($exists === null) {
        m360_am_exec($conn, "CREATE TABLE dbo.core_access_package_permissions (
            id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
            package_key nvarchar(80) NOT NULL,
            permission_key nvarchar(160) NOT NULL,
            is_sensitive bit NOT NULL CONSTRAINT DF_capp_sens DEFAULT 0,
            CONSTRAINT UQ_capp UNIQUE (package_key, permission_key)
        )");
    }
    $exists = m360_am_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='core_personnel_access_recommendations'");
    if ($exists === null) {
        m360_am_exec($conn, "CREATE TABLE dbo.core_personnel_access_recommendations (
            recommendation_id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
            employee_code nvarchar(40) NOT NULL,
            user_id int NOT NULL,
            status nvarchar(40) NOT NULL,
            reason_fa nvarchar(800) NULL,
            primary_packages_json nvarchar(max) NULL,
            secondary_packages_json nvarchar(max) NULL,
            conflicts_json nvarchar(max) NULL,
            counts_json nvarchar(max) NULL,
            ui_flags_json nvarchar(max) NULL,
            generated_at datetime2 NOT NULL CONSTRAINT DF_cpar_gen DEFAULT SYSUTCDATETIME(),
            reviewed_at datetime2 NULL,
            reviewed_by_user_id int NULL,
            review_note nvarchar(400) NULL,
            CONSTRAINT CK_cpar_status CHECK (status IN (
                N'GENERATED', N'OWNER_REVIEW_REQUIRED', N'CONFLICT_REVIEW_REQUIRED',
                N'APPROVED_FOR_FUTURE_APPLY', N'REJECTED', N'SUPERSEDED'))
        )");
    }
    $exists = m360_am_one($conn, "SELECT 1 x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='core_personnel_access_recommendation_items'");
    if ($exists === null) {
        m360_am_exec($conn, "CREATE TABLE dbo.core_personnel_access_recommendation_items (
            item_id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
            recommendation_id int NOT NULL,
            permission_key nvarchar(160) NOT NULL,
            package_key nvarchar(80) NOT NULL,
            layer_code nvarchar(10) NULL,
            bucket_code nvarchar(40) NOT NULL,
            enforcement_state nvarchar(40) NOT NULL,
            is_sensitive bit NOT NULL CONSTRAINT DF_cpari_sens DEFAULT 0,
            reason_fa nvarchar(600) NULL,
            CONSTRAINT FK_cpari_rec FOREIGN KEY (recommendation_id)
                REFERENCES dbo.core_personnel_access_recommendations(recommendation_id)
        )");
    }
}

/**
 * Persist recommendations (supersede previous). Does NOT touch effective access tables.
 *
 * @param list<array<string,mixed>> $recs
 */
function m360_rec_persist_all($conn, array $recs, int $actorUserId = 0): array
{
    m360_rec_ensure_tables($conn);
    $pkgs = m360_access_package_definitions();
    foreach ($pkgs as $pkg) {
        $ex = m360_am_one($conn, 'SELECT package_id FROM dbo.core_access_package_definitions WHERE package_key=?', [$pkg['package_key']]);
        if ($ex === null) {
            m360_am_exec($conn, 'INSERT INTO dbo.core_access_package_definitions
                (package_key, title_fa, purpose_fa, scope_code, authority_level, assignable_to_users, is_active, updated_at)
                VALUES (?,?,?,?,?,?,1,SYSUTCDATETIME())', [
                $pkg['package_key'], $pkg['title_fa'], $pkg['purpose_fa'], $pkg['scope'], $pkg['authority'],
                !empty($pkg['assignable_to_users']) ? 1 : 0,
            ]);
        } else {
            m360_am_exec($conn, 'UPDATE dbo.core_access_package_definitions SET
                title_fa=?, purpose_fa=?, scope_code=?, authority_level=?, assignable_to_users=?, updated_at=SYSUTCDATETIME()
                WHERE package_key=?', [
                $pkg['title_fa'], $pkg['purpose_fa'], $pkg['scope'], $pkg['authority'],
                !empty($pkg['assignable_to_users']) ? 1 : 0, $pkg['package_key'],
            ]);
        }
        foreach ($pkg['permissions'] as $permKey) {
            $px = m360_am_one($conn, 'SELECT id FROM dbo.core_access_package_permissions WHERE package_key=? AND permission_key=?', [
                $pkg['package_key'], $permKey,
            ]);
            $sens = in_array($permKey, $pkg['sensitive'], true) ? 1 : 0;
            if ($px === null) {
                m360_am_exec($conn, 'INSERT INTO dbo.core_access_package_permissions (package_key, permission_key, is_sensitive) VALUES (?,?,?)', [
                    $pkg['package_key'], $permKey, $sens,
                ]);
            }
        }
    }

    // Supersede open recommendations
    m360_am_exec($conn, "UPDATE dbo.core_personnel_access_recommendations
        SET status=N'SUPERSEDED' WHERE status IN (N'GENERATED', N'OWNER_REVIEW_REQUIRED', N'CONFLICT_REVIEW_REQUIRED', N'APPROVED_FOR_FUTURE_APPLY')");

    $saved = 0;
    foreach ($recs as $rec) {
        m360_am_exec($conn, 'INSERT INTO dbo.core_personnel_access_recommendations
            (employee_code, user_id, status, reason_fa, primary_packages_json, secondary_packages_json,
             conflicts_json, counts_json, ui_flags_json, generated_at)
            VALUES (?,?,?,?,?,?,?,?,?,SYSUTCDATETIME())', [
            $rec['employee_code'],
            (int)$rec['user_id'],
            $rec['status'],
            $rec['reason_fa'],
            json_encode($rec['primary_packages'], JSON_UNESCAPED_UNICODE),
            json_encode($rec['secondary_packages'], JSON_UNESCAPED_UNICODE),
            json_encode($rec['conflicts'], JSON_UNESCAPED_UNICODE),
            json_encode($rec['counts'], JSON_UNESCAPED_UNICODE),
            json_encode($rec['ui_flags'], JSON_UNESCAPED_UNICODE),
        ]);
        $ridRow = m360_am_one($conn, 'SELECT TOP 1 recommendation_id FROM dbo.core_personnel_access_recommendations WHERE employee_code=? ORDER BY recommendation_id DESC', [
            $rec['employee_code'],
        ]);
        $rid = (int)($ridRow['recommendation_id'] ?? 0);
        if ($rid < 1) {
            continue;
        }
        foreach ($rec['items'] as $it) {
            m360_am_exec($conn, 'INSERT INTO dbo.core_personnel_access_recommendation_items
                (recommendation_id, permission_key, package_key, layer_code, bucket_code, enforcement_state, is_sensitive, reason_fa)
                VALUES (?,?,?,?,?,?,?,?)', [
                $rid,
                $it['permission_key'],
                $it['package_key'],
                $it['layer'],
                $it['bucket'],
                $it['enforcement_state'],
                (int)$it['is_sensitive'],
                $it['reason_fa'],
            ]);
        }
        $saved++;
    }

    return ['saved' => $saved, 'actor' => $actorUserId];
}

/** Mark recommendation for Owner review — no access change. */
function m360_rec_mark_owner_review($conn, string $employeeCode, int $actorId, string $note = ''): bool
{
    $row = m360_am_one($conn, "SELECT TOP 1 recommendation_id FROM dbo.core_personnel_access_recommendations
        WHERE employee_code=? AND status<>N'SUPERSEDED' ORDER BY recommendation_id DESC", [$employeeCode]);
    if ($row === null) {
        return false;
    }
    return m360_am_exec($conn, "UPDATE dbo.core_personnel_access_recommendations
        SET status=N'OWNER_REVIEW_REQUIRED', reviewed_at=SYSUTCDATETIME(), reviewed_by_user_id=?, review_note=?
        WHERE recommendation_id=?", [$actorId, mb_substr($note, 0, 400), (int)$row['recommendation_id']]);
}

/** Load latest recommendations keyed by employee_code. */
function m360_rec_load_latest_map($conn): array
{
    $rows = m360_am_fetch_all($conn, "SELECT r.* FROM dbo.core_personnel_access_recommendations r
        INNER JOIN (
            SELECT employee_code, MAX(recommendation_id) AS mid
            FROM dbo.core_personnel_access_recommendations
            WHERE status<>N'SUPERSEDED'
            GROUP BY employee_code
        ) x ON x.mid=r.recommendation_id");
    $map = [];
    foreach ($rows as $r) {
        $code = (string)$r['employee_code'];
        $items = m360_am_fetch_all($conn, 'SELECT * FROM dbo.core_personnel_access_recommendation_items WHERE recommendation_id=?', [
            (int)$r['recommendation_id'],
        ]);
        $map[$code] = [
            'recommendation_id' => (int)$r['recommendation_id'],
            'employee_code' => $code,
            'user_id' => (int)$r['user_id'],
            'status' => (string)$r['status'],
            'reason_fa' => (string)($r['reason_fa'] ?? ''),
            'primary_packages' => json_decode((string)($r['primary_packages_json'] ?? '[]'), true) ?: [],
            'secondary_packages' => json_decode((string)($r['secondary_packages_json'] ?? '[]'), true) ?: [],
            'conflicts' => json_decode((string)($r['conflicts_json'] ?? '[]'), true) ?: [],
            'counts' => json_decode((string)($r['counts_json'] ?? '{}'), true) ?: [],
            'ui_flags' => json_decode((string)($r['ui_flags_json'] ?? '[]'), true) ?: [],
            'items' => array_map(static function ($it) {
                return [
                    'permission_key' => (string)$it['permission_key'],
                    'package_key' => (string)$it['package_key'],
                    'layer' => (string)($it['layer_code'] ?? ''),
                    'bucket' => (string)$it['bucket_code'],
                    'enforcement_state' => (string)$it['enforcement_state'],
                    'is_sensitive' => (int)$it['is_sensitive'],
                    'reason_fa' => (string)($it['reason_fa'] ?? ''),
                ];
            }, $items),
            'locked_profile' => true,
        ];
    }
    return $map;
}

function m360_rec_write_preview_tsv(array $recs, string $path): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        throw new RuntimeException('Cannot write preview');
    }
    fwrite($fh, "personnel_code\tname\tunit\ttitle\tprimary\tsecondary\tenforceable\tpending\tsensitive\tconflicts\tstatus\treason\n");
    foreach ($recs as $r) {
        $prim = implode('|', array_map(static fn($x) => $x['title_fa'] ?? $x['package_key'] ?? '', $r['primary_packages'] ?? []));
        $sec = implode('|', array_map(static fn($x) => $x['title_fa'] ?? $x['package_key'] ?? '', $r['secondary_packages'] ?? []));
        $line = [
            $r['employee_code'] ?? '',
            $r['full_name'] ?? '',
            $r['unit_name'] ?? '',
            $r['job_title'] ?? '',
            $prim,
            $sec,
            (string)(($r['counts']['enforceable'] ?? 0)),
            (string)(($r['counts']['pending'] ?? 0)),
            (string)(($r['counts']['sensitive'] ?? 0)),
            (string)(($r['counts']['conflict'] ?? 0)),
            $r['status'] ?? '',
            str_replace(["\t", "\n", "\r"], ' ', (string)($r['reason_fa'] ?? '')),
        ];
        fwrite($fh, implode("\t", $line) . "\n");
    }
    fclose($fh);
}
