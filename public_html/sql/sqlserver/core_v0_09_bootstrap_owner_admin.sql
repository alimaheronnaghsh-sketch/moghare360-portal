/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_09_bootstrap_owner_admin.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Controlled bootstrap for approved Platform Owner (single user only).

Approval reference:
  - docs/V0_BOOTSTRAP_APPROVAL_CHECKLIST.md
  - docs/V0_BOOTSTRAP_ADMIN_USER_STRATEGY.md
  - docs/PRODUCT_ARCHITECTURE_DECISION.md

Approved by: MahinParadigmCo.
Approval date: 2026-06-16

!!! CRITICAL PASSWORD WARNING !!!
  The password_hash value below is a PLACEHOLDER:
    CHANGE_ME_SECURE_PASSWORD_HASH
  You MUST replace it with a real bcrypt/argon hash in SSMS BEFORE execution.
  Do NOT commit a real password or real hash to GitHub.

Creates/updates:
  - core_users (user_id = 10001)
  - core_staff_profiles (minimal bootstrap profile)
  - core_access_requests (synthetic EMERGENCY / migration_source = BOOTSTRAP)
  - core_user_roles (owner + system_admin — temporary dual role for setup)
  - core_access_change_history
  - core_audit_logs

Does NOT:
  - Create normal staff, customer users, or legacy migration
  - Seed CUSTOMER access
  - Create any user other than user_id = 10001

Idempotent: safe to re-run; will not duplicate active roles or bootstrap audit rows.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;
SET XACT_ABORT ON;

DECLARE
    @UserId                 INT             = 10001,
    @Username               NVARCHAR(80)    = N'mahin.paradigm.owner',
    @FullName               NVARCHAR(160)   = N'MahinParadigmCo.',
    @Mobile                 NVARCHAR(30)    = N'+989131173340',
    @Email                  NVARCHAR(255)   = N'amiralimaher@yahoo.com',
    @PasswordHashPlaceholder NVARCHAR(255)  = N'CHANGE_ME_SECURE_PASSWORD_HASH',
    @IsSystemOwner          BIT             = 1,
    @LifecycleState         NVARCHAR(30)    = N'ACTIVE',
    @IsLoginEnabled         BIT             = 1,
    @ApprovedBy             NVARCHAR(160)   = N'MahinParadigmCo.',
    @ApprovalDate           DATE            = '2026-06-16',
    @BootstrapRequestNumber NVARCHAR(30)    = N'BOOTSTRAP-10001',
    @BootstrapJustification NVARCHAR(MAX)   = N'Bootstrap Platform Owner for MOGHARE360 ERP V0. Approved by MahinParadigmCo. on 2026-06-16. All platform ownership and system control assigned to MahinParadigmCo. Moghareh is the first pilot tenant and must not be confused with platform ownership.',
    @OwnerRoleId            INT,
    @SystemAdminRoleId      INT,
    @BootstrapRequestId     BIGINT,
    @Now                    DATETIME2(3)    = SYSUTCDATETIME();

/* ----------------------------------------------------------------------------
   1) Verify required roles exist
---------------------------------------------------------------------------- */
SELECT @OwnerRoleId = role_id
FROM dbo.core_roles
WHERE role_key = N'owner' AND is_active = 1;

SELECT @SystemAdminRoleId = role_id
FROM dbo.core_roles
WHERE role_key = N'system_admin' AND is_active = 1;

IF @OwnerRoleId IS NULL OR @SystemAdminRoleId IS NULL
BEGIN
    THROW 51001, N'Required roles missing: owner and/or system_admin must exist and be active.', 1;
END;

/* ----------------------------------------------------------------------------
   2) Verify user_id / username do not conflict with a different user
---------------------------------------------------------------------------- */
IF EXISTS (
    SELECT 1
    FROM dbo.core_users
    WHERE user_id = @UserId
      AND username <> @Username
)
BEGIN
    THROW 51002, N'user_id 10001 already exists with a different username.', 1;
END;

