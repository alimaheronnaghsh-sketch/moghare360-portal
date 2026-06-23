# MOGHARE360 — Read-Only Permission Guard Plan

**Status:** Planning only — **Permission model is not modified**

---

## Planned Guard Concept

All read-only architecture visibility pages require:

1. **Active staff session** (existing login path — not modified)
2. **Permission check** (read existing `core_permissions` — no new permissions in Phase 09)
3. **CSRF N/A** for GET-only pages (no mutating POST)

---

## Access Tiers

| Tier | Audience | Planned access |
|------|----------|----------------|
| **Read-only viewer** | Staff with `report.read` or domain read permission | All 8 pages except database risk board |
| **Admin/owner access** | Platform owner + `access.admin` / admin role | All 8 pages including database risk board |
| **Forbidden** | Anonymous, public, customer portal | Denied |

---

## Per-Page Guard (Conceptual)

| Page | Minimum permission concept |
|------|---------------------------|
| Architecture overview | `report.read` OR platform owner |
| Domain map | `report.read` OR platform owner |
| Validation matrix | `report.read` |
| Workflow contract | `report.read` |
| Permission gates | `access.admin` OR platform owner |
| Audit contract | `audit.read` OR platform owner |
| Module readiness | `report.read` |
| Database risk board | `access.admin` OR platform owner |

> **No new permission creation in PHASE 09.** Keys must exist in seed or map to existing keys at Phase 10 implementation time.

---

## Implementation Pattern (Future — After Approval)

Reuse existing patterns from Phase 13 security hardening:

- Session check via existing auth context
- Permission helper read (not `access-control.php` modification)
- Access denied handler redirect
- Placeholder owner ID for dev tests only if prior phase pattern applies

**Existing auth architecture is not modified.**

---

## Forbidden

| Item | Status |
|------|--------|
| **no public access** | Required |
| **no customer portal exposure** | Required |
| **no anonymous access** | Required |
| **no production SaaS behavior** | Required |
| Modify `staff-auth.php`, `access-control.php` | Forbidden |
| Create new roles/permissions in Phase 09 | Forbidden |

---

## Product Boundary

- **Do not modify permission model yet**
- Guard plan is conceptual until Phase 10

---

**END OF READ-ONLY PERMISSION GUARD PLAN**
