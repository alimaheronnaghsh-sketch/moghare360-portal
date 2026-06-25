# MOGHARE360 — Daily Error Log Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Error Log Purpose

**Any operational error must be logged.** The daily error log captures workshop friction during live run — blocks, failures, fallback usage — for owner review and continuous improvement without bypassing system controls.

---

## Error Categories

| Category | Examples |
|----------|----------|
| **Validation failure** | National ID, mobile, plate, VIN format reject (E-02) |
| **Workflow block** | Illegal state transition (E-04); JobCard stuck |
| **Permission block** | Staff lacks permission (E-05) |
| **Media capture issue** | Camera fail, incomplete 6/8 photos, E-07 |
| **Contract/approval issue** | Ceiling breach, out-of-contract pending, missing acceptance |
| **Network/server issue** | SQL unreachable, Apache down, LAN dropout |
| **User training issue** | Wrong workflow step, data entry mistake |
| **Manual fallback usage** | Paper form used — system unavailable |

---

## Required Fields

| Field | Description |
|-------|-------------|
| **date/time** | When issue occurred |
| **user** | Staff user ID or name |
| **module** | Reception, JobCard, QC, Delivery, Media, Contract, Network |
| **issue** | Short description + error code if system-generated |
| **severity** | Low / Medium / High / Critical |
| **action taken** | Retry, manager override request, fallback, training |
| **owner decision** | Owner/admin note — approve exception, fix tomorrow, etc. |
| **follow-up status** | Open / In progress / Resolved / Deferred |

---

## Logging Rules

| Rule | Requirement |
|------|-------------|
| Same-day entry | Errors logged before day-end report |
| System-generated | Validation/workflow blocks auto-suggest log entry (future) |
| Manual entry | Staff can add training/fallback entries |
| No PII in shared log export | Mask national ID in external shares |

---

## Severity Guide

| Level | Criteria |
|-------|----------|
| Low | Single retry success; user error |
| Medium | Job delayed < 2 hours; workaround used |
| High | Job blocked rest of day; fallback used |
| Critical | Server down; data integrity concern |

---

## Owner Review

- CRM/admin or owner reviews log at day-end
- Critical items require same-day owner decision field
- Unresolved → next-day action list in day-end report

---

## Relation to Audit

System audit log (`core_audit_logs`, domain histories) is authoritative for writes. Daily error log is **operational supplement** — not a substitute for audit.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — log may start as controlled spreadsheet or document until ERP module approved.

---

**END OF DAILY ERROR LOG RULE**
