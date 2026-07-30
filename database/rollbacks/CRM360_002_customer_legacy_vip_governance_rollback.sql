/*
  CRM360_002_customer_legacy_vip_governance_rollback.sql
  Rollback for CRM360_002 — drops only tables/columns added by CRM360_002
*/
USE [moghare360_ERP];
GO

IF OBJECT_ID(N'dbo.crm360_vip_requests', N'U') IS NOT NULL DROP TABLE dbo.crm360_vip_requests;
GO
IF OBJECT_ID(N'dbo.crm360_vip_rules', N'U') IS NOT NULL DROP TABLE dbo.crm360_vip_rules;
GO
IF OBJECT_ID(N'dbo.crm360_import_rows', N'U') IS NOT NULL DROP TABLE dbo.crm360_import_rows;
GO
IF OBJECT_ID(N'dbo.crm360_import_batches', N'U') IS NOT NULL DROP TABLE dbo.crm360_import_batches;
GO

IF COL_LENGTH(N'dbo.crm360_reception_cases', N'source_ref_text') IS NOT NULL
BEGIN
  ALTER TABLE dbo.crm360_reception_cases DROP COLUMN source_ref_text;
END
GO
