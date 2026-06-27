# PHASE 5 — JobCard Cost Engine

Page: `public_html/erp-jobcard-cost-preview.php`

## Flow

1. Create cost header (operation_case_id / jobcard_id optional)
2. Add cost lines: SERVICE, LABOUR, PART, DISCOUNT, MANUAL_ADJUSTMENT
3. Recalculate totals
4. View payments linked to header

## Write Actions (self-POST, CSRF)

- `create_header` — purpose `finance_cost_header`
- `add_line` — purpose `finance_cost_line`
- `recalculate` — purpose `finance_cost_recalc`

No official invoice. No tax.
