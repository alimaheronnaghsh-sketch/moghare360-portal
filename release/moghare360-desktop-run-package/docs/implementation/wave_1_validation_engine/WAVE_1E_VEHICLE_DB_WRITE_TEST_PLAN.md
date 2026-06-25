# WAVE 1E — Vehicle DB Write Test Plan

**Wave:** IMPLEMENTATION WAVE 1E  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-1e-vehicle-db-write.php`

**Pass:** `WAVE 1E VEHICLE DB WRITE TEST PASSED` + `DB_WRITE_ACTIVATED_FOR_VEHICLE_V2`

---

## Browser Tests

| Case | Expected |
|------|----------|
| Invalid vehicle POST | Persian validation errors, no DB |
| Valid vehicle POST | Result page success or controlled blocked message |
| Customer v2 POST | Still writes to `erp_customers` |
| JobCard v2 POST | Wave 1C DB-disabled message |

**URL:** `http://localhost:8080/moghare360/erp-vehicle-create-v2.php`

---

**END OF TEST PLAN**
