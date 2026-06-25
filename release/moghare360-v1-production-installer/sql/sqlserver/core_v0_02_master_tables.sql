/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_02_master_tables.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Version 0 MASTER DATA foundation only (internal staff).
Design reference: docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md

Creates:
  1. core_users
  2. core_departments
  3. core_roles
  4. core_permissions
  5. core_positions
  6. core_role_permissions
  7. core_staff_profiles

Does NOT create workflow / transaction tables:
  core_access_requests, core_access_request_items, core_access_approvals,
  core_user_roles, core_access_suspensions, core_access_restrictions,
  core_access_change_history, core_audit_logs

Prerequisites: core_v0_01_create_database.sql
Idempotent: skips CREATE TABLE when table already exists.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. core_users
   user_id is NOT IDENTITY — preserves legacy staff_users.id on migration.
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_users
    (
        user_id                 INT             NOT NULL,
        username                NVARCHAR(80)    NOT NULL,
        password_hash           NVARCHAR(255)   NOT NULL,
        full_name               NVARCHAR(160)   NOT NULL,
        email                   NVARCHAR(255)   NULL,
        mobile                  NVARCHAR(30)    NULL,
        lifecycle_state         NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_core_users_lifecycle_state DEFAULT (N'DRAFT'),
        is_system_owner         BIT             NOT NULL
            CONSTRAINT DF_core_users_is_system_owner DEFAULT (0),
        is_login_enabled        BIT             NOT NULL
            CONSTRAINT DF_core_users_is_login_enabled DEFAULT (0),
        legacy_staff_user_id    INT             NULL,
        last_login_at           DATETIME2(3)    NULL,
        created_at              DATETIME2(3)    NOT NULL
            CONSTRAINT DF_core_users_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)    NULL,
        created_by_user_id      INT             NULL,
        updated_by_user_id      INT             NULL,
        row_version             ROWVERSION      NOT NULL,
        CONSTRAINT PK_core_users PRIMARY KEY CLUSTERED (user_id),
        CONSTRAINT CK_core_users_lifecycle_state CHECK (
            lifecycle_state IN (
                N'DRAFT', N'PENDING_ONBOARDING', N'ACTIVE', N'PROMOTED',
                N'ACCESS_CHANGED', N'SUSPENDED', N'RESTRICTED',
                N'OFFBOARDING', N'INACTIVE'
            )
        )
    );

    ALTER TABLE dbo.core_users
        ADD CONSTRAINT FK_core_users_created_by
            FOREIGN KEY (created_by_user_id) REFERENCES dbo.core_users (user_id);

    ALTER TABLE dbo.core_users
        ADD CONSTRAINT FK_core_users_updated_by
            FOREIGN KEY (updated_by_user_id) REFERENCES dbo.core_users (user_id);

    CREATE UNIQUE INDEX UX_core_users_username
        ON dbo.core_users (username);

    CREATE INDEX IX_core_users_lifecycle_state
        ON dbo.core_users (lifecycle_state);

    CREATE UNIQUE INDEX UX_core_users_legacy_staff_user_id
        ON dbo.core_users (legacy_staff_user_id)
        WHERE legacy_staff_user_id IS NOT NULL;

    CREATE INDEX IX_core_users_is_login_enabled
        ON dbo.core_users (is_login_enabled)
        INCLUDE (lifecycle_state);

    PRINT N'Created table dbo.core_users';
END
ELSE
    PRINT N'Table dbo.core_users already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   2. core_roles
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_roles
    (
        role_id         INT IDENTITY(1, 1)  NOT NULL,
        role_key        NVARCHAR(80)        NOT NULL,
        role_name       NVARCHAR(120)       NOT NULL,
        access_level    NVARCHAR(30)        NOT NULL,
        description     NVARCHAR(500)       NULL,
        is_active       BIT                 NOT NULL
            CONSTRAINT DF_core_roles_is_active DEFAULT (1),
        sort_order      INT                 NOT NULL
            CONSTRAINT DF_core_roles_sort_order DEFAULT (100),
        created_at      DATETIME2(3)        NOT NULL
            CONSTRAINT DF_core_roles_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at      DATETIME2(3)        NULL,
        CONSTRAINT PK_core_roles PRIMARY KEY CLUSTERED (role_id),
        CONSTRAINT CK_core_roles_access_level CHECK (
            access_level IN (
                N'OWNER', N'GENERAL_MANAGER', N'OPERATIONS_MANAGER',
                N'DEPARTMENT_MANAGER', N'STAFF', N'READ_ONLY', N'CUSTOMER'
            )
        )
    );

    CREATE UNIQUE INDEX UX_core_roles_role_key
        ON dbo.core_roles (role_key);

    CREATE INDEX IX_core_roles_access_level
        ON dbo.core_roles (access_level, is_active);

    PRINT N'Created table dbo.core_roles';
