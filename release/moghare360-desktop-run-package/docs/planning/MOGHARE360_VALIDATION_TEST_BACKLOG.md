# MOGHARE360 — Validation Test Backlog

**Status:** Test planning only — **No runtime test implementation in PHASE 08**

---

## Purpose

Define future validation test groups that verify **UI → Validation Engine → Workflow Engine → Database → Audit Log** when engines are implemented.

---

## Media Rules (All Media Tests)

- **Camera direct only**
- **No upload bypass**

---

## Test Groups

### Required Field Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-RF-001 | Customer | Empty name submit | E-01 REQUIRED_FIELD_MISSING |
| VT-RF-002 | Vehicle | Missing plate and VIN | E-01 |
| VT-RF-003 | JobCard | Missing customer ref | E-01 |

### Format Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-FM-001 | Customer | Invalid national ID | E-02 INVALID_FORMAT |
| VT-FM-002 | Customer | Mobile not `09XXXXXXXXX` | E-02 |
| VT-FM-003 | Vehicle | Invalid VIN | E-02 |
| VT-FM-004 | Vehicle | Invalid Iran plate | E-02 |

### Duplicate Risk Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-DP-001 | Customer | Duplicate national ID | E-03 DUPLICATE_RISK |
| VT-DP-002 | Vehicle | Duplicate plate | E-03 |

### State Transition Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-ST-001 | JobCard | DRAFT → SUBMITTED valid | Pass |
| VT-ST-002 | JobCard | DRAFT → APPLIED | E-04 INVALID_STATE_TRANSITION |

### Permission Denied Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-PD-001 | JobCard | Submit without `jobcard.submit` | E-05 PERMISSION_DENIED |

### Cross-Domain Write Blocked Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-XD-001 | Inventory | JobCard module writes `erp_stock_balances` | E-06 CROSS_DOMAIN_WRITE_BLOCKED |

### Media Rule Violation Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-MD-001 | Vehicle | File upload ingest | E-07 MEDIA_RULE_VIOLATION |
| VT-MD-002 | Vehicle | Non-camera source metadata | E-07 |

### Production Boundary Blocked Tests

| Test ID | Domain | Case | Expected |
|---------|--------|------|----------|
| VT-PB-001 | Finance | Official ledger post | E-10 PRODUCTION_BOUNDARY_BLOCKED |
| VT-PB-002 | Finance | Payment gateway charge | E-10 |
| VT-PB-003 | Portal | Public customer write | E-10 |

---

## Future Test Location

- `tools/test-validation-*.php` when Phase 09+ authorizes
- CLI-first; no production data mutation in test harness

---

## Signoff Gate

Validation test backlog execution required before controlled write candidate implementation.

---

**END OF VALIDATION TEST BACKLOG**
