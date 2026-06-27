/*
 * MOGHARE360 P9 — End-to-end soft run / demo tracking (non-destructive, no operational mutation)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_scenarios', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_scenarios (
        soft_run_id BIGINT IDENTITY(1,1) NOT NULL,
        scenario_code NVARCHAR(100) NOT NULL,
        scenario_title NVARCHAR(300) NOT NULL,
        scenario_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_soft_run_scenario_status DEFAULT (N'DRAFT'),
        demo_jobcard_id BIGINT NULL,
        demo_customer_id BIGINT NULL,
        demo_vehicle_id BIGINT NULL,
        started_at DATETIME2 NULL,
        completed_at DATETIME2 NULL,
        readiness_score DECIMAL(5,2) NULL,
        notes NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_soft_run_scenario_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_soft_run_scenarios PRIMARY KEY CLUSTERED (soft_run_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_events (
        soft_run_event_id BIGINT IDENTITY(1,1) NOT NULL,
        soft_run_id BIGINT NULL,
        jobcard_id BIGINT NULL,
        stage_code NVARCHAR(100) NOT NULL,
        event_name NVARCHAR(100) NOT NULL,
        event_status NVARCHAR(50) NOT NULL,
        event_note NVARCHAR(1000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_soft_run_events_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_soft_run_events PRIMARY KEY CLUSTERED (soft_run_event_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_soft_run_checklist', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_soft_run_checklist (
        checklist_id BIGINT IDENTITY(1,1) NOT NULL,
        soft_run_id BIGINT NULL,
        checklist_key NVARCHAR(100) NOT NULL,
        checklist_title NVARCHAR(300) NOT NULL,
        checklist_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_soft_run_checklist_status DEFAULT (N'PENDING'),
        checklist_note NVARCHAR(1000) NULL,
        checked_at DATETIME2 NULL,
        checked_by_user_id BIGINT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_soft_run_checklist_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_soft_run_checklist PRIMARY KEY CLUSTERED (checklist_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_soft_run_scenarios_code' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_scenarios'))
    CREATE INDEX IX_erp_soft_run_scenarios_code ON dbo.erp_soft_run_scenarios (scenario_code, scenario_status);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_soft_run_checklist_key' AND object_id = OBJECT_ID(N'dbo.erp_soft_run_checklist'))
    CREATE INDEX IX_erp_soft_run_checklist_key ON dbo.erp_soft_run_checklist (checklist_key, checklist_status);
GO

PRINT N'P9 end-to-end soft run tracking migration applied (read-only tracking tables only).';
GO
