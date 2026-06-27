/*
 * MOGHARE360 P5 — Work execution, parts consumption, technical completion (non-destructive)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'work_execution_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD work_execution_status NVARCHAR(50) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'work_started_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD work_started_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'work_completed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD work_completed_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'ready_for_qc_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD ready_for_qc_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'parts_consumption_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD parts_consumption_status NVARCHAR(50) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'technical_completion_notes') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD technical_completion_notes NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'final_technician_user_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD final_technician_user_id BIGINT NULL;
END;
GO

IF OBJECT_ID(N'dbo.erp_work_execution_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_work_execution_events (
        event_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        service_operation_id BIGINT NULL,
        event_name NVARCHAR(100) NOT NULL,
        event_note NVARCHAR(1000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_work_exec_events_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_work_execution_events PRIMARY KEY CLUSTERED (event_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcards_work_execution_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcards'))
    CREATE INDEX IX_erp_jobcards_work_execution_status ON dbo.erp_jobcards (work_execution_status, approved_for_work_at DESC);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_work_execution_events_jobcard' AND object_id = OBJECT_ID(N'dbo.erp_work_execution_events'))
    CREATE INDEX IX_erp_work_execution_events_jobcard ON dbo.erp_work_execution_events (jobcard_id, created_at DESC);
GO

PRINT N'P5 work execution parts consumption migration applied.';
GO
