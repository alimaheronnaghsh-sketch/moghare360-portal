# MOGHARE360 — Camera-Only Capture Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-07 MEDIA_RULE_VIOLATION  
**SQL:** No SQL required

---

## Core Rules

| Rule | Requirement |
|------|-------------|
| **Camera direct only** | Media ingested only from device camera API at capture time |
| **No upload bypass** | No alternate ingest path (API, admin tool, import script) without explicit future phase |
| **No free file upload** | No `<input type="file">`, drag-drop, or gallery picker |
| **No gallery upload** | Unless future owner approval explicitly changes this rule — **not in Phase 18** |

---

## Forbidden Capture Paths

| Path | Status |
|------|--------|
| File picker / browse | FORBIDDEN |
| Drag and drop | FORBIDDEN |
| Clipboard paste image | FORBIDDEN |
| URL fetch / download ingest | FORBIDDEN |
| Host or cloud sync folder | FORBIDDEN |
| Base64 POST from non-camera source | FORBIDDEN |

Validation Engine rejects all non-camera sources with E-07.

---

## Required Metadata

Every captured media record must include:

| Field | Description |
|-------|-------------|
| **captured_at** | Server-side timestamp (authoritative); device time as secondary |
| **captured_by** | Staff user ID from auth session |
| **jobcard_id** | Parent JobCard FK — mandatory |
| **media_stage** | `INPUT` \| `DURING_WORK` \| `OUTPUT` \| `DIAGNOSTIC` \| `EXCEPTION` |
| **device_context** | Device identifier class (tablet ID, reception PC, technician tablet) |

Metadata stored with media index row and audit event — not embedded as sole trust in EXIF.

---

## Re-Capture Policy

| Scenario | Policy |
|----------|--------|
| Blurry / failed first attempt | Allow re-capture **before registration** — prior attempt logged as failed |
| After registration | **Immutable** — replacement only via workflow correction (see immutability rule) |
| Wrong position (input 3 of 6) | Re-capture same slot before slot registration |
| Exception | Manager approval path — stage `EXCEPTION` |

---

## Failed Capture Policy

| Event | Action |
|-------|--------|
| Camera permission denied | Block submit; user message; **failed capture audit** |
| Hardware unavailable | Block; log failure; retry when device ready |
| Validation fail (size/format) | Reject; E-07; no partial save |
| Network drop during save | Retry idempotent save; audit `capture_retry` if needed |

---

## Diagnostic PDF Note

Diagnostic PDFs are generated or captured through approved diagnostic device workflow — still **no free file upload**. Device-generated PDF stream bound to JobCard with same metadata principles.

---

## Audit Requirement

| Event | Audit |
|-------|-------|
| Successful capture | `media_captured` — jobcard_id, stage, actor, timestamp |
| Failed capture | `media_capture_failed` — reason code |
| Re-capture (pre-registration) | `media_recapture` — slot/position |
| E-07 violation attempt | `media_rule_violation` — upload bypass attempt |

Per `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md`.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — no camera UI, no file inputs, no PHP capture service in Phase 18.

---

**END OF CAMERA-ONLY CAPTURE RULE**
