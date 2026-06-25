# MOGHARE360 — No-Cloud Storage Boundary

**Status:** LOCKED — PHASE 16  
**SQL:** No SQL required

---

## Principle

**All data lives only on local laptop server.**  
**No cloud database.**  
**Domain mirror cannot become data storage.**

---

## Forbidden Cloud / Host Storage

| Asset | Rule |
|-------|------|
| **No cloud database** | MOGHARE360_ERP must not run on Azure SQL, RDS, etc. |
| **No host-side customer data** | moghareh360.ir must not store customer records |
| **No host-side media storage** | Vehicle photos stay local — **Camera direct only** capture to local path |
| **No host-side diagnostic PDF storage** | Initial/secondary/final PDFs local only |
| **No host-side HR document storage** | HR files local only |
| Cloud object storage (S3, Blob) | FORBIDDEN without explicit owner phase |
| SaaS file hosting | FORBIDDEN |

---

## Local-Only Data Ownership

| Data class | Storage location |
|------------|------------------|
| SQL business data | `MOGHARE360_ERP` on `.\SQLEXPRESS` |
| Runtime config | `private/erp-config.php` (local) |
| Uploads / media | Local filesystem under workshop server paths |
| Diagnostic PDFs | Local filesystem |
| Backups | Owner-controlled local or physical media |
| Release ZIPs | Local `release/` — not domain |

---

## Media Boundary

- **Camera direct only** — images written to local server paths
- **No upload bypass** to cloud or domain
- **No upload bypass** to external file hosts

---

## Backup Policy

- **Backup must be owner-controlled**
- Backups contain full business data — treat as sensitive as production DB
- Backups must not be uploaded to public cloud without encryption + owner approval
- Git repository must not contain real customer PII or `erp-config.php`

---

## Domain Mirror Rule

**Domain mirror cannot become data storage.**

Even if moghareh360.ir has disk space and PHP/MySQL available on host:

- Do not create MOGHARE360_ERP on host
- Do not sync customer/jobcard tables to host
- Do not use host MySQL as read replica
- Static cache pages must not embed live PII

---

## Architecture Reference

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Persistence layer is **local SQL Server only**.

---

## Product Boundary

- **No production SaaS activation**
- **No public customer portal activation** until Phase 22
- **No official accounting activation** until Phase 23

---

**END OF NO-CLOUD STORAGE BOUNDARY**
