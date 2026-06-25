# Supplier Boundary

## Purpose
This document locks the supplier boundary for purchase approval design.

## Mission 25 Rule
Supplier is **placeholder only** in Mission 25 and initial Mission 26 scope.

## Supplier Field Design (Locked)

### supplier_id
- Nullable on `erp_purchase_requests`
- No supplier master table created in Mission 25
- No supplier master required in Mission 26 initial scope

## Forbidden in Mission 25 and Mission 26 Initial Scope
- Supplier contract implementation
- Vendor rating / scorecard
- Real purchase order (PO) document generation
- PO transmission to supplier (email, EDI, API)
- Supplier payment
- Supplier portal integration
- Supplier onboarding workflow

## Future Supplier Mission (Indicative)
A future mission may define:
- `erp_suppliers` master table
- Supplier selection UI on purchase request
- Contract terms linkage
- Preferred vendor rules

Until then:
- `supplier_id` remains NULL on create
- `requested_part_name` and optional `part_id` carry part identity
- `request_reason` carries operational context

## Relationship to part_id
- If `part_id` set, links to `erp_parts` catalog
- Supplier selection does not affect catalog part in M25/M26

## Legacy Context (Read-Only)
Legacy inventory / procurement systems in project are not modified.
No migration from legacy supplier data in Mission 25.

## Mission 25 Boundary
Supplier boundary documented only. No supplier tables or contracts.

## Final Supplier Decision
supplier_id nullable placeholder; real supplier design and PO execution deferred to future missions.
