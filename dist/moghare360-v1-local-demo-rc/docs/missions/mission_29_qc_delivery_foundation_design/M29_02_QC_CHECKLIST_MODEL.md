# QC Checklist Model

## Purpose
This document defines the planned QC checklist data model and checklist items.

## Mission 29 Boundary
Model documented only. No QC tables or writes in Mission 29.

## Proposed Entity: dbo.erp_qc_checks

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| qc_check_id | INT | No | PK, IDENTITY |
| jobcard_id | INT | No | FK → dbo.erp_jobcards |
| service_operation_id | INT | Yes | FK → dbo.erp_service_operations |
| qc_status | NVARCHAR(30) | No | See status model |
| checked_by_user_id | INT | No | Inspector / staff |
| checked_at | DATETIME2 | No | Default SYSUTCDATETIME() |
| qc_note | NVARCHAR(MAX) | Yes | Findings / notes |
| is_active | BIT | No | Default 1 |

## QC Status Model (Locked)

| Status | Meaning |
|--------|---------|
| PENDING | QC not yet completed |
| PASSED | QC approved |
| FAILED | QC rejected — rework required |
| RECHECK_REQUIRED | Failed items fixed — needs re-inspection |
| CANCELLED | QC cancelled — no physical delete |

## Proposed Checklist Items (Design Reference)

Mission 29 locks these as **design checklist items** for Mission 30 UI and future item-level tables:

| # | Checklist Item |
|---|----------------|
| 1 | Work completed |
| 2 | No visible leak |
| 3 | No warning light |
| 4 | Road test if required |
| 5 | Parts installed correctly |
| 6 | Customer complaint addressed |
| 7 | Final visual inspection |
| 8 | Tools/items removed from vehicle |

### Mission 30 Initial Scope (Indicative)
- Checklist items may be captured in `qc_note` free text or boolean fields in a future `erp_qc_check_items` table
- Mission 30 prototype may use simplified pass/fail without per-item rows unless chartered

## Proposed History: dbo.erp_qc_check_history

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| history_id | INT | No | PK, IDENTITY |
| qc_check_id | INT | No | FK |
| jobcard_id | INT | No | Denormalized |
| action_code | NVARCHAR(80) | No | e.g. QC_CHECK_CREATED |
| old_status | NVARCHAR(30) | Yes | Previous qc_status |
| new_status | NVARCHAR(30) | Yes | New qc_status |
| changed_by_user_id | INT | No | Auth user |
| changed_at | DATETIME2 | No | Default SYSUTCDATETIME() |
| change_note | NVARCHAR(MAX) | Yes | Reason / detail |

## Validation Rules (Future M30)
- jobcard_id must be valid ACTIVE JobCard
- service_operation_id if set must belong to same JobCard
- qc_status in allowed enum
- PASSED / FAILED require explicit action — **no silent QC pass**

## Mission 29 Boundary
QC model documented only.

## Final QC Model Decision
erp_qc_checks + history with five statuses; eight checklist items as design reference; per-item table optional in M30+.
