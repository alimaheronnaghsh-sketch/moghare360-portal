/*
 * MOGHARE360 Wave 1C-B4.1-B — Immutable issued estimate versions (additive, idempotent)
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

DECLARE @migration_status NVARCHAR(200) = N'P1C_B41_immutable_estimate_versions: START';
PRINT @migration_status;
GO

IF OBJECT_ID(N'dbo.erp_estimates', N'U') IS NULL
BEGIN
    RAISERROR(N'P1C_B41: required table dbo.erp_estimates is missing.', 16, 1);
    RETURN;
END;
GO

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_estimate_versions
        (
            estimate_version_id         BIGINT          NOT NULL IDENTITY(1, 1),
            estimate_id                 BIGINT          NOT NULL,
            version_number              INT             NOT NULL,
            jobcard_id                  BIGINT          NOT NULL,
            customer_id                 BIGINT          NULL,
            online_request_id           BIGINT          NULL,
            subtotal_amount             DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_versions_subtotal DEFAULT (0),
            discount_amount             DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_versions_discount DEFAULT (0),
            tax_amount                  DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_versions_tax DEFAULT (0),
            total_amount                DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_versions_total DEFAULT (0),
            currency_code               NVARCHAR(10)    NOT NULL CONSTRAINT DF_erp_estimate_versions_currency DEFAULT (N'IRR'),
            issue_reason                NVARCHAR(100)   NULL,
            version_status              NVARCHAR(50)    NOT NULL,
            content_hash                NVARCHAR(128)   NOT NULL,
            secure_token_hash           NVARCHAR(128)   NULL,
            secure_token_expires_at     DATETIME2       NULL,
            superseded_by_version_id    BIGINT          NULL,
            issued_at                   DATETIME2       NOT NULL CONSTRAINT DF_erp_estimate_versions_issued DEFAULT (SYSUTCDATETIME()),
            viewed_at                   DATETIME2       NULL,
            decided_at                  DATETIME2       NULL,
            decision_channel            NVARCHAR(50)    NULL,
            customer_note               NVARCHAR(1000)  NULL,
            created_at                  DATETIME2       NOT NULL CONSTRAINT DF_erp_estimate_versions_created DEFAULT (SYSUTCDATETIME()),
            created_by_actor_type       NVARCHAR(30)    NOT NULL,
            created_by_actor_id         NVARCHAR(100)   NULL,
            CONSTRAINT PK_erp_estimate_versions PRIMARY KEY CLUSTERED (estimate_version_id),
            CONSTRAINT CK_erp_estimate_versions_status CHECK (
                version_status IN (
                    N'ISSUED',
                    N'VIEWED',
                    N'ACCEPTED',
                    N'REJECTED',
                    N'SUPERSEDED',
                    N'CANCELLED'
                )
            ),
            CONSTRAINT CK_erp_estimate_versions_version_positive CHECK (version_number > 0)
        );
    END;

    IF OBJECT_ID(N'dbo.erp_estimate_version_items', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_estimate_version_items
        (
            estimate_version_item_id    BIGINT          NOT NULL IDENTITY(1, 1),
            estimate_version_id         BIGINT          NOT NULL,
            source_estimate_item_id     BIGINT          NULL,
            line_number                 INT             NOT NULL,
            item_type                   NVARCHAR(50)    NOT NULL,
            item_title                  NVARCHAR(300)   NOT NULL,
            item_description            NVARCHAR(MAX)   NULL,
            quantity                    DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_version_items_qty DEFAULT (1),
            unit_name                   NVARCHAR(30)    NOT NULL CONSTRAINT DF_erp_estimate_version_items_unit DEFAULT (N'عدد'),
            unit_price                  DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_version_items_price DEFAULT (0),
            line_discount               DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_version_items_discount DEFAULT (0),
            line_total                  DECIMAL(18, 2)  NOT NULL CONSTRAINT DF_erp_estimate_version_items_total DEFAULT (0),
            item_reference              NVARCHAR(100)   NULL,
            created_at                  DATETIME2       NOT NULL CONSTRAINT DF_erp_estimate_version_items_created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT PK_erp_estimate_version_items PRIMARY KEY CLUSTERED (estimate_version_item_id),
            CONSTRAINT CK_erp_estimate_version_items_line_positive CHECK (line_number > 0)
        );
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_versions_estimate', N'F') IS NULL
       AND OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_versions
            ADD CONSTRAINT FK_erp_estimate_versions_estimate
                FOREIGN KEY (estimate_id) REFERENCES dbo.erp_estimates (estimate_id);
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_versions_superseded', N'F') IS NULL
       AND OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_versions
            ADD CONSTRAINT FK_erp_estimate_versions_superseded
                FOREIGN KEY (superseded_by_version_id) REFERENCES dbo.erp_estimate_versions (estimate_version_id);
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_version_items_version', N'F') IS NULL
       AND OBJECT_ID(N'dbo.erp_estimate_version_items', N'U') IS NOT NULL
       AND OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_version_items
            ADD CONSTRAINT FK_erp_estimate_version_items_version
                FOREIGN KEY (estimate_version_id) REFERENCES dbo.erp_estimate_versions (estimate_version_id);
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'estimate_version_id') IS NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals ADD estimate_version_id BIGINT NULL;
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'content_hash') IS NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals ADD content_hash NVARCHAR(128) NULL;
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'decision_channel') IS NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals ADD decision_channel NVARCHAR(50) NULL;
    END;

    IF COL_LENGTH(N'dbo.erp_estimate_events', N'estimate_version_id') IS NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_events ADD estimate_version_id BIGINT NULL;
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_approvals_version', N'F') IS NULL
       AND COL_LENGTH(N'dbo.erp_estimate_approvals', N'estimate_version_id') IS NOT NULL
       AND OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_approvals
            ADD CONSTRAINT FK_erp_estimate_approvals_version
                FOREIGN KEY (estimate_version_id) REFERENCES dbo.erp_estimate_versions (estimate_version_id);
    END;

    IF OBJECT_ID(N'dbo.FK_erp_estimate_events_version', N'F') IS NULL
       AND COL_LENGTH(N'dbo.erp_estimate_events', N'estimate_version_id') IS NOT NULL
       AND OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.erp_estimate_events
            ADD CONSTRAINT FK_erp_estimate_events_version
                FOREIGN KEY (estimate_version_id) REFERENCES dbo.erp_estimate_versions (estimate_version_id);
    END;

    COMMIT TRANSACTION;
    PRINT N'P1C_B41_immutable_estimate_versions tables/columns applied.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;
    DECLARE @err NVARCHAR(4000) = ERROR_MESSAGE();
    RAISERROR(N'P1C_B41 migration failed: %s', 16, 1, @err);
END CATCH;
GO

IF OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'UX_erp_estimate_versions_estimate_version_number'
          AND object_id = OBJECT_ID(N'dbo.erp_estimate_versions')
    )
    BEGIN
        CREATE UNIQUE INDEX UX_erp_estimate_versions_estimate_version_number
            ON dbo.erp_estimate_versions (estimate_id, version_number);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_estimate_versions_token_hash'
          AND object_id = OBJECT_ID(N'dbo.erp_estimate_versions')
    )
    BEGIN
        CREATE INDEX IX_erp_estimate_versions_token_hash
            ON dbo.erp_estimate_versions (secure_token_hash)
            WHERE secure_token_hash IS NOT NULL;
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_estimate_versions_active_lookup'
          AND object_id = OBJECT_ID(N'dbo.erp_estimate_versions')
    )
    BEGIN
        CREATE INDEX IX_erp_estimate_versions_active_lookup
            ON dbo.erp_estimate_versions (estimate_id, version_status, issued_at DESC);
    END;
END;
GO

IF OBJECT_ID(N'dbo.erp_estimate_version_items', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_estimate_version_items_version_line'
          AND object_id = OBJECT_ID(N'dbo.erp_estimate_version_items')
    )
    BEGIN
        CREATE INDEX IX_erp_estimate_version_items_version_line
            ON dbo.erp_estimate_version_items (estimate_version_id, line_number);
    END;
END;
GO

IF COL_LENGTH(N'dbo.erp_estimate_approvals', N'estimate_version_id') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'UX_erp_estimate_approvals_version_decision'
          AND object_id = OBJECT_ID(N'dbo.erp_estimate_approvals')
    )
    BEGIN
        CREATE UNIQUE INDEX UX_erp_estimate_approvals_version_decision
            ON dbo.erp_estimate_approvals (estimate_version_id)
            WHERE estimate_version_id IS NOT NULL;
    END;
END;
GO

IF OBJECT_ID(N'dbo.TR_erp_estimate_version_items_block_update', N'TR') IS NULL
   AND OBJECT_ID(N'dbo.erp_estimate_version_items', N'U') IS NOT NULL
BEGIN
    EXEC(N'
    CREATE TRIGGER dbo.TR_erp_estimate_version_items_block_update
    ON dbo.erp_estimate_version_items
    AFTER UPDATE
    AS
    BEGIN
        SET NOCOUNT ON;
        RAISERROR(N''Immutable estimate version items cannot be updated.'', 16, 1);
        ROLLBACK TRANSACTION;
    END;
    ');
END;
GO

IF OBJECT_ID(N'dbo.TR_erp_estimate_version_items_block_delete', N'TR') IS NULL
   AND OBJECT_ID(N'dbo.erp_estimate_version_items', N'U') IS NOT NULL
BEGIN
    EXEC(N'
    CREATE TRIGGER dbo.TR_erp_estimate_version_items_block_delete
    ON dbo.erp_estimate_version_items
    AFTER DELETE
    AS
    BEGIN
        SET NOCOUNT ON;
        RAISERROR(N''Immutable estimate version items cannot be deleted.'', 16, 1);
        ROLLBACK TRANSACTION;
    END;
    ');
END;
GO

IF OBJECT_ID(N'dbo.TR_erp_estimate_versions_block_content_update', N'TR') IS NULL
   AND OBJECT_ID(N'dbo.erp_estimate_versions', N'U') IS NOT NULL
BEGIN
    EXEC(N'
    CREATE TRIGGER dbo.TR_erp_estimate_versions_block_content_update
    ON dbo.erp_estimate_versions
    AFTER UPDATE
    AS
    BEGIN
        SET NOCOUNT ON;
        IF EXISTS (
            SELECT 1
            FROM inserted i
            INNER JOIN deleted d ON d.estimate_version_id = i.estimate_version_id
            WHERE i.estimate_id <> d.estimate_id
               OR i.version_number <> d.version_number
               OR i.jobcard_id <> d.jobcard_id
               OR ISNULL(i.customer_id, -1) <> ISNULL(d.customer_id, -1)
               OR ISNULL(i.online_request_id, -1) <> ISNULL(d.online_request_id, -1)
               OR i.subtotal_amount <> d.subtotal_amount
               OR i.discount_amount <> d.discount_amount
               OR i.tax_amount <> d.tax_amount
               OR i.total_amount <> d.total_amount
               OR i.currency_code <> d.currency_code
               OR ISNULL(i.issue_reason, N'''') <> ISNULL(d.issue_reason, N'''')
               OR i.content_hash <> d.content_hash
               OR ISNULL(i.secure_token_hash, N'''') <> ISNULL(d.secure_token_hash, N'''')
               OR ISNULL(i.secure_token_expires_at, ''19000101'') <> ISNULL(d.secure_token_expires_at, ''19000101'')
               OR i.issued_at <> d.issued_at
               OR ISNULL(i.created_by_actor_type, N'''') <> ISNULL(d.created_by_actor_type, N'''')
               OR ISNULL(i.created_by_actor_id, N'''') <> ISNULL(d.created_by_actor_id, N'''')
        )
        BEGIN
            RAISERROR(N''Immutable estimate version content cannot be changed.'', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END
    END;
    ');
END;
GO

PRINT N'P1C_B41_immutable_estimate_versions migration complete.';
GO
