# Parts / Inventory Scope

## Purpose
This document locks the scope of Parts / Inventory foundation design for MOGHARE360 ERP.

## Mission Goal (Locked)
Design the foundation for parts master data, stock, stock movement, and future part usage on JobCard / Service Operation.

## In Scope (Design Only)
Mission 21 defines:
- Part Master data model plan (`erp_parts`)
- Stock Location data model plan (`erp_stock_locations`)
- Stock Movement data model plan (`erp_stock_movements`)
- Future JobCard / Service Operation part usage rules
- Purchase Request boundary (design only)
- Permission and audit rules for future writes
- SQL implementation plan (no execution)
- UI plan for Mission 22 (no file creation)
- Testing plan for Mission 22 (no runnable tests)
- Legacy inventory read-only review

## Core Scope Rules (Locked)

### Part Master
- Canonical ERP part catalog for Soft Run foundation
- Independent from legacy MySQL staging tables
- No migration executed in Mission 21

### Stock Location
- Warehouse / shelf / bin style locations for ERP stock
- Location master only in Mission 21 design

### Stock Movement
- Movement ledger design for future controlled writes
- Movement types locked in M21_05

### JobCard / Service Operation Linkage
- Future part usage must link to JobCard
- Future part usage should prefer Service Operation link
- No consumption implementation in Mission 21

## Out of Scope (Locked)
Mission 21 must not:
- Execute SQL
- Create PHP operational code
- Perform stock movement write
- Perform stock deduction
- Create purchase request
- Perform finance write
- Create invoice
- Process supplier payment
- Consume parts on JobCard
- Modify legacy inventory tables or pages

## Relationship to Existing ERP Foundation
| Foundation | Mission 21 Relationship |
|------------|-------------------------|
| JobCard | Future part usage parent |
| Service Operation | Preferred future part usage child |
| Service Operation WAITING_PARTS status | Status placeholder only; no stock integration in M21 |
| Customer / Vehicle | No direct part usage in M21 |

## Excluded From Parts / Inventory Foundation (Initial Missions)
- Supplier master
- Purchase order approval workflow
- Finance posting from stock
- Invoice generation from parts
- Barcode scanning UI
- Legacy StockCenter sync

## Mission 21 Boundary
Design only. No tables created. No stock changed.

## Final Scope Decision
Mission 21 = clean ERP parts/stock design independent of legacy, with explicit boundaries for usage, purchase, and finance in later missions.
