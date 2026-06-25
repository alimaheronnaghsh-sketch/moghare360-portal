# PHASE 7 — Attendance

## Page
`erp-attendance-entry.php` → `submit-attendance-entry.php`

## Calculation (`hr_calculate_attendance_hours`)
- `work_hours` = check_out − check_in
- `net_work_hours` = work_hours − break_hours
- `overtime_hours` = max(net − required, 0)
- `absence_hours` = max(required − net, 0)

## Status
Default `RECORDED` on insert.
