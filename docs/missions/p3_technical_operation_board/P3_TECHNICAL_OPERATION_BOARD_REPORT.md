# P3 — Technical Operation Board + Technician Execution Workflow

**MOGHARE360 V1** | Mission report

## 1. Schema discovery result

| Item | Finding |
|------|---------|
| JobCard table | `dbo.erp_jobcards` — PK `jobcard_id` |
| Reception status | `jobcard_status` (P2 ends at `READY_FOR_TECHNICAL`) |
| Technical workflow | `technical_status` NVARCHAR(50) — **added by P3 migration** |
| `READY_FOR_TECHNICAL` | Used in P2 on `jobcard_status`; P3 entry when `technical_status` is null |
| Service operations | `dbo.erp_service_operations` — **exists** (Mission 20) |
| Service operation history | `dbo.erp_service_operation_change_history` — **exists** |
| `assigned_technician_user_id` | Added by P3 migration |
| `diagnosis_started_at` / `diagnosis_completed_at` | Added by P3 migration |
| `technician_notes` / `diagnosis_summary` | Added by P3 migration |
| `technical_started_at` / `technical_completed_at` | Added by P3 migration |
| JobCard history | `dbo.erp_jobcard_change_history` — reused |
| P1.5/P2 gate helpers | `m360_contract_can_continue_to_p2`, `m360_reception_jobcard_p15_gate_available` — integrated |

### Service operation column mapping (existing schema)

| P3 concept | ERP column |
|------------|------------|
| operation_title | `service_title` |
| operation_description | `service_description` |
| operation_status CREATED | `service_status` = `ASSIGNED` |
| operation_status STARTED | `service_status` = `IN_PROGRESS` |
| operation_status COMPLETED | `service_status` = `DONE` |
| technician_user_id | `assigned_to_user_id` |
| started_at / completed_at | `updated_at` (no new columns on SO table) |

## 2. SQL migration

**Needed:** Yes — `database/migrations/P3_technical_operation_workflow.sql`

Adds to `erp_jobcards`: `technical_status`, `assigned_technician_user_id`, `diagnosis_started_at`, `diagnosis_completed_at`, `technician_notes`, `diagnosis_summary`, `technical_started_at`, `technical_completed_at`, plus index on `(technical_status, ready_for_technical_at DESC)`.

No duplicate `erp_service_operations` table created.

## 3. Files added/modified

| File | Action |
|------|--------|
| `public_html/erp-technical-board.php` | Added |
| `public_html/erp-technical-jobcard-detail.php` | Added |
| `public_html/erp-technical-jobcard-action.php` | Added |
| `public_html/includes/m360-technical-operation-helper.php` | Added |
| `public_html/includes/m360-technician-workflow-helper.php` | Added |
| `database/migrations/P3_technical_operation_workflow.sql` | Added |
| `tools/test-p3-*.php` (5 suites) | Added |

No changes to auth core, login pages, or contract helpers (read/check only).

## 4. Technical board behavior

Lists JobCards where `jobcard_status = READY_FOR_TECHNICAL` or `technical_status` is set. Filters by technical status and contract (SIGNED / OVERRIDDEN). Persian RTL. Newest `ready_for_technical_at` first.

## 5. Technical detail behavior

Full JobCard + reception context, technician notes, diagnosis, service operations list with start/complete, histories. Actions disabled with Persian messages when P2 or contract gate fails.

## 6. Action handler behavior

POST only, CSRF, validates transitions, idempotent repeats, safe redirect with Persian flash messages.

## 7. Technician workflow transitions

`READY_FOR_TECHNICAL → TECHNICAL_QUEUE → ASSIGNED_TO_TECHNICIAN → DIAGNOSIS_STARTED → DIAGNOSIS_COMPLETED → SERVICE_OPERATION_* → TECHNICAL_REVIEW → WAITING_FOR_APPROVAL → TECHNICAL_DONE`

`ON_HOLD` available mid-flow. `WAITING_FOR_APPROVAL` is P4 handoff (estimate/approval/parts/finance).

## 8. Service operation handling

Creates rows in existing `erp_service_operations` linked by `jobcard_id`. Start/complete updates `service_status` and writes `erp_service_operation_change_history`. No pricing, payment, or inventory.

## 9. P2/P1.5 gate enforcement

Before every technical action:

1. `m360_technician_workflow_is_p2_ready()` — must be `READY_FOR_TECHNICAL` or already in technical workflow
2. `m360_contract_can_continue_to_p2()` — contract SIGNED or valid override

On failure: no state change, history `JOBCARD_TECHNICAL_ACTION_BLOCKED_NOT_READY` or `JOBCARD_TECHNICAL_ACTION_BLOCKED_CONTRACT_GATE`.

## 10. Audit/history behavior

JobCard events per spec in `erp_jobcard_change_history`. Service operation events in `erp_service_operation_change_history`.

## 11. Tests passed

| Suite | Result |
|-------|--------|
| `test-p3-technical-board.php` | 17/17 PASS |
| `test-p3-technician-workflow.php` | 8/8 PASS |
| `test-p3-service-operation-control.php` | 10/10 PASS |
| `test-p3-history-audit.php` | 22/22 PASS |
| `test-p3-p2-gate-enforcement.php` | 11/11 PASS |
| PHP `-l` on all P3 helpers/pages | No syntax errors |

`test-v1-production-signoff.php` — not run in this session.

## 12. Security confirmation

- No credentials in repo
- No real config changes
- No destructive SQL
- Auth/login core unchanged
- Contract gate not bypassed
- No pricing/payment/inventory scope added

## 13. Deploy note

Run `P3_technical_operation_workflow.sql` after P1/P1.5/P2 migrations.

---

**MOGHARE360 P3 enables controlled technical operation board and technician execution workflow for READY_FOR_TECHNICAL JobCards, enforcing P1.5/P2 gates without changing auth core or destructive schema.**
