# MOGHARE360 — JobCard Media Binding Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Core Rule

**Every media item must belong to a JobCard.**  
**No orphan media** — files without `jobcard_id` must not be persisted.

---

## Media Stages

| Stage | Code | Content |
|-------|------|---------|
| Intake evidence | **INPUT** | 6 input photos |
| Work in progress | **DURING_WORK** | Optional operation photos |
| Completion evidence | **OUTPUT** | 8 output photos |
| Diagnostic reports | **DIAGNOSTIC** | Initial / Secondary / Final PDF |
| Approved deviation | **EXCEPTION** | Exception-tagged media or PDF |

Stage stored in `media_stage` metadata on every record.

---

## Binding Requirements

| Requirement | Rule |
|-------------|------|
| `jobcard_id` | Mandatory FK on create |
| Customer / vehicle | Resolved through JobCard — not duplicate bind on media row |
| Diagnostic PDF | Same JobCard FK as photos |
| Local file path | Indexed in DB row; file on local server only |
| **Media must not be detached after registration** | No nulling `jobcard_id`; no move to another JobCard without correction workflow |

---

## Link to Workflow State

| JobCard state | Media allowed |
|---------------|---------------|
| DRAFT | INPUT capture may begin |
| SUBMITTED | INPUT complete gate; Initial diagnostic |
| APPROVED / APPLIED | DURING_WORK, Secondary diagnostic |
| Pre-CLOSED | OUTPUT capture; Final diagnostic |
| CLOSED | Read-only; immutability enforced |

Illegal capture for current state → E-04 INVALID_STATE_TRANSITION or E-07.

---

## Link to Audit

Every bind and state association logged:

| Event | Payload |
|-------|---------|
| `media_registered` | jobcard_id, media_id, stage, actor |
| `media_bind_rejected` | orphan attempt |
| `media_state_mismatch` | workflow violation |

---

## Detach / Rebind Policy

| Action | Allowed |
|--------|---------|
| Detach from JobCard | **FORBIDDEN** after registration |
| Rebind to different JobCard | **FORBIDDEN** — correction workflow creates new registered record |
| Soft-delete | **FORBIDDEN** — no silent delete |

---

## Local-Only Storage

- File bytes on owner laptop server only
- DB index in MOGHARE360_ERP local SQL Server
- **No domain/host storage**

Per `MOGHARE360_MEDIA_STORAGE_BOUNDARY.md`.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF JOBCARD MEDIA BINDING RULE**
