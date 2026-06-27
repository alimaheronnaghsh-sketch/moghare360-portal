/*
 * MOGHARE360 P2 — Reception JobCard workflow columns (non-destructive, idempotent)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'vehicle_arrival_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD vehicle_arrival_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'checked_in_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD checked_in_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'reception_notes') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD reception_notes NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'initial_inspection_notes') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD initial_inspection_notes NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'ready_for_technical_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD ready_for_technical_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'assigned_reception_user_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD assigned_reception_user_id BIGINT NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'online_request_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD online_request_id BIGINT NULL;
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcards_jobcard_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcards'))
    CREATE INDEX IX_erp_jobcards_jobcard_status ON dbo.erp_jobcards (jobcard_status, created_at DESC);
GO

PRINT N'P2 reception jobcard workflow migration applied.';
GO
