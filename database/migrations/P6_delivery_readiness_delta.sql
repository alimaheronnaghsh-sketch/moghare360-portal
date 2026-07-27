/*
 * MOGHARE360 P6 additive delta — complete approved QC/delivery readiness persistence.
 * Local application only. Idempotent and non-destructive.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL
        THROW 51000, 'P6 preflight failed: erp_jobcards is missing.', 1;
    IF OBJECT_ID(N'dbo.erp_qc_checks', N'U') IS NULL
        THROW 51001, 'P6 preflight failed: erp_qc_checks is missing.', 1;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_jobcards'
          AND COLUMN_NAME = N'jobcard_id' AND DATA_TYPE = N'int'
    )
        THROW 51002, 'P6 preflight failed: erp_jobcards.jobcard_id type mismatch.', 1;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_qc_checks'
          AND COLUMN_NAME = N'qc_check_id' AND DATA_TYPE = N'int'
    )
        THROW 51003, 'P6 preflight failed: erp_qc_checks.qc_check_id type mismatch.', 1;

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
        ALTER TABLE dbo.erp_qc_checks ADD created_by_user_id INT NULL;

    IF OBJECT_ID(N'dbo.erp_delivery_readiness_checks', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_delivery_readiness_checks
        (
            readiness_check_id BIGINT IDENTITY(1,1) NOT NULL,
            jobcard_id INT NOT NULL,
            qc_check_id INT NULL,
            readiness_status NVARCHAR(50) NOT NULL
                CONSTRAINT DF_erp_delivery_readiness_status DEFAULT (N'PENDING'),
            readiness_note NVARCHAR(MAX) NULL,
            ready_at DATETIME2 NULL,
            created_at DATETIME2 NOT NULL
                CONSTRAINT DF_erp_delivery_readiness_created DEFAULT (SYSUTCDATETIME()),
            created_by_user_id INT NULL,
            CONSTRAINT PK_erp_delivery_readiness_checks
                PRIMARY KEY CLUSTERED (readiness_check_id),
            CONSTRAINT FK_erp_delivery_readiness_jobcard
                FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards(jobcard_id),
            CONSTRAINT FK_erp_delivery_readiness_qc
                FOREIGN KEY (qc_check_id) REFERENCES dbo.erp_qc_checks(qc_check_id),
            CONSTRAINT FK_erp_delivery_readiness_user
                FOREIGN KEY (created_by_user_id) REFERENCES dbo.core_users(user_id),
            CONSTRAINT CK_erp_delivery_readiness_status
                CHECK (readiness_status IN (N'PENDING', N'READY', N'BLOCKED', N'CANCELLED'))
        );
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE object_id = OBJECT_ID(N'dbo.erp_delivery_readiness_checks')
          AND name = N'IX_erp_delivery_readiness_jobcard'
    )
        CREATE INDEX IX_erp_delivery_readiness_jobcard
            ON dbo.erp_delivery_readiness_checks(jobcard_id, readiness_status, created_at DESC);

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF XACT_STATE() <> 0
        ROLLBACK TRANSACTION;
    THROW;
END CATCH;
