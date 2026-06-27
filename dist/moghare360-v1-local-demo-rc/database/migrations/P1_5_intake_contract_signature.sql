/*
 * MOGHARE360 P1.5 — Intake contract + digital signature (non-destructive, idempotent)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.erp_intake_contracts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_intake_contracts
    (
        contract_id             BIGINT          NOT NULL IDENTITY(1, 1),
        contract_version        NVARCHAR(50)    NOT NULL CONSTRAINT DF_erp_intake_contract_version DEFAULT (N'MOGHARE360-INTAKE-V1'),
        online_request_id       BIGINT          NULL,
        jobcard_id              BIGINT          NULL,
        customer_id             BIGINT          NULL,
        vehicle_id              BIGINT          NULL,
        mobile                  NVARCHAR(20)    NOT NULL,
        contract_status         NVARCHAR(50)    NOT NULL CONSTRAINT DF_erp_intake_contract_status DEFAULT (N'DRAFT'),
        contract_title          NVARCHAR(300)   NULL,
        contract_body_hash      NVARCHAR(128)   NOT NULL,
        contract_data_json      NVARCHAR(MAX)   NULL,
        secure_token_hash       NVARCHAR(128)   NULL,
        secure_token_expires_at DATETIME2       NULL,
        sent_at                 DATETIME2       NULL,
        viewed_at               DATETIME2       NULL,
        signed_at               DATETIME2       NULL,
        cancelled_at            DATETIME2       NULL,
        created_at              DATETIME2       NOT NULL CONSTRAINT DF_erp_intake_contract_created DEFAULT (SYSUTCDATETIME()),
        updated_at              DATETIME2       NULL,
        created_by_user_id      BIGINT          NULL,
        manager_override        BIT             NOT NULL CONSTRAINT DF_erp_intake_contract_override DEFAULT (0),
        manager_override_reason NVARCHAR(1000)  NULL,
        CONSTRAINT PK_erp_intake_contracts PRIMARY KEY CLUSTERED (contract_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_intake_contract_signatures', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_intake_contract_signatures
    (
        signature_id            BIGINT          NOT NULL IDENTITY(1, 1),
        contract_id             BIGINT          NOT NULL,
        mobile                  NVARCHAR(20)    NOT NULL,
        otp_verified            BIT             NOT NULL CONSTRAINT DF_erp_intake_sig_otp DEFAULT (0),
        otp_verified_at         DATETIME2       NULL,
        signature_image_data    NVARCHAR(MAX)   NULL,
        signature_hash          NVARCHAR(128)   NULL,
        signer_ip               NVARCHAR(100)   NULL,
        signer_user_agent       NVARCHAR(1000)  NULL,
        signed_contract_hash    NVARCHAR(128)   NULL,
        signed_at               DATETIME2       NULL,
        created_at              DATETIME2       NOT NULL CONSTRAINT DF_erp_intake_sig_created DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_erp_intake_contract_signatures PRIMARY KEY CLUSTERED (signature_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_intake_contract_events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.erp_intake_contract_events
    (
        event_id                BIGINT          NOT NULL IDENTITY(1, 1),
        contract_id             BIGINT          NOT NULL,
        event_name              NVARCHAR(100)   NOT NULL,
        event_note              NVARCHAR(1000)  NULL,
        event_ip                NVARCHAR(100)   NULL,
        event_user_agent        NVARCHAR(1000)  NULL,
        created_at              DATETIME2       NOT NULL CONSTRAINT DF_erp_intake_contract_evt_created DEFAULT (SYSUTCDATETIME()),
        created_by_user_id      BIGINT          NULL,
        CONSTRAINT PK_erp_intake_contract_events PRIMARY KEY CLUSTERED (event_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_jobcards', N'contract_status') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD contract_status NVARCHAR(50) NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'intake_contract_id') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD intake_contract_id BIGINT NULL;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'contract_signed_at') IS NULL
        ALTER TABLE dbo.erp_jobcards ADD contract_signed_at DATETIME2 NULL;
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_intake_contracts_jobcard' AND object_id = OBJECT_ID(N'dbo.erp_intake_contracts'))
    CREATE INDEX IX_erp_intake_contracts_jobcard ON dbo.erp_intake_contracts (jobcard_id);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_intake_contracts_status' AND object_id = OBJECT_ID(N'dbo.erp_intake_contracts'))
    CREATE INDEX IX_erp_intake_contracts_status ON dbo.erp_intake_contracts (contract_status, created_at DESC);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_intake_contracts_token_hash' AND object_id = OBJECT_ID(N'dbo.erp_intake_contracts'))
    CREATE INDEX IX_erp_intake_contracts_token_hash ON dbo.erp_intake_contracts (secure_token_hash);
GO

PRINT N'P1.5 intake contract signature migration applied.';
GO
