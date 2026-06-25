# WAVE 3C — Contract Authorization Gate Test Plan

**Wave:** IMPLEMENTATION WAVE 3C  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-3c-contract-authorization-gate.php`

**Expected:** `WAVE 3C CONTRACT AUTHORIZATION GATE TEST PASSED`

---

## Browser Tests

| URL | Check |
|-----|-------|
| `/erp-jobcard-authorization-gate.php?jobcard_id=1` | Gate status from live DB |
| `/erp-jobcard-authorization-gate.php?jobcard_id=abc` | Invalid ID error |
| `/erp-jobcard-contract-authorization-preview.php?jobcard_id=1` | Gate link |

---

**END OF TEST PLAN**
