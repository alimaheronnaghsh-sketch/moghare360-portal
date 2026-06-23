# MOGHARE360 — Manual Fallback Protocol

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Any real operational use must have manual fallback protocol.** Fallback keeps the workshop operating when ERP is unavailable — **no permanent bypass** of validation, workflow, or audit.

---

## When Manual Fallback Is Allowed

| Condition | Allowed |
|-----------|---------|
| Laptop server offline | Yes — after manager notify |
| SQL Server unreachable | Yes |
| Apache/PHP failure | Yes |
| Extended LAN outage | Yes — paper only on floor |
| Validation disagreement | **No** — fix data, do not bypass |
| Staff convenience | **No** |
| Skip contract/photos | **No** — unless owner emergency policy |

---

## Manager Approval for Fallback

| Step | Requirement |
|------|-------------|
| 1 | Staff notifies manager/owner |
| 2 | Manager declares fallback mode — timestamp recorded |
| 3 | Daily error log entry: `manual_fallback_usage` |
| 4 | Owner notified if outage > 1 hour |

---

## Paper Fallback Form Concept

Single **workshop intake / JobCard fallback sheet** (owner-printed):

- Sequential fallback ID (paper number)
- Date/time
- Reception staff name
- Customer: name, mobile, national ID (if available)
- Vehicle: plate, brand/model note
- Complaint / requested service
- Authorization note (verbal ceiling if any)
- Manager signature

**Minimum required paper fields** — no blank handover without identity.

---

## Photo / Diagnostic Fallback Handling

| Asset | Fallback |
|-------|----------|
| Input photos | Capture on tablet/phone camera — store locally on device until ERP up |
| Output photos | Same |
| Diagnostic PDF | Device export saved locally; attach to JobCard when restored |
| **No upload to moghareh360.ir** | Transfer to laptop server only |

When system restored: register per Phase 18 camera/metadata rules — manager flags `fallback_import` audit.

---

## Later System Entry Requirement

| Rule | Requirement |
|------|-------------|
| **Later system entry requirement** | All paper fallbacks entered into ERP within owner SLA (recommended: same business day) |
| Order | Customer → vehicle → contract → JobCard → media import |
| Backdate policy | Use actual intake timestamps in notes — server time at entry |
| Duplicate check | Phase 17 validators on entry |

---

## Audit Correction Requirement

| Event | Detail |
|-------|--------|
| `fallback_mode_started` | Manager, time |
| `fallback_paper_issued` | Paper ID |
| `fallback_erp_backfill` | Actor, paper ID → jobcard_id |
| `fallback_media_imported` | Count, stages |

**Audit correction requirement** — backfill must produce full audit chain, not silent insert.

---

## No Permanent Bypass

| Rule | Status |
|------|--------|
| Paper as system of record | FORBIDDEN beyond SLA |
| Skip ERP for "simple jobs" | FORBIDDEN |
| Disable validation on backfill | FORBIDDEN |
| **No permanent bypass** | LOCKED |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — paper template and ERP backfill workflow in future approved phase.

---

**END OF MANUAL FALLBACK PROTOCOL**
