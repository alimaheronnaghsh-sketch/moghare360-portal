/*
 * MOGHARE360 Wave 1C-B2.9 — Canonical customer cartable tasks (additive, idempotent)
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

DECLARE @migration_status NVARCHAR(200) = N'P1C_B29_customer_cartable_tasks: START';
PRINT @migration_status;
GO

IF OBJECT_ID(N'dbo.erp_customer_cartable_tasks', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_customer_cartable_tasks', N'task_id') IS NULL
       OR COL_LENGTH(N'dbo.erp_customer_cartable_tasks', N'source_entity_id') IS NULL
       OR COL_LENGTH(N'dbo.erp_customer_cartable_tasks', N'is_active') IS NULL
    BEGIN
        RAISERROR(N'P1C_B29: incompatible existing dbo.erp_customer_cartable_tasks schema detected. Owner decision required.', 16, 1);
        RETURN;
    END;
END;
GO

IF OBJECT_ID(N'dbo.erp_customer_cartable_task_events', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH(N'dbo.erp_customer_cartable_task_events', N'event_id') IS NULL
       OR COL_LENGTH(N'dbo.erp_customer_cartable_task_events', N'task_id') IS NULL
    BEGIN
        RAISERROR(N'P1C_B29: incompatible existing dbo.erp_customer_cartable_task_events schema detected. Owner decision required.', 16, 1);
        RETURN;
    END;
END;
GO

BEGIN TRY
    SET ANSI_NULLS ON;
    SET QUOTED_IDENTIFIER ON;
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_customer_cartable_tasks', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_customer_cartable_tasks
        (
            task_id                     BIGINT          NOT NULL IDENTITY(1, 1),
            customer_id                 INT             NULL,
            customer_mobile_normalized  NVARCHAR(20)    NULL,
            task_type                   NVARCHAR(50)    NOT NULL,
            title                       NVARCHAR(200)   NOT NULL,
            message                     NVARCHAR(1000)  NOT NULL,
            priority                    TINYINT         NOT NULL CONSTRAINT DF_erp_customer_cartable_tasks_priority DEFAULT (50),
            status                      NVARCHAR(20)    NOT NULL,
            is_active                   BIT             NOT NULL CONSTRAINT DF_erp_customer_cartable_tasks_is_active DEFAULT (1),
            source_module               NVARCHAR(50)    NOT NULL,
            source_entity_type          NVARCHAR(50)    NOT NULL,
            source_entity_id            NVARCHAR(100)   NOT NULL,
            online_request_id           BIGINT          NULL,
            intake_id                   BIGINT          NULL,
            jobcard_id                  INT             NULL,
            contract_id                 BIGINT          NULL,
            estimate_id                 BIGINT          NULL,
            invoice_id                  BIGINT          NULL,
            action_route                NVARCHAR(300)   NULL,
            action_token_hash           NVARCHAR(128)   NULL,
            action_expires_at           DATETIME2       NULL,
            due_at                      DATETIME2       NULL,
            created_at                  DATETIME2       NOT NULL CONSTRAINT DF_erp_customer_cartable_tasks_created DEFAULT (SYSUTCDATETIME()),
            opened_at                   DATETIME2       NULL,
            completed_at                DATETIME2       NULL,
            cancelled_at                DATETIME2       NULL,
            expired_at                  DATETIME2       NULL,
            created_by_actor_type       NVARCHAR(30)    NOT NULL,
            created_by_actor_id         NVARCHAR(100)   NULL,
            completed_channel           NVARCHAR(30)    NULL,
            updated_at                  DATETIME2       NOT NULL CONSTRAINT DF_erp_customer_cartable_tasks_updated DEFAULT (SYSUTCDATETIME()),
            row_version                 ROWVERSION      NOT NULL,
            CONSTRAINT PK_erp_customer_cartable_tasks PRIMARY KEY CLUSTERED (task_id),
            CONSTRAINT CK_erp_customer_cartable_tasks_status CHECK (status IN (N'PENDING', N'OPENED', N'COMPLETED', N'CANCELLED', N'EXPIRED')),
            CONSTRAINT CK_erp_customer_cartable_tasks_priority CHECK (priority BETWEEN 0 AND 100),
            CONSTRAINT CK_erp_customer_cartable_tasks_active_status CHECK (
                (is_active = 1 AND status IN (N'PENDING', N'OPENED'))
                OR (is_active = 0 AND status IN (N'COMPLETED', N'CANCELLED', N'EXPIRED'))
            ),
            CONSTRAINT CK_erp_customer_cartable_tasks_owner CHECK (
                customer_id IS NOT NULL
                OR (customer_mobile_normalized IS NOT NULL AND LTRIM(RTRIM(customer_mobile_normalized)) <> N'')
            ),
            CONSTRAINT CK_erp_customer_cartable_tasks_action_route CHECK (
                action_route IS NULL
                OR (
                    action_route NOT LIKE N'%?t=%'
                    AND action_route NOT LIKE N'%token=%'
                    AND action_route NOT LIKE N'%access_token=%'
                )
            )
        );
    END;

    IF OBJECT_ID(N'dbo.erp_customer_cartable_task_events', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.erp_customer_cartable_task_events
        (
            event_id                BIGINT          NOT NULL IDENTITY(1, 1),
            task_id                 BIGINT          NOT NULL,
            event_type              NVARCHAR(50)    NOT NULL,
            old_status              NVARCHAR(20)    NULL,
            new_status              NVARCHAR(20)    NULL,
            actor_type              NVARCHAR(30)    NOT NULL,
            actor_id                NVARCHAR(100)   NULL,
            event_at                DATETIME2       NOT NULL CONSTRAINT DF_erp_customer_cartable_task_events_event_at DEFAULT (SYSUTCDATETIME()),
            event_metadata_json     NVARCHAR(MAX)   NULL,
            CONSTRAINT PK_erp_customer_cartable_task_events PRIMARY KEY CLUSTERED (event_id),
            CONSTRAINT CK_erp_customer_cartable_task_events_metadata_json CHECK (
                event_metadata_json IS NULL OR ISJSON(event_metadata_json) = 1
            )
        );
    END;

    IF OBJECT_ID(N'dbo.erp_customers', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_customer'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_customer
            FOREIGN KEY (customer_id) REFERENCES dbo.erp_customers (customer_id);
    END;

    IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_online_request'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_online_request
            FOREIGN KEY (online_request_id) REFERENCES dbo.erp_customer_online_requests (online_request_id);
    END;

    IF OBJECT_ID(N'dbo.erp_intake_contracts', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_contract'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_contract
            FOREIGN KEY (contract_id) REFERENCES dbo.erp_intake_contracts (contract_id);
    END;

    IF OBJECT_ID(N'dbo.erp_customer_intakes', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_intake'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_intake
            FOREIGN KEY (intake_id) REFERENCES dbo.erp_customer_intakes (intake_id);
    END;

    IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_jobcard'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_jobcard
            FOREIGN KEY (jobcard_id) REFERENCES dbo.erp_jobcards (jobcard_id);
    END;

    IF OBJECT_ID(N'dbo.erp_estimates', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_estimate'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_estimate
            FOREIGN KEY (estimate_id) REFERENCES dbo.erp_estimates (estimate_id);
    END;

    IF OBJECT_ID(N'dbo.erp_final_invoices', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_tasks_invoice'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_tasks
            ADD CONSTRAINT FK_erp_customer_cartable_tasks_invoice
            FOREIGN KEY (invoice_id) REFERENCES dbo.erp_final_invoices (final_invoice_id);
    END;

    IF OBJECT_ID(N'dbo.erp_customer_cartable_tasks', N'U') IS NOT NULL
       AND OBJECT_ID(N'dbo.erp_customer_cartable_task_events', N'U') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM sys.foreign_keys
           WHERE name = N'FK_erp_customer_cartable_task_events_task'
             AND parent_object_id = OBJECT_ID(N'dbo.erp_customer_cartable_task_events')
       )
    BEGIN
        ALTER TABLE dbo.erp_customer_cartable_task_events
            ADD CONSTRAINT FK_erp_customer_cartable_task_events_task
            FOREIGN KEY (task_id) REFERENCES dbo.erp_customer_cartable_tasks (task_id);
    END;

    COMMIT TRANSACTION;
    PRINT N'P1C_B29_customer_cartable_tasks tables/constraints applied successfully.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    DECLARE @err NVARCHAR(4000) = ERROR_MESSAGE();
    RAISERROR(N'P1C_B29 migration failed: %s', 16, 1, @err);
END CATCH;
GO

SET NOCOUNT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.erp_customer_cartable_tasks', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'UX_erp_customer_cartable_tasks_active_idempotency'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
    )
    BEGIN
        CREATE UNIQUE INDEX UX_erp_customer_cartable_tasks_active_idempotency
            ON dbo.erp_customer_cartable_tasks (source_module, source_entity_type, source_entity_id, task_type)
            WHERE is_active = 1;
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_customer_cartable_tasks_customer_active_inbox'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
    )
    BEGIN
        CREATE INDEX IX_erp_customer_cartable_tasks_customer_active_inbox
            ON dbo.erp_customer_cartable_tasks (customer_id, is_active, priority, due_at, created_at);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_customer_cartable_tasks_mobile_active'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
    )
    BEGIN
        CREATE INDEX IX_erp_customer_cartable_tasks_mobile_active
            ON dbo.erp_customer_cartable_tasks (customer_mobile_normalized, is_active, created_at);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_customer_cartable_tasks_type_status'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
    )
    BEGIN
        CREATE INDEX IX_erp_customer_cartable_tasks_type_status
            ON dbo.erp_customer_cartable_tasks (task_type, status, created_at DESC);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_customer_cartable_tasks_online_request'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
    )
    BEGIN
        CREATE INDEX IX_erp_customer_cartable_tasks_online_request
            ON dbo.erp_customer_cartable_tasks (online_request_id);
    END;

    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_customer_cartable_tasks_contract'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_tasks')
    )
    BEGIN
        CREATE INDEX IX_erp_customer_cartable_tasks_contract
            ON dbo.erp_customer_cartable_tasks (contract_id);
    END;
END;
GO

IF OBJECT_ID(N'dbo.erp_customer_cartable_task_events', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes
        WHERE name = N'IX_erp_customer_cartable_task_events_task_time'
          AND object_id = OBJECT_ID(N'dbo.erp_customer_cartable_task_events')
    )
    BEGIN
        CREATE INDEX IX_erp_customer_cartable_task_events_task_time
            ON dbo.erp_customer_cartable_task_events (task_id, event_at);
    END;
END;
GO

PRINT N'P1C_B29_customer_cartable_tasks indexes applied successfully.';
GO

-- Post-migration verification (read-only)
SELECT
    t.name AS table_name,
    CASE WHEN t.object_id IS NOT NULL THEN N'yes' ELSE N'no' END AS exists_flag
FROM (VALUES
    (N'erp_customer_cartable_tasks'),
    (N'erp_customer_cartable_task_events')
) AS expected(table_name)
LEFT JOIN sys.tables t ON t.name = expected.table_name AND t.schema_id = SCHEMA_ID(N'dbo');

SELECT
    c.TABLE_NAME,
    c.COLUMN_NAME,
    c.DATA_TYPE,
    c.IS_NULLABLE,
    c.CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS c
WHERE c.TABLE_SCHEMA = N'dbo'
  AND c.TABLE_NAME IN (N'erp_customer_cartable_tasks', N'erp_customer_cartable_task_events')
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;

SELECT name AS constraint_name, type_desc
FROM sys.objects
WHERE parent_object_id IN (
    OBJECT_ID(N'dbo.erp_customer_cartable_tasks'),
    OBJECT_ID(N'dbo.erp_customer_cartable_task_events')
)
AND type IN (N'C', N'F', N'PK', N'UQ');

SELECT i.name AS index_name, t.name AS table_name, i.is_unique, i.has_filter, i.filter_definition
FROM sys.indexes i
INNER JOIN sys.tables t ON t.object_id = i.object_id
WHERE t.name IN (N'erp_customer_cartable_tasks', N'erp_customer_cartable_task_events')
  AND i.name IS NOT NULL
ORDER BY t.name, i.name;
GO
