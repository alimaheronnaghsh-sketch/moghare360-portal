# MOGHARE360 Demo Scenario Guide

Guide for running the P9 end-to-end demo scenario across P1–P8.

## Prerequisites

1. Apply `database/migrations/P9_end_to_end_soft_run.sql` (non-destructive).
2. Staff login via `staff-login.php`.
3. Demo JobCard with prefix **`M360-DEMO`** (recommended) or scenario row `M360-DEMO-E2E-V1`.
4. P8 migration applied for management dashboard reflection.

## Entry points

| Page | Purpose |
|------|---------|
| `erp-soft-run-control-center.php` | Hub — readiness score, categories, phase links |
| `erp-end-to-end-demo-scenario.php` | Stage-by-stage E2E status with gate evidence |
| `erp-demo-flow-map.php` | Visual map to operational pages |
| `erp-demo-readiness-report.php` | Checklist-based readiness score |
| `erp-soft-run-checklist.php` | Operator checklist (writes `erp_soft_run_checklist` only) |

## Demo flow (19 stages)

| Phase | Stages |
|-------|--------|
| P1 | Online request |
| P1.5 | Contract / OTP / signature |
| P2 | Reception JobCard |
| P3 | Technical diagnosis |
| P4 | Estimate, customer approval, parts gate, finance gate |
| P5 | Work execution, parts consumption, technical completion |
| P6 | QC, delivery readiness |
| P7 | Final invoice, settlement, customer delivery, vehicle release, JobCard closed |
| P8 | Management dashboard reflection |

## Status meanings

| Status | Meaning |
|--------|---------|
| PASS | Evidence found; gate satisfied |
| WARNING | Partial demo data or optional step skipped |
| BLOCKED | Required gate failed (e.g. unsigned contract) |
| NOT_RUN | Table missing or stage not executed |

## Demo data rules

- Use **`M360-DEMO`** prefix on JobCard numbers and/or customer names.
- Do not mix production customer data into demo walkthroughs.
- P9 pages do not approve, override, settle, or close JobCards — use normal P1–P7 operational pages for that.

## API (read-only)

- `GET api/soft-run/demo-scenario-status.php` — stages + demo prefix
- `GET api/soft-run/readiness-summary.php` — score + categories

## P8 closure

After P7 close, open `erp-management-dashboard.php` from nav or phase link. Confirm pipeline view and timeline show the demo JobCard.

## Troubleshooting

| Issue | Action |
|-------|--------|
| No demo JobCard | Create reception JobCard with `M360-DEMO-*` number |
| Low readiness score | Complete checklist items in `erp-soft-run-checklist.php` |
| BLOCKED on contract | Complete P1.5 contract signature on demo JobCard |
| P8 WARNING | Apply P8 migration and verify views exist |
