# MOGHARE360 — Module Implementation Sequence

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — Documentation only

---

## Sequence Principle

- **Access/security and audit visibility come first**
- **Business modules come after guards are visible**
- **Finance remains preview-only**
- **Payment gateway remains inactive**
- **Official accounting remains inactive**
- **Public customer portal remains inactive**

---

## Ordered Implementation Sequence

| Order | Domain | Phase focus | Readiness | Notes |
|-------|--------|-------------|-----------|-------|
| 1 | **Identity / Access / Security** | Read-only visibility | FOUNDATION_REFERENCE | RBAC seed (311 rows); auth files not modified |
| 2 | **Audit / History** | Read-only visibility | SEED_OR_PROTOTYPE | Audit contract viewer; append-only education |
| 3 | **Customer** | Read-only visibility | SEED_OR_PROTOTYPE | National ID/mobile rules visible; no intake write |
| 4 | **Vehicle** | Read-only visibility | SEED_OR_PROTOTYPE | Plate/VIN rules; camera policy banner |
| 5 | **JobCard** | Read-only visibility | SEED_OR_PROTOTYPE | Workflow states display |
| 6 | **Operation / Service / QC / Delivery** | Read-only visibility | STRUCTURAL_EMPTY | QC/delivery tables mostly empty |
| 7 | **Inventory / Parts / Purchase** | Read-only visibility | SEED_OR_PROTOTYPE | Stock overlap warning |
| 8 | **Finance Preview / Payment** | Read-only visibility | PREVIEW_ONLY | No official accounting; no payment gateway |
| 9 | **CRM / Customer Experience** | Read-only visibility | STRUCTURAL_EMPTY | 0 rows |
| 10 | **HR** | Read-only visibility | STRUCTURAL_EMPTY | 0 rows; employment vs service contract note |
| 11 | **Reporting / Soft Run / Commercial** | Read-only visibility | SOFT_RUN_READY | No production SaaS |
| 12 | **Rule / Workflow Decision** | Read-only visibility | SEED_OR_PROTOTYPE | Rule/decision tables |

---

## Post-Read-Only Sequence (Not Phase 08)

| Stage | Content | Gate |
|-------|---------|------|
| A | Validation test console (`tools/test-validation-*`) | Read-only signoff |
| B | Workflow simulation (dry-run, no DB) | Validation tests pass |
| C | Audit preview console | Simulation signoff |
| D | Controlled write candidates | Engines implemented |
| E | SQL packages (User SSMS) | ChatGPT approval |
| F | Full module services in `app/` | All above |

---

## Cross-Cutting Dependencies

```
Identity read-only ──► Permission gate viewer
        │
        ▼
Audit read-only ──► Audit contract viewer
        │
        ▼
Validation/Workflow viewers ──► Business module viewers
```

---

## Product Boundary

- Sequence planning only
- **Do not implement backlog items yet**

---

**END OF MODULE IMPLEMENTATION SEQUENCE**
