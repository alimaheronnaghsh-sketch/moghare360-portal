# MOGHARE360 — Dropdown / Cascading Select Rules

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required  
**Phase:** PHASE 17 — planning only; **no runtime implementation in PHASE 17**

---

## Dropdown-First Principle

| Rule | Requirement |
|------|-------------|
| **Dropdown-first principle** | Sensitive categorical fields MUST use controlled selectors |
| **Free text only for notes/descriptions** | Remarks, technician notes, customer complaint text, internal comments |
| No free-text status | Status values must come from workflow enum tables or reference lists |
| No free-text category | Service type, part category, channel — dropdown only |

---

## Cascading Select Planning

```
Brand (dropdown)
  └── Model (dropdown — filtered by selected brand)

Province (dropdown or code)
  └── Plate letter + segments (structured)

Customer (search/select)
  └── Vehicle (filtered by customer bind)
```

Parent selection clears invalid child selection on change.

---

## Required Dropdown Fields

### Brand Dropdown

| Property | Rule |
|----------|------|
| Source | Reference table / seed list |
| Required on | Vehicle registration |
| Free text | FORBIDDEN for brand name |

### Model Depends on Brand

| Property | Rule |
|----------|------|
| Cascade | Model list filtered by `brand_id` |
| Disabled until | Brand selected |
| Free text | FORBIDDEN |

### Customer Channel Dropdown

| Property | Rule |
|----------|------|
| Examples | Walk-in, phone, referral, web (mirror placeholder), repeat |
| Domain | Customer intake |
| Free text | FORBIDDEN |

### Customer Class Dropdown

| Property | Rule |
|----------|------|
| Examples | Retail, fleet, VIP, warranty |
| Domain | Customer master |
| Free text | FORBIDDEN |

### Vehicle Class Dropdown

| Property | Rule |
|----------|------|
| Examples | Sedan, SUV, commercial, motorcycle |
| Domain | Vehicle registration |
| Free text | FORBIDDEN |

### Service Category Dropdown

| Property | Rule |
|----------|------|
| Domain | JobCard, operation |
| Tie to | Service catalog reference |
| Free text | FORBIDDEN |

### Part Category Dropdown

| Property | Rule |
|----------|------|
| Domain | Inventory, purchase request |
| Examples | OEM, aftermarket, consumable, fluid |
| Free text | FORBIDDEN |

### HR Contract Type Dropdown

| Property | Rule |
|----------|------|
| Domain | HR employee form |
| Examples | Full-time, part-time, contract, apprentice |
| Free text | FORBIDDEN |

---

## Status Values Must Be Controlled

| Context | Control |
|---------|---------|
| JobCard status | Workflow state machine — no manual text |
| Operation step status | Enum dropdown |
| QC result | Pass / Fail / Rework — dropdown |
| Delivery control | Checklist enum |
| Purchase request | PR workflow states |
| CRM follow-up | Follow-up type + status dropdown |

Aligned with `docs/architecture/MOGHARE360_WORKFLOW_STATE_TRANSITION_CONTRACT.md`.

---

## Validation Integration

| Step | Rule |
|------|------|
| Submit | Selected value must exist in reference set — E-02 if tampered POST value |
| Cascade integrity | Child value must belong to parent — E-02 |
| Server-side | Never trust client-only dropdown population |

---

## UI Planning Notes (Future)

- RTL layout for all selectors
- Searchable select for long lists (brand, customer)
- Read-only display of resolved labels on view mode
- No implementation in PHASE 17

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — rules locked; runtime cascade engine in future approved phase.

---

**END OF DROPDOWN / CASCADING SELECT RULES**
