/*
 * MOGHARE360 Wave 1C-B2.9 — Rollback canonical customer cartable tasks
 * DO NOT execute during Wave 1C-B2.9 implementation phase.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

DECLARE @task_rows BIGINT = 0;
DECLARE @event_rows BIGINT = 0;

IF OBJECT_ID(N'dbo.erp_customer_cartable_task_events', N'U') IS NOT NULL
    SELECT @event_rows = COUNT(*) FROM dbo.erp_customer_cartable_task_events;

IF OBJECT_ID(N'dbo.erp_customer_cartable_tasks', N'U') IS NOT NULL
    SELECT @task_rows = COUNT(*) FROM dbo.erp_customer_cartable_tasks;

IF @event_rows > 0 OR @task_rows > 0
BEGIN
    RAISERROR(
        N'P1C_B29 rollback refused: dependent cartable data exists (tasks=%I64d, events=%I64d). Owner decision required.',
        16,
        1,
        @task_rows,
        @event_rows
    );
    RETURN;
END;
GO

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_customer_cartable_task_events', N'U') IS NOT NULL
    BEGIN
        DROP TABLE dbo.erp_customer_cartable_task_events;
    END;

    IF OBJECT_ID(N'dbo.erp_customer_cartable_tasks', N'U') IS NOT NULL
    BEGIN
        DROP TABLE dbo.erp_customer_cartable_tasks;
    END;

    COMMIT TRANSACTION;
    PRINT N'P1C_B29_customer_cartable_tasks rollback completed.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    DECLARE @err NVARCHAR(4000) = ERROR_MESSAGE();
    RAISERROR(N'P1C_B29 rollback failed: %s', 16, 1, @err);
END CATCH;
GO
