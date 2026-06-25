# WAVE 2D — JobCard Evidence Gate Test Plan

**Wave:** IMPLEMENTATION WAVE 2D  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2d-jobcard-evidence-gate.php`

**Expected:** `WAVE 2D JOBCARD EVIDENCE GATE TEST PASSED`

---

## Browser Test

| URL | Check |
|-----|-------|
| `/erp-jobcard-evidence-review.php?jobcard_id=1` | Status, counts, required/found/missing |
| `/erp-jobcard-evidence-review.php?jobcard_id=abc` | Controlled invalid ID error |

---

## Manual Validation

- JobCard with media/diagnostic → COMPLETE or PARTIAL
- JobCard without media → EMPTY
- Invalid `jobcard_id` → controlled error
- Links to capture/preview/diagnostic pages work
- No upload/file input on review page
- No DB write

---

**END OF TEST PLAN**
