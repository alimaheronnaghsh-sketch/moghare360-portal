# MOGHARE360 — Database Row Count Profile

**Database:** MOGHARE360_ERP  
**Source:** PHASE 03 SSMS read-only row count discovery  
**Status:** Documentation only

---

## Profile Summary

Current data level across **MOGHARE360_ERP** is **seed/demo/prototype level**, not full production operation volume. The schema supports full ERP modules, but most operational tables are empty or minimally populated.

---

## Highest Populated Tables

| Rank | Table | Rows | Domain |
|------|-------|------|--------|
| 1 | `core_role_permissions` | **162** | Identity / Access |
| 2 | `core_permissions` | **43** | Identity / Access |
| 3 | `core_positions` | **43** | Identity / Access |
| 4 | `core_audit_logs` | **18** | Identity / Access |
| 5 | `core_roles` | **18** | Identity / Access |
| 6 | `core_access_approval_rules` | **16** | Identity / Access |
| 7 | `core_departments` | **14** | Identity / Access |
| 8 | `erp_commercial_readiness_checks` | **10** | Commercial Preview |
| 9 | `erp_soft_run_audit_checks` | **10** | Soft Run |

**Observation:** The most populated tables are access-control seeds, organizational reference data, and soft-run/commercial readiness checks — not live customer or workshop transaction volume.

---

## Population Tiers

### Tier A — Reference / Seed (10+ rows)

Access control matrix, departments, roles, permissions, commercial/soft-run readiness checks. These appear intentionally seeded during phased bootstrap.

### Tier B — Minimal Operational (1 row)

Many ERP operational tables contain a single demo or test record (e.g. sample customer, sample job card, sample payment preview). Indicates structure validation, not sustained operations.

### Tier C — Built but Empty (0 rows)

Tables with **0 rows** are classified as:

1. **Built but not operationally populated** — schema exists from phased SQL; application path not yet exercised or data cleared
2. **Future soft-run / business operation candidates** — ready for controlled pilot data entry in soft-run phases

---

## Tables with 0 Rows — Classification

| Classification | Description | Examples (typical) |
|----------------|-------------|-------------------|
| Built but not operationally populated | Table created by phase SQL; no business writes yet | History tables, some CRM follow-up tables, some HR disciplinary records |
| Future soft-run / business operation candidates | Intended for next pilot or operational phases | Additional job card operations, inventory movements, CRM schedules, upsell opportunities |

> Exact per-table 0-row list should be exported from SSMS in a future detailed inventory if needed. Phase 03 confirms the **pattern**: majority of operational ERP tables are at 0–1 rows.

---

## Domain Row Count Pattern

| Domain | Typical row level | Notes |
|--------|-------------------|-------|
| Identity / Access | High (seeded) | 162 role-permission mappings |
| Customer / Vehicle | Low (0–1) | Structure ready, minimal data |
| JobCard / Operations | Low (0–1) | Pilot-level |
| Inventory / Purchase | Low (0–1) | Pilot-level |
| Finance Preview / Payment | Low (0–1) | Preview tables exist; not official accounting |
| CRM | Low (0–1) | Pilot-level |
| HR | Low (0–1) | Pilot-level |
| Rule Engine | Low (0–1) | Definitions may exist |
| Soft Run / Commercial | Low–medium | Readiness checks at 10 rows |

---

## Implications

1. **Do not assume empty tables are unused** — they may be required by future phased operations
2. **Do not drop empty tables** without gap analysis — structure is intentional
3. **Row count growth** should be tracked per soft-run pilot before production volume assumptions
4. Current state supports **demo, training, and controlled pilot** — not high-volume production

---

## Note

**Current data level is seed/demo/prototype level, not full production operation volume.**

---

## Product Boundary

- Documentation only
- No database modification
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF ROW COUNT PROFILE**
