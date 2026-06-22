# PHASE 7 — Payroll Preview

## Page
`erp-payroll-preview.php` → `submit-payroll-preview.php`

## Warning (UI)
«این پیش‌نمایش داخلی حقوق است و فیش رسمی/سند قانونی نیست.»

## Calculation (`hr_calculate_payroll_preview`)
- `gross_preview_amount` = base + allowance + overtime + friday + bonus
- `net_preview_amount` = gross − deduction (floor at 0)

## Not Included
- No real payment
- No insurance/tax deductions
- No official legal payslip
