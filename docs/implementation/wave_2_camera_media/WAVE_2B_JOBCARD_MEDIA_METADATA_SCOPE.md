# WAVE 2B — JobCard Media Metadata Scope

**Wave:** IMPLEMENTATION WAVE 2B  
**Parent:** IMPLEMENTATION WAVE 2 — Camera-only Media + Diagnostic Binding  
**Date:** 2026-06-23

---

## Objective

Bind locally saved camera-only JobCard media to controlled database metadata records when a safe existing schema is confirmed.

---

## Inspection Result

| Item | Finding |
|------|---------|
| `erp_jobcard_media` | Not found in SQL Server ERP schema |
| `erp_jobcard_files` | Not found |
| `erp_media_files` | Not found |
| `erp_jobcard_attachments` (dbo) | Not found |
| `erp_jobcard_media_history` | Not found |
| `portal_jobcard_attachments` | MySQL portal table — excluded (not safe ERP target) |
| `erp_vehicle_photo_records` | Vehicle binding photos — wrong domain (no `jobcard_id`) |
| `erp_jobcards` | Exists — used for JobCard reference validation when DB available |
| `erp_jobcard_change_history` | Exists — optional audit target for `MEDIA_REGISTERED` when metadata write succeeds |

**Decision:** Metadata DB write **blocked** — `BLOCKED_SAFE_MEDIA_SCHEMA_NOT_CONFIRMED`

---

## Implemented

| Component | Status |
|-----------|--------|
| Metadata helper with schema inspection | ✅ |
| Submit: local save then metadata bind attempt | ✅ |
| Preview: local files + metadata read when schema confirmed | ✅ |
| Upload bypass remains disabled | ✅ |
| Camera-only capture unchanged | ✅ |

---

## Boundaries

- No SQL file created
- No schema change
- No auth/config/permission change
- No public portal / SaaS / accounting / payment gateway
- Diagnostic PDF binding not activated
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
