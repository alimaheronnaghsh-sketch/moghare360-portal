/*
  CRM360_001_reception_crm_completion_rollback.sql
  Drops only crm360_ tables.
*/
USE [moghare360_ERP];
GO
IF OBJECT_ID(N'dbo.crm360_audit_log',N'U') IS NOT NULL DROP TABLE dbo.crm360_audit_log;
IF OBJECT_ID(N'dbo.crm360_sms_campaign_recipients',N'U') IS NOT NULL DROP TABLE dbo.crm360_sms_campaign_recipients;
IF OBJECT_ID(N'dbo.crm360_sms_campaigns',N'U') IS NOT NULL DROP TABLE dbo.crm360_sms_campaigns;
IF OBJECT_ID(N'dbo.crm360_promotion_assignments',N'U') IS NOT NULL DROP TABLE dbo.crm360_promotion_assignments;
IF OBJECT_ID(N'dbo.crm360_promotions',N'U') IS NOT NULL DROP TABLE dbo.crm360_promotions;
IF OBJECT_ID(N'dbo.crm360_return_pipeline',N'U') IS NOT NULL DROP TABLE dbo.crm360_return_pipeline;
IF OBJECT_ID(N'dbo.crm360_service_reminders',N'U') IS NOT NULL DROP TABLE dbo.crm360_service_reminders;
IF OBJECT_ID(N'dbo.crm360_customer_club',N'U') IS NOT NULL DROP TABLE dbo.crm360_customer_club;
IF OBJECT_ID(N'dbo.crm360_complaints',N'U') IS NOT NULL DROP TABLE dbo.crm360_complaints;
IF OBJECT_ID(N'dbo.crm360_satisfaction_surveys',N'U') IS NOT NULL DROP TABLE dbo.crm360_satisfaction_surveys;
IF OBJECT_ID(N'dbo.crm360_customer_cartable',N'U') IS NOT NULL DROP TABLE dbo.crm360_customer_cartable;
IF OBJECT_ID(N'dbo.crm360_case_documents',N'U') IS NOT NULL DROP TABLE dbo.crm360_case_documents;
IF OBJECT_ID(N'dbo.crm360_reception_cases',N'U') IS NOT NULL DROP TABLE dbo.crm360_reception_cases;
IF OBJECT_ID(N'dbo.crm360_vehicle_profiles',N'U') IS NOT NULL DROP TABLE dbo.crm360_vehicle_profiles;
IF OBJECT_ID(N'dbo.crm360_customer_profiles',N'U') IS NOT NULL DROP TABLE dbo.crm360_customer_profiles;
GO
