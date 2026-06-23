# MOGHARE360 — Read-Only Page Backlog

**Status:** Backlog candidates only — **Do not create PHP files in PHASE 08**

---

## Policy

These are **backlog candidates only**. Pages are proposed for a future authorized implementation phase (e.g. Phase 09). PHASE 08 does not create files in `public_html/`.

---

## Proposed Read-Only Pages

### erp-readonly-architecture-overview.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Admin structure overview: 12 domains, canonical flow, product boundaries |
| **Source document** | `MOGHARE360_CANONICAL_DOMAIN_MODEL.md`, master execution prompt |
| **Data source type** | Static documentation render + optional DB name check |
| **Must be read-only** | Yes — no mutations |
| **Required guard** | Session + platform owner or `report.read` permission |
| **Forbidden actions** | Write routes; portal activation; config display |
| **Test requirement** | Page loads; no POST handlers; auth denied without session |

---

### erp-readonly-domain-map.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Domain ownership viewer — table→domain, ambiguous flags |
| **Source document** | `MOGHARE360_DOMAIN_OWNERSHIP_MAP.md` |
| **Data source type** | Static map + optional SELECT count per domain |
| **Must be read-only** | Yes |
| **Required guard** | Session + admin/read permission |
| **Forbidden actions** | Table DROP hints; ownership edit |
| **Test requirement** | 96 tables represented; heuristic warnings visible |

---

### erp-readonly-validation-matrix.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Validation rule viewer per domain |
| **Source document** | `MOGHARE360_VALIDATION_RULE_MATRIX.md` |
| **Data source type** | Static rule matrix |
| **Must be read-only** | Yes |
| **Required guard** | Session + permission |
| **Forbidden actions** | Rule edit UI; validation bypass |
| **Test requirement** | National ID, mobile, VIN, plate rules visible |

---

### erp-readonly-workflow-contract.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Workflow transition viewer — allowed and forbidden |
| **Source document** | `MOGHARE360_WORKFLOW_STATE_TRANSITION_CONTRACT.md` |
| **Data source type** | Static transition matrix |
| **Must be read-only** | Yes |
| **Required guard** | Session + permission |
| **Forbidden actions** | State transition buttons that write |
| **Test requirement** | DRAFT→SUBMITTED, APPROVED→APPLIED, APPLIED→CLOSED shown; DRAFT→APPLIED forbidden |

---

### erp-readonly-permission-gates.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Permission gate viewer (conceptual keys) |
| **Source document** | `MOGHARE360_PERMISSION_WORKFLOW_GATE_MATRIX.md` |
| **Data source type** | Static gate matrix + optional read from `core_permissions` |
| **Must be read-only** | Yes |
| **Required guard** | Session + permission |
| **Forbidden actions** | Permission model modification; new role creation |
| **Test requirement** | Submit/approve/apply/close gates documented |

---

### erp-readonly-audit-contract.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Audit event contract viewer |
| **Source document** | `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md` |
| **Data source type** | Static field list + optional sample read from `core_audit_logs` |
| **Must be read-only** | Yes |
| **Required guard** | Session + audit read permission |
| **Forbidden actions** | Audit row delete; skip-audit toggle |
| **Test requirement** | All audit fields listed |

---

### erp-readonly-module-readiness.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Module readiness levels per domain |
| **Source document** | `MOGHARE360_MODULE_CONTRACT_MATRIX.md`, domain ownership summary |
| **Data source type** | Static readiness + optional row counts |
| **Must be read-only** | Yes |
| **Required guard** | Session + permission |
| **Forbidden actions** | "Production ready" claim; SaaS activation |
| **Test requirement** | NOT_PRODUCTION_READY messaging for business modules |

---

### erp-readonly-database-risk-board.php

| Attribute | Value |
|-----------|-------|
| **Purpose** | Database readiness: PK/FK, empty tables, ID types, overlap |
| **Source document** | Structure health, gap analysis, SQL change candidates |
| **Data source type** | Static metrics + optional aggregated SELECT |
| **Must be read-only** | Yes |
| **Required guard** | Session + admin permission |
| **Forbidden actions** | SQL execution UI; schema change |
| **Test requirement** | 46 empty tables, 10 dual IDs, 33 cross-domain FKs referenced |

---

## Common Page Requirements (All)

- RTL Persian UI (Phase 12.5 brand CSS when implemented)
- **UI → Validation Engine → Workflow Engine → Database → Audit Log** displayed as architecture banner
- No INSERT/UPDATE/DELETE
- No workflow state change
- No customer portal, SaaS, accounting, payment gateway activation

---

**END OF READ-ONLY PAGE BACKLOG**
