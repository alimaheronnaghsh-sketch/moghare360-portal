# PHASE 7 — Employee Lifecycle

## Create (`erp-employee-create.php` → `submit-employee-create.php`)
- CSRF protected POST
- `employee_code` auto: `EMP-YYYYMMDD-HHMMSS-random4`
- Duplicate warning on mobile/national_code (non-blocking)
- Redirect to profile after insert

## Profile (`erp-employee-profile.php`)
- Search/list when no `employee_id`
- Read-only view of employee data
- Sections: contracts, attendance, payroll previews, training, disciplinary
- Links to related HR pages
