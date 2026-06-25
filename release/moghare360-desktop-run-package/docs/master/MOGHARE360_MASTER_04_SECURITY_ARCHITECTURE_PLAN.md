# MOGHARE360 — Master 04 Security Architecture Plan

**Status:** Planning only — Documentation only  
**SQL:** Not required

---

## Purpose

Define the security architecture layers for MOGHARE360 ERP. This plan does not modify `staff-auth.php`, `access-control.php`, `staff-login.php`, or permission models.

---

## Security Layers (Ordered)

### 1. Authentication Layer

- Staff login via existing production login path (forbidden to rewrite)
- Session establishment after credential validation
- No anonymous write access

### 2. Session Validation

- Every request validates active session
- Session timeout and regeneration policy per existing auth stack
- Invalid/expired session → reject before business logic

### 3. Role Check

- Role resolved from session (e.g. platform owner, admin, mechanic, QC)
- Role gates module entry and API group access

### 4. Permission Check

- Fine-grained permission keys per action (e.g. `jobcard.approve`, `inventory.reserve`)
- Permission matrix enforced on submit routes and planned APIs

### 5. Workflow State Check

- Mutations allowed only when entity is in valid source state
- Transition requires target state permission

### 6. Audit Logging

- All security-relevant actions logged
- Failed auth/permission attempts logged where feasible
- Aligns with Phase 13 security hardening patterns

---

## Data Flow Security

```
Request → Auth → Session → Role → Permission → Workflow → Validation → DB → Audit
```

Equivalent to:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## CSRF Boundary

- All POST write routes require CSRF token validation
- Token bound to session
- No state-changing GET endpoints
- Phase 13 CSRF audit patterns apply to new routes

---

## private Config Boundary

| Asset | Rule |
|-------|------|
| `private/erp-config.php` | Local only; never in packages |
| DB credentials | Not in docs, not in public_html |
| `config.php` | Forbidden to modify without explicit mission |

---

## Domain Mirror Security

- **moghareh360.ir** — mirror only
- **No business data** on mirror domain
- **No database** on mirror host
- **No primary processing** on mirror
- Display-only / future sync concept — not implemented in this pack

---

## Storage and Exposure Rules

| Rule | Status |
|------|--------|
| No cloud storage | Required |
| No business data outside local server | Required |
| No public exposure of private config | Required |
| No customer portal activation | Required |
| No production SaaS activation | Required |

---

## Forbidden Public Exposure

- Raw SQL connection strings
- Internal audit dumps without auth
- Upload directories in release ZIPs
- `.bak` files in packages
- Real customer PII in demo artifacts

---

## Product Boundary

- Documentation only
- No auth architecture change
- No permission model change
- No private config change

---

**END OF SECURITY ARCHITECTURE PLAN**
