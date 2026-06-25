/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_06_seed_roles_permissions.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Seeds Version 0 staff access foundation:
  - core_roles
  - core_permissions
  - core_role_permissions (role-permission matrix)

Important business constraints:
  - No direct permission assignment to users (no core_user_permissions table).
  - Do NOT assign roles to any user in this script (do NOT write core_user_roles).
  - Do NOT create access requests in this script.
  - CUSTOMER access level is out of scope for Version 0 and MUST NOT be seeded.
  - Matrix is additive only: this script does not delete existing permissions.

Design reference:
  docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md
Policy reference:
  docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md

Prerequisites:
  core_v0_02_master_tables.sql (roles/permissions tables)

Idempotent:
  - Uses MERGE for roles and permissions.
  - Uses MERGE to insert missing role-permission pairs.
  - Does not DELETE or TRUNCATE.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1) Seed roles (staff only)
---------------------------------------------------------------------------- */
MERGE dbo.core_roles AS tgt
USING (
    VALUES
        (N'owner',                 N'مالک سیستم',              N'OWNER',              N'مالک سیستم (اختیار اضطراری و نظارت نهایی)', 1,   1),
        (N'system_admin',          N'ادمین سیستم',             N'OWNER',              N'ادمین سیستم (اجرای فنی چرخه عمر دسترسی)',   1,   5),
        (N'general_manager',       N'مدیر کل',                 N'GENERAL_MANAGER',    N'مدیر کل (مشاهده و گزارش و نظارت)',          1,  10),
        (N'operations_manager',    N'مدیر عملیات',             N'OPERATIONS_MANAGER', N'مدیر عملیات (هماهنگی و تایید تغییرات کلیدی)', 1,  20),
        (N'department_manager',    N'مدیر واحد',               N'DEPARTMENT_MANAGER', N'مدیر واحد (تایید درخواست‌های واحد خود)',    1,  30),

        (N'reception_staff',       N'پذیرش',                   N'STAFF',              N'پرسنل پذیرش',                               1, 100),
        (N'crm_staff',             N'ارتباط با مشتریان',        N'STAFF',              N'پرسنل CRM',                                 1, 110),
        (N'mechanical_staff',      N'مکانیک',                  N'STAFF',              N'پرسنل مکانیک',                              1, 120),
        (N'electrical_staff',      N'برق و آپشن',              N'STAFF',              N'پرسنل برق و آپشن',                          1, 130),
        (N'suspension_staff',      N'زیروبند و تعلیق',         N'STAFF',              N'پرسنل زیروبند و تعلیق',                     1, 140),
        (N'technical_manager',     N'مدیر فنی',                N'STAFF',              N'مدیر فنی (بازبینی/تایید فنی)',              1, 150),
        (N'inventory_staff',       N'انبار',                    N'STAFF',              N'پرسنل انبار',                               1, 160),
        (N'inventory_price_control', N'کنترل قیمت انبار',       N'STAFF',              N'انبار (صرفاً مشاهده/ثبت قیمت خرید)',         1, 165),
        (N'purchase_staff',        N'خرید',                     N'STAFF',              N'پرسنل خرید',                                1, 170),
        (N'finance_staff',         N'مالی',                     N'STAFF',              N'پرسنل مالی',                                1, 180),
        (N'hr_staff',              N'منابع انسانی و اداری',     N'STAFF',              N'پرسنل منابع انسانی و اداری',                1, 190),
        (N'marketing_sales_staff', N'بازاریابی و فروش',         N'STAFF',              N'پرسنل بازاریابی و فروش',                    1, 200),

        (N'read_only',             N'فقط مشاهده',              N'READ_ONLY',          N'فقط مشاهده و گزارش (بدون ویرایش)',          1, 900)
) AS src (role_key, role_name, access_level, description, is_active, sort_order)
    ON tgt.role_key = src.role_key
