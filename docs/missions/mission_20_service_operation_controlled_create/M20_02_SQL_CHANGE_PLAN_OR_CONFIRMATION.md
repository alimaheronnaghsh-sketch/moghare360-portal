# Mission 20 - SQL Change Plan or Confirmation

## Purpose
This document confirms the SQL change plan for Mission 20 Service Operation foundation.

## Script
- public_html/sql/sqlserver/mission_20_service_operation_foundation.sql

## Database
- moghare360_ERP

## Tables Created (If Missing)

### dbo.erp_service_operations
- service_operation_id INT IDENTITY(1,1) PRIMARY KEY
- jobcard_id INT NOT NULL → FK dbo.erp_jobcards
- service_title NVARCHAR(200) NOT NULL
- service_description NVARCHAR(MAX) NULL
- assigned_to_user_id INT NULL
- service_status NVARCHAR(30) NOT NULL with CHECK constraint
- created_by_user_id INT NOT NULL
- created_at DATETIME2(3) NOT NULL DEFAULT SYSUTCDATETIME()
- updated_at DATETIME2(3) NULL
- is_active BIT NOT NULL DEFAULT 1

### dbo.erp_service_operation_change_history
- history_id INT IDENTITY(1,1) PRIMARY KEY
- service_operation_id INT NOT NULL → FK erp_service_operations
- jobcard_id INT NOT NULL → FK dbo.erp_jobcards
- action_code NVARCHAR(80) NOT NULL
- old_status NVARCHAR(30) NULL
- new_status NVARCHAR(30) NULL
- changed_by_user_id INT NOT NULL
- changed_at DATETIME2(3) NOT NULL DEFAULT SYSUTCDATETIME()
- change_note NVARCHAR(MAX) NULL

## Status CHECK Constraint
Allowed values:
- DRAFT
- ASSIGNED
- IN_PROGRESS
- WAITING_PARTS
- DONE
- QC_REJECTED
- CANCELLED

## Foreign Key Target
JobCard foundation table confirmed: **dbo.erp_jobcards** (Mission 17)

## Idempotency
- IF OBJECT_ID ... IS NULL for CREATE TABLE
- IF OBJECT_ID ... IS NULL for ALTER TABLE ADD CONSTRAINT
- IF NOT EXISTS for indexes

## Execution Rule
- User executes manually in SSMS
- PHP does not auto-run SQL
- No DROP / TRUNCATE / destructive migration

## Confirmation Status
SQL file created and ready for manual SSMS execution.
