# MOGHARE360 — Inventory-to-Finance Binding Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Inventory-to-finance binding remains planning only in PHASE 21.**

No official GL posting, customer invoice issuance, tax calculation, or payment gateway integration.

---

## Parts Cost → JobCard Cost Preview

| Rule | Detail |
|------|--------|
| **Parts cost affects JobCard cost preview** | Sum consumed parts (unit cost × qty) |
| Labor preview | Operation module — separate line |
| Running total | Finance preview header on JobCard |
| Contract ceiling | Phase 19 — block/warn when preview > ceiling |

Visible on JobCard — admin/delivery roles; masked for technician per Phase 20.

---

## Purchase Cost → Supplier Credit Preview

| Rule | Detail |
|------|--------|
| **Purchase cost affects supplier credit preview** | PR line amounts accrue preview payable |
| On receipt | Preview obligation firming |
| No AP voucher | Official accounting NOT active |

Per `MOGHARE360_SUPPLIER_CREDIT_PREVIEW_RULE.md`.

---

## Stock Value Preview

| Rule | Detail |
|------|--------|
| **Stock value remains preview until official accounting approval** | On-hand × average cost (planning method) |
| Valuation method | Documented at Phase 23 — not locked in Phase 21 |
| No balance sheet | Preview report only |

---

## Customer Invoice Preview

| Rule | Detail |
|------|--------|
| **Customer invoice remains draft/preview until PHASE 23** | LOCKED |
| Delivery handover | Payment tracking preview note only (Phase 20) |
| Tax lines | NOT calculated |
| Official invoice number | NOT issued |

---

## Forbidden in Phase 21

| Action | Status |
|--------|--------|
| **No tax/billing/payment gateway activation** | LOCKED |
| GL journal from inventory | E-10 |
| Payment capture | E-10 |
| Official accounting reports | E-10 |

---

## Data Flow (Planning)

```
Part consumption ──► JobCard cost preview line
Purchase receipt   ──► Supplier credit preview
Stock on-hand      ──► Stock value preview report
                          │
                          ▼
              (PHASE 23: official accounting gate)
```

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `jobcard_cost_preview_updated` | parts_total delta |
| `supplier_preview_updated` | purchase receipt |
| `finance_preview_viewed` | actor (optional) |
| `official_accounting_blocked` | boundary attempt |

---

## Workflow Integration

- Consumption and purchase still require full Validation → Workflow → DB → Audit
- Finance preview is **read-side aggregation** — not bypass path

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF INVENTORY-TO-FINANCE BINDING RULE**