WHEN NOT MATCHED BY TARGET THEN
    INSERT (role_key, role_name, access_level, description, is_active, sort_order, created_at)
    VALUES (src.role_key, src.role_name, src.access_level, src.description, src.is_active, src.sort_order, SYSUTCDATETIME())
WHEN MATCHED AND (
        tgt.role_name <> src.role_name
        OR tgt.access_level <> src.access_level
        OR ISNULL(tgt.description, N'') <> ISNULL(src.description, N'')
        OR tgt.is_active <> src.is_active
        OR tgt.sort_order <> src.sort_order
    ) THEN
    UPDATE SET
        tgt.role_name   = src.role_name,
        tgt.access_level= src.access_level,
        tgt.description = src.description,
        tgt.is_active   = src.is_active,
        tgt.sort_order  = src.sort_order,
        tgt.updated_at  = SYSUTCDATETIME();
GO

/* ----------------------------------------------------------------------------
   2) Seed permissions
   Note: permission_key is the canonical unique identifier.
---------------------------------------------------------------------------- */
MERGE dbo.core_permissions AS tgt
USING (
    VALUES
        /* Access lifecycle permissions */
        (N'access.request.create',      N'access',  N'request.create',      N'ثبت درخواست دسترسی',            10, 1),
        (N'access.request.approve',     N'access',  N'request.approve',     N'تأیید/رد درخواست دسترسی',       11, 1),
        (N'access.request.view_all',    N'access',  N'request.view_all',    N'مشاهده همه درخواست‌های دسترسی', 12, 1),
        (N'access.request.apply',       N'access',  N'request.apply',       N'اعمال (فعال‌سازی) درخواست دسترسی', 13, 1),
        (N'access.user.lifecycle',      N'access',  N'user.lifecycle',      N'مدیریت چرخه عمر پرسنل',          14, 1),
        (N'access.roles.manage',        N'access',  N'roles.manage',        N'مدیریت نقش‌ها',                  15, 1),
        (N'access.permissions.manage',  N'access',  N'permissions.manage',  N'مدیریت مجوزها',                  16, 1),
        (N'access.matrix.manage',       N'access',  N'matrix.manage',       N'مدیریت ماتریس نقش/مجوز',         17, 1),
        (N'access.audit.view',          N'access',  N'audit.view',          N'مشاهده لاگ امنیتی',              18, 1),
        (N'access.history.view',        N'access',  N'history.view',        N'مشاهده تاریخچه تغییر دسترسی',    19, 1),

        /* Core admin permissions */
        (N'admin.dashboard',            N'admin',   N'dashboard',           N'داشبورد مدیریت',                 30, 1),
        (N'admin.users',                N'admin',   N'users',               N'مدیریت کاربران پرسنل',            31, 1),
        (N'admin.departments',          N'admin',   N'departments',         N'مدیریت واحدها',                   32, 1),
        (N'admin.positions',            N'admin',   N'positions',           N'مدیریت سمت‌ها',                   33, 1),
        (N'admin.settings',             N'admin',   N'settings',            N'تنظیمات مدیریتی',                 34, 1),

        /* Operational module permissions */
        (N'reception.view',             N'reception', N'view',              N'مشاهده پذیرش',                    100, 1),
        (N'reception.create',           N'reception', N'create',            N'ثبت پذیرش',                       101, 1),
        (N'reception.edit',             N'reception', N'edit',              N'ویرایش پذیرش',                    102, 1),

        (N'crm.view',                   N'crm',       N'view',              N'مشاهده CRM',                      110, 1),
        (N'crm.followup',               N'crm',       N'followup',          N'پیگیری مشتری',                    111, 1),

        (N'mechanical.view',            N'mechanical', N'view',             N'مشاهده مکانیک',                   120, 1),
        (N'mechanical.update',          N'mechanical', N'update',           N'ثبت/به‌روزرسانی مکانیک',          121, 1),

        (N'electrical.view',            N'electrical', N'view',             N'مشاهده برق و آپشن',               130, 1),
        (N'electrical.update',          N'electrical', N'update',           N'ثبت/به‌روزرسانی برق و آپشن',      131, 1),

        (N'suspension.view',            N'suspension', N'view',             N'مشاهده زیروبند و تعلیق',          140, 1),
        (N'suspension.update',          N'suspension', N'update',           N'ثبت/به‌روزرسانی زیروبند و تعلیق', 141, 1),

        (N'technical.review',           N'technical',  N'review',           N'بازبینی فنی',                     150, 1),
        (N'technical.approve',          N'technical',  N'approve',          N'تأیید فنی',                        151, 1),

        (N'inventory.view',             N'inventory',  N'view',             N'مشاهده انبار',                     160, 1),
        (N'inventory.inbound',          N'inventory',  N'inbound',          N'ثبت ورود کالا',                    161, 1),
        (N'inventory.outbound',         N'inventory',  N'outbound',         N'ثبت خروج کالا',                    162, 1),
        (N'inventory.price',            N'inventory',  N'price',            N'مشاهده/ثبت قیمت خرید',             163, 1),

        (N'purchase.view',              N'purchase',   N'view',             N'مشاهده خرید',                      170, 1),
        (N'purchase.create',            N'purchase',   N'create',           N'ثبت خرید',                         171, 1),
        (N'purchase.approve',           N'purchase',   N'approve',          N'تأیید خرید',                        172, 1),

        (N'finance.view',               N'finance',    N'view',             N'مشاهده مالی',                      180, 1),
        (N'finance.payment',            N'finance',    N'payment',          N'ثبت پرداخت',                       181, 1),
        (N'finance.invoice',            N'finance',    N'invoice',          N'ثبت/مشاهده فاکتور',                182, 1),

        (N'hr.view',                    N'hr',         N'view',             N'مشاهده منابع انسانی',              190, 1),
        (N'hr.staff',                   N'hr',         N'staff',            N'مدیریت پرونده پرسنلی',             191, 1),

        (N'marketing.view',             N'marketing',  N'view',             N'مشاهده بازاریابی',                 200, 1),
        (N'marketing.campaign',         N'marketing',  N'campaign',         N'مدیریت کمپین',                      201, 1),

        (N'reports.view',               N'reports',    N'view',             N'مشاهده گزارش‌ها',                  300, 1)
) AS src (permission_key, module_key, action_key, permission_label, sort_order, is_active)
    ON tgt.permission_key = src.permission_key
