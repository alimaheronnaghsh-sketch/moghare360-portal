/*
================================================================================
MOGHARE360 V1 — SaaS Activation Foundation
Script: v1_saas_activation_foundation.sql
Idempotent. No DROP. Execute on moghare360_ERP.
================================================================================
*/

SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.erp_companies', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_companies
    (
        company_id          INT             NOT NULL IDENTITY(1, 1),
        company_code        NVARCHAR(80)    NOT NULL,
        company_name        NVARCHAR(200)   NOT NULL,
        is_active           BIT             NOT NULL CONSTRAINT DF_erp_companies_active DEFAULT (1),
        saas_plan_code      NVARCHAR(80)    NULL,
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_companies_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_companies PRIMARY KEY CLUSTERED (company_id),
        CONSTRAINT UQ_erp_companies_code UNIQUE (company_code)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_company_domains', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_company_domains
    (
        domain_id           INT             NOT NULL IDENTITY(1, 1),
        company_id          INT             NOT NULL,
        domain_name         NVARCHAR(255)   NOT NULL,
        is_primary          BIT             NOT NULL CONSTRAINT DF_erp_company_domains_primary DEFAULT (0),
        is_active           BIT             NOT NULL CONSTRAINT DF_erp_company_domains_active DEFAULT (1),
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_company_domains_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_company_domains PRIMARY KEY CLUSTERED (domain_id),
        CONSTRAINT FK_erp_company_domains_company FOREIGN KEY (company_id) REFERENCES dbo.erp_companies (company_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_company_users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_company_users
    (
        company_user_id     BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NOT NULL,
        user_id             INT             NOT NULL,
        role_code           NVARCHAR(80)    NOT NULL,
        is_active           BIT             NOT NULL CONSTRAINT DF_erp_company_users_active DEFAULT (1),
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_company_users_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_company_users PRIMARY KEY CLUSTERED (company_user_id),
        CONSTRAINT FK_erp_company_users_company FOREIGN KEY (company_id) REFERENCES dbo.erp_companies (company_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_api_request_log', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_api_request_log
    (
        api_log_id          BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NULL,
        endpoint_path       NVARCHAR(300)   NOT NULL,
        http_method         NVARCHAR(20)    NOT NULL,
        status_code         INT             NOT NULL,
        request_note        NVARCHAR(500)   NULL,
        source_ip           NVARCHAR(100)   NULL,
        user_agent          NVARCHAR(500)   NULL,
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_api_log_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_api_request_log PRIMARY KEY CLUSTERED (api_log_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_mirror_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_mirror_requests
    (
        mirror_request_id   BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NOT NULL,
        request_type        NVARCHAR(80)    NOT NULL,
        payload_json        NVARCHAR(MAX)   NULL,
        response_status     INT             NULL,
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_mirror_req_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_mirror_requests PRIMARY KEY CLUSTERED (mirror_request_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_customer_online_requests
    (
        online_request_id   BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NOT NULL,
        customer_name       NVARCHAR(200)   NOT NULL,
        mobile              NVARCHAR(30)    NULL,
        vehicle_plate       NVARCHAR(50)    NULL,
        service_note        NVARCHAR(2000)  NULL,
        request_status      NVARCHAR(80)    NOT NULL CONSTRAINT DF_erp_online_req_status DEFAULT (N'PENDING'),
        source_channel      NVARCHAR(80)    NOT NULL CONSTRAINT DF_erp_online_req_channel DEFAULT (N'MIRROR'),
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_online_req_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_customer_online_requests PRIMARY KEY CLUSTERED (online_request_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_user_access_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_user_access_requests
    (
        access_request_id   BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NOT NULL,
        requester_name      NVARCHAR(200)   NOT NULL,
        requester_mobile    NVARCHAR(30)    NULL,
        requested_role      NVARCHAR(80)    NOT NULL,
        request_status      NVARCHAR(80)    NOT NULL CONSTRAINT DF_erp_access_req_status DEFAULT (N'PENDING'),
        request_note        NVARCHAR(2000)  NULL,
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_access_req_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_user_access_requests PRIMARY KEY CLUSTERED (access_request_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_saas_storage_objects', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_saas_storage_objects
    (
        storage_object_id   BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NOT NULL,
        bucket_name         NVARCHAR(120)   NOT NULL,
        object_key          NVARCHAR(300)   NOT NULL,
        content_type        NVARCHAR(120)   NULL,
        byte_size           BIGINT          NULL,
        storage_path        NVARCHAR(500)   NULL,
        created_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_storage_obj_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_saas_storage_objects PRIMARY KEY CLUSTERED (storage_object_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_deployment_health_checks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_deployment_health_checks
    (
        health_check_id     BIGINT          NOT NULL IDENTITY(1, 1),
        company_id          INT             NULL,
        check_code          NVARCHAR(100)   NOT NULL,
        check_status        NVARCHAR(80)    NOT NULL,
        check_note          NVARCHAR(1000)  NULL,
        checked_at          DATETIME2       NOT NULL CONSTRAINT DF_erp_health_checked DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_deployment_health_checks PRIMARY KEY CLUSTERED (health_check_id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_companies WHERE company_code = N'MOGHAREH_MAIN')
BEGIN
    INSERT INTO dbo.erp_companies (company_code, company_name, saas_plan_code)
    VALUES (N'MOGHAREH_MAIN', N'MOGHAREH MOTORS', N'V1_PRODUCTION');
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.erp_company_domains WHERE domain_name = N'moghareh360.ir')
BEGIN
    DECLARE @cid INT = (SELECT TOP 1 company_id FROM dbo.erp_companies WHERE company_code = N'MOGHAREH_MAIN');
    IF @cid IS NOT NULL
        INSERT INTO dbo.erp_company_domains (company_id, domain_name, is_primary, is_active)
        VALUES (@cid, N'moghareh360.ir', 1, 1);
END;
GO

PRINT N'V1 SaaS activation foundation applied.';
GO
