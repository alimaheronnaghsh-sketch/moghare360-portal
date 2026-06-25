# MOGHARE360 — Read-Only Local Route Plan

**Status:** Routes planned only — **files must not be created in PHASE 09**

---

## Local Environment

| Property | Value |
|----------|-------|
| Local base URL | **http://localhost:8080/moghare360/** |
| Deploy path | `C:\xampp\htdocs\moghare360` |
| Database | MOGHARE360_ERP on `.\SQLEXPRESS` |

---

## Planned Routes

| # | Route | Page file | Status |
|---|-------|-----------|--------|
| 1 | http://localhost:8080/moghare360/erp-readonly-architecture-overview.php | `erp-readonly-architecture-overview.php` | Planned |
| 2 | http://localhost:8080/moghare360/erp-readonly-domain-map.php | `erp-readonly-domain-map.php` | Planned |
| 3 | http://localhost:8080/moghare360/erp-readonly-validation-matrix.php | `erp-readonly-validation-matrix.php` | Planned |
| 4 | http://localhost:8080/moghare360/erp-readonly-workflow-contract.php | `erp-readonly-workflow-contract.php` | Planned |
| 5 | http://localhost:8080/moghare360/erp-readonly-permission-gates.php | `erp-readonly-permission-gates.php` | Planned |
| 6 | http://localhost:8080/moghare360/erp-readonly-audit-contract.php | `erp-readonly-audit-contract.php` | Planned |
| 7 | http://localhost:8080/moghare360/erp-readonly-module-readiness.php | `erp-readonly-module-readiness.php` | Planned |
| 8 | http://localhost:8080/moghare360/erp-readonly-database-risk-board.php | `erp-readonly-database-risk-board.php` | Planned |

---

## Navigation Hub (Phase 10 — Planned)

Future link from existing safe integration points:

- `erp-business-command-center.php` (small link block)
- `erp-product-status.php` (read-only visibility section)

No changes to those files in Phase 09.

---

## Route Rules

| Rule | Requirement |
|------|-------------|
| **Routes are planned only** | No files in `public_html/` yet |
| **Files must not be created in PHASE 09** | Locked |
| **Public domain deployment not included** | Local laptop only |
| moghareh360.ir mirror | Not in scope — display-only mirror separate |

---

## Product Boundary

- No public customer portal routes
- No production SaaS URLs

---

**END OF READ-ONLY LOCAL ROUTE PLAN**
