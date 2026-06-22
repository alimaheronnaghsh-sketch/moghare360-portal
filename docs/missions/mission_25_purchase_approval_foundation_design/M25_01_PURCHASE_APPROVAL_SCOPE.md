# Purchase Approval Scope

## Purpose
This document locks the scope of Purchase Approval foundation design for MOGHARE360 ERP.

## Mission Goal (Locked)
Design purchase request and purchase approval when a required part is not in stock.

## In Scope (Design Only)
Mission 25 defines:
- Purchase Request entity plan (`erp_purchase_requests`)
- Purchase Request history plan (`erp_purchase_request_history`)
- Waiting parts flow linkage to JobCard / Service Operation
- Approval rules (status transitions, no auto-approval)
- Supplier boundary (placeholder only)
- Finance boundary (no payment, informational cost only)
- Permission and audit rules
- SQL implementation plan (no execution)
- UI plan for Mission 26 (no file creation)
- Testing plan for Mission 26 (no runnable tests)

## Core Scope Rules (Locked)

### Purchase Request
- Created when stock is insufficient for operational need
- Linked to JobCard (mandatory context)
- Optionally linked to Service Operation and catalog `part_id`
- Approval workflow designed but not executed in Mission 25

### Waiting Parts Context
- Service Operation `WAITING_PARTS` status is operational signal
- Purchase Request does not auto-change Service Operation status in M25/M26 initial scope

## Out of Scope (Locked)
Mission 25 must not:
- Execute SQL
- Create code or PHP operational files
- Implement supplier contracts
- Process supplier payment
- Perform finance write (AP, ledger, journal)
- Perform stock receipt (RECEIPT movement)
- Execute real purchase orders
- Implement automatic approval
- Modify forbidden or legacy files

## Relationship to Prior Missions

| Prior Mission | Relationship |
|---------------|--------------|
| M21 | Purchase boundary first defined |
| M24 | Part usage ISSUE when stock exists; purchase when not |
| M22 | `erp_parts` master; `part_id` optional on request |
| M20 | `service_operation_id` optional link |

## Future Mission Chain (Locked Reference)
- Mission 26 — Purchase request controlled create + list + detail (DRAFT/SUBMITTED)
- Future mission — Approval actions (APPROVED/REJECTED)
- Future mission — ORDERED, stock RECEIPT, supplier selection
- Future finance mission — Payment, AP, costing linkage

## Mission 25 Boundary
Design only. No purchase rows. No payments. No receipts.

## Final Scope Decision
Mission 25 = purchase request + approval design; execution deferred to Mission 26+; supplier and finance remain placeholders.
