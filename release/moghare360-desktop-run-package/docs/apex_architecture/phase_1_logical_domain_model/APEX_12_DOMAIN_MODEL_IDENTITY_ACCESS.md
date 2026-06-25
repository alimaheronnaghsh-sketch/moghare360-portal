# APEX 12 — Identity & Access Domain Logical Model

## Domain

**Identity & Access Domain** — authentication, authorization, and access governance.

**Logical only. Not physical schema. No SQL.**

---

## Logical Entities

| Entity | Role |
|--------|------|
| **User** | System account linked to a person identity |
| **Role** | Named bundle of permissions |
| **Permission** | Atomic capability (e.g. approve QC, post payment) |
| **AccessPolicy** | Rule set governing access scope (branch, module, time) |
| **AccessRequest** | Request/grant workflow for elevated or temporary access |

---

## Responsibilities

- Identity lifecycle (create, activate, deactivate, lock)
- Login and account access governance
- Role and permission assignment
- Access policy enforcement contracts for other domains
- Audit of access changes (logical event stream)

---

## Does Not Own

| Area | Owning Domain |
|------|---------------|
| Employee HR record (skills, attendance, KPI) | HR |
| Finance approval business records | Finance |
| JobCard business state and workflow | Job & Technical Intelligence |
| Branch organizational structure | Organization |

---

## Logical Diagram

```mermaid
erDiagram
    User ||--o{ UserRole : assigned
    Role ||--o{ UserRole : grants
    Role ||--o{ RolePermission : includes
    Permission ||--o{ RolePermission : defined_by
    User ||--o{ AccessRequest : may_submit
    AccessPolicy ||--o{ Role : may_scope

    User {
        string user_ref logical
        string display_name logical
        string account_status logical
    }
    Role {
        string role_ref logical
        string role_name logical
    }
    Permission {
        string permission_ref logical
        string permission_code logical
    }
```

---

## Key Relationships (Logical)

| From | To | Notes |
|------|-----|-------|
| User | Role | M:N via assignment; scoped by branch/tenant |
| Role | Permission | M:N; permissions are stable codes |
| User | Employee (HR) | Linked by `person_ref` — HR owns employee truth |
| AccessPolicy | Role/User | Constrains where and when access applies |

---

## Service Boundary Notes

| Exposed (preview) | Description |
|-------------------|-------------|
| `authenticate(credentials)` | Validate login |
| `authorize(user_ref, permission_code, context)` | Boolean authorization check |
| `assignRole(user_ref, role_ref, scope)` | Role assignment |
| `getUserContext(user_ref)` | Return user + effective permissions |

| Consumed | Via |
|----------|-----|
| Branch scope | Organization service |
| Person linkage | HR service (employee_ref) |

Credentials and permission stores are **owned here**. No other domain reads credential storage directly.

---

## Cursor Statement

**Cursor did not decide the next roadmap step.**
