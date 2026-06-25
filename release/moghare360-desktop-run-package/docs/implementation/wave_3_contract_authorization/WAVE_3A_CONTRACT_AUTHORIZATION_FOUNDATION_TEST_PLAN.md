# WAVE 3A — Contract Authorization Foundation Test Plan

**Wave:** IMPLEMENTATION WAVE 3A  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-3a-contract-authorization-foundation.php`

**Expected:** `WAVE 3A CONTRACT AUTHORIZATION FOUNDATION TEST PASSED`

---

## Browser Tests

| URL | Check |
|-----|-------|
| `/erp-jobcard-contract-authorization.php` | Form loads, disclaimer visible, no file input |
| `/erp-jobcard-contract-authorization-preview.php?jobcard_id=1` | Preview or controlled block |

---

## Manual Scenarios

1. Invalid payload (bad mobile, empty name) → validation error
2. Valid payload + schema BLOCKED → controlled block message
3. Valid payload + schema READY (after SSMS) → insert + preview list

---

**END OF TEST PLAN**
