/*
================================================================================
MOGHARE360 ERP — Version 0 Access Lifecycle
Script: core_v0_01_create_database.sql
================================================================================

ENVIRONMENT: Development / Staging ONLY — NOT Production.

This script creates the SQL Server database [moghare360_ERP] for internal staff
access lifecycle work (Version 0). Do not run on production without explicit
manual approval, backup, and change control.

Target design reference:
  docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md

Safety rules:
  - Does NOT drop any database.
  - Does NOT alter: moghare360, moghare360_StockCenter, moghare360D.
  - Safe to run multiple times (creates database only if missing).
  - Does NOT create tables (see later core_v0_* scripts).

Collation: Persian_100_CI_AS (Persian-compatible text storage).
================================================================================
*/

SET NOCOUNT ON;

IF NOT EXISTS (
    SELECT 1
    FROM sys.databases
    WHERE name = N'moghare360_ERP'
)
BEGIN
    PRINT N'Creating development database [moghare360_ERP] with collation Persian_100_CI_AS ...';

    CREATE DATABASE [moghare360_ERP]
    COLLATE Persian_100_CI_AS;
END
ELSE
BEGIN
    PRINT N'Database [moghare360_ERP] already exists — no action taken.';
END;
GO

USE [moghare360_ERP];
GO

SELECT
    d.name              AS database_name,
    d.collation_name    AS collation,
    d.create_date       AS create_date
FROM sys.databases AS d
WHERE d.name = N'moghare360_ERP';
GO
