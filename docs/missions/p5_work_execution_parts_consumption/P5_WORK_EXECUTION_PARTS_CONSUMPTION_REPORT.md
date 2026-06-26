# P5 — Work Execution / Parts Consumption / Technical Completion

**MOGHARE360 V1** | Mission report

## 1. Schema discovery

| Item | Finding |
|------|---------|
| JobCard table | `dbo.erp_jobcards` — PK `jobcard_id` |
| P4 approval marker | `estimate_status = APPROVED_FOR_WORK`, `approved_for_work_at` |
| Work execution status | New column `work_execution_status` (mapped from null → `APPROVED_FOR_WORK` when P4 cleared) |
| Service operations | `dbo.erp_service_operations` — `ASSIGNED` / `IN_PROGRESS` / `DONE` |
| Part usage | `dbo.erp_jobcard_part_usage` (reused; `usage_status = USED`) |
| Stock (optional read) | `erp_stock_balances`, `erp_stock_locations`, `erp_part_reservations` |
| Estimate parts | `erp_estimate_items` where `item_type = PART` |
| History | `erp_jobcard_change_history`, `erp_service_operation_change_history` |
| P5 events | New `dbo.erp_work_execution_events` |
| P1.5 / P2 / P3 / P4 helpers | Present and wired — no bypass |

## 2. SQL migration

**Needed:** Yes — `database/migrations/P5_work_execution_parts_consumption.sql`

Non-destructive additions:
- JobCard columns: `work_execution_status`, `work_started_at`, `work_completed_at`, `ready_for_qc_at`, `parts_consumption_status`, `technical_completion_notes`, `final_technician_user_id`
- Table: `erp_work_execution_events`
- Index on `work_execution_status`

No duplicate part/service tables created.

## 3. Files added/modified

**UI**
- `public_html/erp-work-execution-board.php`
- `public_html/erp-work-execution-detail.php`
- `public_html/erp-work-execution-action.php`
- `public_html/assets/css/m360-work-execution.css`

**Helpers**
- `public_html/includes/m360-work-execution-helper.php`
- `public_html/includes/m360-parts-consumption-helper.php`
- `public_html/includes/m360-technical-completion-helper.php`

**SQL**
- `database/migrations/P5_work_execution_parts_consumption.sql`

**Tests**
- `tools/test-p5-work-execution-schema.php`
- `tools/test-p5-work-execution-board.php`
- `tools/test-p5-p4-gate-enforcement.php`
- `tools/test-p5-parts-consumption-control.php`
- `tools/test-p5-technical-completion.php`
- `tools/test-p5-history-audit.php`
- `tools/test-p5-scope-control.php`

## 4. Work execution board

Lists JobCards with `estimate_status = APPROVED_FOR_WORK` or active `work_execution_status`. Filters: all P5 statuses (APPROVED_FOR_WORK through ON_HOLD). Shows customer, vehicle, plate, technical/estimate/parts/finance gates, work status, QC readiness. Persian RTL UI.

## 5. Detail / action behavior

- **GET detail:** read-only display + POST forms only
- **POST action:** CSRF + staff auth; 11 actions supported
- Redirect back to detail with Persian flash message
- Idempotent transitions where applicable (already started, already consumed)

## 6. P4 gate enforcement

Before every action, `m360_work_assert_gates()` checks:
1. JobCard P4 approved (`APPROVED_FOR_WORK` or in-work statuses)
2. P1.5 contract gate (`m360_contract_can_continue_to_p2`)
3. Active approved estimate
4. Parts gate `CLEARED` or `NOT_REQUIRED`
5. Finance gate `CLEARED` or `NOT_REQUIRED`

On failure: no state change, event `JOBCARD_WORK_EXECUTION_BLOCKED_GATE`, Persian error.

## 7. Parts consumption

- Only `PART` items from approved estimate
- Quantity capped at approved amount
- Idempotent if already consumed
- Optional stock check via `erp_stock_balances`; negative stock blocked
- Insufficient stock → `WAITING_FOR_PARTS`, event `PART_USAGE_CONSUMPTION_BLOCKED`
- Uses existing `erp_jobcard_part_usage` — no duplicate table

## 8. Service operation execution

- Start: `ASSIGNED` → `IN_PROGRESS` with history `SERVICE_OPERATION_EXECUTION_STARTED`
- Complete: requires `operation_result_note`; `IN_PROGRESS` → `DONE`
- Blocked ops log `SERVICE_OPERATION_EXECUTION_BLOCKED`
- JobCard ownership validated on every update

## 9. Technical completion

`complete_technical_work` requires:
- Work started
- All active service ops `DONE` (or none)
- Approved parts consumed (or none required)
- `technical_completion_notes` saved

Sets `work_execution_status = TECHNICAL_COMPLETED`, `technical_status = TECHNICAL_DONE`, `work_completed_at`.

## 10. Ready for QC

`ready_for_qc` only after technical completion validation. Sets `work_execution_status = READY_FOR_QC`, `jobcard_status = READY_FOR_QC`, `ready_for_qc_at`. Event `JOBCARD_READY_FOR_QC`. No QC checklist (P6 scope).

## 11. Audit / history

JobCard history + `erp_work_execution_events` for all P5 transitions including gate blocks, parts consumption, service ops, completion, hold, cancel.

## 12. Scope control confirmation

- No full purchase module
- No payment gateway
- No final invoice
- No accounting voucher
- No QC final module
- No delivery module
- P1.5 / P2 / P3 / P4 gates not bypassed

## 13. Tests passed

| Suite | Result |
|-------|--------|
| `test-p5-work-execution-schema.php` | 11/11 |
| `test-p5-work-execution-board.php` | 19/19 |
| `test-p5-p4-gate-enforcement.php` | 10/10 |
| `test-p5-parts-consumption-control.php` | 11/11 |
| `test-p5-technical-completion.php` | 10/10 |
| `test-p5-history-audit.php` | 24/24 |
| `test-p5-scope-control.php` | 12/12 |

PHP `-l` passed on all P5 PHP files plus `m360-estimate-helper.php` and `m360-parts-finance-gate-helper.php`.

`test-v1-production-signoff.php` — not run (requires production context).

## 14. Security

- No credentials in repo
- No real config changes
- No destructive SQL
- No auth/login core changes
- `staff-login.php`, `owner-login.php`, `access-control.php` untouched
- All state changes POST-only with CSRF

---

**MOGHARE360 P5 enables controlled work execution after approved estimate, approved parts consumption, service operation execution, and technical completion to READY_FOR_QC without adding full purchase, invoice, payment, accounting, QC, or delivery scope.**
