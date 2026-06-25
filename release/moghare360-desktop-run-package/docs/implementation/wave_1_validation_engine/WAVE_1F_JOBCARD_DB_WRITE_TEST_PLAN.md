# WAVE 1F — JobCard DB Write Test Plan

**Wave:** IMPLEMENTATION WAVE 1F  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-1f-jobcard-db-write.php`

**Pass:** `WAVE 1F JOBCARD DB WRITE TEST PASSED` + `DB_WRITE_ACTIVATED_FOR_JOBCARD_V2`

---

## Browser Tests

| Case | Expected |
|------|----------|
| Invalid JobCard POST | Validation errors, no DB |
| Valid JobCard POST | Result page — success, blocked, or reference error (no fake success) |
| Customer v2 POST | Still active |
| Vehicle v2 POST | Still active |

**URL:** `http://localhost:8080/moghare360/erp-jobcard-create-v2.php`

---

**END OF TEST PLAN**
