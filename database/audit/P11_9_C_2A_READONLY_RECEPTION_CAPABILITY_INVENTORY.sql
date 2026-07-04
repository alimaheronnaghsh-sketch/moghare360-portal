/*
 * MOGHARE360 P11.9-C-2A — Read-only Reception Capability Inventory
 * Database: MOGHARE360_ERP (SQL Server)
 *
 * SAFE: SELECT / metadata only. No INSERT, UPDATE, DELETE, EXEC of mutating procs.
 * Run in SSMS against MOGHARE360_ERP and save output for audit attachment.
 */
SET NOCOUNT ON;

IF OBJECT_ID(N'tempdb..#keywords') IS NOT NULL DROP TABLE #keywords;
CREATE TABLE #keywords (kw NVARCHAR(80) NOT NULL PRIMARY KEY);
INSERT INTO #keywords (kw) VALUES
 (N'reception'),(N'intake'),(N'online'),(N'request'),(N'customer'),(N'vehicle'),
 (N'jobcard'),(N'job_card'),(N'contract'),(N'signature'),(N'photo'),(N'media'),
 (N'camera'),(N'diagnostic'),(N'diag'),(N'defect'),(N'fault'),(N'symptom'),
 (N'complaint'),(N'category'),(N'attachment'),(N'document'),(N'agreement'),
 (N'history'),(N'audit'),(N'change'),(N'otp'),(N'plate'),(N'arrival'),(N'checkin');

PRINT N'=== P11.9-C-2A Reception Capability Inventory ===';
PRINT N'Database: ' + DB_NAME();
PRINT N'Timestamp: ' + CONVERT(NVARCHAR(30), SYSUTCDATETIME(), 126);

/* 1) sys.tables search */
PRINT N'--- 1. TABLES (name match) ---';
SELECT t.name AS table_name, SCHEMA_NAME(t.schema_id) AS schema_name
FROM sys.tables t
WHERE EXISTS (
    SELECT 1 FROM #keywords k
    WHERE t.name LIKE N'%' + k.kw + N'%'
)
ORDER BY t.name;

/* 2) sys.views search */
PRINT N'--- 2. VIEWS (name match) ---';
SELECT v.name AS view_name, SCHEMA_NAME(v.schema_id) AS schema_name
FROM sys.views v
WHERE EXISTS (
    SELECT 1 FROM #keywords k
    WHERE v.name LIKE N'%' + k.kw + N'%'
)
ORDER BY v.name;

/* 3) sys.procedures search (metadata only — not executed) */
PRINT N'--- 3. PROCEDURES (name match) ---';
SELECT p.name AS procedure_name, SCHEMA_NAME(p.schema_id) AS schema_name, p.create_date, p.modify_date
FROM sys.procedures p
WHERE EXISTS (
    SELECT 1 FROM #keywords k
    WHERE p.name LIKE N'%' + k.kw + N'%'
)
ORDER BY p.name;

/* 4) sys.columns search on dbo tables */
PRINT N'--- 4. COLUMNS (name match on dbo tables) ---';
SELECT TOP 500
    t.name AS table_name,
    c.name AS column_name,
    ty.name AS data_type,
    c.max_length,
    c.is_nullable
FROM sys.columns c
INNER JOIN sys.tables t ON t.object_id = c.object_id
INNER JOIN sys.types ty ON ty.user_type_id = c.user_type_id
WHERE SCHEMA_NAME(t.schema_id) = N'dbo'
  AND EXISTS (
      SELECT 1 FROM #keywords k
      WHERE c.name LIKE N'%' + k.kw + N'%' OR t.name LIKE N'%' + k.kw + N'%'
  )
ORDER BY t.name, c.column_id;

/* 5) Core reception/intake object existence */
PRINT N'--- 5. CORE OBJECT EXISTENCE ---';
SELECT N'erp_customer_online_requests' AS object_name,
       CASE WHEN OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END AS exists_flag
