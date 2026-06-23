# MOGHARE360 — Diagnostic PDF Capture Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Overview

Three diagnostic PDF stages provide structured technical evidence bound to each JobCard. All PDFs follow **camera/device capture principles** — **no free file upload**.

---

## Diagnostic Stages

| Stage | Name | Typical timing |
|-------|------|----------------|
| 1 | **Initial Diagnostic PDF** | Intake / first assessment — after vehicle acceptance |
| 2 | **Secondary Diagnostic PDF** | Mid-work — findings after disassembly or test |
| 3 | **Final Diagnostic PDF** | Completion — summary before customer handover |

Each stage is a separate versioned record on the same JobCard.

---

## Binding to JobCard

| Requirement | Rule |
|-------------|------|
| `jobcard_id` | Mandatory |
| `media_stage` | `DIAGNOSTIC` |
| `diagnostic_type` | `INITIAL` \| `SECONDARY` \| `FINAL` |
| Orphan PDF | FORBIDDEN |

---

## Versioned Records

| Rule | Detail |
|------|--------|
| Version number | Monotonic per JobCard + diagnostic_type |
| Active version | One current registered version per type (policy) |
| History | Prior versions retained when replaced through correction workflow |
| Filename | Server-generated; not user-supplied path |

---

## Immutability After Registration

| State | Rule |
|-------|------|
| After registration | PDF **immutable** — bytes must not be overwritten |
| **Allowed replacement** | Only through workflow/audit correction — new version row + audit |
| In-place edit | FORBIDDEN |
| Silent replace | FORBIDDEN |

---

## Diagnostic Source Device Note

| Field | Purpose |
|-------|---------|
| `source_device` | Diagnostic tool, tablet, or workshop PC identifier |
| `source_method` | Device-native PDF export / approved capture pipeline |
| `captured_at` | Server timestamp |
| `captured_by` | Staff user ID |

**No gallery or file-picker upload** — device must produce PDF through approved capture channel.

---

## Workflow Gates

| PDF | Typical workflow precondition |
|-----|------------------------------|
| Initial | JobCard ≥ SUBMITTED; INPUT photos policy |
| Secondary | Operations in progress; APPROVED/APPLIED |
| Final | OUTPUT photos policy; pre-CLOSED |

Per `MOGHARE360_WORKFLOW_STATE_TRANSITION_CONTRACT.md`.

---

## Validation

| Check | Error |
|-------|-------|
| Free upload | E-07 |
| Missing jobcard_id | E-01 |
| Wrong state for type | E-04 |
| Unapproved replacement | E-08 |

---

## Local-Only Storage

| Rule | Requirement |
|------|-------------|
| PDF files | Owner laptop server filesystem |
| **No host/domain storage** | moghareh360.ir must not store diagnostic PDFs |
| **No cloud storage** | No external PDF hosting |
| Web path | Outside public web root unless future approved design |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `diagnostic_pdf_registered` | type, version, jobcard_id |
| `diagnostic_pdf_replaced` | old_version, new_version, approver |
| `diagnostic_pdf_rejected` | validation fail |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF DIAGNOSTIC PDF CAPTURE RULE**
