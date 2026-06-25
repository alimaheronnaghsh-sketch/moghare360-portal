# PHASE 5 — SQL Foundation

Script: `public_html/sql/sqlserver/phase_5_financial_system.sql`

## Tables

1. `erp_finance_service_price_list`
2. `erp_finance_labour_rates`
3. `erp_finance_part_margin_rules`
4. `erp_jobcard_cost_headers`
5. `erp_jobcard_cost_lines`
6. `erp_payment_records` (extension when `erp_payments` exists)
7. `erp_invoice_previews`
8. `erp_financial_summary_snapshots`
9. `erp_finance_history`

## Seeds

- `DEFAULT-LABOUR` — نرخ پیش‌فرض اجرت / 0
- `DEFAULT-PART-MARGIN` — حاشیه سود پیش‌فرض قطعات / 20%
- `MANUAL-SERVICE` — خدمت دستی / 0

Execute manually in SSMS on `moghare360_ERP`. Idempotent. No DROP.
