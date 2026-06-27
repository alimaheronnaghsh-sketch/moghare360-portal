/*
 * MOGHARE360 P3 — Technical operation workflow columns (non-destructive, idempotent)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'technical_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD technical_status NVARCHAR(50) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'assigned_technician_user_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD assigned_technician_user_id BIGINT NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'diagnosis_started_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD diagnosis_started_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'diagnosis_completed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD diagnosis_completed_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'technician_notes') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD technician_notes NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'diagnosis_summary') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD diagnosis_summary NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'technical_started_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD technical_started_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'technical_completed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD technical_completed_at DATETIME2 NULL;
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcards_technical_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcards'))
    CREATE INDEX IX_erp_jobcards_technical_status ON dbo.erp_jobcards (technical_status, ready_for_technical_at DESC);
GO

PRINT N'P3 technical operation workflow migration applied.';
GO
