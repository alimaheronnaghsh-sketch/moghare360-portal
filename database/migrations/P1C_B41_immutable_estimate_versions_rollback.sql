/*
 * MOGHARE360 Wave 1C-B4.1-B — Rollback immutable estimate versions (manual only).
 * Destructive rollback: requires explicit Owner approval before execution.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

BEGIN TRY
    BEGIN TRANSACTION;

    PRINT N'P1C_B41 immutable estimate versions rollback started.';

    IF OBJECT_ID(N'dbo.TR_erp_estimate_versions_block_content_update', N'TR') IS NOT NULL
    BEGIN
        DROP TRIGGER dbo.TR_erp_estimate_versions_block_content_update;
        PRINT N'Dropped trigger dbo.TR_erp_estimate_versions_block_content_update.';
    END;

    IF OBJECT_ID(N'dbo.TR_erp_estimate_version_items_block_delete', N'TR') IS NOT NULL
    BEGIN
        DROP TRIGGER dbo.TR_erp_estimate_version_items_block_delete;
        PRINT N'Dropped trigger dbo.TR_erp_estimate_version_items_block_delete.';
    END;

    IF OBJECT_ID(N'dbo.TR_erp_estimate_version_items_block_update', N'TR') IS NOT NULL
    BEGIN
        DROP TRIGGER dbo.TR_erp_estimate_version_items_block_update;
        PRINT N'Dropped trigger dbo.TR_erp_estimate_version_items_block_update.';
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_events_version', N'F') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_events DROP CONSTRAINT FK_erp_estimate_events_version;
        PRINT N'Dropped constraint dbo.FK_erp_estimate_events_version.';
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_approvals_version', N'F') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals DROP CONSTRAINT FK_erp_estimate_approvals_version;
        PRINT N'Dropped constraint dbo.FK_erp_estimate_approvals_version.';
    END;

    IF EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE name = N'UX_erp_estimate_approvals_version_decision'
          AND object_id = OBJECT_ID(N'dbo.erp_estimate_approvals')
    )
    BEGIN
        DROP INDEX UX_erp_estimate_approvals_version_decision ON dbo.erp_estimate_approvals;
        PRINT N'Dropped index UX_erp_estimate_approvals_version_decision.';
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_events', N'estimate_version_id') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_events DROP COLUMN estimate_version_id;
        PRINT N'Dropped column dbo.erp_estimate_events.estimate_version_id.';
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'decision_channel') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals DROP COLUMN decision_channel;
        PRINT N'Dropped column dbo.erp_estimate_approvals.decision_channel.';
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'content_hash') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals DROP COLUMN content_hash;
        PRINT N'Dropped column dbo.erp_estimate_approvals.content_hash.';
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'estimate_version_id') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals DROP COLUMN estimate_version_id;
        PRINT N'Dropped column dbo.erp_estimate_approvals.estimate_version_id.';
    END;

    IF OBJECT_ID(N'dbo.erp_estimate_version_items', N'U') IS NOT NULL
    BEGIN
        DROP TABLE dbo.erp_estimate_version_items;
        PRINT N'Dropped table dbo.erp_estimate_version_items.';
    END;

    IF OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
    BEGIN
        DROP TABLE dbo.erp_estimate_versions;
        PRINT N'Dropped table dbo.erp_estimate_versions.';
    END;

    COMMIT TRANSACTION;

    PRINT N'P1C_B41 immutable estimate versions rollback complete.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    DECLARE @rollback_error NVARCHAR(4000) = ERROR_MESSAGE();
    PRINT N'P1C_B41 immutable estimate versions rollback failed.';
    THROW 51041, @rollback_error, 1;
END CATCH;
GO
