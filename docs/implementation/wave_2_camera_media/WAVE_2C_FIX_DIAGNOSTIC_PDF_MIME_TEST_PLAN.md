# WAVE 2C-FIX — Diagnostic PDF MIME Test Plan

**Wave:** WAVE 2C-FIX  
**Date:** 2026-06-23

---

## CLI Static Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2c-fix-diagnostic-pdf-mime.php`

### Required Checks

| # | Check |
|---|-------|
| 1 | SQL file exists |
| 2 | `CK_erp_jobcard_media_mime` present |
| 3 | `application/pdf` allowed |
| 4 | Image MIME types retained |
| 5 | DROP + ADD constraint pattern |
| 6 | Safe table/column preflight |
| 7 | No DROP TABLE / ALTER DATABASE |
| 8 | Source/capture_method constraints untouched |
| 9 | No external URL enablement |
| 10 | Docs exist |

**Expected:** `WAVE 2C-FIX DIAGNOSTIC PDF MIME TEST PASSED`

---

## Manual SSMS Execution (User)

1. Open `public_html/sql/wave_2c_fix_diagnostic_pdf_mime_constraint.sql`
2. Execute against `MOGHARE360_ERP`
3. Confirm status: `WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_READY`
4. Re-test diagnostic PDF upload via `submit-jobcard-diagnostic-file.php`

---

**END OF TEST PLAN**