WHEN NOT MATCHED BY TARGET THEN
    INSERT (permission_key, module_key, action_key, permission_label, sort_order, is_active, created_at)
    VALUES (src.permission_key, src.module_key, src.action_key, src.permission_label, src.sort_order, src.is_active, SYSUTCDATETIME())
WHEN MATCHED AND (
        tgt.module_key <> src.module_key
        OR tgt.action_key <> src.action_key
        OR tgt.permission_label <> src.permission_label
        OR tgt.sort_order <> src.sort_order
        OR tgt.is_active <> src.is_active
    ) THEN
    UPDATE SET
        tgt.module_key = src.module_key,
        tgt.action_key = src.action_key,
        tgt.permission_label = src.permission_label,
        tgt.sort_order = src.sort_order,
        tgt.is_active = src.is_active;
GO

/* ----------------------------------------------------------------------------
   3) Seed role-permission matrix (additive only)

   Rules:
     1. owner gets all permissions.
     2. system_admin gets all access/admin permissions + audit/history.
     3. general_manager gets: reports.view, access.request.view_all, access.audit.view,
        access.history.view + operational view permissions.
     4. operations_manager gets operational view/update permissions + reports.view +
        access.request.approve.
     5. department_manager gets access.request.create + access.request.approve +
        reports.view + operational view/update permissions (no admin/manage permissions).
     6. each staff role gets only its own module permissions.
     7. finance_staff gets finance.view/payment/invoice.
     8. inventory_price_control gets inventory.view + inventory.price.
     9. read_only gets reports.view + view-only permissions (plus admin.dashboard).

   Note: granted_by_user_id remains NULL (no users seeded here).
---------------------------------------------------------------------------- */

