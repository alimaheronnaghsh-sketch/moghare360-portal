# MOGHARE360 — Master 01 Folder Structure Plan

**Status:** Planning only — Documentation only  
**SQL:** Not required for this document

---

## Purpose

Define the proposed project folder structure and boundaries for MOGHARE360 ERP. No files are created outside `docs/master/` during the MASTER EXECUTION PACK phase.

---

## Proposed Project Structure

```
moghare360-portal/
├── docs/
│   ├── master/              # Locked execution prompt + construction blueprint
│   ├── missions/            # Phase mission docs (phase_01 … phase_15)
│   ├── product/             # Product language, copyright, commercial docs
│   ├── deployment/          # Environment, backup, migration, rollback, monitoring
│   └── release/             # Package manifests and release notes
├── private/                 # FORBIDDEN in repo secrets — local only
│   ├── erp-config.php       # Runtime DB credentials (not in packages)
│   └── erp-config.example.php
├── public_html/             # Web runtime deploy target
│   ├── includes/            # PHP helpers (module-scoped)
│   ├── assets/              # CSS, brand, UI (no unlicensed fonts)
│   ├── sql/sqlserver/       # Future SQL — SSMS execution only when phased
│   └── *.php                # Pages and submit-* write routes
├── tools/                   # CLI test tools, packaging scripts
├── release/                 # Built ZIP outputs (excluded from local package copy)
└── (forbidden at root)      # config.php, staff-auth.php — see forbidden list
```

---

## public_html Boundaries

| Area | Role | Rules |
|------|------|-------|
| `includes/` | Helpers | Read-only audit helpers vs operational helpers separated by phase |
| `assets/moghare360-ui/` | CSS | RTL Persian, no external CDN dependency |
| `assets/moghare360-brand/` | Brand | Owner-provided Moghareh Motors logo only |
| `sql/sqlserver/` | Migrations | Idempotent scripts; no DROP without approval |
| `submit-*.php` | Write routes | POST + auth + permission + CSRF + workflow |
| `erp-*.php` | Read/report | Prefer read-only for audit layers |

**Rule:** No direct database write from UI pages — writes go through submit routes and engines.

---

## private Config Boundary

- `private/erp-config.php` — local server only, never in ZIP packages
- Not displayed in audit pages
- Gitignored / excluded from release copy
- Agents must not copy or commit real credentials

---

## docs Boundary

| Subfolder | Content |
|-----------|---------|
| `docs/master/` | Locked prompt + construction blueprint |
| `docs/missions/` | Per-phase scope, test result, signoff |
| `docs/product/` | Language guide, copyright policy |
| `docs/deployment/` | Deployment planning (Phase 14) |
| `docs/release/` | Package manifests |

---

## tools Boundary

- `test-phase-*.php` — phase validation CLI
- `package-moghare360-*.ps1` — controlled ZIP build (Phase 15)
- No auto deploy scripts that overwrite production config

---

## sql Boundary (Future Only)

- Location: `public_html/sql/sqlserver/`
- Execution: SSMS against `moghare360_ERP` in controlled phases only
- This MASTER pack does **not** create SQL files

---

## release Boundary

- `release/moghare360-demo-package.zip`
- `release/moghare360-local-rc1.zip`
- `public_html/release/` — web-download copies only
- Excluded from nested local package (`public_html/release/` not inside RC1 ZIP)

---

## Forbidden Folder / File Behavior

Do not create or modify in implementation phases without explicit mission scope:

- `staff-auth.php`, `access-control.php`, `staff-login.php`
- `config.php`, `config.example.php` at repo root
- Legacy customer portal paths
- `uploads/` with real customer data in packages
- `logs/`, `backups/` in distributable artifacts

---

## This Phase Constraint

**No file creation outside `docs/master/` in the MASTER EXECUTION PACK phase.**

---

**Product Boundary:** Documentation only · No SQL required
