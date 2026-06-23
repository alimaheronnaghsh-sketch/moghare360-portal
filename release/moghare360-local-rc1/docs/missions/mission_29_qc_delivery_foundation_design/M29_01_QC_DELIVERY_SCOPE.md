# QC / Delivery Scope

## Purpose
This document locks the scope of QC and delivery foundation design for MOGHARE360 ERP Soft Run.

## Mission Goal (Locked)
Design quality control and vehicle delivery foundation for Soft Run.

## In Scope (Design Only)
Mission 29 defines:
- QC checklist entity plan (`erp_qc_checks`)
- QC history plan (`erp_qc_check_history`)
- Delivery control plan (`erp_delivery_controls`, `erp_delivery_control_history`)
- QC checklist items (design checklist)
- Delivery flow from service completion to release
- JobCard status transition model (design only — no live status change)
- Customer confirmation boundary (no signature implementation)
- Payment before delivery rules (design only — no enforcement execution)
- Permission and audit rules
- SQL implementation plan (no execution)
- UI plan for Mission 30 (no file creation)
- Testing plan for Mission 30 (no runnable tests)

## Core Scope Rules (Locked)

### QC Checklist
- One QC check record per JobCard QC event (design)
- Optional link to `service_operation_id`
- Status: PENDING, PASSED, FAILED, RECHECK_REQUIRED, CANCELLED
- Checklist items documented as design reference

### Delivery Control
- Delivery allowed/blocked based on QC + payment summary review (design)
- `delivery_status`: BLOCKED, READY, RELEASED, CANCELLED
- No actual delivery write in Mission 29

### Soft Run Readiness Gate
- Combined visibility: JobCard, service, parts, payment, QC, delivery (Mission 30 UI plan)
- Read-only aggregation where possible

## Out of Scope (Locked)
Mission 29 must not:
- Execute SQL
- Create code or PHP operational files
- Write QC records
- Write delivery records
- Implement customer signature
- Finalize invoices
- Execute payment enforcement (block/release automation)
- Modify Customer Portal
- Deploy to production
- Change live JobCard status
- Modify forbidden or legacy files

## Relationship to Prior Missions

| Prior Mission | Relationship |
|---------------|--------------|
| M20 | Service operations — QC may link to service_operation_id |
| M24 | Part usage — optional context for Soft Run gate |
| M28 | Payment summary — reviewed before delivery (design rule) |
| M17 | JobCard mandatory context for QC and delivery |

## Future Mission Chain (Locked Reference)
- Mission 30 — QC check + delivery control + Soft Run readiness prototype
- Future mission — Customer signature (production)
- Future mission — Invoice finalization
- Future mission — Payment gate enforcement policy

## Mission 29 Boundary
Design only. No QC rows. No delivery rows. No status changes.

## Final Scope Decision
Mission 29 = QC + delivery design for Soft Run; execution deferred to Mission 30; signature, invoice, and payment enforcement remain design-only.