DECLARE @RolePerm TABLE
(
    role_key        NVARCHAR(80)  NOT NULL,
    permission_key  NVARCHAR(120) NOT NULL
);

/* system_admin: access.* + admin.* + audit/history (already in access.*) */
INSERT INTO @RolePerm (role_key, permission_key)
SELECT N'system_admin', p.permission_key
FROM dbo.core_permissions AS p
WHERE p.permission_key LIKE N'access.%'
   OR p.permission_key LIKE N'admin.%';

/* general_manager: reports + view_all + audit/history + operational view permissions */
INSERT INTO @RolePerm (role_key, permission_key) VALUES
    (N'general_manager', N'reports.view'),
    (N'general_manager', N'access.request.view_all'),
    (N'general_manager', N'access.audit.view'),
    (N'general_manager', N'access.history.view'),
    (N'general_manager', N'reception.view'),
    (N'general_manager', N'crm.view'),
    (N'general_manager', N'mechanical.view'),
    (N'general_manager', N'electrical.view'),
    (N'general_manager', N'suspension.view'),
    (N'general_manager', N'inventory.view'),
    (N'general_manager', N'purchase.view'),
    (N'general_manager', N'finance.view'),
    (N'general_manager', N'hr.view'),
    (N'general_manager', N'marketing.view');

/* operations_manager: operational view/update + reports + approve requests */
INSERT INTO @RolePerm (role_key, permission_key) VALUES
    (N'operations_manager', N'access.request.approve'),
    (N'operations_manager', N'reports.view'),
    (N'operations_manager', N'reception.view'),
    (N'operations_manager', N'reception.create'),
    (N'operations_manager', N'reception.edit'),
    (N'operations_manager', N'crm.view'),
    (N'operations_manager', N'crm.followup'),
    (N'operations_manager', N'mechanical.view'),
    (N'operations_manager', N'mechanical.update'),
    (N'operations_manager', N'electrical.view'),
    (N'operations_manager', N'electrical.update'),
    (N'operations_manager', N'suspension.view'),
    (N'operations_manager', N'suspension.update'),
    (N'operations_manager', N'technical.review'),
    (N'operations_manager', N'technical.approve'),
    (N'operations_manager', N'inventory.view'),
    (N'operations_manager', N'inventory.inbound'),
    (N'operations_manager', N'inventory.outbound'),
    (N'operations_manager', N'purchase.view'),
    (N'operations_manager', N'purchase.create'),
    (N'operations_manager', N'purchase.approve'),
    (N'operations_manager', N'finance.view'),
    (N'operations_manager', N'hr.view'),
    (N'operations_manager', N'marketing.view'),
    (N'operations_manager', N'marketing.campaign');

/* department_manager: create/approve requests + reports + relevant operational view/update */
INSERT INTO @RolePerm (role_key, permission_key) VALUES
    (N'department_manager', N'access.request.create'),
    (N'department_manager', N'access.request.approve'),
    (N'department_manager', N'reports.view'),
    (N'department_manager', N'reception.view'),
    (N'department_manager', N'reception.create'),
    (N'department_manager', N'reception.edit'),
    (N'department_manager', N'crm.view'),
    (N'department_manager', N'crm.followup'),
    (N'department_manager', N'mechanical.view'),
    (N'department_manager', N'mechanical.update'),
    (N'department_manager', N'electrical.view'),
    (N'department_manager', N'electrical.update'),
    (N'department_manager', N'suspension.view'),
    (N'department_manager', N'suspension.update'),
    (N'department_manager', N'technical.review'),
    (N'department_manager', N'technical.approve'),
    (N'department_manager', N'inventory.view'),
    (N'department_manager', N'inventory.inbound'),
    (N'department_manager', N'inventory.outbound'),
    (N'department_manager', N'purchase.view'),
    (N'department_manager', N'purchase.create'),
    (N'department_manager', N'finance.view'),
    (N'department_manager', N'hr.view'),
    (N'department_manager', N'marketing.view');

