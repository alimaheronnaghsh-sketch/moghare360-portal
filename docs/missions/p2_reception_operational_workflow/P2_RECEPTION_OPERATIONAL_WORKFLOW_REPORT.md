# P2 — Reception Operational Workflow + JobCard Execution Control

**MOGHARE360 V1** | Mission report

## 1. Schema discovery result

| Item | Finding |
|------|---------|
| JobCard table | `dbo.erp_jobcards` |
| Primary key | `jobcard_id` (BIGINT) |
| Workflow status column | `jobcard_status` NVARCHAR(30), default `RECEIVED` |
| `contract_status` | Added by P1.5 migration |
| `intake_contract_id` | Added by P1.5 migration |
| `contract_signed_at` | Added by P1.5 migration |
| `reception_status` | **Not added** — existing `jobcard_status` used for P2 workflow |
| `vehicle_arrival_at` | Added by P2 migration |
| `checked_in_at` | Added by P2 migration |
| `reception_notes` | Added by P2 migration (fallback: `internal_notes`) |
| `customer_complaint` | Already exists |
| `initial_inspection_notes` | Added by P2 migration (fallback: `initial_vehicle_condition`) |
| `ready_for_technical_at` | Added by P2 migration |
| `assigned_reception_user_id` | Added by P2 migration (also sets `reception_user_id` when present) |
| History table | `dbo.erp_jobcard_change_history` — reused |
| P1.5 contract helper | `m360-intake-contract-helper.php` — present and integrated |

**Status mapping:** P2 uses `jobcard_status` with values `RECEIVED → ARRIVED → CHECKED_IN → INITIAL_REVIEW → READY_FOR_TECHNICAL` plus `IN_PROGRESS`, `ON_HOLD`, `CANCELLED`. No separate `reception_status` column to avoid schema duplication.

## 2. SQL migration

**Needed:** Yes — `database/migrations/P2_reception_jobcard_workflow.sql`

Non-destructive `IF COL_LENGTH` adds: `vehicle_arrival_at`, `checked_in_at`, `reception_notes`, `initial_inspection_notes`, `ready_for_technical_at`, `assigned_reception_user_id`, `online_request_id` (if missing), plus index on `(jobcard_status, created_at DESC)`.

## 3. Files added/modified

| File | Action |
|------|--------|
| `public_html/erp-reception-jobcards.php` | Added — dashboard |
| `public_html/erp-reception-jobcard-detail.php` | Added — detail + forms |
| `public_html/erp-reception-jobcard-action.php` | Added — POST handler |
| `public_html/includes/m360-reception-jobcard-helper.php` | Added |
| `public_html/includes/m360-jobcard-workflow-helper.php` | Added |
| `database/migrations/P2_reception_jobcard_workflow.sql` | Added |
| `tools/test-p2-reception-jobcard-workflow.php` | Added |
| `tools/test-p2-jobcard-action-control.php` | Added |
| `tools/test-p2-jobcard-history-audit.php` | Added |
| `tools/test-p2-contract-gate-integration.php` | Added |

No changes to auth core, `staff-login.php`, `owner-login.php`, `access-control.php`, or `config.php`.

## 4. Dashboard behavior

`erp-reception-jobcards.php` lists JobCards newest-first with filters for workflow status and contract state (SIGNED / UNSIGNED / OVERRIDDEN). Shows customer, mobile, vehicle, plate, source (ONLINE/MANUAL), status, contract state, and arrival/signature dates. Link to detail. Persian RTL. Alerts if P1.5 gate helpers missing.

## 5. Detail page behavior

`erp-reception-jobcard-detail.php` shows full JobCard, customer, vehicle, contract link, complaint/notes, change history, and contract events. Actions shown per allowed state. Ready for Technical disabled with Persian message when contract unsigned. Manager override form (reason ≥ 10 chars) when gate blocks.

## 6. Action handler behavior

`erp-reception-jobcard-action.php` — POST only, CSRF required, validates transitions, idempotent repeats, safe redirect to detail with Persian flash messages.

## 7. Workflow transitions

| Action | Transition |
|--------|------------|
| `mark_arrived` | RECEIVED → ARRIVED |
| `check_in` | ARRIVED → CHECKED_IN |
| `save_initial_inspection` | CHECKED_IN → INITIAL_REVIEW |
| `ready_for_technical` | CHECKED_IN / INITIAL_REVIEW / ON_HOLD → READY_FOR_TECHNICAL (gated) |
| `hold` | → ON_HOLD |
| `cancel` | → CANCELLED |
| `save_*` | Notes only, no invalid status jump |

## 8. Contract gate (P1.5)

- `ready_for_technical` calls `m360_contract_can_continue_to_p2($jobcardId)` before status change.
- If false: no status change, history `JOBCARD_READY_FOR_TECHNICAL_BLOCKED_CONTRACT_UNSIGNED`, contract event recorded, redirect message: «قرارداد پذیرش هنوز توسط مشتری امضا نشده است.»
- If P1.5 helpers missing: fail closed with «P1.5 Gate missing».

## 9. Manager override

`manager_override_contract_gate` requires reason ≥ 10 characters, calls `m360_intake_contract_apply_manager_override`, records `JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE` in JobCard history and contract events. After override, `ready_for_technical` is allowed.

## 10. Audit / history

All actions write to `erp_jobcard_change_history` with event types: `JOBCARD_ARRIVED`, `JOBCARD_CHECKED_IN`, `JOBCARD_CUSTOMER_COMPLAINT_SAVED`, `JOBCARD_RECEPTION_NOTES_SAVED`, `JOBCARD_INITIAL_INSPECTION_SAVED`, `JOBCARD_READY_FOR_TECHNICAL`, `JOBCARD_READY_FOR_TECHNICAL_BLOCKED_CONTRACT_UNSIGNED`, `JOBCARD_CONTRACT_GATE_MANAGER_OVERRIDE`, `JOBCARD_ON_HOLD`, `JOBCARD_CANCELLED`.

## 11. Tests passed

| Suite | Result |
|-------|--------|
| `test-p2-reception-jobcard-workflow.php` | 19/19 PASS |
| `test-p2-jobcard-action-control.php` | 11/11 PASS |
| `test-p2-jobcard-history-audit.php` | 17/17 PASS |
| `test-p2-contract-gate-integration.php` | 12/12 PASS |
| PHP `-l` on all P2 + intake helper | No syntax errors |

`test-v1-production-signoff.php` — not run in this session (requires production HTTP/signoff context).

## 12. Security confirmation

- No credentials in repo
- No real `mirror-config.php` changes
- No destructive SQL (DROP/DELETE/TRUNCATE)
- Auth/login core unchanged
- `staff-login.php` / `owner-login.php` unchanged
- Contract gate not bypassed — fail closed if P1.5 missing

## 13. Deploy note

Run migrations on ERP SQL Server before use:

1. `P1_online_request_intake.sql`
2. `P1_5_intake_contract_signature.sql`
3. `P2_reception_jobcard_workflow.sql`

---

**MOGHARE360 P2 enables controlled reception-side JobCard execution workflow from arrival through ready-for-technical, enforcing the signed intake contract gate from P1.5 without changing auth core or destructive schema.**