IF EXISTS (
    SELECT 1
    FROM dbo.core_users
    WHERE username = @Username
      AND user_id <> @UserId
)
BEGIN
    THROW 51003, N'username mahin.paradigm.owner already exists for a different user_id.', 1;
END;

IF @PasswordHashPlaceholder = N'CHANGE_ME_SECURE_PASSWORD_HASH'
BEGIN
    PRINT N'WARNING: password_hash is still the placeholder. Replace CHANGE_ME_SECURE_PASSWORD_HASH before production use.';
END;

BEGIN TRANSACTION;

/* ----------------------------------------------------------------------------
   3) Insert or update core_users
---------------------------------------------------------------------------- */
IF EXISTS (SELECT 1 FROM dbo.core_users WHERE user_id = @UserId)
BEGIN
    UPDATE dbo.core_users
    SET
        username         = @Username,
        full_name        = @FullName,
        email            = @Email,
        mobile           = @Mobile,
        lifecycle_state  = @LifecycleState,
        is_system_owner  = @IsSystemOwner,
        is_login_enabled = @IsLoginEnabled,
        password_hash    = CASE
                               WHEN password_hash = N'CHANGE_ME_SECURE_PASSWORD_HASH'
                                    OR LTRIM(RTRIM(password_hash)) = N''
                               THEN @PasswordHashPlaceholder
                               ELSE password_hash
                           END,
        updated_at       = @Now,
        updated_by_user_id = @UserId
    WHERE user_id = @UserId;
END
ELSE
BEGIN
    INSERT INTO dbo.core_users (
        user_id,
        username,
        password_hash,
        full_name,
        email,
        mobile,
        lifecycle_state,
        is_system_owner,
        is_login_enabled,
        created_at,
        created_by_user_id
    )
    VALUES (
        @UserId,
        @Username,
        @PasswordHashPlaceholder,
        @FullName,
        @Email,
        @Mobile,
        @LifecycleState,
        @IsSystemOwner,
        @IsLoginEnabled,
        @Now,
        @UserId
    );
END;

/* ----------------------------------------------------------------------------
   4) Insert or update core_staff_profiles (minimal bootstrap profile)
---------------------------------------------------------------------------- */
IF EXISTS (SELECT 1 FROM dbo.core_staff_profiles WHERE user_id = @UserId)
BEGIN
    UPDATE dbo.core_staff_profiles
    SET
        notes      = N'Bootstrap Platform Owner profile. Not Moghareh Tenant Owner.',
        updated_at = @Now
    WHERE user_id = @UserId;
END
ELSE
BEGIN
    INSERT INTO dbo.core_staff_profiles (
        user_id,
        department_id,
        position_id,
        notes,
        created_at
    )
    VALUES (
        @UserId,
        NULL,
        NULL,
        N'Bootstrap Platform Owner profile. Not Moghareh Tenant Owner.',
        @Now
    );
END;

/* ----------------------------------------------------------------------------
   5) Synthetic APPLIED bootstrap request (EMERGENCY + migration_source BOOTSTRAP)
---------------------------------------------------------------------------- */
SELECT @BootstrapRequestId = request_id
FROM dbo.core_access_requests
WHERE request_number = @BootstrapRequestNumber;

IF @BootstrapRequestId IS NULL
BEGIN
    INSERT INTO dbo.core_access_requests (
        request_number,
        request_type,
        request_state,
        priority,
        subject_user_id,
        requested_by_user_id,
        justification,
        owner_acknowledged,
        is_emergency,
        migration_source,
        submitted_at,
        decided_at,
        applied_at,
        applied_by_user_id,
        created_at
    )
    VALUES (
        @BootstrapRequestNumber,
        N'EMERGENCY',
        N'APPLIED',
        N'NORMAL',
        @UserId,
        @UserId,
        @BootstrapJustification,
        1,
        1,
        N'BOOTSTRAP',
        @Now,
        @Now,
        @Now,
        @UserId,
        @Now
    );

    SET @BootstrapRequestId = SCOPE_IDENTITY();
