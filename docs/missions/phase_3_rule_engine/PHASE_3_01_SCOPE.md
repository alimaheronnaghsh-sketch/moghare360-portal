# PHASE 3 — Scope

## Purpose

Build the ERP decision brain so no extra operation, part request, route change, or out-of-contract work proceeds without Rule Check.

## Core Rules

1. Out-of-contract operations require approval
2. Open Authorization continues within threshold
3. Limited Authorization blocks/requests approval above threshold
4. Available parts → warehouse path (reserve placeholder)
5. Unavailable parts → purchase request path
6. No operation proceeds without rule evaluation

## In Scope

- 5 `erp_rule_*` tables + seeded definitions
- Contract authorization checker
- Service approval trigger
- Inventory decision trigger (read-only stock estimate)
- Decision board, approval board, internal test console
- Audit history

## Out of Scope

- Real stock deduction (Phase 4)
- Purchase order accounting
- Customer portal / OTP / SMS
- Auth/permission model changes
- Destructive SQL
