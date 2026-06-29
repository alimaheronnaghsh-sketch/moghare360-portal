# MOGHARE360 P11.7 — Workbench Scope Gate Report

**Phase:** P11.7 — Employee Workbench Consolidation  
**Date:** 2026-06-26  
**Gate status:** **PASS — navigation/workbench consolidation only**

---

## Owner concerns addressed

1. Employees cannot find daily work pages after login — too many reports/demo pages vs operational desks.
2. `erp-staff-home.php` is a small link hub, not a role workbench.
3. Broken staff-home links (`erp-jobcard-part-usage-list.php`, `erp-finance-center.php`).
4. Hidden but existing workflow pages (contract board, estimate board, part usage) not exposed on landing.
5. One-Day Run requires clear per-role starting points without new workflow code.

---

## Audit findings handled (P11.6-0 / P11.6-1)

| Finding | P11.7 action |
|---------|--------------|
| Staff home is link hub only | Grouped workbench: کار امروز / پیگیری / عملیات / گزارش / backlog |
| Broken PARTS link to missing `erp-jobcard-part-usage-list.php` | Link `erp-jobcard-part-use.php`; backlog card for old name |
| Broken FINANCE link to missing `erp-finance-center.php` | Disabled backlog card; link payment tracking + invoice board |
| Contract/intake hidden from RECEPTION | Expose `erp-intake-contracts.php` |
| Estimate board hidden from FINANCE | Expose `erp-estimate-board.php` |
| Part usage hidden from PARTS | Expose `erp-jobcard-part-use.php` |
| QC detail only via board | Document in follow-up group (info card) |
| No technician “my jobs” filter | **Backlog disabled card only** — no fake filter |
| HR self-service missing | **Backlog cards only** — no build |
| Product home mixes demo + ops | Out of scope for staff home; owner workbench unchanged |

---

## Included in P11.7

- Scope gate report (this document)
- One-Day Run workbench coverage matrix doc
- Refactor `m360-staff-home-helper.php` — grouped workbench definitions per role
- Update `erp-staff-home.php` — render grouped sections + role start question
- Update `m360-staff-home.css` — group headers, status badges, backlog styling
- Fix broken links to existing substitute files
- Expose existing hidden operational pages (navigation cards only)
- Disabled backlog cards for missing files (non-clickable)
- Disabled backlog card for technician assignment filter (not implemented)
- Five P11.7 CLI tests + regression on P11.4.4 authorization and production signoff
- Preserve `allowed_routes` flattening for backward-compatible authorization checks

---

## Explicitly Excluded from P11.7

- Auth/Login architecture or password logic changes
- Permission/role seed changes
- Database schema changes
- Workflow state machine or action handler logic changes
- Technician assignment filter implementation (DB/query)
- Creating `erp-finance-center.php`, `erp-jobcard-part-usage-list.php`, `erp-purchase-request-list.php`
- HR self-service module (attendance, leave, overtime)
- P12 scope
- Breadcrumbs on P1–P7 detail pages (would touch many unrelated files)
- Product home / route map restructuring
- OTP, online bridge, access management logic changes

---

## Existing Work Pages to Reuse

| File | Roles |
|------|-------|
| `erp-reception-online-requests.php` | RECEPTION |
| `erp-reception-jobcards.php` | RECEPTION |
| `erp-intake-contracts.php` | RECEPTION |
| `erp-technical-board.php` | SERVICE_MANAGER, TECHNICIAN |
| `erp-work-execution-board.php` | SERVICE_MANAGER, TECHNICIAN |
| `erp-qc-board.php` | SERVICE_MANAGER, QC |
| `erp-jobcard-part-use.php` | PARTS, TECHNICIAN |
| `erp-part-reserve.php` | PARTS |
| `erp-purchase-request-create.php` | PARTS |
| `erp-parts-catalog.php` | PARTS |
| `erp-stock-board.php` | PARTS |
| `erp-payment-tracking.php` | FINANCE |
| `erp-estimate-board.php` | FINANCE |
| `erp-final-invoice-board.php` | FINANCE |
| `erp-delivery-control.php` | QC |
| Owner/management pages | OWNER, SYSTEM_ADMIN |

Action endpoints (`*-action.php`, `erp-reception-online-request-accept.php`) — **not direct cards**; accessed via board/detail forms.

---

## Missing Pages Not to Build

| File | Treatment |
|------|-----------|
| `erp-finance-center.php` | Disabled backlog card (FINANCE) |
| `erp-jobcard-part-usage-list.php` | Disabled backlog card (PARTS) — substitute linked |
| `erp-purchase-request-list.php` | Disabled backlog card (PARTS) |
| Technician “my jobs” filter | Disabled backlog card (TECHNICIAN) |
| HR self-service pages | Disabled backlog cards — P15 backlog label |

---

## Broken Links to Fix

| Broken | Fix |
|--------|-----|
| `erp-jobcard-part-usage-list.php` | Card → `erp-jobcard-part-use.php` |
| `erp-finance-center.php` | Remove clickable link; backlog + use payment tracking |

---

## Hidden Pages to Expose

| Page | Role |
|------|------|
| `erp-intake-contracts.php` | RECEPTION |
| `erp-estimate-board.php` | FINANCE |
| `erp-jobcard-part-use.php` | PARTS |
| `erp-purchase-request-create.php` | PARTS |
| `erp-jobcard-part-readonly-list.php` | PARTS (report group) |
| `erp-service-operation-detail.php` | TECHNICIAN (follow-up, via technical detail flow) |

---

## Role Workbench Plan

Each role gets five groups (empty groups omitted):

1. **کار امروز** — primary daily entry screens  
2. **پیگیری و جزئیات** — detail/list continuation (info cards where ID required)  
3. **عملیات مجاز** — notes on POST/action flow where relevant  
4. **گزارش‌های مرتبط** — read-only timelines, catalogs, permission preview  
5. **موارد غیرفعال / نیازمند تکمیل** — missing/future items  

Persian role start question shown in banner per role.

---

## One-Day Run Link Coverage Plan

Document in `MOGHARE360_P11_7_ONE_DAY_RUN_WORKBENCH_COVERAGE.md` mapping each operational step → workbench card → existing file → action possible → gap.

---

## Stop Conditions

| Condition | Triggered? |
|-----------|------------|
| DB schema change required | **No** |
| Permission seed change required | **No** |
| Role seed change required | **No** |
| Auth/Login change required | **No** |
| New workflow logic required | **No** |
| P12 scope required | **No** |

**Gate result:** Implementation may proceed as navigation/workbench/linking/UI consolidation only.

---

## Final Implementation Boundary

**In:** Staff home helper, staff home page, staff home CSS, audit docs, P11.7 tests.  
**Out:** Everything else in Forbidden Changes list (section B of P11.7 spec).

P11.7 consolidates existing employee work pages into clear role-based daily workbenches without creating new workflow domains.
