# MOGHARE360 — External Purchase Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## External Purchase Request Definition

**External purchase** — procurement for **outside-city, import, or long-lead** parts. Separate workflow path and tracking fields from internal purchase.

---

## Use Cases

| Scenario | Detail |
|----------|--------|
| Import OEM parts | Long shipping |
| Out-of-city supplier | Not same-day |
| Special order | Customer vehicle rare model |

---

## Required Fields (Beyond Internal)

| Field | Control |
|-------|---------|
| Purchase type | `EXTERNAL` (enum) |
| **Supplier/source tracking** | Supplier dropdown + external source note |
| **Expected arrival date** | Required date field |
| **Shipping/logistics note** | Free text — carrier, tracking |
| **Customs/import note** | Free text if applicable |
| **Customer delivery impact** | Dropdown: none / delayed / customer notified |
| JobCard ref | When job-blocked |

---

## Supplier / Source Tracking

| Data | Rule |
|------|------|
| Primary supplier | FK |
| Alternate source | Optional note |
| Lead time days | Numeric planning field |
| External PO reference | Supplier order number |

---

## Customer Delivery Impact

| Impact | Action |
|--------|--------|
| Job blocked waiting part | Flag JobCard — day-end report |
| Customer notified | CRM follow-up link (Phase 22) |
| Delivery date revision | Contract storage/delivery terms review |

---

## Workflow and Approval Requirement

| Rule | Detail |
|------|--------|
| Same state machine | DRAFT → … → CLOSED |
| **Higher approval tier** | Owner approval for external above threshold |
| Finance preview | Estimated cost on PR — not official PO accounting |
| Ceiling | JobCard contract ceiling check |

---

## Receipt Confirmation

| Step | Rule |
|------|--------|
| Partial shipments | Multiple receipt events |
| Import clearance | Note on receipt |
| Stock in | Destination warehouse |
| JobCard notify | Auto-flag when part arrives |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `external_purchase_created` | expected_arrival |
| `external_purchase_delayed` | Revised date |
| `external_purchase_received` | qty, warehouse |
| `customer_delivery_impact_logged` | jobcard_id |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF EXTERNAL PURCHASE RULE**
