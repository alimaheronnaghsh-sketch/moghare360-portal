# WAVE 2B-FIX — Media SQL Foundation Scope

**Wave:** WAVE 2B-FIX — JobCard Media Metadata SQL Foundation  
**Parent:** IMPLEMENTATION WAVE 2 — Camera-only Media + Diagnostic Binding  
**Date:** 2026-06-23  
**Database:** MOGHARE360_ERP

---

## Objective

Prepare the official SQL Server foundation for JobCard media metadata so WAVE 2B metadata DB binding can be safely activated after the user executes SQL manually in SSMS.

---

## Deliverable

| Item | Path |
|------|------|
| SQL foundation script | `public_html/sql/wave_2b_fix_jobcard_media_metadata.sql` |
| Static CLI test | `tools/test-wave-2b-fix-media-sql-foundation.php` |

---

## Tables Prepared

| Table | Purpose |
|-------|---------|
| `dbo.erp_jobcard_media` | JobCard media metadata (camera-only, local paths) |
| `dbo.erp_jobcard_media_history` | Audit/history events for media lifecycle |

---

## Boundaries

- SQL prepared only — **not executed by Cursor**
- User must execute manually in SQL Server / SSMS
- No runtime PHP modified in this fix
- No metadata binding activation in this fix
- No diagnostic binding activation
- No upload bypass / no file input
- No public portal / SaaS / accounting / payment gateway
- No auth / config / permission changes
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
