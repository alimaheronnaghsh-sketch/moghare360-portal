# APEX 11 — Organization Domain Logical Model

## Domain

**Organization Domain** — tenant and branch structure for multi-workshop operations.

**Logical only. Not physical schema. No SQL.**

---

## Logical Entities

| Entity | Role |
|--------|------|
| **Tenant** | Root organizational operator (workshop group / legal entity group) |
| **Branch** | Workshop location or logical operating unit under a tenant |
| **OrganizationUnit** | Optional sub-structure (department, bay group, service line) within a branch |

---

## Responsibilities

- Company / tenant structure and lifecycle
- Branch registration, hierarchy, and operating boundaries
- Organizational scope for all downstream domains (finance, inventory, jobs)
- Branch-level configuration references (not operational truth)

---

## Does Not Own

| Area | Owning Domain |
|------|---------------|
| Users and auth credentials | Identity & Access |
| Finance transactions and ledger | Finance |
| JobCard operations and technical cases | Job & Technical Intelligence |
| Employee HR records | HR |
| Customer relationships | CRM & Marketing |

---

## Logical Diagram

```mermaid
erDiagram
    Tenant ||--o{ Branch : contains
    Branch ||--o{ OrganizationUnit : may_have
    Tenant {
        string tenant_ref logical
        string legal_name logical
        string status logical
    }
    Branch {
        string branch_ref logical
        string branch_name logical
        string branch_type logical
    }
    OrganizationUnit {
        string unit_ref logical
        string unit_name logical
    }
```

*Attributes shown are logical descriptors, not database columns.*

---

## Key Relationships (Logical)

| From | To | Cardinality | Notes |
|------|-----|-------------|-------|
| Tenant | Branch | 1:N | Every branch belongs to one tenant |
| Branch | OrganizationUnit | 1:N | Optional internal structure |
| Branch | All operational domains | 1:N scope | Branch is scope key, not data owner |

---

## Service Boundary Notes

| Exposed (preview) | Description |
|-------------------|-------------|
| `resolveBranch(branch_ref)` | Validate branch exists and is active |
| `listBranches(tenant_ref)` | Query branches for tenant |
| `getBranchContext(branch_ref)` | Return organizational context for other services |

| Consumed | Via |
|----------|-----|
| User branch assignment | Identity & Access service |
| Branch-scoped operations | All domains via branch_ref contract |

**No direct cross-domain table access.** Other domains hold `branch_ref` as a stable reference ID only.

---

## Cursor Statement

**Cursor did not decide the next roadmap step.**
