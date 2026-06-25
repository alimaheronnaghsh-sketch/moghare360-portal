/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_04_history_audit_tables.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

Version 0 immutable HISTORY and AUDIT tables for internal staff access lifecycle.
Design reference: docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md

Business rules (append-only):
  - core_access_change_history and core_audit_logs are INSERT-only ledgers.
  - No UPDATE or DELETE should be performed on these tables (application or DBA).
  - Every approved and applied access change MUST write at least one row to
    core_access_change_history (before/after snapshot + request_id).
  - Every security-relevant event MUST write to core_audit_logs (request state
    changes, approvals, apply, login deny, emergency override, legacy fallback).

Creates:
  1. core_access_change_history
  2. core_audit_logs

Prerequisites:
  core_v0_01_create_database.sql
  core_v0_02_master_tables.sql
  core_v0_03_workflow_tables.sql

Idempotent: skips CREATE TABLE when table already exists.
================================================================================
*/

USE [moghare360_ERP];
GO

SET NOCOUNT ON;

/* ----------------------------------------------------------------------------
   1. core_access_change_history
   Immutable ledger of applied access / lifecycle changes (before/after JSON).
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_access_change_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_access_change_history
    (
        history_id          BIGINT IDENTITY(1, 1)   NOT NULL,
        user_id             INT                     NOT NULL,
        request_id          BIGINT                  NOT NULL,
        change_type         NVARCHAR(40)            NOT NULL,
        entity_type         NVARCHAR(50)            NOT NULL,
        entity_id           BIGINT                  NULL,
        before_json         NVARCHAR(MAX)           NULL,
        after_json          NVARCHAR(MAX)           NULL,
        changed_by_user_id  INT                     NULL,
        changed_at          DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_access_change_history_changed_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_access_change_history PRIMARY KEY CLUSTERED (history_id),
        CONSTRAINT FK_core_access_change_history_user
            FOREIGN KEY (user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_access_change_history_request
            FOREIGN KEY (request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT FK_core_access_change_history_changed_by
            FOREIGN KEY (changed_by_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT CK_core_access_change_history_before_json CHECK (
            before_json IS NULL OR ISJSON(before_json) = 1
        ),
        CONSTRAINT CK_core_access_change_history_after_json CHECK (
            after_json IS NULL OR ISJSON(after_json) = 1
        )
    );

    CREATE INDEX IX_core_access_change_history_user
        ON dbo.core_access_change_history (user_id, changed_at DESC);

    CREATE INDEX IX_core_access_change_history_request
        ON dbo.core_access_change_history (request_id);

    CREATE INDEX IX_core_access_change_history_type
        ON dbo.core_access_change_history (change_type, changed_at DESC);

    PRINT N'Created table dbo.core_access_change_history';
END
ELSE
    PRINT N'Table dbo.core_access_change_history already exists — skipped.';
GO

/* ----------------------------------------------------------------------------
   2. core_audit_logs
   Append-only security event log (broader than change history).
---------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.core_audit_logs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.core_audit_logs
    (
        audit_id            BIGINT IDENTITY(1, 1)   NOT NULL,
        actor_user_id       INT                     NULL,
        action              NVARCHAR(80)            NOT NULL,
        entity_type         NVARCHAR(50)            NULL,
        entity_id           BIGINT                  NULL,
        request_id          BIGINT                  NULL,
        subject_user_id     INT                     NULL,
        details_json        NVARCHAR(MAX)           NULL,
        ip_address          NVARCHAR(45)            NULL,
        user_agent          NVARCHAR(500)           NULL,
        is_emergency        BIT                     NOT NULL
            CONSTRAINT DF_core_audit_logs_is_emergency DEFAULT (0),
        created_at          DATETIME2(3)            NOT NULL
            CONSTRAINT DF_core_audit_logs_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_core_audit_logs PRIMARY KEY CLUSTERED (audit_id),
        CONSTRAINT FK_core_audit_logs_actor
            FOREIGN KEY (actor_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_audit_logs_subject
            FOREIGN KEY (subject_user_id) REFERENCES dbo.core_users (user_id),
        CONSTRAINT FK_core_audit_logs_request
            FOREIGN KEY (request_id) REFERENCES dbo.core_access_requests (request_id),
        CONSTRAINT CK_core_audit_logs_details_json CHECK (
            details_json IS NULL OR ISJSON(details_json) = 1
        )
    );

    CREATE INDEX IX_core_audit_logs_created
        ON dbo.core_audit_logs (created_at DESC);

    CREATE INDEX IX_core_audit_logs_actor
        ON dbo.core_audit_logs (actor_user_id, created_at DESC);

    CREATE INDEX IX_core_audit_logs_action
        ON dbo.core_audit_logs (action, created_at DESC);

    CREATE INDEX IX_core_audit_logs_request
        ON dbo.core_audit_logs (request_id);

    CREATE INDEX IX_core_audit_logs_subject
        ON dbo.core_audit_logs (subject_user_id, created_at DESC);

    PRINT N'Created table dbo.core_audit_logs';
END
ELSE
    PRINT N'Table dbo.core_audit_logs already exists — skipped.';
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
  AND t.name IN (N'core_access_change_history', N'core_audit_logs')
ORDER BY t.name;
GO
