# MOGHARE360 — JobCard Live Entry Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## JobCard Live Entry Requirements

Live JobCard creation is the operational anchor linking customer, vehicle, contract, media, and workflow for each workshop visit.

---

## Customer Binding

| Rule | Requirement |
|------|-------------|
| `customer_id` | Validated customer master FK |
| Identity | Phase 17 validators passed at customer create/update |
| Duplicate policy | National ID / mobile checks enforced |

---

## Vehicle Binding

| Rule | Requirement |
|------|-------------|
| `vehicle_id` | Validated vehicle master FK |
| Customer link | Vehicle bound to customer per domain rules |
| Plate/VIN | Unique per active vehicle policy |

---

## Contract Binding

| Rule | Requirement |
|------|-------------|
| Service contract ref | Linked per `MOGHARE360_CONTRACT_TO_WORKFLOW_BINDING_RULE.md` |
| State | Contract **APPLIED** before chargeable operations (except inspection-only path) |
| Ceiling / authorization | Enforced on operation submit |

---

## Initial Diagnostic Binding

| Rule | Requirement |
|------|-------------|
| Initial Diagnostic PDF | Registered per `MOGHARE360_DIAGNOSTIC_PDF_CAPTURE_RULE.md` |
| Timing | After intake; before or with SUBMITTED transition (policy) |
| JobCard FK | Mandatory — stage `DIAGNOSTIC`, type `INITIAL` |

---

## Input Media Binding

| Rule | Requirement |
|------|-------------|
| 6 input photos | Complete set or approved exception |
| Stage | `INPUT` |
| No orphan media | Per `MOGHARE360_JOBCARD_MEDIA_BINDING_RULE.md` |

---

## Workflow Starting State

| Field | Value |
|-------|-------|
| Initial state | **DRAFT** |
| Allowed transitions | Per `MOGHARE360_WORKFLOW_STATE_TRANSITION_CONTRACT.md` |
| DRAFT → SUBMITTED | Validation pass + submit permission + intake preconditions |
| Illegal jumps | FORBIDDEN (e.g. DRAFT → APPLIED) |

---

## Required Fields (Live Entry)

Per Critical Forms v2 — JobCard create:

- Customer ref, vehicle ref, jobcard type, service category (dropdowns)
- Free text: customer complaint, reception notes only

---

## Required Audit

| Event | When |
|-------|------|
| `jobcard_created` | Live entry save |
| `jobcard_submitted` | DRAFT → SUBMITTED |
| `validation_failed` | Any block on entry |
| `jobcard_change_history` | Master field changes |

Per `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md`.

---

## No Bypass

- No direct UI→database
- No validation/workflow/audit skip

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF JOBCARD LIVE ENTRY RULE**
