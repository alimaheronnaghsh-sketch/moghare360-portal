/*
================================================================================
MOGHARE360 V1 — Canonical Database Extensions (additive only)
Adds mirror/API payload columns without DROP/TRUNCATE.
================================================================================
*/
SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_type') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD request_type NVARCHAR(80) NULL;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_payload_json') IS NULL
        ALTER TABLE dbo.erp_customer_online_requests ADD request_payload_json NVARCHAR(MAX) NULL;
END;
GO

PRINT N'V1 canonical extensions applied.';
GO
