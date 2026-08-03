/*
  WORK360_001_daily_task_followup_report_center_rollback.sql
  Drops only work360_ tables. Does not touch other schemas.
*/
USE [moghare360_ERP];
GO

IF OBJECT_ID(N'dbo.work360_daily_performance_snapshots', N'U') IS NOT NULL DROP TABLE dbo.work360_daily_performance_snapshots;
IF OBJECT_ID(N'dbo.work360_daily_notes', N'U') IS NOT NULL DROP TABLE dbo.work360_daily_notes;
IF OBJECT_ID(N'dbo.work360_task_status_history', N'U') IS NOT NULL DROP TABLE dbo.work360_task_status_history;
IF OBJECT_ID(N'dbo.work360_task_followups', N'U') IS NOT NULL DROP TABLE dbo.work360_task_followups;
IF OBJECT_ID(N'dbo.work360_tasks', N'U') IS NOT NULL DROP TABLE dbo.work360_tasks;
IF OBJECT_ID(N'dbo.work360_users', N'U') IS NOT NULL DROP TABLE dbo.work360_users;
IF OBJECT_ID(N'dbo.work360_departments', N'U') IS NOT NULL DROP TABLE dbo.work360_departments;
GO
