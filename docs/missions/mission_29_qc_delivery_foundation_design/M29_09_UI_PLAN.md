# UI Plan

## Purpose
This document defines the UI plan for QC, delivery, and Soft Run readiness.

## Mission 29 Boundary
UI planned only. **No PHP files created in Mission 29.**

## Mission 30 Planned Pages (Locked Reference)

### 1. erp-qc-check.php
**Purpose:** Create and update QC check for JobCard.

**Behavior (indicative):**
- POST create QC check (PENDING)
- POST pass → PASSED (`qc.check.pass`)
- POST fail → FAILED (`qc.check.fail`)
- Checklist items from M29_02 in form or note field
- Transaction: QC row + history
- CSRF + Permission Guard

### 2. erp-delivery-control.php
**Purpose:** View and control delivery allow/block for JobCard.

**Behavior (indicative):**
- Read QC status + payment summary (calculated)
- Set `delivery_allowed`, `block_reason`, `delivery_status`
- POST release → RELEASED (`delivery.control.release`) — prototype only
- No payment create
- No invoice
- No customer signature UI

### 3. erp-soft-run-readiness.php
**Purpose:** Soft Run gate — aggregated readiness view.

**Sections (indicative):**
- Customer exists (from JobCard)
- Vehicle exists (from JobCard)
- JobCard exists + status display
- Service operation exists (optional list)
- Part usage optional (M24 link)
- Payment optional or documented (M28 summary)
- QC status
- Delivery allowed / blocked + reason
- Payment full required vs optional indicator (design flag)

**Behavior:**
- Read-only aggregation + links to create pages
- Permission: `soft.run.readiness.view`
- No production deploy banner

## UI Elements Explicitly Deferred
- Customer signature pad
- Customer Portal pages
- Invoice finalize button
- Automatic payment gate enforcement UI (beyond display)
- Tax / accounting export

## Forbidden UI Changes (Locked)
- No Customer Portal modification
- No config / auth file changes

## Mission 29 Deliverable
This UI plan document only.

## Final UI Decision
Three PHP pages in Mission 30: QC check, delivery control, Soft Run readiness; internal prototype only.
