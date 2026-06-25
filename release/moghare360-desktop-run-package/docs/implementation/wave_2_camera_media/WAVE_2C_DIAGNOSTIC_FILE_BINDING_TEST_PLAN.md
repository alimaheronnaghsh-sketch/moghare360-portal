# WAVE 2C — Diagnostic File Binding Test Plan

**Wave:** IMPLEMENTATION WAVE 2C  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2c-diagnostic-file-binding.php`

### Required Checks

| # | Check |
|---|-------|
| 1 | Diagnostic helper exists |
| 2 | Diagnostic page/submit/preview exist |
| 3 | Helper allows pdf/jpg/jpeg/png/webp only |
| 4 | Helper rejects exe/zip/rar/docm/url/path traversal |
| 5 | Submit uses metadata registration after local save |
| 6 | Helper writes `DIAGNOSTIC_FILE_REGISTERED` history |
| 7 | Camera capture files unchanged |
| 8 | No Wave 2C SQL files |
| 9 | Docs exist |

**Expected:** `WAVE 2C DIAGNOSTIC FILE BINDING TEST PASSED`

---

## Browser Test

Copy `public_html/` to `C:\xampp\htdocs\moghare360\` then:

| URL | Check |
|-----|-------|
| `/erp-jobcard-diagnostic-file.php` | Controlled diagnostic upload page |
| `/submit-jobcard-diagnostic-file.php` | POST valid PDF/image |
| `/erp-jobcard-diagnostic-preview.php?jobcard_id=1` | Local + DB metadata |
| `/erp-jobcard-camera-capture.php` | Still no file input |

---

## Manual Validation

- Upload valid diagnostic PDF or image
- Metadata DB row created in `erp_jobcard_media`
- History row created in `erp_jobcard_media_history`
- Invalid file type rejected
- External URL rejected
- Camera capture page unchanged

---

**END OF TEST PLAN**
