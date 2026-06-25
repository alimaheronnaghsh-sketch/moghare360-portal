# MOGHARE360 — Technician Tablet View Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Technician Tablet View Scope

Defines what technicians see on workshop tablets during live run. **No new tablet UI in Phase 20** — rule planning only for future implementation.

---

## Read-Only Job Details (Default)

| Rule | Requirement |
|------|-------------|
| **Read-only job details for technician unless future approval changes rule** | LOCKED for Phase 20 planning |
| Technician sees | Assigned JobCard summary, vehicle, complaint, authorized services |
| Technician does not edit | Customer master, contract ceiling, financial totals |

Status updates and operation execution may be write paths in future phase — gated by permission, not open edit.

---

## Assigned JobCard Visibility

| Visibility | Rule |
|------------|------|
| Assigned jobs only | Technician sees JobCards assigned to their user/team |
| Unassigned queue | Hidden unless `operation.queue.view` permission (future) |
| Other technicians' active jobs | Hidden by default |

---

## Service Operation Visibility

| Data | Visibility |
|------|------------|
| Operation steps | Current step, status enum |
| Authorized services | From contract — read-only |
| Out-of-contract flags | Visible if pending approval |
| Blocked state | Show reason (ceiling, approval) without bypass |

---

## Parts / Request Visibility Planning

| Data | Visibility |
|------|------------|
| Part reservations | Read — linked to JobCard |
| Purchase requests | Read — status only |
| Create reservation request | Future write — inventory permission |
| Supplier pricing | Hidden from technician unless approved |

Phase 21 will complete inventory/parts rules.

---

## Media / Diagnostic Visibility Planning

| Data | Visibility |
|------|------------|
| 6 input photos | View — intake evidence |
| During-work photos | View + capture (camera direct) — future |
| Diagnostic PDFs | View Initial/Secondary — read-only |
| Capture new diagnostic | Device workflow — Phase 18 rules |

---

## No Unauthorized Write

| Write | Gate |
|-------|------|
| Customer master | FORBIDDEN for technician role |
| Contract / ceiling | FORBIDDEN |
| QC decision | QC role only |
| Delivery close | Delivery role only |
| **No unauthorized write** | Permission + workflow enforced |

---

## No Customer Financial / Accounting Exposure

| Data | Rule |
|------|------|
| Contract ceiling amount | Hidden or masked — owner policy |
| Finance preview totals | **No customer financial/accounting exposure unless approved** |
| Official accounting | Not active |
| Payment gateway | Not active |
| Payment tracking preview | Delivery/admin roles only — preview not accounting |

---

## Network Access

- Tablet on workshop LAN only (default)
- No ERP on moghareh360.ir

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — no tablet UI created in Phase 20.

---

**END OF TECHNICIAN TABLET VIEW RULE**
