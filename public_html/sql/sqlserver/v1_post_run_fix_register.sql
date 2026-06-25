/*
================================================================================
MOGHARE360 V1 — Post-Run Fix Register + Production Signoff
Script: v1_post_run_fix_register.sql
Idempotent. No DROP. No real customer data.
================================================================================
*/

SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_v1_production_run_signoff', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_v1_production_run_signoff
    (
        signoff_id                  BIGINT          NOT NULL IDENTITY(1, 1),
        signoff_version             NVARCHAR(20)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_version DEFAULT (N'V1'),
        installer_status            NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_installer DEFAULT (N'PENDING'),
        auto_deploy_status          NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_deploy DEFAULT (N'PENDING'),
        saas_status                 NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_saas DEFAULT (N'PENDING'),
        api_status                  NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_api DEFAULT (N'PENDING'),
        mirror_pwa_status           NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_mirror DEFAULT (N'PENDING'),
        ssl_configured              BIT             NOT NULL
            CONSTRAINT DF_erp_v1_signoff_ssl DEFAULT (0),
        storage_configured          BIT             NOT NULL
            CONSTRAINT DF_erp_v1_signoff_storage DEFAULT (0),
        controlled_scenario_status  NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_scenario DEFAULT (N'PENDING'),
        owner_signoff_status        NVARCHAR(40)    NOT NULL
            CONSTRAINT DF_erp_v1_signoff_owner DEFAULT (N'PENDING'),
        owner_signoff_by            NVARCHAR(200)   NULL,
        owner_signoff_at            DATETIME2       NULL,
        signoff_note                NVARCHAR(2000)  NULL,
        created_at                  DATETIME2       NOT NULL
            CONSTRAINT DF_erp_v1_signoff_created DEFAULT (SYSUTCDATETIME()),
        updated_at                  DATETIME2       NULL,
        CONSTRAINT PK_erp_v1_production_run_signoff PRIMARY KEY CLUSTERED (signoff_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_v1_post_run_fix_register', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_v1_post_run_fix_register
    (
        item_id             BIGINT          NOT NULL IDENTITY(1, 1),
        category            NVARCHAR(30)    NOT NULL,
        severity            NVARCHAR(20)    NOT NULL,
        source              NVARCHAR(30)    NOT NULL,
        description         NVARCHAR(2000)  NOT NULL,
        affected_module     NVARCHAR(200)   NOT NULL,
        owner_decision      NVARCHAR(500)   NULL,
        status              NVARCHAR(30)    NOT NULL
            CONSTRAINT DF_erp_v1_fix_status DEFAULT (N'OPEN'),
        created_at          DATETIME2       NOT NULL
            CONSTRAINT DF_erp_v1_fix_created DEFAULT (SYSUTCDATETIME()),
        closed_at           DATETIME2       NULL,
        CONSTRAINT PK_erp_v1_post_run_fix_register PRIMARY KEY CLUSTERED (item_id),
        CONSTRAINT CK_erp_v1_fix_category CHECK (
            category IN (N'BUG', N'FIX', N'UI', N'TRAINING', N'DATA', N'SECURITY', N'V2_BACKLOG')
        ),
        CONSTRAINT CK_erp_v1_fix_severity CHECK (
            severity IN (N'CRITICAL', N'HIGH', N'MEDIUM', N'LOW')
        ),
        CONSTRAINT CK_erp_v1_fix_source CHECK (
            source IN (N'PRODUCTION_RUN', N'USER_FEEDBACK', N'OWNER_REVIEW', N'STAFF_REVIEW')
        ),
        CONSTRAINT CK_erp_v1_fix_status CHECK (
            status IN (N'OPEN', N'IN_REVIEW', N'FIXED', N'DEFERRED_TO_V2', N'CLOSED')
        )
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_v1_production_run_signoff WHERE signoff_version = N'V1')
BEGIN
    INSERT INTO dbo.erp_v1_production_run_signoff (
        signoff_version, installer_status, auto_deploy_status, saas_status, api_status,
        mirror_pwa_status, ssl_configured, storage_configured, controlled_scenario_status,
        owner_signoff_status, signoff_note
    ) VALUES (
        N'V1', N'READY', N'READY', N'ACTIVE', N'READY', N'READY', 0, 1, N'SMOKE_PASS', N'PENDING',
        N'V1 SaaS-enabled Production Release — awaiting formal owner signoff after live run.'
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_v1_post_run_fix_register)
BEGIN
    INSERT INTO dbo.erp_v1_post_run_fix_register
        (category, severity, source, description, affected_module, owner_decision, status)
    VALUES
    (N'FIX', N'HIGH', N'PRODUCTION_RUN', N'Seed core_users with password_hash for staff/owner API login on production server.', N'Auth/API', N'Fix in controlled auth mission — not V1 blocker for internal run', N'OPEN'),
    (N'UI', N'MEDIUM', N'OWNER_REVIEW', N'Shell navigation links partially point to legacy prototype pages instead of M33-M37 workbenches.', N'Application Shell M32', N'Accept for V1 internal run; consolidate in post-run fix cycle', N'IN_REVIEW'),
    (N'SECURITY', N'HIGH', N'PRODUCTION_RUN', N'Production SSL on moghareh360.ir must be verified before public mirror traffic.', N'Mirror/PWA', N'External hosting prerequisite', N'OPEN'),
    (N'TRAINING', N'MEDIUM', N'STAFF_REVIEW', N'Reception/service/finance staff need 2-hour controlled scenario walkthrough.', N'Operations', N'Schedule before daily run', N'OPEN'),
    (N'V2_BACKLOG', N'LOW', N'OWNER_REVIEW', N'Customer portal, contract/pricing engine, full accounting export.', N'Commercial V2', N'Deferred to V2 — out of V1 scope', N'DEFERRED_TO_V2'),
    (N'DATA', N'MEDIUM', N'PRODUCTION_RUN', N'Import legacy customer/vehicle data if migrating from previous system.', N'Customer/Vehicle', N'Owner decision per go-live', N'IN_REVIEW');
END;
GO

PRINT N'V1 post-run fix register and production signoff foundation applied.';
GO
