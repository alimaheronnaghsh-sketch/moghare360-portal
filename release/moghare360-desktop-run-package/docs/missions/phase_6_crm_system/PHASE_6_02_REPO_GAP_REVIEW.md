# PHASE 6 — Repo Gap Review

- Legacy `Payments`, `JobCard`, `Customers_v2` — not modified
- Phase 1–5 tables linked via `customer_id`, `intake_id`, `operation_case_id`
- No existing CRM tables dropped; all new `erp_crm_*` / `erp_customer_score_cards` / `erp_upsell_opportunities`
- Helpers reused: auth, CSRF, permission guard — unchanged
