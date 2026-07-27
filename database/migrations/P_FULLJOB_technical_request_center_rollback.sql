/*
 * MOGHARE360 Full Job Lifecycle — manual rollback for additive request center objects.
 * Do not run if Owner UAT evidence must be preserved.
 */
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.FK_erp_external_service_requests_request', N'F') IS NOT NULL
        ALTER TABLE dbo.erp_external_service_requests DROP CONSTRAINT FK_erp_external_service_requests_request;

    IF OBJECT_ID(N'dbo.FK_erp_jobcard_technical_request_events_request', N'F') IS NOT NULL
        ALTER TABLE dbo.erp_jobcard_technical_request_events DROP CONSTRAINT FK_erp_jobcard_technical_request_events_request;

    IF OBJECT_ID(N'dbo.FK_erp_jobcard_technical_requests_jobcard', N'F') IS NOT NULL
        ALTER TABLE dbo.erp_jobcard_technical_requests DROP CONSTRAINT FK_erp_jobcard_technical_requests_jobcard;

    IF OBJECT_ID(N'dbo.FK_erp_jobcard_assignments_jobcard', N'F') IS NOT NULL
        ALTER TABLE dbo.erp_jobcard_assignments DROP CONSTRAINT FK_erp_jobcard_assignments_jobcard;

    IF OBJECT_ID(N'dbo.erp_external_service_requests', N'U') IS NOT NULL
        DROP TABLE dbo.erp_external_service_requests;

    IF OBJECT_ID(N'dbo.erp_jobcard_technical_request_events', N'U') IS NOT NULL
        DROP TABLE dbo.erp_jobcard_technical_request_events;

    IF OBJECT_ID(N'dbo.erp_jobcard_technical_requests', N'U') IS NOT NULL
        DROP TABLE dbo.erp_jobcard_technical_requests;

    IF OBJECT_ID(N'dbo.erp_jobcard_assignments', N'U') IS NOT NULL
        DROP TABLE dbo.erp_jobcard_assignments;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    DECLARE @err NVARCHAR(4000) = ERROR_MESSAGE();
    THROW 51091, @err, 1;
END CATCH;
