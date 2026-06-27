/*
 * MOGHARE360 P6 — QC, final inspection, delivery readiness (non-destructive)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_status NVARCHAR(50) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_started_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_started_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_completed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_completed_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_passed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_passed_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_failed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_failed_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_failure_reason') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_failure_reason NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'final_inspection_notes') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD final_inspection_notes NVARCHAR(MAX) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'delivery_readiness_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD delivery_readiness_status NVARCHAR(50) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'delivery_ready_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD delivery_ready_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'qc_user_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD qc_user_id BIGINT NULL;
END;
GO

IF OBJECT_ID(N'dbo.erp_qc_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_qc_checks (
        qc_check_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        qc_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_qc_checks_p6_status DEFAULT (N'DRAFT'),
        qc_result NVARCHAR(50) NULL,
        final_note NVARCHAR(MAX) NULL,
        failure_reason NVARCHAR(MAX) NULL,
        started_at DATETIME2 NULL,
        completed_at DATETIME2 NULL,
        passed_at DATETIME2 NULL,
        failed_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_qc_checks_p6_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        is_active BIT NOT NULL CONSTRAINT DF_erp_qc_checks_p6_active DEFAULT (1),
        CONSTRAINT PK_erp_qc_checks_p6 PRIMARY KEY CLUSTERED (qc_check_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_qc_checks', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'qc_result') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD qc_result NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'final_note') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD final_note NVARCHAR(MAX) NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'failure_reason') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD failure_reason NVARCHAR(MAX) NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'started_at') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD started_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'completed_at') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD completed_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'passed_at') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD passed_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'failed_at') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD failed_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'created_by_user_id') IS NULL
        ALTER TABLE dbo.erp_qc_checks ADD created_by_user_id BIGINT NULL;
END;
GO

IF OBJECT_ID(N'dbo.erp_qc_check_items', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_qc_check_items (
        qc_item_id BIGINT IDENTITY(1,1) NOT NULL,
        qc_check_id BIGINT NOT NULL,
        jobcard_id BIGINT NOT NULL,
        item_key NVARCHAR(100) NOT NULL,
        item_title NVARCHAR(300) NOT NULL,
        item_result NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_qc_items_result DEFAULT (N'PENDING'),
        item_note NVARCHAR(MAX) NULL,
        checked_at DATETIME2 NULL,
        checked_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_qc_check_items PRIMARY KEY CLUSTERED (qc_item_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_qc_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_qc_events (
        event_id BIGINT IDENTITY(1,1) NOT NULL,
        qc_check_id BIGINT NULL,
        jobcard_id BIGINT NOT NULL,
        event_name NVARCHAR(100) NOT NULL,
        event_note NVARCHAR(1000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_qc_events_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_qc_events PRIMARY KEY CLUSTERED (event_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_qc_media_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_qc_media_events (
        media_event_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        qc_check_id BIGINT NULL,
        media_type NVARCHAR(50) NOT NULL,
        capture_method NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_qc_media_capture DEFAULT (N'DIRECT_CAMERA'),
        media_note NVARCHAR(1000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_qc_media_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_qc_media_events PRIMARY KEY CLUSTERED (media_event_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_delivery_readiness_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_delivery_readiness_checks (
        readiness_check_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        qc_check_id BIGINT NULL,
        readiness_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_del_ready_status DEFAULT (N'PENDING'),
        readiness_note NVARCHAR(MAX) NULL,
        ready_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_del_ready_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_delivery_readiness_checks PRIMARY KEY CLUSTERED (readiness_check_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcards_qc_status' AND object_id = OBJECT_ID(N'dbo.erp_jobcards'))
    CREATE INDEX IX_erp_jobcards_qc_status ON dbo.erp_jobcards (qc_status, ready_for_qc_at DESC);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_qc_check_items_check' AND object_id = OBJECT_ID(N'dbo.erp_qc_check_items'))
    CREATE INDEX IX_erp_qc_check_items_check ON dbo.erp_qc_check_items (qc_check_id, item_key);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_qc_events_jobcard' AND object_id = OBJECT_ID(N'dbo.erp_qc_events'))
    CREATE INDEX IX_erp_qc_events_jobcard ON dbo.erp_qc_events (jobcard_id, created_at DESC);
GO

PRINT N'P6 QC final inspection delivery readiness migration applied.';
GO
