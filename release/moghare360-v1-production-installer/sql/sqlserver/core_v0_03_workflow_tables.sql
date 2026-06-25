/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_03_workflow_tables.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Version 0 WORKFLOW / TRANSACTION tables for internal staff access lifecycle.
Design reference: docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md

Business rules (enforced by application + schema intent):
  - No direct permission assignment to users (no core_user_permissions table).
  - Access changes must originate from approved requests, then apply step.
  - Every row in core_user_roles must trace to granted_by_request_id (APPLIED).
  - core_access_suspensions and core_access_restrictions override role grants
    at runtime (evaluated before role-permission union).

Creates:
  1. core_access_requests
  2. core_access_request_items
  3. core_access_approvals
  4. core_user_roles
  5. core_access_suspensions
  6. core_access_restrictions

Does NOT create:
  core_access_change_history, core_audit_logs (see core_v0_04_*)

Prerequisites: core_v0_01_create_database.sql, core_v0_02_master_tables.sql
Idempotent: skips CREATE TABLE when table already exists.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. core_access_requests
   Workflow header: submit → review → approve → apply.
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_requests
    (
        request_id              BIGINT IDENTITY(1, 1)   NOT NULL,
        request_number          NVARCHAR(30)            NOT NULL,
        request_type            NVARCHAR(40)            NOT NULL,
        request_state           NVARCHAR(30)            NOT NULL
            CONSTRAINT DF_core_access_requests_state DEFAULT (N'DRAFT'),
        priority                NVARCHAR(20)            NOT NULL
            CONSTRAINT DF_core_access_requests_priority DEFAULT (N'NORMAL'),
        subject_user_id         INT                     NOT NULL,
        requested_by_user_id    INT                     NOT NULL,
        justification           NVARCHAR(MAX)           NOT NULL,
        owner_acknowledged      BIT                     NOT NULL
            CONSTRAINT DF_core_access_requests_owner_ack DEFAULT (0),
        is_emergency            BIT                     NOT NULL
            CONSTRAINT DF_core_access_requests_emergency DEFAULT (0),
        migration_source        NVARCHAR(30)            NULL,
        submitted_at            DATETIME2(3)            NULL,
        decided_at              DATETIME2(3)            NULL,
        applied_at              DATETIME2(3)            NULL,
        applied_by_user_id      INT                     NULL,
        cancelled_at            DATETIME2(3)            NULL,
        cancelled_by_user_id    INT                     NULL,
        created_at              DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_requests_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2(3)            NULL,
        row_version             ROWVERSION              NOT NULL,
        CONSTRAINT PK_core_access_requests PRIMARY KEY CLUSTERED (request_id),
        CONSTRAINT FK_core_access_requests_subject
            FOREIGN KEY (subject_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_access_requests_requester
            FOREIGN KEY (requested_by_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_access_requests_applied_by
            FOREIGN KEY (applied_by_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_access_requests_cancelled_by
            FOREIGN KEY (cancelled_by_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT CK_core_access_requests_type CHECK (
            request_type IN (
                N'ONBOARDING', N'ROLE_GRANT', N'TEMPORARY_ROLE_GRANT',
                N'DEPARTMENT_ASSIGN', N'POSITION_ASSIGN', N'PROMOTION',
                N'ACCESS_UPGRADE', N'ACCESS_DOWNGRADE', N'SUSPENSION',
                N'ACCESS_RESTRICTION', N'OFFBOARDING', N'EMERGENCY'
            )
        ),
        CONSTRAINT CK_core_access_requests_state CHECK (
            request_state IN (
                N'DRAFT', N'SUBMITTED', N'UNDER_REVIEW', N'APPROVED',
                N'PARTIALLY_APPROVED', N'REJECTED', N'APPLIED', N'CANCELLED'
            )
        ),
        CONSTRAINT CK_core_access_requests_priority CHECK (
            priority IN (N'NORMAL', N'URGENT')
        )
    );

    CREATE UNIQUE INDEX UX_core_access_requests_request_number
        ON dbo.core_access_requests (request_number);

    CREATE INDEX IX_core_access_requests_state_type
        ON dbo.core_access_requests (request_state, request_type, submitted_at DESC);

    CREATE INDEX IX_core_access_requests_subject
        ON dbo.core_access_requests (subject_user_id, created_at DESC);

    CREATE INDEX IX_core_access_requests_requester
        ON dbo.core_access_requests (requested_by_user_id, created_at DESC);

    CREATE INDEX IX_core_access_requests_pending
        ON dbo.core_access_requests (request_state)
        WHERE request_state IN (
            N'SUBMITTED', N'UNDER_REVIEW', N'APPROVED', N'PARTIALLY_APPROVED'
        );

    PRINT N'Created table dbo.core_access_requests';
END
ELSE
    PRINT N'Table dbo.core_access_requests already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   2. core_access_request_items
   Line items: role grant/revoke, org changes, suspension/restriction params.
   No direct permission-grant item type in Version 0.
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_request_items', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_request_items
    (
        item_id             BIGINT IDENTITY(1, 1)   NOT NULL,
        request_id          BIGINT                  NOT NULL,
        item_type           NVARCHAR(40)            NOT NULL,
        role_id             INT                     NULL,
        department_id       INT                     NULL,
        position_id         INT                     NULL,
        module_key          NVARCHAR(80)            NULL,
        permission_key      NVARCHAR(120)           NULL,
        scope_type          NVARCHAR(20)            NULL,
        effective_from      DATETIME2(3)            NOT NULL,
        expires_at          DATETIME2(3)            NULL,
        is_temporary        BIT                     NOT NULL
            CONSTRAINT DF_core_access_request_items_temp DEFAULT (0),
        item_decision       NVARCHAR(20)            NOT NULL
            CONSTRAINT DF_core_access_request_items_decision DEFAULT (N'PENDING'),
        sort_order          INT                     NOT NULL
            CONSTRAINT DF_core_access_request_items_sort DEFAULT (1),
        created_at          DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_request_items_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_access_request_items PRIMARY KEY CLUSTERED (item_id),
        CONSTRAINT FK_core_access_request_items_request
            FOREIGN KEY (request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_access_request_items_role
            FOREIGN KEY (role_id) REFERENCES dbo.core_roles (role_id),
        CONSTRAINT FK_core_access_request_items_department
            FOREIGN KEY (department_id) REFERENCES dbo.core_departments (department_id),
        CONSTRAINT FK_core_access_request_items_position
            FOREIGN KEY (position_id) REFERENCES dbo.core_positions (position_id),
        CONSTRAINT CK_core_access_request_items_type CHECK (
            item_type IN (
                N'ROLE_GRANT', N'ROLE_REVOKE', N'DEPARTMENT_SET', N'POSITION_SET',
                N'SUSPENSION_CREATE', N'RESTRICTION_CREATE', N'LIFECYCLE_STATE_SET'
            )
        ),
        CONSTRAINT CK_core_access_request_items_decision CHECK (
            item_decision IN (N'PENDING', N'APPROVED', N'REJECTED')
        ),
        CONSTRAINT CK_core_access_request_items_scope CHECK (
            scope_type IS NULL
            OR scope_type IN (N'FULL', N'MODULE', N'LOGIN_ONLY')
        )
    );

    CREATE INDEX IX_core_access_request_items_request
        ON dbo.core_access_request_items (request_id, sort_order);

    CREATE INDEX IX_core_access_request_items_role
        ON dbo.core_access_request_items (role_id)
        WHERE role_id IS NOT NULL;

    CREATE INDEX IX_core_access_request_items_expiry
        ON dbo.core_access_request_items (expires_at)
        WHERE expires_at IS NOT NULL AND item_type = N'ROLE_GRANT';

    PRINT N'Created table dbo.core_access_request_items';
END
ELSE
    PRINT N'Table dbo.core_access_request_items already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   3. core_access_approvals
   Append-only approver decisions (approve / reject / partial).
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_approvals', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_approvals
    (
        approval_id         BIGINT IDENTITY(1, 1)   NOT NULL,
        request_id          BIGINT                  NOT NULL,
        approver_user_id    INT                     NOT NULL,
        approver_capacity   NVARCHAR(40)            NOT NULL,
        decision            NVARCHAR(20)            NOT NULL,
        comment             NVARCHAR(MAX)           NOT NULL,
        decided_at          DATETIME2(3)            NOT NULL,
        created_at          DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_approvals_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_access_approvals PRIMARY KEY CLUSTERED (approval_id),
        CONSTRAINT FK_core_access_approvals_request
            FOREIGN KEY (request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_access_approvals_approver
            FOREIGN KEY (approver_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT CK_core_access_approvals_capacity CHECK (
            approver_capacity IN (
                N'DEPARTMENT_MANAGER', N'SYSTEM_ADMIN',
                N'OPERATIONS_MANAGER', N'OWNER'
            )
        ),
        CONSTRAINT CK_core_access_approvals_decision CHECK (
            decision IN (N'APPROVED', N'REJECTED', N'PARTIAL')
        )
    );

    CREATE INDEX IX_core_access_approvals_request
        ON dbo.core_access_approvals (request_id, decided_at);

    CREATE INDEX IX_core_access_approvals_approver_pending
        ON dbo.core_access_approvals (approver_user_id, decision)
        INCLUDE (request_id);

    CREATE UNIQUE INDEX UX_core_access_approvals_unique_capacity
        ON dbo.core_access_approvals (request_id, approver_capacity);

    PRINT N'Created table dbo.core_access_approvals';
END
ELSE
    PRINT N'Table dbo.core_access_approvals already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   4. core_user_roles
   Effective staff role assignments only — each grant must reference APPLIED request.
   Permissions resolve via core_roles → core_role_permissions (not stored here).
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_user_roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_user_roles
    (
        user_role_id            BIGINT IDENTITY(1, 1)   NOT NULL,
        user_id                 INT                     NOT NULL,
        role_id                 INT                     NOT NULL,
        granted_by_request_id   BIGINT                  NOT NULL,
        effective_from          DATETIME2(3)            NOT NULL,
        expires_at              DATETIME2(3)            NULL,
        revoked_at              DATETIME2(3)            NULL,
        revoked_by_request_id   BIGINT                  NULL,
        is_temporary            BIT                     NOT NULL
            CONSTRAINT DF_core_user_roles_temp DEFAULT (0),
        created_at              DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_user_roles_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_user_roles PRIMARY KEY CLUSTERED (user_role_id),
        CONSTRAINT FK_core_user_roles_user
            FOREIGN KEY (user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_user_roles_role
            FOREIGN KEY (role_id) REFERENCES dbo.core_roles (role_id),
        CONSTRAINT FK_core_user_roles_granted_by
            FOREIGN KEY (granted_by_request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_user_roles_revoked_by
            FOREIGN KEY (revoked_by_request_id) REFERENCES dbo.core_access_requests (request_id)
    );

    CREATE INDEX IX_core_user_roles_user_active
        ON dbo.core_user_roles (user_id, effective_from, expires_at)
        WHERE revoked_at IS NULL;

    CREATE INDEX IX_core_user_roles_role
        ON dbo.core_user_roles (role_id)
        INCLUDE (user_id);

    CREATE INDEX IX_core_user_roles_expiry
        ON dbo.core_user_roles (expires_at)
        WHERE revoked_at IS NULL AND expires_at IS NOT NULL;

    CREATE UNIQUE INDEX UX_core_user_roles_active_unique
        ON dbo.core_user_roles (user_id, role_id)
        WHERE revoked_at IS NULL;

    PRINT N'Created table dbo.core_user_roles';
END
ELSE
    PRINT N'Table dbo.core_user_roles already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   5. core_access_suspensions
   Denial overlay — evaluated BEFORE role permissions at runtime.
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_suspensions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_suspensions
    (
        suspension_id           BIGINT IDENTITY(1, 1)   NOT NULL,
        user_id                 INT                     NOT NULL,
        request_id              BIGINT                  NOT NULL,
        scope_type              NVARCHAR(20)            NOT NULL,
        module_key              NVARCHAR(80)            NULL,
        reason                  NVARCHAR(MAX)           NOT NULL,
        starts_at               DATETIME2(3)            NOT NULL,
        ends_at                 DATETIME2(3)            NULL,
        lifted_at               DATETIME2(3)            NULL,
        lifted_by_request_id    BIGINT                  NULL,
        created_at              DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_suspensions_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_access_suspensions PRIMARY KEY CLUSTERED (suspension_id),
        CONSTRAINT FK_core_access_suspensions_user
            FOREIGN KEY (user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_access_suspensions_request
            FOREIGN KEY (request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_access_suspensions_lifted_by
            FOREIGN KEY (lifted_by_request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT CK_core_access_suspensions_scope CHECK (
            scope_type IN (N'FULL', N'MODULE', N'LOGIN_ONLY')
        )
    );

    CREATE INDEX IX_core_access_suspensions_user_active
        ON dbo.core_access_suspensions (user_id, starts_at, ends_at)
        WHERE lifted_at IS NULL;

    CREATE INDEX IX_core_access_suspensions_scope
        ON dbo.core_access_suspensions (scope_type, module_key);

    PRINT N'Created table dbo.core_access_suspensions';
END
ELSE
    PRINT N'Table dbo.core_access_suspensions already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   6. core_access_restrictions
   Targeted deny overlay (module or permission) — overrides roles for scoped access.
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_restrictions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_restrictions
    (
        restriction_id          BIGINT IDENTITY(1, 1)   NOT NULL,
        user_id                 INT                     NOT NULL,
        request_id              BIGINT                  NOT NULL,
        restriction_type        NVARCHAR(20)            NOT NULL,
        module_key              NVARCHAR(80)            NULL,
        permission_key          NVARCHAR(120)           NULL,
        incident_note           NVARCHAR(MAX)           NOT NULL,
        starts_at               DATETIME2(3)            NOT NULL,
        ends_at                 DATETIME2(3)            NULL,
        lifted_at               DATETIME2(3)            NULL,
        lifted_by_request_id    BIGINT                  NULL,
        created_at              DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_restrictions_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_access_restrictions PRIMARY KEY CLUSTERED (restriction_id),
        CONSTRAINT FK_core_access_restrictions_user
            FOREIGN KEY (user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_access_restrictions_request
            FOREIGN KEY (request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_access_restrictions_lifted_by
            FOREIGN KEY (lifted_by_request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_access_restrictions_permission
            FOREIGN KEY (permission_key) REFERENCES dbo.core_permissions (permission_key),
        CONSTRAINT CK_core_access_restrictions_type CHECK (
            restriction_type IN (N'MODULE', N'PERMISSION')
        )
    );

    CREATE INDEX IX_core_access_restrictions_user_active
        ON dbo.core_access_restrictions (user_id)
        WHERE lifted_at IS NULL;

    CREATE INDEX IX_core_access_restrictions_module
        ON dbo.core_access_restrictions (module_key)
        WHERE lifted_at IS NULL;

    CREATE INDEX IX_core_access_restrictions_permission
        ON dbo.core_access_restrictions (permission_key)
        WHERE lifted_at IS NULL;

    PRINT N'Created table dbo.core_access_restrictions';
END
ELSE
    PRINT N'Table dbo.core_access_restrictions already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   Verification
---------------------------------------------------------------------------- */
SELECT
    t.name          AS table_name,
    t.create_date   AS create_date,
    t.modify_date   AS modify_date
FROM sys.tables AS t
WHERE SCHEMA_NAME(t.schema_id) = N'dbo'
  AND (
        t.name LIKE N'core_access[_]%'
        OR t.name = N'core_user_roles'
      )
ORDER BY t.name;
GO
