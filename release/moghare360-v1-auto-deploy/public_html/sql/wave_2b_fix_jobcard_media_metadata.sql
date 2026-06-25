/*
================================================================================
MOGHARE360 ERP — WAVE 2B-FIX
Script: wave_2b_fix_jobcard_media_metadata.sql
================================================================================

Official SQL Server foundation for JobCard Media Metadata.

Database: MOGHARE360_ERP

Execute manually in SSMS only. Do not auto-run from PHP.
Cursor must not execute this script.

Idempotent: skips CREATE when tables already exist. No DROP TABLE.
================================================================================
*/

USE [MOGHARE360_ERP];
GO

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL
BEGIN
    THROW 51000, 'Required table dbo.erp_jobcards was not found. WAVE 2B-FIX stopped safely.', 1;
END;

DECLARE @jobcard_id_type_name NVARCHAR(128);

SELECT @jobcard_id_type_name = t.name
FROM sys.columns c
INNER JOIN sys.types t ON c.user_type_id = t.user_type_id
WHERE c.object_id = OBJECT_ID(N'dbo.erp_jobcards')
  AND c.name = N'jobcard_id';

IF @jobcard_id_type_name IS NULL
BEGIN
    THROW 51001, 'Required column dbo.erp_jobcards.jobcard_id was not found. WAVE 2B-FIX stopped safely.', 1;
END;

IF @jobcard_id_type_name <> N'int'
BEGIN
    THROW 51002, 'dbo.erp_jobcards.jobcard_id must be INT for FK compatibility. WAVE 2B-FIX stopped safely.', 1;
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_media', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_media
    (
        media_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id INT NOT NULL,

        media_stage NVARCHAR(50) NOT NULL,
        media_type NVARCHAR(50) NOT NULL,

        relative_path NVARCHAR(500) NOT NULL,
        file_path NVARCHAR(1000) NULL,
        original_file_name NVARCHAR(255) NULL,

        mime_type NVARCHAR(100) NOT NULL,
        file_size BIGINT NOT NULL,
        checksum_sha256 CHAR(64) NULL,

        source NVARCHAR(50) NOT NULL
            CONSTRAINT DF_erp_jobcard_media_source DEFAULT (N'CAMERA_ONLY'),

        capture_method NVARCHAR(50) NOT NULL
            CONSTRAINT DF_erp_jobcard_media_capture_method DEFAULT (N'BROWSER_CAMERA'),

        metadata_status NVARCHAR(50) NOT NULL
            CONSTRAINT DF_erp_jobcard_media_metadata_status DEFAULT (N'ACTIVE'),

        notes NVARCHAR(1000) NULL,

        is_active BIT NOT NULL
            CONSTRAINT DF_erp_jobcard_media_is_active DEFAULT (1),

        is_deleted BIT NOT NULL
            CONSTRAINT DF_erp_jobcard_media_is_deleted DEFAULT (0),

        created_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_media_created_at DEFAULT (SYSUTCDATETIME()),

        created_by NVARCHAR(100) NULL,

        updated_at DATETIME2(0) NULL,
        updated_by NVARCHAR(100) NULL,

        CONSTRAINT PK_erp_jobcard_media
            PRIMARY KEY CLUSTERED (media_id ASC),

        CONSTRAINT CK_erp_jobcard_media_stage
            CHECK (media_stage IN
            (
                N'input',
                N'output',
                N'diagnostic_initial',
                N'diagnostic_secondary',
                N'diagnostic_final'
            )),

        CONSTRAINT CK_erp_jobcard_media_type
            CHECK (media_type IN
            (
                N'front',
                N'rear',
                N'right',
                N'left',
                N'dashboard',
                N'odometer',
                N'damage',
                N'part',
                N'diagnostic',
                N'other'
            )),

        CONSTRAINT CK_erp_jobcard_media_source
            CHECK (source = N'CAMERA_ONLY'),

        CONSTRAINT CK_erp_jobcard_media_capture_method
            CHECK (capture_method = N'BROWSER_CAMERA'),

        CONSTRAINT CK_erp_jobcard_media_file_size
            CHECK (file_size > 0),

        CONSTRAINT CK_erp_jobcard_media_relative_path
            CHECK (
                relative_path IS NOT NULL
                AND LEN(LTRIM(RTRIM(relative_path))) > 0
                AND relative_path NOT LIKE N'%..%'
                AND relative_path NOT LIKE N'http://%'
                AND relative_path NOT LIKE N'https://%'
            ),

        CONSTRAINT CK_erp_jobcard_media_mime
            CHECK (mime_type IN
            (
                N'image/jpeg',
                N'image/png',
                N'image/webp'
            ))
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_media_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_media')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_media
        ADD CONSTRAINT FK_erp_jobcard_media_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_media_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_media')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_media_jobcard_id
        ON dbo.erp_jobcard_media(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_media_stage_type'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_media')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_media_stage_type
        ON dbo.erp_jobcard_media(media_stage, media_type);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_media_created_at'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_media')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_media_created_at
        ON dbo.erp_jobcard_media(created_at);
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcard_media_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_jobcard_media_history
    (
        history_id BIGINT IDENTITY(1,1) NOT NULL,
        media_id BIGINT NULL,
        jobcard_id INT NOT NULL,

        event_code NVARCHAR(100) NOT NULL,
        event_title NVARCHAR(255) NOT NULL,
        event_notes NVARCHAR(2000) NULL,

        old_status NVARCHAR(50) NULL,
        new_status NVARCHAR(50) NULL,

        event_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_erp_jobcard_media_history_event_at DEFAULT (SYSUTCDATETIME()),

        event_by NVARCHAR(100) NULL,

        CONSTRAINT PK_erp_jobcard_media_history
            PRIMARY KEY CLUSTERED (history_id ASC),

        CONSTRAINT CK_erp_jobcard_media_history_event_code
            CHECK (LEN(LTRIM(RTRIM(event_code))) > 0)
    );
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_media_history_media'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_media_history')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_media_history
        ADD CONSTRAINT FK_erp_jobcard_media_history_media
            FOREIGN KEY (media_id)
            REFERENCES dbo.erp_jobcard_media(media_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_erp_jobcard_media_history_jobcard'
      AND parent_object_id = OBJECT_ID(N'dbo.erp_jobcard_media_history')
)
BEGIN
    ALTER TABLE dbo.erp_jobcard_media_history
        ADD CONSTRAINT FK_erp_jobcard_media_history_jobcard
            FOREIGN KEY (jobcard_id)
            REFERENCES dbo.erp_jobcards(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_media_history_jobcard_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_media_history')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_media_history_jobcard_id
        ON dbo.erp_jobcard_media_history(jobcard_id);
END;
GO

IF NOT EXISTS
(
    SELECT 1
    FROM sys.indexes
    WHERE name = N'IX_erp_jobcard_media_history_media_id'
      AND object_id = OBJECT_ID(N'dbo.erp_jobcard_media_history')
)
BEGIN
    CREATE INDEX IX_erp_jobcard_media_history_media_id
        ON dbo.erp_jobcard_media_history(media_id);
END;
GO

SELECT
    'WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_READY' AS status,
    OBJECT_ID(N'dbo.erp_jobcard_media', N'U') AS media_table_object_id,
    OBJECT_ID(N'dbo.erp_jobcard_media_history', N'U') AS media_history_table_object_id;
GO
