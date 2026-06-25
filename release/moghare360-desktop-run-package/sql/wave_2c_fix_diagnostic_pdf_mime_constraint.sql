/*
================================================================================
MOGHARE360 ERP — WAVE 2C-FIX
Script: wave_2c_fix_diagnostic_pdf_mime_constraint.sql
================================================================================

Extends dbo.erp_jobcard_media.mime_type CHECK to allow application/pdf
for controlled diagnostic PDF metadata binding.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: drops and recreates CK_erp_jobcard_media_mime only.
No DROP TABLE. No other constraint changes.
================================================================================
*/

USE [MOGHARE360_ERP];
GO

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_media', N'U') IS NULL
BEGIN
    THROW 52000, 'Required table dbo.erp_jobcard_media was not found. WAVE 2C-FIX stopped safely.', 1;
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.erp_jobcard_media')
      AND name = N'mime_type'
)
BEGIN
    THROW 52001, 'Required column dbo.erp_jobcard_media.mime_type was not found. WAVE 2C-FIX stopped safely.', 1;
END;
GO

IF EXISTS
(
    SELECT 1
    FROM sys.check_constraints
    WHERE name = N'CK_erp_jobcard_media_mime'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_media')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_media
        DROP CONSTRAINT CK_erp_jobcard_media_mime;
END;
GO

ALTER TABLE dbo.erp_jobcard_media
    ADD CONSTRAINT CK_erp_jobcard_media_mime
        CHECK (mime_type IN
        (
            N'application/pdf',
            N'image/jpeg',
            N'image/png',
            N'image/webp'
        ));
GO

SELECT
    'WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_READY' AS status,
    OBJECT_ID(N'dbo.erp_jobcard_media', N'U') AS media_table_object_id,
    OBJECT_ID(N'dbo.erp_jobcard_media_history', N'U') AS media_history_table_object_id;
GO
