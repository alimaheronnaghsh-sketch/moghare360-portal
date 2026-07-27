/*
 * MOGHARE360 P6 delivery readiness delta rollback.
 * Do not execute automatically. This reverses only objects added by the delta.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.erp_delivery_readiness_checks', N'U') IS NOT NULL
        DROP TABLE dbo.erp_delivery_readiness_checks;

    IF COL_LENGTH(N'dbo.erp_qc_checks', N'created_by_user_id') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN created_by_user_id;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'failed_at') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN failed_at;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'passed_at') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN passed_at;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'completed_at') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN completed_at;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'started_at') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN started_at;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'failure_reason') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN failure_reason;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'final_note') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN final_note;
    IF COL_LENGTH(N'dbo.erp_qc_checks', N'qc_result') IS NOT NULL
        ALTER TABLE dbo.erp_qc_checks DROP COLUMN qc_result;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF XACT_STATE() <> 0
        ROLLBACK TRANSACTION;
    THROW;
END CATCH;
