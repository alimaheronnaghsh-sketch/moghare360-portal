# P6 — QC / Final Inspection / Delivery Readiness

**MOGHARE360 V1** | Mission report

## 1. Schema discovery

| Item | Finding |
|------|---------|
| JobCard | `dbo.erp_jobcards` — PK `jobcard_id` |
| P5 output | `work_execution_status = READY_FOR_QC`, `ready_for_qc_at`, `technical_completion_notes` |
| Existing QC | `dbo.erp_qc_checks` (Mission 30) — status: PENDING/PASSED/FAILED/RECHECK_REQUIRED/CANCELLED |
| Existing history | `dbo.erp_qc_check_history` (read-only reference; P6 adds `erp_qc_events`) |
| Delivery | `dbo.erp_delivery_controls` — READY (readiness), RELEASED (out of P6 scope) |
| Checklist | **Not existing** — created `erp_qc_check_items` |
| Photo/docs | No file upload table — metadata only via `erp_qc_media_events` + link to `erp-jobcard-camera-capture.php` |
| P1.5/P4/P5 helpers | Wired via `m360-qc-helper.php` — no bypass |

**Status mapping:** P6 workflow uses `jobcard.qc_status`; legacy `erp_qc_checks.qc_status` maps PENDING↔QC_IN_PROGRESS, PASSED↔QC_PASSED, FAILED↔QC_FAILED, RECHECK_REQUIRED↔REWORK.

## 2. SQL migration

**Needed:** Yes — `database/migrations/P6_qc_final_inspection_delivery_readiness.sql`

Adds JobCard QC/delivery columns, `erp_qc_check_items`, `erp_qc_events`, `erp_qc_media_events`, `erp_delivery_readiness_checks`; extends existing `erp_qc_checks` with P6 columns if present.

## 3. Files added

**UI:** `erp-qc-board.php`, `erp-qc-detail.php`, `erp-qc-action.php`, `assets/css/m360-qc.css`

**Helpers:** `m360-qc-helper.php`, `m360-final-inspection-helper.php`, `m360-delivery-readiness-helper.php`

**SQL:** `P6_qc_final_inspection_delivery_readiness.sql`

**Tests:** 7 suites under `tools/test-p6-*.php`

## 4. QC board

Lists JobCards at `READY_FOR_QC` or active QC workflow. Filters: READY_FOR_QC, QC_IN_PROGRESS, QC_FAILED, REWORK_REQUIRED, QC_PASSED, DELIVERY_READY, ON_HOLD. Persian RTL.

## 5. QC detail / action

POST-only actions with CSRF: start_qc, save_checklist_item, save_final_inspection_notes, qc_failed/rework_required, rework_completed, qc_passed, delivery_ready, hold, cancel.

## 6. Gate enforcement

`m360_qc_assert_gates()` before every action:
- P5 READY_FOR_QC + technical completion notes
- P1.5 contract gate
- P4 estimate APPROVED_FOR_WORK
- P5 work completed

Block event: `JOBCARD_QC_BLOCKED_GATE` / `QC_BLOCKED_GATE`

## 7. Final inspection checklist

14 items (services, parts, leaks, diag, exterior, belongings, km/fuel, functional/road test, defects, recommendations, delivery prep). Results: PASS / FAIL / NOT_APPLICABLE. QC pass requires all required items PASS or N/A, no active FAIL, final notes present.

## 8. Rework

QC fail → `REWORK_REQUIRED` with mandatory `failure_reason`. `rework_completed` resets to `READY_FOR_QC` for re-inspection. No full rework workflow — status/audit only.

## 9. Delivery readiness

`delivery_ready` only after `QC_PASSED`. Sets `delivery_readiness_status = READY`, `delivery_ready_at`, optional `erp_delivery_controls.delivery_status = READY` (not RELEASED). No vehicle release, settlement, or invoice.

## 10. Audit / history

JobCard history + `erp_qc_events` for all P6 transitions including gate blocks, checklist saves, pass/fail, rework, delivery ready.

## 11. Scope control

No final invoice, payment settlement, delivery release, delivery OTP, accounting voucher, or free file upload. Controlled camera via existing capture page link only.

## 12. Tests passed

| Suite | Result |
|-------|--------|
| `test-p6-qc-schema.php` | 11/11 |
| `test-p6-qc-board.php` | 19/19 |
| `test-p6-qc-gate-enforcement.php` | 9/9 |
| `test-p6-final-inspection-checklist.php` | 11/11 |
| `test-p6-rework-and-delivery-readiness.php` | 10/10 |
| `test-p6-history-audit.php` | 27/27 |
| `test-p6-scope-control.php` | 11/11 |

PHP `-l` passed on all P6 files plus P5 integration helpers.

`test-v1-production-signoff.php` — not run (requires production context).

## 13. Security

No credentials, no auth core changes, non-destructive SQL, all state changes POST+CSRF.

---

**MOGHARE360 P6 enables controlled QC, final inspection checklist, rework routing, and delivery readiness after READY_FOR_QC without adding final invoice, payment settlement, vehicle release, accounting, or upload-bypass scope.**
