# MOGHARE360 — Master 08 Local Deployment Plan

**Status:** Deployment planning only — Documentation only  
**SQL:** Not required — no installer created

---

## Purpose

Plan local deployment topology for MOGHARE360 ERP development and demo. No auto deployment. No production installer in this phase.

---

## Local Server Topology

| Component | Target |
|-----------|--------|
| Local Server | Laptop (developer machine) |
| Web server | XAMPP / Apache + PHP |
| Database | SQL Server Express (`.\SQLEXPRESS`) |
| Database name | `moghare360_ERP` |
| URL | `http://localhost:8080/moghare360/` |
| Deploy path | `C:\xampp\htdocs\moghare360` |

---

## Stack Boundaries

### SQL Server (Local)

- Single local instance
- Schema applied via phased SSMS scripts (not in this pack)
- Backups: local disk only per Phase 14 deployment docs

### PHP Backend (Local)

- Copy/sync from repo `public_html/` to htdocs
- `private/erp-config.php` configured locally only
- No cloud-hosted PHP

### No Cloud Storage

- No S3, Azure Blob, or external file hosting
- Media stays on local server filesystem
- Release ZIPs built locally via `tools/package-moghare360-*.ps1`

---

## Data Residency

**No business data outside local server.**

- No sync to moghareh360.ir in current phase
- No production SaaS activation
- Demo packages exclude real PII and private config

---

## Deployment Steps (Manual — Future)

1. Install XAMPP + SQL Server Express
2. Create database `moghare360_ERP`
3. Run phased SQL in SSMS (when authorized)
4. Copy `public_html` to htdocs
5. Create `private/erp-config.php` from example
6. Run `tools/test-phase-*.php` for validation
7. Access `http://localhost:8080/moghare360/`

**No auto deployment.** **No installer created** in MASTER EXECUTION PACK.

---

## Release Package Reference

| Package | Purpose |
|---------|---------|
| `moghare360-demo-package.zip` | Demo / training |
| `moghare360-local-rc1.zip` | Local RC1 without secrets |

Built by Phase 15 scripts; not redeployed automatically.

---

## Product Boundary

- Documentation only
- Deployment planning only
- No installer created
- No auto deployment
- No production SaaS activation

---

**END OF LOCAL DEPLOYMENT PLAN**
