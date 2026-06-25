/*
================================================================================
MOGHARE360 V1 — Database Verification (read-only checks)
Run: sqlcmd -S .\SQLEXPRESS -d moghare360_ERP -E -i MOGHARE360_V1_DATABASE_VERIFY.sql
================================================================================
*/
SET NOCOUNT ON;

DECLARE @missing INT = 0;

DECLARE @required TABLE (table_name SYSNAME NOT NULL);
INSERT INTO @required (table_name) VALUES
 (N'erp_customers'),(N'erp_customer_phones'),(N'erp_vehicles'),(N'erp_customer_vehicle_relations'),
 (N'erp_jobcards'),(N'erp_service_operations'),(N'erp_jobcard_part_usage'),(N'erp_purchase_requests'),
 (N'erp_payments'),(N'erp_qc_checks'),(N'erp_delivery_controls'),
 (N'erp_companies'),(N'erp_company_domains'),(N'erp_company_users'),
 (N'erp_api_request_log'),(N'erp_mirror_requests'),(N'erp_customer_online_requests'),
 (N'erp_user_access_requests'),(N'erp_saas_storage_objects'),(N'erp_deployment_health_checks'),
 (N'erp_v1_production_run_signoff'),(N'erp_v1_post_run_fix_register');

PRINT N'--- Required table existence ---';
SELECT r.table_name,
       CASE WHEN t.object_id IS NULL THEN N'MISSING' ELSE N'OK' END AS status
FROM @required r
LEFT JOIN sys.tables t ON t.name = r.table_name AND SCHEMA_NAME(t.schema_id)=N'dbo'
ORDER BY r.table_name;

SELECT @missing = COUNT(*)
FROM @required r
LEFT JOIN sys.tables t ON t.name = r.table_name AND SCHEMA_NAME(t.schema_id)=N'dbo'
WHERE t.object_id IS NULL;

PRINT N'--- Default tenant ---';
SELECT TOP 1 company_id, company_code, company_name, is_active
FROM dbo.erp_companies
WHERE company_code = N'MOGHAREH_MAIN';

PRINT N'--- Online request payload columns ---';
SELECT COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_payload_json') AS request_payload_json_col,
       COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_type') AS request_type_col;

PRINT N'--- Row counts (test vs operational signal) ---';
SELECT N'erp_customers' AS tbl, COUNT(*) AS cnt FROM dbo.erp_customers
UNION ALL SELECT N'erp_jobcards', COUNT(*) FROM dbo.erp_jobcards
UNION ALL SELECT N'erp_payments', COUNT(*) FROM dbo.erp_payments
UNION ALL SELECT N'erp_customer_online_requests', COUNT(*) FROM dbo.erp_customer_online_requests
UNION ALL SELECT N'TEST_V1_RUN_DO_NOT_USE', COUNT(*) FROM dbo.erp_customer_online_requests WHERE customer_name = N'TEST_V1_RUN_DO_NOT_USE';

PRINT N'--- Legacy MySQL tables (must be absent) ---';
SELECT name FROM sys.tables
WHERE name IN (N'portal_customers_staging', N'portal_service_requests_staging');

IF @missing > 0
BEGIN
    PRINT N'VERIFY RESULT: FAIL — missing required tables = ' + CAST(@missing AS NVARCHAR(10));
END
ELSE
BEGIN
    PRINT N'VERIFY RESULT: PASS — all required V1 tables present';
END
GO
