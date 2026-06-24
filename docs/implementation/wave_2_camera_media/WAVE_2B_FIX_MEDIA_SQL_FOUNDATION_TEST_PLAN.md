# WAVE 2B-FIX — Media SQL Foundation Test Plan

**Wave:** WAVE 2B-FIX  
**Date:** 2026-06-23

---

## CLI Static Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2b-fix-media-sql-foundation.php`

### Required Checks

| # | Check |
|---|-------|
| 1 | SQL file exists |
| 2 | SQL contains `dbo.erp_jobcard_media` |
| 3 | SQL contains `dbo.erp_jobcard_media_history` |
| 4 | SQL contains `FK_erp_jobcard_media_jobcard` |
| 5 | CHECK constraints for `media_stage` and `media_type` |
| 6 | `CAMERA_ONLY` source constraint |
| 7 | `BROWSER_CAMERA` capture method constraint |
| 8 | No public/external URL field activation |
| 9 | No `DROP TABLE` |
| 10 | No `ALTER DATABASE` |
| 11 | No auth/config changes |
| 12 | Docs exist |

**Expected:** `WAVE 2B-FIX MEDIA SQL FOUNDATION TEST PASSED`

---

## Manual SSMS Execution (User)

After Cursor delivery, user runs in SSMS:

1. Open `public_html/sql/wave_2b_fix_jobcard_media_metadata.sql`
2. Execute against `MOGHARE360_ERP`
3. Confirm output status: `WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_READY`
4. Verify `erp_jobcard_media` and `erp_jobcard_media_history` object IDs are non-null

---

## Out of Scope

- Cursor does not execute SQL
- Cursor does not modify PHP metadata binding in this fix
- Browser/runtime tests deferred until post-SSMS activation wave

---

**END OF TEST PLAN**
