# MOGHARE360 — Audit Preview Backlog

**Status:** Preview planning only — Documentation only

---

## Purpose

Define audit preview backlog for future read-only audit console and engine verification.

---

## Audit Preview Fields

| Field | Preview use |
|-------|-------------|
| **audit_id** | Display surrogate key |
| **actor_user_id** | Show actor (masked if policy requires) |
| **source_module** | Canonical domain name |
| **reference_entity** | Entity type string |
| **reference_id** | Entity PK |
| **old_state** | Prior workflow state |
| **new_state** | Target workflow state |
| **action** | Verb (submit, approve, etc.) |
| **action_result** | success / failed / blocked |
| **validation_result** | pass / fail + code |
| **permission_result** | pass / fail + key |
| **workflow_result** | pass / fail + reason |
| **created_at** | Timestamp |
| **notes** | Reject reason, diagnostic |

---

## Audit Preview Events

| Event ID | Event | Preview display |
|----------|-------|-----------------|
| AP-001 | **validation failed** | validation_result=fail, action_result=blocked |
| AP-002 | **permission denied** | permission_result=fail |
| AP-003 | **workflow transition allowed** | old_state→new_state, action_result=success |
| AP-004 | **workflow transition blocked** | workflow_result=fail |
| AP-005 | **cross-domain write blocked** | action=cross_domain_write_blocked |
| AP-006 | **media rule violation** | validation_result=fail, notes=media |
| AP-007 | **production boundary blocked** | action_result=blocked, notes=E-10 |

---

## Preview Console Backlog (Future)

| Backlog ID | Purpose | Dependency |
|------------|---------|------------|
| BL-AP-010 | Read-only audit log sample viewer | `core_audit_logs` SELECT |
| BL-AP-011 | Simulated event preview (dry-run) | Workflow simulation |
| BL-AP-012 | Audit contract field reference page | `erp-readonly-audit-contract.php` |

---

## Rules

- Audit / History domain records event evidence
- Domain owner responsible for business meaning of `reference_entity`
- Audit logging cannot be skipped for controlled workflow actions

---

## Product Boundary

- No audit table schema change
- No runtime in Phase 08

---

**END OF AUDIT PREVIEW BACKLOG**