UNION ALL SELECT N'erp_customer_online_request_history', CASE WHEN OBJECT_ID(N'dbo.erp_customer_online_request_history', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_intake_contracts', CASE WHEN OBJECT_ID(N'dbo.erp_intake_contracts', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_intake_contract_signatures', CASE WHEN OBJECT_ID(N'dbo.erp_intake_contract_signatures', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_intake_contract_events', CASE WHEN OBJECT_ID(N'dbo.erp_intake_contract_events', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_jobcards', CASE WHEN OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_jobcard_change_history', CASE WHEN OBJECT_ID(N'dbo.erp_jobcard_change_history', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_customers', CASE WHEN OBJECT_ID(N'dbo.erp_customers', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_vehicles', CASE WHEN OBJECT_ID(N'dbo.erp_vehicles', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END
UNION ALL SELECT N'erp_customer_vehicle_relations', CASE WHEN OBJECT_ID(N'dbo.erp_customer_vehicle_relations', N'U') IS NOT NULL THEN N'YES' ELSE N'NO' END;

/* 6) P1/P2/P1.5 column probes on live DB */
PRINT N'--- 6. P1/P2/P1.5 COLUMN PROBES ---';
IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
BEGIN
    SELECT
        COL_LENGTH(N'dbo.erp_customer_online_requests', N'customer_id') AS customer_id_col,
        COL_LENGTH(N'dbo.erp_customer_online_requests', N'vehicle_id') AS vehicle_id_col,
        COL_LENGTH(N'dbo.erp_customer_online_requests', N'converted_jobcard_id') AS converted_jobcard_id_col,
        COL_LENGTH(N'dbo.erp_customer_online_requests', N'visit_date') AS visit_date_col,
        COL_LENGTH(N'dbo.erp_customer_online_requests', N'otp_verified') AS otp_verified_col,
        COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_payload_json') AS request_payload_json_col;
END;
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    SELECT
        COL_LENGTH(N'dbo.erp_jobcards', N'reception_notes') AS reception_notes_col,
        COL_LENGTH(N'dbo.erp_jobcards', N'initial_inspection_notes') AS initial_inspection_notes_col,
        COL_LENGTH(N'dbo.erp_jobcards', N'online_request_id') AS online_request_id_col,
        COL_LENGTH(N'dbo.erp_jobcards', N'intake_contract_id') AS intake_contract_id_col,
        COL_LENGTH(N'dbo.erp_jobcards', N'contract_status') AS contract_status_col,
        COL_LENGTH(N'dbo.erp_jobcards', N'diagnosis_summary') AS diagnosis_summary_col;
END;

/* 7) Status / code distribution (read-only aggregates) */
PRINT N'--- 7. ONLINE REQUEST STATUS COUNTS ---';
IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
    SELECT request_status, COUNT(*) AS cnt
    FROM dbo.erp_customer_online_requests WITH (NOLOCK)
    GROUP BY request_status
    ORDER BY cnt DESC;

PRINT N'--- 8. JOBCARD STATUS COUNTS (top) ---';
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
    SELECT TOP 20 jobcard_status, COUNT(*) AS cnt
    FROM dbo.erp_jobcards WITH (NOLOCK)
    GROUP BY jobcard_status
    ORDER BY cnt DESC;

PRINT N'--- 9. INTAKE CONTRACT STATUS COUNTS ---';
IF OBJECT_ID(N'dbo.erp_intake_contracts', N'U') IS NOT NULL
    SELECT contract_status, COUNT(*) AS cnt
    FROM dbo.erp_intake_contracts WITH (NOLOCK)
    GROUP BY contract_status
    ORDER BY cnt DESC;

/* 10) Defect/category table probe (expected often missing) */
PRINT N'--- 10. DEFECT/CATEGORY TABLE PROBE ---';
SELECT t.name AS table_name
FROM sys.tables t
WHERE SCHEMA_NAME(t.schema_id) = N'dbo'
  AND (
      t.name LIKE N'%defect%' OR t.name LIKE N'%fault%' OR t.name LIKE N'%symptom%'
      OR t.name LIKE N'%complaint%' OR t.name LIKE N'%category%code%'
  )
ORDER BY t.name;

/* 11) Media/photo/attachment table probe */
PRINT N'--- 11. MEDIA/ATTACHMENT TABLE PROBE ---';
SELECT t.name AS table_name
FROM sys.tables t
WHERE SCHEMA_NAME(t.schema_id) = N'dbo'
  AND (
      t.name LIKE N'%media%' OR t.name LIKE N'%photo%' OR t.name LIKE N'%attachment%'
      OR t.name LIKE N'%document%' OR t.name LIKE N'%diagnostic%'
  )
ORDER BY t.name;

/* 12) Sample rows — online requests TOP 20 */
PRINT N'--- 12. SAMPLE ONLINE REQUESTS (TOP 20 by id desc) ---';
IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
    SELECT TOP 20
        online_request_id, request_status, customer_name, mobile, vehicle_plate,
        source_channel, converted_jobcard_id, created_at
    FROM dbo.erp_customer_online_requests WITH (NOLOCK)
    ORDER BY online_request_id DESC;

/* 13) Sample intake contracts TOP 20 */
PRINT N'--- 13. SAMPLE INTAKE CONTRACTS (TOP 20) ---';
IF OBJECT_ID(N'dbo.erp_intake_contracts', N'U') IS NOT NULL
    SELECT TOP 20
        contract_id, contract_status, jobcard_id, online_request_id, mobile, signed_at, created_at
    FROM dbo.erp_intake_contracts WITH (NOLOCK)
    ORDER BY contract_id DESC;

/* 14) Sample jobcards reception columns TOP 20 */
PRINT N'--- 14. SAMPLE JOBCARDS RECEPTION FIELDS (TOP 20) ---';
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
    SELECT TOP 20
        jobcard_id, jobcard_number, jobcard_status, customer_id, vehicle_id,
        online_request_id, contract_status, intake_contract_id, reception_notes, created_at
    FROM dbo.erp_jobcards WITH (NOLOCK)
    ORDER BY jobcard_id DESC;

PRINT N'=== END P11.9-C-2A READONLY INVENTORY ===';

DROP TABLE #keywords;
