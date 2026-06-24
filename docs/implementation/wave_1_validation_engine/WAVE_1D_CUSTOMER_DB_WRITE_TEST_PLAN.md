# WAVE 1D — Customer DB Write Test Plan

**Wave:** IMPLEMENTATION WAVE 1D  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-1d-customer-db-write.php`

Checks:

- Wave 1D files exist (submit, result, helper, bridge)
- Customer submit validates before `moghare360_customer_v2_write`
- Helper uses `customer_core_execute` (prepared statements)
- `INSERT INTO dbo.erp_customers` present
- Vehicle/JobCard submits still show Wave 1C DB-disabled message
- No SQL files in Wave 1D deliverables
- Documentation files exist

**Pass:** `WAVE 1D CUSTOMER DB WRITE TEST PASSED` + `DB_WRITE_ACTIVATED_FOR_CUSTOMER_V2`

---

## Browser Tests

| URL | Action | Expected |
|-----|--------|----------|
| `erp-customer-create-v2.php` | Invalid POST | Validation errors, no DB |
| `erp-customer-create-v2.php` | Valid POST | Redirect to result; success or blocked message |
| `submit-vehicle-v2.php` | Any valid POST | Wave 1C DB-disabled preview |
| `submit-jobcard-v2.php` | Any valid POST | Wave 1C DB-disabled preview |

---

## Runtime DB Test (manual, local SQL Server)

1. Ensure `erp_customers` exists with required columns
2. Submit valid customer (Persian name + `09121234567`)
3. Verify row in `erp_customers` and optional `erp_customer_phones`
4. Verify `erp_customer_core_history` row if table exists

---

**END OF TEST PLAN**
