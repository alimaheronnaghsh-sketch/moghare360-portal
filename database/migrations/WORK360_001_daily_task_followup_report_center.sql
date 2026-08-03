/*
  WORK360_001_daily_task_followup_report_center.sql
  Idempotent migration for Work360 standalone daily task center.
  Target database: moghare360_ERP
  Prefix: work360_
*/
USE [moghare360_ERP];
GO

IF OBJECT_ID(N'dbo.work360_departments', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_departments (
    department_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    department_code NVARCHAR(64) NOT NULL,
    department_name_fa NVARCHAR(200) NOT NULL,
    sort_order INT NOT NULL CONSTRAINT DF_work360_dept_sort DEFAULT (0),
    is_active BIT NOT NULL CONSTRAINT DF_work360_dept_active DEFAULT (1),
    created_at DATETIME2 NOT NULL CONSTRAINT DF_work360_dept_created DEFAULT (SYSUTCDATETIME()),
    CONSTRAINT UQ_work360_departments_code UNIQUE (department_code)
  );
END
GO

IF OBJECT_ID(N'dbo.work360_users', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_users (
    user_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    username NVARCHAR(80) NOT NULL,
    password_hash NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(200) NOT NULL,
    role_code NVARCHAR(32) NOT NULL,
    department_id INT NULL,
    supervisor_user_id INT NULL,
    is_active BIT NOT NULL CONSTRAINT DF_work360_users_active DEFAULT (1),
    created_at DATETIME2 NOT NULL CONSTRAINT DF_work360_users_created DEFAULT (SYSUTCDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_work360_users_updated DEFAULT (SYSUTCDATETIME()),
    CONSTRAINT UQ_work360_users_username UNIQUE (username)
  );
END
GO

IF OBJECT_ID(N'dbo.work360_tasks', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_tasks (
    task_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    task_code NVARCHAR(40) NOT NULL,
    title NVARCHAR(300) NOT NULL,
    description NVARCHAR(MAX) NULL,
    department_id INT NOT NULL,
    assigned_to_user_id INT NULL,
    supervisor_user_id INT NULL,
    created_by_user_id INT NOT NULL,
    priority_code NVARCHAR(16) NOT NULL CONSTRAINT DF_work360_task_prio DEFAULT (N'NORMAL'),
    status_code NVARCHAR(16) NOT NULL CONSTRAINT DF_work360_task_status DEFAULT (N'TODO'),
    due_date DATE NOT NULL,
    due_time TIME NULL,
    source_type NVARCHAR(40) NULL,
    reference_text NVARCHAR(400) NULL,
    expected_result NVARCHAR(400) NULL,
    completion_note NVARCHAR(MAX) NULL,
    completed_at DATETIME2 NULL,
    approved_by_user_id INT NULL,
    approved_at DATETIME2 NULL,
    is_recurring BIT NOT NULL CONSTRAINT DF_work360_task_recurring DEFAULT (0),
    recurrence_note NVARCHAR(200) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_work360_task_created DEFAULT (SYSUTCDATETIME()),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_work360_task_updated DEFAULT (SYSUTCDATETIME()),
    CONSTRAINT UQ_work360_tasks_code UNIQUE (task_code)
  );
END
GO

IF OBJECT_ID(N'dbo.work360_task_followups', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_task_followups (
    followup_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    task_id INT NOT NULL,
    followup_by_user_id INT NOT NULL,
    followup_type NVARCHAR(32) NOT NULL,
    note NVARCHAR(MAX) NOT NULL,
    next_followup_date DATE NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_work360_fu_created DEFAULT (SYSUTCDATETIME())
  );
END
GO

IF OBJECT_ID(N'dbo.work360_task_status_history', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_task_status_history (
    history_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    task_id INT NOT NULL,
    old_status_code NVARCHAR(16) NULL,
    new_status_code NVARCHAR(16) NOT NULL,
    changed_by_user_id INT NOT NULL,
    note NVARCHAR(400) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_work360_hist_created DEFAULT (SYSUTCDATETIME())
  );
END
GO

IF OBJECT_ID(N'dbo.work360_daily_notes', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_daily_notes (
    note_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    department_id INT NOT NULL,
    note_date DATE NOT NULL,
    note_text NVARCHAR(MAX) NOT NULL,
    created_by_user_id INT NOT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_work360_notes_created DEFAULT (SYSUTCDATETIME())
  );
END
GO

IF OBJECT_ID(N'dbo.work360_daily_performance_snapshots', N'U') IS NULL
BEGIN
  CREATE TABLE dbo.work360_daily_performance_snapshots (
    snapshot_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    snapshot_date DATE NOT NULL,
    department_id INT NULL,
    user_id INT NULL,
    total_tasks INT NOT NULL CONSTRAINT DF_work360_snap_total DEFAULT (0),
    today_tasks INT NOT NULL CONSTRAINT DF_work360_snap_today DEFAULT (0),
    overdue_tasks INT NOT NULL CONSTRAINT DF_work360_snap_overdue DEFAULT (0),
    blocked_tasks INT NOT NULL CONSTRAINT DF_work360_snap_blocked DEFAULT (0),
    waiting_tasks INT NOT NULL CONSTRAINT DF_work360_snap_waiting DEFAULT (0),
    done_tasks INT NOT NULL CONSTRAINT DF_work360_snap_done DEFAULT (0),
    completion_rate DECIMAL(9,2) NOT NULL CONSTRAINT DF_work360_snap_comp DEFAULT (0),
    delay_rate DECIMAL(9,2) NOT NULL CONSTRAINT DF_work360_snap_delay DEFAULT (0),
    performance_score DECIMAL(9,2) NOT NULL CONSTRAINT DF_work360_snap_score DEFAULT (0),
    generated_at DATETIME2 NOT NULL CONSTRAINT DF_work360_snap_gen DEFAULT (SYSUTCDATETIME())
  );
END
GO

/* Seed departments */
IF NOT EXISTS (SELECT 1 FROM dbo.work360_departments WHERE department_code = N'PROCUREMENT_LOCAL')
  INSERT INTO dbo.work360_departments (department_code, department_name_fa, sort_order) VALUES (N'PROCUREMENT_LOCAL', N'خرید و تأمین', 1);
IF NOT EXISTS (SELECT 1 FROM dbo.work360_departments WHERE department_code = N'FINANCE_ACCOUNTING_CASHIER')
  INSERT INTO dbo.work360_departments (department_code, department_name_fa, sort_order) VALUES (N'FINANCE_ACCOUNTING_CASHIER', N'مالی، حسابداری، فروش و صندوق', 2);
IF NOT EXISTS (SELECT 1 FROM dbo.work360_departments WHERE department_code = N'CRM_RECEPTION')
  INSERT INTO dbo.work360_departments (department_code, department_name_fa, sort_order) VALUES (N'CRM_RECEPTION', N'CRM و پذیرش', 3);
IF NOT EXISTS (SELECT 1 FROM dbo.work360_departments WHERE department_code = N'INTERNAL_SERVICE_HALL')
  INSERT INTO dbo.work360_departments (department_code, department_name_fa, sort_order) VALUES (N'INTERNAL_SERVICE_HALL', N'سالن و اجرای خدمات داخلی', 4);
IF NOT EXISTS (SELECT 1 FROM dbo.work360_departments WHERE department_code = N'LOGISTICS_EXTERNAL_SERVICE')
  INSERT INTO dbo.work360_departments (department_code, department_name_fa, sort_order) VALUES (N'LOGISTICS_EXTERNAL_SERVICE', N'لجستیک و خدمات بیرونی', 5);
IF NOT EXISTS (SELECT 1 FROM dbo.work360_departments WHERE department_code = N'FOREIGN_PURCHASE')
  INSERT INTO dbo.work360_departments (department_code, department_name_fa, sort_order) VALUES (N'FOREIGN_PURCHASE', N'خرید خارجه', 6);
GO

/* Seed OWNER amir — password hash for Amir1985 (PHP password_hash) */
IF NOT EXISTS (SELECT 1 FROM dbo.work360_users WHERE username = N'amir')
BEGIN
  INSERT INTO dbo.work360_users (username, password_hash, full_name, role_code, department_id, is_active)
  VALUES (
    N'amir',
    N'$2y$10$kI9V9u0TCH310EbBn8a3yO9/r5hwgACBvazPSazJES5Qwj1rfQwnS',
    N'Amir Owner',
    N'OWNER',
    NULL,
    1
  );
END
ELSE
BEGIN
  UPDATE dbo.work360_users
  SET password_hash = N'$2y$10$kI9V9u0TCH310EbBn8a3yO9/r5hwgACBvazPSazJES5Qwj1rfQwnS',
      full_name = N'Amir Owner',
      role_code = N'OWNER',
      is_active = 1,
      updated_at = SYSUTCDATETIME()
  WHERE username = N'amir';
END
GO
