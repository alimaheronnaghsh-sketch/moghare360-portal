/*
 * MOGHARE360 — Rollback P_FINAL_00 (drops vault table only — run only on disposable DB / owner approval)
 * Does NOT delete disk mirror files.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

PRINT N'P_FINAL_00_document_blob_vault_rollback: START';
GO

IF OBJECT_ID(N'dbo.erp_document_blobs', N'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.erp_document_blobs;
    PRINT N'P_FINAL_00: dropped dbo.erp_document_blobs';
END
ELSE
BEGIN
    PRINT N'P_FINAL_00: table already absent';
END;
GO

PRINT N'P_FINAL_00_document_blob_vault_rollback: END';
GO
