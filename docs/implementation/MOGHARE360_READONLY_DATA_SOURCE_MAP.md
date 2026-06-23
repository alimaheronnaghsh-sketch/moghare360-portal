# MOGHARE360 — Read-Only Data Source Map

**Database:** MOGHARE360_ERP  
**Status:** Planning only — Documentation only

---

## Source Type Legend

| Type | Description |
|------|-------------|
| **documentation-derived** | Content from `docs/` markdown only |
| **database-read-only-derived** | Future SELECT against MOGHARE360_ERP |
| **hybrid** | Documentation primary + optional SELECT aggregates |

---

## Global Data Rules

| Rule | Status |
|------|--------|
| **no INSERT** | Required |
| **no UPDATE** | Required |
| **no DELETE** | Required |
| **no workflow state mutation** | Required |
| **no permission model mutation** | Required |

**Database reads, if later required, must be SELECT-only.**  
**Any SQL must be approved in a later phase.**  
**Cursor must not execute SQL.**

---

## Per-Page Data Source Map

### erp-readonly-architecture-overview.php

| Attribute | Value |
|-----------|-------|
| Source type | **documentation-derived** |
| Document sources | `MOGHARE360_CANONICAL_DOMAIN_MODEL.md`, master prompt |
| Future SELECT | Optional: `SELECT DB_NAME()` verify MOGHARE360_ERP |
| Mutations | None |

### erp-readonly-domain-map.php

| Attribute | Value |
|-----------|-------|
| Source type | **hybrid** |
| Document sources | `MOGHARE360_DOMAIN_OWNERSHIP_MAP.md`, ambiguous review |
| Future SELECT | `SELECT COUNT(*) FROM {table}` per domain (aggregates only) |
| Mutations | None |

### erp-readonly-validation-matrix.php

| Attribute | Value |
|-----------|-------|
| Source type | **documentation-derived** |
| Document sources | `MOGHARE360_VALIDATION_RULE_MATRIX.md` |
| Future SELECT | None required |
| Mutations | None |

### erp-readonly-workflow-contract.php

| Attribute | Value |
|-----------|-------|
| Source type | **documentation-derived** |
| Document sources | `MOGHARE360_WORKFLOW_STATE_TRANSITION_CONTRACT.md` |
| Future SELECT | None required |
| Mutations | None |

### erp-readonly-permission-gates.php

| Attribute | Value |
|-----------|-------|
| Source type | **hybrid** |
| Document sources | `MOGHARE360_PERMISSION_WORKFLOW_GATE_MATRIX.md` |
| Future SELECT | `SELECT permission_key, description FROM core_permissions` (read-only list) |
| Mutations | None — **no permission model mutation** |

### erp-readonly-audit-contract.php

| Attribute | Value |
|-----------|-------|
| Source type | **hybrid** |
| Document sources | `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md` |
| Future SELECT | `SELECT TOP 20 * FROM core_audit_logs ORDER BY created_at DESC` (masked) |
| Mutations | None |

### erp-readonly-module-readiness.php

| Attribute | Value |
|-----------|-------|
| Source type | **hybrid** |
| Document sources | Module contract matrix, domain ownership summary |
| Future SELECT | Per-domain `COUNT(*)` from owned tables |
| Mutations | None |

### erp-readonly-database-risk-board.php

| Attribute | Value |
|-----------|-------|
| Source type | **hybrid** |
| Document sources | Structure health, gap analysis, SQL change candidates |
| Future SELECT | `sys.tables` row counts; FK counts — approved SQL phase only |
| Mutations | None |

---

## Helper Pattern (Phase 10 — Planned)

```
docs/implementation specs
  → public_html/includes/moghare360-readonly-*-helper.php (read-only)
  → page renders helper output
  → no write methods on helper
```

---

## SQL Approval Chain

1. Phase 10 defines SELECT statements in helper spec
2. ChatGPT approves each SELECT
3. User may validate in SSMS (optional)
4. Cursor implements PHP that runs SELECT only
5. **Do not create SQL yet** in Phase 09

---

**END OF READ-ONLY DATA SOURCE MAP**
