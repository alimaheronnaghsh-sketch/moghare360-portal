# MOGHARE360 — Payment Tracking Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Payment Tracking Purpose

Record **planned/manual payment status** against JobCard invoice preview — cash, transfer, partial payments — for workshop handover checklist without payment gateway or bank integration.

**Payment tracking remains preview unless explicitly approved later** — LOCKED.

---

## Payment Tracking Preview / Readiness Only

| Capability | Phase 23 |
|------------|----------|
| Payment note on JobCard | Preview |
| Paid / partial / unpaid flag | Preview |
| Customer balance preview | Sum of open drafts |
| Official AR settlement | **NOT active** |

Aligned with Phase 20 delivery payment preview.

---

## Allowed Payment Record Planning

| Field | Control |
|-------|---------|
| Payment date | Date picker |
| Amount | Positive decimal IRR |
| Method | Dropdown: cash, card-present (manual), transfer, other |
| Reference note | Transfer ref — free text |
| Recorded by | Staff user |
| JobCard / draft invoice ref | Required |

**No online payment processing** — staff records payment after fact.

---

## Customer Balance Preview

| Calculation | Rule |
|-------------|------|
| Open balance | Sum invoice draft totals − payment preview records |
| Display label | «پیش‌نمایش مانده» |
| Credit limit | Customer credit preview — separate rule |

---

## Forbidden Integrations

| Integration | Status |
|-------------|--------|
| **No payment gateway activation** | LOCKED |
| **No online payment processing** | LOCKED |
| **No bank integration** | LOCKED |
| **No tax/billing integration** | LOCKED |
| Card capture API | E-10 |

---

## Validation

| Check | Rule |
|-------|------|
| Payment > draft total | E-02 warn/block |
| Payment without CLOSED JobCard | Policy gate |
| Gateway call attempt | E-10 |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `payment_preview_recorded` | jobcard_id, amount |
| `payment_preview_adjusted` | correction workflow |
| `payment_gateway_blocked` | E-10 |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF PAYMENT TRACKING RULE**
