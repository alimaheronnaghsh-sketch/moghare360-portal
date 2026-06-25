# PHASE 5 — Financial System Index

Status: **PENDING USER SQL + TEST**

## Built Files

### SQL
- `public_html/sql/sqlserver/phase_5_financial_system.sql`

### Helper
- `public_html/includes/erp-pricing-engine.php`

### PHP Pages
- `public_html/erp-finance-control-center.php`
- `public_html/erp-service-price-list.php`
- `public_html/erp-jobcard-cost-preview.php`
- `public_html/erp-payment-tracking.php`
- `public_html/submit-payment-record.php`
- `public_html/erp-invoice-preview.php`

### CSS
- `public_html/assets/moghare360-ui/moghare360-financial-system.css`

### Test Tool
- `tools/test-phase-5-financial-system.php`

### Integration (minimal links)
- `erp-operation-control-center.php` — nav to cost preview and finance center
- `erp-jobcard-operation-flow.php` — link to cost preview with `operation_case_id`

## Browser URLs

Base: `http://localhost:8080/moghare360/`

| Page | URL |
|------|-----|
| Finance Control Center | `erp-finance-control-center.php` |
| Service Price List | `erp-service-price-list.php` |
| JobCard Cost Preview | `erp-jobcard-cost-preview.php` |
| Payment Tracking | `erp-payment-tracking.php` |
| Invoice Preview | `erp-invoice-preview.php` |

## Docs

- `PHASE_5_01_SCOPE.md` through `PHASE_5_99_SIGNOFF.md`