END
ELSE
BEGIN
    UPDATE dbo.core_access_requests
    SET
        request_type         = N'EMERGENCY',
        request_state        = N'APPLIED',
        subject_user_id      = @UserId,
        requested_by_user_id = @UserId,
        justification        = @BootstrapJustification,
        owner_acknowledged   = 1,
        is_emergency         = 1,
        migration_source     = N'BOOTSTRAP',
        applied_at           = COALESCE(applied_at, @Now),
        applied_by_user_id   = COALESCE(applied_by_user_id, @UserId),
        updated_at           = @Now
    WHERE request_id = @BootstrapRequestId;
END;

/* ----------------------------------------------------------------------------
   6) Assign owner + system_admin roles (bootstrap exception)
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM dbo.core_user_roles
    WHERE user_id = @UserId
      AND role_id = @OwnerRoleId
      AND revoked_at IS NULL
)
BEGIN
    INSERT INTO dbo.core_user_roles (
        user_id,
        role_id,
        granted_by_request_id,
        effective_from,
        is_temporary,
        created_at
    )
    VALUES (
        @UserId,
        @OwnerRoleId,
        @BootstrapRequestId,
        @Now,
        0,
        @Now
    );
END;

IF NOT EXISTS (
    SELECT 1
    FROM dbo.core_user_roles
    WHERE user_id = @UserId
      AND role_id = @SystemAdminRoleId
      AND revoked_at IS NULL
)
BEGIN
    INSERT INTO dbo.core_user_roles (
        user_id,
        role_id,
        granted_by_request_id,
        effective_from,
        is_temporary,
        created_at
    )
    VALUES (
        @UserId,
        @SystemAdminRoleId,
        @BootstrapRequestId,
        @Now,
        1,
        @Now
    );
END;

/* ----------------------------------------------------------------------------
   7) core_access_change_history (append-only; skip if bootstrap rows exist)
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM dbo.core_access_change_history
    WHERE request_id = @BootstrapRequestId
      AND change_type = N'BOOTSTRAP_USER_UPSERT'
)
BEGIN
    INSERT INTO dbo.core_access_change_history (
        user_id,
        request_id,
        change_type,
        entity_type,
        entity_id,
        before_json,
        after_json,
        changed_by_user_id,
        changed_at
    )
    VALUES (
        @UserId,
        @BootstrapRequestId,
        N'BOOTSTRAP_USER_UPSERT',
        N'CORE_USER',
        @UserId,
        NULL,
        (
            SELECT
                @UserId AS user_id,
                @Username AS username,
                @FullName AS full_name,
                @Mobile AS mobile,
                @Email AS email,
                @LifecycleState AS lifecycle_state,
                @IsSystemOwner AS is_system_owner,
                @IsLoginEnabled AS is_login_enabled
            FOR JSON PATH, WITHOUT_ARRAY_WRAPPER
        ),
        @UserId,
        @Now
    );
END;

IF NOT EXISTS (
    SELECT 1
    FROM dbo.core_access_change_history
    WHERE request_id = @BootstrapRequestId
      AND change_type = N'ROLE_GRANTED'
      AND entity_type = N'USER_ROLE'
      AND after_json LIKE N'%"role_key":"owner"%'
)
BEGIN
    INSERT INTO dbo.core_access_change_history (
        user_id,
        request_id,
        change_type,
        entity_type,
        before_json,
        after_json,
        changed_by_user_id,
        changed_at
    )
    VALUES (
        @UserId,
        @BootstrapRequestId,
        N'ROLE_GRANTED',
        N'USER_ROLE',
        NULL,
        (
            SELECT N'owner' AS role_key, @OwnerRoleId AS role_id, 0 AS is_temporary
            FOR JSON PATH, WITHOUT_ARRAY_WRAPPER
        ),
        @UserId,
        @Now
    );
END;

IF NOT EXISTS (
    SELECT 1
    FROM dbo.core_access_change_history
    WHERE request_id = @BootstrapRequestId
      AND change_type = N'ROLE_GRANTED'
      AND entity_type = N'USER_ROLE'
      AND after_json LIKE N'%"role_key":"system_admin"%'
)
BEGIN
    INSERT INTO dbo.core_access_change_history (
        user_id,
        request_id,
        change_type,
        entity_type,
        before_json,
        after_json,
        changed_by_user_id,
        changed_at
    )
    VALUES (
        @UserId,
        @BootstrapRequestId,
        N'ROLE_GRANTED',
        N'USER_ROLE',
        NULL,
        (
            SELECT N'system_admin' AS role_key, @SystemAdminRoleId AS role_id, 1 AS is_temporary
            FOR JSON PATH, WITHOUT_ARRAY_WRAPPER
        ),
        @UserId,
        @Now
    );
END;

/* ----------------------------------------------------------------------------
   8) core_audit_logs (append-only; skip duplicate bootstrap marker)
---------------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1
    FROM dbo.core_audit_logs
    WHERE action = N'BOOTSTRAP_PLATFORM_OWNER_APPLIED'
      AND subject_user_id = @UserId
      AND request_id = @BootstrapRequestId
)
BEGIN
    INSERT INTO dbo.core_audit_logs (
        actor_user_id,
        action,
        entity_type,
        entity_id,
        request_id,
        subject_user_id,
        details_json,
        is_emergency,
        created_at
    )
    VALUES (
        @UserId,
        N'BOOTSTRAP_PLATFORM_OWNER_APPLIED',
        N'CORE_USER',
        @UserId,
        @BootstrapRequestId,
        @UserId,
        (
            SELECT
                @ApprovedBy AS approved_by,
                CONVERT(NVARCHAR(10), @ApprovalDate, 23) AS approval_date,
                @BootstrapRequestNumber AS request_number,
                N'BOOTSTRAP' AS migration_source,
                N'owner,system_admin' AS roles_assigned,
                N'Platform Owner only; Moghareh tenant owner is separate concept' AS notes
            FOR JSON PATH, WITHOUT_ARRAY_WRAPPER
        ),
        1,
        @Now
    );
END;

COMMIT TRANSACTION;

PRINT N'Bootstrap Platform Owner applied for user_id = 10001.';
GO

/* ----------------------------------------------------------------------------
   Final verification
---------------------------------------------------------------------------- */
USE [moghare360_ERP];
GO

