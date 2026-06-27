/*
 * MOGHARE360 P1 — Online Request Intake (non-destructive, idempotent)
 * Extends erp_customer_online_requests for reception workflow + audit history.
 * Safe to run multiple times.
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'customer_id') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD customer_id BIGINT NULL;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'vehicle_id') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD vehicle_id BIGINT NULL;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'converted_jobcard_id') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD converted_jobcard_id BIGINT NULL;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'visit_date') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD visit_date NVARCHAR(20) NULL;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'updated_at') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD updated_at DATETIME2 NULL;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'otp_verified') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD otp_verified BIT NULL;
END;
GO

IF OBJECT_ID(N'dbo.erp_customer_online_request_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_online_request_history
    (
        history_id          BIGINT          NOT NULL IDENTITY(1, 1),
        online_request_id   BIGINT          NOT NULL,
        event_type          NVARCHAR(80)    NOT NULL,
        previous_status     NVARCHAR(80)    NULL,
        new_status          NVARCHAR(80)    NULL,
        event_note          NVARCHAR(2000)  NULL,
        changed_by_user_id  INT             NULL,
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_online_req_hist_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_customer_online_request_history PRIMARY KEY CLUSTERED (history_id)
    );
END;
GO

PRINT N'P1 online request intake migration applied.';
GO