END
ELSE
    PRINT N'Table dbo.core_roles already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   3. core_permissions
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_permissions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_permissions
    (
        permission_id       INT IDENTITY(1, 1)  NOT NULL,
        permission_key      NVARCHAR(120)       NOT NULL,
        module_key          NVARCHAR(80)        NOT NULL,
        action_key          NVARCHAR(80)        NOT NULL,
        permission_label    NVARCHAR(180)       NOT NULL,
        sort_order          INT                 NOT NULL
            CONSTRAINT DF_core_permissions_sort_order DEFAULT (100),
        is_active           BIT                 NOT NULL
            CONSTRAINT DF_core_permissions_is_active DEFAULT (1),
        created_at          DATETIME2(3)        NOT NULL
            CONSTRAINT DF_core_permissions_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_permissions PRIMARY KEY CLUSTERED (permission_id)
    );

    CREATE UNIQUE INDEX UX_core_permissions_permission_key
        ON dbo.core_permissions (permission_key);

    CREATE INDEX IX_core_permissions_module
        ON dbo.core_permissions (module_key, sort_order, is_active);

    PRINT N'Created table dbo.core_permissions';
END
ELSE
    PRINT N'Table dbo.core_permissions already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   4. core_departments
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_departments', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_departments
    (
        department_id           INT IDENTITY(1, 1)  NOT NULL,
        dept_key                NVARCHAR(50)        NOT NULL,
        dept_name               NVARCHAR(120)       NOT NULL,
        parent_department_id    INT                 NULL,
        manager_user_id         INT                 NULL,
        is_active               BIT                 NOT NULL
            CONSTRAINT DF_core_departments_is_active DEFAULT (1),
        sort_order              INT                 NOT NULL
            CONSTRAINT DF_core_departments_sort_order DEFAULT (100),
        created_at              DATETIME2(3)        NOT NULL
            CONSTRAINT DF_core_departments_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)        NULL,
        CONSTRAINT PK_core_departments PRIMARY KEY CLUSTERED (department_id),
        CONSTRAINT FK_core_departments_parent
            FOREIGN KEY (parent_department_id)
            REFERENCES dbo.core_departments (department_id),
        CONSTRAINT FK_core_departments_manager
            FOREIGN KEY (manager_user_id)
            REFERENCES dbo.core_users (user_id)
    );

    CREATE UNIQUE INDEX UX_core_departments_dept_key
        ON dbo.core_departments (dept_key);

    CREATE INDEX IX_core_departments_parent
        ON dbo.core_departments (parent_department_id);

    CREATE INDEX IX_core_departments_manager
        ON dbo.core_departments (manager_user_id);

    CREATE INDEX IX_core_departments_active_sort
        ON dbo.core_departments (is_active, sort_order);

    PRINT N'Created table dbo.core_departments';
END
ELSE
    PRINT N'Table dbo.core_departments already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   5. core_role_permissions
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_role_permissions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_role_permissions
    (
        role_id             INT             NOT NULL,
        permission_id       INT             NOT NULL,
        granted_at          DATETIME2(3)    NOT NULL
            CONSTRAINT DF_core_role_permissions_granted_at DEFAULT (SYSUTCDATETIME()),
        granted_by_user_id  INT             NULL,
        CONSTRAINT PK_core_role_permissions PRIMARY KEY CLUSTERED (role_id, permission_id),
        CONSTRAINT FK_core_role_permissions_role
            FOREIGN KEY (role_id) REFERENCES dbo.core_roles (role_id),
        CONSTRAINT FK_core_role_permissions_permission
            FOREIGN KEY (permission_id) REFERENCES dbo.core_permissions (permission_id),
        CONSTRAINT FK_core_role_permissions_granted_by
            FOREIGN KEY (granted_by_user_id) REFERENCES dbo.core_users (user_id)
    );

    CREATE INDEX IX_core_role_permissions_permission
        ON dbo.core_role_permissions (permission_id)
        INCLUDE (role_id);

    PRINT N'Created table dbo.core_role_permissions';
