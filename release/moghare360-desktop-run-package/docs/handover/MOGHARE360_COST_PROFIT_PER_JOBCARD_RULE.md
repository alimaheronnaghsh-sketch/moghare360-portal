# MOGHARE360 — Cost & Profit per JobCard Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Cost / Profit Preview Purpose

Provide **manager-only** JobCard-level economics — revenue preview minus cost preview — for workshop decisions without official accounting or GL posting.

**No official accounting activation** — LOCKED.

---

## Service Revenue Preview

| Source | Calculation |
|--------|-------------|
| **Service revenue preview** | Sum priced labor lines from operations |
| Discounts | Subtract approved discounts |
| Contract ceiling | Compare cumulative preview — Phase 19 |

---

## Parts Cost Preview

| Source | Calculation |
|--------|-------------|
| **Parts cost preview** | Sum consumption qty × unit cost (purchase/margin base) |
| Parts sell in revenue | Separate line — margin = sell − cost |

Phase 21 consumption + Phase 23 pricing.

---

## Labor / Operation Cost Planning

| Element | Rule |
|---------|------|
| **Labor/operation cost planning** | Internal cost rate × hours (planning) |
| Technician time | Optional time capture — future |
| Overhead allocation | Owner policy — not auto GL |

---

## Discount Impact

| Rule | Detail |
|------|--------|
| Line discount | Reduces revenue preview |
| JobCard discount | Reduces gross profit preview |
| Audit | All discounts logged |

---

## Gross Profit Preview

```
Gross profit preview = Revenue preview − Parts cost preview − Labor cost preview (planning)
```

| Display | Label |
|---------|-------|
| Margin % | Preview only |
| Negative margin | Manager alert — no auto block unless policy |

---

## Manager-Only Visibility

| Role | Access |
|------|--------|
| **Manager-only visibility** | Owner, admin, finance-preview role |
| Technician | FORBIDDEN — Phase 20 |
| Customer / portal | FORBIDDEN — Phase 22 |
| Export | Local report — not domain |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `jobcard_cost_profit_preview_calculated` | jobcard_id |
| `jobcard_margin_viewed` | actor |
| `cost_preview_exported` | manager |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF COST & PROFIT PER JOBCARD RULE**
