# PHASE 6 — CRM System Index

Status: **PENDING USER SQL + TEST**

## Built Files

### SQL
- `public_html/sql/sqlserver/phase_6_crm_system.sql`

### Helper
- `public_html/includes/erp-crm-helper.php`

### PHP Pages
- `public_html/erp-crm-followup-board.php`
- `public_html/erp-crm-followup-detail.php`
- `public_html/submit-crm-followup.php`
- `public_html/erp-customer-satisfaction.php`
- `public_html/submit-customer-satisfaction.php`
- `public_html/erp-customer-score-board.php`
- `public_html/erp-upsell-opportunities.php`

### CSS
- `public_html/assets/moghare360-ui/moghare360-crm-system.css`

### Test Tool
- `tools/test-phase-6-crm-system.php`

### Integration
- `erp-operation-control-center.php` — CRM follow-up board link
- `erp-finance-control-center.php` — CRM follow-up board link
- `erp-customer-profile.php` — read-only CRM links

## Browser URLs

| Page | URL |
|------|-----|
| Follow-up Board | `erp-crm-followup-board.php` |
| Follow-up Detail | `erp-crm-followup-detail.php?followup_schedule_id=` |
| Satisfaction | `erp-customer-satisfaction.php` |
| Score Board | `erp-customer-score-board.php` |
| Upsell | `erp-upsell-opportunities.php` |
