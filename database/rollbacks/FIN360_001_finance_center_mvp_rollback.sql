/*
  FIN360_001_finance_center_mvp_rollback.sql
  Drops only fin360_ tables.
*/
USE [moghare360_ERP];
GO
IF OBJECT_ID(N'dbo.fin360_settings',N'U') IS NOT NULL DROP TABLE dbo.fin360_settings;
IF OBJECT_ID(N'dbo.fin360_audit_log',N'U') IS NOT NULL DROP TABLE dbo.fin360_audit_log;
IF OBJECT_ID(N'dbo.fin360_cost_calculations',N'U') IS NOT NULL DROP TABLE dbo.fin360_cost_calculations;
IF OBJECT_ID(N'dbo.fin360_import_cases',N'U') IS NOT NULL DROP TABLE dbo.fin360_import_cases;
IF OBJECT_ID(N'dbo.fin360_tax_documents',N'U') IS NOT NULL DROP TABLE dbo.fin360_tax_documents;
IF OBJECT_ID(N'dbo.fin360_tax_rules',N'U') IS NOT NULL DROP TABLE dbo.fin360_tax_rules;
IF OBJECT_ID(N'dbo.fin360_journal_lines',N'U') IS NOT NULL DROP TABLE dbo.fin360_journal_lines;
IF OBJECT_ID(N'dbo.fin360_journal_headers',N'U') IS NOT NULL DROP TABLE dbo.fin360_journal_headers;
IF OBJECT_ID(N'dbo.fin360_posting_rules',N'U') IS NOT NULL DROP TABLE dbo.fin360_posting_rules;
IF OBJECT_ID(N'dbo.fin360_jobcard_settlements',N'U') IS NOT NULL DROP TABLE dbo.fin360_jobcard_settlements;
IF OBJECT_ID(N'dbo.fin360_payment_allocations',N'U') IS NOT NULL DROP TABLE dbo.fin360_payment_allocations;
IF OBJECT_ID(N'dbo.fin360_payments',N'U') IS NOT NULL DROP TABLE dbo.fin360_payments;
IF OBJECT_ID(N'dbo.fin360_document_lines',N'U') IS NOT NULL DROP TABLE dbo.fin360_document_lines;
IF OBJECT_ID(N'dbo.fin360_documents',N'U') IS NOT NULL DROP TABLE dbo.fin360_documents;
IF OBJECT_ID(N'dbo.fin360_cash_accounts',N'U') IS NOT NULL DROP TABLE dbo.fin360_cash_accounts;
IF OBJECT_ID(N'dbo.fin360_parties',N'U') IS NOT NULL DROP TABLE dbo.fin360_parties;
IF OBJECT_ID(N'dbo.fin360_accounts',N'U') IS NOT NULL DROP TABLE dbo.fin360_accounts;
GO