SELECT
    user_id,
    username,
    full_name,
    mobile,
    email,
    lifecycle_state,
    is_system_owner,
    is_login_enabled,
    created_at,
    updated_at
FROM dbo.core_users
WHERE user_id = 10001;
GO

SELECT
    u.user_id,
    r.role_key,
    r.role_name,
    ur.is_temporary,
    ur.effective_from,
    ur.granted_by_request_id,
    ur.revoked_at
FROM dbo.core_user_roles AS ur
INNER JOIN dbo.core_roles AS r
    ON r.role_id = ur.role_id
INNER JOIN dbo.core_users AS u
    ON u.user_id = ur.user_id
WHERE u.user_id = 10001
  AND ur.revoked_at IS NULL
ORDER BY r.role_key;
GO

SELECT
    request_id,
    request_number,
    request_type,
    request_state,
    migration_source,
    is_emergency,
    subject_user_id,
    applied_at,
    justification
FROM dbo.core_access_requests
WHERE request_number = N'BOOTSTRAP-10001';
GO

SELECT
    audit_id,
    action,
    entity_type,
    entity_id,
    request_id,
    subject_user_id,
    is_emergency,
    created_at,
    details_json
FROM dbo.core_audit_logs
WHERE subject_user_id = 10001
   OR request_id IN (
        SELECT request_id
        FROM dbo.core_access_requests
        WHERE request_number = N'BOOTSTRAP-10001'
   )
ORDER BY created_at DESC;
GO

SELECT
    history_id,
    change_type,
    entity_type,
    request_id,
    changed_at
FROM dbo.core_access_change_history
WHERE user_id = 10001
ORDER BY changed_at DESC;
GO
