# PHASE 7 — SQL Foundation

Script: `public_html/sql/sqlserver/phase_7_hr_internal_admin.sql`

## Tables
1. `dbo.erp_hr_employees`
2. `dbo.erp_hr_employment_contracts`
3. `dbo.erp_hr_attendance_records`
4. `dbo.erp_hr_payroll_previews`
5. `dbo.erp_hr_training_records`
6. `dbo.erp_hr_disciplinary_records`
7. `dbo.erp_hr_history`

## Rules
- Idempotent `IF OBJECT_ID IS NULL` create
- No `USE database`
- No `DROP` / `RENAME`
- Indexes created only if missing

## Execute
Run manually in SSMS against `moghare360_ERP`.
