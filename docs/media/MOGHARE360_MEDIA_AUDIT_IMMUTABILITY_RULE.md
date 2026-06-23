# MOGHARE360 — Media Audit / Immutability Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principles

| Principle | Rule |
|-----------|------|
| **Immutable after registration** | Registered media bytes and metadata must not change in place |
| **No silent delete** | Delete attempts logged and blocked without correction workflow |
| **No silent replace** | Replacement requires new version + audit trail |
| **Owner/admin approval for correction** | Manager or admin role for exception and replacement |

---

## Media Creation Audit

| Event | Required fields |
|-------|-----------------|
| `media_captured` | media_id, jobcard_id, stage, slot, captured_by, captured_at, device_context |
| `diagnostic_pdf_registered` | pdf_id, jobcard_id, diagnostic_type, version, captured_by |

Append to audit log per `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md` — never skip on successful persist.

---

## Diagnostic PDF Creation Audit

| Event | When |
|-------|------|
| `diagnostic_pdf_registered` | Initial / Secondary / Final first registration |
| `diagnostic_pdf_version_created` | Correction replacement |

---

## Failed Capture Audit

| Event | When |
|-------|------|
| `media_capture_failed` | Camera error, permission denied, validation fail |
| `media_rule_violation` | Upload bypass attempt (E-07) |

Includes actor, jobcard_id (if known), reason code — no binary payload in audit.

---

## Exception Approval Audit

| Event | When |
|-------|------|
| `input_photo_exception_approved` | Manager waives input slot(s) |
| `output_photo_exception_approved` | Manager waives output slot(s) |
| `media_exception_approved` | General exception tag |

Must include approver ID, reason text, affected slots.

---

## Replacement / Correction Audit

| Event | When |
|-------|------|
| `media_correction_requested` | Staff requests replace registered media |
| `media_correction_approved` | Admin/owner approves |
| `media_replaced` | New version registered; old marked superseded |
| `diagnostic_pdf_replaced` | New PDF version for same diagnostic_type |

Old record remains in history — **no hard delete** of audit or superseded index.

---

## Immutability Enforcement (Future Runtime)

| Layer | Control |
|-------|---------|
| Filesystem | Write-once path or WORM policy per registered file |
| Database | Status `REGISTERED` → block UPDATE on blob path |
| API | No DELETE endpoint; correction endpoint only |
| Validation | Reject overwrite POST |

---

## Forbidden Actions

| Action | Status |
|--------|--------|
| Silent file delete | FORBIDDEN |
| Silent file replace on disk | FORBIDDEN |
| Detach from JobCard | FORBIDDEN |
| Audit skip on write | FORBIDDEN — E-09 rollback |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF MEDIA AUDIT / IMMUTABILITY RULE**