/* Staff roles: only their module permissions */
INSERT INTO @RolePerm (role_key, permission_key) VALUES
    (N'reception_staff', N'reception.view'),
    (N'reception_staff', N'reception.create'),
    (N'reception_staff', N'reception.edit'),

    (N'crm_staff', N'crm.view'),
    (N'crm_staff', N'crm.followup'),

    (N'mechanical_staff', N'mechanical.view'),
    (N'mechanical_staff', N'mechanical.update'),

    (N'electrical_staff', N'electrical.view'),
    (N'electrical_staff', N'electrical.update'),

    (N'suspension_staff', N'suspension.view'),
    (N'suspension_staff', N'suspension.update'),

    (N'technical_manager', N'technical.review'),
    (N'technical_manager', N'technical.approve'),

    (N'inventory_staff', N'inventory.view'),
    (N'inventory_staff', N'inventory.inbound'),
    (N'inventory_staff', N'inventory.outbound'),

    (N'inventory_price_control', N'inventory.view'),
    (N'inventory_price_control', N'inventory.price'),

    (N'purchase_staff', N'purchase.view'),
    (N'purchase_staff', N'purchase.create'),

    (N'finance_staff', N'finance.view'),
    (N'finance_staff', N'finance.payment'),
    (N'finance_staff', N'finance.invoice'),

    (N'hr_staff', N'hr.view'),
    (N'hr_staff', N'hr.staff'),

    (N'marketing_sales_staff', N'marketing.view'),
    (N'marketing_sales_staff', N'marketing.campaign');

/* read_only: reports + view-only permissions (+ admin.dashboard for landing) */
INSERT INTO @RolePerm (role_key, permission_key)
SELECT N'read_only', p.permission_key
FROM dbo.core_permissions AS p
WHERE p.permission_key = N'reports.view'
   OR p.permission_key = N'admin.dashboard'
   OR p.permission_key LIKE N'%.view';

/* owner: all permissions */
INSERT INTO @RolePerm (role_key, permission_key)
SELECT N'owner', p.permission_key
FROM dbo.core_permissions AS p;

/* Upsert role-permission pairs (additive only) */
;WITH src AS
(
    SELECT
        r.role_id,
        p.permission_id
    FROM @RolePerm rp
    INNER JOIN dbo.core_roles r
        ON r.role_key = rp.role_key
    INNER JOIN dbo.core_permissions p
        ON p.permission_key = rp.permission_key
)
MERGE dbo.core_role_permissions AS tgt
USING src
    ON tgt.role_id = src.role_id
   AND tgt.permission_id = src.permission_id
WHEN NOT MATCHED BY TARGET THEN
    INSERT (role_id, permission_id, granted_at, granted_by_user_id)
    VALUES (src.role_id, src.permission_id, SYSUTCDATETIME(), NULL);
GO

/* ----------------------------------------------------------------------------
   Final verification outputs
---------------------------------------------------------------------------- */
SELECT
    role_id,
    role_key,
    role_name,
    access_level,
    is_active,
    sort_order
FROM dbo.core_roles
ORDER BY sort_order, role_id;
GO

SELECT
    permission_id,
    permission_key,
    module_key,
    action_key,
    permission_label,
    is_active,
    sort_order
FROM dbo.core_permissions
ORDER BY module_key, sort_order, permission_id;
GO

SELECT
    r.role_key,
    r.role_name,
    COUNT(rp.permission_id) AS permission_count
FROM dbo.core_roles r
LEFT JOIN dbo.core_role_permissions rp
    ON rp.role_id = r.role_id
GROUP BY r.role_key, r.role_name
ORDER BY permission_count DESC, r.role_key;
GO

