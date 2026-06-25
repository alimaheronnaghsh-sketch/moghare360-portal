# Soft Run Readiness Gap

## Purpose
This document locks the gap between current foundation state and Soft Run readiness.

## Current Status
**Until Mission 18 completion, the system is NOT Soft Run Ready.**

## What Is Ready (Locked)
| Area | Status |
|------|--------|
| Auth Context | Ready |
| Permission Guard | Ready |
| CSRF pattern | Ready |
| Customer foundation | Ready (prototype) |
| Vehicle foundation | Ready (prototype) |
| Customer / Vehicle relation | Ready (prototype) |
| JobCard foundation | Ready (prototype) |
| Controlled create pattern | Proven (M15, M17) |
| Audit / history on create | Proven (M15, M17) |
| Read-only list / detail | Proven (M15, M17) |

## What Is NOT Ready (Gap)
| Area | Gap |
|------|-----|
| Service Operation | Not implemented |
| Inventory | Not implemented |
| Part Usage | Not implemented |
| Purchase | Not implemented |
| Finance | Not implemented |
| QC | Not implemented |
| Delivery | Not implemented |
| Status transitions | Not implemented (beyond DRAFT/RECEIVED create) |
| Invoice / Payment | Not implemented |
| Soft Run operational workflow | Not complete |

## Soft Run Definition (Target — Not Yet Met)
Soft Run requires controlled internal shop operations end-to-end:
1. Customer / Vehicle identification — **Done (prototype)**
2. Reception / JobCard — **Done (prototype)**
3. Technical diagnosis — **Not done**
4. Service operation planning — **Not done**
5. Parts request — **Not done**
6. Approval — **Not done**
7. Work in progress — **Not done**
8. Quality control — **Not done**
9. Delivery preparation — **Not done**
10. Delivery / closure — **Not done**

## Mission 30 Gate (Locked)
Per risk register R-06:
**Soft Run is not allowed before Mission 30** unless explicitly overridden by main project controller.

## Mission 18 Boundary
Mission 18 documents the gap only.
No feature is built to close the gap under Mission 18.

## Final Gap Decision
Foundation is stable for executive review.
Soft Run readiness remains **open** with documented gaps in Service Operation, Inventory, Part Usage, Purchase, Finance, QC, and Delivery.
