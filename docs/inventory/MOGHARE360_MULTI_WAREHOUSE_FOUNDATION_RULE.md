# MOGHARE360 — Multi-warehouse Foundation Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Multi-warehouse Requirement

**Inventory must support multi-warehouse planning.** All stock quantities are warehouse-scoped — no global anonymous stock pool.

---

## Warehouse Types

### Main Warehouse

| Property | Rule |
|----------|------|
| Role | Primary stock location — bulk parts, fast movers |
| Default reservation source | First pick for JobCard reservations |
| Location code | Controlled dropdown e.g. `WH-MAIN` |

### Secondary Warehouse

| Property | Rule |
|----------|------|
| Role | Overflow, slow movers, specialty parts |
| Transfers | From main via audited transfer |
| Location code | e.g. `WH-SEC` |

### Technician Temporary Holding

| Property | Rule |
|----------|------|
| **Technician temporary holding concept** | Parts issued to tech bay — not yet consumed on operation |
| Stock ownership | Still workshop inventory — warehouse type `TECH-HOLD` |
| Max duration | Policy — return to main or consume within SLA |
| Audit | Transfer in/out of tech hold |

### Returned / Defective Holding Area

| Property | Rule |
|----------|------|
| **Returned/defective holding area** | Quarantine — not available for reservation |
| Warehouse type | `DEFECT-HOLD` or `RETURN-HOLD` |
| Stock effect | Removed from available qty |

---

## Warehouse Transfer Concept

| Rule | Detail |
|------|--------|
| Transfer request | Source warehouse → destination warehouse |
| Validation | Sufficient qty at source; no negative without approval |
| Workflow | `inventory.move` permission |
| Audit | `warehouse_transfer` event with part, qty, actor |

---

## Stock Ownership Rules

| Rule | Requirement |
|------|-------------|
| Workshop owns all stock | No consignment without explicit flag (future) |
| Available qty | On-hand minus reserved minus defective hold |
| **No negative stock without approval** | Block issue; manager override workflow |
| Part master | Single catalog — warehouse holds quantities |

---

## Part Category Integration

Part category dropdown per Phase 17 — OEM, aftermarket, consumable, fluid.

---

## Validation

| Check | Error |
|-------|-------|
| Issue from empty warehouse | E-07 / stock block |
| Negative available | E-03 unless approved override |
| Cross-warehouse double count | FORBIDDEN — single movement row |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `stock_adjustment` | Qty change with reason |
| `warehouse_transfer` | Source, dest, part, qty |
| `negative_stock_override` | Approver, reason |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF MULTI-WAREHOUSE FOUNDATION RULE**
