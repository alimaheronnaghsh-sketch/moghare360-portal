/*
 * MOGHARE360 P4 — Estimate, approval, parts & finance gate (non-destructive, idempotent)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_estimates', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_estimates (
        estimate_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        customer_id BIGINT NULL,
        vehicle_id BIGINT NULL,
        estimate_version INT NOT NULL CONSTRAINT DF_erp_estimates_version DEFAULT (1),
        estimate_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_estimates_status DEFAULT (N'DRAFT'),
        estimate_title NVARCHAR(300) NULL,
        subtotal_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimates_subtotal DEFAULT (0),
        discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimates_discount DEFAULT (0),
        tax_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimates_tax DEFAULT (0),
        total_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimates_total DEFAULT (0),
        advance_required_amount DECIMAL(18,2) NULL,
        parts_required BIT NOT NULL CONSTRAINT DF_erp_estimates_parts_req DEFAULT (0),
        finance_required BIT NOT NULL CONSTRAINT DF_erp_estimates_finance_req DEFAULT (1),
        parts_gate_status NVARCHAR(50) NULL,
        finance_gate_status NVARCHAR(50) NULL,
        secure_token_hash NVARCHAR(128) NULL,
        secure_token_expires_at DATETIME2 NULL,
        sent_at DATETIME2 NULL,
        viewed_at DATETIME2 NULL,
        approved_at DATETIME2 NULL,
        rejected_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_estimates_created DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2 NULL,
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_estimates PRIMARY KEY CLUSTERED (estimate_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_estimate_items', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_estimate_items (
        estimate_item_id BIGINT IDENTITY(1,1) NOT NULL,
        estimate_id BIGINT NOT NULL,
        jobcard_id BIGINT NOT NULL,
        service_operation_id BIGINT NULL,
        item_type NVARCHAR(50) NOT NULL,
        item_title NVARCHAR(300) NOT NULL,
        item_description NVARCHAR(MAX) NULL,
        quantity DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimate_items_qty DEFAULT (1),
        unit_price DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimate_items_price DEFAULT (0),
        line_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_estimate_items_total DEFAULT (0),
        part_id BIGINT NULL,
        part_required BIT NOT NULL CONSTRAINT DF_erp_estimate_items_part_req DEFAULT (0),
        approval_required BIT NOT NULL CONSTRAINT DF_erp_estimate_items_approval DEFAULT (1),
        item_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_estimate_items_status DEFAULT (N'DRAFT'),
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_estimate_items_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_estimate_items PRIMARY KEY CLUSTERED (estimate_item_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_estimate_approvals', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_estimate_approvals (
        approval_id BIGINT IDENTITY(1,1) NOT NULL,
        estimate_id BIGINT NOT NULL,
        jobcard_id BIGINT NOT NULL,
        mobile NVARCHAR(20) NOT NULL,
        approval_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_estimate_approvals_status DEFAULT (N'PENDING'),
        otp_verified BIT NOT NULL CONSTRAINT DF_erp_estimate_approvals_otp DEFAULT (0),
        otp_verified_at DATETIME2 NULL,
        approved_total_amount DECIMAL(18,2) NULL,
        approval_ip NVARCHAR(100) NULL,
        approval_user_agent NVARCHAR(1000) NULL,
        approval_hash NVARCHAR(128) NULL,
        customer_note NVARCHAR(1000) NULL,
        approved_at DATETIME2 NULL,
        rejected_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_estimate_approvals_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_estimate_approvals PRIMARY KEY CLUSTERED (approval_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_estimate_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_estimate_events (
        event_id BIGINT IDENTITY(1,1) NOT NULL,
        estimate_id BIGINT NULL,
        jobcard_id BIGINT NOT NULL,
        event_name NVARCHAR(100) NOT NULL,
        event_note NVARCHAR(1000) NULL,
        event_ip NVARCHAR(100) NULL,
        event_user_agent NVARCHAR(1000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_estimate_events_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_estimate_events PRIMARY KEY CLUSTERED (event_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'estimate_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD estimate_status NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'current_estimate_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD current_estimate_id BIGINT NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'estimate_approved_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD estimate_approved_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'parts_gate_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD parts_gate_status NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'finance_gate_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD finance_gate_status NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'approved_for_work_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD approved_for_work_at DATETIME2 NULL;
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_estimates_jobcard_id' AND object_id = OBJECT_ID(N'dbo.erp_estimates'))
    CREATE INDEX IX_erp_estimates_jobcard_id ON dbo.erp_estimates (jobcard_id, estimate_status);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_estimates_token_hash' AND object_id = OBJECT_ID(N'dbo.erp_estimates'))
    CREATE INDEX IX_erp_estimates_token_hash ON dbo.erp_estimates (secure_token_hash);
GO

PRINT N'P4 estimate approval parts finance gate migration applied.';
GO
