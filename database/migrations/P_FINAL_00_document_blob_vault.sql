/*
 * MOGHARE360 — Final ERP Completion Drive — Prompt 0
 * Canonical SQL Server document / PDF / photo blob vault (additive, idempotent)
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

PRINT N'P_FINAL_00_document_blob_vault: START';
GO

IF OBJECT_ID(N'dbo.erp_document_blobs', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_document_blobs', N'document_blob_id') IS NULL
       OR COL_LENGTH(N'dbo.erp_document_blobs', N'content_binary') IS NULL
       OR COL_LENGTH(N'dbo.erp_document_blobs', N'sha256_hash') IS NULL
    BEGIN
        RAISERROR(N'P_FINAL_00: incompatible existing dbo.erp_document_blobs schema. Owner decision required.', 16, 1);
        RETURN;
    END;
END;
GO

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_document_blobs', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_document_blobs
        (
            document_blob_id        BIGINT          NOT NULL IDENTITY(1, 1),
            owner_type              NVARCHAR(40)    NOT NULL,
            owner_id                BIGINT          NOT NULL,
            related_customer_id     BIGINT          NULL,
            related_vehicle_id      BIGINT          NULL,
            related_request_id      BIGINT          NULL,
            related_jobcard_id      BIGINT          NULL,
            document_category       NVARCHAR(60)    NOT NULL,
            original_file_name      NVARCHAR(260)   NULL,
            content_type            NVARCHAR(120)   NOT NULL,
            file_extension          NVARCHAR(20)    NULL,
            file_size_bytes         BIGINT          NOT NULL,
            sha256_hash             CHAR(64)        NOT NULL,
            content_binary          VARBINARY(MAX)  NOT NULL,
            disk_mirror_path        NVARCHAR(500)   NULL,
            version_no              INT             NOT NULL
                CONSTRAINT DF_erp_document_blobs_version_no DEFAULT (1),
            is_active               BIT             NOT NULL
                CONSTRAINT DF_erp_document_blobs_is_active DEFAULT (1),
            superseded_by_blob_id   BIGINT          NULL,
            created_by              BIGINT          NULL,
            created_at              DATETIME2       NOT NULL
                CONSTRAINT DF_erp_document_blobs_created_at DEFAULT (SYSUTCDATETIME()),
            updated_at              DATETIME2       NULL,
            deleted_at              DATETIME2       NULL,
            notes_json              NVARCHAR(MAX)   NULL,
            CONSTRAINT PK_erp_document_blobs PRIMARY KEY CLUSTERED (document_blob_id)
        );
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_document_blobs_owner'
          AND object_id = OBJECT_ID(N'dbo.erp_document_blobs', N'U')
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_erp_document_blobs_owner
            ON dbo.erp_document_blobs (owner_type, owner_id, is_active)
            INCLUDE (document_category, version_no, sha256_hash);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_document_blobs_customer'
          AND object_id = OBJECT_ID(N'dbo.erp_document_blobs', N'U')
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_erp_document_blobs_customer
            ON dbo.erp_document_blobs (related_customer_id)
            WHERE related_customer_id IS NOT NULL;
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_document_blobs_request'
          AND object_id = OBJECT_ID(N'dbo.erp_document_blobs', N'U')
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_erp_document_blobs_request
            ON dbo.erp_document_blobs (related_request_id)
            WHERE related_request_id IS NOT NULL;
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_document_blobs_jobcard'
          AND object_id = OBJECT_ID(N'dbo.erp_document_blobs', N'U')
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_erp_document_blobs_jobcard
            ON dbo.erp_document_blobs (related_jobcard_id)
            WHERE related_jobcard_id IS NOT NULL;
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_document_blobs_category'
          AND object_id = OBJECT_ID(N'dbo.erp_document_blobs', N'U')
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_erp_document_blobs_category
            ON dbo.erp_document_blobs (document_category, is_active);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_document_blobs_sha256'
          AND object_id = OBJECT_ID(N'dbo.erp_document_blobs', N'U')
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_erp_document_blobs_sha256
            ON dbo.erp_document_blobs (sha256_hash);
    END;

    COMMIT TRANSACTION;
    PRINT N'P_FINAL_00_document_blob_vault: OK';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;
    THROW;
END CATCH;
GO
