# JobCard Status Transitions

## Purpose
This document locks the proposed JobCard status transition model for Soft Run.

## Critical Rule (Locked)

**Mission 29 designs the status model only.**

**No live JobCard status is changed in Mission 29.**

**No status transition code is implemented in Mission 29.**

Mission 30 may implement controlled transitions only if explicitly chartered; Mission 29 remains design-only.

## Proposed JobCard Status Model (Locked)

| Status | Meaning |
|--------|---------|
| RECEIVED | Vehicle received at shop |
| IN_SERVICE | Active service work |
| WAITING_PARTS | Blocked on parts (links to M26 purchase path) |
| SERVICE_DONE | Service work completed — awaiting QC |
| QC_PENDING | QC inspection scheduled or in progress |
| QC_PASSED | QC approved |
| QC_FAILED | QC failed — rework |
| READY_FOR_DELIVERY | QC passed + delivery gate ready (design) |
| DELIVERED | Vehicle delivered to customer |
| CANCELLED | JobCard cancelled |

## Indicative Transition Map (Design)

```
RECEIVED → IN_SERVICE
IN_SERVICE → WAITING_PARTS (parts needed)
WAITING_PARTS → IN_SERVICE (parts available)
IN_SERVICE → SERVICE_DONE
SERVICE_DONE → QC_PENDING
QC_PENDING → QC_PASSED | QC_FAILED
QC_FAILED → IN_SERVICE | QC_PENDING (rework / recheck)
QC_PASSED → READY_FOR_DELIVERY
READY_FOR_DELIVERY → DELIVERED
* → CANCELLED (controlled cancel policy — future)
```

## Relationship to Other Entities

| Entity | Status Interaction (Design) |
|--------|----------------------------|
| erp_service_operations | service_status independent; JobCard status is aggregate signal |
| erp_qc_checks | qc_status drives QC_PASSED / QC_FAILED / QC_PENDING |
| erp_delivery_controls | delivery_status READY/RELEASED correlates with READY_FOR_DELIVERY / DELIVERED |
| erp_payments | Does not auto-change JobCard status (M29_06) |

## Mission 29 vs Mission 30

| Aspect | Mission 29 | Mission 30 |
|--------|------------|------------|
| Status enum defined | Yes | Uses design |
| Status column altered on erp_jobcards | No | Only if chartered |
| Status transition PHP | No | Prototype only if chartered |
| Live data migration | No | No |

## Mission 29 Boundary
Status model documented only. Zero status writes.

## Final Status Decision
Ten-state JobCard lifecycle designed for Soft Run; no implementation or live changes in Mission 29.
