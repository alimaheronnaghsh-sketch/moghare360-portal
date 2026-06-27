/*
 * MOGHARE360 P7 — Final invoice, settlement, customer delivery (non-destructive)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'final_invoice_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD final_invoice_status NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'current_final_invoice_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD current_final_invoice_id BIGINT NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'final_invoice_amount') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD final_invoice_amount DECIMAL(18,2) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'settlement_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD settlement_status NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'settlement_amount_paid') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD settlement_amount_paid DECIMAL(18,2) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'settlement_remaining_amount') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD settlement_remaining_amount DECIMAL(18,2) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'customer_delivery_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD customer_delivery_status NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'customer_delivery_signed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD customer_delivery_signed_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'vehicle_released_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD vehicle_released_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'jobcard_closed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD jobcard_closed_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_jobcards', N'closed_by_user_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD closed_by_user_id BIGINT NULL;
END;
GO

IF OBJECT_ID(N'dbo.erp_final_invoices', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_final_invoices (
        final_invoice_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        estimate_id BIGINT NULL,
        customer_id BIGINT NULL,
        vehicle_id BIGINT NULL,
        invoice_no NVARCHAR(50) NULL,
        invoice_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_final_inv_status DEFAULT (N'DRAFT'),
        subtotal_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_final_inv_sub DEFAULT (0),
        discount_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_final_inv_disc DEFAULT (0),
        tax_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_final_inv_tax DEFAULT (0),
        total_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_final_inv_total DEFAULT (0),
        estimate_total_amount DECIMAL(18,2) NULL,
        variance_amount DECIMAL(18,2) NULL,
        variance_status NVARCHAR(50) NULL,
        variance_override_reason NVARCHAR(1000) NULL,
        delivery_token_hash NVARCHAR(128) NULL,
        delivery_token_expires_at DATETIME2 NULL,
        finalized_at DATETIME2 NULL,
        customer_notified_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_final_inv_created DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2 NULL,
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_final_invoices PRIMARY KEY CLUSTERED (final_invoice_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_final_invoices', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'delivery_token_hash') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD delivery_token_hash NVARCHAR(128) NULL;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'delivery_token_expires_at') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD delivery_token_expires_at DATETIME2 NULL;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'variance_override_reason') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD variance_override_reason NVARCHAR(1000) NULL;
    IF COL_LENGTH(N'dbo.erp_final_invoices', N'customer_notified_at') IS NULL
        ALTER TABLE dbo.erp_final_invoices ADD customer_notified_at DATETIME2 NULL;
END;
GO

IF OBJECT_ID(N'dbo.erp_final_invoice_items', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_final_invoice_items (
        final_invoice_item_id BIGINT IDENTITY(1,1) NOT NULL,
        final_invoice_id BIGINT NOT NULL,
        jobcard_id BIGINT NOT NULL,
        source_type NVARCHAR(50) NOT NULL,
        source_id BIGINT NULL,
        item_type NVARCHAR(50) NOT NULL,
        item_title NVARCHAR(300) NOT NULL,
        item_description NVARCHAR(MAX) NULL,
        quantity DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_fii_qty DEFAULT (1),
        unit_price DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_fii_unit DEFAULT (0),
        line_total DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_fii_line DEFAULT (0),
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_fii_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_final_invoice_items PRIMARY KEY CLUSTERED (final_invoice_item_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_settlement_controls', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_settlement_controls (
        settlement_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        final_invoice_id BIGINT NOT NULL,
        settlement_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_settle_status DEFAULT (N'PAYMENT_PENDING'),
        total_due_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_settle_due DEFAULT (0),
        total_paid_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_settle_paid DEFAULT (0),
        remaining_amount DECIMAL(18,2) NOT NULL CONSTRAINT DF_erp_settle_rem DEFAULT (0),
        manager_release_approved BIT NOT NULL CONSTRAINT DF_erp_settle_mgr DEFAULT (0),
        manager_release_reason NVARCHAR(1000) NULL,
        settled_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_settle_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_settlement_controls PRIMARY KEY CLUSTERED (settlement_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_customer_delivery_confirmations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_delivery_confirmations (
        delivery_confirmation_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        final_invoice_id BIGINT NULL,
        settlement_id BIGINT NULL,
        mobile NVARCHAR(20) NULL,
        confirmation_status NVARCHAR(50) NOT NULL CONSTRAINT DF_erp_del_conf_status DEFAULT (N'PENDING'),
        otp_verified BIT NOT NULL CONSTRAINT DF_erp_del_conf_otp DEFAULT (0),
        otp_verified_at DATETIME2 NULL,
        signature_hash NVARCHAR(128) NULL,
        confirmation_hash NVARCHAR(128) NULL,
        confirmation_ip NVARCHAR(100) NULL,
        confirmation_user_agent NVARCHAR(1000) NULL,
        customer_note NVARCHAR(1000) NULL,
        confirmed_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_del_conf_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_customer_delivery_confirmations PRIMARY KEY CLUSTERED (delivery_confirmation_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_delivery_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_delivery_events (
        event_id BIGINT IDENTITY(1,1) NOT NULL,
        jobcard_id BIGINT NOT NULL,
        final_invoice_id BIGINT NULL,
        settlement_id BIGINT NULL,
        event_name NVARCHAR(100) NOT NULL,
        event_note NVARCHAR(1000) NULL,
        event_ip NVARCHAR(100) NULL,
        event_user_agent NVARCHAR(1000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_erp_delivery_events_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id BIGINT NULL,
        CONSTRAINT PK_erp_delivery_events PRIMARY KEY CLUSTERED (event_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_final_invoices_jobcard' AND object_id = OBJECT_ID(N'dbo.erp_final_invoices'))
    CREATE INDEX IX_erp_final_invoices_jobcard ON dbo.erp_final_invoices (jobcard_id, invoice_status);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_settlement_controls_jobcard' AND object_id = OBJECT_ID(N'dbo.erp_settlement_controls'))
    CREATE INDEX IX_erp_settlement_controls_jobcard ON dbo.erp_settlement_controls (jobcard_id, settlement_status);
GO

PRINT N'P7 final invoice settlement customer delivery migration applied.';
GO
