# Component Style Rules

## Components Defined in moghare360-components.css

| Component | Classes |
|-----------|---------|
| Page header | m360-page-header, m360-page-header-title, m360-page-header-meta |
| Buttons | m360-btn, m360-btn-primary, m360-btn-accent, m360-btn-secondary, m360-btn-ghost, m360-btn-danger, m360-btn-sm |
| Cards | m360-card, m360-card-header, m360-card-body, m360-entity-card |
| Status badges | m360-badge + semantic variants (neutral, primary, accent, success, warning, danger, info) |
| Forms | m360-form-group, m360-form-label, m360-form-input, m360-form-select, m360-form-textarea, m360-form-hint |
| Tables | m360-table-wrap, m360-table |
| Alerts | m360-alert + success/warning/danger/info |
| KPI cards | m360-kpi-card, m360-kpi-value, m360-kpi-label |
| Timeline | m360-timeline, m360-timeline-item |
| Empty state | m360-empty-state |
| Diagnostic | m360-diagnostic-block |
| Demo banner | m360-demo-banner |

## JobCard Status Badge Mapping (Demo)
RECEIVED, IN_SERVICE, WAITING_PARTS, SERVICE_DONE, QC_*, READY_FOR_DELIVERY, DELIVERED — semantic colors per operational meaning

## Rules
- Use tokens only — no hardcoded hex in new components
- Border radius: sm for inputs/buttons, md for cards
- Shadow: card shadow for elevated surfaces

## Final Component Decision
Full primitive set for Soft Run ERP screens; demo showcases all components.
