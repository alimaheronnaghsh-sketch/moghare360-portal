# MOGHARE360 V1 RC Manifest

**Version:** `MOGHARE360-V1-RC`  
**Phase:** P10 — Release Hardening / Navigation RC  
**Scope:** Read-only navigation, route registry, release readiness — no operational workflow mutation

## Purpose

This manifest defines the Release Candidate (RC) boundary for MOGHARE360 V1 internal demo and owner presentation. P10 adds product navigation, route auditing, and release readiness reporting without changing P1–P9 operational behavior.

## RC Entry Points

| Page | Phase | Role |
|------|-------|------|
| `erp-product-home.php` | P10 | Staff / Owner / Demo product home |
| `erp-demo-package-rc.php` | P10 | Demo package manifest |
| `erp-release-readiness.php` | P10 | Release readiness categories |
| `erp-route-map.php` | P10 | Full route registry table |
| `erp-link-audit.php` | P10 | Missing file audit (file_exists only) |

## Required Migrations (P1–P10)

1. `P1_online_request_intake.sql`
2. `P1_5_intake_contract_signature.sql`
3. `P2_reception_jobcard_workflow.sql`
4. `P3_technical_operation_workflow.sql`
5. `P4_estimate_approval_parts_finance_gate.sql`
6. `P5_work_execution_parts_consumption.sql`
7. `P6_qc_final_inspection_delivery_readiness.sql`
8. `P7_final_invoice_settlement_customer_delivery.sql`
9. `P8_management_dashboard_owner_control.sql`
10. `P9_end_to_end_soft_run.sql`
11. `P10_release_hardening_navigation_rc.sql` (non-destructive; no schema changes)

## Required Test Suites

- `tools/test-p10-navigation-registry.php`
- `tools/test-p10-route-map.php`
- `tools/test-p10-link-audit.php`
- `tools/test-p10-release-hardening.php`
- `tools/test-p10-demo-package-rc.php`
- `tools/test-p10-security-scope-control.php`
- `tools/test-p10-production-signoff-integration.php`
- `tools/test-v1-production-signoff.php`

## Required Documentation

- `docs/release/MOGHARE360_V1_RC_MANIFEST.md` (this file)
- `docs/release/MOGHARE360_V1_DEMO_PACKAGE_RC.md`
- `docs/release/MOGHARE360_V1_RELEASE_READINESS_REPORT.md`
- `docs/release/MOGHARE360_V1_ROUTE_MAP.md`
- `docs/release/MOGHARE360_V1_SECURITY_SCOPE_LOCK.md`
- `docs/demo/MOGHARE360_V1_OWNER_DEMO_RUNBOOK.md`

## Known Exclusions (Out of RC Scope)

- No accounting voucher or ledger posting
- No payment gateway integration
- No bank or tax authority integration
- No SaaS multi-tenant provisioning
- No production deploy automation from P10 pages
- No real customer data in demo paths
- No credentials stored in repository
- No zip package build from P10 UI (manifest only)

## Registry Reference

Canonical route catalog: `public_html/includes/m360-navigation-registry.php`  
Summary table: `docs/release/MOGHARE360_V1_ROUTE_MAP.md`

## Sign-off Criteria

| Status | Meaning |
|--------|---------|
| **PASS** | All P10 tests pass; readiness score ≥ 90%; no blockers |
| **WARNING** | Demo allowed with documented gaps (≤ 3 category warnings) |
| **BLOCKED** | Resolve blockers before owner demo |