END
ELSE
    PRINT N'Table dbo.core_role_permissions already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   6. core_positions
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_positions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_positions
    (
        position_id         INT IDENTITY(1, 1)  NOT NULL,
        department_id       INT                 NOT NULL,
        position_key        NVARCHAR(50)        NOT NULL,
        position_name       NVARCHAR(120)       NOT NULL,
        suggested_role_id   INT                 NULL,
        is_active           BIT                 NOT NULL
            CONSTRAINT DF_core_positions_is_active DEFAULT (1),
        sort_order          INT                 NOT NULL
            CONSTRAINT DF_core_positions_sort_order DEFAULT (100),
        created_at          DATETIME2(3)        NOT NULL
            CONSTRAINT DF_core_positions_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at          DATETIME2(3)        NULL,
        CONSTRAINT PK_core_positions PRIMARY KEY CLUSTERED (position_id),
        CONSTRAINT FK_core_positions_department
            FOREIGN KEY (department_id) REFERENCES dbo.core_departments (department_id),
        CONSTRAINT FK_core_positions_suggested_role
            FOREIGN KEY (suggested_role_id) REFERENCES dbo.core_roles (role_id)
    );

    CREATE UNIQUE INDEX UX_core_positions_dept_key
        ON dbo.core_positions (department_id, position_key);

    CREATE INDEX IX_core_positions_department_active
        ON dbo.core_positions (department_id, is_active, sort_order);

    PRINT N'Created table dbo.core_positions';
END
ELSE
    PRINT N'Table dbo.core_positions already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   7. core_staff_profiles
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_staff_profiles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_staff_profiles
    (
        profile_id          INT IDENTITY(1, 1)  NOT NULL,
        user_id             INT                 NOT NULL,
        department_id       INT                 NULL,
        position_id         INT                 NULL,
        employee_code       NVARCHAR(40)        NULL,
        hire_date           DATE                NULL,
        exit_date           DATE                NULL,
        profile_photo_path  NVARCHAR(500)       NULL,
        notes               NVARCHAR(MAX)       NULL,
        created_at          DATETIME2(3)        NOT NULL
            CONSTRAINT DF_core_staff_profiles_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at          DATETIME2(3)        NULL,
        CONSTRAINT PK_core_staff_profiles PRIMARY KEY CLUSTERED (profile_id),
        CONSTRAINT FK_core_staff_profiles_user
            FOREIGN KEY (user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_staff_profiles_department
            FOREIGN KEY (department_id) REFERENCES dbo.core_departments (department_id),
        CONSTRAINT FK_core_staff_profiles_position
            FOREIGN KEY (position_id) REFERENCES dbo.core_positions (position_id)
    );

    CREATE UNIQUE INDEX UX_core_staff_profiles_user_id
        ON dbo.core_staff_profiles (user_id);

    CREATE UNIQUE INDEX UX_core_staff_profiles_employee_code
        ON dbo.core_staff_profiles (employee_code)
        WHERE employee_code IS NOT NULL;

    CREATE INDEX IX_core_staff_profiles_department
        ON dbo.core_staff_profiles (department_id)
        INCLUDE (user_id, position_id);

    PRINT N'Created table dbo.core_staff_profiles';
END
ELSE
    PRINT N'Table dbo.core_staff_profiles already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   Verification
---------------------------------------------------------------------------- */
SELECT
    t.name          AS table_name,
    t.create_date   AS create_date,
    t.modify_date   AS modify_date
FROM sys.tables AS t
WHERE t.name LIKE N'core[_]%'
  AND SCHEMA_NAME(t.schema_id) = N'dbo'
ORDER BY t.name;
GO
