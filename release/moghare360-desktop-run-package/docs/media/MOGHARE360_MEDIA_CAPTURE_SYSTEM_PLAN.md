# MOGHARE360 — Media Capture System Plan

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 18  
**Implementation:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

The **Media and Diagnostic Capture System** provides tamper-evident operational evidence for every workshop JobCard: vehicle condition at intake, work progress, final delivery state, and diagnostic reports. It supports dispute resolution, QC accountability, and customer trust — without exposing files to the public domain or cloud.

---

## Role in Operational Evidence

| Evidence type | Business role |
|---------------|---------------|
| Input photos (6) | Document vehicle condition at intake |
| During-work photos | Optional stage captures tied to operations |
| Output photos (8) | Document completed work and delivery readiness |
| Diagnostic PDFs | Formal technical findings at intake, mid-work, and completion |
| Audit trail | Who captured what, when, on which JobCard |

---

## Connection to JobCard Lifecycle

```
JobCard DRAFT
    │
    ├── INPUT stage: 6 photos required ──► gate before technical workflow
    │
    ├── DIAGNOSTIC: Initial PDF
    │
    ├── DURING_WORK: optional media
    │
    ├── DIAGNOSTIC: Secondary PDF (mid-work)
    │
    ├── OUTPUT stage: 8 photos required ──► gate before delivery close
    │
    ├── DIAGNOSTIC: Final PDF
    │
    └── CLOSED (media immutable)
```

All media and diagnostics are **bound to JobCard** — no orphan files.

---

## Input Photos

- **Exactly 6** required input photos per `MOGHARE360_INPUT_PHOTO_6_RULE.md`
- Stage: `INPUT`
- **Camera direct only** — no file picker

---

## Output Photos

- **Exactly 8** required output photos per `MOGHARE360_OUTPUT_PHOTO_8_RULE.md`
- Stage: `OUTPUT`
- Required before delivery close workflow transition

---

## Diagnostic PDFs

| Stage | Document |
|-------|----------|
| Initial | Intake / first assessment |
| Secondary | Mid-work findings |
| Final | Completion / handover report |

Per `MOGHARE360_DIAGNOSTIC_PDF_CAPTURE_RULE.md` — versioned, immutable after registration.

---

## Validation Requirement

Media passes through **Validation Engine** before persistence:

| Check | Error |
|-------|-------|
| Camera source only | E-07 MEDIA_RULE_VIOLATION |
| File upload attempted | E-07 — **No upload bypass** |
| Photo count (6 input / 8 output) | E-07 |
| JobCard ref present | E-01 |
| Workflow state allows capture | E-04 |

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Workflow Requirement

| Gate | Media precondition |
|------|-------------------|
| Start technical operations | 6 input photos complete (or approved exception) |
| Initial diagnostic registered | JobCard state ≥ SUBMITTED policy |
| Delivery close | 8 output photos complete (or approved exception) |
| Final diagnostic | Before or with CLOSED transition |

Workflow Engine enforces transitions; Validation Engine enforces media rules.

---

## Audit Requirement

Every capture, failure, exception, and correction produces audit events per `MOGHARE360_MEDIA_AUDIT_IMMUTABILITY_RULE.md` and `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md`.

---

## Local-Only Storage Principle

| Rule | Requirement |
|------|-------------|
| Storage location | Owner laptop server filesystem |
| **No domain/host storage** | moghareh360.ir must not store media or PDFs |
| **No cloud media storage** | No S3, CDN, or host uploads |
| Web access | Paths outside public web root unless future approved design |
| Backup | Owner-controlled local backup |

Per `MOGHARE360_MEDIA_STORAGE_BOUNDARY.md`.

---

## Phase 18 Modules (Planned)

| Module | Document |
|--------|----------|
| Camera-only capture | `MOGHARE360_CAMERA_ONLY_CAPTURE_RULE.md` |
| 6 input photos | `MOGHARE360_INPUT_PHOTO_6_RULE.md` |
| 8 output photos | `MOGHARE360_OUTPUT_PHOTO_8_RULE.md` |
| JobCard binding | `MOGHARE360_JOBCARD_MEDIA_BINDING_RULE.md` |
| Diagnostic PDFs | `MOGHARE360_DIAGNOSTIC_PDF_CAPTURE_RULE.md` |
| Audit / immutability | `MOGHARE360_MEDIA_AUDIT_IMMUTABILITY_RULE.md` |
| Storage boundary | `MOGHARE360_MEDIA_STORAGE_BOUNDARY.md` |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — no PHP runtime, no camera UI, no upload fields, no SQL in Phase 18.

---

## Product Boundary

- No production SaaS · No public portal · No official accounting · No payment gateway
- moghareh360.ir = Mirror Only

---

**END OF MEDIA CAPTURE SYSTEM PLAN**
